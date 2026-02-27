# Manual de Usuario - Sistema OC15
## Sistema de Gestión de Órdenes de Compra

---

## Tabla de Contenidos

1. [Introducción](#1-introducción)
2. [Acceso al Sistema](#2-acceso-al-sistema)
3. [Panel Principal (Dashboard)](#3-panel-principal-dashboard)
4. [Gestión de Órdenes de Compra](#4-gestión-de-órdenes-de-compra)
5. [Aprobación de Solicitudes](#5-aprobación-de-solicitudes)
6. [Gestión de Aprobadores](#6-gestión-de-aprobadores)
7. [Gestión de Proveedores](#7-gestión-de-proveedores)
8. [Gestión de Artículos](#8-gestión-de-artículos)
9. [Gestión de Inventario](#9-gestión-de-inventario)
10. [Gestión de Usuarios](#10-gestión-de-usuarios)
11. [Preguntas Frecuentes](#11-preguntas-frecuentes)

---

## 1. Introducción

OC15 es un sistema web para la gestión de órdenes de compra e inventario con integración a SAP Business One. Permite crear, aprobar y dar seguimiento a solicitudes de compra, gestionar proveedores, artículos e inventario.

### Tipos de Usuario

| Tipo | Rol | Permisos |
|------|-----|----------|
| 1 | Administrador | Acceso total al sistema |
| 2 | Compras | Gestión de órdenes de compra |
| 3 | Almacén | Gestión de inventario |

---

## 2. Acceso al Sistema

### Inicio de Sesión

1. Abra su navegador web y acceda a la URL del sistema
2. Ingrese su **Usuario** y **Contraseña**
3. Haga clic en **INICIAR SESIÓN**

### Recuperación de Contraseña

Si olvidó su contraseña:
1. Haga clic en el enlace **"Olvidé mi contraseña"**
2. Ingrese su correo electrónico registrado
3. Recibirá un enlace para restablecer su contraseña

### Advertencia de Código SAP

Si al iniciar sesión ve el mensaje: *"ADVERTENCIA: Su usuario no tiene código de SAP"*, contacte a desarrollo@prensa.com para que le asignen uno antes de realizar órdenes de compra.

---

## 3. Panel Principal (Dashboard)

Al ingresar al sistema, verá el panel principal con estadísticas:

- **Total Solicitantes**: Cantidad de proveedores registrados
- **Total Solicitudes**: Número total de órdenes de compra
- **Solicitudes Aprobadas**: Órdenes con estado aprobado
- **Solicitudes Canceladas**: Órdenes rechazadas

---

## 4. Gestión de Órdenes de Compra

### 4.1 Crear Nueva Solicitud de Compra

1. Vaya a **Solicitudes de Compra** → **Crear Nuevo**
2. Complete los campos requeridos:

#### Campos del Formulario

| Campo | Descripción | Obligatorio |
|-------|-------------|-------------|
| Proveedor | Seleccione el proveedor de la lista | Sí |
| Solicitud # | Se genera automáticamente | No |
| Fecha necesaria | Fecha en que requiere el producto/servicio | Sí |

#### Agregar Artículos

Para cada línea de artículo:

1. Haga clic en **"Agregar Fila"**
2. Complete los campos:
   - **Cantidad**: Número de unidades
   - **Búsqueda del Artículo**: Escriba al menos 3 caracteres para buscar (código o nombre)
   - **Descripción** (opcional): Detalles adicionales
   - **Marca**: Seleccione el centro de costo de marca
   - **Departamento**: Seleccione el departamento
   - **Enlace** (opcional): URL de referencia del producto
   - **Precio**: Precio unitario

3. El sistema calcula automáticamente:
   - **Sub Total**: Suma de todos los artículos
   - **Descuento**: Puede ingresar porcentaje o monto
   - **Impuestos**: Puede ingresar porcentaje o monto
   - **Total**: Monto final de la orden

#### Adjuntar Archivos

- Solo se permiten archivos PDF
- Haga clic en **"Agregar otro adjunto"** para añadir más archivos (máximo 10)
- Use el botón **"Borrar"** para eliminar un adjunto

#### Notas

- Campo de texto libre para observaciones (máximo 230 caracteres)
- El contador muestra los caracteres utilizados

4. Haga clic en **"Guardar"** para crear la solicitud

### 4.2 Ver Solicitudes de Compra

En la pantalla principal de solicitudes verá dos secciones:

- **Pendientes**: Solicitudes sin número SAP y no rechazadas
- **Historial**: Todas las solicitudes del sistema

#### Filtrar por Monto

1. Ingrese un monto mínimo en el campo de filtro
2. Haga clic en **"Aplicar"**
3. Use **"Limpiar"** para quitar el filtro

### 4.3 Acciones sobre Solicitudes

Desde el menú **"Acción"** de cada solicitud:

| Acción | Descripción |
|--------|-------------|
| Ver | Muestra los detalles completos |
| Editar | Modifica la solicitud (solo Admin/Compras) |
| Duplicar | Crea una copia de la solicitud |
| Aprobar | Aprueba la solicitud (solo Admin) |
| Rechazar | Rechaza la solicitud (solo Admin) |

### 4.4 Estados de las Solicitudes

| Estado | Descripción | Color |
|--------|-------------|-------|
| Pendiente | Esperando revisión | Gris |
| Listo para aprobar | Requiere aprobación | Negro/Negrita |
| Aprobado | Solicitud aprobada | Verde |
| Rechazado | Solicitud denegada | Rojo |

---

## 5. Aprobación de Solicitudes

### Solicitudes Pendientes de Aprobación

Si usted es aprobador, al ingresar verá una sección especial:
**"Las siguientes solicitudes requieren su aprobación"**

### Proceso de Aprobación

1. Revise los detalles de la solicitud haciendo clic en **"Ver"**
2. Desde el menú **"Acción"**:
   - Seleccione **"Aprobar"** para autorizar
   - Seleccione **"Rechazar"** para denegar
3. Confirme la acción en el cuadro de diálogo

### Flujo de Aprobación

```
1. Usuario crea solicitud
2. Sistema notifica a aprobadores por email
3. Aprobador revisa y aprueba/rechaza
4. Si aprobada → Se envía a SAP automáticamente
5. SAP genera documento y devuelve número
```

---

## 6. Gestión de Aprobadores

*Acceso: Solo Administradores*

### Ver Lista de Aprobadores

1. Vaya a **Gestión de Aprobadores**
2. Haga clic en **"Mostrar/Ocultar Lista de Aprobadores"**
3. Verá cada aprobador con sus departamentos asignados

### Agregar Nuevo Aprobador

1. Seleccione el **Departamento** de la lista
2. Seleccione el **Usuario** que aprobará
3. Opciones adicionales:
   - **Aprobará órdenes de compra solamente**: Marque si solo aprobará compras
   - **Aprobará salidas de inventario solamente**: Marque si solo aprobará inventario
4. Haga clic en **"Enviar"**

### Eliminar Aprobador

1. En la lista de aprobadores, haga clic en el botón rojo **X** junto al departamento
2. Confirme la eliminación

### Super Firma

La **Super Firma** permite a un usuario aprobar solicitudes de cualquier departamento:

1. En la lista de aprobadores, use el interruptor junto al nombre del usuario
2. **Activada**: El usuario puede aprobar cualquier solicitud
3. **Desactivada**: Solo aprueba sus departamentos asignados

### Transferir Aprobaciones

Para transferir las responsabilidades de un aprobador a otro:

1. Seleccione el **Antiguo aprobador**
2. Seleccione el **Nuevo aprobador**
3. Elija el tipo de transferencia:
   - **Permanente**: El antiguo aprobador pierde los permisos
   - **Temporal**: Por vacaciones o licencia (se puede revertir)
4. Haga clic en **"Enviar"**

### Retornar Aprobaciones

Si hay una transferencia temporal activa:

1. Verá la sección **"Retorno de aprobador"**
2. Haga clic en **"Sí, retornar aprobaciones"** para devolver los permisos al aprobador original

### Departamentos sin Aprobador

Al final de la página verá una lista de departamentos que no tienen aprobador asignado.

---

## 7. Gestión de Proveedores

### Ver Proveedores

1. Vaya a **Proveedores** en el menú lateral
2. Verá la lista de todos los proveedores registrados

### Información del Proveedor

| Campo | Descripción |
|-------|-------------|
| Código SAP | Identificador único en SAP |
| Nombre | Nombre del proveedor |
| Estado | Activo/Inactivo |

### Sincronización con SAP

Los proveedores se importan automáticamente desde SAP mediante tareas programadas.

---

## 8. Gestión de Artículos

### Buscar Artículos

Al crear una orden de compra:
1. Escriba al menos 3 caracteres en el campo de búsqueda
2. El sistema mostrará coincidencias por código o descripción
3. Seleccione el artículo de la lista

### Catálogo de Artículos

1. Vaya a **Artículos** en el menú lateral
2. Puede ver y buscar todos los artículos disponibles

### Sincronización con SAP

Los artículos se importan automáticamente desde SAP mediante tareas programadas.

---

## 9. Gestión de Inventario

### Solicitudes de Inventario

1. Vaya a **Inventario** en el menú lateral
2. Puede crear solicitudes de salida de inventario
3. Las solicitudes siguen un flujo de aprobación similar a las órdenes de compra

### Consultar Stock

El sistema consulta el stock actual de los artículos desde SAP.

---

## 10. Gestión de Usuarios

*Acceso: Solo Administradores*

### Ver Usuarios

1. Vaya a **Usuarios** en el menú lateral
2. Verá la lista de todos los usuarios del sistema

### Información del Usuario

| Campo | Descripción |
|-------|-------------|
| Usuario | Nombre de usuario para login |
| Nombre | Nombre completo |
| Email | Correo electrónico |
| Código SAP | Identificador en SAP (obligatorio para crear OC) |
| Tipo | Rol del usuario (Admin/Compras/Almacén) |

---

## 11. Preguntas Frecuentes

### ¿Por qué no puedo crear órdenes de compra?

Verifique que su orden de compra tenga todos los campos llenos y que incluya un PDF.

### ¿Por qué no veo solicitudes para aprobar?

- Verifique que esté configurado como aprobador de algún departamento
- Debe ser aprobador del departamento de los artículos de la solicitud
- Las solicitudes deben estar en estado "Listo para aprobar"


### ¿Cómo sé si mi solicitud fue enviada a SAP?

Cuando una solicitud es aprobada y enviada exitosamente a SAP, aparecerá un número en la columna **"# SAP"**.

### ¿Puedo editar una solicitud aprobada?

No. Una vez aprobada, la solicitud no puede modificarse. Puede duplicarla para crear una nueva.

### ¿Qué formatos de archivo puedo adjuntar?

Solo se permiten archivos en formato PDF.

### ¿Cuántos archivos puedo adjuntar?

Puede adjuntar hasta 10 archivos PDF por solicitud.

### ¿Cómo busco un artículo específico?

En el campo de búsqueda, escriba al menos 3 caracteres del código SAP o la descripción del artículo.

---

## Soporte Técnico

Para asistencia técnica o reportar problemas, contacte a:
- **Email**: desarrollo@prensa.com

---

*Documento actualizado: Febrero 2026*
*Versión del Sistema: OC15*
