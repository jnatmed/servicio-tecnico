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

    public function __construct(PDO $pdo, ?Logger $logger = null)
    {   
        $this->pdo = $pdo;
        $this->logger = $logger;
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
                          INNER JOIN dependencia ON agente.dependencia = dependencia.id
                    ";
            } else {
                $query = "SELECT $columns FROM $table";
            }
    
            $bindings = [];
    
            // Si hay un valor de búsqueda y una lista de campos para buscar
            if (!empty($searchValue) && !empty($fieldsToSearch)) {
                $likeClauses = [];
                foreach ($fieldsToSearch as $field) {
                    $likeClauses[] = "$field LIKE :searchValue";
                }
                $query .= " WHERE " . implode(' OR ', $likeClauses);
                $bindings[":searchValue"] = "%$searchValue%";
            }
    
            $this->logger->info("query: $query");
    
            // Preparar la sentencia
            $stmt = $this->pdo->prepare($query);
    
            // Enlazar el valor de búsqueda
            foreach ($bindings as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
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
        $this->registrarAuditoria($table, 'UPDATE', $_SESSION['usuario'] ?? null, $datosPrevios, $data, $conditions['id'] ?? null);
    
        return $executionResult;
    }
    
    
    

    public function delete($table, $conditions)
    {
        $whereClauses = [];
        $bindings = [];
    
        foreach ($conditions as $key => $value) {
            $whereClauses[] = "$key = :$key";
            $bindings[":$key"] = $value;
        }
        $where = implode(' AND ', $whereClauses);
    
        $query = "SELECT * FROM $table WHERE $where";
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);
        $datosPrevios = $statement->fetch(PDO::FETCH_ASSOC);
    
        $query = "DELETE FROM $table WHERE $where";
        $sentencia = $this->pdo->prepare($query);
    
        foreach ($bindings as $key => $value) {
            $sentencia->bindValue($key, $value);
        }
    
        $sentencia->execute();
        $affectedRows = $sentencia->rowCount();
    
        // Registrar en la auditoría
        $this->registrarAuditoria($table, 'DELETE', $_SESSION['usuario'] ?? null, $datosPrevios, null, $conditions['id'] ?? null);
    
        return $affectedRows;
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

    public function obtenerProductosConPrecioMasReciente($searchItem = null, $idProducto = null) {
        try {
            $sql = "SELECT 
                        p.id AS id_producto,
                        p.nro_proyecto_productivo,
                        p.descripcion_proyecto,
                        pr.precio
                    FROM producto p
                    INNER JOIN precio pr ON p.id = pr.id_producto
                    WHERE pr.fecha_precio = (
                        SELECT MAX(pr2.fecha_precio) 
                        FROM precio pr2 
                        WHERE pr2.id_producto = pr.id_producto
                    )";
    
            // Arreglo de parámetros para bind
            $params = [];
    
            // Si se envía un idProducto, agregar condición
            if (!is_null($idProducto)) { // Asegurar que no es NULL
                $sql .= " AND pr.id_producto = :idProducto";
                $params['idProducto'] = $idProducto;
            }
    
            // Si se envía un término de búsqueda, agregar condición
            if (!is_null($searchItem) && $searchItem !== '') { // Evita agregar si está vacío
                $sql .= " AND p.descripcion_proyecto LIKE :searchItem";
                $params['searchItem'] = "%{$searchItem}%";
            }
    
            // Agregar ORDER BY para asegurar que se obtienen los datos más recientes
            $sql .= " ORDER BY pr.fecha_precio DESC";
    
            // Log de la consulta SQL antes de ejecutarla
            $this->logger->info("Consulta SQL generada: " . $sql);
            $this->logger->info("Parámetros: " . json_encode($params));
    
            $stmt = $this->pdo->prepare($sql);
    
            // Enlazar parámetros con bindValue() solo si existen
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
            }
    
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Log de los resultados obtenidos
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
            $query = "SELECT * FROM {$table}";
            $params = [];
    
            if (!empty($search) && !empty($searchFields)) {
                $conditions = [];
                foreach ($searchFields as $field) {
                    $conditions[] = "{$field} LIKE :search";
                }
                $query .= " WHERE " . implode(' OR ', $conditions);
                $params[':search'] = '%' . $search . '%';
            }
    
            $query .= " ORDER BY id LIMIT :limit OFFSET :offset";
    
            $stmt = $this->pdo->prepare($query);
    
            // Bind valores
            if (isset($params[':search'])) {
                $stmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
            }
    
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    
            $stmt->execute();
    
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        } catch (PDOException $e) {
            $this->logger->error("Error en getPaginatedWithSearch: " , [$e->getMessage()]);
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
    
    public function getFacturasPaginatedQuery($limit, $offset, $search = '')
    {
        try {
            $query = "SELECT f.*, 
                             d.descripcion AS unidad_facturadora, 
                             a.nombre AS nombre_agente, 
                             a.apellido AS apellido_agente,
                             COUNT(c.id) AS cantidad_cuotas
                      FROM factura f
                      LEFT JOIN dependencia d ON f.unidad_que_factura = d.id
                      LEFT JOIN agente a ON f.id_agente = a.id
                      LEFT JOIN cuota c ON f.id = c.factura_id"; // Relación con cuotas
            
            $params = [];
    
            // Agregar filtro de búsqueda si se proporciona un término
            if (!empty($search)) {
                $query .= " WHERE f.nro_factura LIKE :search 
                            OR a.nombre LIKE :search 
                            OR a.apellido LIKE :search";
                $params['search'] = "%{$search}%";
            }
    
            $query .= " GROUP BY f.id, d.descripcion, a.nombre, a.apellido"; // Agrupar por factura
            $query .= " ORDER BY f.fecha_factura DESC LIMIT :limit OFFSET :offset";
    
            // Preparar la consulta
            $stmt = $this->pdo->prepare($query);
    
            // Enlazar parámetros
            if (!empty($search)) {
                $stmt->bindValue(':search', $params['search'], PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    
            // Ejecutar la consulta
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error en getFacturasPaginatedQuery: " . $e->getMessage());
            throw new Exception("Error al obtener las facturas.");
        }
    }
    
    
    
    public function countFacturasQuery($search = '')
    {
        try {
            $query = "SELECT COUNT(*) as total FROM factura";
            
            $params = [];
    
            if (!empty($search)) {
                $query .= " WHERE nro_factura LIKE :search OR id_agente LIKE :search";
                $params['search'] = "%{$search}%";
            }
    
            // Preparar la consulta manualmente en lugar de usar select()
            $stmt = $this->pdo->prepare($query);
    
            // Enlazar parámetros de búsqueda si existen
            if (!empty($search)) {
                $stmt->bindValue(':search', $params['search'], PDO::PARAM_STR);
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
    
            // Enlazar parámetros dinámicamente
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
    
            $stmt->execute();
    
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error en query(): " . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            throw new Exception("Error al ejecutar la consulta en la base de datos.");
        }
    }
         
}