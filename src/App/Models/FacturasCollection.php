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
                'nro_factura' => $factura['nro_factura'],
                'fecha_factura' => date('Y-m-d'), // Se usa la fecha actual
                'unidad_que_factura' => $factura['unidad_que_factura'],
                'total_facturado' => $factura['total_facturado'],
                'condicion_venta' => $factura['condicion_venta'],
                'condicion_impositiva' => $factura['condicion_impositiva'],
                'id_agente' => $factura['id_agente']
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
                'factura_id' => $detalleFactura['factura_id'],
                'producto_id' => $detalleFactura['producto_id'],
                'cantidad_facturada' => $detalleFactura['cantidad_facturada'],
                'precio_unitario' => $detalleFactura['precio_unitario']
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