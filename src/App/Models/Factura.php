<?php 

namespace Paw\App\Models;

use Paw\Core\Model;
use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class Factura extends Model
{
    use Loggable;

    private $nro_factura;
    private $fecha_factura;
    private $unidad_que_factura;
    private $total_facturado;
    private $condicion_venta;
    private $condicion_impositiva;
    private $id_agente;

    // Listas de valores permitidos en ENUM
    private static $CONDICIONES_VENTA = ['contado', 'cta_cte', 'codigo_608', 'codigo_689'];
    private static $CONDICIONES_IMPOSITIVAS = [
        'consumidor_final', 'exento', 'no_responsable', 'responsable_monotributo', 'responsable_inscripto'
    ];

    // Constructor con validación
    public function __construct(array $data = [], $logger = null)
    {
        parent::setLogger($logger);
        try {
            if (!empty($data)) {
                $this->logger->info("Instanciando factura", ['data' => $data]);
                
                $this->setNroFactura($data['nro_factura'] ?? null);
                $this->setFechaFactura($data['fecha_factura'] ?? null);
                $this->setUnidadQueFactura($data['unidad_que_factura'] ?? null);
                $this->setTotalFacturado($data['total_facturado'] ?? null);
                $this->setCondicionVenta($data['condicion_venta'] ?? null);
                $this->setCondicionImpositiva($data['condicion_impositiva'] ?? null);
                $this->setIdAgente($data['id_agente'] ?? null);
            }
        } catch (Exception $e) {
            $this->logger->error("Error en Factura::__construct", ['error' => $e->getMessage()]);
            throw new Exception("Error al crear la factura: " . $e->getMessage());
        }
    }

    // Getters
    public function getNroFactura() { return $this->nro_factura; }
    public function getFechaFactura() { return $this->fecha_factura; }
    public function getUnidadQueFactura() { return $this->unidad_que_factura; }
    public function getTotalFacturado() { return $this->total_facturado; }
    public function getCondicionVenta() { return $this->condicion_venta; }
    public function getCondicionImpositiva() { return $this->condicion_impositiva; }
    public function getIdAgente() { return $this->id_agente; }

    // Setters con validaciones
    public function setNroFactura($nro_factura)
    {
        if ($nro_factura !== null && !is_string($nro_factura)) {
            $this->logger->error("Número de factura inválido", ['valor' => $nro_factura]);
            throw new Exception("Número de factura debe ser una cadena de texto.");
        }
        $this->nro_factura = $nro_factura;
        $this->logger->info("Id Factura Seteado correctamente: ", [$this->nro_factura]);
    }

    public function setFechaFactura($fecha_factura)
    {
        if ($fecha_factura !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_factura)) {
            $this->logger->error("Fecha de factura inválida", ['valor' => $fecha_factura]);
            throw new Exception("Fecha de factura debe estar en formato YYYY-MM-DD.");
        }
        $this->fecha_factura = $fecha_factura;
    }

    public function setUnidadQueFactura($unidad_que_factura)
    {
        if ($unidad_que_factura !== null && !is_numeric($unidad_que_factura)) {
            $this->logger->error("Unidad que factura inválida", ['valor' => $unidad_que_factura]);
            throw new Exception("Unidad que factura debe ser un número.");
        }
        $this->unidad_que_factura = (int) $unidad_que_factura;
    }

    public function setTotalFacturado($total_facturado)
    {
        if ($total_facturado !== null && !is_numeric($total_facturado)) {
            $this->logger->error("Total facturado inválido", ['valor' => $total_facturado]);
            throw new Exception("Total facturado debe ser un número decimal.");
        }
        $this->total_facturado = (float) $total_facturado;
    }

    public function setCondicionVenta($condicion_venta)
    {
        if ($condicion_venta !== null && !in_array($condicion_venta, self::$CONDICIONES_VENTA)) {
            $this->logger->error("Condición de venta inválida", ['valor' => $condicion_venta]);
            throw new Exception("Condición de venta no válida.");
        }
        $this->condicion_venta = $condicion_venta;
    }

    public function setCondicionImpositiva($condicion_impositiva)
    {
        if ($condicion_impositiva !== null && !in_array($condicion_impositiva, self::$CONDICIONES_IMPOSITIVAS)) {
            $this->logger->error("Condición impositiva inválida", ['valor' => $condicion_impositiva]);
            throw new Exception("Condición impositiva no válida.");
        }
        $this->condicion_impositiva = $condicion_impositiva;
    }

    public function setIdAgente($id_agente)
    {
        if ($id_agente !== null && !is_numeric($id_agente)) {
            $this->logger->error("ID de agente inválido", ['valor' => $id_agente]);
            throw new Exception("ID de agente debe ser un número.");
        }
        $this->id_agente = (int) $id_agente;
    }
}
