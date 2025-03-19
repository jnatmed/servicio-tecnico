<?php 

namespace Paw\App\Models;

use Paw\Core\Model;
use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class DetalleFactura extends Model
{
    use Loggable;

    private $factura_id;
    private $producto_id;
    private $cantidad_facturada;
    private $precio_unitario;

    // Constructor con validaciones
    public function __construct(array $data = [], $logger = null)
    {
        parent::setLogger($logger);
        try {
            if (!empty($data)) {
                $this->logger->debug("__construct: Instanciando DetalleFactura", ['data' => $data]);

                $this->setFacturaId($data['factura_id'] ?? null);
                $this->setProductoId($data['producto_id'] ?? null);
                $this->setCantidadFacturada($data['cantidad_facturada'] ?? null);
                $this->setPrecioUnitario($data['precio_unitario'] ?? null);
            }
        } catch (Exception $e) {
            $this->logger->error("Error en DetalleFactura::__construct", ['error' => $e->getMessage()]);
            throw new Exception("Error al crear el detalle de factura: " . $e->getMessage());
        }
    }

    // Getters
    public function getFacturaId() { return $this->factura_id; }
    public function getProductoId() { return $this->producto_id; }
    public function getCantidadFacturada() { return $this->cantidad_facturada; }
    public function getPrecioUnitario() { return $this->precio_unitario; }

    // Setters con validaciones y logs
    public function setFacturaId($factura_id)
    {
        if ($factura_id !== null && !is_numeric($factura_id)) {
            $this->logger->error("Factura ID inválido", ['valor' => $factura_id]);
            throw new Exception("Factura ID debe ser un número.");
        }
        $this->factura_id = (int) $factura_id;
    }

    public function setProductoId($producto_id)
    {
        if ($producto_id !== null && !is_numeric($producto_id)) {
            $this->logger->error("Producto ID inválido", ['valor' => $producto_id]);
            throw new Exception("Producto ID debe ser un número.");
        }
        $this->producto_id = (int) $producto_id;
    }

    public function setCantidadFacturada($cantidad_facturada)
    {
        if ($cantidad_facturada !== null && (!is_numeric($cantidad_facturada) || $cantidad_facturada < 1)) {
            $this->logger->error("Cantidad facturada inválida", ['valor' => $cantidad_facturada]);
            throw new Exception("Cantidad facturada debe ser un número entero mayor a 0.");
        }
        $this->cantidad_facturada = (int) $cantidad_facturada;
    }

    public function setPrecioUnitario($precio_unitario)
    {
        if ($precio_unitario === null || !is_numeric($precio_unitario) || $precio_unitario < 0) {
            $this->logger->error("Precio unitario inválido", ['valor' => $precio_unitario]);
            throw new Exception("Precio unitario debe ser un número decimal mayor o igual a 0.");
        }
        $this->precio_unitario = (float) $precio_unitario;
    }
}
