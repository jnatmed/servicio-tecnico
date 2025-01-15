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

    

}
       