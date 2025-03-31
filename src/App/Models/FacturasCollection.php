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
    

}