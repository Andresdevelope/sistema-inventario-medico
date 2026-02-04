# Actualización de lógica de inventario: Solo Blíster y Distribución

Fecha: 31-01-2026  
Responsable: Equipo de desarrollo / Cliente  
Estado: Propuesto (a validar)

## 1) Resumen ejecutivo

- Unidad operativa = 1 blíster. El sistema NO trabaja con pastillas individuales; cualquier manejo de pastillas sueltas queda fuera del sistema (manual).
- Entradas por lote permanecen, pero se debe especificar el contenido por blíster (p. ej. 10, 12…). Este valor puede variar entre lotes del mismo producto.
- Salida se separa en dos momentos:
  - Distribución: transferencia Central → Destinos (ACI, Agroalimentación, Odontología, Principal). No es consumo; mantiene trazabilidad por lote.
  - Consumo (entrega real): registro mínimo sin datos personales, solo tipo de identificación, destino, cantidad en blíster (select 1–10) y sexo.
- Política de selección de lote: FEFO (First-Expire-First-Out) en el consumo.
- Regla de negocio: Odontología solo puede sacar insumos; no medicamentos.

## 2) Objetivos

- Estandarizar la unidad de conteo a blíster, evitando manejo de pastillas sueltas.
- Mantener trazabilidad por lote y destino.
- Aplicar FEFO para minimizar vencimientos.
- Simplificar el registro de consumo (sin datos personales identificables).
- Garantizar que Odontología opere exclusivamente con insumos.

## 3) Alcance

Incluye reglas de negocio, datos y validaciones, flujos, modelo de datos (alto nivel), cambios de formularios y reportes. No incluye aún cambios de código; es documento funcional.

## 4) Glosario

- Almacén Central: almacén principal de entrada de lotes.
- Destinos: ACI, Agroalimentación, Odontología, Principal.
- Distribución: transferencia logística Central → Destino(s). No es consumo.
- Consumo (salida real): entrega a un grupo poblacional desde un Destino.
- Blíster: unidad mínima de operación. “1 unidad” = “1 blíster”.
- FEFO: consumir primero lo que vence primero.

## 5) Reglas de negocio

### 5.1 Unidad mínima fija
- La unidad mínima es el blíster. No se registran ni descuentan pastillas individuales.
- Para cada lote se define su `unidades_por_blister` (p. ej. 10), que puede variar entre lotes del mismo producto.

### 5.2 Entradas por lote (con presentación fija a blíster)
- Registrar: `producto`, `lote`, `fecha_vencimiento`, `unidades_por_blister` (select), `cantidad_blister`.
- La entrada se realiza en el Almacén Central.
- El stock se contabiliza en número de blíster (no en pastillas).

### 5.3 Distribución (Central → Destinos)
- Origen: Central. Destino: ACI, Agroalimentación, Odontología, Principal.
- Selección de lote(s): FEFO por defecto (o manual si se habilita).
- Se registra cantidad en blíster por lote.
- No es consumo; no requiere datos de identificación.
- Regla específica: Odontología puede recibir distribución de insumos; cualquier intento de distribuir medicamentos a Odontología debe bloquearse o advertirse.

### 5.4 Consumo (salida real con datos mínimos)
- Origen: un Destino (no Central).
- Selección de lote: FEFO por defecto.
- Campos del formulario:
  - Tipo de identificación (select): estudiante | trabajador | profesor | comunidad.
  - Destino de distribución (select): ACI | Agroalimentación | Odontología | Principal.
  - Cantidad (select 1–10 blíster).
  - Sexo (select).
- No se solicita nombres ni datos personales; se reduce la carga de información a fines estadísticos/reportes.
- Validación: Odontología solo puede consumir insumos; no medicamentos.

### 5.5 Prohibiciones
- No permitir consumo desde Central.
- No permitir registrar cantidades en pastillas; solo blíster.
- No permitir distribuir/consumir más que el stock disponible por (destino, producto, lote).

## 6) Modelo de datos (alto nivel)

Se basará en las tablas existentes del sistema, alineadas con el diagrama relacional actual, con los siguientes ajustes:

- `lotes` (o `inventarios` por lote):
  - Agregar/usar campo `unidades_por_blister` (entero).
  - `cantidad_blister` (entero).
