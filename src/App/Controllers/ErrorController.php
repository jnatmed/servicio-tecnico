<?php

namespace Paw\App\Controllers;

use Paw\Core\Controller;

class ErrorController extends Controller
{

    public function __construct(){

        parent::__construct();

    }
    
    public function notFound() {
        http_response_code(404);
        $titulo = 'Page Not Found';
        $main = 'Page Not Found';
        view('errors/not-found.view');
    }
    
    public function internalError() {
        http_response_code(500);
        $titulo = 'Internal Error';
        $main = 'Internal Server Error';
        view('errors/internal-error.view');
    }

    public function forbidden()
    {
        http_response_code(403);
        $titulo = 'Acceso Denegado';
        $main = 'No tenés permisos para acceder a esta sección.';
        view('errors/forbidden.view');
    }    
}