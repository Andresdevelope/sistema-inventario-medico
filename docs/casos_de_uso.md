# Sistema de Inventario Médico — Casos de Uso

Fecha: 23/10/2025
Autor: Equipo Servicios Médicos

## Objetivo
Documentar de forma completa los actores, reglas de negocio y casos de uso del sistema de Inventario Médico, incluyendo trazabilidad por lote, áreas de salida/entrada, KPIs de inventario y exportaciones, diferenciando responsabilidades de Administrador y Operador.

## Alcance
- Gestión de catálogo: categorías, subcategorías, productos y proveedores.
- Gestión de inventario por lotes (tabla `inventarios`) y movimientos (tabla `movimientos`).
- Registro de entradas, salidas por área (principal, acinf, agroalimentación, odontología) y ajustes.
- Consultas, KPIs del inventario, filtros y exportación CSV.
- Reportes operativos (tabla `reportes`, base para consultas agregadas).
- Administración de usuarios, roles y bitácora de auditoría.

## Actores
- Administrador: acceso total, crea/gestiona usuarios y roles, configura reglas, aprueba ajustes críticos, ve bitácora y reportes.
- Operador/Almacén: opera catálogo, inventario y movimientos; consulta reportes. No gestiona usuarios.
- Auditor/Visor: acceso de solo lectura a inventario, movimientos y reportes.
- Sistema externo (futuro): integraciones para import/export.

## Supuestos y contexto técnico
- Framework: Laravel 10+; UI con Blade + Bootstrap.
- BD: MySQL (XAMPP). Migraciones activas y versionadas.
- Autenticación por usuario/contraseña. Roles: admin, operador, auditor.
- Inventario por lotes: los movimientos referencian `inventario_id` para trazabilidad.
- Validaciones con feedback tipo toast; CSV de inventario disponible.

## Vocabulario
- Producto/Medicamento: ítem almacenable (tabla `productos`) con `stock_minimo`.
- Lote: registro en `inventarios` (por producto), con stock del lote y metadatos (por ejemplo: lote, proveedor, fechas si aplica).
- Movimiento: registro en `movimientos` que afecta stock del lote (`inventario_id`) con `tipo` (entrada/ingreso, salida/egreso, ajuste), `salida`/`entrada` (área u origen), `cantidad`, `motivo`, `fecha`, `usuario_id` y `observaciones`.
- Bitácora: acciones relevantes por usuario (crear, editar, mover stock, exportar).

## Reglas de negocio clave
1) Stock no negativo: salidas requieren disponibilidad suficiente en el lote seleccionado.
2) Stock mínimo: alertas visuales cuando `stock_actual` < `stock_minimo` del producto.
3) Trazabilidad por lote: todo movimiento debe guardar `inventario_id` (si aplica) y `usuario_id`.
4) Áreas de salida/entrada controladas: {principal, acinf, agroalimentacion, odontologia}.
5) Integridad referencial: `movimientos.producto_id` → `productos.id`; `movimientos.inventario_id` → `inventarios.id` (nullable en casos excepcionales); `movimientos.usuario_id` → `users.id`.
6) Exportaciones: la exportación CSV respeta filtros y no expone datos sensibles.
7) Auditoría: cambios críticos (usuarios, stock, ajustes) se registran en bitácora.

---

## Casos de uso

### CU-01 Autenticación de usuario
- Actor: Administrador u Operador
- Precondiciones: Usuario registrado; sistema operativo.
- Disparador: Acceso a /login.
- Flujo principal:
  1. Ingresa email/contraseña.
  2. El sistema valida credenciales.
  3. Redirige a Dashboard.
- Alternos:
  - A1: Credenciales inválidas → muestra toast de error y permanece en login.
- Poscondiciones: Sesión iniciada; se registra login en bitácora.

### CU-02 Ver Dashboard y KPIs
- Actor: Operador/Administrador
- Disparador: Acceso a /dashboard o /inventario.
- Flujo principal:
  1. El sistema muestra KPIs: total productos, stock bajo, movimientos recientes.
  2. El usuario puede ir a filtros/consultas.
- Poscondiciones: Consulta de solo lectura.

