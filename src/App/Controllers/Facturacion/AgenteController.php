<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;

use Exception;

use Paw\App\Models\Agente;

class AgenteController extends Controller
{   
    public ?string $modelName = Agente::class; 
    use Loggable;
    public $usuario;
    public $producto;
    public $configFacturacion;

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

    public function alta()
    {
        
    }

    public function getAgentes()
    {
        $searchItem = $this->request->get('search');
    
        try {
            $listaAgentes = $this->model->getAgentes($searchItem);

            // Enviar la respuesta en formato JSON
            header('Content-Type: application/json');
            echo json_encode(['success' => true, $listaAgentes]);
            exit; // Detener la ejecución después de enviar la respuesta

        } catch (Exception $e) {
            // Manejo de errores en JSON
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        } 
    
    }
    

    public function ver()
    {
        if($this->request->get('id_producto') !== NULL) 
        {
            $id = $this->request->get('id_producto');
            $this->logger->info("id_producto: ", [$id]);
            $detalleProducto = $this->model->getDetalleProducto($id);

            view('facturacion/productos/detalle', array_merge(
                ['producto' => $detalleProducto],
                $this->menu
            ));
        }else{
            $this->logger->error("Error al obtener el id_producto");
            $detalleProducto = NULL;
        }
    }

}
