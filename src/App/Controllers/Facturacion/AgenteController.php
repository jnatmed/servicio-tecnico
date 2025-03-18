<?php 

namespace Paw\App\Controllers\Facturacion;

use Paw\Core\Controller;
use Paw\Core\Traits\Loggable;
use Paw\App\Controllers\UserController;

use Exception;
use InvalidArgumentException;

use Paw\App\Models\Agente;
use Paw\App\Models\AgentesCollection;

class AgenteController extends Controller
{   
    public ?string $modelName = AgentesCollection::class; 
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

        $log->debug("Datos modelo: " , [$this->model]);

        $log->info("info __construct: this->menu",  [$this->menu]);
        $this->menu = $this->usuario->adjustMenuForSession($this->menu);        

        $log->info("this->menu: ", [$this->menu]);
    }

    public function new()
    {
        global $log;
    
        if($this->request->method() == 'GET'){
            // Renderizar la vista de alta de Agente
            view('facturacion/agentes/agente.new', array_merge(

                $this->menu
            ));
        }elseif ($this->request->method() == 'POST') {
            try {
                // Obtener datos del formulario
                $rawData = [
                    'credencial' => $this->request->get('credencial'),
                    'nombre' => $this->request->get('nombre'),
                    'apellido' => $this->request->get('apellido'),
                    'cuil' => $this->request->get('cuil'),
                    'dependencia' => $this->request->get('dependencia'),
                    'estado_agente' => $this->request->get('estado_agente'),
                ];
        
                // 🔥 Limpiar los datos antes de pasarlos a Agente
                $data = $this->sanitize($rawData);
        
                // Intentar crear el objeto Agente (validación ocurre en el constructor)
                $agente = new Agente($data);
        
                // Insertar en la base de datos
                [$insertedId, $insertSuccess] = $this->model->add($agente);
    
                // Registrar la operación
                if ($insertSuccess) {
                    $log->info("Agente creado exitosamente.", ['id' => $insertedId, 'status' => $insertSuccess]);
                    view('facturacion/agentes/agente.success', array_merge(
                        ['id' => $insertedId],
                        $this->menu
                    ));
                } else {
                    $log->warning("No se pudo insertar el Agente en la base de datos.");
                }
        
            } catch (InvalidArgumentException $e) {
                // Capturar errores de validación
                $log->error("Error de validación al crear Agente: " . $e->getMessage());
            } catch (Exception $e) {
                // Capturar otros errores
                $log->error("Error inesperado al crear Agente: " . $e->getMessage());
            }
    
        }
    }


    public function getAgentes()
    {
        $searchItem = $this->request->get('search') ?? '';
        $page = $this->request->get('page') ?? 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
    
        // 🔥 Log inicial con los parámetros recibidos
        $this->logger->info("getAgentes() llamado", [
            'search' => $searchItem,
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ]);
    
        try {
            // Obtener agentes paginados
            $listaAgentes = $this->model->getAgentesPaginated($limit, $offset, $searchItem);
            $totalAgentes = $this->model->countAgentes($searchItem);
    
            // 🔥 Log después de obtener los datos
            $this->logger->info("Datos obtenidos de la BD", [
                'cantidad' => count($listaAgentes),
                'total' => $totalAgentes
            ]);
    
            // 🔥 Verificar si la solicitud es AJAX
            if ($this->request->isAjax()) {
                $this->logger->info("Solicitud detectada como AJAX. Enviando JSON.");
    
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'agentes' => $listaAgentes,
                    'total' => $totalAgentes,
                    'limit' => $limit,
                    'currentPage' => $page,
                    'search' => $searchItem
                ]);
    
                exit; // 🔥 IMPORTANTE: Detener la ejecución después de enviar JSON
            }
    
            // 🔥 Log antes de renderizar la vista
            $this->logger->info("Solicitud detectada como vista normal. Renderizando HTML.");
    
            return view('facturacion/agentes/agente.listado', array_merge(
                ['agentes' => $listaAgentes, 'total' => $totalAgentes, 'limit' => $limit, 'currentPage' => $page, 'search' => $searchItem],
                $this->menu
            ));
    
        } catch (Exception $e) {
            $this->logger->error("Error en getAgentes()", ['error' => $e->getMessage()]);
    
            if ($this->request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
    
            return view('facturacion/agentes/agente.listado', ['error' => $e->getMessage()]);
        }
    }
    
    
    
    

    public function ver()
    {
        if($this->request->get('id_producto') !== NULL) 
        {
            $id = $this->request->get('id_producto');
            $this->logger->info("id_producto: ", [$id]);
            $detalleProducto = $this->model->getDetalleProducto($id);

            view('facturacion/productos/detalle', array_merge(
                ['producto' => $detalleProducto],
                $this->menu
            ));
        }else{
            $this->logger->error("Error al obtener el id_producto");
            $detalleProducto = NULL;
        }
    }

}
