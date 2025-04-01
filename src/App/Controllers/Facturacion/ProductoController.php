<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;
use Paw\App\Models\Imagen;

use Paw\App\Models\Producto;

use Exception;

use Paw\App\Models\ProductosCollection;

class ProductoController extends Controller
{   
    public ?string $modelName = ProductosCollection::class; 
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
                    $imagen = new Imagen($_FILES['imagen'], $this->logger);
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
                view('facturacion/productos/alta.producto', array_merge([
                    'error' => $e->getMessage()
                ], $this->menu));
            }

        } else {
            view('facturacion/productos/alta.producto', $this->menu);
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

                $this->logger->info("S_FILES :", [$_FILES]);
                // Si hay nueva imagen, la procesamos
                if ($_FILES['imagen']['tmp_name']) {
                    $imagen = new Imagen(
                        $_FILES['imagen']['name'], 
                        $_FILES['imagen']['type'], 
                        $_FILES['imagen']['tmp_name'], 
                        $_FILES['imagen']['size'], 
                        $_FILES['imagen']['error'], 
                        $this->logger);

                    $data['path_imagen'] = $imagen->getFileName();
                }

                $this->logger->info("datos de la imagen :", [$imagen->load()]);
                $resultSearch = $this->model->getById($data['id']);
                if($resultSearch)
                {
                    $this->logger->info("Producto encontrado: ", [$resultSearch]);
                    // Si la imagen no ha cambiado, no la reemplazamos

                    $imagen->subirArchivo();
                    $data['path_imagen'] = $imagen->getFileName();
                    $success = $this->model->actualizarProducto([
                        'id' => $data['id'], 
                        'nro_proyecto_productivo' => $data['nro_proyecto_productivo'],
                        'descripcion_proyecto' => $data['descripcion_proyecto'],
                        'estado' => $data['estado'],
                        'id_taller' => $resultSearch['id_taller'],
                        'id_unidad_q_fabrica' => $resultSearch['id_unidad_q_fabrica'],
                        'stock_inicial' => $data['stock_inicial'],
                        'unidad_medida' => $data['unidad_medida'],
                        'path_imagen' => $data['path_imagen']
                    ]);
                    
                    if ($success) {
                        $this->logger->info("Producto actualizado correctamente", [$data]);
                        redirect('facturacion/productos/ver?id_producto=' . $data['id']);
                    } else {
                        throw new Exception("No se pudo actualizar el producto.");
                    }
                }

            } catch (Exception $e) {
                $this->logger->error("Error al actualizar producto", ['error' => $e->getMessage()]);
                view('facturacion/productos/editar.producto', array_merge([
                    'producto' => $this->model->getDetalleProducto($id),
                    'error' => $e->getMessage()
                ], $this->menu));
            }

        } else {
            $producto = $this->model->getDetalleProducto($id);
            view('facturacion/productos/editar.producto', array_merge([
                'producto' => $producto
            ], $this->menu));
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
    
        if ($jsonList) {

            if ($searchItem) {
                try {
                    $this->logger->info("bucle if(searchItem){", [$searchItem]);
                    $listaProductos = $this->model->getProductosYPrecios($searchItem);
        
                    // Enviar la respuesta en formato JSON
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, $listaProductos]);
                    exit; // Detener la ejecución después de enviar la respuesta

                } catch (Exception $e) {
                    // Manejo de errores en JSON
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }

            }else{
                try {
                    $this->logger->info("bucle else {");
                    $listaProductos = $this->model->getProductosYPrecios();
        
                    // Enviar la respuesta en formato JSON
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'data' => $listaProductos]);
                    exit; // Detener la ejecución después de enviar la respuesta
                } catch (Exception $e) {
                    // Manejo de errores en JSON
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }    
            }

        } else {
            $listaProductos = $this->model->getProductosConUltimoPrecio();
            $this->logger->info("listaProductos: ", [$listaProductos]);
    
            view('facturacion/productos/listado', array_merge(
                ['listaProductos' => $listaProductos],
                $this->menu
            ));
        }
    }
    

    public function ver()
    {
        if ($this->request->get('id_producto') !== null) {
            $id = $this->request->get('id_producto');
            $this->logger->info("id_producto: ", [$id]);
    
            $detalleProducto = $this->model->getDetalleProducto($id);
            $precios = $this->model->getHistorialPrecios($id);
    
            view('facturacion/productos/detalle.producto', array_merge(
                ['producto' => $detalleProducto],
                ['precios' => $precios],
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
