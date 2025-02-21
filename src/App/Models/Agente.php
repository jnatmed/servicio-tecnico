<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class Agente extends Model
{
    use Loggable;


    public function getAgentes($searchAgente = null) 
    {
        try {
            // Si hay un término de búsqueda, usar selectAdHoc
            if ($searchAgente !== null) {
                $result = $this->queryBuilder->selectAdHoc(
                    'agente',
                    '*',
                    'agente',
                    $searchAgente,
                    ['credencial', 'nombre', 'apellido', 'cuil', 'estado_agente']
                );
            } else {
                $result = $this->queryBuilder->select('agente', '*');
            }
    
            if (!empty($result)) {
                $this->logger->info("Datos de agentes recuperados con éxito: ", $result);
                $result[0]['exito'] = true;
                return $result;
            } else {
                $this->logger->error("No se encontró listado de agentes");
                return ["exito" => false];
            }
        } catch (PDOException $e) {
            $this->logger->error("Error al recuperar los datos de los agentes: " . $e->getMessage());
            return ["exito" => false];
        } catch (Exception $e) {
            $this->logger->error("Ocurrió un error al obtener los datos de los agentes: " . $e->getMessage());
            return ["exito" => false];
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

    public function getProductosYPrecios($searchItem=null)
    {
        try {

            $productos = $this->queryBuilder->obtenerProductosConPrecioMasReciente($searchItem);

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