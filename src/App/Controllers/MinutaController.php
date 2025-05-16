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

        // $log->info("info __construct: this->menu",  [$this->menu]);
        $this->menu = $this->usuario->adjustMenuForSession($this->menu);        

        // $log->info("this->menu: ", [$this->menu]);
    }

    public function listar(){

        $this->usuario->verificarSesion();

        $minutas = $this->model->listarMinutas();

        view('minutas/minutas.listado', array_merge(
            ['minutas' => $minutas],
            $this->menu
        ));

    }

    public function ver(){
        
        $minuta = $this->request->get('id');
        $this->logger->info("id_minuta: ", [$minuta]);
        $minuta = $this->model->getMinutaById($minuta);
        if($minuta)
        {
            view('minutas/minuta.ver', array_merge(
                ['minuta' => $minuta],
                $this->menu
            ));
        }else{
            $this->logger->error("Error al obtener el id_minuta");
            view('errors/not-found.view', $this->menu);
        }

    }


    public function new()
    {
        if ($this->request->method() == 'POST') {
            try {
                $datosMinuta = [
                    'orgName'        => $this->request->get('orgName'),
                    'meetingTitle'   => $this->request->get('meetingTitle'),
                    'meetingDate'    => $this->request->get('meetingDate'),
                    'meetingTime'    => $this->request->get('meetingTime'),
                    'meetingPlace'   => $this->request->get('meetingPlace'),
                    'facilitator'    => $this->request->get('facilitator'),
                    'secretary'      => $this->request->get('secretary'),
                    'attendees'      => $this->request->get('attendees'),
                    'absentees'      => $this->request->get('absentees'),
                    'guests'         => $this->request->get('guests'),
                    'agenda'         => $this->request->get('agenda'),
                    'discussion'     => $this->request->get('discussion'),
                    'newTopics'      => $this->request->get('newTopics'),
                    'nextMeeting'    => $this->request->get('nextMeeting'),
                    'closingTime'    => $this->request->get('closingTime'),
                    'closingRemarks' => $this->request->get('closingRemarks'),
                ];
                
    
                $this->model->insertMinuta($datosMinuta);
    
                // Redirigir 
                redirect("minutas/listar");
    
            } catch (Exception $e) {
                $this->logger->error("Error al insertar minuta: " . $e->getMessage());
    
                return view('minutas/minuta.new', [
                    'error' => 'Error al guardar la minuta. Intente nuevamente.',
                    ...$this->menu
                ]);
            }
        }
    
        // Si es GET, mostrar el formulario
        return view('minutas/minuta.new', [
            "datos" => ["action" => "nuevo"],
            ...$this->menu
        ]);
    }
    
}