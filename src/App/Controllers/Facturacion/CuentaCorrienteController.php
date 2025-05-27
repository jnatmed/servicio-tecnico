<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;

use Paw\App\Models\CuentaCorrienteCollection;
use Paw\App\Models\AgentesCollection;
use Exception;

class CuentaCorrienteController extends Controller
{
    
    use Loggable;

    public $usuario;

    public ?string $modelName = CuentaCorrienteCollection::class; 

    public function __construct()
    {
        global $log;
         
       
        parent::__construct();     
        
        $this->usuario = new UserController();
        $this->usuario->setLogger($log);

        // $log->info("info __construct: this->menu",  [$this->menu]);
        $this->menu = $this->claseMenu->getMenuFiltrado($this->usuario->getRolUsuario(), $this->usuario->haySession());        

        // $log->info("this->menu: ", [$this->menu]);
    }

    public function verCuentaCorrienteAgente()
    {
        /**
         *  Recepcion de datos para consultar la cuenta corriente
         *  del agente.
         */
        $agenteId = $this->request->sanitize(
            $this->request->get('agente_id')
        );

        $agenteCollection = new AgentesCollection($this->qb, $this->logger);
        $agente = $agenteCollection->getAgentes(null, $agenteId);

        $movimientos = $this->model->obtenerExtractoConSaldo($agenteId);
        $saldo = $this->model->obtenerSaldoActual($agenteId);

        $this->logger->info("agente recuperado: ", [$agente]);
        $this->logger->info("cuentaCorriente: ", [$movimientos]);

        return view('facturacion/agentes/cuentaCorriente_agente.view', array_merge(
            ["agente" => $agente[0] ?? []],
            ['movimientos' => $movimientos],
            ['saldo' => $saldo],
            $this->menu
        ));

        
    }

    public function generarReporteHaberes()
    {
        $periodo = $this->request->get('periodo') ?? date('Y-m-01'); // Por defecto el mes actual
        $this->logger->info("🧾 Generando reporte de descuentos de haberes para el periodo", [$periodo]);
    
        try {
            $cuentaCorriente = new CuentaCorrienteCollection($this->qb, $this->logger);
            $reporte = $cuentaCorriente->procesarReporteDescuentosHaberes($periodo);
    
            // Mostrarlo en vista o exportarlo a TXT
            view('facturacion/cuentaCorriente/reporte_haberes.view', array_merge(
                ['reporte' => $reporte, 'periodo' => $periodo],
                $this->menu
            ));
    
        } catch (Exception $e) {
            $this->logger->error("❌ Error al generar el reporte de haberes", ['error' => $e->getMessage()]);
            echo "Error al generar el reporte: " . $e->getMessage();
        }
    }
    

    public function exportarPdf()
    {
        try {
            $idAgente = $this->request->sanitize($this->request->get('agente_id'));
    
            // Validar ID
            if (empty($idAgente) || !is_numeric($idAgente)) {
                http_response_code(400);
                echo "ID de agente inválido o no especificado.";
                return;
            }
    
            // Cargar dependencias
            $agenteCollection = new AgentesCollection($this->qb, $this->logger);
            $cuentaCorriente = new CuentaCorrienteCollection($this->qb, $this->logger);
    
            $this->logger->info("Intentando exportar PDF para agente_id:", [$idAgente]);
    
            // Buscar agente
            $agente = $agenteCollection->getAgentes(null, $idAgente)[0] ?? null;
    
            if (!$agente) {
                http_response_code(404);
                echo "Agente no encontrado.";
                return;
            }
    
            // Cargar movimientos y saldo
            $movimientos = $cuentaCorriente->obtenerExtractoConSaldo($idAgente);
            $saldo = $cuentaCorriente->obtenerSaldoActual($idAgente);
    
            // Renderizar HTML de la vista
            ob_start();
            $html = return_view('facturacion/agentes/cuentaCorriente_pdf.view', [
                'agente' => $agente,
                'movimientos' => $movimientos,
                'saldo' => $saldo
            ]);
    
            // Log opcional para depurar contenido
            $this->logger->info("HTML generado para PDF", [$html]);
    
            // Generar PDF
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
    
            // Preparar salida
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="cuenta_corriente_agente_' . $idAgente . '.pdf"');
            echo $dompdf->output();
        } catch (\Exception $e) {
            $this->logger->error("Error exportando PDF: " . $e->getMessage());
            http_response_code(500);
            echo "Error al generar el PDF.";
        }
    }
    


}