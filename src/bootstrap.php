<?php

require __DIR__.'/../vendor/autoload.php';

// librerias de terceros
use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\StreamHandler;
use Dotenv\Dotenv;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\Extension\DebugExtension;

// librerias propias
use Paw\Core\Router;
use Paw\Core\Config;
use Paw\Core\Request;
use Paw\Core\Database\ConnectionBuilder;
use Paw\Core\Configs\WhoopsConfig;

/**
 * 1) DOTENV
 * configurando el dotenv - para las variables de entorno 
 */
$dotenv = Dotenv::createUnsafeImmutable(__DIR__.'/../');
$dotenv->load();

/**
 * 2) CONFIG
 * con las variables de entorno levantadas
 * inicializo la clase Config
 */
$config = new Config;


/**
 * 3) LOG
 * ahora configuro el logger
 */
$log = new Logger('informatica_log');
$handler = new StreamHandler(getenv('LOG_PATH'));
$handler->setLevel($config->get("LOG_LEVEL"));
$handler->setLevel(Level::Debug);
$log->pushHandler($handler);

/**
 * 4) BASE DE DATOS - ConnectionBuilder
 */
$connectionBuilder = new ConnectionBuilder;
$connectionBuilder->setLogger($log);
$connection = $connectionBuilder->make($config);

/**
 * 5) WHOOPS 
 * configuro el whoops para los errores del servidor
 */
$whoopsConfig = new WhoopsConfig();
$whoopsConfig->configure([
    'DB_PASSWORD', 
    'DB_USERNAME', 
    'DB_HOSTNAME',
]);

/**
 * 6) REQUEST
 * inicializo request 
 */
$request = new Request($log);


/**
 * 7) TWIG
 * Load template engine
 */
$templateDir = __DIR__ . $config->get('TEMPLATE_DIR');
$cacheDir = __DIR__ . $config->get('TEMPLATE_CACHE_DIR');

$loader = new FilesystemLoader($templateDir);

$twig = new Environment($loader, [
    'cache' => $cacheDir, 
    'debug' => true,
]);

$twig->addExtension(new DebugExtension());

$twig->addFilter(new \Twig\TwigFilter('format_estado', function ($estado) {
    // Reemplaza guiones bajos por espacios
    $estado = str_replace('_', ' ', $estado);

    // Lista de palabras que no se deben capitalizar
    $no_capitalize_words = ['de', 'ante', 'con', 'por', 'en', 'a', 'el', 'la', 'los', 'las', 'desde', 'hasta', 'para', 'entre', 'sobre'];

    // Capitaliza cada palabra importante
    $words = explode(' ', $estado);
    $formatted_words = [];

    foreach ($words as $index => $word) {
        $lowercase_word = strtolower($word);
        if ($index == 0 || $index == count($words) - 1 || !in_array($lowercase_word, $no_capitalize_words)) {
            $formatted_words[] = ucfirst($lowercase_word);
        } else {
            $formatted_words[] = $lowercase_word;
        }
    }

    return implode(' ', $formatted_words);
}));

/**
 * 8) ROUTER
 * inicializo router para luego agregarle las rutas
 */
$router = new Router;
$router->setLogger($log);
 $rutaJson = __DIR__ . '/permisos_routes_boostrap.json';
 $permisos_routes = json_decode(file_get_contents($rutaJson), true);

/**
 * 9) RUTAS
 * Aca van los enrutadores
 */
$router->get('/orden-de-trabajo/nuevo', 'OrdenController@new', 
    $permisos_routes['/orden-de-trabajo/nuevo']);
$router->post('/orden-de-trabajo/nuevo', 'OrdenController@new', 
    $permisos_routes['/orden-de-trabajo/nuevo']);
$router->get('/orden-de-trabajo/ver', 'OrdenController@show', 
    $permisos_routes['/orden-de-trabajo/ver']);
$router->get('/orden-de-trabajo/listar', 'OrdenController@listar', 
    $permisos_routes['/orden-de-trabajo/listar']);
$router->get('/ordenes', 'OrdenController@listar', 
    $permisos_routes['/ordenes']);
$router->get('/ordenes/home', 'OrdenController@listar', 
    $permisos_routes['/ordenes/home']);
$router->get('/orden-de-trabajo/editar', 'OrdenController@edit', 
    $permisos_routes['/orden-de-trabajo/editar']);
$router->post('/orden-de-trabajo/editar', 'OrdenController@edit',
    $permisos_routes['/orden-de-trabajo/editar']);
$router->get('/orden-de-trabajo/eliminar', 'OrdenController@delete',
    $permisos_routes['/orden-de-trabajo/eliminar']);
$router->get('/orden-de-trabajo/descargar', 'OrdenController@download',
    $permisos_routes['/orden-de-trabajo/descargar']);
