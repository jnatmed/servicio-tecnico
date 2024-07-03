<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
// use Paw\App\Models\OrdenCollection;

class OrdenController extends Controller
{
    // public ?string $modelName = OrdenCollection::class;    

    public function __construct()
    {
        parent::__construct();
    }

    public function new()
    {
        global $request;
        global $log;

        if($request->method() == 'POST')
        {
            $log->info("parametros formulario: ", [$_POST]);
            // Capturar los datos del formulario
            $tipoServicio = $request->get('tipo-servicio');
            $fechaEmision = $request->get('fecha-emision');
            $apellido = $request->get('apellido');
            $nombre = $request->get('nombre');
            $grado = $request->get('grado');
            $credencial = $request->get('credencial');
            $division = $request->get('division');
            $seccion = $request->get('seccion');
            $email = $request->get('correo-electronico');
            $observaciones = $request->get('observaciones');

            // Preparar los datos para pasar a la vista
            $datos = [
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

            // Mostrar la vista de resumen con los datos
            view('resumen.orden.view', $datos);            
        }else{
            view('index.view');
        }

    }


}   