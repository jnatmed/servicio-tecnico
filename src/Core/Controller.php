<?php

namespace Paw\Core;

use Paw\Core\Model; 
use Paw\Core\Database\QueryBuilder;
use Paw\Core\Traits\Loggable;
use Paw\Core\Menu;

class Controller 
{
    public string $viewsDir;

    public array $menu;
    
    public $claseMenu;

    public ?string $modelName = null;   
    protected $model;
    use Loggable;
    public $qb;
    public $request;

    public function __construct($usuariosAdmin = []){
        
        global $connection, $log;        

        $this->viewsDir = __DIR__ . '/../App/views/';

        $this->claseMenu = new Menu();
        
        $this->menu = [
            'menu' => [

                [
                    'href' => '/minutas/listar',
                    'class' => '.edicion',
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
                    'href' => '/facturacion/listar',
                    'class' => '.documento',
                    'name' => 'VENTAS',
                    'submenu' => [
                        [
                            'href' => '/facturacion/listar',
                            'name' => 'VENTAS'
                        ],
                        [
                            'href' => '/facturacion/numerador/lista',
                            'name' => 'NUMERADOR FACTURACION'
                        ],
                        [
                            'href' => '/facturacion/cuotas/listado',
                            'name' => 'REPORTES'
                        ],
                        [
                            'href' => '/facturacion/cuotas/solicitudes-pendientes',
                            'name' => 'SOLICITUDES DESCUENTOS'
                        ],
                        [
                            'href' => '/facturacion/new',
                            'name' => 'NUEVA VENTA'
                        ],
                        [
                            'href' => '/facturacion/productos/listado',
                            'name' => 'PRODUCTOS'
                        ],
                    ]
                    
                ],
                [
                    'href' => '/facturacion/agentes/listado',
                    'class' => '.lista',
                    'name' => 'AGENTES',
                    'submenu' => [
                        [
                            'href' => '/facturacion/agentes/listado',
                            'name' => 'LISTADO'
                        ],
                        [
                            'href' => '/facturacion/agentes/nuevo',
                            'name' => 'NUEVO AGENTE'
                        ]
                    ]
                    
                ],
                [
                    'href' => '/user/get_listado',
                    'class' => '.lista',
                    'name' => 'USUARIOS',
                    'submenu' => [
                        [
                            'href' => '/user/get_listado',
                            'name' => 'LISTADO'
                        ]
                    ]
                    
                ],
                [
                    'href' => '/user/login',
                    'name' => 'LOGIN'
                ],
                [
                    'href' => '/user/logout', // 
                    'class' => '.salir',
                    'name' => 'SALIR'
                ],
                [
                    'href' => '/user/register', // cualquiera
                    'class' => '.archivo',
                    'name' => 'REGISTRO'
                ],
                [
                    'href' => '/user/ver-perfil', // logueados > todos
                    'class' => '.perfil',
                    'name' => 'PERFIL'
                ]
            ]
        ];
    
        $this->qb = new QueryBuilder($connection, $log);
        $this->request = new Request();

        if(!is_null($this->modelName)){

            if($usuariosAdmin !== []){
                $log->info("🖥️✅🖥️✅📦 Seteando Modelo con rolesPermitidos", [$this->modelName, $usuariosAdmin]);
                $model = new $this->modelName(null, null, $usuariosAdmin);
            }else{
                $log->info("🖥️📦 Seteando Modelo SIN rolesPermitidos", [$this->modelName, $usuariosAdmin]);
                $model = new $this->modelName; 
            }
            // $model = new $this->modelName;
            $model->setQueryBuilder($this->qb);
            $model->setLogger($log); // todos los modelos tienen q ser logeables
            $this->setModel($model);
            $log->info('Seteando Querybuilder: ' , [$model]);
        }else{
            $log->error('No se encontro el modelo modelName', [$this->modelName] );
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