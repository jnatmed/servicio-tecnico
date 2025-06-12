<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;
use Paw\App\Models\Imagen;

use Paw\App\Models\Producto;

use Exception;

use Paw\App\Models\ProductosCollection;
use Paw\App\Models\DependenciasCollection;

class ProductoController extends Controller
{   
    public ?string $modelName = ProductosCollection::class; 
    use Loggable;
    public $usuario;
    public $producto;
    public $configFacturacion;

    public function __construct($usuariosAdmin=null)
    {
        global $log;
        
        parent::__construct($usuariosAdmin);     

        $this->usuario = new UserController();
        $this->usuario->setLogger($log);

        // $log->info("info __construct: this->menu",  [$this->menu]);
        $this->menu = $this->claseMenu->getMenuFiltrado($this->usuario->getRolUsuario(), $this->usuario->haySession());       

        // $log->info("this->menu: ", [$this->menu]);
    }

    public function alta()
    {
        if ($this->request->method() == 'POST') {
            try {
                $data = [
                    'descripcion_proyecto' => $this->request->get('descripcion_proyecto'),
                    'estado' => $this->request->get('estado'),
                    'stock_inicial' => $this->request->get('stock_inicial'),
                    'unidad_medida' => $this->request->get('unidad_medida'),
                    'nro_proyecto_productivo' => $this->request->get('nro_proyecto_productivo'),
                ];

                $this->request->sanitize($data);

                // Procesar imagen si se cargó una
                if ($_FILES['imagen']['tmp_name']) {
                    $imagen = new Imagen(
                        $_FILES['imagen']['name'],
                        $_FILES['imagen']['type'],
                        $_FILES['imagen']['tmp_name'],
                        $_FILES['imagen']['size'],
                        $_FILES['imagen']['error'],
                        $this->logger
                    );
                    $imagen->guardar();
                    $data['path_imagen'] = $imagen->getNombreArchivo();
                }

                $producto = new Producto($data, $this->logger);
                list($idInsertado, $success) = $this->model->updateProducto($producto);

                if ($success) {
                    $this->logger->info("Producto insertado correctamente con ID $idInsertado");
                    redirect('/facturacion/productos/ver?id_producto=' . $idInsertado);
                } else {
                    throw new Exception("Error al insertar el producto.");
                }

            } catch (Exception $e) {
                $this->logger->error("Error en alta producto", ['error' => $e->getMessage()]);
                view('/facturacion/productos/alta.producto', array_merge([
                    'error' => $e->getMessage()
                ], $this->menu));
            }

        } else {
            view('facturacion/productos/alta.producto', $this->menu);
        }
    }

    public function registrarDecomiso()
    {
        try {
            $productoId = $this->request->sanitize($this->request->get('id_producto'), 'int');
            $cantidad = $this->request->sanitize($this->request->get('cantidad'), 'int');
            $descripcion = $this->request->sanitize($this->request->get('descripcion_decomiso'), 'string');
            $archivo = $this->request->file('comprobante');

            $this->logger->debug("parametros entrada registrarDecomiso: [productoId, cantidad, descripcion, archivo]", [$productoId, $cantidad, $descripcion, $archivo]);

            if (!$productoId || !$cantidad || !$descripcion || !$archivo || $archivo['error'] !== 0) {
                throw new Exception('Faltan datos obligatorios o el archivo no se subió correctamente.');
            }
    
            // Validar stock actual
            $stockActual = $this->model->obtenerStockActual($productoId); // implementado en ProductosCollection
    
            if ($cantidad > $stockActual) {
                throw new Exception("No se puede decomisar $cantidad unidades. Stock disponible: $stockActual.");
            }
    
            // Subida del archivo
            $resultadoUpload = \Paw\App\Models\Uploader::uploadFile($archivo);
    
            if ($resultadoUpload['exito'] !== \Paw\App\Models\Uploader::UPLOAD_COMPLETED) {
                throw new Exception("Error al subir el comprobante: " . ($resultadoUpload['description'] ?? ''));
            }
    
            // Registrar movimiento
            $this->model->registrarMovimientoInventario([
                'producto_id' => $productoId,
                'fecha_movimiento' => date('Y-m-d H:i:s'),
                'tipo_movimiento' => 'out',
                'cantidad' => $cantidad,
                'descripcion_movimiento' => "Decomiso: " . $descripcion,
                'path_comprobante_decomiso' => $resultadoUpload['nombre_imagen']
            ]);
    
            $this->logger->info("✔️ Decomiso registrado: Producto #$productoId - Cantidad: $cantidad");
    
            redirect("facturacion/productos/ver?id_producto=$productoId");
    
        } catch (Exception $e) {
            $this->logger->error("❌ Error al registrar decomiso: " . $e->getMessage());
            redirect("facturacion/productos/ver?id_producto=$productoId");
        }
    }
        
