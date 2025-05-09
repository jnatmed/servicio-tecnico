<?php

namespace Paw\Core;

class Request 
{
    private $segments = [];

    public function uri() 
    {
        return isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '/';
    }
    public function getHeader($key)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? null;
    }

    public function file($key)
    {
        return $_FILES[$key] ?? null;
    }

    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    public function isAjax()
    {
        return (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            isset($_SERVER['HTTP_ACCEPT']) &&
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        );
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

    public function sanitize($data)
    {
        if (is_array($data)) {
            $sanitizedData = [];
            foreach ($data as $key => $value) {
                $sanitizedData[$key] = $this->sanitize($value);
            }
            return $sanitizedData;
        }
    
        if (is_string($data)) {
            return trim(htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8'));
        }
    
        if (is_int($data) || is_float($data)) {
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }
    
        return $data; // Otros tipos como boolean, null, etc.
    }
      
}