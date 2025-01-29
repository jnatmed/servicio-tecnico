<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class Producto extends Model
{
    use Loggable;


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
}