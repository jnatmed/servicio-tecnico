<?php 

namespace Paw\App\Models;

use Paw\Core\Model;
use Paw\App\Models\Factura;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class FacturasCollection extends Model
{
    use Loggable;

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

    public function eliminarFacturaPorId($facturaId)
    {
        try {
            $factura = $this->getFacturaById($facturaId);
            if (!$factura) {
                throw new Exception("La factura con ID $facturaId no existe.");
            }

            // Obtener IDs de cuotas asociadas
            $cuotas = $this->queryBuilder->select('cuota', '*',['factura_id' => $facturaId]);

            $this->logger->info("cuotas encontradas: ", [$cuotas]);

            $cuotaIds = array_column($cuotas, 'id');

            if (!empty($cuotaIds)) {
                // Eliminar de cuenta_corriente donde cuota_id esté en esos IDs
                $this->queryBuilder->delete('cuenta_corriente', ['cuota_id' => $cuotaIds]);
            }
            
            // Eliminar factura (si tenés ON DELETE CASCADE se encarga del resto)
            $this->queryBuilder->delete('factura', ['id' => $facturaId]);
    
            $this->logger->info("Factura ID $facturaId eliminada correctamente.");
            return true;
    
        } catch (Exception $e) {
            $this->logger->error("Error al eliminar factura ID $facturaId: " . $e->getMessage());
            throw $e;
        }
    }
    

    public function insertFactura($factura)
    {
        try {
            // Insertar en la tabla `factura`
            list($idFactura, $resultado) = $this->queryBuilder->insert('factura', [
                'nro_factura' => $factura->getNroFactura(),
                'fecha_factura' => $factura->getFechaFactura(),
                'unidad_que_factura' => $factura->getUnidadQueFactura(),
                'total_facturado' => $factura->getTotalFacturado(),
                'condicion_venta' => $factura->getCondicionVenta(),
                'condicion_impositiva' => $factura->getCondicionImpositiva(),
                'id_agente' => $factura->getIdAgente()
            ]);
    
            // Verificar si la inserción fue exitosa
            if (!$resultado) {
                throw new Exception("No se pudo insertar la factura.");
            }
    
            return $idFactura; // Retorna el ID de la factura insertada
    
        } catch (Exception $e) {
            $this->logger->error("Error en insertFactura: " . $e->getMessage());
            throw new Exception("Error al insertar la factura: " . $e->getMessage());
        }
    }

    public function insertDetalleFactura($detalleFactura)
    {
        try {
            // Insertar en la tabla `detalle_factura`
            list($idDetalle, $resultado) = $this->queryBuilder->insert('detalle_factura', [
                'factura_id' => $detalleFactura->getFacturaId(),
                'producto_id' => $detalleFactura->getProductoId(),
                'cantidad_facturada' => $detalleFactura->getCantidadFacturada(),
                'precio_unitario' => $detalleFactura->getPrecioUnitario()
            ]);

            // Verificar si la inserción fue exitosa
            if (!$resultado) {
                throw new Exception("No se pudo insertar el detalle de la factura.");
            }

            return $idDetalle; // Retorna el ID del detalle insertado

        } catch (Exception $e) {
            $this->logger->error("Error en insertDetalleFactura: " . $e->getMessage());
            throw new Exception("Error al insertar el detalle de la factura: " . $e->getMessage());
        }
    }

    public function getCuotasByFacturaId($id)
    {
        try {
            return $this->queryBuilder->select('cuota', '*', ['factura_id' => $id]);
        } catch (Exception $e) {
            $this->logger->error("Error en getCuotasByFacturaId: " . $e->getMessage());
            throw new Exception("Error al obtener las cuotas de la factura.");
        }
    }
    

    public function getFacturasPaginated($limit, $offset, $search = '')
    {
        return $this->queryBuilder->getFacturasPaginatedQuery($limit, $offset, $search);
    }
    
    public function countFacturas($search = '')
    {
        return $this->queryBuilder->countFacturasQuery($search);
    }

    public function getFacturaById($id)
    {
        return $this->queryBuilder->getFacturaById($id);
    }
    
    public function getDetalleFacturaByFacturaId($id)
    {
        return $this->queryBuilder->getDetalleFacturaByFacturaId($id);
    }
    
    public function actualizarFactura(array $datos)
    {
        try {
            if (!isset($datos['id'])) {
                throw new Exception("No se especificó el ID de la factura para actualizar.");
            }
    
            $id = $datos['id'];
            unset($datos['id']); // Lo removemos para evitar que se intente actualizar el ID
    
            if (empty($datos)) {
                throw new Exception("No hay datos para actualizar la factura.");
            }
    
            $this->logger->info("Actualizando factura ID: $id", $datos);
    
            $resultado = $this->queryBuilder->update('factura', $datos, ['id' => $id]);
    
            return $resultado;
        } catch (PDOException $e) {
            $this->logger->error("Error SQL al actualizar factura: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->logger->error("Error general al actualizar factura: " . $e->getMessage());
            return false;
        }
    }
    

}