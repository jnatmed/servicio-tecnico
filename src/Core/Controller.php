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
            'menu' => [
                [
                    'href' => '/orden-de-trabajo/nuevo',
                    'name' => 'NUEVA ORDEN DE TRABAJO'
                ],
                [
                    'href' => '/minuta/new',
                    'name' => 'NUEVA MINUTA'
                ],
                [
                    'href' => '/orden-de-trabajo/listar',
                    'name' => 'VER ORDENES DE TRABAJO'
                ],
                [
                    'href' => '/user/login',
                    'name' => 'LOGIN'
                ],
                [
                    'href' => '/user/logout',
                    'name' => 'SALIR'
                ],                
                [
                    'href' => '/user/register',
                    'name' => 'REGISTRO'
                ],
                [
                    'href' => '/user/ver-perfil',
                    'name' => 'PERFIL'
                ]
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