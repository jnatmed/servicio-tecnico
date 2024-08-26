<?php 

namespace Paw\App\Controllers;

use Exception;
use Paw\Core\Controller;
use Paw\App\Controllers\UserController;
use Paw\App\Models\OrdenCollection;
use Paw\Core\Traits\Loggable;
use Paw\App\Utils\Uploader;

class ModulosController extends Controller
{
    public function viewModulos()
    {
        view('modulos.view');
    }
}