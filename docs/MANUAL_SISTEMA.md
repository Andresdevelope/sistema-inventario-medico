# Manual del Sistema — Inventario Médico (técnico)

Este documento recoge la información técnica que usa el sistema: arquitectura, rutas, modelos, servicios, reglas de negocio y configuraciones claves.

## 1. Arquitectura general
- Framework: Laravel (PHP).
- Patrón: MVC tradicional con servicios para lógica compleja (`app/Services`).
- Carpetas clave:
  - Controladores: `app/Http/Controllers` (p.ej. `CategoriaController.php`, `ProductoController.php`, `MovimientosController.php`, `AuthController.php`).
  - Modelos: `app/Models` (p.ej. `Producto.php`, `Inventario.php`, `Movimiento.php`, `Categoria.php`).
  - Servicios: `app/Services` (`InventarioService.php`, `ReportesMovimientosService.php`).
  - Configuración: `config/` (incluye `inventario.php`, `services.php` para reCAPTCHA).
  - Rutas: `routes/web.php`

## 2. Rutas y middleware relevantes (resumen)
- Autenticación y perfil: `/login`, `/register`, `/recover`, `/perfil`.
- Dashboard: `GET /dashboard` (middleware `auth`).
- Categorías: `Route::resource('categorias', CategoriaController::class)` + vistas y endpoints AJAX (`categorias-listar`, `/categorias/{id}/dependencias`, `/subcategorias/by-categoria/{id}`). Middleware de permiso: `permission:categorias.*`.
- Productos: `Route::resource('productos', ProductoController::class)` y `GET /productos/buscar`.
- Movimientos: `GET /movimientos`, `POST /movimientos`, `GET /movimientos/inventarios/{productoId}`, `GET /movimientos/distribuciones/{producto}`.
- Inventario: `GET /inventario` (index view).
- Reportes: `GET /reportes` y `GET /reportes/export-pdf/*`.
- Usuarios (admin): rutas protegidas por `is_admin` y permisos específicos.
- Notificaciones: rutas con `throttle` para proteger del abuso.

Ver el archivo `routes/web.php` para la lista completa y los nombres de rutas.

## 3. Modelos y campos principales
(Resumen basado en uso en controladores/servicios — revisa migraciones en `database/migrations` para el detalle completo)

- Producto (`app/Models/Producto.php`)
  - Campos notables: `id`, `nombre`, `codigo`, `presentacion`, `unidad_medida`, `tipo_producto` (medicamento|insumo), `categoria_inventario`, `categoria_id`, `subcategoria_id`, `proveedor_id`, `stock`, `stock_minimo`, `fecha_ingreso`, `fecha_vencimiento`, `created_by`, `updated_by`.

- Inventario (`app/Models/Inventario.php`)
  - Campos: `id`, `producto_id`, `lote`, `cantidad`, `fecha_vencimiento`, `um_operativa`, `contenido_por_blister`, `stock_minimo`, `estado`, `created_at`, `updated_at`.

- Movimiento (`app/Models/Movimiento.php`)
  - Campos: `id`, `producto_id`, `tipo` (ingreso|egreso|ajuste_pos|ajuste_neg), `modalidad`, `cantidad`, `fecha`, `inventario_id`, `entrada`, `salida`, `destino_id`, `motivo`, `observaciones`, `usuario_id`.

- Categoria / Subcategoria / Proveedor / Destino / User / Bitacora: modelos de soporte con campos estándar.

## 4. Reglas de negocio críticas (implementadas en `InventarioService`)
- Validación de fecha de vencimiento: no se permiten ingresos/ajustes positivos con fecha de vencimiento pasada.
- Bloqueos por vencimiento:
  - Consumo (`modalidad == 'consumo'`): siempre bloqueado si lote vencido.
  - Distribución: comportamiento configurable vía `config/inventario.php` (clave `bloquear_vencidos_distribucion`).
  - Ajuste negativo: bloqueo configurable `bloquear_vencidos_ajuste_neg`.
- Compatibilidad por tipo:
  - Medicamento ↔ `um_operativa = 'blister'` y `contenido_por_blister` obligatorio.
  - Insumo ↔ `um_operativa = 'unidad'`.
