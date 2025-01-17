<?php 

namespace Paw\App\Controllers;

use Exception;
use Paw\Core\Controller;
use Paw\App\Controllers\UserController;
use Paw\App\Models\OrdenCollection;
use Paw\Core\Traits\Loggable;
use Paw\App\Utils\Uploader;

class PageController extends Controller
{
    public ?string $modelName = OrdenCollection::class;    
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

    public function home()
    {
        $this->usuario->verificarSesion();
        
        // Datos dinámicos para las tarjetas
        $cards = [
            [
                'title' => 'Órdenes de Trabajo',
                'description' => 'Accede a todas las órdenes de trabajo generadas.',
                'link' => '/orden-de-trabajo/listar',
                'button_text' => 'Ver Órdenes',
                'media_size_class' => 'media_size'
            ],
            [
                'title' => 'Minutas',
                'description' => 'Consulta las minutas de reuniones anteriores.',
                'link' => '/minutas/listar',
                'button_text' => 'Ver Minutas'
            ],
            [
                'title' => 'Internos Trabajadores',
                'description' => 'Establezca la Prioridad de Cupo Talleres a Internos.',
                'link' => '/talleres/ver_talleres',
                'button_text' => 'Ver Ranking'
            ],
            [
                'title' => 'Modulo de Facturacion',
                'description' => 'Gestion de Puntos de Venta.',
                'link' => '/facturacion/new',
                'button_text' => 'Nueva Factura'
            ]
        ];
        
        // Pasar los datos a la vista
        view('home.view', [
            "datos" => ["action" => "nuevo"],
            "cards" => $cards, // Enviar las tarjetas a la vista
            ...$this->menu
        ]);
    }
}