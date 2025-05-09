<?php 

namespace Paw\App\Models;

use Paw\Core\Model;
use Paw\App\Models\Agente;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;


class DependenciasCollection extends Model
{
    use Loggable;
    private $table = 'dependencias';

    public function __construct($logger =null, $qb=null)
    {
        parent::setLogger($logger);
        parent::setQueryBuilder($qb);
    }

    public function getDependencias($usuarioDependencia = null)
    {
        $sql = "SELECT id, nombre_dependencia, descripcion FROM dependencia";
        $params = [];
    
        if (!is_null($usuarioDependencia)) {
            $sql .= " WHERE id = :usuarioDependencia";
            $params['usuarioDependencia'] = $usuarioDependencia;
        }
    
        try {
            $this->logger->info("Ejecutando consulta para obtener dependencias: $sql");
            $this->logger->info("Parámetros: " . json_encode($params));
    
            $registros = $this->queryBuilder->query($sql, $params);
    
            $resultado = [];
            foreach ($registros as $dependencia) {
                $resultado[] = $dependencia;
            }
    
            return $resultado;
        } catch (PDOException $e) {
            $this->logger->error("Error al obtener dependencias: " . $e->getMessage());
            return [];
        }
    }
        
    
}