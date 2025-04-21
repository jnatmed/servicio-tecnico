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
        parent::__construct($qb, $logger);
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
                    'monto' =>$importeCuota,
                    'estado' => 'pendiente',
                    'fecha_vencimiento' => $fechaVencimiento
                ]);
                // Crear instancia de Cuota (validaciones en el constructor)
                $cuota = new Cuota([
                    'factura_id' => $facturaId,
                    'nro_cuota' => $i,
                    'monto' =>$importeCuota,                    
                    'estado' => 'pendiente',
                    'fecha_vencimiento' => $fechaVencimiento
                ], $this->logger);

                // Insertar en la base de datos
                list($cuotaId, $success) = $this->queryBuilder->insert('cuota', [
                    'factura_id' => $cuota->getFacturaId(),
                    'nro_cuota' => $cuota->getNroCuota(),
                    'monto' => $cuota->getMonto(),
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
                        'fecha' => date('Y-m-d'), // fecha actual, fecha real del movimiento
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


    public function getDetalleSolicitudesPendientesPorFecha($fecha = null)
    {
        try {
            $this->logger->info("Consultando solicitudes agrupadas por agente" . ($fecha ? " para fecha $fecha" : ""));
    
            $sql = "
                SELECT 
                    CONCAT(sdh.fecha_solicitud, 'T12:00:00') AS fecha_solicitud,
                    COUNT(DISTINCT a.id) AS total_agentes,
                    SUM(c.monto_pagado) AS total_a_descontar,
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'agente_id', a.id,
                            'agente', CONCAT(a.nombre, ' ', a.apellido),
                            'cuota_id', c.id,
                            'nro_factura', f.nro_factura,
                            'monto', c.monto,
                            'monto_pagado', c.monto_pagado,
                            'monto_reprogramado', c.monto_reprogramado,
                            'estado', c.estado
                        )
                    ) AS cuotas
                FROM solicitud_descuento_haberes sdh
                INNER JOIN cuota c ON c.id = sdh.cuota_id
                INNER JOIN factura f ON f.id = c.factura_id
                INNER JOIN agente a ON a.id = f.id_agente
                WHERE sdh.resultado = 'pendiente'
                GROUP BY sdh.fecha_solicitud
                ORDER BY sdh.fecha_solicitud DESC
            ";
    
            $params = [];
    
            if ($fecha) {
                $sql .= " AND sdh.fecha_solicitud = :fecha";
                $params[':fecha'] = $fecha;
            }
    
            $resultados = $this->queryBuilder->query($sql, $params);

            foreach ($resultados as &$grupo) {
                if (isset($grupo['cuotas']) && is_string($grupo['cuotas'])) {
                    $grupo['cuotas'] = json_decode($grupo['cuotas'], true);
                }
            }            
            
            return $resultados;
    
        } catch (Exception $e) {
            $this->logger->error("Error en getSolicitudesPendientesPorFecha: " . $e->getMessage());
            return [];
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

    public function generarTextoExportacion($fechaSolicitud)
    {
        try {
            $this->logger->info("Generando TXT para solicitudes con fecha $fechaSolicitud (pendientes).");
    
            $registros = $this->queryBuilder->query("
                SELECT 
                    d.nombre_dependencia,
                    a.credencial,
                    SUM(COALESCE(c.monto_pagado, 0)) AS total_descontado
                FROM solicitud_descuento_haberes s
                INNER JOIN cuota c ON c.id = s.cuota_id
                INNER JOIN factura f ON f.id = c.factura_id
                INNER JOIN agente a ON a.id = f.id_agente
                INNER JOIN dependencia d ON d.id = a.dependencia
                WHERE s.resultado = 'pendiente'
                AND s.fecha_solicitud = :fecha_solicitud
                GROUP BY d.nombre_dependencia, a.credencial
            ", [
                ':fecha_solicitud' => $fechaSolicitud
            ]);
    
            $contenido = "";
            foreach ($registros as $r) {
                $dependencia = strtoupper(substr($r['nombre_dependencia'], 0, 2));
                $credencial = str_pad($r['credencial'], 6, '0', STR_PAD_LEFT);
                $monto = floatval($r['total_descontado']);
    
                $montoFormateado = number_format($monto, 2, '.', '');
                $montoNumerico = str_replace('.', '', $montoFormateado);
                $montoFinal = str_pad($montoNumerico, 9, '0', STR_PAD_LEFT);
    
                $linea = "41{$dependencia}   0{$credencial}11 608{$montoFinal}\n";
                $contenido .= $linea;
            }
    
            return $contenido;
    
        } catch (Exception $e) {
            $this->logger->error("Error en generarTextoExportacionPorSolicitud: " . $e->getMessage());
            return "ERROR EN EXPORTACIÓN";
        }
    }
    
    
    
        
    public function getCuotasAgrupadasPorAgente($desde, $hasta)
    {
        try {
            $this->logger->info("Obteniendo cuotas agrupadas por agente entre $desde y $hasta (excluyendo pagadas).");

            $resultados = $this->queryBuilder->query("
                    SELECT 
                        c.id,
                        a.id AS agente_id,
                        CONCAT(a.nombre, ' ', a.apellido) AS nombre_agente,
                        f.nro_factura,
                        c.nro_cuota,
                        c.monto,
                        c.monto_pagado,
                        c.monto_reprogramado,
                        c.fecha_vencimiento,
                        c.periodo,
                        CASE 
                            WHEN c.estado = 'pendiente' THEN 'pendiente'
                            WHEN c.estado = 'reprogramada' THEN 'a-reprogramar'
                            ELSE c.estado
                        END AS estado
                    FROM cuota c
                    INNER JOIN factura f ON f.id = c.factura_id
                    INNER JOIN agente a ON a.id = f.id_agente
                    WHERE IFNULL(c.periodo, c.fecha_vencimiento) BETWEEN :desde AND :hasta
                    AND c.estado != 'pagada'
                    ORDER BY a.id, IFNULL(c.periodo, c.fecha_vencimiento)
            ", [
                ':desde' => $desde,
                ':hasta' => $hasta
            ]);

            $agrupadas = [];

            foreach ($resultados as $fila) {
                $idAgente = $fila['agente_id'];
                if (!isset($agrupadas[$idAgente])) {
                    $agrupadas[$idAgente] = [
                        'agente_id' => $idAgente,
                        'agente' => $fila['nombre_agente'],
                        'cuotas' => [],
                        'total' => 0
                    ];
                }

                // Sumar solo si la cuota está pendiente (las a-reprogramar no se descuentan)
                if ($fila['estado'] === 'pendiente') {
                    $agrupadas[$idAgente]['total'] += $fila['monto'];
                }

                $agrupadas[$idAgente]['cuotas'][] = [
                    'id' => $fila['id'],
                    'nro_factura' => $fila['nro_factura'],
                    'nro_cuota' => $fila['nro_cuota'],
                    'monto' => $fila['monto'],
                    'monto_pagado' => $fila['monto_pagado'],
                    'monto_reprogramado' => $fila['monto_reprogramado'],
                    'fecha_vencimiento' => $fila['fecha_vencimiento'],
                    'estado' => $fila['estado'],
                    'periodo' => $fila['periodo']
                ];
            }

            $this->logger->debug("Cuotas agrupadas excluyendo pagadas:", [$agrupadas]);

            return array_values($agrupadas);
        } catch (Exception $e) {
            $this->logger->error("Error en getCuotasAgrupadasPorAgente: " . $e->getMessage());
            return [];
        }
    }

    
    
        

    public function aplicarDescuentoDeHaberesInteligente($agenteId, $desde, $hasta)
    {
        try {
            $this->logger->info("Aplicando descuento con lógica optimizada para el agente $agenteId.", [$desde, $hasta]);
    
            $tope = 100000.00;
            $acumulado = 0.00;
            $totalDescontado = 0.00;
            $detalleDescuento = [];
            $movimientosCuentaCorriente = [];
    
            $cuotas = $this->queryBuilder->query("
                SELECT c.id, c.factura_id, c.monto, c.monto_pagado, c.monto_reprogramado
                FROM cuota c
                INNER JOIN factura f ON f.id = c.factura_id
                WHERE f.id_agente = :agenteId
                  AND c.estado IN ('pendiente', 'reprogramada')
                  AND IFNULL(c.periodo, c.fecha_vencimiento) BETWEEN :desde AND :hasta
                ORDER BY IFNULL(c.periodo, c.fecha_vencimiento) ASC, c.id ASC
            ", [
                ':agenteId' => $agenteId,
                ':desde' => $desde,
                ':hasta' => $hasta
            ]);
    
            foreach ($cuotas as $cuota) {
                $id = $cuota['id'];
                $facturaId = $cuota['factura_id'];
                $montoTotal = (float)$cuota['monto'];
                $pagado = (float)$cuota['monto_pagado'];
                $reprogramado = (float)$cuota['monto_reprogramado'];
    
                $pendiente = $reprogramado > 0 ? $reprogramado : $montoTotal - $pagado;
    
                if ($pendiente <= 0) {
                    continue;
                }
    
                $factura = $this->queryBuilder->select(
                    'factura',
                    'id, nro_factura, condicion_venta, id_agente',
                    ['id' => $facturaId]
                )[0] ?? null;
    
                if (!$factura) {
                    $this->logger->warning("Factura no encontrada para cuota $id");
                    continue;
                }
    
                $montoDescontado = 0.00;
                $nuevoReprogramado = 0.00;
    
                if ($acumulado + $pendiente <= $tope) {
                    // Pago total
                    $montoDescontado = $pendiente;
    
                    $this->queryBuilder->query(
                        "UPDATE cuota 
                         SET estado = 'pagada', 
                             monto_pagado = monto_pagado + :monto,
                             monto_reprogramado = 0
                         WHERE id = :id",
                        [':monto' => $montoDescontado, ':id' => $id]
                    );
    
                    $detalleDescuento[] = "$" . number_format($montoDescontado, 2, '.', '') . " de cuota #$id";
                } elseif ($acumulado < $tope) {
                    // Pago parcial
                    $montoDescontado = $tope - $acumulado;
                    $nuevoReprogramado = $pendiente - $montoDescontado;
    
                    $this->queryBuilder->query(
                        "UPDATE cuota 
                         SET estado = 'reprogramada',
                             monto_pagado = monto_pagado + :pagado,
                             monto_reprogramado = :reprogramado,
                             periodo = DATE_ADD(IFNULL(periodo, fecha_vencimiento), INTERVAL 1 MONTH)
                         WHERE id = :id",
                        [
                            ':pagado' => $montoDescontado,
                            ':reprogramado' => $nuevoReprogramado,
                            ':id' => $id
                        ]
                    );
    
                    $detalleDescuento[] = "parcial: $" . number_format($montoDescontado, 2, '.', '') . " de cuota #$id";
                } else {
                    // No hay más cupo para descontar
                    $this->queryBuilder->query(
                        "UPDATE cuota 
                         SET estado = 'reprogramada',
                             monto_reprogramado = :reprogramado,
                             periodo = DATE_ADD(IFNULL(periodo, fecha_vencimiento), INTERVAL 1 MONTH)
                         WHERE id = :id",
                        [
                            ':reprogramado' => $pendiente,
                            ':id' => $id
                        ]
                    );
                    continue; // No insertamos nada si no hubo pago
                }
    
                // Solo si se descontó algo
                if ($montoDescontado > 0) {
                    $saldoRestante = $montoTotal - ($pagado + $montoDescontado);
    
                    $this->queryBuilder->insert('cuenta_corriente', [
                        'agente_id' => $factura['id_agente'],
                        'fecha' => date('Y-m-d'),
                        'descripcion' => ($nuevoReprogramado > 0 ? 'Descuento parcial' : 'Descuento total') . " cuota #$id - Factura N° {$factura['nro_factura']}",
                        'condicion_venta' => $factura['condicion_venta'],
                        'tipo_movimiento' => 'credito',
                        'monto' => $montoDescontado,
                        'saldo' => $saldoRestante,
                        'cuota_id' => $id
                    ]);
    
                    $movimientosCuentaCorriente[] = [
                        'cuota_id' => $id,
                        'monto' => $montoDescontado,
                        'saldo' => $saldoRestante,
                        'descripcion' => "Descuento " . ($nuevoReprogramado > 0 ? 'parcial' : 'total') . " cuota #$id",
                        'fecha' => date('Y-m-d')
                    ];
    
                    $acumulado += $montoDescontado;
                    $totalDescontado += $montoDescontado;

                    // Verificamos si ya existe una solicitud pendiente para esta cuota
                    $solicitudes = $this->queryBuilder->select('solicitud_descuento_haberes', '*', [
                        'cuota_id' => $id,
                        'resultado' => 'pendiente'
                    ]);

                    if (empty($solicitudes)) {
                        // Insertar solicitud pendiente
                        $this->queryBuilder->insert('solicitud_descuento_haberes', [
                            'cuota_id' => $id,
                            'fecha_solicitud' => date('Y-m-d'),
                            'resultado' => 'pendiente',
                            'motivo' => null
                        ]);
                        
                        $this->logger->info("Solicitud de descuento registrada para cuota #$id");
                    } else {
                        $this->logger->info("Solicitud ya existente para cuota #$id, se omite inserción duplicada.");
                    }
                }
            }
    
            // Consultar cuotas actualizadas
            $cuotasActualizadas = $this->queryBuilder->query("
                SELECT c.id, f.nro_factura, c.nro_cuota, c.monto, c.monto_pagado, c.monto_reprogramado, 
                       c.fecha_vencimiento, c.periodo, c.estado
                FROM cuota c
                INNER JOIN factura f ON f.id = c.factura_id
                WHERE f.id_agente = :agenteId
                  AND IFNULL(c.periodo, c.fecha_vencimiento) BETWEEN :desde AND :hasta
                  AND c.estado IN ('pagada', 'reprogramada')
                ORDER BY IFNULL(c.periodo, c.fecha_vencimiento) ASC
            ", [
                ':agenteId' => $agenteId,
                ':desde' => $desde,
                ':hasta' => $hasta
            ]);
    
            // Calcular total adeudado post-descuento
            $deudaPendiente = $this->queryBuilder->query("
                SELECT SUM(monto - monto_pagado) AS total
                FROM cuota c
                INNER JOIN factura f ON f.id = c.factura_id
                WHERE f.id_agente = :agenteId
                  AND c.estado IN ('pendiente', 'reprogramada')
            ", [
                ':agenteId' => $agenteId
            ])[0]['total'] ?? 0.00;
    
            return [
                'cuotas' => $cuotasActualizadas,
                'total_descontado' => $totalDescontado,
                'detalle_descontado' => $detalleDescuento,
                'movimientos' => $movimientosCuentaCorriente,
                'total_adeudado' => (float) $deudaPendiente
            ];
    
        } catch (Exception $e) {
            $this->logger->error("Error en aplicarDescuentoDeHaberesInteligente: " . $e->getMessage());
            return false;
        }
    }
    
        
    public function confirmarDescuentosPorAgente(string $fecha, array $descuentos): array
    {
        try {
            $this->logger->info("💾 confirmando descuentos desde modelo para fecha: $fecha");
    
            return $this->queryBuilder->confirmarDescuentosParaFecha($fecha, $descuentos);
        } catch (Exception $e) {
            $this->logger->error("❌ Error en confirmarDescuentosPorAgente: " . $e->getMessage());
            throw new Exception("No se pudieron confirmar los descuentos.");
        }
    }
        
    
    
}