### CU-03 Gestión de categorías y subcategorías
- Actor: Operador/Administrador
- Precondiciones: Autenticado.
- Flujo principal:
  1. Crear/editar/eliminar categoría.
  2. Crear/editar/eliminar subcategoría asociada.
  3. Listar y filtrar.
- Reglas: nombres únicos; subcategoría requiere categoría válida.
- Auditoría: registrar altas/bajas.

### CU-04 Gestión de proveedores
- Actor: Operador/Administrador
- Flujo principal: Alta/edición/baja de proveedor; búsqueda y selección en formularios de producto.
- Reglas: Datos básicos requeridos (nombre/contacto) y unicidad razonable.

### CU-05 Gestión de productos/medicamentos
- Actor: Operador/Administrador
- Precondiciones: Categoría/subcategoría y proveedor existentes.
- Flujo principal:
  1. Crear producto con nombre, unidad/presentación, categoría/subcategoría, proveedor y `stock_minimo`.
  2. Listar, buscar, filtrar (por categoría, proveedor, texto).
  3. Editar datos del producto; eliminar si no compromete integridad.
- Reglas: Código/nombre no duplicado; `stock_minimo` >= 0.
- Auditoría: cambios guardados en bitácora.

### CU-06 Consultar inventario por lotes
  1. Accede a Inventario.
  2. Filtra por categoría, subcategoría, proveedor, texto.
  3. Visualiza tabla con stock por producto y alertas por `stock_minimo`.
  4. Puede abrir detalle de lotes (inventarios) por producto para ver existencias por lote.

---

#### Ejemplo visual para Canva — Caso de uso: Consultar inventario por lotes

Puedes replicar este esquema en Canva usando rectángulos y conectores:

```
┌─────────────┐      ┌────────────────────────────┐
│  Operador   │─────▶│ Consultar inventario por   │
└─────────────┘      │         lotes              │
┌─────────────┐      └────────────────────────────┘
│Administrador│─────▶│                            │
└─────────────┘      │                            │
              │                            │
              │                            │
  ┌─────────────┐   │   ┌─────────────┐          │
  │Precondición │───┘   │Disparador   │──────────┘
  └─────────────┘       └─────────────┘
  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐
  │ Alternos    │──▶│Caso de uso  │──▶│Poscondición │
  └─────────────┘   └─────────────┘   └─────────────┘
```

**Ejemplo de contenido para cada bloque:**
- Actor: Operador, Administrador
- Precondición: Usuario autenticado
- Disparador: Acceso al módulo inventario
- Alternos: Sin datos → mensaje vacío
- Poscondición: Consulta realizada, sin cambios en BD

**Sugerencia de colores:**
- Actor: azul claro
- Caso de uso: verde o amarillo
- Precondición/Disparador/Alternos/Poscondición: gris o naranja

Puedes usar flechas o líneas para conectar cada bloque al caso de uso central. Así tendrás un diagrama claro y replicable para cualquier caso de uso.

### CU-07 Registrar entrada por lote
- Actor: Operador/Administrador
- Precondiciones: Producto existente; origen (`entrada`) definido (compra/donación/transferencia, etc.).
- Flujo principal:
  1. Selecciona producto.
  2. Ingresa datos de la entrada (cantidad, origen `entrada`, motivo, observaciones).
  3. Crea o incrementa un registro en `inventarios` (nuevo lote o sumar a lote aplicable).
  4. Inserta movimiento `tipo=entrada` con `inventario_id` del lote impactado.
- Alternos:
  - A1: Datos incompletos → toasts con validaciones; no se guarda.
- Poscondiciones: Stock del lote incrementado; movimiento registrado.

### CU-08 Registrar salida por lote y área
- Actor: Operador/Administrador
- Precondiciones: Lote (`inventario_id`) del producto con stock suficiente; área de `salida` definida {principal, acinf, agroalimentacion, odontologia}.
- Flujo principal:
  1. Selecciona producto y el sistema lista lotes disponibles (stock > 0).
  2. El usuario elige el lote (`inventario_id`) y el área de `salida`.
  3. Ingresa `cantidad` y `motivo` (consumo, traslado, etc.).
  4. Se valida disponibilidad; se descuenta stock del lote.
  5. Inserta movimiento `tipo=salida` con `inventario_id`, `salida` y metadatos.
