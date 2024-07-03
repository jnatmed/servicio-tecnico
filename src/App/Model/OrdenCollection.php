<?php 

namespace Paw\App\Models;


use Paw\Core\Model;

use Exception;
use PDOException;


class OrdenCollection extends Model
{

    private $nro_orden;

    public function __construct()
    {
        $this->nro_orden = 1;
    }
}