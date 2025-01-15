<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;

class FacturacionController extends Controller
{
    use Loggable;

    public function nuevaFactura() 
    {
        view('facturacion/factura_new', [
            'nro_factura' => 1234,
            'fecha_factura' => '12/12/2014',
        ]);
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
       