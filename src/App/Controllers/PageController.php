<?php 

namespace Paw\App\Controllers;

use Exception;
use Paw\Core\Controller;
use Paw\App\Controllers\UserController;
use Paw\App\Models\OrdenCollection;
use Paw\Core\Traits\Loggable;
use Paw\App\Utils\Uploader;
use Paw\App\Models\FacturasCollection;
use Paw\App\Models\ProductosCollection;

class PageController extends Controller
{
    public ?string $modelName = OrdenCollection::class;    
    use Loggable;
    public $usuario;
    public $uploader;

    public function __construct()
    {
        global $log;
         
        parent::__construct();     
        $this->uploader = new Uploader;
        $this->usuario = new UserController();
        $this->usuario->setLogger($log);

        // $log->info("info __construct: this->menu",  [$this->menu]);
        $this->menu = $this->usuario->adjustMenuForSession($this->menu);        

        // $log->info("this->menu: ", [$this->menu]);
    }

    public function home()
    {
        $this->usuario->verificarSesion();

        // Obtener datos de facturación
        $facturasModel = new FacturasCollection($this->qb, $this->logger);
        $cantidadSinComprobante = $facturasModel->contarSinComprobanteAdjunto();
        $totalFacturas = $facturasModel->contarTodas();

        // Obtener datos de productos
        $this->logger->info("logger: ", [$this->logger]);
        $productosModel = new ProductosCollection($this->qb, $this->logger);
        
        $productosSinPrecio = $productosModel->contarSinPrecio();
        $totalProductos = $productosModel->contarTodos();
        $productosPorUnidad = $productosModel->contarPorUnidadMedida();

        // Separar claves y valores para pasarlos a la vista
        $productosUnidadLabels = array_keys($productosPorUnidad);
        $productosUnidadData = array_values($productosPorUnidad);

        // Tarjetas
        $cards = [
            [
                'title' => 'Modulo de Facturacion',
                'description' => 'Gestion de Puntos de Venta.',
                'link' => '/facturacion/new',
                'button_text' => 'Nueva Factura'
            ]
        ];

        // Pasar los datos a la vista
        view('home.view', [
            "datos" => ["action" => "nuevo"],
            "cards" => $cards,
            "cantidad_sin_comprobante" => $cantidadSinComprobante,
            "total_facturas" => $totalFacturas,
            "productos_sin_precio" => $productosSinPrecio,
            "total_productos" => $totalProductos,
            "productos_unidad_labels" => $productosUnidadLabels,
            "productos_unidad_data" => $productosUnidadData,
            ...$this->menu
        ]);
    }
}
