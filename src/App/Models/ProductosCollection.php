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

    public function __construct($qb=null) {
        if ($qb) {
            parent::setQueryBuilder($qb);
        }    
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


}