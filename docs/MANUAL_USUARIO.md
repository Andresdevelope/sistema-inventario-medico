# Manual de Usuario — Sistema de Inventario Médico

Índice
- Acceso y Autenticación
- Recuperación de contraseña
- Dashboard / Inicio
- Perfil de usuario
- Gestión de categorías y subcategorías
- Gestión de productos (medicamentos/insumos)
- Gestión de proveedores
- Módulo de Movimientos (Ingresos / Egresos / Ajustes)
- Inventario y lotes
- Reportes
- Usuarios (admin)
- Notificaciones
- Bitácora

---

## Acceso y Autenticación
1. Pantalla de login
   - URL: `/login` (también la raíz `/` muestra la vista de autenticación).
   - Campos: usuario (username) y contraseña.
   - Requisitos: la cuenta debe existir y no estar bloqueada.
   - Política: tras 3 intentos fallidos el usuario queda bloqueado y sólo un administrador puede desbloquearlo.
   - Salida (logout): POST a `/logout` (protegido con CSRF).

2. Registro (si está habilitado)
   - Endpoint: `POST /register`.
   - Campos obligatorios: username, email, password, respuestas de seguridad (color, animal, padre).
   - ReCAPTCHA opcional: si `services.recaptcha.enabled` está activado, el formulario incluye reCAPTCHA v2.
   - Nota de seguridad: la contraseña mínima es de 16 caracteres con mayúscula, minúscula, número y símbolo.

## Recuperación de contraseña
- Vista: `/recover`.
- Flujo:
  1. Enviar email para solicitar token (`POST /recover/check-email`).
  2. Validar preguntas de seguridad (`POST /recover/check-security`).
  3. Verificar token enviado por correo (`POST /recover/verify-email-token`).
  4. Cambiar contraseña (`POST /recover/change-password`).
- Throttle aplicado (`throttle:recover`) para evitar abuso.

## Dashboard / Inicio
- URL: `/dashboard` (requiere `auth`).
- Muestra métricas principales: total de categorías, total de productos.
- Notas:
  - Algunas notificaciones y avisos pueden mostrarse luego del login (cache por usuario).

## Perfil de usuario
- Vista: `/perfil` (requiere `auth`).
- Acciones:
  - Validar seguridad (`POST /perfil/validar-seguridad`).
  - Cambiar contraseña (`POST /perfil/cambiar-contrasena`).

## Gestión de categorías y subcategorías
- Vista: `/categorias` (permiso `categorias.ver`).
- Crear categoría / subcategoría: `POST /categorias` (permiso `categorias.crear`).
  - Campos: `nombre_categoria` (requerido), `nombre_subcategoria` (opcional).
  - Reglas de validación: longitud 2–80, debe contener letras reales, evita entradas numéricas o ruido.
  - Respuesta JSON con `success` y objeto creado.
- Editar categoría: `PUT /categorias/{id}` (permiso `categorias.editar`).
- Editar subcategoría: acción `updateSubcategoria` (`PUT` a ruta personalizada en frontend).
- Eliminar: `DELETE /categorias/{id}` (permiso `categorias.eliminar`).
  - Si existen productos dependientes o subcategorías en uso, la operación falla con `422` y listado de dependencias.
- API útiles:
  - `GET /categorias-listar` → obtiene categorías con subcategorías en JSON.
  - `GET /subcategorias/by-categoria/{id}` → subcategorías por categoría (AJAX).
  - `GET /categorias/{id}/dependencias` → conteos de dependencias antes de eliminar.

## Gestión de productos (medicamentos / insumos)
- Listado: `GET /productos` (permiso `medicamentos.ver`)
  - Filtros: búsqueda, orden, paginación, filtro por categoría.
- Crear: `GET /productos/create` (vista) y `POST /productos`.
  - Campos clave: `nombre`, `codigo`, `presentacion`, `unidad_medida`, `tipo_producto` (medicamento|insumo), `categoria_inventario`, `categoria_id`, `subcategoria_id`, `proveedor_id`, `stock`, `stock_minimo`, `fecha_ingreso`, `fecha_vencimiento`.
  - Validaciones: códigos en mayúsculas, nombre con letras y máximo 4 números, stock entero 1–9999, fecha_vencimiento > fecha_ingreso y > hoy.
  - Al crear con stock > 0 se crea un inventario inicial (lote null) para reflejar stock.
- Editar: `PUT /productos/{id}` con validaciones similares.
  - Si se cambia fecha de vencimiento, se actualizan inventarios sin lote o con la fecha antigua.
