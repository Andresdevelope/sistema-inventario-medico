# Migración a inventario por lotes (lote + fecha de vencimiento + unidad operativa)

Este documento guía la adopción del manejo de inventario por **lotes**, preservando la **fecha de vencimiento**, la **unidad operativa (blíster o unidad)** y evitando sobreescrituras por movimientos.

## Resumen de cambios

- Los movimientos de **ingreso** ahora registran stock por combinación: `producto_id + lote + fecha_vencimiento` y fijan la unidad operativa del lote mediante los campos `um_operativa` y `contenido_por_blister`.
- Para medicamentos, la unidad operativa es **blíster** (`um_operativa = 'blister'`) y se exige `contenido_por_blister` (entero > 0) por lote.
- Para insumos, la unidad operativa es **unidad** (`um_operativa = 'unidad'`) y `contenido_por_blister` es siempre `NULL`.
- Los **egresos** consumen por **FEFO** (vence primero) y **FIFO** dentro de la misma fecha de vencimiento.
- No se permite mezclar en un mismo lote distintas combinaciones de `um_operativa` y `contenido_por_blister`; si un lote ya tiene esos atributos fijados, un nuevo ingreso con valores distintos será rechazado y se debe crear un lote nuevo.
- Se mantiene el **índice lógico** por combinación (`producto_id`, `lote`, `fecha_vencimiento`); un mismo lote/vencimiento debe tener siempre la misma `um_operativa` y `contenido_por_blister`.
- La UI de **Movimientos** incluye campo `lote`, `fecha_vencimiento` y, para medicamentos, `contenido_por_blister` en ingresos y ajustes positivos, alineado con `InventarioService::procesarMovimiento`.

## Pasos para desplegar

1. Asegurar que la base de datos esté disponible (MySQL).  
2. Ejecutar migraciones:

```bash
php artisan migrate
```

3. Limpiar cachés (opcional):

```bash
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

> Si `cache:clear` falla por la conexión a MySQL, repita el comando cuando el servicio esté disponible.

## Consideraciones de datos

- Si existen **registros duplicados** con la misma combinación (`producto_id`, `lote`, `fecha_vencimiento`), la migración del índice único puede fallar.  
  - Solución: consolidar duplicados sumando cantidades en un solo registro y eliminar los restantes, o asignar `lote` distinto.
- Si anteriormente se usaba `productos.fecha_vencimiento` de forma operativa, se recomienda **mostrarlo sólo como informativo** y usar el «vencimiento más próximo» calculado a partir de `inventarios`.

## Buenas prácticas

- Siempre registrar **Número de lote**, **Fecha de vencimiento** y, para medicamentos, **Contenido por blíster** en **ingresos** y **ajustes positivos**.  
- En caso de error de captura (ej. se digitó mal el contenido por blíster), crear un **nuevo lote** con los datos correctos y ajustar el lote anterior (ajuste negativo/positivo) sin editar sus atributos críticos (`lote`, `fecha_vencimiento`, `um_operativa`, `contenido_por_blister`).
- Reportes deben basarse en inventarios (no en el campo vencimiento del producto) para caducidad y stock real.

## Cómo verificar

1. Crear un ingreso con `lote = L-TEST-001`, `fecha_vencimiento = 2026-01-31`, `um_operativa = blister` y `contenido_por_blister = 10`, cantidad = 5 blíster.  
2. Crear otro ingreso del mismo producto con `lote = L-TEST-002`, `fecha_vencimiento = 2026-02-28`, `contenido_por_blister = 5`, cantidad = 5 blíster.  
3. Registrar un egreso: el sistema consumirá primero del lote que **vence antes** (L-TEST-001).  
4. Intentar editar el lote, fecha de vencimiento, unidad operativa o contenido por blíster de un inventario existente debe lanzar error.  
5. Intentar registrar un ingreso a un lote existente con un `contenido_por_blister` distinto debe ser rechazado (tanto en la UI como en el backend).

## Impacto esperado

-- Mayor trazabilidad sanitaria (cada lote conserva su vencimiento).  
-- Egresos más seguros (evitan caducar stock en almacén).  
-- Menos riesgos de sobreescritura accidental de vencimientos.

## Historial de implementación

- 31-01-2026: definición funcional de migración a inventario por lotes (este documento).
- 31-01-2026: migración `2026_01_31_000000_add_modalidad_y_beneficiario_a_movimientos.php` añade `modalidad`, `tipo_identificacion` y `sexo` a `movimientos` para soportar los reportes de consumo.
- 04-02-2026: migración `2026_02_04_000000_add_um_y_contenido_por_blister_to_inventarios_table.php` añade `um_operativa` y `contenido_por_blister` a `inventarios`, materializando la política de **solo blíster** para medicamentos.
- 04-02-2026: actualización de `InventarioService::procesarMovimiento` para:
  - Fijar `um_operativa` y `contenido_por_blister` en el primer ingreso/ajuste del lote.
  - Validar que no se mezclen distintos contenidos por blíster en el mismo lote.
  - Aplicar FEFO/FIFO en consumos y respetar las reglas de Odontología (solo insumos) y de no consumo desde Central.
