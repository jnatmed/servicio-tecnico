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
    
}
