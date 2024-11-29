<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Exception;

class InternoController extends Controller
{
    use Loggable;

    public function datosInternos()
    {
        // Definir los datos de la persona
        $persona = [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan.perez@example.com',
            'edad' => 30
        ];
    
        try {
            // Establecer el encabezado de tipo de contenido como JSON
            header('Content-Type: application/json');
    
            // Establecer un código de respuesta HTTP 200 (OK)
            http_response_code(200);
    
            // Convertir los datos a JSON y devolver la respuesta
            echo json_encode($persona);
        } catch (Exception $e) {
            // Si ocurre un error, devolver código de error 500 (Internal Server Error)
            http_response_code(500);
    
            // Enviar un mensaje de error en formato JSON
            echo json_encode([
                'error' => 'Ocurrió un error al procesar la solicitud.',
                'mensaje' => $e->getMessage()
            ]);
        }
    }
    
}