$router->get('/orden-de-trabajo/actualizar_estado', 'OrdenController@actualizarEstado',
    $permisos_routes['/orden-de-trabajo/actualizar_estado']);

/**
 *  MINUTAS DE REUNION
 */

$router->get('/minuta/new', 'MinutaController@new');
$router->post('/minuta/new', 'MinutaController@new');
$router->get('/minuta/ver', 'MinutaController@ver');
$router->get('/minutas/listar', 'MinutaController@listar');

$router->get('/','PageController@home', [
    'auth' => true,
    'roles' => ['*']        
]);

/**
 * 9.1) Logueo de usuario
 */
$router->get('/user/login', 'UserController@login',
    $permisos_routes['/user/login']);
$router->post('/user/login', 'UserController@login',
    $permisos_routes['/user/login']);
$router->get('/user/logout', 'UserController@logout', 
    $permisos_routes['/user/logout']);
$router->get('/user/register', 'UserController@register', 
    $permisos_routes['/user/register']);
$router->post('/user/register', 'UserController@register',     
    $permisos_routes['/user/register']);
$router->get('/user/get_listado', 'UserController@getListado', 
    $permisos_routes['/user/get_listado']);
$router->post('/user/actualizar_rol', 'UserController@actualizarRol', 
    $permisos_routes['/user/actualizar_rol']);
$router->post('/user/confirmar_solicitud_dependencia', 'UserController@confirmarSolicitudDependencia', 
    $permisos_routes['/user/confirmar_solicitud_dependencia']);
$router->post('/user/rechazar_solicitud_dependencia', 'UserController@rechazarSolicitudDependencia', 
    $permisos_routes['/user/rechazar_solicitud_dependencia']);
$router->get('/user/ver-perfil', 'UserController@verPerfil', 
    $permisos_routes['/user/ver-perfil']);
$router->post('/user/asignar-dependencia', 'UserController@asignarDestino', 
    $permisos_routes['/user/asignar-dependencia']);

// $router->get('/auth/google/callback', 'UserController@callback', 
// ['auth' => true, 'roles' => ['administrador']]);

// $router->get('/enviar-mail', 'UserController@enviarMail');

/**
 * 10) Datos de los internos trabajadores
 */
//  $router->get('/interno', 'InternoController@datosInternos');
//  $router->get('/internos/ver_internos', 'InternoController@verInternosTrabajadores');
//  $router->get('/taller/ver_asignaciones', 'InternoController@verInternosAsignados');
//  $router->get('/talleres/ver_talleres', 'TalleresController@verTalleres');
 // /interno?id=1

 $log->info("permisos_routes: ", [$permisos_routes['/facturacion/listar']]);

 /**
  * 11) Facturacion
  */
  $router->get('/facturacion/new', 'Facturacion\\FacturacionController@alta', 
               $permisos_routes['/facturacion/new']);
  $router->post('/facturacion/new', 'Facturacion\\FacturacionController@alta', 
               $permisos_routes['/facturacion/new']);
  $router->get('/facturacion/listar', 'Facturacion\\FacturacionController@listar', 
               $permisos_routes['/facturacion/listar']); 
  $router->post('/facturacion/listar', 'Facturacion\\FacturacionController@listar', 
               $permisos_routes['/facturacion/listar']);

  $router->get('/facturacion/ver', 'Facturacion\\FacturacionController@ver', 
               $permisos_routes['/facturacion/ver']);

  $router->delete('/facturacion/eliminar', 'Facturacion\\FacturacionController@eliminarFactura', 
               $permisos_routes['/facturacion/eliminar']);
// Ver Numeracion Facturas 
  $router->get('/facturacion/numerador/lista', 'Facturacion\\FacturacionController@listarNumerador', 
               $permisos_routes['/facturacion/numerador/lista']);
// Ver Numeracion de facturas peticion JSON
  $router->get('/facturacion/numerador/solicitudes/pendientes/json', 'Facturacion\\FacturacionController@listarNumerador', 
               $permisos_routes['/facturacion/numerador/solicitudes/pendientes/json']);
// Aceptar Solicitud de facturacion 
  $router->post('/facturacion/numerador/solicitudes/aceptar', 'Facturacion\\FacturacionController@aceptarSolicitud', 
               $permisos_routes['/facturacion/numerador/solicitudes/aceptar']);
