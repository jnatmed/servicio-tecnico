<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;

use Paw\App\Models\Producto;

class ProductoController extends Controller
{   
    public ?string $modelName = Producto::class; 
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

    public function listar()
    {
        $listaProductos = $this->model->getProductosALaVenta();

        $this->logger->info("listaProductos: ", [$listaProductos]);

        view('facturacion/productos/listado', array_merge(
            ['listaProductos' => $listaProductos],
            $this->menu
        ));
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
