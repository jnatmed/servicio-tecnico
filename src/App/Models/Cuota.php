<?php

namespace Paw\App\Models;

use Paw\Core\Model;
use Exception;
use Paw\Core\Traits\Loggable;

class Cuota extends Model
{
    use Loggable;

    private $factura_id;
    private $nro_cuota;
    private $estado;
    private $fecha_vencimiento;

    private static $ESTADOS_VALIDOS = ['pagada', 'pendiente'];

    /**
     * Constructor de la clase Cuota con validaciones.
     *
     * @param array $data
     * @param mixed $logger
     * @throws Exception
     */
    public function __construct(array $data = [], $logger = null)
    {
        parent::setLogger($logger);

        try {
            if (!empty($data)) {
                $this->setFacturaId($data['factura_id'] ?? null);
                $this->setNroCuota($data['nro_cuota'] ?? null);
                $this->setEstado($data['estado'] ?? null);
                $this->setFechaVencimiento($data['fecha_vencimiento'] ?? null);
            }
            $this->logger->info("Cuota seteada correctamente", [$this->toArray()]);
            
        } catch (Exception $e) {
            $this->logger->error("Error en Cuota::__construct", [$e->getMessage(), $e]);
            throw new Exception("Error al crear la cuota: " . $e->getMessage());
        }
    }

    // Getters
    public function getFacturaId()
    {
        return $this->factura_id;
    }

    public function getNroCuota()
    {
        return $this->nro_cuota;
    }

    public function getEstado()
    {
        return $this->estado;
    }

    public function getFechaVencimiento()
    {
        return $this->fecha_vencimiento;
    }

    // Setters con validaciones
    public function setFacturaId($factura_id)
    {
        if ($factura_id === null || !is_numeric($factura_id)) {
            $this->logger->error("El ID de factura debe ser un número válido.", [$factura_id]);
            throw new Exception("El ID de factura debe ser un número válido.");
        }
        $this->logger->debug("FacturaId seteada: ", [$factura_id]);
        $this->factura_id = (int) $factura_id;
    }

    public function setNroCuota($nro_cuota)
    {
        if ($nro_cuota === null || !is_numeric($nro_cuota) || $nro_cuota < 1) {
            $this->logger->error("El número de cuota debe ser un número entero positivo.", [$nro_cuota]);
            throw new Exception("El número de cuota debe ser un número entero positivo.");
        }
        $this->logger->debug("NroCuota seteada: ", [$nro_cuota]);
        $this->nro_cuota = (int) $nro_cuota;
    }

    public function setEstado($estado)
    {
        if (!in_array($estado, self::$ESTADOS_VALIDOS)) {
            $this->logger->error("Estado de cuota no válido.", [$estado]);
            throw new Exception("Estado de cuota no válido.");
        }
        $this->logger->debug("Estado seteado: ", [$estado]);
        $this->estado = $estado;
    }

    public function setFechaVencimiento($fecha_vencimiento)
    {
        if ($fecha_vencimiento === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_vencimiento)) {
            $this->logger->error("Fecha de vencimiento inválida", [$fecha_vencimiento]);
            throw new Exception("La fecha de vencimiento debe estar en formato YYYY-MM-DD.");
        }
        $this->logger->debug("FechaVencimiento seteada: ", [$fecha_vencimiento]);
        $this->fecha_vencimiento = $fecha_vencimiento;
    }
}