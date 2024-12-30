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
            return $this->queryBuilder->obtenerInternosAsignados(['id_taller' => $idTaller]);
        } catch (Exception $e) {
            // Manejar cualquier excepción lanzada por el QueryBuilder
            throw new Exception("Error al obtener los internos asignados: " . $e->getMessage());
        }
    }
}