- Ver detalle: `GET /productos/{id}`.
- Eliminar: `DELETE /productos/{id}` — sólo permitido si no hay movimientos; si hay inventarios sin movimientos, se eliminan antes.
- Búsqueda AJAX para movimientos: `GET /productos/buscar?q=...&tipo=...` retorna paginación mínima.

## Gestión de proveedores
- Rutas AJAX bajo `proveedores/ajax` (prefijo) para crear/editar/eliminar vía AJAX:
  - `POST /proveedores/ajax` crear (storeAjax)
  - `PUT /proveedores/ajax/{id}` actualizar (updateAjax)
  - `DELETE /proveedores/ajax/{id}` eliminar (destroyAjax)

## Módulo de Movimientos
- Vista: `GET /movimientos` (requiere permisos para operar movimientos).
- Tipos permitidos: `ingreso`, `egreso`, `ajuste_pos`, `ajuste_neg`.
- Modalidad en egresos: `consumo` o `distribucion`.
- Reglas básicas:
  - Ingresos y ajustes positivos requieren `fecha_vencimiento` y `lote`.
  - Egresos en modalidad `consumo` requieren datos de beneficiario: `tipo_identificacion` y `sexo`.
  - Distribuciones requieren seleccionar `destino`.
  - Validaciones de lote y motivo para evitar datos basura.
- Endpoint para registrar: `POST /movimientos`.
  - Centraliza la lógica en `InventarioService::procesarMovimiento`.
- Consultas auxiliares:
  - `GET /movimientos/inventarios/{productoId}` → lista de lotes (inventarios) por producto.
  - `GET /movimientos/distribuciones/{producto}` → resumen de distribuciones por destino.
  - `GET /consumo/historial` → historial de consumos (filtrable).

## Inventario y lotes
- Modelo: `Inventario` con campos claves: `producto_id`, `lote`, `cantidad`, `fecha_vencimiento`, `um_operativa`, `contenido_por_blister`, `stock_minimo`, `estado`.
- Principales reglas de negocio (servicio `App\Services\InventarioService`):
  - FEFO/FIFO: consumo prioriza fecha de vencimiento más próxima (FEFO), con NULL al final; y en empates created_at asc (FIFO).
  - Sincronización: si hay registros en `inventarios`, el campo `productos.stock` se sincroniza con la suma de inventarios.
  - Validaciones por tipo de producto: `medicamento` ↔ `um_operativa` = `blister` y exige `contenido_por_blister` > 0; `insumo` ↔ `unidad`.
  - Políticas para uso de lotes vencidos: consumo siempre bloqueado; distribución y ajustes pueden configurar bloqueo desde `config/inventario.php`.
  - Ajustes negativos y egresos consumen lotes por FEFO respetando compatibilidad y bloqueo.
  - Distribuciones historiadas no alteran inventario (registran envíos sin afectar cantidades locales).

## Reportes
- Vista/índice `GET /reportes`.
- Exportes a PDF:
  - Inventario completo: `GET /reportes/export-pdf/inventario`.
  - Consumo: `GET /reportes/export-pdf/consumo`.
  - Detalle consumo: `GET /reportes/export-pdf/detalle-consumo`.
- Servicios de generación: `App\Services\ReportesMovimientosService` — funciones: `resumen`, `inventarioMatrizPorDestino`, `detalle`, `salidasFarmaciaInterna`, `evolucionMensual`.

## Usuarios (administración)
- Rutas protegidas por middleware `is_admin` y permisos:
  - `GET /usuarios` (lista), `POST /usuarios` (crear), `PUT /usuarios/{id}` (actualizar), `DELETE /usuarios/{id}` (eliminar), `PUT /usuarios/{id}/unlock` (desbloquear cuenta).
  - `GET /usuarios/{id}/permisos` y `PUT /usuarios/{id}/permisos` para asignar permisos.
  - `GET /usuarios-lista` para AJAX.
- Restricciones: creación de admins limitada por `config('inventario.max_admins')`.

## Notificaciones
- Campana: `GET /notificaciones/movimientos` (throttle 20 por minuto) y marcar como leídas `POST /notificaciones/movimientos/leer`.

## Bitácora
- Registro automático de eventos importantes (login, login fallido, bloqueo, movimientos, acceso a módulos, CRUD de entidades).
- Vista (solo admin): `GET /bitacora`.

---

Notas finales
- Las rutas mencionadas requieren autenticación y permisos según la acción. Si te aparece "Acceso restringido", consulta con un administrador.
- Para acciones destructivas (eliminar categoría/producto) el sistema valida dependencias y evita pérdida accidental de datos.

Si quieres, puedo generar versiones en PDF o preparar capturas de pantalla y pasos detallados por pantalla para incluir en este manual.