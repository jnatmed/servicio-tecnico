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

/**
 * 9) RUTAS
 * Aca van los enrutadores
 */
$router->get('/orden-de-trabajo/nuevo', 'OrdenController@new', [
    'auth' => true,
    'roles' => ['administrador', 'oficina']    
]);
$router->post('/orden-de-trabajo/nuevo', 'OrdenController@new', [
    'auth' => true,
    'roles' => ['administrador', 'oficina']    
]);
$router->get('/orden-de-trabajo/ver', 'OrdenController@show', [
    'auth' => true,
    'roles' => ['administrador', 'oficina']    
]);
$router->get('/orden-de-trabajo/listar', 'OrdenController@listar', [
    'auth' => true,
    'roles' => ['administrador', 'tecnica']    
]);
$router->get('/ordenes', 'OrdenController@listar', [
    'auth' => true,
    'roles' => ['administrador', 'tecnica']    
]);

$router->get('/', 'OrdenController@listar', [
    'auth' => true,
    'roles' => ['administrador', 'tecnica', 'oficina']    
]);
$router->get('/orden-de-trabajo/editar', 'OrdenController@edit', [
    'auth' => true,
    'roles' => ['administrador', 'tecnica']    
]);
$router->post('/orden-de-trabajo/editar', 'OrdenController@edit');
$router->get('/orden-de-trabajo/eliminar', 'OrdenController@delete');
$router->get('/orden-de-trabajo/descargar', 'OrdenController@download');
$router->get('/orden-de-trabajo/actualizar_estado', 'OrdenController@actualizarEstado');

/**
 *  MINUTAS DE REUNION
 */

$router->get('/minuta/new', 'MinutaController@new');
$router->post('/minuta/new', 'MinutaController@new');
$router->get('/minuta/ver', 'MinutaController@ver');
$router->get('/minutas/listar', 'MinutaController@listar');

$router->get('/','PageController@home');

/**
 * 9.1) Logueo de usuario
 */
$router->get('/user/login', 'UserController@login');
$router->post('/user/login', 'UserController@login');
$router->get('/user/logout', 'UserController@logout', [
    'auth' => true,
    'roles' => ['administrador']    
]);
$router->get('/user/register', 'UserController@register');
$router->post('/user/register', 'UserController@register');

$router->get('/user/get_listado', 'UserController@getListado', ['auth' => true, 'roles' => ['administrador']]);
$router->post('/user/actualizar_rol', 'UserController@actualizarRol', ['auth' => true, 'roles' => ['administrador']]);
$router->post('/user/confirmar_solicitud_dependencia', 'UserController@confirmarSolicitudDependencia', ['auth' => true, 'roles' => ['administrador']]);
$router->post('/user/rechazar_solicitud_dependencia', 'UserController@rechazarSolicitudDependencia', ['auth' => true, 'roles' => ['administrador']]);

$router->get('/user/ver-perfil', 'UserController@verPerfil', ['auth' => true, 'roles' => ['*']]); 

$router->post('/user/asignar-dependencia', 'UserController@asignarDestino', ['auth' => true, 'roles' => ['administrador']]);
$router->get('/auth/google/callback', 'UserController@callback', ['auth' => true, 'roles' => ['administrador']]);

$router->get('/enviar-mail', 'UserController@enviarMail');

/**
 * 10) Datos de los internos trabajadores
 */
//  $router->get('/interno', 'InternoController@datosInternos');
//  $router->get('/internos/ver_internos', 'InternoController@verInternosTrabajadores');
//  $router->get('/taller/ver_asignaciones', 'InternoController@verInternosAsignados');
//  $router->get('/talleres/ver_talleres', 'TalleresController@verTalleres');
 // /interno?id=1

 /**
  * 11) Facturacion
  */
  $router->get('/facturacion/new', 'Facturacion\\FacturacionController@alta', ['auth' => true, 'roles' => ['administrador', 'punto_venta']]);
  $router->post('/facturacion/new', 'Facturacion\\FacturacionController@alta', ['auth' => true, 'roles' => ['administrador', 'punto_venta']]);
  $router->post('/facturacion/listar', 'Facturacion\\FacturacionController@listar', ['auth' => true, 'roles' => ['administrador', 'punto_venta', 'codigo_608', 'planificacion']]);
  $router->get('/facturacion/ver', 'Facturacion\\FacturacionController@ver', ['auth' => true, 'roles' => ['administrador', 'punto_venta', 'codigo_608', 'planificacion']]);
  $router->delete('/facturacion/eliminar', 'Facturacion\\FacturacionController@eliminarFactura', ['auth' => true, 'roles' => ['administrador', 'jefatura_ventas']]);
// Ver Numeracion Facturas 
  $router->get('/facturacion/numerador/lista', 'Facturacion\\FacturacionController@listarNumerador', [
        'auth' => true, 'roles' => ['administrador', 'jefatura_ventas']
    ]); 
// Ver Numeracion de facturas peticion JSON
  $router->get('/facturacion/numerador/solicitudes/pendientes/json', 'Facturacion\\FacturacionController@listarNumerador', [
        'auth' => true, 'roles' => ['administrador', 'jefatura_ventas']
    ]); 
// Aceptar Solicitud de facturacion 
  $router->post('/facturacion/numerador/solicitudes/aceptar', 'Facturacion\\FacturacionController@aceptarSolicitud', [
        'auth' => true, 'roles' => ['administrador', 'jefatura_ventas']
    ]); 
