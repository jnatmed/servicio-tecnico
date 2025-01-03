<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class Taller extends Model
{
    use Loggable;

    public function listarTalleres()
    {
        try {
            // Nombre de la tabla
            $table = 'talleres';
    
            // Columnas que quieres seleccionar
            $columns = '*'; // O especifica las columnas como 'id, orgName, meetingTitle, ...'
    
            // Parámetros opcionales (puedes enviar filtros aquí si lo necesitas)
            $params = [];
    
            // Usar el método select del QueryBuilder para obtener las talleres
            $talleres = $this->queryBuilder->select($table, $columns, $params);
    
            return $talleres;

        } catch (Exception $e) {
            $this->logger->error('Error al listar talleres: ' . $e->getMessage());
            throw new Exception('Ocurrió un error al listar las talleres.');
        }
    }


}