    public function editarProducto()
    {
        $id = $this->request->get('id_producto');

        if ($this->request->method() == 'POST') {
            try {
                $data = [
                    'id' => $this->request->get('id'),
                    'descripcion_proyecto' => $this->request->get('descripcion_proyecto'),
                    'estado' => $this->request->get('estado'),
                    'stock_inicial' => $this->request->get('stock_inicial'),
                    'unidad_medida' => $this->request->get('unidad_medida'),
                    'nro_proyecto_productivo' => $this->request->get('nro_proyecto_productivo')
                ];

                $this->request->sanitize($data);

                $this->logger->info("📁 Archivos recibidos (S_FILES):", [$_FILES]);

                // Procesar imagen si fue cargada
                if (!empty($_FILES['imagen']['tmp_name'])) {
                    $imagen = new Imagen(
                        $_FILES['imagen']['name'],
                        $_FILES['imagen']['type'],
                        $_FILES['imagen']['tmp_name'],
                        $_FILES['imagen']['size'],
                        $_FILES['imagen']['error'],
                        $this->logger
                    );

                    $resultadoSubida = $imagen->subirArchivo();

                    if (!$resultadoSubida['exito']) {
                        throw new \Exception("Fallo al subir la imagen: " . $resultadoSubida['description']);
                    }

                    $data['path_imagen'] = $resultadoSubida['path_imagen'];
                    $this->logger->info("🖼️ Imagen subida correctamente:", [$resultadoSubida]);
                }

                $productoExistente = $this->model->getById($data['id']);

                if ($productoExistente) {
                    $this->logger->info("✅ Producto encontrado:", [$productoExistente]);

                    // Actualizar con datos del formulario y la imagen (si hubo)
                    $exito = $this->model->actualizarProducto([
                        'id' => $data['id'],
                        'nro_proyecto_productivo' => $data['nro_proyecto_productivo'],
                        'descripcion_proyecto' => $data['descripcion_proyecto'],
                        'estado' => $data['estado'],
                        'id_taller' => $productoExistente['id_taller'],
                        'id_unidad_q_fabrica' => $productoExistente['id_unidad_q_fabrica'],
                        'stock_inicial' => $data['stock_inicial'],
                        'unidad_medida' => $data['unidad_medida'],
                        'path_imagen' => $data['path_imagen'] ?? $productoExistente['path_imagen']
                    ]);

                    if ($exito) {
                        $this->logger->info("📦 Producto actualizado correctamente", [$data]);
                        redirect('facturacion/productos/ver?id_producto=' . $data['id']);
                    } else {
                        throw new \Exception("No se pudo actualizar el producto.");
                    }
                } else {
                    throw new \Exception("Producto no encontrado.");
                }
            } catch (\Exception $e) {
                $this->logger->error("❌ Error al actualizar producto", ['error' => $e->getMessage()]);
                view('facturacion/productos/editar.producto', array_merge([
                    'producto' => $this->model->getDetalleProducto($id),
                    'error' => $e->getMessage()
                ], $this->menu));
            }
        } else {
            // GET: Mostrar formulario con datos actuales
            $producto = $this->model->getDetalleProducto($id);
            view('facturacion/productos/editar.producto', array_merge([
                'producto' => $producto
            ], $this->menu));
        }
    }