// Rechazar Solicitud de facturacion 
  $router->post('/facturacion/numerador/solicitudes/rechazar', 'Facturacion\\FacturacionController@rechazarSolicitud', [
        'auth' => true, 'roles' => ['administrador', 'jefatura_ventas']
    ]); 
  $router->get('/facturacion/listar', 'Facturacion\\FacturacionController@listar', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas', 'punto_venta']
    ]); 
  $router->get('/facturacion/api_get_agentes', 'Facturacion\\AgenteController@getAgentes', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas', 'punto_venta']
    ]); 
  $router->get('/facturacion/api_get_productos', 'Facturacion\\FacturacionController@getProductos', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas', 'punto_venta']
    ]);
  $router->get('/facturacion/api_get_precio_producto', 'Facturacion\\FacturacionController@getPreciosProductos', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas', 'punto_venta']
    ]);
  $router->post('/facturacion/subir-comprobante', 'Facturacion\\FacturacionController@subirComprobante', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas', 'punto_venta']
    ]);
  $router->get('/facturacion/ver-comprobante', 'Facturacion\\FacturacionController@verComprobante', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas', 'punto_venta']
    ]);

  /**
   * 12) Productos
   */
  $router->get('/facturacion/productos/listado', 'Facturacion\\ProductoController@listar', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas', 'punto_venta', 'planificacion_comercial']
    ]);
  $router->get('/facturacion/productos/ver', 'Facturacion\\ProductoController@ver', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas', 'punto_venta', 'planificacion_comercial']
    ]);
  $router->get('/facturacion/productos/ver_imagen', 'Facturacion\\ProductoController@verImgProducto', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas', 'punto_venta', 'planificacion_comercial']
    ]);
  $router->get('/facturacion/productos/agregar-precio', 'Facturacion\\ProductoController@agregarPrecio', [
        'auth' => true, 'roles' => ['administrador', 'planificacion_comercial']
    ]);
  $router->post('/facturacion/productos/agregar-precio', 'Facturacion\\ProductoController@agregarPrecio', [
        'auth' => true, 'roles' => ['administrador', 'planificacion_comercial']
    ]);
  $router->get('/facturacion/productos/editar', 'Facturacion\\ProductoController@editarProducto', [
        'auth' => true, 'roles' => ['administrador', 'planificacion_comercial']
    ]);
  $router->post('/facturacion/productos/editar', 'Facturacion\\ProductoController@editarProducto', [
        'auth' => true, 'roles' => ['administrador', 'planificacion_comercial']
    ]);
  $router->get('/facturacion/productos/eliminar', 'Facturacion\\ProductoController@eliminarProducto', [
        'auth' => true, 'roles' => ['administrador', 'planificacion_comercial']
    ]);
  $router->post('/facturacion/productos/informar-decomiso', 'Facturacion\\ProductoController@registrarDecomiso', [
        'auth' => true, 'roles' => ['administrador', 'punto_venta']
    ]);
  $router->get('/facturacion/productos/ver-comprobante', 'Facturacion\\ProductoController@verComprobanteDecomiso', [
        'auth' => true, 'roles' => ['administrador', 'punto_venta']
    ]);

/**
 * 13) Agentes
 *  */  

  $router->get('/facturacion/agentes/listado', 'Facturacion\\AgenteController@getAgentes', [
        'auth' => true, 'roles' => ['*']
    ]);
  $router->get('/facturacion/agentes/nuevo', 'Facturacion\\AgenteController@new', [
        'auth' => true, 'roles' => ['administrador']
    ]);
  $router->post('/facturacion/agentes/nuevo', 'Facturacion\\AgenteController@new', [
        'auth' => true, 'roles' => ['administrador']
    ]);
  $router->get('/facturacion/agente/ver', 'Facturacion\\CuentaCorrienteController@verCuentaCorrienteAgente', [
        'auth' => true, 'roles' => ['*']
    ]);

/**
 *  14) Cuotas
 */
// http://localhost:8080/facturacion/listar?con_comprobante=1

 $router->get('/facturacion/cuotas/listado', 'Facturacion\\CuotasController@listar', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas']
    ]);
 $router->post('/facturacion/cuotas/listado', 'Facturacion\\CuotasController@reporteAgrupado', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas']
    ]);
 $router->post('/facturacion/cuotas/aplicar-descuento-masivo', 'Facturacion\\CuotasController@aplicarDescuentoMasivo', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas']
    ]);
 $router->get('/facturacion/cuotas/exportar-txt', 'Facturacion\\CuotasController@exportarTxt', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas']
    ]);
 $router->get('/facturacion/cuotas/solicitudes-pendientes', 'Facturacion\\CuotasController@verSolicitudesPendientes', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas']
    ]);
 $router->post('/facturacion/cuotas/confirmar-descuentos', 'Facturacion\\CuotasController@confirmarDescuentos', [
        'auth' => true, 'roles' => ['administrador', 'codigo_608', 'jefatura_ventas']
    ]);

 /**
  * 15) Facturacion
  */
  $router->get('/cuenta-corriente/exportar-pdf', 'Facturacion\\CuentaCorrienteController@exportarPdf', [
        'auth' => true, 'roles' => ['*']
    ]);
 