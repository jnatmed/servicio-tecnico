<?php 

namespace Paw\App\Models;

use Paw\Core\Model;
use Paw\App\Models\Agente;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;


class AgentesCollection extends Model
{
    use Loggable;
    private $table = 'agente';

    public function __construct($qb=null, $logger=null)
    {
        if ($qb || $logger) {
            parent::__construct($qb, $logger);
        }
    }

    public function add(Agente $agente)
    {
        try {
            $this->queryBuilder->insert($this->table, $agente);
        } catch (Exception $e) {
            error_log("Error al insertar agente: " . $e->getMessage());
            throw $e;
        }
    }



    public function getAgentes($searchAgente = null, $id = null) 
    {
        try {
            if ($id !== null) {


                $this->logger->info("Buscando agente por ID usando selectAdHoc..", [$id]);
                // selectAdHoc no está preparado para múltiples condiciones, pero podemos adaptarlo:
                $result = $this->queryBuilder->selectAdHoc(
                    'agente',
                    '*',
                    'id',          // campo a buscar
                    $id,           // valor a buscar
                    ['id']         // lista de campos a hacer LIKE, aunque se usará con = en este caso especial
                );
            } elseif ($searchAgente !== null) {
                // Buscar por término
                $this->logger->info("Buscando agentes por término..");

                // Usar selectAdHoc() para buscar por término
                // Campos a buscar: credencial, nombre, apellido, cuil, dependencia, estado_agente

                // Ejemplos de busqueda:
                // - getAgentesPaginated(10, 0, 'Juan');
                // - getAgentesPaginated(10, 0, '1234567890');
                // - getAgentesPaginated(10, 0, '%Juan%');
                // - getAgentesPaginated(10, 0, ['apellido' => 'Garcia', 'estado_agente' => 'activo']);
                $result = $this->queryBuilder->selectAdHoc(
                    'agente',
                    '*',
                    'agente',
                    $searchAgente,
                    ['credencial', 'nombre', 'apellido', 'cuil', 'estado_agente']
                );
            } else {
                // Obtener todos los agentes
                $this->logger->info("Recuperando todos los agentes..");
                
                // Usar select() para obtener todos los agentes
                // Campos a seleccionar: credencial, nombre, apellido, cuil, dependencia, estado_agente
                
                // Ejemplos:
                // - getAgentesPaginated(10, 0);
                // - getAgentesPaginated(10, 0, ['apellido' => 'Garcia', 'estado_agente' => 'activo']);
                
                // Sin especificar busqueda, se seleccionan todos los agentes
                
                // Ejemplos:
                // - getAgentesPaginated(10, 0);
                // - getAgentesPaginated(10, 0, ['apellido' => 'Garcia', 'estado_agente' => 'activo']);
                
                // Usar select() para obtener todos los agentes
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
    
        

    public function getAgentesPaginated($limit, $offset, $search = '')
    {
        return $this->queryBuilder->getPaginatedWithSearch(
            'agente', 
            $limit, 
            $offset, 
            $search,['credencial', 'nombre', 'apellido', 'cuil', 'nombre_dependencia', 'estado_agente']
        );
    }
    
    public function countAgentes($search = '')
    {
        return $this->queryBuilder->countRowsWithSearch('agente', $search);
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