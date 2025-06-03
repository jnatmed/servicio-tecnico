<?php 

namespace Paw\Core\Database;

use PDO;
use Monolog\Logger;
use Exception;
use PDOException;

class QueryBuilder 
{
    public PDO $pdo;
    public Logger $logger;
    private $lastQuery;
    public $request;

    public function __construct(PDO $pdo, ?Logger $logger = null)
    {   
        global $request;
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->request = $request;
    }

    public function select($table, $columns = '*', $params = [])
    {
        try {
            $this->logger->info("params : ", [$params]);

            $whereClauses = [];
            $bindings = [];
    
            // Construir las cláusulas WHERE y los parámetros de enlace
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $whereClauses[] = "$key = :$key";
                    $bindings[":$key"] = $value;
                }
            }
    
            $this->logger->info("whereClauses : ", [$whereClauses]);
            
            // Unir las cláusulas WHERE con AND
            $where = implode(' AND ', $whereClauses);
            $query = "SELECT $columns FROM $table";
    
            $this->logger->info("query: $query");

            if (!empty($whereClauses)) {
                $query .= " WHERE $where";
            }
    
            // Preparar la sentencia
            $sentencia = $this->pdo->prepare($query);
    
            // Enlazar los valores de los parámetros
            foreach ($bindings as $key => $value) {
                $sentencia->bindValue($key, $value);
            }
    
            // Establecer el modo de obtención y ejecutar la consulta
            $sentencia->setFetchMode(PDO::FETCH_ASSOC);
            $sentencia->execute();
            
            $result = $sentencia->fetchAll();

