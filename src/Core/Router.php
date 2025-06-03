<?php

namespace Paw\Core;

use Exception;
use Paw\Core\Request;
use Paw\Core\Exceptions\RouteNotFoundException;
use Paw\Core\Exceptions\ForbiddenException;
use Paw\Core\Traits\Loggable;

Class Router 
{
    use Loggable;

    public array $routes= [
        "GET" => [],
        "POST" => [],
        "DELETE" => [],
    ];

    protected array $protectedRoutes = []; // path => ['roles' => []]

    public string $notFound = 'not_found';
    public string $internalError = 'internal_error';    
    public string $forbidden = 'forbidden';

    public function __construct(){
        $this->get($this->notFound, 'ErrorController@notFound');
        $this->get($this->internalError, 'ErrorController@internalError');
        $this->get($this->forbidden, 'ErrorController@forbidden');
    }

    public function loadRoutes($path, $action, $method = "GET", $options = []) 
    {
        $this->routes[$method][$path] = $action;

        if (!empty($options['auth'])) {
            $this->protectedRoutes[$path] = [
                'roles' => $options['roles'] ?? []
            ];
        }
    }

    public function get($path, $action, $options = []) {
        $this->loadRoutes($path, $action, "GET", $options);
    }

    public function post($path, $action, $options = []) {
        $this->loadRoutes($path, $action, "POST", $options);
    }

    public function delete($path, $action, $options = []) {
        $this->loadRoutes($path, $action, "DELETE", $options);
    }

    public function exists($path, $method) {
        return array_key_exists($path, $this->routes[$method]);
    }

    public function getController($path, $http_method)
    {
            
        if(!$this->exists($path, $http_method)){
            throw new RouteNotFoundException("No existe ruta para este Path");
        }

        return explode('@', $this->routes[$http_method][$path]);
    }

    public function call($controller, $method, $allowedRoles = []) {
        $controller = "Paw\\App\\Controllers\\{$controller}";
        if (!class_exists($controller)) {
            throw new Exception("Controller '{$controller}' no encontrado.");
        }
        
        if ($allowedRoles !== []){
            $this->logger->info("📤✅📤✅ Seteando Controller con rolesPermitidos", [$controller, $allowedRoles]);
            $objController = new $controller($allowedRoles);
        }else{
            $this->logger->info("📤 Seteando Controller SIN rolesPermitidos", [$controller]);
            $objController = new $controller;
        }
        

        // $this->logger->info("Calling objController objController, model ", [$objController->modelName, $objController]);
        if (method_exists($objController, 'setLogger')) {
            $objController->setLogger($this->logger);
        } else {
            $this->logger->warning("El controlador {$controller} no tiene el método setLogger.");
        }
    
        if (!method_exists($objController, $method)) {
            throw new Exception("Método '{$method}' no encontrado en el controlador '{$controller}'.");
        }
    
        $objController->$method();
    }

    public function direct(Request $request)
    {
        $alreadyCalled = false; 

        try {
            list($path, $http_method) = $request->route();

            $session = new Session();

            // $this->logger->info("protectedRoutes: ", [$this->protectedRoutes]);
            $this->logger->info("path: ", [$path]);
            $this->logger->info("session: ", [$_SESSION]);

            if (isset($this->protectedRoutes[$path])) {
                if (!$session->isLoggedIn()) {
                    $this->logger->warning("🔐 Acceso no autenticado bloqueado", [
                        "Path" => $path,
                        "Method" => $http_method
                    ]);

                    if ($request->isAjax()) {
                        $this->logger->info("🔐 Acceso no autenticado bloqueado por AJAX");

                        header('Content-Type: application/json', true, 401);
                        echo json_encode([
                            'success' => false,
                            'error' => '401 - No autenticado',
                            'message' => 'Debes iniciar sesión para acceder a este recurso.'
                        ]);
                        exit;
                    }

                    header('Location: /user/login');
                    exit;
                }

                $allowedRoles = $this->protectedRoutes[$path]['roles'] ?? [];

                if (!empty($allowedRoles)) {
                    $userRole = $session->get('usuario_rol');

                    if (!in_array(ALL, $allowedRoles) && !in_array($userRole, $allowedRoles)) {
                        $this->logger->warning("🚫 Acceso denegado por rol", [
                            "Path" => $path,
                            "RolUsuario" => $userRole,
                            "RolesPermitidos" => $allowedRoles
                        ]);

                        if ($request->isAjax()) {
                            $this->logger->warning("🚫 Acceso denegado por rol mediante AJAX");
                            header('Content-Type: application/json');
                            http_response_code(403);
                            echo json_encode([
                                'success' => false, 
                                'error' => '403 - Acceso denegado para el rol ' . $userRole
                            ]);
                            exit;
                        } else {
                            throw new ForbiddenException("403 - Acceso denegado al recurso", 403);
                        }
                    }

                    $this->logger->info("✅ Acceso permitido", [
                        "Path" => $path,
                        "Usuario" => $session->get('usuario'),
                        "Rol" => $userRole
                    ]);
                } else {
                    $this->logger->info("🔐 Ruta protegida sin restricción de roles", [
                        "Path" => $path
                    ]);
                }
            }

            list($controller, $method) = $this->getController($path, $http_method);

            $this->logger->info("Status Code: 200", [
                "Path" => $path,
                "Controller" => $controller,
                "Method" => $method
            ]);
            
            if (isset($allowedRoles) && $allowedRoles !== null) {
                $this->call($controller, $method, $allowedRoles);
            } else {
                $this->call($controller, $method);
            }
            $alreadyCalled = true;

        } catch (ForbiddenException $e) {
            $this->logger->error("Status Code: 403 - Forbidden", [
                "ERROR" => $e->getMessage()
            ]);

            if ($request->isAjax()) {
                header('Content-Type: application/json', true, 403);
                echo json_encode([
                    'success' => false,
                    'error' => '403 - Acceso denegado',
                    'message' => $e->getMessage()
                ]);
                exit;
            }

            list($controller, $method) = $this->getController($this->forbidden, "GET");
            if (isset($allowedRoles) && $allowedRoles !== null) {
                $this->call($controller, $method, $allowedRoles);
            } else {
                $this->call($controller, $method);
            }
            $alreadyCalled = true;

        } catch (RouteNotFoundException $e) {
            $this->logger->error("Status Code: 404 - Route Not Found", [
                "ERROR" => [$path, $http_method]
            ]);

            if ($request->isAjax()) {
                header('Content-Type: application/json', true, 404);
                echo json_encode([
                    'success' => false,
                    'error' => '404 - Ruta no encontrada',
                    'message' => 'No se encontró una ruta para este recurso.'
                ]);
                exit;
            }

            list($controller, $method) = $this->getController($this->notFound, "GET");
            if (isset($allowedRoles) && $allowedRoles !== null) {
                $this->call($controller, $method, $allowedRoles);
            } else {
                $this->call($controller, $method);
            }
            $alreadyCalled = true;

        } catch (Exception $e) {
            $this->logger->error("Status Code: 500 - Internal Server Error", [
                "ERROR" => $e
            ]);

            if ($request->isAjax()) {
                header('Content-Type: application/json', true, 500);
                echo json_encode([
                    'success' => false,
                    'error' => '500 - Error interno del servidor',
                    'message' => $e->getMessage()
                ]);
                exit;
            }

            list($controller, $method) = $this->getController($this->internalError, "GET");
            if (isset($allowedRoles) && $allowedRoles !== null) {
                $this->call($controller, $method, $allowedRoles);
            } else {
                $this->call($controller, $method);
            }
            $alreadyCalled = true;
        } finally {
            if (!$alreadyCalled) {
                if (isset($allowedRoles) && $allowedRoles !== null) {
                    $this->call($controller, $method, $allowedRoles);
                } else {
                    $this->call($controller, $method);
                }
            }
        }
    }

}