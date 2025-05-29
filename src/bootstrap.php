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
 * Definicion de constantes que seran usadas en la deficion 
 * de rutas y roles en el metodo Router->direct
 */
require_once __DIR__ . '/core/roles_constants.php';

// 9) RUTAS: se cargan desde archivo PHP que retorna el array
$permisos_definidos = require __DIR__ . '/routes_definicion.php';

foreach ($permisos_definidos as $ruta) {
    $methods = (array) ($ruta['method'] ?? 'get');
    $path = $ruta['path'];
    $controller = $ruta['controller'];
    $permisos = [
        'auth' => $ruta['auth'] ?? true,
        'roles' => $ruta['roles'] ?? [],
    ];

    foreach ($methods as $method) {
        $method = strtolower($method);
        if (method_exists($router, $method)) {
            $router->{$method}($path, $controller, $permisos);
            // $log->info("✅ Ruta cargada: [$method] $path → $controller", $permisos);
        } else {
            $log->error("⚠️ Método HTTP no soportado: {$method} en ruta {$path}");
        }
    }
}

$log->info("✅ Todas las rutas fueron registradas correctamente.");

