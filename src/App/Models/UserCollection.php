<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class UserCollection extends Model
{
    use Loggable;

    protected $table = 'usuarios';  // Nombre de la tabla de usuarios en la base de datos


    // Ejemplo de método para verificar la existencia de un usuario por nombre de usuario y contraseña
    public function getUserByUsernameAndPassword($username, $password)
    {
        try {
            // Ejecutar la consulta usando los parámetros directamente
            $result = $this->queryBuilder->select('usuarios', '*', [
                'usuario' => $username
            ]);
    
            $result = $result[0];
            // Verificar el resultado de la consulta
            if ($result) {
                // Registrar el resultado para depuración
                $this->logger->info("Resultado de la consulta: ", [$result]);
    
                // Verificar si el campo 'contrasenia' existe en el resultado
                if (isset($result['contrasenia'])) {
                    if (password_verify($password, $result['contrasenia'])) {
                        $this->logger->info("Usuario ".$result["usuario"]." logueado con exito", [$result['usuario'], $result['tipo_usuario']]);
                        return $result;
                    }
                } else {
                    // Registrar un mensaje de error si el campo 'contrasenia' no existe
                    $this->logger->error("El campo 'contrasenia' no existe en el resultado.");
                }
            } else {
                // Registrar un mensaje de error si no se obtuvo ningún resultado
                $this->logger->error("No se encontró un usuario con el nombre de usuario proporcionado.");
            }
    
            return null;
        } catch (\Exception $e) {
            // Manejo de errores (puedes implementar según necesites)
            $this->logger->error("Error en getUserByUsernameAndPassword: ", [$e->getMessage()]);
            return null;
        }
    }
}