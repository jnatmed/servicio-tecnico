# 🛠️ Sistema de Servicio Técnico - Dirección de Comercialización

Bienvenido al repositorio del sistema de **Servicio Técnico y Descuento de Haberes**, desarrollado para la **Dirección de Comercialización**.  
Este sistema permite gestionar órdenes de servicio, facturación de productos, administración de agentes, control de stock y generación de reportes automáticos.

---

## 📋 Sobre este proyecto

Este sistema cubre:

- **Facturación**: Gestión de facturas, cuotas, condiciones de venta y exportaciones.
- **Órdenes de Servicio**: Registro, asignación y resolución de trabajos técnicos.
- **Administración de Productos**: Control de stock, actualizaciones de precio, decomisos.
- **Gestión de Agentes**: Alta, modificación, asignación de dependencias.
- **Reportes**: Listados de cuotas, cuentas corrientes, resumen de órdenes, minutas.
- **Control de Cuenta Corriente**: Seguimiento de créditos, débitos y saldos de agentes.

---

## 📚 Documentación

| Documento | Descripción |
|:---|:---|
| [📘 Vistas del Sistema](docs/vistas.md) | Detalle de cada vista implementada, con capturas, funcionalidades y aspectos técnicos.|

> 🔔 **Nota**: La documentación se actualiza periódicamente conforme a las mejoras del sistema.

---

## 🛠️ Estructura del Proyecto

```plaintext
.
├── .vscode/                      # Configuraciones del entorno de desarrollo
├── banco_pruebas/                 # Base de datos de prueba
├── comprobantes/                  # Comprobantes de facturación almacenados
├── db/                            # Scripts SQL y backups de estructura
├── docs/
│   ├── vistas.md                  # Documentación de vistas
│   └── img/                       # Capturas de pantalla
├── logs/                          # Logs de ejecución
├── ordenes/                       # Documentos de órdenes de servicio
├── public/                        # Archivos públicos
├── reportes/                      # Reportes TXT y PDF
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
│   │   ├── Utils/                 # Utilidades auxiliares
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
├── uploads/                       # Subida de archivos de usuarios
├── vendor/                        # Librerías de Composer
├── .env                            # Variables de entorno
├── .gitignore                      # Exclusiones para Git
├── composer.json                   # Dependencias PHP
└── README.md                       # Este documento

```
plaintext---

# 🛠️ Sistema de Servicio Técnico - Dirección de Comercialización

![Versión](https://img.shields.io/badge/versión-1.0.0-blue.svg)
![Estado](https://img.shields.io/badge/estado-En%20desarrollo-yellow.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-blueviolet.svg)
![Licencia](https://img.shields.io/badge/Licencia-Privado-lightgrey.svg)

Bienvenido al repositorio del sistema de **Servicio Técnico y Descuento de Haberes**, desarrollado para la **Dirección de Comercialización**.  
Este sistema permite gestionar órdenes de servicio, facturación de productos, administración de agentes, control de stock y generación de reportes automáticos.


