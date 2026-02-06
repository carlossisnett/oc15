# OC15 - Sistema de Gestión de Órdenes de Compra

## Descripción General

OC15 es un sistema web de gestión de órdenes de compra e inventario desarrollado en PHP con integración a SAP Business One. Permite la creación, aprobación y seguimiento de solicitudes de compra, gestión de proveedores, artículos e inventario.

## Stack Tecnológico

- **Backend**: PHP 7+
- **Base de Datos**: MySQL
- **Frontend**: Bootstrap 4, jQuery, AdminLTE
- **Dependencias**: PHPMailer (correos), Select2 (dropdowns)
- **Integraciones**: SAP Business One Service Layer, LDAP/Active Directory

## Estructura del Proyecto

```
├── admin/                    # Panel de administración
│   ├── index.php            # Punto de entrada principal
│   ├── login.php            # Autenticación
│   ├── purchase_orders/     # Gestión de órdenes de compra
│   ├── all_purchase_orders/ # Listado y aprobaciones
│   ├── inventario/          # Gestión de inventario
│   ├── items/               # Catálogo de artículos
│   ├── proveedores/         # Gestión de proveedores
│   ├── user/                # Gestión de usuarios
│   └── inc/                 # Componentes (header, nav, footer)
├── classes/                  # Lógica de negocio
│   ├── Master.php           # Controlador principal
│   ├── Login.php            # Autenticación
│   ├── DBConnection.php     # Conexión a BD
│   ├── SystemSettings.php   # Configuración del sistema
│   └── Users.php            # Gestión de usuarios
├── uploads/                  # Archivos adjuntos de OC
├── vendor/                   # Dependencias Composer
├── initialize.php           # Constantes de BD
├── config.php               # Configuración global
└── configuracion.php        # Credenciales SAP
```

## Configuración

### Base de Datos

Editar `initialize.php`:

```php
define('DB_SERVER', "localhost");
define('DB_USERNAME', "root");
define('DB_PASSWORD', "");
define('DB_NAME', "ordenes_compra_pruebas");
define('base_url', 'http://localhost/ordenes_compra_pruebas/');
```

### SAP Service Layer

Editar `configuracion.php`:

```php
return [
    'hostsap' => 'servidor-sap.dominio.com',
    'puertosap' => '50000',
    'companydbsap' => 'NOMBRE_BD_SAP',
    'usernamesap' => 'usuario_sap',
    'passwordsap' => 'contraseña_sap'
];
```

## Tablas Principales de la Base de Datos

| Tabla | Descripción |
|-------|-------------|
| `po_list` | Órdenes de compra (id, po_no, status, total, SAPDocEntry) |
| `order_items` | Líneas de órdenes (quantity, unit_price, item_id, proveedor_id) |
| `item_list` | Catálogo de artículos (codSAP, description, inventory_item) |
| `proveedores` | Proveedores (name, codSAP, status) |
| `users` | Usuarios (username, password, codSAP, type, email) |
| `aprobadores` | Aprobadores por departamento |
| `centro_costo` | Centros de costo/departamentos |
| `solicitud_de_inventario` | Solicitudes de inventario |
| `inventory_items` | Líneas de inventario |
| `aprobaciones` | Historial de aprobaciones |

## Clases Principales

### Master.php

Controlador principal con métodos para:

- **Proveedores**: `save_proveedor()`, `delete_supplier()`, `obtener_proveedor()`
- **Artículos**: `save_articulo()`, `save_item()`, `search_items()`, `get_stock()`
- **Órdenes de Compra**: `save_po()`, `edit_po()`, `hay_lineas()`, `es_pedido()`
- **Aprobaciones**: `notificar_a_aprobadores()`, `change_po_status()`, `update_approver()`
- **Inventario**: `update_inventory_request()`, `search_marca()`, `search_departamento()`

### Login.php

- `login()` - Autenticación principal
- `validate_password()` - Verificación con bcrypt
- Soporte para recuperación de contraseña por email

### SAPServiceLayer

Clase para comunicación con SAP (`admin/purchase_orders/sap_service_layer.php`):

- `createPurchaseRequest()` - Crear solicitud de compra
- `createPurchaseOrder()` - Crear orden de compra
- `create_inventory_exit()` - Crear salida de inventario
- `get_old_open_purchase_requests()` - Consultar solicitudes abiertas

## Flujo de Trabajo de Órdenes de Compra

1. Usuario crea OC con proveedor, artículos y fechas
2. Sistema genera número único (PO-{random})
3. Calcula subtotal, descuentos e impuestos
4. Notifica a aprobadores según departamento
5. Sistema envía notificaciones por email
6. Aprobadores revisan y aprueban/rechazan
7. Envía solicitud a SAP vía Service Layer
8. OC aprobada genera orden de compra en SAP


## Estados de Órdenes

| Status | Descripción |
|--------|-------------|
| 0 | Pendiente |
| 1 | Aprobado |
| 2 | Rechazado |
| 3 | Listo para aprobar |

## Tipos de Usuario

| Type | Rol |
|------|-----|
| 1 | Administrador |
| 2 | Compras |
| 3 | Almacén |

## Rutas Principales del Admin

- `?page=purchase_orders/manage_po` - Crear/editar OC
- `?page=all_purchase_orders` - Ver todas las OC
- `?page=purchase_orders/update_approver` - Gestionar aprobadores
- `?page=inventario` - Gestión de inventario
- `?page=proveedores` - Gestión de proveedores
- `?page=items` - Catálogo de artículos
- `?page=user` - Gestión de usuarios

## Sistema de Correos

Configurado en `admin/purchase_orders/enviar_correo.php`:

- Servidor SMTP: `simon.prensa.com:2525`
- Funciones: `enviar_email()`, `enviar_email2()`, `enviar_email_orden_de_compra_aprobada()`
- Soporta adjuntos PDF desde `/uploads/`

## Archivos de Sincronización SAP

- `traer_items_de_sap.php` - Importar artículos
- `traer_proveedores_de_sap.php` - Importar proveedores
- `actualizar_ordenes_cerradas.php` - Sincronizar estados
- `obtener_inventario.php` - Obtener stock

## Instalación

1. Clonar repositorio en directorio web (ej: `C:\xampp\htdocs\ordenes_compra_pruebas`)
2. Importar esquema SQL desde archivos `.sql`
3. Configurar `initialize.php` con credenciales de BD
4. Configurar `configuracion.php` con credenciales SAP
5. Ejecutar `composer install` para dependencias
6. Acceder a `http://localhost/ordenes_compra_pruebas/admin/`

## Notas de Desarrollo

- Las contraseñas se hashean con bcrypt (`password_hash()`)
- Los archivos adjuntos se guardan en `/uploads/{fecha}_OCID_{id}/`
- Los códigos SAP son obligatorios para usuarios, artículos y proveedores
