<?php
namespace Paw\App\Models;

use Paw\Core\Model;
use InvalidArgumentException;

class CuentaCorriente extends Model
{
    private $id;
    public $agente_id;
    public $fecha;
    public $descripcion;
    public $tipo_movimiento;
    public $monto;
    public $saldo;
    public $condicion_venta;

    const TIPOS_MOVIMIENTO = ['debito', 'credito'];
    const CONDICIONES_VENTA = ['contado','cta_cte','codigo_608','codigo_689'];

    public function __construct($data = [], $logger = null)
    {

        if ($logger !== null) {
            parent::setLogger($logger);
        }
        $this->logger->info("Datos CuentaCorriente a setear: ", [$data]);
        // Seteamos los valores a partir de los datos recibidos
        foreach ($data as $campo => $valor) {
            $setter = 'set' . ucfirst($campo);
            if (method_exists($this, $setter)) {
                $this->$setter($valor);
                $this->logger->info("Seteado campo: $campo con valor: $valor");
            }
        }

    }

    // === Setters ===
    public function setAgente_id($id) { $this->agente_id = (int) $id; }
    public function setFecha($fecha)
    {
        if (!strtotime($fecha)) {
            $this->logger->info("Fecha inválida: $fecha");
            throw new InvalidArgumentException("Fecha inválida: $fecha");
        }
        $this->fecha = $fecha;
    }
    public function setDescripcion($desc)
    {
        if (empty($desc)) {
            $this->logger->info("La descripción no puede estar vacía.");
            throw new InvalidArgumentException("La descripción no puede estar vacía.");
        }
        $this->descripcion = $desc;
    }
    public function setTipo_movimiento($tipo)
    {
        if (!in_array($tipo, self::TIPOS_MOVIMIENTO)) {
            $this->logger->info("Tipo de movimiento inválido: $tipo");
            throw new InvalidArgumentException("Tipo de movimiento inválido: $tipo");
        }
        $this->tipo_movimiento = $tipo;
    }
    public function setMonto($monto)
    {
        if (!is_numeric($monto) || $monto < 0) {
            $this->logger->info("Monto inválido: $monto");
            throw new InvalidArgumentException("Monto inválido.");
        }
        $this->monto = (float) $monto;
    }
    public function setSaldo($saldo) { $this->saldo = (float) $saldo; }
    public function setCondicion_venta($cond)
    {
        if (!in_array($cond, self::CONDICIONES_VENTA)) {
            $this->logger->info("Condición de venta inválida: $cond");  
            throw new InvalidArgumentException("Condición de venta inválida: $cond");
        }
        $this->condicion_venta = $cond;
    }

    // === Getters ===
    public function getId() { return $this->id; }
    public function getAgente_id() { return $this->agente_id; }
    public function getFecha() { return $this->fecha; }
    public function getDescripcion() { return $this->descripcion; }
    public function getTipo_movimiento() { return $this->tipo_movimiento; }
    public function getMonto() { return $this->monto; }
    public function getSaldo() { return $this->saldo; }
    public function getCondicion_venta() { return $this->condicion_venta; }

}
