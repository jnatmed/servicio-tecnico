<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use PDOException;
use Paw\Core\Traits\Loggable;


class RolesCollection extends Model
{
    use Loggable;
    private $table = 'roles';

    public function __construct($logger =null, $qb=null)
    {
        parent::setLogger($logger);
        parent::setQueryBuilder($qb);
    }

    public function getRoles($rolUsuario = null)
    {
        $sql = "SELECT * FROM roles";
        $params = [];
    
        if (!is_null($rolUsuario)) {
            $sql .= " WHERE id = :rolUsuario";
            $params['rolUsuario'] = $rolUsuario;
        }
    
        try {
            $this->logger->info("Ejecutando consulta para obtener roles: $sql");
            $this->logger->info("Parámetros: " . json_encode($params));
    
            $registros = $this->queryBuilder->query($sql, $params);
    
            $resultado = [];
            foreach ($registros as $rol) {
                $resultado[] = $rol;
            }
    
            return $resultado;
        } catch (PDOException $e) {
            $this->logger->error("Error al obtener dependencias: " . $e->getMessage());
            return [];
        }
    }
        
    
}