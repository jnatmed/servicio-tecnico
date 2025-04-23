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
    public function existe($username)
    {
        try {
            // Ejecutar la consulta para verificar si el usuario existe
            $result = $this->queryBuilder->select('usuarios', '*', [
                'usuario' => $username
            ]);
    
            // Verificar si se obtuvo algún resultado
            if (!empty($result)) {
                // Registrar el resultado para depuración
                $this->logger->info("(metodo - Existe) Usuario encontrado: ", [$username]);
                return [true, $result[0]['id']];
            } else {
                // Registrar un mensaje de error si no se obtuvo ningún resultado
                $this->logger->error("No se encontró un usuario con el nombre proporcionado: {$username}");
                return [false, null];
            }
        } catch (\Exception $e) {
            // Manejo de errores
            $this->logger->error("Error en getUserByName: ", [$e->getMessage(), $username]);
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
            // Ejecutar la consulta usando el ID del usuario
            $result = $this->queryBuilder->select($this->table, '*', ['id' => $id]);

            if ($result) {
                // Registrar el resultado para depuración
                $this->logger->info("Usuario encontrado: ", [$result]);
                return $result[0]; // Asumimos que el ID es único y devuelve un solo resultado
            } else {
                $this->logger->info("No se encontró un usuario con el ID proporcionado.");
                return null;
            }
        } catch (\Exception $e) {
            $this->logger->error("Error en getUserById: ", [$e->getMessage()]);
            return null;
        }
    }

    public function actualizarDependenciaUsuario($usuarioId, $dependenciaId, $ordenativaFunciona)
    {
        $sql = "UPDATE usuarios 
                SET dependencia_id = :dep, 
                    ordenativa_funcion = :ordFun 
                WHERE id = :id";
    
        $this->queryBuilder->query($sql, [
            ':dep' => $dependenciaId ?: null,
            ':ordFun' => $ordenativaFunciona,
            ':id' => $usuarioId
        ]);
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