    public function nuevo()
    {
        try {
            if ($this->request->method() === 'post') {
                // 🟡 Procesar formulario
                $this->logger->info("Recepción de datos para creación de producto", [$this->request->all()]);

                $descripcion = $this->request->get('descripcion_proyecto');
                $estado = $this->request->get('estado');
                $stockInicial = (int) $this->request->get('stock_inicial');
                $unidadMedida = $this->request->get('unidad_medida');
                $nroProyecto = $this->request->get('nro_proyecto_productivo');

                $imagen = $_FILES['imagen'] ?? null;
                $nombreImagen = null;

                if ($imagen && $imagen['error'] === UPLOAD_ERR_OK) {
                    $resultado = \Paw\App\Models\Uploader::uploadFile($imagen);

                    if ($resultado['exito'] === true) {
                        $nombreImagen = $resultado['nombre_imagen'];
                        $this->logger->info("Imagen subida exitosamente", [$nombreImagen]);
                    } else {
                        $this->logger->error("Error al subir imagen: " . $resultado['description']);
                        echo "Error al subir imagen: " . $resultado['description'];
                        return;
                    }
                }

                $this->model->crearProducto([
                    'descripcion_proyecto' => $descripcion,
                    'estado' => $estado,
                    'stock_inicial' => $stockInicial,
                    'unidad_medida' => $unidadMedida,
                    'nro_proyecto_productivo' => $nroProyecto,
                    'imagen' => $nombreImagen
                ]);

                redirect('facturacion/productos/listado');

            } else {
                // 🟢 Mostrar formulario
                $this->logger->info("Acceso a formulario de nuevo producto");
                view('facturacion/productos/nuevo.producto', $this->menu);
            }

        } catch (Exception $e) {
            $this->logger->error("Error en nuevo(): " . $e->getMessage());
            echo "Ocurrió un error al procesar el producto.";
        }
    }




