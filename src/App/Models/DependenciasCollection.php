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

    public function getDependencias()
    {
        $sql = "SELECT id, nombre_dependencia, descripcion FROM dependencia";
    
        try {
            $this->logger->info("Ejecutando consulta para obtener dependencias: $sql");
            $registros = $this->queryBuilder->query($sql);

            
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