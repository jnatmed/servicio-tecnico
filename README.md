# 🛠️ Sistema de Servicio Técnico - Dirección de Comercialización

![Versión](https://img.shields.io/badge/versión-1.0.0-blue.svg)
![Estado](https://img.shields.io/badge/estado-En%20desarrollo-yellow.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-blueviolet.svg)
![Licencia](https://img.shields.io/badge/Licencia-Privado-lightgrey.svg)

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
| [📘 Vistas del Sistema](docs/vistas.md) | Detalle de cada vista implementada, con capturas, funcionalidades y aspectos técnicos. |
| [📁 Árbol de Archivos del Framework](docs/arbol_framework.md) | Estructura de carpetas y archivos del sistema, detallando controladores, modelos y vistas. |

> 🔔 **Nota**: La documentación se actualiza periódicamente conforme a las mejoras del sistema.

---


## Roles y permisos de usuario

### `administrador`
- ✅ Tiene acceso **total** a todas las opciones y vistas del sistema.

---

### `jefatura_ventas`
- 📄 **Facturación**: puede ver ventas/facturas de **todas las dependencias**.
- 🔢 **Numeración de facturas**: puede **aceptar o rechazar** solicitudes.
- 👤 **Agentes**: puede buscar agentes.
- 🔐 **Login**: puede iniciar sesión.
- 🚪 **Salir**: puede cerrar sesión.
- 🧑‍💼 **Usuarios**: puede ver usuarios con rol `punto_venta` y **aceptar o rechazar** asignaciones de dependencia.
- 🪪 **Perfil**: puede ver su perfil.

---

### `punto_venta`
- 📦 **Productos**: puede buscar productos de **su propia dependencia**.
- 📄 **Facturación**:
  - Puede **crear facturas** para **su dependencia**.
  - Puede ver **solo las facturas de su dependencia**.
- 👤 **Agentes**: puede buscar agentes.
- 🔐 **Login**: puede iniciar sesión.
- 🚪 **Salir**: puede cerrar sesión.
- 🪪 **Perfil**:
  - Puede ver su perfil.
  - Puede solicitar **asignación de dependencia**.

---

### `codigo608`
- 📄 **Facturación**: puede ver ventas/facturas de **todas las dependencias**.
- 📊 **Reportes**: puede **armar y confirmar** solicitudes de descuentos de haberes por fechas.
- 👤 **Agentes**: puede buscar agentes.
- 🔐 **Login**: puede iniciar sesión.
- 🚪 **Salir**: puede cerrar sesión.
- 🪪 **Perfil**: puede ver su perfil.

---

### `planificacion_comercial`
- 📦 **Productos**:
  - Puede buscar productos de **todas las dependencias**.
  - Puede **crear, modificar y eliminar productos**.
- 📄 **Facturación**: **sin acceso**.
- 👤 **Agentes**: **sin acceso**.
- 🔐 **Login**: puede iniciar sesión.
- 🚪 **Salir**: puede cerrar sesión.
- 🪪 **Perfil**:
  - Puede ver su perfil.
  - **No requiere asignación de dependencia**.
- 🚫 **Registro de usuario nuevo**: **sin acceso** (gestionado por red interna - Windows Server).
