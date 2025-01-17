<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;

class FacturacionController extends Controller
{
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
    
    public function nuevaFactura() 
    {


        $datos = [
            'nro_factura' => 1234,
            'fecha_factura' => '12/12/2014',
        ];

        view('facturacion/factura_new', array_merge(
            $datos, $this->menu));        
    }

    public function getAgentes()
    {
        // Simular una lista de agentes como datos de prueba
        $listaAgentes = [
            [
                "id" => 4,
                "nombre" => "Ana",
                "apellido" => "López"
            ],
            [
                "id" => 5,
                "nombre" => "Luis",
                "apellido" => "Martínez"
            ]
        ];
    
        // Establecer el encabezado para JSON
        header('Content-Type: application/json');
    
        // Devolver la respuesta en formato JSON
        echo json_encode($listaAgentes);
    }
    

}
       