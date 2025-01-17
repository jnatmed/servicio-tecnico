<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;

class FacturacionController extends Controller
{
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
    
    public function nuevaFactura() 
    {

        $datosFactura = [
            'nro_factura' => 1234,
            'fecha_factura' => '12/12/2014',
        ];

        $dependencias = [
            'dependencias' => [
                [
                'id' => 'CPFCABA',
                'nombre' => 'Complejo Penitenciario de la Ciudad Autonoma de Buenos Aires'    
                ],
                [
                'id' => 'CFJA',
                'nombre' => 'Complejo Penitenciario de Jovenes Adultos'    
                ]
            ]
        ];

        view('facturacion/factura_new', array_merge(
            $datosFactura, $dependencias, $this->menu));        
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
        // Recibir el ID del producto desde la URL (por ejemplo, `api_get_precio_producto?id=1`)
        $productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    
        // Lista de precios con ID
        $listaPrecios = [
            [
                "id" => 1,
                "descripcion" => "Precio 1",
                "valor" => 10
            ],
            [
                "id" => 2,
                "descripcion" => "Precio 2",
                "valor" => 15
            ],
            [
                "id" => 3,
                "descripcion" => "Precio 3",
                "valor" => 20
            ],
        ];
    
        // Buscar el precio correspondiente al ID recibido
        $producto = null;
        foreach ($listaPrecios as $precio) {
            if ($precio['id'] === $productId) {
                $producto = $precio;
                break;
            }
        }
    
        // Si se encuentra el producto, devolverlo, si no, devolver un error
        if ($producto) {
            header('Content-Type: application/json');
            echo json_encode(["precio" => $producto['valor']]);
        } else {
            // Si no se encuentra el producto, devolver error
            header('Content-Type: application/json');
            echo json_encode(["error" => "Producto no encontrado"]);
        }
    }
    

}
       