<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class OrdenCollection extends Model
{
    use Loggable;

    protected $table = 'usuarios';  // Nombre de la tabla de usuarios en la base de datos

    public function __construct()
    {
        parent::__construct();
    }

    // Ejemplo de método para verificar la existencia de un usuario por nombre de usuario y contraseña
    public function getUserByUsernameAndPassword($username, $password)
    {
        try {
            $query = $this->queryBuilder->select('*')
                                       ->from($this->table)
                                       ->where('usuario', '=', $username)
                                       ->limit(1);

            $result = $query->execute()->fetch();

            if ($result && password_verify($password, $result['contrasenia'])) {
                return $result;
            }

            return null;
        } catch (\Exception $e) {
            // Manejo de errores (puedes implementar según necesites)
            $this->logError($e->getMessage());
            return null;
        }
    }
}