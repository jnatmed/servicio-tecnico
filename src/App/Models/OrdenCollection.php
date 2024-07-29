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

            $this->logger->info("guardarOrden: ", [$idGenerado, $resultado]);

            if ($resultado) {
                $this->logger->info("Orden insertada con existo - id Generada : " . $idGenerado);
                return [
                    "exito" => true,
                    "nuevoNroOrden" => $idGenerado
                ];
            } else {
                $this->logger->error('Error al insertar la nueva orden en la base de datos.');
                return [
                    "exito" => false,
                    "details" => 'Error al insertar la nueva orden en la base de datos.'
                ];
            }
        } catch (PDOException $e) {
            // Manejar la excepción de la base de datos
            $this->logger->error('Error al realizar la inserción en la base de datos: ' . $e->getMessage());
            return [
                "exito" => false,
                "details" => 'Error al realizar la inserción en la base de datos: ' . $e->getMessage()
            ];            
        } catch (Exception $e) {
            // Manejar otras excepciones
            $this->logger->error('Ocurrió un error al guardar la orden: ' . $e->getMessage());
            return [
                "exito" => false,
                "details" => 'Ocurrió un error al guardar la orden: ' . $e->getMessage()
            ];
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

    public function listarOrdenes($userId)
    {
        try {
            // Obtener todas las órdenes
            $ordenes = $this->queryBuilder->select('ordenes', '*', ['usuario_id' => $userId]);
    
            // Para cada orden, obtener la descripción del estado
            foreach ($ordenes as &$orden) {
                // Obtener el estado de la orden
                $estadoOrden = $this->queryBuilder->select('estado_ordenes', 'descripcion_estado', [
                    'id' => $orden['estado_orden_id']
                ]);
    
                // Si se encuentra el estado, añadirlo a la orden
                if (!empty($estadoOrden)) {
                    $orden['descripcion_estado'] = $estadoOrden[0]['descripcion_estado'];
                } else {
                    $orden['descripcion_estado'] = 'Estado desconocido';
                }
            }
    
            return $ordenes;
        } catch (Exception $e) {
            throw new Exception("Error al obtener las órdenes de trabajo: " . $e->getMessage());
        }
    }

    public function actualizarEstadoOrden($idOrden, $nuevoEstado)
    {
        try {
            // Preparar los datos de la actualización
            $datosActualizar = [
                'estado_orden_id' => $nuevoEstado
            ];

            // Preparar las condiciones para la actualización
            $condiciones = [
                'id' => $idOrden
            ];

            // Llamar al método update del QueryBuilder
            $resultado = $this->queryBuilder->update('ordenes', $datosActualizar, $condiciones);

            if ($resultado) {
                $this->logger->info("Estado de la orden actualizado exitosamente - ID: " . $idOrden);
                return [
                    "exito" => true,
                    "idOrden" => $idOrden
                ];
            } else {
                $this->logger->error("Error al actualizar el estado de la orden - ID: " . $idOrden);
                return [
                    "exito" => false,
                    "details" => "Error al actualizar el estado de la orden en la base de datos."
                ];
            }
        } catch (PDOException $e) {
            $this->logger->error('Error al realizar la actualización en la base de datos: ' . $e->getMessage());
            return [
                "exito" => false,
                "details" => 'Error al realizar la actualización en la base de datos: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            $this->logger->error('Ocurrió un error al actualizar el estado de la orden: ' . $e->getMessage());
            return [
                "exito" => false,
                "details" => 'Ocurrió un error al actualizar el estado de la orden: ' . $e->getMessage()
            ];
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
            $resultado = $this->queryBuilder->update('ordenes', $ordenActualizada, ['id' => $idOrden]);
    
            if ($resultado) {
                $this->logger->info("Orden actualizada exitosamente - ID: " . $idOrden);
                
                return [
                    "exito" => true,
                    "idOrden" => $idOrden // Devuelve el ID de la orden actualizada
                ];
                    
            } else {
                $this->logger->error('Error al actualizar la orden en la base de datos.');
                return [
                    "exito" => false,
                    "details" => 'Error al actualizar la orden en la base de datos.'
                ];
            }
        } catch (PDOException $e) {
            // Manejar la excepción de la base de datos
            $this->logger->error('Error al realizar la actualización en la base de datos: ' . $e->getMessage());
            return [
                "exito" => false,
                "details" => 'Error al realizar la actualización en la base de datos: ' . $e->getMessage()
            ];            

        } catch (Exception $e) {
            // Manejar otras excepciones
            $this->logger->error('Ocurrió un error al actualizar la orden: ' . $e->getMessage());
            return [
                "exito" => false,
                "details" => 'Ocurrió un error al actualizar la orden: ' . $e->getMessage()
            ];            
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