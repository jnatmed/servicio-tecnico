<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;

use Paw\App\Models\CuentaCorrienteCollection;
use Paw\App\Models\AgentesCollection;

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

        $log->info("info __construct: this->menu",  [$this->menu]);
        $this->menu = $this->usuario->adjustMenuForSession($this->menu);        

        $log->info("this->menu: ", [$this->menu]);
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

        $this->logger->info("agente: ", [$agente]);
        $this->logger->info("cuentaCorriente: ", [$movimientos]);

        return view('facturacion/agentes/cuentaCorriente_agente.view', array_merge(
            ["agente" => $agente[0] ?? []],
            ['movimientos' => $movimientos],
            ['saldo' => $saldo],
            $this->menu
        ));

        
    }

    public function exportarPdf()
    {
        $idAgente = $this->request->sanitize(
            $this->request->get('agente_id')
        );
        $agenteCollection = new AgentesCollection($this->qb, $this->logger);
        $cuentaCorriente = new CuentaCorrienteCollection($this->qb, $this->logger);

        $agente = $agenteCollection->getAgentes(null, $idAgente)[0] ?? null;

        if (!$agente) {
            echo "Agente no encontrado";
            exit;
        }

        $movimientos = $cuentaCorriente->obtenerExtractoConSaldo($idAgente);
        $saldo = $cuentaCorriente->obtenerSaldoActual($idAgente);

        // Renderización simple con output buffering
        ob_start();
        $html = return_view('facturacion/agentes/cuentaCorriente_pdf.view', [
            'agente' => $agente,
            'movimientos' => $movimientos,
            'saldo' => $saldo
        ]);

        $this->logger->info("HTML generado para PDF:", [$html]);

        // Generar PDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream("cuenta_corriente_agente_{$idAgente}.pdf", ["Attachment" => false]);
    }


}