- FEFO/FIFO: selección de inventarios para consumo ordenada por `fecha_vencimiento` asc (NULL al final) y `created_at` asc.
- Sincronización de stock: si existen registros en `inventarios` para un producto, `productos.stock` se sobrescribe con la suma de inventarios para evitar desajustes.
- Distribuciones: no modifican inventarios locales (registran envíos históricos), salvo comprobaciones de saldo para evitar sobre-envíos.

## 5. Validaciones y sanitización (controladores)
- Entrada de texto: funciones de normalización y detección de texto sospechoso (evitar entradas solo numéricas, repeticiones, cadenas sin vocales).
- Productos: reglas estrictas para nombre, código, presentación, fechas y stock.
- Usuarios: políticas de contraseña (mínimo 16), sanitización y respuestas de seguridad hasheadas.

## 6. Servicios importantes
- `App\Services\InventarioService`
  - Método principal: `procesarMovimiento(array $data)` — centraliza lógica de ingreso/egreso/ajuste con transacciones, locks y creación de movimientos e inventarios.
  - Funciones auxiliares: `getInventariosFefoFifo`, `findOrCreateInventario`, validaciones y sincronización de stock.

- `App\Services\ReportesMovimientosService`
  - Funciones: `resumen`, `inventarioMatrizPorDestino`, `detalle`, `salidasFarmaciaInterna`, `evolucionMensual`.
  - Usa caching de 10 minutos para algunos reportes (`Cache::remember` con TTL 600s).

## 7. Configuraciones y políticas
- `config/inventario.php` — contiene parámetros operativos como `bloquear_vencidos_distribucion`, `bloquear_vencidos_ajuste_neg`, `max_admins`.
- `config/services.php` — configuración de reCAPTCHA (`services.recaptcha.enabled`, `site_key`, `secret`).
- Throttle: rutas sensibles usan `throttle` (login, recover, notificaciones).

## 8. Seguridad
- Autenticación: sistema propio con hasheo de contraseñas (Hash::make) — respeta salt y bcrypt/argon según Laravel config.
- CSRF: formularios usan token; logout exige POST.
- Permisos: middleware `permission:*` aplicado por acción para controlar acceso granular.
- Registro de auditoría: `Bitacora` para eventos críticos.

## 9. Dependencias y requisitos
- Ver `composer.json` para paquetes PHP (Laravel, Carbon, DomPDF, etc.).
- Extensiones PHP requeridas listadas en `docs/extensiones_php_requeridas.txt`.

## 10. Migraciones y estructura de BD
- Revisar `database/migrations` para el esquema exacto. Migraciones en este proyecto crean tablas: `users`, `bitacora`, `productos`, `inventarios`, `movimientos`, `categorias`, `subcategorias`, `proveedores`, `destinos`, `permissions`.

## 11. Observaciones operativas y recomendaciones
- Mantener coherencia entre `productos.stock` e `inventarios` — preferir operar mediante movimientos para que los inventarios queden consistentes.
- Política de bloqueo de vencidos debe definirse según normativas locales y ajustarse en `config/inventario.php`.
- Hacer backups periódicos de la base de datos antes de tareas masivas (importaciones/bajas masivas).
- Para generación de reportes grandes: ajustar tiempo de ejecución y memoria PHP o generar en batch asíncrono.

## 12. Archivos y puntos a revisar para auditoría o despliegue
- Rutas: `routes/web.php`
- Lógica inventario: `app/Services/InventarioService.php`
- Lógica reportes: `app/Services/ReportesMovimientosService.php`
- Controladores principales: `app/Http/Controllers/` (ej. `MovimientosController.php`, `ProductoController.php`, `CategoriaController.php`, `AuthController.php`).
- Configs: `config/inventario.php`, `config/services.php`.

---

Si quieres, puedo:
- Extraer automáticamente la lista completa de migraciones y tablas con sus columnas y añadirlas aquí.
- Generar diagramas ER (mermaid) a partir de las migraciones.
- Preparar un PDF técnico y un PDF del manual de usuario con secciones y capturas.

Indícame cuál de esas acciones quieres que haga a continuación.