<?php

$routes_definidas = [
    'config' => [
        'rol_root' => ADMINISTRADOR,
        'auth' => true,
    ],
    'menu' => [
        [
            'href' => '/facturacion/listar',
            'class' => '.documento',
            'name' => 'VENTAS',
            'roles' => [JEFATURA_VENTAS, CODIGO608, PUNTO_VENTA, PLANIFICACION_COMERCIAL],
            'submenu' => [
                [
                    'href' => '/facturacion/listar',
                    'name' => 'VENTAS',
                    'roles' => [JEFATURA_VENTAS, CODIGO608, PUNTO_VENTA],
                ],
                [
                    'href' => '/facturacion/numerador/lista',
                    'name' => 'NUMERADOR FACTURACION',
                    'roles' => [JEFATURA_VENTAS],
                ],
                [
                    'href' => '/facturacion/cuotas/listado',
                    'name' => 'REPORTES',
                    'roles' => [CODIGO608, JEFATURA_VENTAS],
                ],
                [
                    'href' => '/facturacion/cuotas/solicitudes-pendientes',
                    'name' => 'SOLICITUDES DESCUENTOS',
                    'roles' => [CODIGO608, JEFATURA_VENTAS],
                ],
                [
                    'href' => '/facturacion/new',
                    'name' => 'NUEVA VENTA',
                    'roles' => [PUNTO_VENTA],
                ],
                [
                    'href' => '/facturacion/productos/listado',
                    'name' => 'PRODUCTOS',
                    'roles' => [PUNTO_VENTA, PLANIFICACION_COMERCIAL, JEFATURA_VENTAS],
                ],
            ],
        ],
        [
            'href' => '/facturacion/agentes/listado',
            'class' => '.lista',
            'name' => 'AGENTES',
            'roles' => ['all_less:oficina,tecnica,planificacion_comercial'],
            'submenu' => [
                [
                    'href' => '/facturacion/agentes/listado',
                    'name' => 'LISTADO',
                ],
                [
                    'href' => '/facturacion/agentes/nuevo',
                    'name' => 'NUEVO AGENTE',
                    'roles' => [ADMINISTRADOR],
                ],
            ],
        ],
        [
            'href' => '/user/get_listado',
            'class' => '.lista',
            'name' => 'USUARIOS',
            'submenu' => [
                [
                    'href' => '/user/get_listado',
                    'name' => 'LISTADO',
                ],
            ],
        ],
        [
            'href' => '/user/login',
            'name' => 'LOGIN',
            'auth' => false,
        ],
        [
            'href' => '/user/logout',
            'class' => '.salir',
            'name' => 'SALIR',
            'roles' => [ALL],
        ],
        // [
        //     'href' => '/user/register',
        //     'class' => '.archivo',
        //     'name' => 'REGISTRO',
        // ],
        [
            'href' => '/user/ver-perfil',
            'class' => '.perfil',
            'name' => 'PERFIL',
            'roles' => [ALL],
        ],
    ],
];

return $routes_definidas;