            $this->logger->info("result: ", [$result]);
            // Retornar todos los resultados
            return $result;
            
        } catch (PDOException $e) {
            // Manejar la excepción de la base de datos
            $this->logger->error('Database error: ' . $e->getMessage());
            throw new Exception('Error al realizar la consulta en la base de datos');
        } catch (Exception $e) {
            // Manejar otras excepciones
            $this->logger->error('General error: ' . $e->getMessage());
            throw new Exception('Ocurrió un error inesperado');
        }
    }
    
    public function selectAdHoc($table, $columns = '*', $searchField = null, $searchValue = null, $fieldsToSearch = [])
    {
        try {
            $this->logger->info("searchValue: ", [$searchValue]);
    
            // Verificar si la consulta es sobre la tabla 'agente' para incluir el JOIN
            if ($table === 'agente') {
                $query = "
                    SELECT agente.*, dependencia.descripcion AS descripcion_dependencia 
                          FROM agente 
                          LEFT JOIN dependencia ON agente.dependencia = dependencia.id
                    ";
            } else {
                $query = "SELECT $columns FROM $table";
            }
    
            $bindings = [];
    
            // Si hay un valor de búsqueda y una lista de campos para buscar
            if (!empty($searchValue) && !empty($fieldsToSearch)) {
                $likeClauses = [];
                foreach ($fieldsToSearch as $field) {
                    if ($field === 'id' && is_numeric($searchValue)) {
                        $likeClauses[] = "agente.$field = :searchValue";
                    } else {
                        $likeClauses[] = "$field LIKE :searchValue";
                    }
                }
                
                $query .= " WHERE " . implode(' OR ', $likeClauses);
                $bindings[":searchValue"] = "%$searchValue%";
            }
    
            $this->logger->info("query: $query");
    
            // Preparar la sentencia
            $stmt = $this->pdo->prepare($query);
    
            // Enlazar el valor de búsqueda
            foreach ($bindings as $key => $value) {
                if ($searchField === 'id') {
                    $stmt->bindValue(':searchValue', (int) $searchValue, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':searchValue', "%$searchValue%", PDO::PARAM_STR);
                }
            }
            
    
            // Ejecutar y obtener resultados
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute();
            $result = $stmt->fetchAll();
    
            $this->logger->info("result: ", [$result]);
    
            return $result;
    
        } catch (PDOException $e) {
            $this->logger->error('Database error: ' . $e->getMessage());
            throw new Exception('Error al realizar la consulta en la base de datos');
        } catch (Exception $e) {
            $this->logger->error('General error: ' . $e->getMessage());
            throw new Exception('Ocurrió un error inesperado');
        }
    }
    
        

    public function insert($table, $data, $username = null)
    {
        try {

            // Verificar si $data es un objeto y tiene el método toArray
            if (is_object($data)) {
                if (method_exists($data, 'toArray')) {
                    $data = $data->toArray();
                } else {
                    $this->logger->error("El objeto de tipo " . get_class($data) . " no tiene el método toArray.", [$data]);
                    throw new Exception("El objeto de tipo " . get_class($data) . " no tiene el método toArray.");
                }
            }

            // Validar que $data sea un array después de la conversión
            if (!is_array($data)) {
                $this->logger->error("Los datos proporcionados no son un array válido.", [$data]);
                throw new Exception("Los datos proporcionados no son un array válido.");
            }
    
            $columnas = implode(', ', array_keys($data));
            $valores = ':' . implode(', :', array_keys($data));
            $query = "INSERT INTO $table ($columnas) VALUES ($valores)";
            $sentencia = $this->pdo->prepare($query);
    
            // Asignar valores a los parámetros
            foreach ($data as $clave => $valor) {
                $sentencia->bindValue(":$clave", $valor);
            }
    
            // Ejecutar la consulta
            $resultado = $sentencia->execute();
            $idGenerado = $this->pdo->lastInsertId();
    
            // Registrar en la auditoría
            $this->registrarAuditoria($table, 'INSERT', $username, null, $data, $idGenerado);
    
            return [$idGenerado, $resultado];
    
        } catch (PDOException $e) {
            // Error específico de PDO (errores de base de datos)
            $this->logger->error("Error de base de datos en insert: " . $e->getMessage());
            throw new Exception("Error al insertar en la base de datos. Contacte con el administrador." . $e->getMessage());
    
        } catch (Exception $e) {
            // Cualquier otro error
            $this->logger->error("Error general en insert: " . $e->getMessage());
            throw new Exception("Ocurrió un error inesperado. Contacte con el administrador." . $e->getMessage());
        }
    }
    
    

    public function update($table, $data, $conditions = [])
    {
        $setValues = [];
        foreach ($data as $key => $value) {
            $setValues[] = "$key = :$key";
        }
        $setString = implode(', ', $setValues);
    
        $whereClauses = [];
        $bindings = [];
        foreach ($conditions as $key => $value) {
            $whereClauses[] = "$key = :where_$key";
            $bindings[":where_$key"] = $value;
        }
        $whereString = implode(' AND ', $whereClauses);
    
        $query = "SELECT * FROM $table WHERE $whereString";
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);
        $datosPrevios = $statement->fetch(PDO::FETCH_ASSOC);
    
        $query = "UPDATE $table SET $setString WHERE $whereString";
        $statement = $this->pdo->prepare($query);
    
        foreach ($data as $key => $value) {
            $statement->bindValue(":$key", $value);
        }
        foreach ($bindings as $key => $value) {
            $statement->bindValue($key, $value);
        }
    
        $executionResult = $statement->execute();
    
        // Registrar en la auditoría
        $this->registrarAuditoria($table, 'UPDATE', $this->request->getKeySession(ID_SESSION) ?? null, $datosPrevios, $data, $conditions['id'] ?? null);
    
        return $executionResult;
    }
    
    
    

    public function delete($table, $conditions)
    {
        try {
            $whereClauses = [];
            $bindings = [];

            foreach ($conditions as $key => $value) {
                if (is_array($value)) {
                    $placeholders = [];
                    foreach ($value as $index => $item) {
                        $placeholder = ":{$key}_{$index}";
                        $placeholders[] = $placeholder;
                        $bindings[$placeholder] = $item;
                    }
                    $whereClauses[] = "$key IN (" . implode(', ', $placeholders) . ")";
                } else {
                    $whereClauses[] = "$key = :$key";
                    $bindings[":$key"] = $value;
                }
            }

            $where = implode(' AND ', $whereClauses);

            // Obtener datos previos para auditoría
            $querySelect = "SELECT * FROM $table WHERE $where";
            $statement = $this->pdo->prepare($querySelect);
            $statement->execute($bindings);
            $datosPrevios = $statement->fetchAll(PDO::FETCH_ASSOC);

            // Ejecutar eliminación
            $queryDelete = "DELETE FROM $table WHERE $where";
            $sentencia = $this->pdo->prepare($queryDelete);

            foreach ($bindings as $key => $value) {
                $sentencia->bindValue($key, $value);
            }

            $sentencia->execute();
            $affectedRows = $sentencia->rowCount();

            // Registrar auditoría por cada fila eliminada
            foreach ($datosPrevios as $row) {
                $this->registrarAuditoria($table, 'DELETE', $this->request->getKeySession(ID_SESSION), $row, null, $row['id'] ?? null);
            }

            return $affectedRows;
        } catch (PDOException $e) {
            $this->logger->error("❌ Error al eliminar de la tabla $table: " . $e->getMessage());
            throw new Exception("No se pudo eliminar correctamente de $table.");
        }
    }

    
    
    private function registrarAuditoria($tabla, $operacion, $usuario, $datosPrevios = null, $datosNuevos = null, $idRegistro = null)
    {
        try {
            $query = "INSERT INTO auditoria (tabla_afectada, operacion, id_registro_afectado, usuario, datos_previos, datos_nuevos)
                      VALUES (:tabla_afectada, :operacion, :id_registro_afectado, :usuario, :datos_previos, :datos_nuevos)";
            $stmt = $this->pdo->prepare($query);
    
            // Datos a insertar
            $stmt->bindValue(':tabla_afectada', $tabla);
            $stmt->bindValue(':operacion', $operacion);
            $stmt->bindValue(':id_registro_afectado', $idRegistro);
            $stmt->bindValue(':usuario', $usuario);
            $stmt->bindValue(':datos_previos', json_encode($datosPrevios));
            $stmt->bindValue(':datos_nuevos', json_encode($datosNuevos));
    
            // Log para depuración: Consulta y parámetros
            $this->logger->debug('Query de auditoría:', ['query' => $query]);
            $this->logger->debug('Parámetros de auditoría:', [
                'tabla_afectada' => $tabla,
                'operacion' => $operacion,
                'id_registro_afectado' => $idRegistro,
                'usuario' => $usuario,
                'datos_previos' => json_encode($datosPrevios),
                'datos_nuevos' => json_encode($datosNuevos),
            ]);
    
            $stmt->execute();
            $this->logger->info('Registro de auditoría exitoso.');
    
        } catch (PDOException $e) {
            // Log de error con detalles
            $this->logger->error('Error al registrar auditoría: ' . $e->getMessage(), [
                'tabla_afectada' => $tabla,
                'operacion' => $operacion,
                'id_registro_afectado' => $idRegistro,
                'usuario' => $usuario,
                'datos_previos' => $datosPrevios,
                'datos_nuevos' => $datosNuevos,
            ]);
        }
    }
    
    
    
    public function obtenerInternosAsignados($params) {
        try {
            // La consulta SQL ahora está directamente en el método
            $sql = "
                SELECT 
                    t.nombre AS nombre_taller, 
                    t.cupo AS cupo_taller, 
                    i.nombre AS nombre_interno, 
                    i.apellido AS apellido_interno, 
                    a.fecha_asignacion
                FROM 
                    talleres t
                INNER JOIN 
                    asignaciones a ON t.id = a.taller_id
                INNER JOIN 
                    internos i ON a.interno_id = i.id
                WHERE 
                    t.id = :id_taller
            ";            
    
            // Preparar la consulta y ejecutar con los parámetros
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetchAll(PDO::FETCH_OBJ); 
            // Retornar los resultados como objetos

            $this->logger->debug("resultado asignaciones",[$params, $result]);
            return $result;
        } catch (Exception $e) {
            // Manejo de errores
            throw new Exception("Error al ejecutar la consulta: " . $e->getMessage());
        }
    }

    public function obtenerProductosConPrecioMasReciente($searchItem = null, $idProducto = null, $usuarioDependencia = null) {
        try {
            $sql = "
                SELECT 
                    p.id AS id_producto,
                    p.nro_proyecto_productivo,
                    p.descripcion_proyecto,
                    pr.precio,
                    p.stock_inicial,
                    p.unidad_medida,
                    p.estado,
                    (
                        p.stock_inicial
                        + COALESCE((
                            SELECT SUM(mi.cantidad)
                            FROM movimiento_inventario mi
                            WHERE mi.producto_id = p.id AND mi.tipo_movimiento = 'in'
                        ), 0)
                        - COALESCE((
                            SELECT SUM(mi.cantidad)
                            FROM movimiento_inventario mi
                            WHERE mi.producto_id = p.id AND mi.tipo_movimiento = 'out'
                        ), 0)
                    ) AS stock_actual
                FROM producto p
                INNER JOIN precio pr ON p.id = pr.id_producto
                WHERE pr.fecha_precio = (
                    SELECT MAX(pr2.fecha_precio) 
                    FROM precio pr2 
                    WHERE pr2.id_producto = pr.id_producto
                )
            ";
    
            $params = [];
    
            if (!is_null($idProducto)) {
                $sql .= " AND pr.id_producto = :idProducto";
                $params['idProducto'] = $idProducto;
            }
    
            if (!is_null($searchItem) && $searchItem !== '') {
                $sql .= " AND p.descripcion_proyecto LIKE :searchItem";
                $params['searchItem'] = "%{$searchItem}%";
            }
    
            if (!is_null($usuarioDependencia)) {
                $sql .= " AND p.id_unidad_q_fabrica = :usuarioDependencia";
                $params['usuarioDependencia'] = $usuarioDependencia;
            }
    
            $sql .= " ORDER BY pr.fecha_precio DESC";
    
            $this->logger->info("Consulta SQL generada: " . $sql);
            $this->logger->info("Parámetros: " . json_encode($params));
    
            $stmt = $this->pdo->prepare($sql);
    
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
    
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            $this->logger->info("Productos con precio más reciente obtenidos.", [$result]);
    
            return $result;
        } catch (PDOException $e) {
            $this->logger->error('Error en obtenerProductosConPrecioMasReciente: ' . $e->getMessage());
            throw new Exception('Error al obtener productos con el precio más reciente.');
        }
    }
        
    public function getPaginatedWithSearch($table, $limit, $offset, $search = '', array $searchFields = [])
    {
        try {
            // Armar SELECT con JOIN si corresponde
            if ($table === 'agente') {
                $query = "
                    SELECT a.*, d.nombre_dependencia, d.descripcion
                    FROM agente a
                    LEFT JOIN dependencia d ON a.dependencia = d.id
                ";
            } else {
                $query = "SELECT * FROM {$table}";
            }
    
            $params = [];
    
            // Si hay búsqueda, armar WHERE + ORDER CASE
            if (!empty($search) && !empty($searchFields)) {
                $this->logger->info("Buscando agentes con término:", ['search' => $search]);
    
                $conditions = [];
                foreach ($searchFields as $field) {
                    if (in_array($field, ['credencial', 'nombre', 'apellido', 'cuil', 'estado_agente'])) {
                        $conditions[] = "a.{$field} LIKE :search";
                    } elseif (in_array($field, ['nombre_dependencia', 'descripcion'])) {
                        $conditions[] = "d.{$field} LIKE :search";
                    } else {
                        $conditions[] = "{$field} LIKE :search";
                    }
                }
    
                $query .= " WHERE " . implode(' OR ', $conditions);
    
                // Definir parámetros de búsqueda
                $params[':search'] = '%' . $search . '%';
                $params[':exact'] = $search;
    
                // Ordenar priorizando coincidencias exactas
                $query .= "
                    ORDER BY 
                        CASE
                            WHEN a.apellido LIKE :exact THEN 1
                            WHEN a.nombre LIKE :exact THEN 2
                            WHEN d.nombre_dependencia LIKE :exact THEN 3
                            WHEN d.descripcion LIKE :exact THEN 4
                            ELSE 5
                        END,
                        a.apellido, a.nombre
                ";
            } else {
                // Si no hay búsqueda, orden normal
                $query .= " ORDER BY a.apellido, a.nombre ";
            }
    
            // Paginación
            $query .= " LIMIT :limit OFFSET :offset";
    
            // Preparar y ejecutar
            $stmt = $this->pdo->prepare($query);
    
            if (isset($params[':search'])) {
                $stmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
            }
            if (isset($params[':exact'])) {
                $stmt->bindValue(':exact', $params[':exact'], PDO::PARAM_STR);
            }
    
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    
            $stmt->execute();
    
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        } catch (PDOException $e) {
            $this->logger->error("Error en getPaginatedWithSearch: ", [$e->getMessage()]);
            throw new Exception("Error al obtener los datos.");
        }
    }
    
    
    
    public function countRowsWithSearch($table, $search = '')
    {
        try {
            $query = "SELECT COUNT(*) as total FROM {$table}";
    
            if (!empty($search)) {
                $query .= " WHERE nombre LIKE :search OR apellido LIKE :search OR cuil LIKE :search";
            }
    
            $stmt = $this->pdo->prepare($query);
    
            if (!empty($search)) {
                $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
            }
    
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            $this->logger->error("Error en countRowsWithSearch: " , [$e->getMessage()]);
            throw new Exception("Error al contar los registros.");
        }
    }
    
    public function getFacturasPaginatedQuery($limit, $offset, $search = '', $sinComprobante = false, $usuarioDependencia = null, $rolUsuario=null)
    {
        try {
            $this->logger->info("usuarioDependencia, rolUsuario", [$usuarioDependencia, $rolUsuario]);
            $query = "SELECT f.*, 
                             d.descripcion AS unidad_facturadora, 
                             a.nombre AS nombre_agente, 
                             a.apellido AS apellido_agente,
                             COUNT(c.id) AS cantidad_cuotas
                      FROM factura f
                      LEFT JOIN dependencia d ON f.unidad_que_factura = d.id
                      LEFT JOIN agente a ON f.id_agente = a.id
                      LEFT JOIN cuota c ON f.id = c.factura_id";
    
            $whereClauses = [];
            $params = [];
    
            // Búsqueda
            if (!empty($search)) {
                $whereClauses[] = "(f.nro_factura LIKE :search OR a.nombre LIKE :search OR a.apellido LIKE :search)";
                $params['search'] = "%{$search}%";
            }
    
            // Filtro por comprobante
            if ($sinComprobante) {
                $whereClauses[] = "f.path_comprobante IS NULL";
            }
    
            // Filtro por dependencia
            if ($rolUsuario === PUNTO_VENTA && !is_null($usuarioDependencia)) {
                $whereClauses[] = "f.unidad_que_factura = :usuarioDependencia";
                $params['usuarioDependencia'] = $usuarioDependencia;
            }
    
            // Unificar cláusulas WHERE
            if (count($whereClauses) > 0) {
                $query .= " WHERE " . implode(" AND ", $whereClauses);
            }
    
            // Agrupamiento y paginación
            $query .= " GROUP BY f.id, d.descripcion, a.nombre, a.apellido";
            $query .= " ORDER BY f.fecha_factura DESC LIMIT :limit OFFSET :offset";
    
            $stmt = $this->pdo->prepare($query);
    
            // Bind de parámetros
            if (!empty($search)) {
                $stmt->bindValue(':search', $params['search'], PDO::PARAM_STR);
            }
            if (isset($params['usuarioDependencia'])) {
                $stmt->bindValue(':usuarioDependencia', $params['usuarioDependencia'], PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    
            $stmt->execute();
    
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error en getFacturasPaginatedQuery: " . $e->getMessage());
            throw new Exception("Error al obtener las facturas.");
        }
    }
        
    
    
    public function countFacturasQuery($search = '', $sinComprobante = false, $dependenciaId = null, $rolUsuario=null)
    {
        try {
            $query = "SELECT COUNT(*) as total FROM factura";
            
            $whereClauses = [];
            $params = [];
    
            // Filtro por búsqueda
            if (!empty($search)) {
                $whereClauses[] = "(nro_factura LIKE :search OR id_agente LIKE :search)";
                $params['search'] = "%{$search}%";
            }
    
            // Filtro por comprobante
            if ($sinComprobante) {
                $whereClauses[] = "path_comprobante IS NULL";
            }
    
            // Filtro por dependencia solo si el rol ES PUNTO_VENTA
            if ($rolUsuario === PUNTO_VENTA && !is_null($dependenciaId)) {
                $whereClauses[] = "unidad_que_factura = :dependenciaId";
                $params['dependenciaId'] = $dependenciaId;
            }
    
            // Armar cláusula WHERE si hay condiciones
            if (count($whereClauses) > 0) {
                $query .= " WHERE " . implode(" AND ", $whereClauses);
            }
    
            // Preparar y ejecutar
            $stmt = $this->pdo->prepare($query);
    
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
    
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
            return $resultado['total'] ?? 0;
        } catch (PDOException $e) {
            $this->logger->error("Error en countFacturasQuery: " . $e->getMessage());
            throw new Exception("Error al contar las facturas.");
        }
    }
        
    
    public function getFacturaById($id)
    {
        try {
            $query = "SELECT f.*, 
                             d.descripcion AS unidad_facturadora, 
                             a.nombre AS nombre_agente, 
                             a.apellido AS apellido_agente 
                      FROM factura f 
                      LEFT JOIN dependencia d ON f.unidad_que_factura = d.id 
                      LEFT JOIN agente a ON f.id_agente = a.id 
                      WHERE f.id = :id";
    
            return $this->query($query, [':id' => $id])[0] ?? null;
        } catch (Exception $e) {
            $this->logger->error("Error en getFacturaById: " . $e->getMessage());
            throw new Exception("Error al obtener la factura.");
        }
    }
    
    public function getDetalleFacturaByFacturaId($id)
    {
        try {
            $query = "SELECT df.*, p.descripcion_proyecto, p.nro_proyecto_productivo, p.id 
                      FROM detalle_factura df
                      INNER JOIN producto p ON df.producto_id = p.id
                      WHERE df.factura_id = :id";
    
            return $this->query($query, [':id' => $id]);
        } catch (PDOException $e) {
            $this->logger->error("Error en getDetalleFacturaByFacturaId: " . $e->getMessage());
            throw new Exception("Error al obtener los productos de la factura.");
        }
    }
    
        
    public function query($sql, $params = [])
    {
        try {
            $this->logger->info("Ejecutando query:", ['sql' => $sql, 'params' => $params]);

            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            // AUDITORÍA - antes del execute
            $tipo = strtoupper(strtok(trim($sql), " "));
            $datosPrevios = null;
            $tabla = null;
            $idRegistro = null;

            // Log del tipo de operación
            $this->logger->info("🛠 OPERACIÓN {$tipo} detectada");

            if ($tipo === 'UPDATE') {
                preg_match('/UPDATE\s+([^\s]+)/i', $sql, $matches);
                $tabla = $matches[1] ?? null;

                if (isset($params['id']) && $tabla) {
                    $idRegistro = $params['id'];

                    $this->logger->info("🔍 Buscando estado previo de registro con ID $idRegistro en tabla $tabla");

                    $selectPrevio = "SELECT * FROM {$tabla} WHERE id = :id";
                    $stmtPrevio = $this->pdo->prepare($selectPrevio);
                    $stmtPrevio->bindValue(':id', $idRegistro, PDO::PARAM_INT);
                    $stmtPrevio->execute();
                    $datosPrevios = $stmtPrevio->fetch(PDO::FETCH_ASSOC);
                }
            }


            $stmt->execute();
            $this->logger->info("✅ Consulta ejecutada correctamente");

            if (in_array($tipo, ['INSERT', 'UPDATE', 'DELETE'])) {
                if (!$tabla) {
                    preg_match('/(INTO|FROM)\s+([^\s]+)/i', $sql, $matches);
                    $tabla = $matches[2] ?? 'desconocida';
                }

                $this->logger->info("🧾 Registrando auditoría para operación $tipo en tabla $tabla");

                $this->registrarAuditoria(
                    $tabla,
                    $tipo,
                    $this->request->getKeySession(ID_SESSION),
                    $datosPrevios,
                    $params,
                    $idRegistro
                );
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $this->logger->error("❌ Error en query(): " . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            throw new Exception("Error al ejecutar la consulta en la base de datos.");
        }
    }
    
    public function confirmarDescuentosParaFecha(string $fecha, array $descuentos): array
    {
        try {
            $this->logger->info("📥 Confirmar descuentos – Fecha original: $fecha");
    
            $query = "
                SELECT s.id AS solicitud_id, s.cuota_id, c.monto, c.monto_pagado, c.monto_pagado_solicitado,
                       c.monto_reprogramado, c.estado, a.credencial, f.nro_factura
                FROM solicitud_descuento_haberes s
                JOIN cuota c ON c.id = s.cuota_id
                JOIN factura f ON f.id = c.factura_id
                JOIN agente a ON a.id = f.id_agente
                WHERE s.fecha_solicitud = :fecha
            ";
    
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':fecha', $fecha);
            $stmt->execute();
            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            $this->logger->info("🔍 Solicitudes encontradas:", $solicitudes);
    
            $pagosPorCredencial = [];
            $cuotasPorCredencial = [];
    
            foreach ($solicitudes as $s) {
                $cred = ltrim($s['credencial'], '0');
                $pagosPorCredencial[$cred] = ($pagosPorCredencial[$cred] ?? 0) + floatval($s['monto_pagado_solicitado']);
                $cuotasPorCredencial[$cred][] = $s;
            }
    
            $this->logger->info("📊 Pagos registrados por credencial:", $pagosPorCredencial);
            $this->logger->info("📤 Lista de descuentos recibidos:", $descuentos);
    
            $resultados = [];
            $credencialesProcesadas = [];
    
            foreach ($descuentos as $d) {
                $cred = ltrim($d['credencial'], '0');
                $credencialesProcesadas[] = $cred;
                $montoArchivo = floatval($d['saldo']);
                $montoRegistrado = $pagosPorCredencial[$cred] ?? 0;
    
                $diferencia = abs($montoArchivo - $montoRegistrado);
                $this->logger->info("🧮 Comparando credencial $cred → archivo: $montoArchivo vs registrado: $montoRegistrado (dif: $diferencia)");
    
                $estado = ($diferencia < 0.01) ? 'aprobado' : 'rechazado';
                $this->logger->info("🟢 Resultado para credencial $cred: $estado");
    
                foreach ($cuotasPorCredencial[$cred] ?? [] as $cuota) {
                    $cuotaId = $cuota['cuota_id'];
                    $solicitado = (float)$cuota['monto_pagado_solicitado'];
                    $pagado = (float)$cuota['monto_pagado'];
                    $monto = (float)$cuota['monto'];
                    $nuevoPagado = $estado === 'aprobado' ? $pagado + $solicitado : $pagado;
    
                    if ($estado === 'aprobado') {
                        $nuevoEstado = ($nuevoPagado >= $monto) ? 'pagada' : 'reprogramada';
                        $this->logger->info("✅ Aprobando cuota #$cuotaId, nuevo estado: $nuevoEstado");
                        $this->pdo->prepare("
                            UPDATE cuota
                            SET monto_pagado = monto_pagado + monto_pagado_solicitado,
                                monto_pagado_solicitado = 0,
                                estado = :estado
                            WHERE id = :id
                        ")->execute([
                            ':estado' => $nuevoEstado,
                            ':id' => $cuotaId
                        ]);
                    } else {
                        $this->logger->warning("❌ Rechazando cuota #$cuotaId");
                        $this->pdo->prepare("
                            UPDATE cuota
                            SET monto_reprogramado = monto_reprogramado + monto_pagado_solicitado,
                                monto_pagado_solicitado = 0,
                                estado = 'reprogramada',
                                periodo = DATE_ADD(IFNULL(periodo, fecha_vencimiento), INTERVAL 1 MONTH)
                            WHERE id = :id
                        ")->execute([':id' => $cuotaId]);
    
                        $descripcion = "Cuota {$cuota['cuota_id']} - Factura N° {$cuota['nro_factura']}, RECHAZADO";
                        $this->pdo->prepare("
                            INSERT INTO cuenta_corriente (agente_id, fecha, descripcion, condicion_venta, tipo_movimiento, monto, cuota_id)
                            SELECT f.id_agente, CURDATE(), :desc, f.condicion_venta, 'debito', :monto, c.id
                            FROM cuota c
                            JOIN factura f ON f.id = c.factura_id
                            WHERE c.id = :cuota_id
                        ")->execute([
                            ':desc' => $descripcion,
                            ':monto' => $solicitado,
                            ':cuota_id' => $cuotaId
                        ]);
                    }
    
                    $this->pdo->prepare("
                        UPDATE solicitud_descuento_haberes
                        SET resultado = :estado, fecha_resultado = CURDATE(), motivo = NULL
                        WHERE id = :id
                    ")->execute([
                        ':estado' => $estado,
                        ':id' => $cuota['solicitud_id']
                    ]);
    
                    $resultados[] = [
                        'cuota_id' => $cuotaId,
                        'solicitud_pago' => $estado
                    ];
                }
            }
    
            // Procesa faltantes
            foreach ($cuotasPorCredencial as $cred => $cuotas) {
                if (in_array($cred, $credencialesProcesadas)) continue;
    
                foreach ($cuotas as $cuota) {
                    $cuotaId = $cuota['cuota_id'];
                    $solicitado = (float)$cuota['monto_pagado_solicitado'];
    
                    $this->logger->warning("🚫 Cuota faltante en archivo – #$cuotaId para credencial $cred");
    
                    $this->pdo->prepare("
                        UPDATE cuota
                        SET monto_reprogramado = monto_reprogramado + monto_pagado_solicitado,
                            monto_pagado_solicitado = 0,
                            estado = 'reprogramada',
                            periodo = DATE_ADD(IFNULL(periodo, fecha_vencimiento), INTERVAL 1 MONTH)
                        WHERE id = :id
                    ")->execute([':id' => $cuotaId]);
    
                    $descripcion = "Cuota {$cuota['cuota_id']} - Factura N° {$cuota['nro_factura']}, RECHAZADO";
                    $this->pdo->prepare("
                        INSERT INTO cuenta_corriente (agente_id, fecha, descripcion, condicion_venta, tipo_movimiento, monto, cuota_id)
                        SELECT f.id_agente, CURDATE(), :desc, f.condicion_venta, 'debito', :monto, c.id
                        FROM cuota c
                        JOIN factura f ON f.id = c.factura_id
                        WHERE c.id = :cuota_id
                    ")->execute([
                        ':desc' => $descripcion,
                        ':monto' => $solicitado,
                        ':cuota_id' => $cuotaId
                    ]);
    
                    $this->pdo->prepare("
                        UPDATE solicitud_descuento_haberes
                        SET resultado = 'rechazado', fecha_resultado = CURDATE(), motivo = 'Faltante en archivo'
                        WHERE id = :id
                    ")->execute([':id' => $cuota['solicitud_id']]);
    
                    $resultados[] = [
                        'cuota_id' => $cuotaId,
                        'solicitud_pago' => 'rechazado'
                    ];
                }
            }
    
            return $resultados;
    
        } catch (PDOException $e) {
            $this->logger->error("❌ Error en confirmarDescuentosParaFecha: " . $e->getMessage());
            throw new Exception("Error al confirmar descuentos.");
        }
    }
    
                     
}