# Migración a inventario por lotes (lote + fecha de vencimiento)

Este documento guía la adopción del manejo de inventario por **lotes**, preservando la **fecha de vencimiento** y evitando sobreescrituras por movimientos.

## Resumen de cambios

- Los movimientos de **ingreso** ahora registran stock por combinación: `producto_id + lote + fecha_vencimiento`.
- Los **egresos** consumen por **FEFO** (vence primero) y **FIFO** dentro de la misma fecha de vencimiento.
- No se permite modificar `lote` ni `fecha_vencimiento` en un registro de inventario existente (trazabilidad).
- Se añade un **índice único** en `inventarios(producto_id, lote, fecha_vencimiento)` para evitar duplicados.
- La UI de **Movimientos** incluye campo `lote` en ingresos.

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

- Siempre registrar **Número de lote** y **Fecha de vencimiento** en **ingresos**.  
- En caso de error de captura, crear un **nuevo lote** con los datos correctos y ajustar el lote anterior (ajuste negativo/positivo) sin editar sus atributos críticos.
- Reportes deben basarse en inventarios (no en el campo vencimiento del producto) para caducidad y stock real.

## Cómo verificar

- Crear un ingreso con `lote = L-TEST-001` y `fecha_vencimiento = 2026-01-31`.  
- Crear otro ingreso del mismo producto con `lote = L-TEST-002` y `fecha_vencimiento = 2026-02-28`.  
- Registrar un egreso: el sistema consumirá primero del lote que **vence antes**.  
- Intentar editar el lote o fecha de un inventario existente debe lanzar error.

## Impacto esperado

- Mayor trazabilidad sanitaria (cada lote conserva su vencimiento).  
- Egresos más seguros (evitan caducar stock en almacén).  
- Menos riesgos de sobreescritura accidental de vencimientos.
