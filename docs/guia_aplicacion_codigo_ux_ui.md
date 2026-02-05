# Guía de Casos de Aplicación — Inventario y Movimientos (Solo Blíster)

Fecha: 31-01-2026  
Responsable: Equipo de desarrollo / Cliente  
Estado: Referencia funcional (para diseño y código)

Referencias:
- Lógica general: [docs/actualizacion_logica_inventario.md](docs/actualizacion_logica_inventario.md)
- Diagrama relacional: [docs/diagrama_relacional.txt](docs/diagrama_relacional.txt)
- Servicios: [`app\Services\InventarioService`](app/Services/InventarioService.php) · (propuesto) `app\Services\MovimientosService.php`
- Controladores: `app/Http/Controllers/InventarioController.php`, `app/Http/Controllers/MovimientosController.php`
- Rutas: [routes/web.php](routes/web.php)

## Objetivo de la guía
Reunir escenarios de uso end-to-end para:
- Asegurar que el backend aplique reglas de negocio (solo blíster, FEFO, Odonto solo insumos).
- Diseñar formularios claros y bonitos en Movimientos e Inventario.
- Producir reportes coherentes (10.1 Inventario, 10.2 Salidas) sin pedir datos personales.

## Contexto operativo
- Unidad operativa (UM) para sólidos (medicamentos): **Blíster** (enteros; sin pastillas sueltas).
- Insumos: UM = **Unidad** (frasco, tubo, paquete), sin conversión a blíster.
- Destinos operativos: Principal/PPAL, ACI, Agro, Odontología (Odonto solo insumos).
- Periodicidad de reportes: mensuales, trimestrales, semestrales, anuales.

## Caso A — Entrada por lote (Central)
Objetivo: registrar un lote nuevo con su contenido por blíster y cantidad en blíster (para medicamentos) o cantidad en unidad (para insumos).

Formulario (UX):
- Producto (typeahead con chip "MEDICAMENTO" | "INSUMO")
- Lote (texto), Fecha de vencimiento (date)
- Contenido por blíster (input numérico; **solo visible para medicamentos**)
- Cantidad (blíster para medicamentos, unidad para insumos)
- Observaciones

Reglas:
- Medicamentos: UM = Blíster; prohibido registrar pastillas individuales.
- Insumos: UM = Unidad; no se captura contenido por blíster.
- El contenido por blíster puede variar **por lote** del mismo producto.
- Si se selecciona un lote existente con contenido definido, ese valor se bloquea (no editable) para preservar trazabilidad.
- Stock se suma al Almacén Central por combinación `producto + lote + fecha_vencimiento + um_operativa`.

Validaciones:
- Medicamentos: obligatorios (producto, lote, vencimiento, contenido por blíster, cantidad).
- Insumos: obligatorios (producto, lote, vencimiento cuando aplica, cantidad); `contenido_por_blister` oculto.
- Fecha de vencimiento válida y posterior a hoy.
- Cantidad positiva (entera, en su UM operativa).
- Si se intenta usar un lote existente con un contenido por blíster distinto, la UI y el backend bloquean el registro y piden crear un nuevo lote.

Impacto en BD:
- `inventarios` (por lote): `producto_id`, `lote`, `fecha_vencimiento`, `um_operativa` (`blister`|`unidad`), `contenido_por_blister` (entero para blíster, `NULL` para unidad), `cantidad`.
- `movimientos`: tipo `ingreso` asociado siempre a `inventario_id` (trazabilidad por lote y UM operativa).

## Caso B — Distribución Central → Destino
Objetivo: registrar envíos desde Central hacia un destino (sin consumo real de stock), para trazabilidad y reportes.

Formulario (UX):
- Destino (select: PPAL, ACI, Agro, Odont)
- Producto (typeahead), Lote(s) (lista orden FEFO con badges de vencimiento)
- Cantidad (blíster)
- Observaciones

Reglas:
- **No descuenta del total global**: la distribución no modifica `inventarios` ni `productos.stock`; solo registra cuánto se ha enviado a cada destino.
- Valida que exista saldo suficiente global (no se puede distribuir más de lo disponible sumando todos los lotes compatibles).
- Odonto solo puede recibir insumos (bloqueo o advertencia).

Validaciones:
- Stock suficiente en Central (por producto y lote).
- Bloqueo si producto es medicamento y destino = Odonto.

Impacto en BD:
- Movimientos (tipo `egreso`, `modalidad=distribucion`): registra solo el envío desde Central hacia `destino_id` sin tocar inventario.
- Los saldos físicos disponibles para consumo siguen estando en `inventarios` (por lote); las distribuciones se usan para reportes agregados por destino (por ejemplo, un botón "DISTR" en acciones para ver total enviado a cada área).