- `movimientos`:
  - Campos existentes (producto_id, tipo, destino_id, inventario_id/lote_id, cantidad, fecha, usuario_id, observaciones).
  - Ajustes:
    - Interpretar `cantidad` como “cantidad_blister”.
    - Agregar `tipo_identificacion` (enum: estudiante|trabajador|profesor|comunidad).
    - Agregar `sexo` (enum: masculino|femenino|otro, a definir).
  - Mantener FEFO en la selección de lote al consumir.
- `productos`:
  - Clasificación explícita por producto: `tipo_producto` (medicamento|insumo).
  - Mantener `categoria_inventario` solo como segmento operativo (p. ej., general|odontologia).
  - Nota: el valor por defecto de `unidades_por_blister` puede existir a nivel producto, pero la definición final siempre es por Lote (porque puede variar).
- Catálogo opcional:
  - `grupos_poblacionales` (id, codigo, nombre): estudiante, trabajador, profesor, comunidad.
  - Alternativa: manejar `tipo_identificacion` como enum directamente en `movimientos`.
- Opción para reportes agregados:
  - `destino_grupo_poblacional` (pivot opcional) para agregar salidas por destino y tipo de identificación. Recomendado solo si se requieren agregaciones precomputadas.

Decisión recomendada de almacenamiento:
- Los datos de salida (trabajadores y comunidad) se guardan en `movimientos` con los campos `tipo_identificacion` y `sexo`, vinculados al `destino_id` y al `inventario_id/lote_id`. Esto simplifica reportes y evita tablas adicionales innecesarias.
- Si el cliente requiere un catálogo con control más fino, se crea `grupos_poblacionales` y se referencia como FK en `movimientos`.

## 7) Flujos

### 7.1 Entrada (Central)
1. Registrar lote con `unidades_por_blister` (select) y `cantidad_blister`.
2. Aumentar stock en Central por lote (blíster).
3. Crear movimiento tipo `entrada`.

### 7.2 Distribución (Central → Destino)
1. Seleccionar destino (ACI | Agro | Odonto | Principal).
2. Seleccionar lote(s) y cantidad (blíster).
3. Validar stock y regla de Odontología (solo insumos, validado por `tipo_producto`).
4. Crear movimiento `transferencia_salida` (Central) y `transferencia_entrada` (Destino).
5. El total global sigue siendo la suma de los almacenes; no es consumo.

### 7.3 Consumo (en Destino, datos mínimos)
1. Seleccionar producto; lote por FEFO.
2. Capturar: tipo_identificación, destino, cantidad (blíster, 1–10), sexo.
3. Validar regla de Odontología (solo insumos, validado por `tipo_producto`) y stock.
4. Disminuir stock del Destino y del global.
5. Registrar movimiento tipo `consumo`.

## 8) Validaciones clave

- Stock suficiente por (almacén/destino, producto, lote).
- FEFO al consumir.
- Odontología: bloqueo cuando `tipo_producto` = medicamento.
- Cantidad en blíster (enteros): no se admiten pastillas sueltas.
- No permitir consumo desde Central.
- Umbral de alerta por vencimiento configurable.

## 9) Formularios (datos exactos)

### Entrada por lote
- Producto, Lote, Fecha de Vencimiento.
- Select “Contenido por blíster” (p. ej. 10, 12, 8…).
- Cantidad (en blíster).
- Observaciones.

### Distribución
- Origen: Central (fijo).
- Destino: ACI | Agroalimentación | Odontología | Principal.
- Producto, Lote(s), Cantidad (blíster).
- Observaciones.
- Validación de Odontología (solo insumos).

### Consumo (salida real, sin datos personales)
- Destino (desde donde se entrega).
- Tipo de identificación: estudiante | trabajador | profesor | comunidad.
- Cantidad (blíster): select 1–10.
- Sexo: masculino | femenino | otro (a validar).
- Producto, Lote seleccionado por FEFO (o manual con permiso).
- Observaciones (opcional).

## 10) Reportes

- Inventario por almacén y global (producto, categoría, subcategoría, lote, vencimiento).
- Distribución por periodo/destino.
- Consumo por periodo/destino/producto/tipo de beneficiario/sexo.
- Vencimientos próximos por destino.
- Top productos y destinos con mayor rotación.
- Cobertura (días de stock estimado = stock actual / consumo promedio diario).

### 10.1 Reporte de Inventario Periódico (matriz por destino)