// Rechazar Solicitud de facturacion 
  $router->post('/facturacion/numerador/solicitudes/rechazar', 'Facturacion\\FacturacionController@rechazarSolicitud', 
               $permisos_routes['/facturacion/numerador/solicitudes/rechazar']); 

  $router->get('/facturacion/api_get_agentes', 'Facturacion\\AgenteController@getAgentes', 
               $permisos_routes['/facturacion/api_get_agentes']);
  $router->get('/facturacion/api_get_productos', 'Facturacion\\FacturacionController@getProductos', 
               $permisos_routes['/facturacion/api_get_productos']);
  $router->get('/facturacion/api_get_precio_producto', 'Facturacion\\FacturacionController@getPreciosProductos', 
               $permisos_routes['/facturacion/api_get_precio_producto']);
  $router->post('/facturacion/subir-comprobante', 'Facturacion\\FacturacionController@subirComprobante', 
               $permisos_routes['/facturacion/subir-comprobante']);
  $router->get('/facturacion/ver-comprobante', 'Facturacion\\FacturacionController@verComprobante', 
               $permisos_routes['/facturacion/ver-comprobante']);

  /**
   * 12) Productos
   */
  $router->get('/facturacion/productos/listado', 'Facturacion\\ProductoController@listar', 
              $permisos_routes['/facturacion/productos/listado']);
  $router->get('/facturacion/productos/ver', 'Facturacion\\ProductoController@ver', 
              $permisos_routes['/facturacion/productos/ver']);
  $router->get('/facturacion/productos/ver_imagen', 'Facturacion\\ProductoController@verImgProducto', 
              $permisos_routes['/facturacion/productos/ver_imagen']);
  $router->get('/facturacion/productos/agregar-precio', 'Facturacion\\ProductoController@agregarPrecio', 
              $permisos_routes['/facturacion/productos/agregar-precio']);
  $router->post('/facturacion/productos/agregar-precio', 'Facturacion\\ProductoController@agregarPrecio', 
              $permisos_routes['/facturacion/productos/agregar-precio']);
  $router->get('/facturacion/productos/editar', 'Facturacion\\ProductoController@editarProducto', 
              $permisos_routes['/facturacion/productos/editar']);
  $router->post('/facturacion/productos/editar', 'Facturacion\\ProductoController@editarProducto', 
              $permisos_routes['/facturacion/productos/editar']);
  $router->get('/facturacion/productos/eliminar', 'Facturacion\\ProductoController@eliminarProducto', 
              $permisos_routes['/facturacion/productos/eliminar']);
  $router->post('/facturacion/productos/informar-decomiso', 'Facturacion\\ProductoController@registrarDecomiso',    
              $permisos_routes['/facturacion/productos/informar-decomiso']);
  $router->get('/facturacion/productos/ver-comprobante', 'Facturacion\\ProductoController@verComprobanteDecomiso', 
              $permisos_routes['/facturacion/productos/ver-comprobante']);

/**
 * 13) Agentes
 *  */  

  $router->get('/facturacion/agentes/listado', 'Facturacion\\AgenteController@getAgentes', 
        $permisos_routes['/facturacion/agentes/listado']);
  $router->get('/facturacion/agentes/nuevo', 'Facturacion\\AgenteController@new', 
        $permisos_routes['/facturacion/agentes/nuevo']);
  $router->post('/facturacion/agentes/nuevo', 'Facturacion\\AgenteController@new', 
        $permisos_routes['/facturacion/agentes/nuevo']);
  $router->get('/facturacion/agente/ver', 'Facturacion\\CuentaCorrienteController@verCuentaCorrienteAgente', 
        $permisos_routes['/facturacion/agente/ver']);

/**
 *  14) Cuotas
 */
// http://localhost:8080/facturacion/listar?con_comprobante=1

 $router->get('/facturacion/cuotas/listado', 'Facturacion\\CuotasController@listar', 
        $permisos_routes['/facturacion/cuotas/listado']);
 $router->post('/facturacion/cuotas/listado', 'Facturacion\\CuotasController@reporteAgrupado', 
        $permisos_routes['/facturacion/cuotas/listado']);
 $router->post('/facturacion/cuotas/aplicar-descuento-masivo', 'Facturacion\\CuotasController@aplicarDescuentoMasivo', 
        $permisos_routes['/facturacion/cuotas/aplicar-descuento-masivo']);
 $router->get('/facturacion/cuotas/exportar-txt', 'Facturacion\\CuotasController@exportarTxt', 
        $permisos_routes['/facturacion/cuotas/exportar-txt']);
 $router->get('/facturacion/cuotas/solicitudes-pendientes', 'Facturacion\\CuotasController@verSolicitudesPendientes', 
        $permisos_routes['/facturacion/cuotas/solicitudes-pendientes']);
 $router->post('/facturacion/cuotas/confirmar-descuentos', 'Facturacion\\CuotasController@confirmarDescuentos', 
        $permisos_routes['/facturacion/cuotas/confirmar-descuentos']);

 /**
  * 15) Facturacion
  */
  $router->get('/cuenta-corriente/exportar-pdf', 'Facturacion\\CuentaCorrienteController@exportarPdf', 
        $permisos_routes['/cuenta-corriente/exportar-pdf']);
 