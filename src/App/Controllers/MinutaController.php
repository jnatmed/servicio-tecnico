<?php 

namespace Paw\App\Controllers;

use Exception;
use Paw\Core\Controller;
use Paw\App\Controllers\UserController;
use Paw\App\Models\Minutas;
use Paw\Core\Traits\Loggable;
use Paw\App\Utils\Uploader;

class MinutaController extends Controller
{
    public ?string $modelName = Minutas::class;    
    use Loggable;
    public $usuario;
    public $uploader;

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

    public function listar(){

        $this->usuario->verificarSesion();

        $minutas = $this->model->listarMinutas();

        view('minutas.listado', array_merge(
            ['minutas' => $minutas],
            $this->menu
        ));

    }

    public function ver(){
        $this->usuario->verificarSesion();
    }


    public function new()
    {
        view('minuta.new', [
            "datos" => ["action" => "nuevo"], 
            ...$this->menu
        ]);        
    }
}