<?php

namespace Paw\Core;

use Paw\Core\Model; 
use Paw\Core\Database\QueryBuilder;
use Paw\Core\Traits\Loggable;

class Controller 
{
    public string $viewsDir;

    public array $menu;

    public ?string $modelName = null;   
    protected $model;
    use Loggable;
    public $qb;
    public $request;

    public function __construct(){
        
        global $connection, $log;        

        $this->viewsDir = __DIR__ . '/../App/views/';

        $this->menu = [
            [
                'href' => '/',
                'name' => 'CARGAR NUEVA ORDEN DE TRABAJO'
            ]
        ];

        $this->qb = new QueryBuilder($connection, $log);
        $this->request = new Request();

        if(!is_null($this->modelName)){
            $model = new $this->modelName;
            $model->setQueryBuilder($this->qb);
            $model->setLogger($log); // todos los modelos tienen q ser logeables
            $this->setModel($model);
        }
        
    }

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function getQb(){
        return $this->qb;
    }


}