<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;
use InvalidArgumentException;

class Agente extends Model
{
    use Loggable;
    public $credencial;
    public $nombre;
    public $apellido;
    public $cuil;
    public $dependencia;
    public $estado_agente;

    public function __construct($agente) {
        try {
            // Validar credencial (string)
            if (!isset($agente['credencial']) || !is_string($agente['credencial'])) {
                throw new InvalidArgumentException("La credencial debe ser una cadena de texto.");
            }

            // Validar nombre (string)
            if (!isset($agente['nombre']) || !is_string($agente['nombre'])) {
                throw new InvalidArgumentException("El nombre debe ser una cadena de texto.");
            }

            // Validar apellido (string)
            if (!isset($agente['apellido']) || !is_string($agente['apellido'])) {
                throw new InvalidArgumentException("El apellido debe ser una cadena de texto.");
            }

            // Validar CUIL (string de 11 caracteres numéricos sin guiones)
            if (!isset($agente['cuil']) || !preg_match('/^\d{11}$/', $agente['cuil'])) {
                throw new InvalidArgumentException("El CUIL debe ser un número de 11 dígitos sin guiones.");
            }

            // Validar dependencia (entero)
            if (!isset($agente['dependencia']) || !filter_var($agente['dependencia'], FILTER_VALIDATE_INT)) {
                throw new InvalidArgumentException("La dependencia debe ser un número entero.");
            }
            $this->dependencia = (int) $agente['dependencia']; // Convertir a entero

            // Validar estado_agente ('activo' o 'retirado')
            if (!isset($agente['estado_agente']) || !in_array($agente['estado_agente'], ['activo', 'retirado'])) {
                throw new InvalidArgumentException("El estado del agente debe ser 'activo' o 'retirado'.");
            }

            // Asignación de valores
            $this->credencial = $agente['credencial'];
            $this->nombre = $agente['nombre'];
            $this->apellido = $agente['apellido'];
            $this->cuil = $agente['cuil'];
            $this->dependencia = $agente['dependencia'];
            $this->estado_agente = $agente['estado_agente'];

        } catch (InvalidArgumentException $e) {
            error_log("Error en la creación de Agente: " . $e->getMessage());
            throw $e; // Relanzar la excepción para manejarla en niveles superiores
        }

    }


    public function setCredencial($credencial) {
        $this->credencial = $credencial;
    }
    public function getCredencial() {
        return $this->credencial;
    }
    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }
    public function getNombre() {
        return $this->nombre;
    }
    public function setApellido($apellido) {
        $this->apellido = $apellido;
    }
    public function getApellido() {
        return $this->apellido;
    }
    public function setCuil($cuil) {
        $this->cuil = $cuil;
    }
    public function getCuil() {
        return $this->cuil;
    }
    public function setDependencia($dependencia) {
        $this->dependencia = $dependencia;
    }
    public function getDependencia() {
        return $this->dependencia;
    }
    public function setEstadoAgente($estado_agente) {
        $this->estado_agente = $estado_agente;
    }
    public function getEstadoAgente() {
        return $this->estado_agente;
    }


    public function getAgentes($searchAgente = null) 
    {
        try {
            // Si hay un término de búsqueda, usar selectAdHoc
            if ($searchAgente !== null) {
                $result = $this->queryBuilder->selectAdHoc(
                    'agente',
                    '*',
                    'agente',
                    $searchAgente,
                    ['credencial', 'nombre', 'apellido', 'cuil', 'estado_agente']
                );
            } else {
                $result = $this->queryBuilder->select('agente', '*');
            }
    
            if (!empty($result)) {
                $this->logger->info("Datos de agentes recuperados con éxito: ", $result);
                $result[0]['exito'] = true;
                return $result;
            } else {
                $this->logger->error("No se encontró listado de agentes");
                return ["exito" => false];
            }
        } catch (PDOException $e) {
            $this->logger->error("Error al recuperar los datos de los agentes: " . $e->getMessage());
            return ["exito" => false];
        } catch (Exception $e) {
            $this->logger->error("Ocurrió un error al obtener los datos de los agentes: " . $e->getMessage());
            return ["exito" => false];
        }        
    }
    

    public function getDetalleProducto($id) 
    {
        try {
            $result = $this->queryBuilder->select('producto', '*', ['id' => $id]);

            if (!empty($result)) {
                $this->logger->info("Datos de producto recuperados con éxito: ", $result);
                $result[0]['exito'] = true;
                return $result[0]; // Suponiendo que select devuelve un array de resultados
            } else {
                $this->logger->error("No se encontró detalle del producto");
                return ["exito" => false ];
            }            
        }catch (PDOException $e) {
            $this->logger->error("Error al recuperar los datos del producto: " . $e->getMessage());
        }
    }

    public function getProductosYPrecios($searchItem=null)
    {
        try {

            $productos = $this->queryBuilder->obtenerProductosConPrecioMasReciente($searchItem);

            return $productos;

        } catch (PDOException $e) {
            $this->logger->error('Error en getProductosYPrecios: ' . $e->getMessage());
            throw new Exception('Error al obtener los productos con precios más recientes.');
        } catch (Exception $e) {
            $this->logger->error('Error inesperado en getProductosYPrecios: ' . $e->getMessage());
            throw new Exception('Ocurrió un error inesperado.');
        }
    }
}