Propósito:
- Entregar un reporte de “existencias” al cierre de un periodo (mensual, trimestral, semestral o anual), consolidado por destino y con total global, para “ver inventario” y emitir el documento oficial.

Encabezado del reporte:
- Título: INVENTARIO DE MEDICAMENTOS E INSUMOS
- Subtítulo de periodo: p. ej., “Febrero 2026”
- Fecha de corte (cierre): dd/mm/aaaa (por defecto, último día del periodo seleccionado)

Estructura de columnas (de izquierda a derecha):
- Descripción: nombre del producto.
- Presentación: texto breve; p. ej., “Blíster de 10”, “Blíster de 12”, “Unidad”, “Kit”, etc.
- UM: unidad de medida operativa. En la política actual: “Blíster” para sólidos; para insumos no blíster, su UM base (p. ej. Unidad/Kit).
- Principal (dd/mm/aa): existencias en el Destino Principal a la fecha de corte.
- ACI (dd/mm/aa): existencias en ACI a la fecha de corte.
- Agro (dd/mm/aa): existencias en Agroalimentación a la fecha de corte.
- Odont (dd/mm/aa): existencias en Odontología a la fecha de corte.
- Depósito/Central (dd/mm/aa): existencias en Central (el “Depósito” de Servicios Médicos) a la fecha de corte.
- Total: suma de Principal + ACI + Agro + Odont + Depósito/Central.

Parámetros del reporte:
- Periodo: Mensual | Trimestral | Semestral | Anual.
- Fecha de corte: dd/mm/aaaa (por defecto, fin del periodo).
- Destinos incluidos: Principal, ACI, Agro, Odont, Central (configurable si en el futuro hay más).
- Nivel de detalle de filas:
  - Consolidado por producto (una fila por producto).
  - Detallado por lote (una fila por producto + lote) para claridad cuando conviven presentaciones/lotes diferentes.
- Filtros opcionales: categoría, subcategoría, tipo (insumo/medicamento), estado (activo), rango de vencimiento.

Reglas de cálculo:
- Cada celda de destino muestra el stock disponible (existencias) a la fecha de corte, en la UM definida para el producto/lote.
- En “solo blíster” para sólidos: todas las cantidades se informan en blíster (enteros). No se muestran pastillas sueltas.
- “Total” = suma de existencias de todos los destinos más Central a la fecha de corte (no confundir con entradas/salidas del periodo).
- Presentación:
  - Consolidado por producto: si conviven lotes con diferentes “unidades_por_blister” (p. ej. 10 y 12), se recomienda una de estas opciones:
    - Mostrar “Blíster 10/12” y añadir nota “ver detalle por lote en anexo”; o
    - Emitir el reporte en modo “Detallado por lote” para ese producto.
- Odontología: si el producto es “medicamento”, la política vigente es “solo insumos” para ese destino. La celda debe quedar en 0 y puede marcarse con nota si hay registros previos no conformes (auditoría).
- FEFO: no aplica en este reporte (inventario es un estado, no un flujo). FEFO se usa solo en consumo.

Ejemplo (consolidado por producto, UM en blíster):

| Descripción           | Presentación  | UM      | Principal 26/01/25 | ACI 26/01/25 | Agro 26/01/25 | Odont 26/01/25 | Central 26/01/25 | Total |
|-----------------------|---------------|---------|--------------------|--------------|---------------|----------------|------------------|-------|
| Amoxicilina 500 mg    | Blíster de 10 | Blíster | 12                 | 8            | 5             | 0              | 20               | 45    |
| Gasas estériles 10x10 | Paquete x10   | Unidad  | 30                 | 15           | 10            | 12             | 50               | 117   |
| Ibuprofeno 400 mg     | Blíster 10/12 | Blíster | 6                  | 3            | 2             | 0              | 9                | 20    |

Notas:
- “Ibuprofeno 400 mg” presenta blíster mixto (10/12). Puede emitirse en “Detallado por lote” si el auditor lo solicita.
- “Odont” muestra 0 para medicamentos en cumplimiento de la restricción.

Variantes:
- Consolidado vs Detallado por lote.
- Exportación: PDF y Excel (XLSX).
- Totales por categoría/subcategoría, y desgloses por destino (hojas separadas en Excel).

