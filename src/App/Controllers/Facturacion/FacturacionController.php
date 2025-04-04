<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;
use Paw\App\Models\Factura;
use Paw\App\Models\FacturasCollection;
use Paw\App\Models\ProductosCollection;
use Paw\App\Models\CuotasCollection;
use Paw\App\Models\DetalleFactura;
use Paw\App\Models\Imagen;
use Paw\App\Models\CuentaCorrienteCollection;
use Paw\App\Models\CuentaCorriente;

use Exception;

class FacturacionController extends Controller
{
    
    use Loggable;

    public $usuario;

    public $configFacturacion;
    public $dependencias;
    public $cuotasCollection;

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
        /**
         * Verifico si el metodo es POST, sino
         * muestro la pagina de carga de formulario
         */
        if ($this->request->method() == 'POST') {
            try {
                /**
                 * Recepcion de datos
                 */
                $data = [
                    'nro_factura' => $this->request->get('nro_comprobante'),
                    'id_agente' => $this->request->get('agente'),
                    'fecha_factura' => date('Y-m-d'),
                    'unidad_que_factura' => $this->request->get('dependencia'),
                    'condicion_venta' => $this->request->get('condicion_venta'),
                    'condicion_impositiva' => $this->request->get('condicion_impositiva'),
                    'total_facturado' => $this->request->get('total_facturado'),
                    'cantidad_cuotas' => $this->request->get('cantidad_cuotas'), // Cantidad de cuotas seleccionadas
                    'path_comprobante' => ''

                ];
    
                /**
                 * La lista de productos la guardo en un arreglo, previamente
                 * decodificado 
                 */
                $productos = json_decode($this->request->get('productos'), true);
    
                /**
                 * Sanitize de los datos
                 */
                $this->request->sanitize($data);
                
                $this->logger->debug("Data recibida: ", [$data]); // DEBUG
    
                // Validación de datos obligatorios
                if (empty($data['nro_factura']) || empty($data['id_agente']) || empty($productos)) {
                    throw new Exception("Faltan datos obligatorios.");
                }
    
                $this->logger->debug("Comenzando a instanciar Factura.");
                // Crear instancia de Factura (con validaciones en el constructor)
                $factura = new Factura($data, $this->logger);

                // Insertar la factura en la base de datos
                $facturaId = $this->model->insertFactura($factura);
    
                $this->logger->debug("Factura insertada con ID: ", [$facturaId]);

                // Si la condición de venta es en cuotas, generar las cuotas
                try {
                    $this->logger->debug("Datos de cuotas: ", [$data['condicion_venta'],$data['cantidad_cuotas']]);
                    // Generación de cuotas si la condición de venta lo requiere
                    if (in_array($data['condicion_venta'], ['codigo_608', 'codigo_689']) && $data['cantidad_cuotas'] > 0) {
                        $this->logger->info("Solicitando generación de cuotas para la factura ID: " . $facturaId);
                
                        // Instanciar la colección de cuotas
                        $this->cuotasCollection = new CuotasCollection($this->qb, $this->logger);
                
                        // Delegar la lógica al modelo
                        $this->cuotasCollection->generarCuotas($facturaId, $data['total_facturado'], $data['cantidad_cuotas']);
                        
                    }else{
                        $this->logger->info("No se solicita la generación de cuotas para la condición de venta {$data['condicion_venta']} o la cantidad de cuotas es 0.");
                    }
                
                    $this->logger->info("Proceso de facturación finalizado correctamente para la factura ID: " . $facturaId);
                } catch (Exception $e) {
                    $this->logger->error("Error en el proceso de facturación para la factura ID {$facturaId}: " . $e->getMessage());
                    throw new Exception("Error al procesar la factura: " . $e->getMessage());
                }

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
                        // Insertar en la tabla detalle_factura
                        $detalleFacturaId = $this->model->insertDetalleFactura(
                            new DetalleFactura([
                                'factura_id' => $facturaId,
                                'producto_id' => $productoData['id'], 
                                'cantidad_facturada' => $productoData['cantidad'],
                                'precio_unitario' => $productoData['precio_unitario']
                            ], $this->logger));

                        $this->logger->debug("DetalleFactura insertado con ID: ", [$detalleFacturaId]);
    
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
    
            $this->logger->info("Dependencias: __", ['dependencias' => $this->dependencias]);
    
            return view('facturacion/factura_new', array_merge(
                $datosFactura, 
                $this->configFacturacion, 
                ["dependencias" => $this->model->getDependencias()], 
                ['monto_minimo_cuota' => 10000],
                $this->menu
            ));
        }
    }

