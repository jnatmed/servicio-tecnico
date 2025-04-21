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
        $esPost = $this->request->method() === 'POST';
        $desde = null;
        $hasta = null;
        $limit = 10;
        $offset = ($page - 1) * $limit;
    
        try {
            $cuotas = [];
            $totalCuotas = 0;
            $hayPagadas = false;
    
            if ($esPost) {
                $data = json_decode(file_get_contents('php://input'), true);
                $desde = $data['desde'] ?? null;
                $hasta = $data['hasta'] ?? null;
                $page = (int) ($data['page'] ?? $page);
                $offset = ($page - 1) * $limit;
    
                $cuotas = $this->model->getCuotasByFecha($desde, $hasta, $limit, $offset);
                $totalCuotas = $this->model->countCuotasByFecha($desde, $hasta);
                $hayPagadas = $this->model->hayCuotasPagadas($desde, $hasta);
            }
    
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


    public function verSolicitudesPendientes()
    {
        try {
            $fecha = $this->request->get('fecha') ?? null;
    
            $solicitudes = $this->model->getDetalleSolicitudesPendientesPorFecha($fecha);
    
            // Si es una solicitud AJAX, devolvemos JSON
            if ($this->request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'solicitudes' => $solicitudes
                ]);
                exit;
            }
    
            // Si no es AJAX, renderiza la vista completa
            return view('facturacion/cuotas/solicitudes_pendientes.view', array_merge(
                ['solicitudes' => $solicitudes],
                $this->menu
            ));
        } catch (Exception $e) {
            $this->logger->error("Error en verSolicitudesPendientes: " . $e->getMessage());
    
            if ($this->request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Error al obtener las solicitudes.'
                ]);
                exit;
            }
    
            return view('facturacion/cuotas/solicitudes_pendientes.view', [
                'solicitudes' => [],
                'error' => 'Ocurrió un error al obtener las solicitudes pendientes.'
            ]);
        }
    }
    
    
    
    public function reporteAgrupado()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $desde = $data['desde'] ?? null;
            $hasta = $data['hasta'] ?? null;
    
            if (!$desde || !$hasta) {
                throw new Exception('Parámetros inválidos');
            }
    
            $grupos = $this->model->getCuotasAgrupadasPorAgente($desde, $hasta);
    
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'grupos' => $grupos
            ]);
            exit;
    
        } catch (Exception $e) {
            $this->logger->error("Error en reporteAgrupado: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    public function aplicarDescuentoMasivo()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $desde = $data['desde'] ?? null;
            $hasta = $data['hasta'] ?? null;
            $agentes = $data['agentes'] ?? [];
    
            if (!$desde || !$hasta || empty($agentes)) {
                throw new Exception('Parámetros inválidos');
            }
    
            $resultados = [];
    
            foreach ($agentes as $agente) {
                $agenteId = $agente['id'] ?? null;
                $agenteNombre = $agente['nombre'] ?? 'Sin nombre';
    
                if (!$agenteId) continue;
    
                $resultado = $this->model->aplicarDescuentoDeHaberesInteligente($agenteId, $desde, $hasta);
    
                if (!$resultado) continue;
    
                $resultados[] = [
                    'agente_id' => $agenteId,
                    'agente_nombre' => $agenteNombre,
                    'cuotas' => $resultado['cuotas'],
                    'total_descontado' => $resultado['total_descontado'],
                    'detalle_descontado' => $resultado['detalle_descontado'] ?? [] // Nueva línea
                ];
            }
    
            echo json_encode(['success' => true, 'resultados' => $resultados]);
    
        } catch (Exception $e) {
            $this->logger->error("Error al aplicar descuento masivo: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function confirmarDescuentos()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $fechaOriginal = $data['fecha_solicitud'] ?? null;

            // Convertir a formato MySQL si viene como dd/mm/yyyy
            $fecha = \DateTime::createFromFormat('d/m/Y', $fechaOriginal)?->format('Y-m-d');

            $descuentos = $data['descuentos'] ?? [];
    
            if (!$fecha || empty($descuentos)) {
                throw new Exception("Datos incompletos");
            }
    
            $this->logger->info("📥 Confirmar descuentos – Fecha original: $fechaOriginal → Formateada: $fecha");
            $this->logger->info("📥 Lista de descuentos:", $descuentos);
    
            $resultados = $this->model->confirmarDescuentosPorAgente($fecha, $descuentos);
    
            echo json_encode(['success' => true, 'resultados' => $resultados]);
        } catch (Exception $e) {
            $this->logger->error("❌ Error en confirmarDescuentos: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
        

    public function exportarTxt()   
    {
        try {
            $fechaSolicitud = $this->request->get('fecha_solicitud');

            if (!$fechaSolicitud) {
                http_response_code(400);
                echo "Falta el parámetro 'fecha_solicitud'.";
                return;
            }

            $contenido = $this->model->generarTextoExportacion($fechaSolicitud);

            $this->logger->info("exportar txt Controller: " , [$contenido]);

            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="descuento_haberes_' . $fechaSolicitud . '.txt"');
            echo $contenido;
            exit;

        } catch (Exception $e) {
            $this->logger->error("Error en exportarTxt: " . $e->getMessage());
            echo "Ocurrió un error al generar el archivo.";
            exit;
        }
    }

}
       