Validaciones del reporte:
- Cuadra con el inventario por almacén (sumatoria por destinos + Central).
- Las cifras corresponden al estado a la fecha de corte (no confundir con movimientos del periodo).
- Respeta UM (blíster/unidad) según la política del producto/lote.

### 10.2 Reporte de Salidas – FARMACIA INTERNA (SALIDAS DE MEDICAMENTOS E INSUMOS)

Propósito:
- Informar las entregas (consumos) realizadas durante un periodo, por destino, con conteos de blíster entregados y distribución de beneficiarios por sexo y tipo de identificación.

Encabezado:
- Título: FARMACIA INTERNA (SALIDAS DE MEDICAMENTOS E INSUMOS)
- Periodo: Mensual | Trimestral | Semestral | Anual (seleccionable)
- Rango de fechas: fecha inicio y fecha fin del periodo (o fecha de corte si aplica)

Estructura de tabla:
- Filas (vertical): Destinos operativos (p. ej. Básico/Principal, ACI, Agro, Odont) y una fila “Total”.
- Columnas (horizontal):
  - Servicios Médicos: nombre del destino (Básico/Principal, ACI, Agro, Odont)
  - Medicamentos entregados: total de blíster entregados del tipo “medicamento” en el periodo
  - Beneficiarios: total de entregas (movimientos de consumo) en el periodo
  - F: beneficiarios de sexo femenino
  - M: beneficiarios de sexo masculino
  - EST: beneficiarios con tipo_identificación = estudiante
  - TRA: beneficiarios con tipo_identificación = trabajador
  - COM: beneficiarios con tipo_identificación = comunidad

Reglas de cálculo:
- Fuente de datos: `movimientos` con `tipo = consumo` dentro del rango de fechas del periodo.
- “Medicamentos entregados”: sumar `cantidad` (en blíster) de movimientos cuyo producto tenga `tipo_producto = medicamento`.
- “Beneficiarios”: contar movimientos de consumo (no hay datos personales; cada entrega se contabiliza como un beneficiario).
- F/M: contar movimientos por `sexo` = femenino/masculino.
- EST/TRA/COM: contar movimientos por `tipo_identificacion` = estudiante/trabajador/comunidad.
- Odontología: por política “solo insumos”. En esa fila, “Medicamentos entregados” debe ser 0 (o quedar en blanco con nota de cumplimiento). Las demás columnas (Beneficiarios, F/M/EST/TRA/COM) se computan solo sobre insumos.
- Total: suma de todas las filas de destinos.

Parámetros y filtros:
- Periodo y fechas.
- Destinos incluidos (configurable).
- Opcional: filtro por categoría/subcategoría si se desea desglosar por tipo de producto.

Variantes (si aplica):
- Si el cliente requiere ver también el total de insumos entregados por destino, se puede añadir una columna adicional “Insumos entregados” que sume `cantidad` de productos con `tipo_producto = insumo`.
- Si se necesita desagregar por producto, se emite un anexo por producto/destino (no recomendado para la vista principal de este reporte).

Ejemplo visual del layout (con valores de muestra):

| Servicios Médicos | Medicamentos entregados (blíster) | Beneficiarios | F  | M  | EST | TRA | COM |
|-------------------|------------------------------------|---------------|----|----|-----|-----|-----|
| Básico/Principal  | 120                                | 98            | 58 | 40 | 60  | 20  | 18  |
| ACI               | 45                                 | 40            | 20 | 20 | 22  | 12  | 6   |
| Agro              | 30                                 | 28            | 14 | 14 | 10  | 9   | 9   |
| Odontología       | 0                                  | 25            | 12 | 13 | 0   | 20  | 5   |
| Total             | 195                                | 191           | 104| 87 | 92  | 61  | 38  |

Notas:
- Los números son ilustrativos. En la práctica, se calculan desde `movimientos` (tipo consumo) filtrados por destino y periodo.
- Odontología muestra 0 en “Medicamentos entregados” por la regla “solo insumos”.
- Si el cliente agrega la columna opcional “Insumos entregados”, la fila de Odontología reflejará sus entregas en esa columna.

Validaciones:
- Los totales por destino del periodo cuadran con la suma de movimientos de consumo registrados.
- “Medicamentos entregados” solo considera `tipo_producto = medicamento`. Si se añade “Insumos entregados”, la suma de ambas columnas equivale al total de entregas en unidades operativas (blíster/unidad).
- Respeta la unidad operativa vigente: blíster para sólidos; unidad para insumos no blíster (la métrica de “Medicamentos entregados” se expresa en blíster).

