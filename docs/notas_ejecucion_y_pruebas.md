# Notas de ejecución y pruebas — Distribución vs Consumo

Fecha: 31-01-2026

## Requisitos previos
- Migraciones al día:
  - Ejecutar `php artisan migrate` (ya aplicado en Batch 6).
- Sesión iniciada (las rutas de movimientos requieren `auth`).

## Rutas involucradas
- GET ` /movimientos` — UI (pestañas de movimientos)
- POST `/movimientos` — Registrar movimiento (Entrada/Egreso/Ajuste)
- GET ` /movimientos/inventarios/{productoId}` — Lotes por producto (para elegir FEFO / revisar vencimientos)

## Casos de prueba rápidos (POST /movimientos)

Enviar como formulario (x-www-form-urlencoded o JSON según frontend):

1) Entrada por lote (INGRESO)
- producto_id: <id_producto>
- tipo: ingreso
- lote: LOTE-A
- fecha_vencimiento: 2026-12-31
- contenido_por_blister (solo medicamentos): 10
- cantidad: 5
- observaciones: Lote inicial

Esperado:
- Crea/actualiza `inventarios` por (producto, lote, fecha_vencimiento, um_operativa) sumando +5 en la UM operativa.
- Para medicamentos: `um_operativa = blister`, `contenido_por_blister = 10`.
- Para insumos: `um_operativa = unidad`, `contenido_por_blister = NULL`.
- Crea `movimientos` con `tipo=ingreso` vinculado a `inventario_id` del lote.

2) Egreso — Distribución (Central → Destino)
- producto_id: <id_producto>
- tipo: egreso
- modalidad: distribucion
- destino_id: <id_destino_NO_CENTRAL>
- cantidad: 2
- inventario_objetivo_id: <id_inventario_lote> (opcional; si se omite aplica FEFO)

Esperado:
- Descuenta del inventario (Central) por lote.
- Crea `movimientos` con `tipo=egreso`, `modalidad=distribucion`.

3) Egreso — Consumo (entrega real)
- producto_id: <id_producto>
- tipo: egreso
- modalidad: consumo
- destino_id: <id_destino_operativo>
- tipo_identificacion: estudiante | trabajador | profesor | comunidad
- sexo: F | M | otro
- cantidad: 1

Esperado:
- Valida campos de beneficiario (tipo_identificacion, sexo).
- Si el área es Odontología, sólo permite productos con `tipo_producto = insumo`.
- Crea `movimientos` con `tipo=egreso`, `modalidad=consumo`, guarda beneficiario mínimo.

4) Bloqueos esperados
- Consumo desde CENTRAL: rechaza con mensaje “El consumo no puede originarse en CENTRAL”.
- Odontología + medicamento: rechaza con “Odontología sólo permite egresos de insumos”.
- Stock insuficiente: rechaza según tipo (egreso/ajuste_neg).

## Prueba de FEFO (consumo)
1. Ingresar 2 lotes del mismo producto:
  - Lote X con vencimiento 2026-04-30, `contenido_por_blister = 10`, cantidad 3 blíster.
  - Lote Y con vencimiento 2026-06-30, `contenido_por_blister = 10`, cantidad 3 blíster.
2. Realizar 1 consumo (egreso con modalidad=consumo) por cantidad 1.
3. Verificar en `movimientos` que el `inventario_id` restado corresponde al Lote X (vencimiento más próximo).

Caso adicional (mismas fechas, distinto contenido por blíster):
4. Intentar registrar un ingreso a un lote existente con la misma combinación `producto + lote + fecha_vencimiento` pero con `contenido_por_blister` distinto.
5. Esperado: el backend rechaza el movimiento con mensaje indicando que debe crearse un nuevo lote para preservar la trazabilidad.

## Verificaciones rápidas en la UI
- En Movimientos > pestaña Egreso:
  - Al elegir modalidad=consumo, aparecen campos de beneficiario (tipo_identificacion, sexo).
  - Al elegir destino “Odontología”, los productos de medicamentos muestran advertencia o bloqueo.
  - La lista de lotes aparece ordenada FEFO; muestra badges “Próximo a vencer” / “Vencido”.

## Consultas auxiliares (SQL orientativo)
- Últimos consumos por destino del mes:
```sql
SELECT destino_id, COUNT(*) AS beneficiarios, SUM(cantidad) AS total_blister
FROM movimientos
WHERE tipo = 'egreso' AND modalidad = 'consumo'
  AND fecha BETWEEN '2026-01-01' AND '2026-01-31'
GROUP BY destino_id;
```

- Desglose por sexo y tipo_identificacion:
```sql
SELECT destino_id, sexo, tipo_identificacion, COUNT(*) AS total
FROM movimientos
WHERE tipo = 'egreso' AND modalidad = 'consumo'
  AND fecha BETWEEN '2026-01-01' AND '2026-01-31'
GROUP BY destino_id, sexo, tipo_identificacion
ORDER BY destino_id;
```

## Criterios de aceptación (resumen)
- Distribución y Consumo separados vía `modalidad` en `movimientos`.
- Consumo exige `tipo_identificacion` y `sexo`.
- Bloqueo: CENTRAL no puede consumir; Odontología sólo insumos.
- FEFO aplicado en consumos; trazabilidad por `inventario_id`.

## Siguientes pasos
- UI: pestañas y validaciones en frontend.
- Reportes: 10.1 (Inventario) y 10.2 (Salidas) usando `modalidad=consumo` para métricas.