## Caso C — Consumo (Salida real) desde Destino
Objetivo: registrar entrega a beneficiario sin datos personales, aplicando FEFO.

Formulario (UX):
- Destino origen (desde donde se entrega)
- Tipo de identificación (select: estudiante | trabajador | profesor | comunidad)
- Cantidad (blíster; stepper 1–10)
- Sexo (select)
- Producto; Lote por FEFO (opción manual solo si el rol lo permite)
- Observaciones (opcional)

Reglas:
- Aplicar FEFO para elegir lote no vencido con vencimiento más próximo.
- Odonto solo insumos (medicamentos → 0/no permitido).

Validaciones:
- Stock suficiente en el destino (producto y lote).
- FEFO aplicado (salvo override con rol).
- No vencido (o requiere permiso y razón).

Impacto en BD:
- Movimiento tipo `egreso` con `modalidad=consumo` (cantidad en UM operativa).
- Captura de `tipo_identificacion` y `sexo` en `movimientos`.

## Caso D — Devolución (Destino → Central)
Objetivo: retornar blíster al Central.

Formulario (UX):
- Destino (origen)
- Producto, Lote(s)
- Cantidad (blíster)
- Motivo (select: sobrante, reasignación, etc.)
- Observaciones

Reglas/validaciones:
- Stock suficiente en el destino.
- Trazabilidad por lote.

Impacto en BD:
- Movimiento tipo `egreso` con `modalidad=distribucion` desde el Destino hacia Central (devolución).

## Caso E — Ajuste (merma/daño/corrección)
Objetivo: corregir stock por daño, pérdida o ajuste administrativo.

Formulario (UX):
- Almacén/Destino
- Producto, Lote(s)
- Cantidad (blíster)
- Tipo de ajuste (merma|daño|corrección)
- Observaciones (obligatorias)

Validaciones:
- Roles con permiso.
- Evidencia/motivo obligatorio.

Impacto en BD:
- Movimiento: `ajuste` con detalle de motivo.
- Bitácora obligatoria.

## Caso F — Reporte 10.1 Inventario (Matriz por destino)
Objetivo: emitir inventario a fecha de corte, por destino y total.

Encabezado:
- Título: INVENTARIO DE MEDICAMENTOS E INSUMOS
- Periodo y fecha de corte (último día del periodo)

Columnas:
- Descripción, Presentación, UM, Principal, ACI, Agro, Odont, Central, Total.

Reglas:
- UM consistente (blíster para sólidos; unidad para insumos).
- Odonto: medicamentos = 0.

Cálculo:
- Sumar existencias por destino a fecha de corte desde `inventarios` (por lote) agregados por producto.

## Caso G — Reporte 10.2 Salidas — FARMACIA INTERNA
Objetivo: informar consumos del periodo por destino con métricas de beneficiarios.

Columnas:
- Servicios Médicos, Medicamentos entregados (blíster), Beneficiarios, F, M, EST, TRAB, COM, Total.

Cálculo:
- Fuente: `movimientos` tipo `consumo` en rango de fechas.
- Medicamentos entregados: suma de `cantidad` para `tipo_producto = medicamento`.
- Beneficiarios: conteo de movimientos (sin datos personales; 1 movimiento = 1 beneficiario).
- F/M/EST/TRAB/COM: conteos por `sexo` y `tipo_identificacion`.
- Odonto: medicamentos entregados = 0; puede haber insumos en sus plantillas específicas.

Plantillas de insumos (XLS):
- Insumos varios, Inyectadoras, Guantes (ver [docs/actualizacion_logica_inventario.md](docs/actualizacion_logica_inventario.md), sección 10.2.1).

## UX/UI — Componentes comunes y patrones
Componentes:
- Select de Destino con regla Odonto (solo insumos).
- Typeahead de Producto.
- Lista de Lotes orden FEFO con badges: Próximo a vencer, Vencido, y etiqueta "1 blíster = N" cuando el lote tiene `contenido_por_blister`.
- Stepper de Cantidad (1–10) en su UM operativa (blíster para medicamentos, unidad para insumos).
- Banner fijo: “Operamos solo en blíster” para medicamentos y “Operamos en unidad” para insumos, en Entrada y Consumo.
- Tarjeta Resumen del movimiento (producto, lote, destino, cantidad).

Páginas:
- Movimientos: pestañas Entrada | Distribución | Consumo | Devolución | Ajuste.
- Inventario: matriz por destino (global) + vista por almacén con detalle opcional por lote.
- Reportes: 10.1 Inventario, 10.2 Salidas (PDF/XLSX), con filtros de periodo y destinos.

