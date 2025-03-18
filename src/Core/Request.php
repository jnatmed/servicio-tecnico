<?php

namespace Paw\Core;

class Request 
{
    private $segments = [];

    public function uri() 
    {
        return isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '/';
    }

    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    public function isAjax(): bool
    {
        error_log("isAjax() llamado. HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'NO DEFINIDO'));
    
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function getKeySession($key){
        return $_SESSION[$key] ?? null;
    }

    public function get($key)
    {
        return $_POST[$key] ?? $_GET[$key] ?? null;
    }

    public function all()
    {
        return $_POST;
    }
    public function getSegments($numeroSegmento)
    {
        $this->segments = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        return $this->segments[$numeroSegmento];
    }
        
    public function route()
    {
        return [
            $this->uri(),
            $this->method()
        ];
    }
}