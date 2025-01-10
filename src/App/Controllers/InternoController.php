<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Models\Interno;
use Exception;

class InternoController extends Controller
{
    public ?string $modelName = Interno::class;    

    use Loggable;
    public $usuario;


    public function __construct()
    {
        global $log;
         
        parent::__construct();     

        $this->usuario = new UserController();
        $this->usuario->setLogger($log);

        $log->info("info __construct: this->menu",  [$this->menu]);
        $this->menu = $this->usuario->adjustMenuForSession($this->menu);        

        $log->info("this->menu: ", [$this->menu]);
    }


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

    public function verInternosTrabajadores()
    {
        
    }

    public function verInternosAsignados() {
        $idTaller = $this->request->get('id_taller');
        // Obtener los datos del modelo
        $datosAsignaciones = $this->model->obtenerInternosAsignados($idTaller);

        // Comprobar si se encontraron datos
        if ($datosAsignaciones) {
            // Pasar los datos a la vista
            return view('asignaciones', array_merge([
                'asignaciones' => $datosAsignaciones],
                $this->menu));
        } else {
            // Si no se encuentran datos, manejar el error o mostrar un mensaje
            return view('asignaciones', ['mensaje' => 'No hay internos asignados a este taller.']);
        }
    }
    
}
