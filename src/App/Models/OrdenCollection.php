<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class OrdenCollection extends Model
{
    use Loggable;

    public function guardarOrden($ordenNueva)
    {
        try {
            // Inserta la nueva orden usando QueryBuilder
            [$idGenerado, $resultado] = $this->queryBuilder->insert('ordenes', $ordenNueva);

            if ($resultado) {
                $this->nro_orden = $idGenerado;
                $this->logger->info("Orden insertada con existo - id Generada : " . $this->nro_orden);
                return true;
            } else {
                $this->logger->error('Error al insertar la nueva orden en la base de datos.');
                throw new Exception('Error al insertar la nueva orden en la base de datos.');
            }
        } catch (PDOException $e) {
            // Manejar la excepción de la base de datos
            $this->logger->error('Error al realizar la inserción en la base de datos: ' . $e->getMessage());
            throw new Exception('Error al realizar la inserción en la base de datos: ' . $e->getMessage());
        } catch (Exception $e) {
            // Manejar otras excepciones
            $this->logger->error('Ocurrió un error al guardar la orden: ' . $e->getMessage());
            throw new Exception('Ocurrió un error al guardar la orden: ' . $e->getMessage());
        }
    }

    public function getDatosOrden($nroOrden)
    {
        try {
            // Usar el método select de QueryBuilder para obtener los datos de la orden
            $result = $this->queryBuilder->select('ordenes', '*', ['id' => $nroOrden]);

            if (!empty($result)) {
                $this->logger->info("Datos de la orden recuperados con éxito: ", $result);
                $result[0]['exito'] = true;
                return $result[0]; // Suponiendo que select devuelve un array de resultados
            } else {
                $this->logger->error("No se encontró la orden con ID: " . $nroOrden);
                return ["exito" => false ];
                // throw new Exception("No se encontró la orden con ID: " . $nroOrden);
            }
        } catch (PDOException $e) {
            $this->logger->error("Error al recuperar los datos de la orden: " . $e->getMessage());
            return ["exito" => false ];
        } catch (Exception $e) {
            $this->logger->error("Ocurrió un error al obtener los datos de la orden: " . $e->getMessage());
            return ["exito" => false ];
        }
    }

    public function listarOrdenes()
    {
        try {
            // Usar el método select de QueryBuilder para obtener todas las órdenes
            $ordenes = $this->queryBuilder->select('ordenes');

            if (!empty($ordenes)) {
                return $ordenes;
            } else {
                throw new Exception("No se encontraron órdenes de trabajo.");
            }
        } catch (Exception $e) {
            throw new Exception("Error al obtener las órdenes de trabajo: " . $e->getMessage());
        }
    }
    
    public function actualizarOrden($ordenActualizada)
    {
        try {
            // Extraer el ID de la orden a actualizar
            $idOrden = $ordenActualizada['id'];
    
            // Eliminar el ID del array para evitar su actualización
            unset($ordenActualizada['id']);
    
            // Actualizar la orden usando QueryBuilder
            $idOrden = $this->queryBuilder->update('ordenes', $ordenActualizada, ['id' => $idOrden]);
    
            if ($idOrden) {
                $this->logger->info("Orden actualizada exitosamente - ID: " . $idOrden);
                return $idOrden; // Devuelve el ID de la orden actualizada
            } else {
                $this->logger->error('Error al actualizar la orden en la base de datos.');
                throw new Exception('Error al actualizar la orden en la base de datos.');
            }
        } catch (PDOException $e) {
            // Manejar la excepción de la base de datos
            $this->logger->error('Error al realizar la actualización en la base de datos: ' . $e->getMessage());
            throw new Exception('Error al realizar la actualización en la base de datos: ' . $e->getMessage());
        } catch (Exception $e) {
            // Manejar otras excepciones
            $this->logger->error('Ocurrió un error al actualizar la orden: ' . $e->getMessage());
            throw new Exception('Ocurrió un error al actualizar la orden: ' . $e->getMessage());
        }
    }

    public function borrarDatosOrden($idOrden)
    {
        try {
            // Eliminar la orden usando QueryBuilder
            $resultado = $this->queryBuilder->delete('ordenes', ['id' => $idOrden]);
    
            if ($resultado) {
                $this->logger->info("Orden eliminada exitosamente - ID: " . $idOrden);
                return ['exito' => true];
            } else {
                $this->logger->error('Error al eliminar la orden de trabajo en la base de datos.');
                return ['exito' => false];
                throw new Exception('Error al eliminar la orden de trabajo en la base de datos.');
            }
        } catch (PDOException $e) {
            // Manejar la excepción de la base de datos
            $this->logger->error('Error al realizar la eliminación en la base de datos: ' . $e->getMessage());
            return ['exito' => false];
            throw new Exception('Error al realizar la eliminación en la base de datos: ' . $e->getMessage());
        } catch (Exception $e) {
            // Manejar otras excepciones
            $this->logger->error('Ocurrió un error al eliminar la orden de trabajo: ' . $e->getMessage());
            return ['exito' => false];
            throw new Exception('Ocurrió un error al eliminar la orden de trabajo: ' . $e->getMessage());
        }
    }
        

}