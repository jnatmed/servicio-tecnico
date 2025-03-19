<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;

use Paw\App\Models\Producto;
use Paw\App\Models\Factura;
use Paw\App\Models\FacturasCollection;
use Paw\App\Models\ProductosCollection;
use Exception;
use Paw\App\Models\DetalleFactura;

class FacturacionController extends Controller
{
    
    use Loggable;

    public $usuario;

    public $configFacturacion;
    public $dependencias;

    public ?string $modelName = FacturasCollection::class; 

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
            try {
                // Capturar los datos del formulario
                $data = [
                    'nro_factura' => $this->request->get('nro_comprobante'),
                    'id_agente' => $this->request->get('agente'),
                    'unidad_que_factura' => $this->request->get('dependencia'),
                    'condicion_venta' => $this->request->get('condicion_venta'),
                    'condicion_impositiva' => $this->request->get('condicion_impositiva'),
                    'total_facturado' => $this->request->get('total_facturado'),
                ];
    
                $productos = json_decode($this->request->get('productos'), true);
    
                $this->sanitize($data);
                $this->logger->debug("Data recibida: ", [$data]);
    
                // Validación de datos obligatorios
                if (empty($data['nro_factura']) || empty($data['id_agente']) || empty($productos)) {
                    throw new Exception("Faltan datos obligatorios.");
                }
    
                $this->logger->debug("Comenzando a instanciar Factura.");
                // Crear instancia de Factura (con validaciones en el constructor)
                $factura = new Factura($data, $this->logger);
    
                // Insertar la factura en la base de datos
                $facturaId = $this->model->insertFactura([
                    'nro_factura' => $factura->getNroFactura(),
                    'fecha_factura' => $factura->getFechaFactura(), 
                    'unidad_que_factura' => $factura->getUnidadQueFactura(),
                    'total_facturado' => $factura->getTotalFacturado(),
                    'condicion_venta' => $factura->getCondicionVenta(),
                    'condicion_impositiva' => $factura->getCondicionImpositiva(),
                    'id_agente' => $factura->getIdAgente()
                ]);
    
                $this->logger->debug("Factura insertada con ID: ", [$facturaId]);
                $this->logger->debug("Comenzando a insertar detalle de factura.");
    
                // Iteramos sobre la lista de productos para insertar cada detalle
                foreach ($productos as $productoData) {
                    try {
                        $this->logger->debug("Producto Data para inserción: ", [$productoData]);
    
                        $queryProducto = new ProductosCollection($this->qb);

                        // Validar que el producto exista en la BD antes de agregarlo al detalle
                        $productoExistente = $queryProducto->getById($productoData['id']);
                        if (!$productoExistente) {
                            throw new Exception("El producto con ID {$productoData['id']} no existe en la base de datos.");
                        }
                        $this->logger->debug("Producto Existente, ", [$productoExistente]);
    
                        // Crear instancia de DetalleFactura para cada producto
                        $detalleFactura = new DetalleFactura([
                            'factura_id' => $facturaId,
                            'producto_id' => $productoData['id'], 
                            'cantidad_facturada' => $productoData['cantidad'],
                            'precio_unitario' => $productoData['precio_unitario']
                        ], $this->logger);
    
                        // Insertar en la tabla detalle_factura
                        $this->model->insertDetalleFactura([
                            'factura_id' => $detalleFactura->getFacturaId(),
                            'producto_id' => $detalleFactura->getProductoId(),
                            'cantidad_facturada' => $detalleFactura->getCantidadFacturada(),
                            'precio_unitario' => $detalleFactura->getPrecioUnitario()
                        ]);
    
                    } catch (Exception $e) {
                        throw new Exception("Error en producto: " . $e->getMessage());
                    }
                }
    
                // Respuesta de éxito
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'factura_id' => $facturaId]);
                exit;
    
            } catch (Exception $e) {
                // Captura de errores
                $this->logger->error("Error en alta de factura", ['error' => $e->getMessage()]);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        } else {
            // Si es una solicitud GET, se muestra el formulario
            $datosFactura = ['fecha_factura' => date('d/m/Y')];
    
            $this->dependencias = $this->model->getDependencias();
            $this->logger->info("Dependencias: __", ['dependencias' => $this->dependencias]);
    
            return view('facturacion/factura_new', array_merge(
                $datosFactura, 
                $this->configFacturacion, 
                ["dependencias" => $this->dependencias], 
                $this->menu
            ));
        }
    }
    
    public function listar()
    {
        $page = (int) ($this->request->get('page') ?? 1);
        $searchItem = trim($this->request->get('search') ?? '');
        $limit = 10;
        $offset = ($page - 1) * $limit;
    
        try {
            // Obtener facturas paginadas
            $facturas = $this->model->getFacturasPaginated($limit, $offset, $searchItem);
            $totalFacturas = $this->model->countFacturas($searchItem);
    
            // Si es AJAX, devolver JSON
            if ($this->request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'facturas' => $facturas,
                    'total' => $totalFacturas,
                    'limit' => $limit,
                    'currentPage' => $page,
                    'search' => $searchItem
                ]);
                exit;
            }
    
            // Si es una solicitud normal, renderizar la vista
            return view('facturacion/factura.listado', array_merge([
                'facturas' => $facturas,
                'total' => $totalFacturas,
                'limit' => $limit,
                'currentPage' => $page,
                'search' => $searchItem], 
                $this->menu)
            );
    
        } catch (Exception $e) {
            $this->logger->error("Error en listar facturas: " . $e->getMessage());
    
            // Si es AJAX, devolver el error en JSON
            if ($this->request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
    
            // Si es una solicitud normal, mostrar la vista con el error
            try {
                return view('facturacion/factura.listado', ['error' => $e->getMessage()]);
            } catch (Exception $viewError) {
                $this->logger->error("Error al cargar la vista: " . $viewError->getMessage());
                return "Ocurrió un error al cargar la página.";
            }
        }
    }
    
    
}
       