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
    
    

    public function insert($table, $data, $username = null)
    {
        $columnas = implode(', ', array_keys($data));
        $valores = ':' . implode(', :', array_keys($data));
        $query = "INSERT INTO $table ($columnas) VALUES ($valores)";
        $sentencia = $this->pdo->prepare($query);
    
        foreach ($data as $clave => $valor) {
            $sentencia->bindValue(":$clave", $valor);
        }
    
        $resultado = $sentencia->execute();
        $idGenerado = $this->pdo->lastInsertId();
    
        // Registrar en la auditoría
        $this->registrarAuditoria($table, 'INSERT', $username, null, $data, $idGenerado);
    
        return [$idGenerado, $resultado];
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
        $this->registrarAuditoria($table, 'UPDATE', $_SESSION['usuario'], $datosPrevios, $data, $conditions['id'] ?? null);
    
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
        $this->registrarAuditoria($table, 'DELETE', $_SESSION['usuario'], $datosPrevios, null, $conditions['id'] ?? null);
    
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
}