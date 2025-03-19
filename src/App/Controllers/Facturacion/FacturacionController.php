<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;

use Paw\App\Models\Producto;
use Paw\App\Models\Factura;
use Exception;

class FacturacionController extends Controller
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
            $data = [
                'nro_comprobante' => $this->request->get('nro_comprobante'),
                'agente' => $this->request->get('agente'),
                'dependencia' => $this->request->get('dependencia'),
                'condicion_venta' => $this->request->get('condicion_venta'),
                'condicion_impositiva' => $this->request->get('condicion_impositiva'),
                'total_facturado' => $this->request->get('total_facturado'),
                'productos' => json_decode($this->request->get('productos'), true) // Decodificar JSON
            ];
        
            // Validación simple
            if (empty($data['nro_comprobante']) || empty($data['agente']) || empty($data['productos'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => "Faltan datos obligatorios."]);
                exit;
            }
        
            try {
                $facturaId = $this->model->insertFactura($data);
        
                // Insertar productos en la base de datos
                foreach ($data['productos'] as $producto) {
                    $this->model->insertDetalleFactura([
                        'factura_id' => $facturaId,
                        'producto_id' => $producto['id'],
                        'cantidad' => $producto['cantidad'],
                        'precio_unitario' => $producto['precio_unitario']
                    ]);
                }
        
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'factura_id' => $facturaId]);
                exit;
        
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        } else {
            // 🔹 Si es una solicitud GET, se muestra el formulario
            $datosFactura = ['fecha_factura' => date('d/m/Y')];
    
            $this->dependencias = $this->model->getDependencias();
            $this->logger->info("Dependencias: ", ['dependencias' => $this->dependencias]);
    
            return view('facturacion/factura_new', array_merge(
                $datosFactura, 
                $this->configFacturacion, 
                ["dependencias" => $this->dependencias], 
                $this->menu
            ));
        }
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
       