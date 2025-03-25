<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;


use Paw\App\Models\CuotasCollection;
use Exception;


class CuotasController extends Controller
{
    
    use Loggable;

    public $usuario;

    public $configFacturacion;
    public $dependencias;

    public ?string $modelName = CuotasCollection::class; 

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
    


    public function listar()
    {
        $page = (int) ($this->request->get('page') ?? 1);
        $desde = $this->request->get('desde') ?? date('Y-m-01');
        $hasta = $this->request->get('hasta') ?? date('Y-m-t');
        $limit = 10;
        $offset = ($page - 1) * $limit;
    
        try {
            // Obtener cuotas paginadas y total
            $cuotas = $this->model->getCuotasByFecha($desde, $hasta, $limit, $offset);
            $totalCuotas = $this->model->countCuotasByFecha($desde, $hasta);
            $hayPagadas = $this->model->hayCuotasPagadas($desde, $hasta);
    
            // Si es una solicitud AJAX
            if ($this->request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'cuotas' => $cuotas,
                    'total' => $totalCuotas,
                    'limit' => $limit,
                    'currentPage' => $page,
                    'desde' => $desde,
                    'hasta' => $hasta,
                    'hayPagadas' => $hayPagadas
                ]);
                exit;
            }
    
            // Si es una solicitud normal
            return view('facturacion/cuotas/cuotas.listado-filtrado', array_merge([
                'cuotas' => $cuotas,
                'total' => $totalCuotas,
                'limit' => $limit,
                'currentPage' => $page,
                'desde' => $desde,
                'hasta' => $hasta,
                'hayPagadas' => $hayPagadas
            ], $this->menu));
        
        } catch (Exception $e) {
            $this->logger->error("Error al listar cuotas: " . $e->getMessage());
    
            if ($this->request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
    
            try {
                return view('cuota/cuota.listado', ['error' => $e->getMessage()]);
            } catch (Exception $viewError) {
                $this->logger->error("Error al cargar la vista de cuotas: " . $viewError->getMessage());
                return "Ocurrió un error al cargar la página.";
            }
        }
    }
    public function exportarTxt()
    {
        try {
            $desde = $this->request->get('desde') ?? date('Y-m-01');
            $hasta = $this->request->get('hasta') ?? date('Y-m-t');

            $contenido = $this->model->generarTextoExportacion($desde, $hasta);

            $this->logger->info("exportar txt Controller: " , [$contenido]);

            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="cuotas_' . date('Ymd_His') . '.txt"');
            echo $contenido;
            exit;

        } catch (Exception $e) {
            $this->logger->error("Error en exportarTxt: " . $e->getMessage());
            echo "Ocurrió un error al generar el archivo.";
            exit;
        }
    }


}
       