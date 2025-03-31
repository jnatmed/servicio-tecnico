<?php

namespace Paw\App\Models;

use Paw\Core\Model;
use Paw\App\Models\Cuota;
use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

use PDO;

class CuotasCollection extends Model
{
    use Loggable;

    public function __construct($qb = null, $logger = null)
    {
        if ($qb || $logger) {
            parent::__construct($qb, $logger);
        }
    }

    /**
     * Genera las cuotas para una factura dada.
     *
     * @param int $facturaId
     * @param float $totalFacturado
     * @param int $cantidadCuotas
     * @throws Exception
     */
    public function generarCuotas(int $facturaId, float $totalFacturado, int $cantidadCuotas)
    {
        try {
            $this->logger->info("Generando cuotas para la factura ID: " . $facturaId);

            // Calcular importe por cuota
            $importeCuota = round($totalFacturado / $cantidadCuotas, 2);
            $this->logger->info("Importe por cuota calculado: " . $importeCuota);

            // Insertar cuotas en la base de datos
            for ($i = 1; $i <= $cantidadCuotas; $i++) {
                $fechaVencimiento = date('Y-m-d', strtotime("+{$i} month"));

                $this->logger->debug("datos cuota:", [
                    'factura_id' => $facturaId,
                    'nro_cuota' => $i,
                    'estado' => 'pendiente',
                    'fecha_vencimiento' => $fechaVencimiento
                ]);
                // Crear instancia de Cuota (validaciones en el constructor)
                $cuota = new Cuota([
                    'factura_id' => $facturaId,
                    'nro_cuota' => $i,
                    'estado' => 'pendiente',
                    'fecha_vencimiento' => $fechaVencimiento
                ], $this->logger);

                // Insertar en la base de datos
                list($cuotaId, $success) = $this->queryBuilder->insert('cuota', [
                    'factura_id' => $cuota->getFacturaId(),
                    'nro_cuota' => $cuota->getNroCuota(),
                    'estado' => $cuota->getEstado(),
                    'fecha_vencimiento' => $cuota->getFechaVencimiento()
                ]);

                if ($success) {

                    $this->logger->info("Cuota ID {$cuotaId} generada.");

                    // 🔍 Buscar datos de la factura para vincular agente
                    $factura = $this->queryBuilder->select('factura', '*', ['id' => $facturaId])[0] ?? null;
                    if (!$factura) {
                        throw new Exception("No se encontró la factura ID {$facturaId}.");
                    }
            
                    // 📥 Registrar movimiento en cuenta corriente
                    $movimiento = new CuentaCorriente([
                        'agente_id' => $factura['id_agente'],
                        'fecha' => $fechaVencimiento,
                        'descripcion' => "Cuota {$i}/{$cantidadCuotas} - Factura N° {$factura['nro_factura']}",
                        'condicion_venta' => $factura['condicion_venta'],
                        'tipo_movimiento' => 'debito',
                        'monto' => $importeCuota,
                        'saldo' => null,
                        'cuota_id' => $cuotaId
                    ], $this->logger);
            
                    $cuentaCorriente = new CuentaCorrienteCollection($this->queryBuilder, $this->logger);
                    $cuentaCorriente->registrarMovimiento($movimiento);

                    $this->logger->info("Cuota ID {$cuotaId} (N° {$cuota->getNroCuota()}) generada exitosamente con vencimiento: {$cuota->getFechaVencimiento()}");
                } else {
                    $this->logger->error("Error al insertar la cuota (Factura ID: {$cuota->getFacturaId()}, N° {$cuota->getNroCuota()}) en la base de datos.");
                }

            }

        } catch (Exception $e) {
            $this->logger->error("Error en generarCuotas: " . $e->getMessage());
            throw new Exception("Error al generar las cuotas.");
        }
    }


