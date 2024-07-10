<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
use Paw\App\Controllers\UserController;
use Paw\App\Models\OrdenCollection;
use Paw\Core\Traits\Loggable;
use Paw\App\Utils\Uploader;

class OrdenController extends Controller
{
    public ?string $modelName = OrdenCollection::class;    
    use Loggable;
    public $usuario;

    public function __construct()
    {
        global $log;
         
        parent::__construct();     
        $this->uploader = new Uploader;
        $this->usuario = new UserController();
        $this->usuario->setLogger($log);

        $log->info("info __construct: this->menu",  [$this->menu]);
        $this->menu = $this->usuario->adjustMenuForSession($this->menu);        

        $log->info("this->menu: ", [$this->menu]);
    }

    public function new()
    {
        global $request;

        if($request->method() == 'POST')
        {
            $this->logger->info("parametros formulario: ", [$_POST]);
            // Capturar y sanitizar los datos del formulario

            $tipoServicio = htmlspecialchars($request->get('tipo-servicio'), ENT_QUOTES, 'UTF-8');
            $fechaEmision = htmlspecialchars($request->get('fecha-emision'), ENT_QUOTES, 'UTF-8');
            $apellido = htmlspecialchars($request->get('apellido'), ENT_QUOTES, 'UTF-8');
            $nombre = htmlspecialchars($request->get('nombre'), ENT_QUOTES, 'UTF-8');
            $grado = htmlspecialchars($request->get('grado'), ENT_QUOTES, 'UTF-8');
            $credencial = htmlspecialchars($request->get('credencial'), ENT_QUOTES, 'UTF-8');
            $division = htmlspecialchars($request->get('division'), ENT_QUOTES, 'UTF-8');
            $seccion = htmlspecialchars($request->get('seccion'), ENT_QUOTES, 'UTF-8');
            $email = htmlspecialchars($request->get('correo-electronico'), ENT_QUOTES, 'UTF-8');
            $observaciones = htmlspecialchars($request->get('observaciones'), ENT_QUOTES, 'UTF-8');


            // Preparar los datos para pasar a la vista
            $ordenNueva = [
                'tipoServicio' => $tipoServicio,
                'fechaEmision' => $fechaEmision,
                'apellido' => $apellido,
                'nombre' => $nombre,
                'grado' => $grado,
                'credencial' => $credencial,
                'division' => $division,
                'seccion' => $seccion,
                'email' => $email,
                'observaciones' => $observaciones,
            ];

            $resultNuevaInsercion = $this->model->guardarOrden($ordenNueva);

            $this->logger->info("resultNuevaInsercion: ", [$resultNuevaInsercion]);

            if($resultNuevaInsercion['exito'])
            {
                /**
                 * hago un redirect a la orden de trabajo generada
                 */
                redirect('orden-de-trabajo/ver?id='. $resultNuevaInsercion['nuevoNroOrden']);
            }else{
                view('errors/not-found.view', $this->menu);
            }

        }else{
            view('index.view', [
                    "datos" => ["action" => "nuevo"], 
                    ...$this->menu
        ]);
        }

    }

    public function show()
    {
        global $request;
        
        $nroOrden = $request->get('id');

        $datosOrden = $this->model->getDatosOrden($nroOrden);
   

        $this->logger->info("datosOrden: ",[$datosOrden]);

        if ($datosOrden['exito']){
            view('resumen.orden.view', [
                "datos" => $datosOrden, 
                ...$this->menu]
        );
        }else{
            view('errors/not-found.view', $this->menu);
        }
    }

    public function delete()
    {
        global $request;

        $nroOrden = $request->get('id');
        $datosOrden = $this->model->borrarDatosOrden($nroOrden);

        if ($datosOrden['exito']){
            redirect('orden-de-trabajo/listar');
        }else{
            view('errors/not-found.view', $this->menu);
        }
    }

