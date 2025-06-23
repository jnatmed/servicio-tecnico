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
use Paw\App\Models\GraphMailer;

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

        // $log->info("info __construct: this->menu",  [$this->menu]);
        $this->menu = $this->claseMenu->getMenuFiltrado($this->usuario->getRolUsuario(), $this->usuario->haySession());      

        // $log->info("this->menu: ", [$this->menu]);
    }
    

    public function alta() 
    {
        /**
         * Verifico si el metodo es POST, sino
         * muestro la pagina de carga de formulario
         */
        if ($this->request->method() == 'POST') {
            try {
                // Paso 1: Obtener ID del usuario
                $userId = $this->usuario->getIdUser();
                $dependenciaId = $this->usuario->getDependenciaId();
                $this->logger->info("Metodo alta POST");
                // $this->logger->debug("Usuario autenticado ID: {$userId}");
        
                // Paso 2: Calcular número de factura seguro desde la tabla numerador_factura
                $numeradorInfo = $this->model->getProximoNumeroFacturaPorDependencia($dependenciaId);
                $puntoDeVenta = $numeradorInfo['punto_venta'];
                $nroSecuencial = $numeradorInfo['proximo_numero'];
                $nroFacturaGenerado = sprintf("%04d-%08d", $puntoDeVenta, $nroSecuencial);
                $this->logger->info("Número de factura generado: {$nroFacturaGenerado}");
        
                // Paso 3: Preparar datos de la factura
                $data = [
                    'nro_factura' => $nroFacturaGenerado,
                    'id_agente' => $this->request->get('agente'),
                    'fecha_factura' => date('Y-m-d'),
                    'unidad_que_factura' => $this->request->get('dependencia'),
                    'condicion_venta' => $this->request->get('condicion_venta'),
                    'condicion_impositiva' => $this->request->get('condicion_impositiva'),
                    'total_facturado' => $this->request->get('total_facturado'),
                    'cantidad_cuotas' => $this->request->get('cantidad_cuotas'),
                    'path_comprobante' => ''
                ];
        
                $productos = json_decode($this->request->get('productos'), true);
                $this->request->sanitize($data);
                $this->logger->debug("Datos recibidos para alta de factura: ", $data);
        
                if (empty($data['nro_factura']) || empty($data['id_agente']) || empty($productos)) {
                    throw new Exception("Faltan datos obligatorios.");
                }
        
                // Crear e insertar la factura
                $factura = new Factura($data, $this->logger);
                $facturaId = $this->model->insertFactura($factura);
                $this->logger->debug("Factura insertada con ID: {$facturaId}");
        
                // Paso 4: Actualizar el numerador de factura
                $this->model->actualizarNumeradorFactura($numeradorInfo['id_numerador'], $nroSecuencial);
        
                // Paso 5: Generar cuotas si corresponde
                if (in_array($data['condicion_venta'], ['codigo_608', 'codigo_689']) && $data['cantidad_cuotas'] > 0) {
                    $this->logger->info("Generando cuotas para la factura ID: {$facturaId}");
                    $this->cuotasCollection = new CuotasCollection($this->qb, $this->logger);
                    $this->cuotasCollection->generarCuotas($facturaId, $data['total_facturado'], $data['cantidad_cuotas']);
                }
        
                // Paso 6: Insertar detalle de productos y registrar movimiento
                foreach ($productos as $productoData) {
                    $queryProducto = new ProductosCollection($this->qb, $this->logger);
                    $productoExistente = $queryProducto->getById($productoData['id']);
        
                    if (!$productoExistente) {
                        throw new Exception("El producto con ID {$productoData['id']} no existe.");
                    }
        
                    $detalleFacturaId = $this->model->insertDetalleFactura(
                        new DetalleFactura([
                            'factura_id' => $facturaId,
                            'producto_id' => $productoData['id'],
                            'cantidad_facturada' => $productoData['cantidad'],
                            'precio_unitario' => $productoData['precio_unitario']
                        ], $this->logger)
                    );
                    
                    $this->logger->info("Detalle factura insertada. Datos Producto: ", [$productoData]);

                    $queryProducto->registrarMovimientoInventario([
                        'factura_id' => $facturaId,
                        'producto_id' => $productoData['id'],
                        'dependencia_id' => $productoData['dependencia_id'],
                        'tipo_movimiento' => 'out',
                        'cantidad' => $productoData['cantidad'],
                        'descripcion_movimiento' => "Descuento de inventario por Factura #{$data['nro_factura']}",
                        'path_comprobante_decomiso' => null,
                    ]);
                }
        
                // Respuesta final
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'factura_id' => $facturaId]);
                exit;
        
            } catch (Exception $e) {
                $this->logger->error("Error en alta de factura", ['error' => $e->getMessage()]);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        
        } else {
            
            $userId = $this->usuario->getIdUser();
            $this->logger->warning("Metodo GET alta factura");
            $this->logger->debug("ID de usuario obtenido: {$userId}");

            $this->logger->debug("Llamando a getProximoNumeroFacturaPorUsuario con usuario ID: {$userId}");
            $numeradorInfo = $this->model->getProximoNumeroFacturaPorDependencia($this->usuario->getDependenciaId());
            $this->logger->debug("Datos del numerador: ", $numeradorInfo);

            $data = [
                'fecha_factura' => date('d/m/Y'),
                'monto_minimo_cuota' => 10000,
                'dependencia_id' => $numeradorInfo['dependencia_id'],
                'dependencias' => $this->model->getDependencias(),
                'version' => time(),
                'dependencia_id_user' => $this->usuario->getDependenciaId(), // ??
            ];

            $this->logger->info("1er paso Get DATA: ", [$data]);

            /**
             * Caso 1: No se puede obtener numerador
             *  */ 

            if (!$numeradorInfo['success']) {
                $this->logger->warning("No se pudo obtener numerador: {$numeradorInfo['message']}");

                $data = array_merge($data, [
                    'mostrar_modal' => true,
                    'mensaje_modal' => $numeradorInfo['message'],
                    'solicitud_numeracion' => true,
                    'estado_dependencia' => $numeradorInfo['estado_dependencia'] ?? 'desconocido'
                ]);
            } else {
                /**
                 * Caso 2: Se pudo obtener numerador
                 * cargamos los datos de numeracion de la factura
                 *  */ 
                $this->logger->warning("Se pudo obtener numerador: {$numeradorInfo['message']}");
                $this->logger->info("numeracionInfo: ", [$numeradorInfo]);
                $puntoDeVenta = $numeradorInfo['punto_venta'];
                $nroSecuencial = $numeradorInfo['proximo_numero'];
                $nroFacturaSugerido = sprintf("%04d-%08d", $puntoDeVenta, $nroSecuencial); 

                $this->logger->info("Número de factura sugerido para el formulario: {$nroFacturaSugerido}");

                $data = array_merge($data, [
                    'nro_factura' => $nroFacturaSugerido,
                    'punto_venta' => $puntoDeVenta,
                    'estado_dependencia' => $numeradorInfo['estado_dependencia'] ?? 'confirmado',
                    'mostrar_modal' => false,
                ]);
            }

            $this->logger->info("Renderizando vista de facturación con datos: ", $data);

            return view('facturacion/factura_new', array_merge(
                $data,
                $this->configFacturacion,
                $this->menu
            ));
        }

    }

    public function solicitudNumeracion()
    {
        try {
            $this->logger->info("Inicio de solicitud de numerador");

            $dependenciaId = $this->usuario->getDependenciaId();
            $datos = json_decode(file_get_contents('php://input'), true);

            $expediente = $datos['expte_pedido_numeracion'] ?? null;
            $desde = isset($datos['desde']) ? (int)$datos['desde'] : null;
            $hasta = isset($datos['hasta']) ? (int)$datos['hasta'] : null;


            if (!$dependenciaId || !$expediente || !$desde || !$hasta) {
                throw new Exception("Datos incompletos para solicitar numerador.");
            }

            if ($desde > $hasta) {
                throw new Exception("El rango de numeración no es válido (desde > hasta).");
            }

            $this->logger->info("Datos recibidos, pasaron el control: ", [$expediente, $desde, $hasta]);

            // Insertar en la tabla numerador_factura
            $this->model->insertarSolicitudNumerador($dependenciaId, $expediente, $desde, $hasta);

            echo json_encode([
                'success' => true,
                'message' => 'Solicitud enviada correctamente.'
            ]);
        } catch (Exception $e) {
            $this->logger->error("Error en solicitarNumerador: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function listarNumerador()
    {
        $this->logger->info('listarNumerador() - Inicio');
    
        try {
            $datos = $this->model->getUltimasSolicitudesPorDependencia();
            $this->logger->debug('Datos obtenidos del modelo:', [$datos]);
    
            $esAjax = (
                $this->request->isAjax() ||
                strpos($this->request->getHeader('Accept'), 'application/json') !== false
            );
            $this->logger->info('¿Es AJAX? ' . ($esAjax ? 'Sí' : 'No'));
    
            if ($esAjax) {
                // Desanidar si viene como [[...]]
                if (is_array($datos) && count($datos) === 1 && is_array($datos[0])) {
                    $datos = $datos[0];
                }
    
                $response = [
                    'success' => true,
                    'data' => $datos
                ];
    
                $this->logger->debug('Respuesta JSON generada (array):', [$response]);
    
                // Devuelve JSON sin codificar dos veces
                header('Content-Type: application/json');
                echo json_encode($response);
                return;
    
            } else {
                return view('facturacion/numeracion_factura', array_merge(
                    ['listado_numeracion' => $datos],
                    $this->menu
                ));
            }
    
        } catch (\Exception $e) {
            $this->logger->error('Error en listarNumerador(): ' . $e->getMessage());
    
            $errorResponse = [
                'success' => false,
                'error' => 'No se pudieron cargar los datos.'
            ];
    
            if ($this->request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode($errorResponse);
                return;
            }
    
            return view('facturacion/numeracion_factura', array_merge(
                ['listado_numeracion' => [], 'error' => 'Error al cargar la vista.'],
                $this->menu
            ));
        }
    }
        
    
    public function aceptarSolicitud()
    {
        $this->logger->info('aceptarSolicitud() - Inicio de solicitud');
    
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id = $datos['numerador_id'] ?? null;
    
            $this->logger->debug('ID recibido en POST:', [$id]);
    
            if (!$id) {
                $this->logger->warning('ID no proporcionado en la solicitud');
                throw new Exception('ID no proporcionado.');
            }
    
            $this->model->aceptarSolicitudPorId($id);
            $this->logger->info("Solicitud de numeración aceptada correctamente para ID: $id");
    
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
    
        } catch (\Exception $e) {
            $this->logger->error('Error en aceptarSolicitud(): ' . $e->getMessage());
    
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    
    
    
    
    public function rechazarSolicitud()
    {
        $this->logger->info('rechazarSolicitud() - Inicio de solicitud');
    
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id = $datos['numerador_id'] ?? null;
            $motivo = $datos['motivo_rechazo'] ?? null;
    
            $this->logger->debug('Datos recibidos:', ['numerador_id' => $id, 'motivo_rechazo' => $motivo]);
    
            if (!$id || !$motivo) {
                $this->logger->warning('ID o motivo de rechazo no proporcionado');
                throw new Exception('ID o motivo no proporcionado.');
            }
    
            $this->logger->info("Llamando a rechazarSolicitudPorId($id)");
            $this->model->rechazarSolicitudPorId($id, $motivo);
    
            $this->logger->info("Solicitud de numeración rechazada correctamente para ID: $id");
    
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
    
        } catch (\Exception $e) {
            $this->logger->error('Error en rechazarSolicitud(): ' . $e->getMessage());
    
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
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
        $filtrarSinComprobante = $this->request->get('sin_comprobante') == '1';
        $limit = 10;
        $offset = ($page - 1) * $limit;
    
        try {
            $this->logger->info("getDependenciaId // getRolUsuario", [$this->usuario->getDependenciaId(),$this->usuario->getRolUsuario()]);
            // Obtener facturas paginadas
            $facturas = $this->model->getFacturasPaginated(
                 $limit, 
                 $offset, 
                 $searchItem, 
                 $filtrarSinComprobante,
                 $this->usuario->getDependenciaId() ?? null,
                 $this->usuario->getRolUsuario() ?? null
                );
            $totalFacturas = $this->model->countFacturas(
                  $searchItem, 
                  $filtrarSinComprobante, 
                  $this->usuario->getDependenciaId() ?? null,
                  $this->usuario->getRolUsuario() ?? null
                );
    
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
    
            // Armar datos base
            $data = [
                'facturas' => $facturas,
                'total' => $totalFacturas,
                'limit' => $limit,
                'currentPage' => $page,
                'search' => $searchItem,
            ];
            
            // Agregar dependencia si corresponde
            if (
                $this->usuario->getRolUsuario() === PUNTO_VENTA &&
                $this->usuario->getDescripcionDependencia() !== null
            ) {
                $data['nombre_dependencia'] = $this->usuario->getDescripcionDependencia();
            }else{
                $data['nombre_dependencia'] = 'Vista General';
            }
            // Renderizar vista
            return view('facturacion/factura.listado', 
                        array_merge($data,$this->menu)
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
       