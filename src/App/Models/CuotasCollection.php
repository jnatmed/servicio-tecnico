<?php

namespace Paw\App\Models;

use Paw\Core\Model;
use Paw\App\Models\Cuota;
use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

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
                list($idGenerado, $success) = $this->queryBuilder->insert('cuota', [
                    'factura_id' => $cuota->getFacturaId(),
                    'nro_cuota' => $cuota->getNroCuota(),
                    'estado' => $cuota->getEstado(),
                    'fecha_vencimiento' => $cuota->getFechaVencimiento()
                ]);

                if ($success) {
                    $this->logger->info("Cuota ID {$idGenerado} (N° {$cuota->getNroCuota()}) generada exitosamente con vencimiento: {$cuota->getFechaVencimiento()}");
                } else {
                    $this->logger->error("Error al insertar la cuota (Factura ID: {$cuota->getFacturaId()}, N° {$cuota->getNroCuota()}) en la base de datos.");
                }

            }

        } catch (Exception $e) {
            $this->logger->error("Error en generarCuotas: " . $e->getMessage());
            throw new Exception("Error al generar las cuotas.");
        }
    }


    public function getAllFilteredByDate()
    {

    }
}