    public function eliminarFactura()
    {
        $facturaId = $this->request->sanitize($this->request->get('id'));
    
        try {
            if (!$facturaId) {
                throw new Exception("ID de factura no proporcionado.");
            }

            $factura = $this->model->getFacturaById($facturaId);
            if (!$factura) {
                throw new Exception("La factura no existe.");
            }            
    
            // Delegar al modelo
            $this->model->eliminarFacturaPorId($facturaId);
    
            $this->logger->info("Factura eliminada con éxito: ID $facturaId");
    
            if ($this->request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
    
            redirect('/facturacion/listar');
    
        } catch (\Exception $e) {
            $this->logger->error("Error en el proceso de eliminación de factura", ['error' => $e->getMessage()]);
    
            if ($this->request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
    
            redirect('/facturacion/listar?error=' . urlencode($e->getMessage()));
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
    public function ver()
    {
        try {
            $id = (int) $this->request->get('id');

            $factura = $this->model->getFacturaById($id);
            $productos = $this->model->getDetalleFacturaByFacturaId($id);
            $cuotas = $this->model->getCuotasByFacturaId($id);

            if (!$factura) {
                throw new Exception("Factura no encontrada.");
            }
    
            $this->logger->info("Datos Factura: ", [$factura]);
            return view('facturacion/detalle.factura', array_merge([
                'factura' => $factura,
                'productos' => $productos,
                'cuotas' => $cuotas
            ], $this->menu));
        } catch (Exception $e) {
            $this->logger->error("Error en ver factura: " . $e->getMessage());
            return view('facturacion/detalle.factura', ['error' => $e->getMessage()]);
        }
    }
     
    public function subirComprobante()
    {
        $idFactura = $this->request->sanitize($this->request->get('id_factura'));
    
        if ($this->request->method() === 'POST') {
            try {
                $factura = $this->model->getFacturaById($idFactura);
    
                if ($factura) {
                    if (isset($_FILES['comprobante']) && is_uploaded_file($_FILES['comprobante']['tmp_name'])) {
                        
                        // Crear instancia de Imagen
                        $imagen = new Imagen(
                            $_FILES['comprobante']['name'],
                            $_FILES['comprobante']['type'],
                            $_FILES['comprobante']['tmp_name'],
                            $_FILES['comprobante']['size'],
                            $_FILES['comprobante']['error'],
                            $this->logger
                        );
    
                        // Subir archivo (reutilizás la lógica ya definida en Imagen)
                        $imagen->subirArchivo('comprobantes'); // Podés usar una carpeta específica para comprobantes
                        $nuevoArchivo = $imagen->getFileName();
    
                        // Actualizar la factura con el nuevo path
                        $this->model->actualizarFactura([
                            'id' => $idFactura,
                            'path_comprobante' => $nuevoArchivo
                        ]);
    
                        $this->logger->info("Comprobante subido correctamente", ['factura_id' => $idFactura, 'archivo' => $nuevoArchivo]);
                    } else {
                        $this->logger->warning("No se recibió archivo válido para comprobante");
                    }
                } else {
                    $this->logger->warning("Factura no encontrada con ID: $idFactura");
                }
    
            } catch (Exception $e) {
                $this->logger->error("Error al subir comprobante", ['error' => $e->getMessage()]);
            }
    
            // Redirigir de nuevo a la vista de la factura
            redirect('facturacion/ver?id=' . $idFactura);
        }
    }

    public function verComprobante()
    {
        $id = $this->request->get('id_factura');
    
        try {
            $factura = $this->model->getFacturaById($id);
    
            if (!$factura || empty($factura['path_comprobante'])) {
                throw new Exception("No se encontró el comprobante asociado.");
            }
    
            $path = realpath(Imagen::UPLOADDIRECTORY_COMPROBANTES . $factura['path_comprobante']);
            $this->logger->info("path comprobante: ", [$path]);
    
            if (!$path || !file_exists($path)) {
                throw new Exception("El archivo no existe en el servidor.");
            }
    
            // Limpieza del buffer para evitar salida corrupta
            if (ob_get_length()) {
                ob_end_clean();
            }
    
            $mime = Imagen::getMimeType($factura['path_comprobante'], 'comprobantes');
    
            header('Content-Type: ' . $mime);
            header('Content-Disposition: inline; filename="' . basename($path) . '"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: private');
            header('Pragma: public');
    
            readfile($path);
            exit;
    
        } catch (Exception $e) {
            $this->logger->error("Error al visualizar comprobante: " . $e->getMessage());
            http_response_code(404);
            echo "No se pudo visualizar el comprobante.";
            exit;
        }
    }
    
        

}
       