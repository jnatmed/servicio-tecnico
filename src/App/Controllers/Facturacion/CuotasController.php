<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;


use Paw\App\Models\CuotasCollection;
use Exception;


class CuotasController extends Controller
{
    
    use Loggable;

    public $usuario;

    public $configFacturacion;
    public $dependencias;

    public ?string $modelName = CuotasCollection::class; 

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
    


    public function listar()
    {
        // $cuotas = $this->model->getAllFilteredByDate();

        return view('facturacion/cuotas/cuotas.listado-filtrado', array_merge(
            // $cuotas, 
            $this->menu
        ));
    }

}
       