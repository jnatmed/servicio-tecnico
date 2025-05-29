<?php

// Definición de rutas
return [
    /**
     * RUTAS PARA ORDENES DE TRABAJO
     */
    [
        'method' => ['get', 'post'],
        'path' => '/orden-de-trabajo/nuevo',
        'controller' => 'OrdenController@new',
        'auth' => true,
        'roles' => [ADMINISTRADOR, OFICINA]
    ],
    [
        'method' => ['get'],
        'path' => '/orden-de-trabajo/ver',
        'controller' => 'OrdenController@show',
        'auth' => true,
        'roles' => [ADMINISTRADOR, TECNICA, OFICINA]
    ],
    [
        'method' => ['get'],
        'path' => '/orden-de-trabajo/listar',
        'controller' => 'OrdenController@listar',
        'auth' => true,
        'roles' => [ADMINISTRADOR, TECNICA]
    ],
    [
        'method' => ['get'],
        'path' => '/ordenes',
        'controller' => 'OrdenController@listar',
        'auth' => true,
        'roles' => [ADMINISTRADOR, TECNICA, OFICINA]
    ],
    [
        'method' => ['get'],
        'path' => '/ordenes/home',
        'controller' => 'OrdenController@listar',
        'auth' => true,
        'roles' => [ADMINISTRADOR, TECNICA, OFICINA]
    ],
    [
        'method' => ['get', 'post'],
        'path' => '/ordenes-de-trabajo/editar',
        'controller' => 'OrdenController@edit',
        'auth' => false,
        'roles' => []
    ],
    [
        'method' => ['get'],
        'path' => '/orden-de-trabajo/eliminar',
        'controller' => 'OrdenController@delete',
        'auth' => true,
        'roles' => [ADMINISTRADOR, TECNICA]
    ],
    [
        'method' => ['get'],
        'path' => '/orden-de-trabajo/descargar',
        'controller' => 'OrdenController@download',
        'auth' => true,
        'roles' => [ADMINISTRADOR, TECNICA]
    ],
    [
        'method' => ['get'],
        'path' => '/orden-de-trabajo/actualizar_estado',
        'controller' => 'OrdenController@actualizarEstado',
        'auth' => true,
        'roles' => [ADMINISTRADOR, TECNICA]
    ],
    /**
     * RUTAS PARA SISTEMA DE FACTURACION
     */
    [
        'method' => ['get', 'post'],
        'path' => '/facturacion/new',
        'controller' => 'Facturacion\FacturacionController@alta',
        'auth' => true,
        'roles' => [ADMINISTRADOR, PUNTO_VENTA, CODIGO608],
    ],
    [
        'method' => ['get', 'post'],
        'path' => '/facturacion/listar',
        'controller' => 'Facturacion\FacturacionController@listar',
        'auth' => true,
        'roles' => [ADMINISTRADOR, PUNTO_VENTA, CODIGO608]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/ver',
        'controller' => 'Facturacion\FacturacionController@ver',
        'auth' => true,
        'roles' => [ADMINISTRADOR, JEFATURA_VENTAS]
    ],
    [
        'method' => 'delete',
        'path' => '/facturacion/eliminar',
        'controller' => 'Facturacion\FacturacionController@eliminarFactura',
        'auth' => true,
        'roles' => [ADMINISTRADOR, JEFATURA_VENTAS]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/numerador/lista',
        'controller' => 'Facturacion\FacturacionController@listarNumerador',
        'auth' => true,
        'roles' => [ADMINISTRADOR, JEFATURA_VENTAS, CODIGO608]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/numerador/solicitudes/pendientes/json',
        'controller' => 'Facturacion\FacturacionController@listarNumerador',
        'auth' => true,
        'roles' => [ADMINISTRADOR, JEFATURA_VENTAS]
    ],
    [
        'method' => ['post'],
        'path' => '/facturacion/numerador/solicitudes/aceptar',
        'controller' => 'Facturacion\FacturacionController@aceptarSolicitud',
        'auth' => true,
        'roles' => [ADMINISTRADOR, JEFATURA_VENTAS]
    ],
    [
        'method' => ['post'],
        'path' => '/facturacion/numerador/solicitudes/rechazar',
        'controller' => 'Facturacion\FacturacionController@rechazarSolicitud',
        'auth' => true,
        'roles' => [ADMINISTRADOR, JEFATURA_VENTAS]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/api_get_agentes',
        'controller' => 'Facturacion\AgenteController@getAgentes',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS, PUNTO_VENTA]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/api_get_productos',
        'controller' => 'Facturacion\FacturacionController@getProductos',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS, PUNTO_VENTA]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/api_get_precio_producto',
        'controller' => 'Facturacion\FacturacionController@getPreciosProductos',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS, PUNTO_VENTA]
    ],
    [
        'method' => ['post'],
        'path' => '/facturacion/subir-comprobante',
        'controller' => 'Facturacion\FacturacionController@subirComprobante',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS, PUNTO_VENTA]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/ver-comprobante',
        'controller' => 'Facturacion\FacturacionController@verComprobante',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS, PUNTO_VENTA]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/productos/listado',
        'controller' => 'Facturacion\ProductoController@listar',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS, PUNTO_VENTA, PLANIFICACION_COMERCIAL]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/productos/ver',
        'controller' => 'Facturacion\ProductoController@ver',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS, PUNTO_VENTA, PLANIFICACION_COMERCIAL]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/productos/ver_imagen',
        'controller' => 'Facturacion\ProductoController@verImgProducto',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS, PUNTO_VENTA, PLANIFICACION_COMERCIAL]
    ],
    [
        'method' => ['get', 'post'],
        'path' => '/facturacion/productos/agregar-precio',
        'controller' => 'Facturacion\ProductoController@agregarPrecio',
        'auth' => true,
        'roles' => [ADMINISTRADOR, PLANIFICACION_COMERCIAL]
    ],
    [
        'method' => ['get', 'post'],
        'path' => '/facturacion/productos/editar',
        'controller' => 'Facturacion\ProductoController@editarProducto',
        'auth' => true,
        'roles' => [ADMINISTRADOR, PLANIFICACION_COMERCIAL]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/productos/eliminar',
        'controller' => 'Facturacion\ProductoController@eliminarProducto',
        'auth' => true,
        'roles' => [ADMINISTRADOR, PLANIFICACION_COMERCIAL]
    ],
    [
        'method' => ['post'],
        'path' => '/facturacion/productos/informar-decomiso',
        'controller' => 'Facturacion\ProductoController@registrarDecomiso',
        'auth' => true,
        'roles' => [ADMINISTRADOR, PLANIFICACION_COMERCIAL]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/productos/ver-comprobante',
        'controller' => 'Facturacion\ProductoController@verComprobanteDecomiso',
        'auth' => true,
        'roles' => [ADMINISTRADOR, PLANIFICACION_COMERCIAL, PUNTO_VENTA]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/agentes/listado',
        'controller' => 'Facturacion\AgenteController@getAgentes',
        'auth' => true,
        'roles' => [ALL]
    ],
    [
        'method' => ['get', 'post'],
        'path' => '/facturacion/agentes/nuevo',
        'controller' => 'Facturacion\AgenteController@new',
        'auth' => true,
        'roles' => [ALL]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/agente/ver',
        'controller' => 'Facturacion\CuentaCorrienteController@verCuentaCorrienteAgente',
        'auth' => true,
        'roles' => [ALL]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/cuotas/listado',
        'controller' => 'Facturacion\CuotasController@listar',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS]
    ],
    [
        'method' => ['post'],
        'path' => '/facturacion/cuotas/listado',
        'controller' => 'Facturacion\CuotasController@reporteAgrupado',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS]
    ],
    [
        'method' => ['post'],
        'path' => '/facturacion/cuotas/aplicar-descuento-masivo',
        'controller' => 'Facturacion\CuotasController@aplicarDescuentoMasivo',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/cuotas/exportar-txt',
        'controller' => 'Facturacion\CuotasController@exportarTxt',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS]
    ],
    [
        'method' => ['get'],
        'path' => '/facturacion/cuotas/solicitudes-pendientes',
        'controller' => 'Facturacion\CuotasController@verSolicitudesPendientes',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS]
    ],
    [
        'method' => ['post'],
        'path' => '/facturacion/cuotas/confirmar-descuentos',
        'controller' => 'Facturacion\CuotasController@confirmarDescuentos',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS]
    ],
    [
        'method' => ['get'],
        'path' => '/cuenta-corriente/exportar-pdf',
        'controller' => 'Facturacion\CuentaCorrienteController@exportarPdf',
        'auth' => true,
        'roles' => [ADMINISTRADOR, CODIGO608, JEFATURA_VENTAS]
    ],
    /**
     * RUTAS PARA LA GESTION DE USUARIOS Y SESIONES
     */
    [
        'method' => ['get'],
        'path' => '/',
        'controller' => 'PageController@home',
        'auth' => true,
        'roles' => [ALL]
    ],
    [
        'method' => ['get', 'post'],
        'path' => '/user/login',
        'controller' => 'UserController@login',
        'auth' => false,
        'roles' => [ALL]
    ],
    [
        'method' => ['get'],
        'path' => '/user/logout',
        'controller' => 'UserController@logout',
        'auth' => true,
        'roles' => [ALL]
    ],
    [
        'method' => ['get', 'post'],
        'path' => '/user/register',
        'controller' => 'UserController@register',
        'auth' => true,
        'roles' => [ADMINISTRADOR]
    ],
    [
        'method' => ['get'],
        'path' => '/user/get_listado',
        'controller' => 'UserController@getListado',
        'auth' => true,
        'roles' => [ADMINISTRADOR]
    ],
    [
        'method' => ['get'],
        'path' => '/user/actualizar_rol',
        'controller' => 'UserController@actualizarRol',
        'auth' => true,
        'roles' => [ADMINISTRADOR]
    ],
    [
        'method' => ['get','post'],
        'path' => '/user/confirmar_solicitud_dependencia',
        'controller' => 'UserController@confirmarSolicitudDependencia',
        'auth' => true,
        'roles' => [ADMINISTRADOR, PUNTO_VENTA]
    ],
    [
        'method' => ['get'],
        'path' => '/user/rechazar_solicitud_dependencia',
        'controller' => 'UserController@rechazarSolicitudDependencia',
        'auth' => true,
        'roles' => [ADMINISTRADOR, PUNTO_VENTA]
    ],
    [
        'method' => ['get'],
        'path' => '/user/ver-perfil',
        'controller' => 'UserController@verPerfil',
        'auth' => true,
        'roles' => [ALL]
    ],
    [
        'method' => ['get','post'],
        'path' => '/user/asignar-dependencia',
        'controller' => 'UserController@asignarDestino',
        'auth' => true,
        'roles' => [ALL]
    ],

];
