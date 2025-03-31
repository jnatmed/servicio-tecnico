<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;
use Paw\App\Models\Imagen;
use Paw\App\Models\ImagenCollection;

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
        
    }

    public function editarProducto()
    {

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
            $listaProductos = $this->model->getProductosALaVenta();
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
    

}
