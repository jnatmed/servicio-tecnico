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
    public function contarSinComprobanteAdjunto(): int
    {
        $sql = "SELECT COUNT(*) as total FROM factura WHERE path_comprobante IS NULL OR path_comprobante = ''";

        $resultado = $this->queryBuilder->query($sql);
        return (int) ($resultado[0]['total'] ?? 0);
    }
    
    public function contarTodas(): int
    {
        $sql = "SELECT COUNT(*) as total FROM factura";
        $resultado = $this->queryBuilder->query($sql);
        return (int) ($resultado[0]['total'] ?? 0);
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
    

    public function getSolicitudesPendientes()
    {
        $sql = "
            SELECT nf.*, d.nombre_dependencia
            FROM numerador_factura nf
            INNER JOIN dependencia d ON d.id = nf.dependencia_id
            WHERE estado_solicitud_numeracion = 'pendiente'
            ORDER BY fecha_solicitud DESC
        ";
    
        return $this->queryBuilder->query($sql);
    }
    
    
    public function getUltimaAceptadaPorDependencia($dependenciaId)
    {
        $sql = "
            SELECT *
            FROM numerador_factura
            WHERE dependencia_id = :dependencia_id
              AND estado_solicitud_numeracion = 'aceptada'
            ORDER BY fecha_solicitud DESC
            LIMIT 1
        ";
    
        return $this->queryBuilder->query($sql, ['dependencia_id' => $dependenciaId]);
    }

    public function getUltimasSolicitudesPorDependencia()
    {
        try {
            $sql = "
                SELECT *
                FROM (
                    SELECT nf.*, d.descripcion,
                        ROW_NUMBER() OVER (
                            PARTITION BY nf.dependencia_id
                            ORDER BY
                                CASE
                                    WHEN nf.estado_solicitud_numeracion = 'pendiente' THEN 1
                                    WHEN nf.estado_solicitud_numeracion = 'aceptada' THEN 2
                                    ELSE 3
                                END,
                                nf.fecha_solicitud DESC
                        ) AS rn
                    FROM numerador_factura nf
                    INNER JOIN dependencia d ON d.id = nf.dependencia_id
                ) t
                WHERE t.rn = 1
                ORDER BY t.descripcion ASC;
            ";
    
            return $this->queryBuilder->query($sql);

            $this->logger->debug('Resultado SQL:', [$resultado]);
    
        } catch (\Exception $e) {
            $this->logger->error('Error en getUltimasSolicitudesAceptadas(): ' . $e->getMessage());
            return [];
        }
    }
    

    public function aceptarSolicitudPorId($id)
    {
        try {
            $sql = "UPDATE numerador_factura SET estado_solicitud_numeracion = 'aceptada' WHERE id = :id";
            $this->logger->info("Intentando aceptar solicitud de numeración con ID: $id");
    
            $this->queryBuilder->query($sql, ['id' => $id]);
    
            $this->logger->info("Solicitud aceptada correctamente para ID: $id");
        } catch (\Exception $e) {
            $this->logger->error("Error en aceptarSolicitudPorId($id): " . $e->getMessage());
            throw $e;
        }
    }

    public function rechazarSolicitudPorId($id, $motivo)
    {
        try {
            $sql = "UPDATE numerador_factura 
                    SET estado_solicitud_numeracion = 'rechazada', motivo_rechazo = :motivo 
                    WHERE id = :id";
    
            $this->logger->info("Intentando rechazar solicitud de numeración con ID: $id y motivo: $motivo");
    
            $this->queryBuilder->query($sql, [
                'id' => $id,
                'motivo' => $motivo
            ]);
    
            $this->logger->info("Solicitud rechazada correctamente para ID: $id");
        } catch (\Exception $e) {
            $this->logger->error("Error en rechazarSolicitudPorId($id): " . $e->getMessage());
            throw $e;
        }
    }
        

    public function getHistorialPorDependencia($dependenciaId)
    {
        $sql = "
            SELECT *
            FROM numerador_factura
            WHERE dependencia_id = :dependencia_id
            ORDER BY fecha_solicitud DESC
        ";

        return $this->queryBuilder->query($sql, ['dependencia_id' => $dependenciaId]);
    }

    public function aceptarSolicitudNumeracion($numeradorId)
    {
        $sql = "
            UPDATE numerador_factura
            SET estado_solicitud_numeracion = 'aceptada',
                motivo_rechazo = NULL
            WHERE id = :id
        ";
    
        return $this->queryBuilder->query($sql, ['id' => $numeradorId]);
    }

    public function rechazarSolicitudNumeracion($numeradorId, $motivo)
    {
        $sql = "
            UPDATE numerador_factura
            SET estado_solicitud_numeracion = 'rechazada',
                motivo_rechazo = :motivo
            WHERE id = :id
        ";
    
        return $this->queryBuilder->query($sql, [
            'id' => $numeradorId,
            'motivo' => $motivo
        ]);
    }
        
    public function getProximoNumeroFacturaPorUsuario($usuarioId)
    {
        $sql = "
            SELECT 
                nf.id AS id_numerador,
                nf.ultimo_utilizado,
                nf.hasta,
                d.punto_venta,
                d.id AS dependencia_idUser,
                s.estado AS estado_dependencia
            FROM solicitud_asignacion_dependencia s
            JOIN dependencia d ON s.dependencia_id = d.id
            JOIN numerador_factura nf ON nf.dependencia_id = d.id
            WHERE s.usuario_id = :usuarioId
            ORDER BY s.fecha_solicitud DESC
            LIMIT 1
        ";

        $result = $this->queryBuilder->query($sql, ['usuarioId' => $usuarioId]);

        if (count($result) === 0) {
            return [
                'success' => false,
                'message' => 'No hay solicitudes realizadas por este usuario.'
            ];
        }

        $numerador = $result[0];

        if ($numerador['estado_dependencia'] === 'confirmado') {
            if ($numerador['ultimo_utilizado'] >= $numerador['hasta']) {
                return [
                    'success' => false,
                    'message' => 'Se alcanzó el límite de numeración para este punto de venta.'
                ];
            }
        }

        return [
            'success' => true,
            'id_numerador' => $numerador['id_numerador'],
            'proximo_numero' => $numerador['ultimo_utilizado'] + 1,
            'punto_venta' => $numerador['punto_venta'],
            'dependencia_idUser' => $numerador['dependencia_idUser'],
            'estado_dependencia' => $numerador['estado_dependencia']
        ];
    }


    
    public function actualizarNumeradorFactura($idNumerador, $nuevoValor) {
        $sql = "UPDATE numerador_factura SET ultimo_utilizado = :nuevoValor WHERE id = :idNumerador";
        $this->queryBuilder->query($sql, [
            'nuevoValor' => $nuevoValor,
            'idNumerador' => $idNumerador
        ]);
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
    

    public function getFacturasPaginated($limit, $offset, $search = '', $sinComprobante = false, $dependenciaId=null, $rolUsuario=null)
    {
        return $this->queryBuilder->getFacturasPaginatedQuery($limit, $offset, $search, $sinComprobante, $dependenciaId, $rolUsuario);
    }
    
    public function countFacturas($search = '', $sinComprobante = false, $dependenciaId=null, $rolUsuario=null)
    {
        return $this->queryBuilder->countFacturasQuery($search, $sinComprobante, $dependenciaId, $rolUsuario=null);
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