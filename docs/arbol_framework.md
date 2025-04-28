# 📁 Árbol de Archivos del Framework - Sistema de Servicio Técnico

Este documento tiene como finalidad describir la **estructura de carpetas y archivos** del sistema de Servicio Técnico.  
La organización modular facilita la escalabilidad, el mantenimiento y la comprensión del proyecto para nuevos desarrolladores.

Cada sección del sistema (controladores, modelos, vistas) sigue el patrón MVC (Modelo–Vista–Controlador), acompañado de carpetas auxiliares para la conexión a base de datos, excepciones, configuración general y utilitarios.

A continuación, se detalla el árbol de archivos completo:

---

```plaintext
.
├── .vscode/
├── banco_pruebas/
├── comprobantes/
├── db/
├── docs/
│   ├── vistas.md
│   └── img/
├── logs/
├── ordenes/
├── public/
├── reportes/
├── src/
│   ├── App/
│   │   ├── Controllers/
│   │   │   ├── Facturacion/
│   │   │   │   ├── ErrorController.php
│   │   │   │   ├── InternoController.php
│   │   │   │   ├── MinutaController.php
│   │   │   │   ├── ModulosController.php
│   │   │   │   ├── OrdenController.php
│   │   │   │   ├── PageController.php
│   │   │   │   ├── TalleresController.php
│   │   │   │   └── UserController.php
│   │   ├── Models/
│   │   │   ├── Agente.php
│   │   │   ├── AgentesCollection.php
│   │   │   ├── CuentaCorriente.php
│   │   │   ├── CuentaCorrienteCollection.php
│   │   │   ├── Cuota.php
│   │   │   ├── CuotasCollection.php
│   │   │   ├── DependenciasCollection.php
│   │   │   ├── DetalleFactura.php
│   │   │   ├── Factura.php
│   │   │   ├── FacturasCollection.php
│   │   │   ├── GoogleClient.php
│   │   │   ├── Imagen.php
│   │   │   ├── ImagenCollection.php
│   │   │   ├── Interno.php
│   │   │   ├── LDAP.php
│   │   │   ├── MailjetMailer.php
│   │   │   ├── Minutas.php
│   │   │   ├── OrdenCollection.php
│   │   │   ├── Producto.php
│   │   │   ├── ProductosCollection.php
│   │   │   ├── Taller.php
│   │   │   └── Uploader.php
│   │   ├── Utils/
│   │   └── Views/
│   │       ├── errors/
│   │       │   ├── internal-error.view.html
│   │       │   └── not-found.view.html
│   │       ├── facturacion/
│   │       │   ├── agentes/
│   │       │   │   ├── agente.listado.html
│   │       │   │   ├── agente.new.html
│   │       │   │   ├── agente.success.html
│   │       │   │   ├── cuentaCorriente_agente.view.html
│   │       │   │   └── cuentaCorriente_pdf.view.html
│   │       │   ├── cuotas/
│   │       │   │   ├── cuotas.listado-filtrado.html
│   │       │   │   └── solicitudes_pendientes.view.html
│   │       │   ├── productos/
│   │       │   │   ├── agregar.precio.html
│   │       │   │   ├── detalle.producto.html
│   │       │   │   ├── editar.producto.html
│   │       │   │   └── listado.html
│   │       │   ├── listado.factura.html
│   │       │   ├── factura.new.html
│   │       │   └── factura.listado.html
│   │       ├── internos/
│   │       │   └── internos.listado.html
│   │       ├── minutas/
│   │       │   ├── minuta.new.html
│   │       │   ├── minuta.ver.html
│   │       │   ├── minutas.listado.html
│   │       │   └── vista_minuta.html
│   │       ├── ordenes-de-trabajo/
│   │       │   ├── orden.trabajo.list.html
│   │       │   └── resumen.orden.view.html
│   │       ├── parts/
│   │       │   ├── cierre-modulos.view.html
│   │       │   ├── footer.view.html
│   │       │   ├── head.view.html
│   │       │   ├── header.view.html
│   │       │   ├── modulos.view.html
│   │       │   └── nav.view.html
│   │       ├── talleres/
│   │       │   ├── talleres.listado.html
│   │       │   ├── asignaciones.html
│   │       │   ├── home.view.html
│   │       │   ├── index.view.html
│   │       │   ├── login.view.html
│   │       │   ├── perfil.view.html
│   │       │   └── register.view.html
│   └── Core/
│       ├── Configs/
│       ├── Database/
│       │   ├── ConnectionBuilder.php
│       │   ├── QueryBuilder.php
│       │   └── QueryBuilderV2.php
│       ├── Exceptions/
│       ├── Traits/
│       ├── config.php
│       ├── Controller.php
│       ├── helpers.php
│       ├── Model.php
│       ├── Request.php
│       ├── Router.php
│       └── bootstrap.php
├── uploads/
├── vendor/
├── .env
├── .gitignore
├── composer.json
└── README.md
