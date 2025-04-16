<?php 

namespace Paw\App\Controllers;

use Exception;
use Paw\Core\Controller;
use Paw\App\Models\FacturasCollection;

class ModulosController extends Controller
{
    public function viewModulos()
    {
        try {
            $facturasModel = new FacturasCollection($this->qb, $this->logger);
    
            $cantidadSinComprobante = $facturasModel->contarSinComprobanteAdjunto();
    
            $this->logger->info("cantidadSinComprobante: ", [$cantidadSinComprobante]);

            view('modulos.view', [
                'cantidad_sin_comprobante' => $cantidadSinComprobante
            ]);
        } catch (Exception $e) {
            $this->logger->error("Error al cargar vista de módulos: " . $e->getMessage());
            view('modulos.view', ['cantidad_sin_comprobante' => 0]);
        }
    }
    
}