    public function eliminarProducto()
    {
        $id = $this->request->sanitize($this->request->get('id_producto'));
        $this->logger->info("id_producto a eliminar: ", [$id]);
    
        try {
            // Obtener producto
            $producto = $this->model->getById($id);
    
            if (!$producto) {
                throw new Exception("Producto no encontrado.");
            }
    
            // Eliminar producto
            $filasAfectadas = $this->model->eliminarProducto($id);
    
            if ($this->request->isAjax()) {
                if ($filasAfectadas > 0) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'No se eliminó ningún producto.']);
                }
                return;
            }
    
            // Redirección tradicional si no es AJAX
            if ($filasAfectadas > 0) {
                redirect('facturacion/productos/listado?msg=Producto eliminado con éxito');
            } else {
                redirect('facturacion/productos/listado?error=No se pudo eliminar el producto');
            }
    
        } catch (Exception $e) {
            $this->logger->error("Error al eliminar producto", ['error' => $e->getMessage()]);
    
            if ($this->request->isAjax()) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                return;
            }
    
            redirect('facturacion/productos/listado?error=Ocurrió un error al eliminar el producto');
        }
    }

    public function verImgProducto()
    {
        $idImgProducto = $this->request->get('id_producto');

        try {

            // Obtener la imagen de la publicación
            $imagenProducto = $this->model->getById($idImgProducto);

            $this->logger->info("(method- getImgProducto) - imagenProducto:", [$imagenProducto]);

            if ($imagenProducto === false) {
                // Si no se encuentra la imagen, devolver un código de error 404
                http_response_code(404);
                // exit;
            }

            $this->logger->info("VALOR DE imagenProducto", [$imagenProducto]);

            $this->logger->info("imagenProducto: -- ", [Imagen::UPLOADDIRECTORY . $imagenProducto['path_imagen']]);

            $mime_type = Imagen::getMimeType($imagenProducto['path_imagen']);

            $this->logger->info("(method- getImgProducto) - mime_type: ", [$mime_type]);

            $this->logger->info("imagenProducto: -- ", [Imagen::UPLOADDIRECTORY . $imagenProducto['path_imagen']]);


            // Establecer el tipo MIME de la imagen y enviarla al cliente
            header("Content-type: " . $mime_type);
            echo file_get_contents(Imagen::UPLOADDIRECTORY . $imagenProducto['path_imagen']);
        } catch (Exception $e) {
            // Manejo de la excepción
            // Registrar el error utilizando el logger
            $this->logger->error("Error al obtener la imagen de la publicación: " . $e->getMessage());

            $mime_type = Imagen::getMimeType('default.png');
            header("Content-type: " . $mime_type);
            echo file_get_contents(Imagen::UPLOADDIRECTORY . 'default.png');
        }
    }

    public function listar()
    {
        $jsonList = $this->request->get('jsonList');
        $searchItem = $this->request->get('search');

        // $usuarioDependencia = $this->usuario->getDependenciaId();
        // Agregar dependencia si corresponde
        if (
            $this->usuario->getRolUsuario() === PUNTO_VENTA &&
            $this->usuario->getDependenciaId() !== null
        ) {
            $usuarioDependencia = $this->usuario->getDependenciaId();
        }else{
            $usuarioDependencia = null;
        }

        $this->logger->info("📡 Entrando al método listar()", [
            'jsonList' => $jsonList,
            'search' => $searchItem,
            'usuarioDependencia' => $usuarioDependencia,
        ]);

        if ($jsonList) {
            if ($searchItem != '') {
                try {
                    $this->logger->info("📥 Solicitud AJAX con búsqueda", ['searchItem' => $searchItem]);
                    
                    $listaProductos = $this->model->getProductosYPrecios($searchItem, $usuarioDependencia);
                    $this->logger->info("consulta json, id_producto: ", $listaProductos[0]);

                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'productos' => $listaProductos]);
                    exit;
    
                } catch (Exception $e) {
                    $this->logger->error("❌ Error al buscar productos (AJAX)", ['error' => $e->getMessage()]);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }
            } else {
                try {
                    $this->logger->info("📥 Solicitud AJAX sin búsqueda (cargar todos los productos)");
    
                    $listaProductos = $this->model->getProductosYPrecios(null, $usuarioDependencia);
    
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'productos' => $listaProductos]);
                    exit;
    
                } catch (Exception $e) {
                    $this->logger->error("❌ Error al cargar todos los productos (AJAX)", ['error' => $e->getMessage()]);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }
            }
        } else {
            try {
                $this->logger->info("🖥️ Solicitud de vista completa (no JSON)");
    

                $listaProductos = $this->model->getProductosConUltimoPrecio($usuarioDependencia, $this->usuario->getRolUsuario());
                $this->logger->debug("📦 Productos cargados para vista", ['cantidad' => count($listaProductos), 'usuarioDependencia' => $usuarioDependencia]);
    
                // Agregar dependencia si corresponde
                if (
                    $this->usuario->getRolUsuario() === PUNTO_VENTA &&
                    $this->usuario->getDescripcionDependencia() !== null
                ) {
                    $data['nombre_dependencia'] = $this->usuario->getDescripcionDependencia();
                }else{
                    $data['nombre_dependencia'] = 'Vista General';
                }                

                view('facturacion/productos/listado', array_merge(
                    [
                        'listaProductos' => $listaProductos 
                    ],
                    $data,
                    $this->menu
                ));
            } catch (Exception $e) {
                $this->logger->error("❌ Error al cargar vista de productos", ['error' => $e->getMessage()]);
                // Podrías redirigir o mostrar error 500 personalizado
            }
        }
    }
    
    public function verComprobanteDecomiso()
    {
        $productoId = $this->request->get('producto_id');
        $fechaMovimiento = $this->request->get('fecha');
    
        try {
            $path = $this->model->obtenerComprobanteDecomiso($productoId, $fechaMovimiento);
    
            $rutaAbsoluta = \Paw\App\Models\Uploader::UPLOADDIRECTORY . $path;
    
            $this->logger->debug("🧾 Ruta comprobante decomiso:", [
                'producto_id' => $productoId,
                'fecha' => $fechaMovimiento,
                'archivo' => $path,
                'ruta' => $rutaAbsoluta
            ]);
    
            if (!$path || !$rutaAbsoluta || !file_exists($rutaAbsoluta)) {
                throw new \Exception("No se encontró el comprobante.");
            }
    
            if (ob_get_length()) {
                ob_end_clean();
            }
    
            $mime = \Paw\App\Models\Uploader::getMimeType($path);
    
            header('Content-Type: ' . $mime);
            header('Content-Disposition: inline; filename="' . basename($path) . '"');
            header('Content-Length: ' . filesize($rutaAbsoluta));
            header('Cache-Control: private');
            header('Pragma: public');
    
            readfile($rutaAbsoluta);
            exit;
    
        } catch (\Exception $e) {
            $this->logger->error("❌ Error al visualizar comprobante de decomiso: " . $e->getMessage());
            http_response_code(404);
            echo "No se pudo visualizar el comprobante.";
            exit;
        }
    }
    
    
        

    public function ver()
    {
        $id = $this->request->get('id_producto');
        
        if ($id !== null) {
            $this->logger->info("id_producto: ", [$id]);
    
            $detalleProducto = $this->model->getDetalleProducto($id);
            $this->logger->info("getDetalleProducto, ", [$detalleProducto]);
            $precios = $this->model->getHistorialPrecios($id);
            $this->logger->info("getHistorialPrecios, ", [$precios]);
            $movimientos = $this->model->getMovimientosInventario($id); 
            $this->logger->info("getMovimientosInventario, ", [$movimientos]);
            $stockActual = $this->model->getStockActual($id);
            $this->logger->info("getStockActual, ", [$stockActual]);
    
            view('facturacion/productos/detalle.producto', array_merge(
                ['producto' => $detalleProducto],
                ['precios' => $precios],
                ['movimientos' => $movimientos],
                ['stock_actual' => $stockActual],
                $this->menu
            ));
        } else {
            $this->logger->error("Error al obtener el id_producto");
            // Podrías redirigir o mostrar un mensaje de error
        }
    }
    
    


    public function agregarPrecio()
    {
        $id = $this->request->sanitize($this->request->get('id_producto'));
    
        if ($this->request->method() == 'POST') {
            $data = [
                'id_producto' => $this->request->get('id_producto'),
                'precio' => $this->request->get('precio'),
                'fecha_precio' => $this->request->get('fecha_precio'),
                'pv_autorizacion_consejo' => $this->request->get('pv_autorizacion_consejo')
            ];
        
            try {
                $this->request->sanitize($data);
        
                $this->model->insertarPrecio($data); // método que debés tener en el modelo
        
                $this->logger->info("Nuevo precio registrado correctamente", $data);
                redirect('facturacion/productos/ver?id_producto=' . $data['id_producto']);
            } catch (Exception $e) {
                $this->logger->error("Error al registrar nuevo precio", ['error' => $e->getMessage()]);
                view('facturacion/productos/agregar.precio', array_merge(
                    ['producto' => $this->model->getDetalleProducto($data['id_producto'])],
                    ['error' => 'No se pudo guardar el precio.'],
                    $this->menu
                ));
            }
            
        } else {
            $detalleProducto = $this->model->getDetalleProducto($id);
    
            if (!$detalleProducto || empty($detalleProducto['id'])) {
                $this->logger->error("Producto no encontrado con ID: $id");
                view('facturacion/productos/agregar.precio', array_merge(
                    ['producto' => null],
                    ['error' => 'No se encontró el producto'],
                    $this->menu
                ));
            } else {
                view('facturacion/productos/agregar.precio', array_merge(
                    ['producto' => $detalleProducto],
                    $this->menu
                ));
            }
        }
    }
    

}
