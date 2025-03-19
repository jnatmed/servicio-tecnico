<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;

use Paw\App\Models\Producto;
use Paw\App\Models\Venta;
use Exception;

class VentaController extends Controller
{   
    use Loggable;

    public $usuario;

    public $configFacturacion;
    public $dependencias;

    public ?string $modelName = Producto::class; 

    public function __construct()
    {
        include 'configFacturacion.php';        

        global $log;
         
        $this->configFacturacion = $configFacturacion;
        
        parent::__construct();     

        
        $this->usuario = new UserController();
        $this->usuario->setLogger($log);    

        $log->info("info __construct: this->menu",  [$this->menu]);
        $this->menu = $this->usuario->adjustMenuForSession($this->menu);        

        $log->info("this->menu: ", [$this->menu]);
    }
    
    public function alta() 
    {

        if ($this->request->method() == 'POST') {
            /**
             * Procesar los datos enviados 
             */

            // 

             /**
              * si estan todos los datos correctos, se inserta la factura
              * y se envia confirmacion con los datos de la factura generada
              */
        }else {
            $datosFactura = [
                'fecha_factura' => date('d/m/Y'),
            ];
            
            $this->dependencias = $this->model->getDependencias();

            $this->logger->info("Dependencias: ", $this->dependencias);

            view('facturacion/factura_new', array_merge(
                $datosFactura, $this->configFacturacion, ["dependencias" => $this->dependencias], $this->menu));        
    
        }

    }

    public function listar()
    {
        
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
    

    public function getProductos() {
        // Simular una lista de productos como datos de prueba
        $listaProductos = [
            [
                "id" => 1,
                "descripcion" => "Producto 1",
                "stock" => 50,
                "precio" => 100
            ],
            [
                "id" => 2,
                "descripcion" => "Producto 2",
                "stock" => 30,
                "precio" => 200
            ],
            [
                "id" => 3,
                "descripcion" => "Producto 3",
                "stock" => 20,
                "precio" => 150
            ],
        ];
    
        // Establecer el encabezado para JSON
        header('Content-Type: application/json');
        
        // Devolver la respuesta en formato JSON
        echo json_encode($listaProductos);
    }

    public function getPreciosProductos()
    {
        try {
            // Recibir el ID del producto desde la URL
            $productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    
            // Obtener detalles del producto
            $detalleProducto = $this->model->getDetalleProductoYUltimoPrecio($productId);
    
            // Configurar la respuesta como JSON
            header('Content-Type: application/json');
    
            if (!empty($detalleProducto) && is_array($detalleProducto) && isset($detalleProducto[0])) {
                // Si la consulta devuelve datos, enviar el primer resultado
                echo json_encode($detalleProducto[0]);
                exit;
            } else {
                // Si no hay resultados, devolver un JSON válido con error
                http_response_code(404);
                echo json_encode(["error" => "Producto no encontrado"]);
                exit;
            }
        } catch (Exception $e) {
            // Si ocurre un error, devolverlo en formato JSON
            http_response_code(500);
            echo json_encode(["error" => "Error interno en el servidor", "detalle" => $e->getMessage()]);
            exit;
        }
    }
    
    
    

}
       