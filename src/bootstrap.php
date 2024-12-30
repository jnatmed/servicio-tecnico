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
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();


/**
 * 6) REQUEST
 * inicializo request 
 */
$request = new Request;


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
$router->get('/orden-de-trabajo/nuevo', 'OrdenController@new');
$router->post('/orden-de-trabajo/nuevo', 'OrdenController@new');
$router->get('/orden-de-trabajo/ver', 'OrdenController@show');
$router->get('/orden-de-trabajo/listar', 'OrdenController@listar');
$router->get('/ordenes', 'OrdenController@listar');
// $router->get('/', 'ModulosController@viewModulos');
$router->get('/', 'OrdenController@listar');
$router->get('/orden-de-trabajo/editar', 'OrdenController@edit');
$router->post('/orden-de-trabajo/editar', 'OrdenController@edit');
$router->get('/orden-de-trabajo/eliminar', 'OrdenController@delete');
$router->get('/orden-de-trabajo/descargar', 'OrdenController@download');
$router->get('/orden-de-trabajo/actualizar_estado', 'OrdenController@actualizarEstado');

$router->get('/minuta/new', 'MinutaController@new');
$router->get('/minutas/listar', 'MinutaController@listar');

$router->get('/','PageController@home');

/**
 * 9.1) Logeo de usuario
 */
$router->get('/user/login', 'UserController@login');
$router->post('/user/login', 'UserController@login');
$router->get('/user/logout', 'UserController@logout');
$router->get('/user/register', 'UserController@register');
$router->post('/user/register', 'UserController@register');
$router->get('/user/ver-perfil', 'UserController@verPerfil');
$router->get('/auth/google/callback', 'UserController@callback');

$router->get('/enviar-mail', 'UserController@enviarMail');


/**
 * 10) Datos de los internos trabajadores
 */
 $router->get('/interno', 'InternoController@datosInternos');
 $router->get('/talleres/ver_asignaciones', 'InternoController@verInternosAsignados');
 // /interno?id=1