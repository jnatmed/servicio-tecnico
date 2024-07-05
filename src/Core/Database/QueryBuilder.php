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

    public function __construct(PDO $pdo, Logger $logger = null)
    {   
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function select($table, $columns = '*', $params = [])
    {
        try {
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
            
            // Retornar todos los resultados
            return $sentencia->fetchAll();
            
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
    
    

    public function insert($table, $data)
    {
        $columnas = implode(', ', array_keys($data));
        $valores = ':' . implode(', :', array_keys($data));
        $query = "INSERT INTO $table ($columnas) VALUES ($valores)";
        $sentencia = $this->pdo->prepare($query);
    
        // Asignar valores a los parámetros
        foreach ($data as $clave => $valor) {
            $sentencia->bindValue(":$clave", $valor);
        }
    
        $resultado = $sentencia->execute();
        
        $idGenerado = $this->pdo->lastInsertId();

        return [$idGenerado, $resultado];
    }

    public function insertMany($table, $data)
    {
        try {
            $this->logger->info("data en capa DB: ", [$data]);
            
            // Preparar las columnas
            $columns = ['id_publicacion', 'path_imagen', 'nombre_imagen', 'id_usuario'];
            $placeholders = rtrim(str_repeat('(?, ?, ?, ?), ', count($data)), ', '); // Genera los marcadores de posición
    
            // Preparar la consulta de inserción
            $query = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES $placeholders";
    
            // Preparar los valores para la inserción
            $values = [];
            foreach ($data as $imagen) {
                $values[] = $imagen['id_publicacion'];
                $values[] = $imagen['path_imagen'];
                $values[] = $imagen['nombre_imagen'];
                $values[] = $imagen['id_usuario'];
            }
    
            // Preparar la sentencia
            $statement = $this->pdo->prepare($query);
            
            // Ejecutar la consulta con los valores preparados
            $statement->execute($values);
            
            return true;
        } catch (PDOException $e) {
            // Manejo de la excepción
            $this->logger->error("Error al insertar múltiples imágenes: " . $e->getMessage());
            return false;
        }
    }  

    public function getImagePath($imagesTable, $id_publicacion, $id_imagen)
    {
        try {

            $this->logger->info("imagesTable, id_publicacion, id_imagen ",[$imagesTable, $id_publicacion, $id_imagen]);
            $sql = "
                SELECT path_imagen
                FROM $imagesTable
                WHERE id_publicacion = :id_publicacion AND id_imagen = :id_imagen;
            ";
            
            $stmt = $this->pdo->prepare($sql);
    
            $this->logger->info("stmt: ", [$stmt]);

            $stmt->bindValue(':id_publicacion', $id_publicacion, PDO::PARAM_INT);
            $stmt->bindValue(':id_imagen', $id_imagen, PDO::PARAM_INT);
        
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            // Registrar el éxito utilizando el logger

            if ($result) {
                $this->logger->info("path_imagen recuperada: ", [$result]);
                return $result;
            } else {
                // Si no se encuentra la imagen, devuelve false
                $this->logger->info("No se encontró ninguna imagen con los IDs proporcionados.", [$result]);
                return false;
            }
        } catch (PDOException $e) {
            // Manejo de la excepción
            // Registrar el error utilizando el logger
            $this->logger->error("Error al ejecutar la consulta: " . $e->getMessage());
            // Puedes lanzar una excepción personalizada aquí o manejarla de otra manera según tus necesidades
            return false;
        }
    }    

    public function getAllWithImages($mainTable, $imageTable, $mainTableKey, $foreignKey) {
        try {
            $query = "
                SELECT 
                    main.*, 
                    img.id_imagen, 
                    img.path_imagen, 
                    img.nombre_imagen 
                FROM 
                    {$mainTable} main
                LEFT JOIN 
                    {$imageTable} img
                ON 
                    main.{$mainTableKey} = img.{$foreignKey}
            ";

            $statement = $this->pdo->prepare($query);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Logging the error
            $this->logger->error("Error in getAllWithImages: " . $e->getMessage());
            return false;
        }
    }    
    

    public function getAllWithImagesByUser($mainTable, $imageTable, $mainTableKey, $foreignKey, $idUser) {
        try {

            $this->logger->info("parametors: ", [$mainTable, $imageTable, $mainTableKey, $foreignKey, $idUser]);
            $query = "
                SELECT 
                    main.*, 
                    img.id_imagen, 
                    img.path_imagen, 
                    img.nombre_imagen 
                FROM 
                    {$mainTable} main
                LEFT JOIN 
                    {$imageTable} img
                ON 
                    main.{$mainTableKey} = img.{$foreignKey}
                WHERE
                    main.id_usuario = :id_usuario
            ";
    
            $statement = $this->pdo->prepare($query);
            $statement->bindParam(':id_usuario', $idUser, PDO::PARAM_INT);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Logging the error
            $this->logger->error("Error in getAllWithImagesByUser: " . $e->getMessage());
            return false;
        }
    }

    public function getLastQuery()
    {
        return $this->lastQuery;
    }


    public function update($table, $data, $conditions = [])
    {
        try {
            // Construir la cadena SET para los datos a actualizar
            $setValues = [];
            foreach ($data as $key => $value) {
                $setValues[] = "$key = :$key";
            }
            $setString = implode(', ', $setValues);
    
            // Construir la cadena WHERE para las condiciones
            $whereClauses = [];
            $bindings = [];
            foreach ($conditions as $key => $value) {
                $whereClauses[] = "$key = :where_$key";
                $bindings[":where_$key"] = $value;
            }
            $whereString = implode(' AND ', $whereClauses);
    
            // Construir y ejecutar la consulta SQL
            $query = "UPDATE $table SET $setString WHERE $whereString";
            $statement = $this->pdo->prepare($query);
    
            // Vincular los valores de los datos a actualizar
            foreach ($data as $key => $value) {
                $statement->bindValue(":$key", $value);
            }
    
            // Vincular los valores de las condiciones WHERE
            foreach ($bindings as $key => $value) {
                $statement->bindValue($key, $value);
            }
    
            // Ejecutar la consulta
            $executionResult = $statement->execute();
    
            // Comprobar si la ejecución fue exitosa
            if ($executionResult) {
                // Devolver true si la ejecución fue exitosa, independientemente del número de filas afectadas
                return true;
            } else {
                // Si la ejecución falló, lanzar una excepción
                $this->logger->error('Error en la actualización: la consulta no se ejecutó correctamente.');
                throw new PDOException('Error en la actualización: la consulta no se ejecutó correctamente.');
            }
        } catch (PDOException $e) {
            // Manejar la excepción de PDO
            $this->logger->error('Error en la actualización: ' . $e->getMessage());
            throw new PDOException('Error en la actualización: ' . $e->getMessage());
        }
    }
    
    

    public function delete($table, $conditions)
    {
        try {
            // Construir la cláusula WHERE y los parámetros de enlace
            $whereClauses = [];
            $bindings = [];
    
            foreach ($conditions as $key => $value) {
                $whereClauses[] = "$key = :$key";
                $bindings[":$key"] = $value;
            }
    
            $where = implode(' AND ', $whereClauses);
    
            // Construir la consulta DELETE
            $query = "DELETE FROM $table WHERE $where";
    
            // Preparar la sentencia
            $sentencia = $this->pdo->prepare($query);
    
            // Enlazar los valores de los parámetros
            foreach ($bindings as $key => $value) {
                $sentencia->bindValue($key, $value);
            }
    
            // Ejecutar la consulta
            $sentencia->execute();
    
            // Devolver el número de filas afectadas
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            // Manejar la excepción de la base de datos
            $this->logger->error('Error al ejecutar consulta DELETE: ' . $e->getMessage());
            throw new Exception('Error al ejecutar consulta DELETE: ' . $e->getMessage());
        } catch (Exception $e) {
            // Manejar otras excepciones
            $this->logger->error('Ocurrió un error al ejecutar consulta DELETE: ' . $e->getMessage());
            throw new Exception('Ocurrió un error al ejecutar consulta DELETE: ' . $e->getMessage());
        }
    }
    
}