### 10.2.1 Plantilla práctica (formato copiado de XLS)

Encabezado:
- FARMACIA INTERNA (SALIDA DE MEDICAMENTOS E INSUMOS)

Aclaración:
- Este formato se usa para “salidas generales” y “insumos”.
- No incluye desglose de “medicamentos por categoría” (queda fuera del alcance por tiempo).
- Cifras de ejemplo; en producción se calculan desde `movimientos` (tipo consumo) por destino y periodo.

Salidas generales (resumen por destino):

| SERVICIO MEDICO | MEDICAMENTOS ENTREGADOS | BENEFICIARIOS | F | M | EST | TRAB | COM |
|-----------------|-------------------------|---------------|---|---|-----|------|-----|
| BASICO          | 63                      | 28            | 15| 13| 20  | 7    | 1   |
| AREA            | 62                      | 43            | 32| 11| 40  | 3    | 0   |
| E. BRACHO       | 5                       | 4             | 2 | 2 | 3   | 1    | 0   |
| TOTAL           | 130                     | 75            | 49| 26| 63  | 11   | 1   |

Notas:
- “Medicamentos entregados” está en blíster (enteros), acorde a la política “solo blíster”.
- Odontología no aparece en este resumen porque aquí el corte es por “servicio médico” global; la restricción “solo insumos” aplica cuando se filtra por destino (ver insumos).

Insumos — Insumos varios:

| DESCRIPCION               | PRESENTACION      | UM     | PPAL | ACI | AGRO | ODONT | TOTAL |
|---------------------------|-------------------|--------|------|-----|------|-------|-------|
| PAPEL DE ECO              | ROLLO             | UNIDAD | 1    |     |      |       | 1     |
| RESINA                    | JERINGA           | UNIDAD |      |     |      | 1     | 1     |
| TAPA BOCAS                | CAJA              | UNIDAD |      |     |      | 1     | 1     |
| SERVILLETAS               | PAQUETE           | UNIDAD |      |     |      | 1     | 1     |
| TOTAL                     |                   |        | 1    |     |      | 3     | 4     |

Plantilla (otras filas con valores usualmente vacíos, se mantienen para capturas):
- GERDEX (GALON, UNIDAD), OBSTURADOR (CAJA), JELCO (UNIDAD), MACROGOTERO (PAQUETE/CAJA), SCALP (UNIDAD), VENDAS (ROLLO), IONOMERO (JERINGA), LIDOCAINA CON EPINEFRINA (CARPULE), AGUJAS CORTAS/LARGAS (UNIDAD), BATAS DE CIRUJANO (UNIDAD), GASAS (ROLLO), GASAS ODONTOLOGICAS (PAQUETE), EYECTOR (PAQUETE), VIDRIO IONOMERICO (JERINGA), TOALLIN (ROLLO), ENJUAGUE BUCAL (FRASCO), RESINA FLUIDA (JERINGA), CAMPOS DESCARTABLES (PAQUETE), ACIDO FOSFORICO (FRASCO), ADHESIVO (ROLLO), FLUOR (FRASCO), BABEROS (PAQUETE), VASOS DESCARTABLES (PAQUETE), PAPEL ESTRAZA (ROLO/RESMA).

Insumos — Inyectadoras:

| DESCRIPCION | PRESENTACION | UM     | PPAL | ACI | AGRO | ODONT | TOTAL |
|-------------|--------------|--------|------|-----|------|-------|-------|
| 1ML         | CAJA         | UNIDAD |      |     |      |       |       |
| 3ML         | CAJA         | UNIDAD |      |     |      |       |       |
| 5ML         | CAJA         | UNIDAD |      |     |      |       |       |
| 10 ML       | CAJA         | UNIDAD |      |     |      |       |       |
| 20 ML       | CAJA         | UNIDAD |      |     |      |       |       |
| TOTAL       |              |        |      |     |      |       |       |

Insumos — Guantes:

