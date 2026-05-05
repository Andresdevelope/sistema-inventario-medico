# Manual de Usuario — Sistema de Inventario Médico

Bienvenido al **Manual de Usuario** del Sistema de Inventario Médico UPTAG. Este documento está diseñado para guiar al personal médico, de almacén y administrativo en el uso eficiente y seguro de la plataforma.

---

## 📋 Índice
1. [Acceso y Autenticación](#1-acceso-y-autenticacion)
2. [Panel Principal (Dashboard)](#2-panel-principal-dashboard)
3. [Mi Perfil](#3-mi-perfil)
4. [Gestión de Categorías y Productos](#4-gestion-de-categorias-y-productos)
5. [Módulo de Movimientos](#5-modulo-de-movimientos)
6. [Gestión de Proveedores](#6-gestion-de-proveedores)
7. [Reportes y Exportación](#7-reportes-y-exportacion)
8. [Administración del Sistema](#8-administracion-del-sistema)

---

## 1. Acceso y Autenticación

El sistema requiere que todos los usuarios se autentiquen para garantizar la trazabilidad de las operaciones.

### 1.1 Iniciar Sesión
1. Ingresa a la URL del sistema. Por defecto, serás redirigido a la pantalla de **Login**.
2. Ingresa tu **Usuario** y **Contraseña**.
3. (Opcional) Si el sistema tiene activado el control de seguridad, deberás resolver el reCAPTCHA.
4. Haz clic en **Entrar**.

> [!WARNING]  
> **Política de Bloqueo:** Por razones de seguridad, si ingresas credenciales incorrectas 3 veces consecutivas, tu cuenta será **bloqueada**. Solo un Administrador del sistema podrá desbloquearla.

### 1.2 Recuperación de Contraseña
Si olvidaste tu contraseña:
1. Haz clic en **¿Olvidaste tu contraseña?** en la pantalla de inicio.
2. Ingresa tu correo electrónico asociado a la cuenta.
3. Responde correctamente a tus **Preguntas de Seguridad** (Color, Animal y Nombre del padre).
4. Recibirás un correo con un token de verificación de 6 dígitos.
5. Ingresa el token y establece tu nueva contraseña (mínimo 16 caracteres, incluyendo mayúsculas, minúsculas, números y símbolos).

---

## 2. Panel Principal (Dashboard)

Una vez que inicias sesión, accederás al **Dashboard**. Esta pantalla te proporciona un resumen rápido del estado del almacén.

- **Tarjetas de Resumen:** Visualiza rápidamente el total de productos registrados, categorías activas y movimientos recientes.
- **Notificaciones (Campana):** En la parte superior derecha, encontrarás un icono de campana. Aquí recibirás alertas automáticas (por ejemplo, si un producto está por agotarse o si hay lotes próximos a vencer).

---

## 3. Mi Perfil

En la sección de tu perfil, puedes gestionar tu información personal y credenciales.
- **Cambio de Contraseña:** Es recomendable cambiar tu contraseña periódicamente. Para hacerlo, primero deberás validar tus respuestas de seguridad.
- **Preferencias:** Ajustes básicos de la cuenta.

---

## 4. Gestión de Categorías y Productos

El inventario está organizado jerárquicamente para facilitar la búsqueda.

### 4.1 Categorías y Subcategorías
Las categorías agrupan los productos (ej. *Analgésicos*, *Material Quirúrgico*).
- **Crear:** Ve al módulo de Categorías y haz clic en "Nueva Categoría". Puedes añadir subcategorías al mismo tiempo.
- **Editar/Eliminar:** Usa los botones de acción en la tabla. 
> [!IMPORTANT]  
> No puedes eliminar una categoría si existen productos asociados a ella. Debes reasignar o eliminar los productos primero.

### 4.2 Productos (Medicamentos e Insumos)
El catálogo principal del sistema. Hay dos tipos de productos:
1. **Medicamentos:** Requieren control de lotes y fechas de vencimiento. Se gestionan por unidades operativas (ej. *Blíster*, *Caja*).
2. **Insumos:** Material médico (jeringas, gasas) que puede o no requerir un control de vencimiento estricto.

**Para registrar un producto:**
1. Ve a **Productos > Nuevo Producto**.
2. Completa la información básica: Nombre, Código, Presentación, Tipo.
3. Define el **Stock Mínimo**. Esto le dirá al sistema cuándo debe alertarte por escasez.
4. (Opcional) Puedes ingresar un stock inicial, lo cual generará automáticamente un movimiento de ingreso sin lote específico.

---

## 5. Módulo de Movimientos

El corazón del sistema. Aquí se registra absolutamente todo lo que entra y sale del almacén.

### Tipos de Movimientos
| Tipo de Movimiento | Descripción | Requisitos de captura |
| :--- | :--- | :--- |
| **Ingreso** | Recepción de nueva mercancía desde un proveedor. | Lote, Fecha de Vencimiento, Cantidad, Proveedor. |
| **Egreso (Consumo)** | Entrega de medicamentos/insumos a pacientes. | Lote automático (FEFO), Beneficiario, Tipo de Identificación. |
| **Egreso (Distribución)** | Envío de mercancía a otras áreas (ej. Emergencias). | Destino interno, Cantidad. |
| **Ajuste Positivo** | Corrección de inventario por sobrantes. | Lote, Vencimiento, Justificación. |
| **Ajuste Negativo** | Corrección por mermas, daños o pérdidas. | Lote automático, Justificación detallada. |

### Lógica de Lotes (FEFO/FIFO)
El sistema utiliza el método **FEFO** *(First Expired, First Out - Primero en Vencer, Primero en Salir)*. 
Cuando registras un **Consumo** o un **Ajuste Negativo**, el sistema **automáticamente** descontará la cantidad del lote que esté más próximo a vencer. No necesitas seleccionar el lote manualmente; el sistema protege el inventario para evitar que los medicamentos se caduquen en los estantes.

> [!CAUTION]  
> El sistema **bloquea automáticamente** la entrega (consumo) de lotes que ya hayan superado su fecha de vencimiento.

---

## 6. Gestión de Proveedores

Módulo para mantener una base de datos de los laboratorios y distribuidores.
- Permite registrar Nombre, RIF/NIT, Teléfono, Correo y Dirección.
- Se utiliza en los Movimientos de Ingreso para mantener la trazabilidad de origen de cada lote.

---

## 7. Reportes y Exportación

El sistema permite auditar el almacén mediante reportes exportables en formato PDF.

- **Inventario General:** Muestra el stock actual de todos los productos.
- **Inventario por Destinos:** Matriz que muestra cuánto se ha distribuido a cada área de la institución.
- **Historial de Consumos:** Reporte detallado de los egresos entregados a pacientes.
- **Evolución Mensual:** Gráficas y tablas para entender el flujo del almacén en un período de tiempo.

Para generar un reporte, ve a la sección **Reportes**, selecciona el tipo, aplica los filtros deseados y haz clic en **Exportar PDF**.

---

## 8. Administración del Sistema

*(Solo para usuarios con rol de Administrador)*

### 8.1 Gestión de Usuarios
- Permite crear nuevas cuentas para el personal.
- Asignación de roles y permisos granulares (ej. permitir ver productos pero no autorizar egresos).
- Desbloqueo de cuentas que hayan excedido el límite de intentos fallidos.

### 8.2 Bitácora de Auditoría
El sistema registra **todas** las acciones críticas en la base de datos (inicios de sesión, registros fallidos, movimientos de inventario, eliminación de registros).
- Los administradores pueden consultar la Bitácora para rastrear "quién hizo qué y cuándo". No se puede alterar ni borrar la bitácora.

---

> [!TIP]  
> **Recomendación Operativa:** Mantén siempre actualizados los stocks mínimos de los productos críticos. Revisa regularmente las notificaciones de la campana para anticiparte a la caducidad de los lotes.