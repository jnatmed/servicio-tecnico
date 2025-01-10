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
                    'href' => '/orden-de-trabajo/listar',
                    'name' => 'ORDENES DE TRABAJO',                    
                    'submenu' => [
                        [
                            'href' => '/orden-de-trabajo/nuevo',
                            'name' => 'NUEVA ORDEN DE TRABAJO'
                        ],
                        [
                            'href' => '/orden-de-trabajo/listado_pcs',
                            'name' => 'LISTADO PCS'
                        ],
                    ]
                ],
                [
                    'href' => '/minutas/listar',
                    'name' => 'MINUTAS',
                    'submenu' => [
                        [
                            'href' => '/minuta/new',
                            'name' => 'NUEVA MINUTA',
                        ],
                        [
                            'href' => '/minutas/listar',
                            'name' => 'VER MINUTAS'
                        ],
                    ]                    
                ],
                [
                    'href' => '/talleres/ver_talleres',
                    'name' => 'TALLERES',
                    'submenu' => [
                        [
                            'href' => '/internos/ver_internos',
                            'name' => 'LISTADO INTERNOS'
                        ]
                    ]
                    
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