| DESCRIPCION | PRESENTACION | UM     | PPAL | ACI | AGRO | ODONT | TOTAL |
|-------------|--------------|--------|------|-----|------|-------|-------|
| TALLA S     | CAJA         | UNIDAD |      |     |      | 2     | 2     |
| TALLA M     | CAJA         | UNIDAD |      |     |      |       |       |
| TALLA L     | CAJA         | UNIDAD | 1    |     |      | 1     | 2     |
| TALLA XL    | CAJA         | UNIDAD |      |     |      |       |       |
| TOTAL       |              |        | 1    |     |      | 3     | 4     |

Observaciones:
- Odontología solo reporta insumos; si algún medicamento aparece, debe figurar como 0 y auditarse.
- Las columnas PPAL/ACI/AGRO/ODONT se calculan sumando `cantidad` (unidad operativa correspondiente) por destino en el periodo seleccionado.
- Este formato se entrega en PDF/XLSX; en XLSX se recomienda mantener hojas separadas por “Insumos varios”, “Inyectadoras” y “Guantes”.

### 10.3 Glosario de métricas de reporte (Salidas e Inventario)

Definiciones para estandarizar interpretación:

- Medicamentos entregados:
  - Suma de `cantidad` (blíster) en movimientos `consumo` con `categoria_inventario = medicamento`.
- Insumos entregados (opcional):
  - Suma de `cantidad` (unidad) en movimientos `consumo` con `categoria_inventario = insumo`.
- Beneficiarios:
  - Conteo de movimientos `consumo` en el periodo (cada entrega cuenta 1; no se deduplican personas).
- F/M:
  - Conteo según `sexo` del beneficiario (femenino/masculino).
- EST/TRA/COM:
  - Conteos según `tipo_identificacion` (estudiante/trabajador/comunidad).
- Profesor:
  - Si se habilita en `tipo_identificacion`, puede añadirse columna “PROF” o mantenerse fuera del formato 10.2 según decisión del cliente (hoy: no incluido).
- Depósito/Central:
  - Sinónimo operativo del Almacén Central; no realiza “consumo” (entrega a beneficiario), solo entradas y distribución.

## 11) Plan de implementación (propuesto)

- Fase 0 – Aprobación del documento con cliente.
- Fase 1 – Migraciones:
  - Agregar `unidades_por_blister` y `cantidad_blister` por lote (si no están).
  - En `movimientos`, interpretar `cantidad` como blíster y agregar `tipo_identificacion` y `sexo`.
- Fase 2 – Servicios:
  - Entrada: validar y registrar blíster.
  - Distribución: mover blíster Central → Destino, regla Odontología.
  - Consumo: FEFO, validar, registrar blíster, datos mínimos.
- Fase 3 – UI:
  - Formulario de entrada con select de contenido por blíster.
  - Distribución y Consumo con selects definidos.
- Fase 4 – Reportes y alertas de vencimiento:
  - Implementar 10.1 (Inventario matriz por destino) y 10.2 (Salidas – FARMACIA INTERNA) con exportación PDF/XLSX.
- Fase 5 – QA/UAT, roles y bitácora.

## 12) Checklist de tareas

- [ ] Validar con cliente el catálogo de valores de “Contenido por blíster” (p. ej. 5, 8, 10, 12, otros).
- [ ] Confirmar lista de destinos vigente (ACI, Agro, Odonto, Principal).
- [ ] Confirmar enum de `tipo_identificacion` y `sexo`.
- [ ] Ajustar migraciones (lotes/inventario: unidades_por_blister; movimientos: tipo_identificacion, sexo).
- [ ] Implementar FEFO en consumo.
- [ ] UI de Entrada, Distribución y Consumo (selects y validaciones).
- [ ] Reglas: Odontología solo insumos (validado por `tipo_producto`).
- [ ] Reportes 10.1 y 10.2 (inventario y salidas; export PDF/XLSX).
- [ ] Bitácora y auditoría de movimientos.
- [ ] UAT con casos de blíster variable por lote (10/12, etc.).

## 13) Criterios de aceptación

- El sistema opera exclusivamente en blíster (sin pastillas sueltas).
- Entrada por lote especifica el contenido por blíster y la cantidad en blíster.
- Distribución separada de consumo; ambos registran blíster por lote.
- Consumo registra solo tipo_identificación, destino, cantidad (1–10 blíster) y sexo.
- Odontología no recibe ni consume medicamentos; solo insumos.
- Reportes:
  - 10.1 Inventario: cuadra con stock por destino/Central a la fecha de corte.
  - 10.2 Salidas: columnas calculadas correctamente (Medicamentos entregados, Beneficiarios, F/M, EST/TRA/COM), con Odontología cumpliendo “solo insumos”.

