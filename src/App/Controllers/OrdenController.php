<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
use Paw\App\Models\OrdenCollection;
use Paw\Core\Traits\Loggable;

class OrdenController extends Controller
{
    public ?string $modelName = OrdenCollection::class;    
    use Loggable;

    public function __construct()
    {
        parent::__construct();     
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

            $nroOrden = $this->model->guardarOrden($ordenNueva);

            /**
             * hago un redirect a la orden de trabajo generada
             */
            redirect('orden-de-trabajo/ver?id='. $nroOrden);

        }else{
            view('index.view');
        }

    }

    public function show()
    {
        global $request;
        
        $nroOrden = $request->get('id');

        $datosOrden = $this->model->getDatosOrden($nroOrden);

        if ($datosOrden['exito']){
            view('resumen.orden.view', $datosOrden);
        }else{
            view('errors/not-found.view');
        }
    }

    public function listar()
    {
        try {
            $ordenes = $this->model->listarOrdenes();

            if (!empty($ordenes)) {
                view('orden.trabajo.list', ['ordenes' => $ordenes]);
            } else {
                view('ordenes/vacio.view');
            }
        } catch (Exception $e) {
            view('errors/error.view', ['error' => $e->getMessage()]);
        }
    }


}   