    public function getCuotasByFecha($desde, $hasta, $limit, $offset)
    {
        try {
            $this->logger->info("Obteniendo cuotas entre $desde y $hasta con limit $limit y offset $offset.");
    
            return $this->queryBuilder->query("
                SELECT cuota.*, factura.nro_factura
                FROM cuota
                INNER JOIN factura ON factura.id = cuota.factura_id
                WHERE cuota.fecha_vencimiento BETWEEN :desde AND :hasta
                ORDER BY cuota.fecha_vencimiento ASC
                LIMIT :limit OFFSET :offset
            ", [
                ':desde' => $desde,
                ':hasta' => $hasta,
                ':limit' => (int)$limit,
                ':offset' => (int)$offset
            ]);
        } catch (Exception $e) {
            $this->logger->error("Error en getCuotasByFecha: " . $e->getMessage());
            return [];
        }
    }
    

    public function countCuotasByFecha($desde, $hasta)
    {
        try {
            $this->logger->info("Contando cuotas entre $desde y $hasta.");
    
            $result = $this->queryBuilder->query("
                SELECT COUNT(*) as total
                FROM cuota
                WHERE fecha_vencimiento BETWEEN :desde AND :hasta
            ", [
                ':desde' => $desde,
                ':hasta' => $hasta
            ]);
    
            return $result[0]['total'] ?? 0;
    
        } catch (Exception $e) {
            $this->logger->error("Error en countCuotasByFecha: " . $e->getMessage());
            return 0;
        }
    }
    

    public function hayCuotasPagadas($desde, $hasta)
    {
        try {
            $this->logger->info("Verificando cuotas pagadas entre $desde y $hasta.");
    
            $result = $this->queryBuilder->query("
                SELECT COUNT(*) as total
                FROM cuota
                WHERE estado = 'pagada'
                AND fecha_vencimiento BETWEEN :desde AND :hasta
            ", [
                ':desde' => $desde,
                ':hasta' => $hasta
            ]);
    
            return $result[0]['total'] > 0;
    
        } catch (Exception $e) {
            $this->logger->error("Error en hayCuotasPagadas: " . $e->getMessage());
            return false;
        }
    }

    public function generarTextoExportacion($desde, $hasta)
    {
        try {
            $this->logger->info("Generando texto exportable de cuotas entre $desde y $hasta.");
    
            $registros = $this->queryBuilder->query("
                SELECT 
                    d.nombre_dependencia,
                    a.credencial,
                    a.apellido,
                    a.nombre,
                    a.cuil
                FROM cuota c
                INNER JOIN factura f ON f.id = c.factura_id
                INNER JOIN agente a ON a.id = f.id_agente
                INNER JOIN dependencia d ON d.id = a.dependencia
                WHERE c.fecha_vencimiento BETWEEN :desde AND :hasta
            ", [
                ':desde' => $desde,
                ':hasta' => $hasta
            ]);
    
            $contenido = "";
            $this->logger->debug("resultado consulta generarTextoExportacion: ", [$registros]);    
            foreach ($registros as $r) {
                // Concatenar nombre completo
                $nombreCompleto = strtoupper(trim($r['apellido'] . ' ' . $r['nombre']));
                $nombreFormateado = str_pad(substr($nombreCompleto, 0, 30), 30, ' ', STR_PAD_RIGHT);
    
                // CUIT/CUIL: asegurar 11 dígitos (rellenar con ceros si hiciera falta)
                $cuilLimpio = $r['cuil'] !== null ? preg_replace('/\D/', '', $r['cuil']) : '';
                $cuil = str_pad($cuilLimpio, 11, '0', STR_PAD_LEFT);
    
                // Línea formateada
                $linea = sprintf(
                    "%-4s %-6s%s%s+ 00       608\n",
                    substr($r['nombre_dependencia'], 0, 4), // Abreviado a 4 caracteres si es largo
                    $r['credencial'],
                    $nombreFormateado,
                    $cuil
                );
    
                $contenido .= $linea;
            }
    
            $this->logger->debug("Contenido TXT generado:", [$contenido]);
            return $contenido;
    
        } catch (Exception $e) {
            $this->logger->error("Error en generarTextoExportacion: " . $e->getMessage());
            return "ERROR EN EXPORTACIÓN";
        }
    }
        
    

    
}