## 14) Notas y decisiones

- Si se requiere mayor control del catálogo de grupos poblacionales, crear `grupos_poblacionales` y referenciar en `movimientos`.
- La selección por FEFO puede ser override con rol especial y motivo.
- Si en el futuro se requieren presentaciones distintas (líquidos, kits), se documentará aparte; en este alcance, la operación es únicamente en blíster para sólidos.

## 15) Política de catálogo y presentaciones mixtas (jarabe vs crema vs blíster)

Para evitar confusión en inventario y reportes cuando un mismo principio activo llega en distintas formas (jarabe, crema, tabletas/blíster), se adopta la siguiente política:

### 15.1 Regla de producto (no mezclar formas farmacéuticas)
- Un “producto” en el catálogo se define por: nombre comercial, principio activo, forma farmacéutica, concentración, unidad operativa (UM), y presentación/tamaño.
- Si cambia la forma farmacéutica o la UM (p. ej., jarabe → crema; blíster → frasco/tubo), se crea un producto distinto (nuevo registro). No se suman ni se reportan en la misma fila.
- Ejemplos de productos distintos:
  - “Amoxicilina 500 mg Tabletas – Blíster x10”
  - “Amoxicilina 250 mg/5 ml Jarabe – Frasco 120 ml”
  - “Ibuprofeno 5% Crema – Tubo 30 g”

### 15.2 Unidad operativa y UM en reportes
- Sólidos orales: UM = “Blíster” (enteros; sin pastillas sueltas).
- No sólidos e insumos: UM = “Unidad” (frasco, tubo, paquete).
- Nunca mezclar UMs en una misma fila de reporte. Si conviven UMs distintas en el catálogo, cada producto tiene su propia fila.

### 15.3 Consolidación en el reporte de inventario
- El reporte “Inventario de Medicamentos e Insumos” se emite consolidado por producto (no por lote).
- Si se necesita una visión por familia (mismo principio activo), se puede añadir un resumen por categoría/subcategoría, pero manteniendo separación por forma farmacéutica y UM (no sumable entre UMs).

### 15.4 Convenciones de nombres y códigos (SKU)
- Código sugerido: {Nombre abreviado}-{Forma}-{Concentración}-{UM}-{Tamaño}
  - Ejemplos: AMOX-Tab-500mg-BL10; AMOX-Jar-250mg/5ml-Fr120ml; IBUP-Cre-5%-Tub30g.
- Nombre visible:
  - “Amoxicilina 500 mg Tabletas – Blíster x10”
  - “Amoxicilina 250 mg/5 ml Jarabe – Frasco 120 ml”
  - “Ibuprofeno 5% Crema – Tubo 30 g”

### 15.5 Modelo de datos y migraciones (alineación)
- `productos`:
  - Agregar/usar campos: `forma_farmaceutica` (enum), `concentracion` (string), `unidad_operativa` (enum: blister|unidad), `presentacion_tamano` (string).
  - Opcional: `familia_activo` para agrupar variantes (solo para reportes agregados).
- `lotes`/`inventarios`:
  - Mantener `unidades_por_blister` solo para sólidos en blíster.
  - Para UM “Unidad” (frasco/tubo/paquete), registrar `cantidad_unidad` sin conversión a pastillas.

### 15.6 Impacto en reportes
- Filas separadas por producto; jarabe y crema del mismo activo se reportan por separado.
- Totales por destino/Central suman dentro de la misma UM; no sumar blíster con frascos/tubos en una única cifra. Si se requiere un “total general”, se presentan subtotales por UM o se añade nota “No sumable entre UMs”.
- Odontología: se mantiene la regla “solo insumos”. Si el producto es medicamento (cualquier forma), la celda de Odonto debe ser 0.

### 15.7 Ejemplo práctico
- Si “Paracetamol” llega en “Jarabe” y “Crema”:
  - Se crean dos productos separados.
  - El reporte mostrará dos filas: “Paracetamol Jarabe – Frasco 120 ml” y “Paracetamol Crema – Tubo 30 g”.
  - No se intenta consolidar ambas filas en una sola.