    public function download()
    {
        global $request;
        
        $nroOrden = $request->get('id');

        $datosOrden = $this->model->getDatosOrden($nroOrden);
        
        $this->logger->info("datosOrden: ",[$datosOrden]);

        $pdfContent = $this->uploader->obtenerOrden($datosOrden['pathOrden']);

        if (!$pdfContent) {
            http_response_code(500);
            echo 'Error al obtener el PDF';
            exit;
        }
    
        // Enviar el PDF como respuesta
        header('Content-Type: application/pdf');
        echo $pdfContent;


    }

    public function listar()
    {
        $this->logger->info("metodo listar(): ", [$this->menu]);
        try {
            $ordenes = $this->model->listarOrdenes();

            view('orden.trabajo.list', [
                'ordenes' => $ordenes,    
                ...$this->menu]
        );

        } catch (Exception $e) {
            view('errors/error.view', [[
                'error' => $e->getMessage()
                ],
                ...$this->menu
            ]);
        }
    }


    public function edit()
    {
        global $request;
        
        if($request->method() == 'POST')
        {
            $this->logger->info("parametros formulario: ", [$_POST]);
            // Capturar y sanitizar los datos del formulario


            $this->logger->info("file: ", [$_FILES]);



            $id = htmlspecialchars($request->get('id'), ENT_QUOTES, 'UTF-8');
            $tipoServicio = htmlspecialchars($request->get('tipo-servicio'), ENT_QUOTES, 'UTF-8');
            $fechaEmision = htmlspecialchars($request->get('fecha-emision'), ENT_QUOTES, 'UTF-8');
            $apellido = htmlspecialchars($request->get('apellido'), ENT_QUOTES, 'UTF-8');
            $nombre = htmlspecialchars($request->get('nombre'), ENT_QUOTES, 'UTF-8');
            $grado = htmlspecialchars($request->get('grado'), ENT_QUOTES, 'UTF-8');
            $credencial = htmlspecialchars($request->get('credencial'), ENT_QUOTES, 'UTF-8');
            $division = htmlspecialchars($request->get('division'), ENT_QUOTES, 'UTF-8');
            $seccion = htmlspecialchars($request->get('seccion'), ENT_QUOTES, 'UTF-8');
            $email = htmlspecialchars($request->get('correo-electronico'), ENT_QUOTES, 'UTF-8');
            $observaciones = htmlspecialchars($request->get('observaciones'), ENT_QUOTES, 'UTF-8');


            // Preparar los datos para pasar a la vista
            $ordenActualizada = [
                'id' => $id,
                'tipoServicio' => $tipoServicio,
                'fechaEmision' => $fechaEmision,
                'apellido' => $apellido,
                'nombre' => $nombre,
                'grado' => $grado,
                'credencial' => $credencial,
                'division' => $division,
                'seccion' => $seccion,
                'email' => $email,
                'observaciones' => $observaciones,
            ];

            if(isset($_FILES["file"]) && $_FILES["file"]['error'] !== 4){
                $file = $_FILES["file"];
                $this->uploader->setLogger($this->logger);
                $resultArchivo = $this->uploader->guardarOrdenPDF($file);
            }

            if($resultArchivo['exito'])
            {
                $ordenActualizada['pathOrden'] = $resultArchivo['pathOrden'];
            }

            $resultUpdate = $this->model->actualizarOrden($ordenActualizada); // 

            /**
             * hago un redirect a la orden de trabajo generada
             */
            redirect('orden-de-trabajo/ver?id='. $id);
        }else{

            $nroOrden = $request->get('id');
    
            $datosOrden = $this->model->getDatosOrden($nroOrden);
            
            $datosOrden['action'] = "editar";

            if ($datosOrden['exito']){
                view('index.view', [
                        "datos" => $datosOrden,
                        ...$this->menu
                        ]
            );
            }else{
                view('errors/not-found.view', $this->menu);
            }        
        }

    }

}   