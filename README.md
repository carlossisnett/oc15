# OC15 - Sistema de Gestión de Órdenes de Compra

Sistema web para gestión de órdenes de compra e inventario con integración a SAP Business One.

## Stack Tecnológico

| Componente | Tecnología |
|------------|------------|
| Backend | PHP 7+ |
| Base de Datos | MySQL |
| Frontend | Bootstrap 4, jQuery, AdminLTE 3 |
| Dependencias | PHPMailer, Azure Storage Blob |
| Integraciones | SAP Business One Service Layer, LDAP/Active Directory |

## Requisitos

- PHP 7.4+
- MySQL 5.7+
- Composer
- Servidor web (Apache/XAMPP)
- Acceso a SAP Business One Service Layer

## Instalación Rápida

```bash
# 1. Clonar en directorio web
git clone [repo] C:\xampp\htdocs\ordenes_compra

# 2. Instalar dependencias
composer install

# 3. Importar base de datos
mysql -u root -p ordenes_compra < ordenes_compra.sql

# 4. Configurar credenciales (ver sección Configuración)

# 5. Acceder
http://localhost/ordenes_compra/admin/
```

## Configuración

### Base de Datos (`initialize.php`)

```php
define('DB_SERVER', "localhost");
define('DB_USERNAME', "root");
define('DB_PASSWORD', "");
define('DB_NAME', "ordenes_compra");
define('base_url', 'http://localhost/ordenes_compra/');
```

### SAP Service Layer (`configuracion.php`)

```php
return [
    'hostsap' => 'servidor-sap.dominio.com',
    'puertosap' => '50000',
    'companydbsap' => 'NOMBRE_BD_SAP',
    'usernamesap' => 'usuario_sap',
    'passwordsap' => 'contraseña_sap',
    'ambiente' => 'local' // local | produccion
];
```

## Estructura del Proyecto

```
├── admin/                      # Panel de administración
│   ├── purchase_orders/        # CRUD órdenes de compra
│   │   ├── manage_po.php       # Crear/editar OC
│   │   ├── sap_service_layer.php # Clase integración SAP
│   │   ├── sendPurchaseRequest.php # Envío a SAP
│   │   └── enviar_correo.php   # Notificaciones email
│   ├── all_purchase_orders/    # Listados y aprobaciones
│   ├── inventario/             # Gestión de inventario
│   ├── items/                  # Catálogo de artículos
│   ├── proveedores/            # Gestión de proveedores
│   └── user/                   # Gestión de usuarios
├── classes/                    # Lógica de negocio
│   ├── Master.php              # Controlador principal (CRUD)
│   ├── Login.php               # Autenticación
│   ├── DBConnection.php        # Conexión MySQL
│   ├── SystemSettings.php      # Configuración del sistema
│   └── Users.php               # Gestión de usuarios
├── uploads/                    # Archivos adjuntos OC
├── config.php                  # Configuración global PHP
├── initialize.php              # Constantes de BD
└── configuracion.php           # Credenciales SAP
```

## Tablas Principales

| Tabla | Descripción |
|-------|-------------|
| `po_list` | Órdenes de compra (cabecera) |
| `order_items` | Líneas de órdenes de compra |
| `item_list` | Catálogo de artículos |
| `proveedores` | Proveedores |
| `users` | Usuarios del sistema |
| `aprobadores` | Aprobadores por departamento |
| `centro_costo` | Centros de costo (marcas/departamentos) |
| `solicitud_de_inventario` | Solicitudes de inventario |
| `inventory_items` | Articulos de una solicitud de inventario |
| `aprobaciones` | Historial de aprobaciones |

## Clases Principales

### Master.php (Controlador Principal)

```php
// Proveedores
$master->save_proveedor();
$master->delete_supplier();

// Artículos
$master->save_articulo();
$master->search_items();
$master->get_stock();

// Órdenes de Compra
$master->save_po();
$master->edit_po();
$master->hay_lineas($id);

// Inventario
$master->update_inventory_request();
$master->search_inventory_items();
```

### SAPServiceLayer (Integración SAP)

```php
$sap = new SAPServiceLayer($host, $puerto, $companyDB, $user, $pass);

// Crear solicitud de compra
$sap->createPurchaseRequest($data);

// Crear orden de compra
$sap->createPurchaseOrder($data);

// Salida de inventario
$sap->create_inventory_exit($data);

// Consultar solicitudes abiertas
$sap->get_old_open_purchase_requests();
```

## Flujo de Trabajo

```
1. Usuario crea OC → 2. Sistema genera PO-{random}
                   → 3. Calcula totales/impuestos
                   → 4. Notifica aprobadores
                   → 5. Aprobación/Rechazo
                   → 6. Envío a SAP Service Layer
                   → 7. Genera documento en SAP
```

## Estados de Órdenes

| Status | Descripción |
|--------|-------------|
| 0 | Pendiente |
| 1 | Aprobado |
| 2 | Rechazado |
| 3 | Listo para aprobar |

## Tipos de Usuario

| Type | Rol | Permisos |
|------|-----|----------|
| 1 | Administrador | Acceso total |
| 2 | Compras | Gestión de OC |
| 3 | Almacén | Inventario |

## Rutas del Admin

| Ruta | Descripción |
|------|-------------|
| `?page=purchase_orders/manage_po` | Crear/editar ordenes de compra |
| `?page=all_purchase_orders` | Listado de ordenes de compra |
| `?page=purchase_orders/update_approver` | Gestionar aprobadores |
| `?page=inventario` | Gestión de inventario |
| `?page=proveedores` | Gestión de proveedores |
| `?page=items` | Catálogo de artículos |
| `?page=user` | Gestión de usuarios |

## Scripts de Sincronización SAP

```bash

Estas 4 son tareas programadas que estan programadas en cron-job.org (un servicio externo que las llama desde un browser)

# Enviar a SAP las ordenes de compra aprobadas
php /admin/purchase_orders/reenviar_a_sap_paralelo.php

# Importar artículos nuevos desde SAP
php traer_items_de_sap_paralelo.php

# Importar proveedores desde SAP
php traer_proveedores_de_sap_paralelo.php

# Obtener stock actual de los articulos en inventario
php obtener_inventario_paralelo.php
```

## Sistema de Correos

Configurado en `admin/purchase_orders/enviar_correo.php`:

- Servidor SMTP configurable
- Funciones: `enviar_email()`, `enviar_email2()`, `enviar_email_orden_de_compra_aprobada()`
- Soporte para adjuntos PDF

## Notas para Desarrolladores

- Las contraseñas se hashean con `password_hash()` (bcrypt)
- Archivos adjuntos se guardan en `/uploads/{fecha}_OCID_{id}/`
- Los códigos SAP son obligatorios para usuarios, artículos y proveedores
- La zona horaria está configurada en `America/Bogota`
- Los logs de errores cURL se guardan en `curl_error_log.json`

## Archivos de Configuración Sensibles

⚠️ No subir a repositorio público:
- `configuracion.php` (credenciales SAP)
- `secrets.json`
- `admin/purchase_orders/secrets_SAP.json`
- `admin/purchase_orders/secrets_mysql.json`
- initialize.php
