<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Models\Taller;
use Exception;

class TalleresController extends Controller
{
    public ?string $modelName = Taller::class;    

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


    public function verTalleres()
    {
        $this->usuario->verificarSesion();

        $talleres = $this->model->listarTalleres();

        view('talleres/talleres.listado', array_merge(
            ['talleres' => $talleres],
            $this->menu
        ));        
    }

}