<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class Minutas extends Model
{
    use Loggable;

    public function listarMinutas()
    {
        try {
            // Nombre de la tabla
            $table = 'minutas';
    
            // Columnas que quieres seleccionar
            $columns = '*'; // O especifica las columnas como 'id, orgName, meetingTitle, ...'
    
            // Parámetros opcionales (puedes enviar filtros aquí si lo necesitas)
            $params = [];
    
            // Usar el método select del QueryBuilder para obtener las minutas
            $minutas = $this->queryBuilder->select($table, $columns, $params);
    
            return $minutas;
        } catch (Exception $e) {
            $this->logger->error('Error al listar minutas: ' . $e->getMessage());
            throw new Exception('Ocurrió un error al listar las minutas.');
        }
    }


}
