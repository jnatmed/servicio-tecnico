<?php 

namespace Paw\App\Models;

use Paw\Core\Model;
use Paw\Core\Database\QueryBuilder;
use Exception;

class Interno extends Model 
{
    public function obtenerInternosAsignados($idTaller) {
        try {
            // Llamamos al método del QueryBuilder pasando los parámetros
            $params = ['id_taller' => $idTaller];
            return $this->queryBuilder->obtenerInternosAsignados($params);
        } catch (Exception $e) {
            // Manejar cualquier excepción lanzada por el QueryBuilder
            throw new Exception("Error al obtener los internos asignados: " . $e->getMessage());
        }
    }

    public function getListadoInternos(){
        try {
            // Llamamos al método del QueryBuilder
            return $this->queryBuilder->select('internos');
            
        } catch (Exception $e) {
            // Manejar cualquier excepción lanzada por el QueryBuilder
            throw new Exception("Error al obtener el listado de internos: ". $e->getMessage());
        }
    }
}
