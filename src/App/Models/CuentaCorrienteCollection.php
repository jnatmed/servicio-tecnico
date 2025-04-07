<?php

namespace Paw\App\Models;

use Paw\Core\Model;
use Paw\Core\Traits\Loggable;
use Exception;
use Paw\App\Models\CuentaCorriente;

class CuentaCorrienteCollection extends Model
{
    use Loggable;

    public function obtenerExtractoConSaldo($agente_id)
    {
        // Consulta SQL con LEFT JOIN para obtener también el factura_id de cuota si existe
        $sql = "
            SELECT 
                cc.*,
                c.factura_id
            FROM cuenta_corriente cc
            LEFT JOIN cuota c ON cc.cuota_id = c.id
            WHERE cc.agente_id = :agente_id
            ORDER BY cc.fecha ASC, cc.id ASC
        ";
    
        $movimientos = $this->queryBuilder->query($sql, [':agente_id' => $agente_id]);
    
        $saldo = 0;
        foreach ($movimientos as &$mov) {
            $tipo = strtolower($mov['tipo_movimiento']);
            if ($tipo === 'credito') {
                $saldo += $mov['monto'];
            } elseif ($tipo === 'debito') {
                $saldo -= $mov['monto'];
            }
            $mov['saldo_acumulado'] = $saldo;
        }
    
        return $movimientos;
    }
    

    public function registrarMovimiento(CuentaCorriente $movimiento)
    {
        try {
            $this->logger->info('Capa Modelo: datos Movimiento', [$movimiento->toArray()]);
            return $this->queryBuilder->insert('cuenta_corriente', $movimiento->toArray());

        } catch (Exception $e) {
            $this->logger->error("Error al registrar movimiento en cuenta corriente: " . $e->getMessage());
            throw new Exception("No se pudo registrar el movimiento.");
        }
    }

    public function obtenerSaldoActual($agente_id)
    {
        try {
            $sql = "
                SELECT 
                    SUM(CASE WHEN tipo_movimiento = 'credito' THEN monto ELSE 0 END) AS total_creditos,
                    SUM(CASE WHEN tipo_movimiento = 'debito' THEN monto ELSE 0 END) AS total_debitos
                FROM cuenta_corriente
                WHERE agente_id = :agente_id
            ";
    
            $result = $this->queryBuilder->query($sql, [':agente_id' => $agente_id]);
    
            if (!$result || !isset($result[0])) {
                return 0;
            }
    
            $creditos = (float) $result[0]['total_creditos'];
            $debitos = (float) $result[0]['total_debitos'];
    
            return $creditos - $debitos;
        } catch (Exception $e) {
            $this->logger->error("Error al obtener saldo desde SQL: " . $e->getMessage());
            return 0;
        }
    }
    
    public function obtenerCuotasDescuentoPorPeriodo($periodo)
    {
        $sql = "
            SELECT c.*, f.condicion_venta, f.id_agente, f.nro_factura, f.fecha_factura
            FROM cuota c
            INNER JOIN factura f ON f.id = c.factura_id
            WHERE c.periodo = :periodo
              AND c.estado IN ('pendiente', 'reprogramada')
              AND f.condicion_venta IN ('codigo_608', 'codigo_689')
            ORDER BY f.id_agente, 
                     CASE c.estado WHEN 'reprogramada' THEN 0 ELSE 1 END, 
                     c.id ASC
        ";
    
        $cuotas = $this->queryBuilder->query($sql, [':periodo' => $periodo]);
        $resultado = [];
    
        foreach ($cuotas as $cuota) {
            $resultado[$cuota['id_agente']][] = $cuota;
        }
    
        return $resultado;
    }
    
    public function procesarReporteDescuentosHaberes($periodo)
    {
        $cuotasPorAgente = $this->obtenerCuotasDescuentoPorPeriodo($periodo);
        $this->logger->info("Procesando cuotas para descuento de haberes", ['periodo' => $periodo]);
    
        $reporte = [];
    
        foreach ($cuotasPorAgente as $agenteId => $cuotas) {
            $total = 0;
            $cuotasIncluidas = [];
    
            foreach ($cuotas as $cuota) {
                if ($total + $cuota['monto'] > 100000) {
                    // Reprogramar cuota para el próximo periodo
                    $this->queryBuilder->query(
                        "UPDATE cuota SET estado = 'reprogramada', periodo = DATE_ADD(periodo, INTERVAL 1 MONTH) WHERE id = :id",
                        [':id' => $cuota['id']]
                    );
                    continue;
                }
    
                // Marcar como pagada (si se desea dejar asentado en el sistema)
                $this->queryBuilder->query(
                    "UPDATE cuota SET estado = 'pagada' WHERE id = :id",
                    [':id' => $cuota['id']]
                );
    
                // Registrar en cuenta corriente
                $this->registrarMovimiento(new CuentaCorriente([
                    'agente_id' => $cuota['id_agente'],
                    'fecha' => date('Y-m-d'),
                    'descripcion' => "Descuento cuota Nro {$cuota['nro_cuota']} de Factura {$cuota['nro_factura']}",
                    'condicion_venta' => $cuota['condicion_venta'],
                    'tipo_movimiento' => 'debito',
                    'monto' => $cuota['monto'],
                    'saldo' => null, // Calculado por otro lado si es necesario
                    'cuota_id' => $cuota['id']
                ]));
    
                $total += $cuota['monto'];
                $cuotasIncluidas[] = $cuota;
            }
    
            $reporte[$agenteId] = [
                'cuotas' => $cuotasIncluidas,
                'total' => $total
            ];
        }
    
        return $reporte;
    }
    

}
