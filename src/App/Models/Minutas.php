<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class Minutas extends Model
{
    use Loggable;


    public function getMinutaById($id)
    {
        try {
            $this->logger->info("Buscando minuta con ID: $id");
    
            $minuta = $this->queryBuilder->query("
                SELECT *
                FROM minutas
                WHERE id = :id
                LIMIT 1
            ", [
                ':id' => $id
            ]);
    
            // Asumimos que devuelve un array con una única fila
            return $minuta[0] ?? null;
    
        } catch (Exception $e) {
            $this->logger->error("Error en getMinutaById: " . $e->getMessage(), ['id' => $id]);
            throw new Exception("No se pudo recuperar la minuta con ID $id.");
        }
    }
    

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
    public function insertMinuta(array $data)
    {
        try {
            $this->logger->info("Insertando nueva minuta para: " . $data['orgName']);
    
            $this->queryBuilder->query("
                INSERT INTO minutas (
                    orgName, meetingTitle, meetingDate, meetingTime, meetingPlace,
                    facilitator, secretary, attendees, absentees, guests,
                    agenda, discussion, newTopics, nextMeeting, closingTime, closingRemarks
                ) VALUES (
                    :orgName, :meetingTitle, :meetingDate, :meetingTime, :meetingPlace,
                    :facilitator, :secretary, :attendees, :absentees, :guests,
                    :agenda, :discussion, :newTopics, :nextMeeting, :closingTime, :closingRemarks
                )
            ", [
                ':orgName'         => $data['orgName'],
                ':meetingTitle'    => $data['meetingTitle'],
                ':meetingDate'     => $data['meetingDate'],
                ':meetingTime'     => $data['meetingTime'],
                ':meetingPlace'    => $data['meetingPlace'],
                ':facilitator'     => $data['facilitator'],
                ':secretary'       => $data['secretary'],
                ':attendees'       => $data['attendees'],
                ':absentees'       => $data['absentees'],
                ':guests'          => $data['guests'],
                ':agenda'          => $data['agenda'],
                ':discussion'      => $data['discussion'],
                ':newTopics'       => $data['newTopics'],
                ':nextMeeting'     => $data['nextMeeting'],
                ':closingTime'     => $data['closingTime'],
                ':closingRemarks'  => $data['closingRemarks'],
            ]);
    
        } catch (Exception $e) {
            $this->logger->error("Error al insertar minuta: " . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

}