- Alternos:
  - A1: Stock insuficiente → error y no descuenta.
  - A2: Área inválida → error de validación.
- Poscondiciones: Stock reducido; movimiento registrado y auditable.

### CU-09 Registrar ajuste de stock
- Actor: Administrador (operador con autorización)
- Precondiciones: Justificación (`motivo`) obligatoria.
- Flujo principal:
  1. Selecciona producto y lote.
  2. Ingresa cantidad (+/-) y motivo (pérdida, corrección de conteo, etc.).
  3. Aplica ajuste y registra movimiento `tipo=ajuste`.
- Reglas: Ajustes negativos no pueden dejar stock < 0.
- Auditoría: registrar usuario, fecha y observaciones.

### CU-10 Consultar movimientos
- Actor: Operador/Administrador/Auditor
- Flujo principal: Filtrar por fechas, producto, tipo, área (`salida`/`entrada`) y lote (`inventario_id`); ver detalle.
- Exportación: CSV/descarga de reporte desde filtros aplicados.

### CU-11 Generar reportes
- Actor: Operador/Administrador/Auditor
- Reportes típicos:
  - Inventario actual (por producto y por lotes).
  - Movimientos por periodo (entradas, salidas, ajustes) con totales por área.
  - Alertas de stock mínimo.
  - Valorizació n de inventario (si se incorpora costo unitario).
- Flujo principal: Selecciona reporte, aplica filtros, visualiza/descarga.

### CU-12 Gestión de usuarios y roles (solo admin)
- Actor: Administrador
- Flujo principal: Crear/editar/eliminar usuarios; asignar rol (admin/operador/auditor).
- Reglas: Solo admin; no se puede auto-deshabilitar la única cuenta admin.
- Auditoría: todas las operaciones registradas.

### CU-13 Bitácora y auditoría
- Actor: Administrador/Auditor
- Flujo principal: Consultar acciones por usuario/rango de fechas (altas de productos, movimientos, exportaciones).
- Poscondición: Evidencia auditable del ciclo de vida del stock.

### CU-14 Configuración de stock mínimo
- Actor: Operador/Administrador
- Flujo principal: Definir/editar `stock_minimo` por producto; ver alertas en inventario.
- Regla: `stock_minimo` >= 0; cambios impactan KPIs.

---

## Modelo de datos (resumen)
- `productos`: id, nombre, categoria_id, subcategoria_id, proveedor_id, stock_minimo, ...
- `inventarios`: id, producto_id, stock (del lote), metadatos del lote (según diseño), timestamps.
- `movimientos`: id, producto_id, inventario_id (nullable), tipo (entrada/salida/ajuste), cantidad, motivo, fecha, usuario_id, observaciones, salida (área), entrada (origen), timestamps.
- `reportes`: estructura base para parametrizar/guardar definiciones de reportes.
- `categorias`, `subcategorias`, `proveedores`.
- `users` (usuarios), `bitacora` (auditoría).

## Consideraciones no funcionales
- Rendimiento: índices en claves foráneas ( , inventario_id, fecha, tipo); paginación en listados.
- Seguridad: auth obligatoria; autorización por rol; CSRF activo; sanitización de input.
- Usabilidad: toasts de validación; formularios con `old()` y mensajes claros; exportación optimizada.

## Diagrama de alto nivel (texto)
Login → Dashboard/KPIs → (Catálogo: Categorías/Subcategorías/Productos/Proveedores) → Inventario (consulta y CSV) → Movimientos (Entradas/Salidas/Ajustes por lote y área) → Reportes → Bitácora.

## Criterios de aceptación
- Los flujos CU-07/08/09 actualizan stock de lote y registran movimientos con `inventario_id` y `usuario_id`.
- Inventario muestra alertas por `stock_minimo` y permite exportar CSV con filtros.
- Solo Administrador gestiona usuarios y puede aprobar ajustes críticos.

## Notas
- Las áreas de salida se limitan a: principal, acinf, agroalimentacion, odontologia.
- La selección de lotes prioriza los que tienen stock > 0; se recomienda política FEFO si se maneja vencimiento por lote.
