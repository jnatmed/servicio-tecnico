<?php 

namespace Paw\App\Models;

use Paw\Core\Model;
use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class Producto extends Model
{
    use Loggable;

    private $id;
    public $nro_proyecto_productivo;
    public $descripcion_proyecto;
    public $estado;
    public $id_taller;
    public $id_unidad_q_fabrica;
    public $stock_inicial;
    public $unidad_medida;

    // ENUM de valores válidos
    private static $ESTADOS_VALIDOS = ['iniciado', 'a_la_venta'];
    private static $UNIDADES_VALIDAS = ['kg', 'bolsas', 'litros', 'unidades', 'docena'];

    // Constructor con validaciones
    public function __construct(array $data = [], $logger = null)
    {
        parent::setLogger($logger);
        try {
            if (!empty($data)) {
                $this->logger->debug("__construct: Instanciando producto", ['data' => $data]);

                // 🔹 Seteamos solo los atributos que se reciben del front
                if (isset($data['id'])) {
                    $this->setId($data['id']);
                }

                if (isset($data['nro_proyecto_productivo'])) {
                    $this->setNroProyectoProductivo($data['nro_proyecto_productivo']);
                }

                if (isset($data['descripcion_proyecto'])) {
                    $this->setDescripcionProyecto($data['descripcion_proyecto']);
                }

                if (isset($data['estado'])) {
                    $this->setEstado($data['estado']);
                }

                if (isset($data['id_taller'])) {
                    $this->setIdTaller($data['id_taller']);
                }

                if (isset($data['id_unidad_q_fabrica'])) {
                    $this->setIdUnidadQFabrica($data['id_unidad_q_fabrica']);
                }

                // 🔹 `unidad_medida` y `stock_inicial` NO se setean porque no los recibes del front
            }
        } catch (Exception $e) {
            $this->logger->error("Error en Producto::__construct", ['error' => $e->getMessage()]);
            throw new Exception("Error al crear el producto: " . $e->getMessage());
        }
    }
    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getNroProyectoProductivo()
    {
        return $this->nro_proyecto_productivo;
    }

    public function getDescripcionProyecto()
    {
        return $this->descripcion_proyecto;
    }

    public function getEstado()
    {
        return $this->estado;
    }

    public function getIdTaller()
    {
        return $this->id_taller;
    }

    public function getIdUnidadQFabrica()
    {
        return $this->id_unidad_q_fabrica;
    }

    public function getStockInicial()
    {
        return $this->stock_inicial;
    }

    public function getUnidadMedida()
    {
        return $this->unidad_medida;
    }

    // Setters con validaciones
    public function setId($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            $this->logger->error("ID inválido en setId", ['valor' => $id]);
            throw new Exception("El ID debe ser un número entero positivo.");
        }

        $this->id = (int) $id;
        $this->logger->info("ID seteado correctamente", ['id' => $this->id]);
    }

    public function setNroProyectoProductivo($nro_proyecto_productivo)
    {
        if ($nro_proyecto_productivo !== null && !is_string($nro_proyecto_productivo)) {
            $this->logger->error("El número de proyecto productivo debe ser una cadena de texto.", [$nro_proyecto_productivo]);
            throw new Exception("El número de proyecto productivo debe ser una cadena de texto.");
        }
        $this->nro_proyecto_productivo = $nro_proyecto_productivo;
    }

    public function setDescripcionProyecto($descripcion_proyecto)
    {
        if ($descripcion_proyecto !== null && !is_string($descripcion_proyecto)) {
            $this->logger->error("La descripción del proyecto debe ser una cadena de texto.", [$descripcion_proyecto]);
            throw new Exception("La descripción del proyecto debe ser una cadena de texto.");
        }
        $this->descripcion_proyecto = $descripcion_proyecto;
    }

    public function setEstado($estado)
    {
        if ($estado !== null && !in_array($estado, self::$ESTADOS_VALIDOS)) {
            $this->logger->error("El estado del producto no es válido.", [$estado]);
            throw new Exception("El estado del producto no es válido.");
        }
        $this->estado = $estado;
    }

    public function setIdTaller($id_taller)
    {
        if ($id_taller !== null && !is_numeric($id_taller)) {
            $this->logger->error("El ID del taller debe ser un número entero.", [$id_taller]);
            throw new Exception("El ID del taller debe ser un número entero.");
        }
        $this->id_taller = $id_taller !== null ? (int) $id_taller : null;
    }

    public function setIdUnidadQFabrica($id_unidad_q_fabrica)
    {
        if ($id_unidad_q_fabrica !== null && !is_numeric($id_unidad_q_fabrica)) {
            $this->logger->error("El ID de la unidad que fabrica debe ser un número entero.", [$id_unidad_q_fabrica]);
            throw new Exception("El ID de la unidad que fabrica debe ser un número entero.");
        }
        $this->id_unidad_q_fabrica = $id_unidad_q_fabrica !== null ? (int) $id_unidad_q_fabrica : null;
    }

    public function setStockInicial($stock_inicial)
    {
        if ($stock_inicial !== null && !is_numeric($stock_inicial)) {
            $this->logger->error("El stock inicial debe ser un número decimal.", [$stock_inicial]);
            throw new Exception("El stock inicial debe ser un número decimal.");
        }
        $this->stock_inicial = $stock_inicial !== null ? (float) $stock_inicial : null;
    }

    public function setUnidadMedida($unidad_medida)
    {
        if (!in_array($unidad_medida, self::$UNIDADES_VALIDAS)) {
            $this->logger->error("La unidad de medida no es válida.", [$unidad_medida]);
            throw new Exception("La unidad de medida no es válida.");
        }
        $this->unidad_medida = $unidad_medida;
    }

}
