<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class ProductosCollection extends Model
{
    use Loggable;
    private $table = 'producto';

    public function __construct($qb=null, $logger=null) {
        if ($qb !== null) {
            parent::setQueryBuilder($qb);
        }
    
        if ($logger !== null) {
            parent::setLogger($logger);
        }

    }

    public function actualizarProducto(array $data)
    {
        try {
            $this->logger->info("Actualizando producto ID: ", [$data]);
        
            $resultado = $this->queryBuilder->update('producto', $data, ['id' => $data['id']]);
    
            if (!$resultado) {
                throw new Exception("No se pudo actualizar el producto.");
            }
    
            return true;
    
        } catch (Exception $e) {
            $this->logger->error("Error al actualizar producto: " . $e->getMessage());
            throw $e;
        }
    }
    

    public function contarSinPrecio(): int
    {
        $sql = "
            SELECT COUNT(*) as total 
            FROM producto p
            WHERE NOT EXISTS (
                SELECT 1 
                FROM precio pr 
                WHERE pr.id_producto = p.id
            )
        ";
        $resultado = $this->queryBuilder->query($sql);
        return (int) ($resultado[0]['total'] ?? 0);
    }
    

    public function contarTodos(): int
    {
        $sql = "SELECT COUNT(*) as total FROM producto";
        $resultado = $this->queryBuilder->query($sql);
        return (int) ($resultado[0]['total'] ?? 0);
    }

    public function contarPorUnidadMedida(): array
    {
        $sql = "SELECT unidad_medida, COUNT(*) as cantidad FROM producto GROUP BY unidad_medida";
        $resultado = $this->queryBuilder->query($sql);
    
        $this->logger->info("contarPorUnidadMedida: ", [$resultado]);

        $formateado = [];
        foreach ($resultado as $row) {
            $formateado[$row['unidad_medida']] = (int) $row['cantidad'];
        }
    
        return $formateado;
    }
    


    public function getDependencias(){
        try {
            $dependencias = $this->queryBuilder->select('dependencia', '*');
            return $dependencias;
        } catch (Exception $e) {
            // Registrar el error (puedes usar un logger en lugar de echo)
            error_log('Error en getDependencias: ' . $e->getMessage());
    
            // Retornar un valor por defecto o manejar el error según la lógica de tu aplicación
            return [];
        }
    }

    public function getById($id)
    {
        try {
            // Validar que el ID sea un número
            if (!is_numeric($id)) {
                throw new Exception("El ID proporcionado no es válido.");
            }
    
            // Consultar el producto en la base de datos
            $resultado = $this->queryBuilder->select('producto', '*', ['id' => $id]);
    
            // Verificar si se encontró un resultado
            if (!$resultado) {
                throw new Exception("No se encontró el producto con ID: $id");
            }
    
            // Retornar el primer registro encontrado (en teoría, solo debería haber uno)
            return $resultado[0];
    
        } catch (Exception $e) {
            $this->logger->error("Error en getById", ['id' => $id, 'error' => $e->getMessage()]);
            throw new Exception("Error al obtener el producto: " . $e->getMessage());
        }
    }
    

    public function getHistorialPrecios($idProducto)
    {
        try {
            $sql = "
                SELECT 
                    precio,
                    fecha_precio,
                    pv_autorizacion_consejo
                FROM precio
                WHERE id_producto = :id
                ORDER BY fecha_precio DESC
            ";

            $params = [':id' => $idProducto];
            $result = $this->queryBuilder->query($sql, $params);

            return $result ?: []; // Si no hay resultados, devolvé un array vacío
        } catch (PDOException $e) {
            $this->logger->error("Error al obtener historial de precios: " . $e->getMessage());
            return [];
        }
    }


    public function getProductosALaVenta()
    {
        try {
        $result = $this->queryBuilder->select('producto', '*');

            if (!empty($result)) {
                $this->logger->info("Datos de productos recuperados con éxito: ", $result);
                $result[0]['exito'] = true;
                return $result; // Suponiendo que select devuelve un array de resultados
            } else {
                $this->logger->error("No se encontró listado de productos");
                return ["exito" => false ];
            }
        } catch (PDOException $e) {
            $this->logger->error("Error al recuperar los datos de los productos: " . $e->getMessage());
            return ["exito" => false ];
        } catch (Exception $e) {
            $this->logger->error("Ocurrió un error al obtener los datos de los productos: " . $e->getMessage());
            return ["exito" => false ];
        }        
    }

    public function getDetalleProducto($id) 
    {
        try {
            $result = $this->queryBuilder->select('producto', '*', ['id' => $id]);

            if (!empty($result)) {
                $this->logger->info("Datos de producto recuperados con éxito: ", $result);
                $result[0]['exito'] = true;
                return $result[0]; // Suponiendo que select devuelve un array de resultados
            } else {
                $this->logger->error("No se encontró detalle del producto");
                return ["exito" => false ];
            }            
        }catch (PDOException $e) {
            $this->logger->error("Error al recuperar los datos del producto: " . $e->getMessage());
        }
    }
    public function getDetalleProductoYUltimoPrecio($id) 
    {
        try {
            $result = $this->queryBuilder->obtenerProductosConPrecioMasReciente(null, $id);

            if (!empty($result)) {
                $this->logger->info("Datos de producto recuperados con éxito: ", $result);
                // $result[0]['exito'] = true;
                return $result; // Suponiendo que select devuelve un array de resultados
            } else {
                $this->logger->error("No se encontró detalle del producto");
                return ["exito" => false ];
            }            
        }catch (PDOException $e) {
            $this->logger->error("Error al recuperar los datos del producto: " . $e->getMessage());
        }
    }

    public function getProductosConUltimoPrecio()
    {
        try {
            $sql = "
                SELECT 
                    p.*,
                    pr.precio,
                    pr.fecha_precio,
                    pr.pv_autorizacion_consejo
                FROM producto p
                LEFT JOIN (
                    SELECT pr1.*
                    FROM precio pr1
                    INNER JOIN (
                        SELECT id_producto, MAX(fecha_precio) AS ultima_fecha
                        FROM precio
                        GROUP BY id_producto
                    ) pr2 ON pr1.id_producto = pr2.id_producto AND pr1.fecha_precio = pr2.ultima_fecha
                ) pr ON p.id = pr.id_producto
                WHERE p.estado = 'a_la_venta'
                ORDER BY p.created_at DESC
            ";
    
            $this->logger->info("Consulta productos con último precio:", [$sql]);
    
            return $this->queryBuilder->query($sql);
        } catch (Exception $e) {
            $this->logger->error("Error al obtener productos con precio más reciente: " . $e->getMessage());
            return [];
        }
    }
    
    public function insertarPrecio(array $data)
    {
        try {
            $this->logger->info("Insertando nuevo precio con data:", [$data]);
    
            // Agregar hora actual si solo vino la fecha
            if (strlen($data['fecha_precio']) === 10) { // formato 'Y-m-d'
                $data['fecha_precio'] .= ' ' . date('H:i:s');
            }
    
            list($idInsertado, $success) = $this->queryBuilder->insert('precio', [
                'precio' => $data['precio'],
                'pv_autorizacion_consejo' => $data['pv_autorizacion_consejo'] ?? null,
                'fecha_precio' => $data['fecha_precio'],
                'id_producto' => $data['id_producto']
            ]);
    
            if (!$success) {
                throw new Exception("No se pudo insertar el nuevo precio.");
            }
    
            return $idInsertado;
    
        } catch (Exception $e) {
            $this->logger->error("Error al insertar precio: " . $e->getMessage());
            throw $e;
        }
    }
    

    public function getProductosYPrecios($searchItem=null)
    {
        try {

            $productos = $this->queryBuilder->obtenerProductosConPrecioMasReciente($searchItem, null);

            return $productos;

        } catch (PDOException $e) {
            $this->logger->error('Error en getProductosYPrecios: ' . $e->getMessage());
            throw new Exception('Error al obtener los productos con precios más recientes.');
        } catch (Exception $e) {
            $this->logger->error('Error inesperado en getProductosYPrecios: ' . $e->getMessage());
            throw new Exception('Ocurrió un error inesperado.');
        }
    }

    public function eliminarProducto($id)
    {
        try {
            if (!is_numeric($id)) {
                throw new Exception("El ID proporcionado no es válido.");
            }
    
            $this->logger->info("Eliminando producto con ID: ", [$id]);
    
            return $this->queryBuilder->delete($this->table, ['id' => $id]);
    
        } catch (Exception $e) {
            $this->logger->error("Error al eliminar producto: " . $e->getMessage());
            throw $e;
        }
    }
    

}