Estilos y paleta:
- Adoptar la paleta del sistema para los tabs usando variables CSS (`--color-orange-500/600/400`) con fallback a naranjas actuales.
- Añadir iconos (Font Awesome) en las pestañas: Entrada (plus), Distribución (share), Consumo (user), Ajuste +/− (plus/minus circle).
- Badges dentro de tabs: "DESTINOS" para Distribución y "BENEFICIARIOS" para Consumo, con pill translúcido sobre fondo naranja.

Accesibilidad y rendimiento:
- Teclado (TAB/ENTER), tooltips, mensajes claros.
- Paginación server-side y filtros.
- Caché para agregaciones de reportes por periodo.

## Validaciones y errores (UX)
- Errores inline: stock insuficiente, producto bloqueado para Odonto, lote vencido.
- Confirmaciones con resumen antes de registrar movimiento.
- Idempotencia: evitar duplicados bajo reintentos (clave única interna).
- Mensajes consistentes y neutrales (sin tecnicismos en exceso).

## Mapeo a modelo de datos
- `productos`: tipo_producto (medicamento|insumo), categoría_inventario (segmento: general|odontologia), forma/UM (ver política de catálogo).
- `inventarios` (por lote): `producto_id`, `lote`, `fecha_vencimiento`, `um_operativa` (`blister`|`unidad`), `contenido_por_blister` (entero sólo para blíster), `cantidad`, `stock_minimo`, `estado`.
- `movimientos`: tipo, modalidad, destino_id, inventario_id (lote, cuando aplica), cantidad (en la UM operativa), tipo_identificacion, sexo, fecha, observaciones.

Relaciones y restricciones:
- FEFO aplicado en consumo; no consumir vencido por defecto; FIFO dentro de la misma fecha.
- Odonto solo insumos; rechazar medicamentos.
- `movimientos.inventario_id` recomendado siempre (trazabilidad por lote).
- No se permite modificar `lote` ni `fecha_vencimiento` de un inventario existente. La `um_operativa` y el `contenido_por_blister` solo pueden definirse la primera vez (pasando de `NULL` a valor); si ya tenían valor, no se pueden cambiar. Ante errores se crean nuevos lotes y se ajusta stock con movimientos de ajuste.

## Casos de borde y decisiones
- Presentaciones mixtas (jarabe vs crema vs blíster): se tratan como productos distintos; no se mezclan en filas de reporte.
- Blíster mixtos (10 y 12): consolidado con nota “Blíster 10/12” o “Detallado por lote” si se requiere auditoría.
- Devoluciones y ajustes: requieren motivo y rol con permiso.
- Destinos con alias (BÁSICO/AREA/E. BRACHO): normalizar en catálogo y mostrar alias en reportes.

## Wireframes (descripción rápida)
- Movimientos:
  - Pestañas superiores con iconos y títulos claros.
  - Columna izquierda: formulario; derecha: tarjeta resumen y stock antes/después.
- Inventario:
  - Tabla matriz con columnas por destino y total; filtros arriba; badges de vencimiento.
- Reportes:
  - Selector de periodo/fechas; botones Exportar PDF/XLSX; preview tabla.

## UAT — Checklist por caso
- Entrada: se registra lote con contenido blíster distinto y suma correctamente al Central.
- Distribución: bloquea medicamentos a Odonto; mueve stock Central → Destino por lote.
- Consumo: aplica FEFO, rechaza vencido (salvo rol), registra sexo y tipo_identificación.
- Devolución/Ajuste: roles, motivos y bitácora OK.
- Reporte 10.1: cuadra con inventario a fecha de corte; UM consistente; Odonto=0 en medicamentos.
- Reporte 10.2: métricas correctas por destino; Beneficiarios = movimientos; Odonto sin medicamentos.

## Métricas y performance
- Índices sugeridos: movimientos(fecha, destino_id, producto_id, tipo, modalidad), inventarios(producto_id, fecha_vencimiento), destinos(codigo).
- Caché de reportes por periodo (invalidación al registrar nuevos consumos).
- Paginación y filtros en inventario y movimientos.

## Glosario rápido
- Beneficiarios: conteo de entregas (no personas únicas).
- Medicamentos entregados: suma de blíster en consumos con `tipo_producto = medicamento`.
- Insumos entregados: suma de unidades en consumos con `tipo_producto = insumo`.
- Central/Depósito: almacén principal; no realiza consumo.

## Criterios de aceptación (resumen)
- Operación en blíster para sólidos; sin pastillas sueltas.
- Odontología solo insumos (en distribución y consumo).
- FEFO en consumo; trazabilidad por lote.
- Reportes 10.1 y 10.2 calculados desde `movimientos`/`inventarios` con UM consistente.
