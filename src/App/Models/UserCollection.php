<?php 

namespace Paw\App\Models;

use Paw\Core\Model;


use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class UserCollection extends Model
{
    use Loggable;

    protected $table = 'usuarios';  // Nombre de la tabla de usuarios en la base de datos


    // Ejemplo de método para verificar la existencia de un usuario por nombre de usuario y contraseña
    public function getUserByUsernameAndPassword($username, $password)
    {
        try {
            // Ejecutar la consulta usando los parámetros directamente
            $result = $this->queryBuilder->select('usuarios', '*', [
                'usuario' => $username
            ]);
    
            $result = $result[0];
            // Verificar el resultado de la consulta
            if ($result) {
                // Registrar el resultado para depuración
                $this->logger->info("Resultado de la consulta: ", [$result]);
    
                // Verificar si el campo 'contrasenia' existe en el resultado
                if (isset($result['contrasenia'])) {
                    if (password_verify($password, $result['contrasenia'])) {
                        $this->logger->info("Usuario ".$result["usuario"]." logueado con exito", [$result['usuario'], $result['tipo_usuario']]);
                        return $result;
                    }
                } else {
                    // Registrar un mensaje de error si el campo 'contrasenia' no existe
                    $this->logger->error("El campo 'contrasenia' no existe en el resultado.");
                }
            } else {
                // Registrar un mensaje de error si no se obtuvo ningún resultado
                $this->logger->error("No se encontró un usuario con el nombre de usuario proporcionado.");
            }
    
            return null;
        } catch (\Exception $e) {
            // Manejo de errores (puedes implementar según necesites)
            $this->logger->error("Error en getUserByUsernameAndPassword: ", [$e->getMessage()]);
            return null;
        }
    }

    public function listarUsuariosConRolesYDependencia()
    {
        $this->logger->info('📥 listarUsuariosConRolesYDependencia() - Inicio del método');

        try {
            $sql = "
                SELECT 
                    u.id,
                    u.usuario,
                    u.email,
                    u.tipo_usuario,
                    u.created_at,
                    u.updated_at,
                    r.nombre AS rol,
                    d.nombre_dependencia AS dependencia,
                    u.ordenativa_funcion
                FROM usuarios u
                LEFT JOIN roles r ON u.rol_id = r.id
                LEFT JOIN dependencia d ON u.dependencia_id = d.id
                ORDER BY u.id DESC
            ";

            $resultado = $this->queryBuilder->query($sql);
            $this->logger->info('✅ listarUsuariosConRolesYDependencia() - Consulta ejecutada correctamente', ['total_resultados' => count($resultado)]);
            return $resultado;

        } catch (\Exception $e) {
            $this->logger->error('❌ listarUsuariosConRolesYDependencia() - Error al obtener usuarios', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }


    public function buscarUsuarios(string $search = '', int $limit = 10, int $offset = 0): array
    {
        $this->logger->info('📄 buscarUsuarios() - Iniciando consulta de usuarios');

        try {
            $sql = "
                SELECT 
                    u.id,
                    u.usuario,
                    u.email,
                    u.tipo_usuario,
                    u.created_at,
                    u.updated_at,
                    r.nombre AS rol,
                    d.descripcion AS dependencia,
                    sad.estado AS estado_dependencia
                FROM usuarios u
                LEFT JOIN roles r ON u.rol_id = r.id
                LEFT JOIN (
                    SELECT s1.*
                    FROM solicitud_asignacion_dependencia s1
                    INNER JOIN (
                        SELECT usuario_id, MAX(fecha_solicitud) AS ultima
                        FROM solicitud_asignacion_dependencia
                        GROUP BY usuario_id
                    ) s2 ON s1.usuario_id = s2.usuario_id AND s1.fecha_solicitud = s2.ultima
                ) sad ON sad.usuario_id = u.id
                LEFT JOIN dependencia d ON sad.dependencia_id = d.id
            ";

            $params = [];
            if ($search !== '') {
                $sql .= " WHERE u.usuario LIKE :search OR u.email LIKE :search";
                $params['search'] = "%{$search}%";
            }

            $sql .= " ORDER BY u.id DESC LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;

            return $this->queryBuilder->query($sql, $params);

        } catch (\Exception $e) {
            $this->logger->error('❌ buscarUsuarios() - Error al consultar usuarios', [
                'mensaje' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function confirmarAsignacionDeDependencia(int $usuarioId, string $observaciones = '')
    {
        try {
            $this->logger->info("✅ Confirmando asignación para usuario $usuarioId");

            // 1. Obtener ID de la última solicitud
            $sql = "SELECT id FROM solicitud_asignacion_dependencia
                    WHERE usuario_id = :usuario_id
                    ORDER BY fecha_solicitud DESC
                    LIMIT 1";

            $resultado = $this->queryBuilder->query($sql, ['usuario_id' => $usuarioId]);
            $solicitudId = $resultado[0]['id'] ?? null;

            if (!$solicitudId) {
                throw new Exception("No se encontró solicitud activa para el usuario.");
            }

            // 2. Confirmar solicitud
            $sqlUpdate = "
                UPDATE solicitud_asignacion_dependencia
                SET estado = 'confirmado',
                    fecha_resolucion = CURRENT_TIMESTAMP,
                    observaciones = :obs
                WHERE id = :id
            ";

            $this->queryBuilder->query($sqlUpdate, [
                'id' => $solicitudId,
                'obs' => $observaciones
            ]);

            $this->logger->info("✅ Solicitud confirmada correctamente para usuario $usuarioId");

        } catch (\Exception $e) {
            $this->logger->error("❌ Error al confirmar solicitud: " . $e->getMessage());
            throw $e;
        }
    }


    public function rechazarAsignacionDeDependencia(int $usuarioId, string $observaciones = '')
    {
        try {
            $this->logger->info("🚫 Rechazando asignación para usuario $usuarioId");

            // 1. Obtener ID de la última solicitud
            $sql = "SELECT id FROM solicitud_asignacion_dependencia
                    WHERE usuario_id = :usuario_id
                    ORDER BY fecha_solicitud DESC
                    LIMIT 1";

            $resultado = $this->queryBuilder->query($sql, ['usuario_id' => $usuarioId]);
            $solicitudId = $resultado[0]['id'] ?? null;

            if (!$solicitudId) {
                throw new Exception("No se encontró solicitud activa para el usuario.");
            }

            // 2. Rechazar solicitud
            $sqlUpdate = "
                UPDATE solicitud_asignacion_dependencia
                SET estado = 'rechazado',
                    fecha_resolucion = CURRENT_TIMESTAMP,
                    observaciones = :obs
                WHERE id = :id
            ";

            $this->queryBuilder->query($sqlUpdate, [
                'id' => $solicitudId,
                'obs' => $observaciones
            ]);

            $this->logger->info("✅ Solicitud rechazada correctamente para usuario $usuarioId");

        } catch (\Exception $e) {
            $this->logger->error("❌ Error al rechazar solicitud: " . $e->getMessage());
            throw $e;
        }
    }


    public function actualizarRolDeUsuario(int $usuarioId, int $nuevoRolId): void
    {
        $this->logger->info("🔧 Actualizando rol del usuario ID $usuarioId a rol ID $nuevoRolId");

        try {
            $sql = "UPDATE usuarios SET rol_id = :nuevoRol WHERE id = :id";
            $params = [
                'nuevoRol' => $nuevoRolId,
                'id' => $usuarioId
            ];

            $this->queryBuilder->query($sql, $params);
            $this->logger->info("✅ Rol actualizado en la base de datos");

        } catch (\Exception $e) {
            $this->logger->error("❌ Error al actualizar el rol en la base: " . $e->getMessage());
            throw $e;
        }
    }

    public function contarUsuarios(string $search = ''): int
    {
        $this->logger->info('🔢 contarUsuarios() - Contando registros');

        try {
            $sql = "SELECT COUNT(*) as total FROM usuarios u";
            $params = [];

            if ($search !== '') {
                $sql .= " WHERE u.usuario LIKE :search OR u.email LIKE :search";
                $params['search'] = "%{$search}%";
            }

            $result = $this->queryBuilder->query($sql, $params);
            return $result[0]['total'] ?? 0;

        } catch (\Exception $e) {
            $this->logger->error('❌ contarUsuarios() - Error al contar', [
                'mensaje' => $e->getMessage()
            ]);
            return 0;
        }
    }


    public function existe($username)
    {
        try {
            // 1. Buscar al usuario junto con su rol
            $sql = "
                SELECT u.*, r.nombre AS rol
                FROM usuarios u
                LEFT JOIN roles r ON u.rol_id = r.id
                WHERE u.usuario = :username
                LIMIT 1
            ";

            $result = $this->queryBuilder->query($sql, ['username' => $username]);

            if (empty($result)) {
                $this->logger->error("No se encontró un usuario con el nombre proporcionado: {$username}");
                return [false, null];
            }

            $usuario = $result[0];
            $usuarioId = $usuario['id'];

            // 2. Buscar la última solicitud confirmada
            $sqlDep = "
                SELECT d.descripcion AS dependencia, s.dependencia_id
                FROM solicitud_asignacion_dependencia s
                LEFT JOIN dependencia d ON s.dependencia_id = d.id
                WHERE s.usuario_id = :usuario_id AND s.estado = 'confirmado'
                ORDER BY s.fecha_solicitud DESC
                LIMIT 1
            ";

            $dep = $this->queryBuilder->query($sqlDep, ['usuario_id' => $usuarioId]);

            if (!empty($dep)) {
                $usuario['dependencia_confirmada'] = $dep[0]['dependencia'];
                $usuario['dependencia_id'] = $dep[0]['dependencia_id'];
            } else {
                $usuario['dependencia_confirmada'] = null;
                $usuario['dependencia_id'] = null;
            }

            $this->logger->info("(metodo - Existe) Usuario encontrado: ", [$username]);

            return [true, $usuarioId, $usuario];

        } catch (\Exception $e) {
            $this->logger->error("Error en existe(): ", [$e->getMessage(), $username]);
            return false;
        }
    }



    public function guardarNuevoAcceso($username, $userInfo)
    {
        try {

            $data = arrayExtractData($userInfo, ['account', 'group', 'email'], ['usuario', 'tipo_usuario', 'email']);

            $existeUsuario =  $this->existe($username);
            if ($existeUsuario[0]) {
                $this->logger->info("El usuario ya existe, no se realizará la inserción: {$username}", [$existeUsuario[0], not($existeUsuario[0])]);
                return false;
            }
    
            // Insertar un nuevo usuario
            $insertResult = $this->queryBuilder->insert('usuarios', $data, $username);
    
            if ($insertResult) {
                $this->logger->info("Nuevo usuario insertado correctamente: {$username}", [$insertResult]);
                return $insertResult[0];
            } else {
                $this->logger->error("No se pudo insertar el nuevo usuario: {$username}");
                return false;
            }
        } catch (\Exception $e) {
            // Manejo de errores
            $this->logger->error("Error en guardarNuevoAcceso: ", [$e->getMessage()]);
            return false;
        }
    }
    

    public function getUserById($id)
    {
        try {
            $this->logger->info("🔍 getUserById() - Buscando usuario con ID $id");

            $sql = "
                SELECT 
                    u.*,
                    d.descripcion AS dependencia_descripcion,
                    s.dependencia_id,
                    s.estado AS estado_solicitud,
                    s.fecha_solicitud,
                    s.fecha_resolucion,
                    s.observaciones
                FROM usuarios u
                LEFT JOIN (
                    SELECT *
                    FROM solicitud_asignacion_dependencia
                    WHERE usuario_id = :id
                    ORDER BY fecha_solicitud DESC
                    LIMIT 1
                ) s ON s.usuario_id = u.id
                LEFT JOIN dependencia d ON d.id = s.dependencia_id
                WHERE u.id = :id
            ";

            $resultado = $this->queryBuilder->query($sql, ['id' => $id]);

            if (!empty($resultado)) {
                $this->logger->info("✅ Usuario con solicitud encontrada:", [$resultado[0]]);
                return $resultado[0];
            } else {
                $this->logger->info("ℹ️ Usuario no encontrado.");
                return null;
            }

        } catch (\Exception $e) {
            $this->logger->error("❌ Error en getUserById: " . $e->getMessage());
            return null;
        }
    }



    public function solicitarAsignacionDependencia($usuarioId, $dependenciaId, $ordenativaFunciona)
    {
        try {
            $this->logger->info("📝 solicitando asignación de dependencia", [
                'usuario_id' => $usuarioId,
                'dependencia_id' => $dependenciaId,
                'ordenativa_funcion' => $ordenativaFunciona
            ]);

            $sql = "
                INSERT INTO solicitud_asignacion_dependencia (
                    usuario_id,
                    dependencia_id,
                    estado,
                    fecha_solicitud,
                    observaciones
                ) VALUES (
                    :usuario_id,
                    :dependencia_id,
                    'solicitado',
                    CURRENT_TIMESTAMP,
                    :observaciones
                )
            ";

            $observaciones = "Ordenativa: {$ordenativaFunciona}";

            $this->queryBuilder->query($sql, [
                'usuario_id' => $usuarioId,
                'dependencia_id' => $dependenciaId,
                'observaciones' => $observaciones
            ]);

            $this->logger->info("✅ Solicitud registrada correctamente");

        } catch (\Exception $e) {
            $this->logger->error("❌ Error al registrar solicitud: " . $e->getMessage());
            throw $e;
        }
    }

    

    public function getNombrePorId($id)
    {
        $sql = "SELECT descripcion FROM dependencia WHERE id = :id";
        $resultados = $this->queryBuilder->query($sql, [':id' => $id]);
    
        foreach ($resultados as $fila) {
            return $fila['descripcion'];
        }
    
        return null;
    }
    
    
    
}