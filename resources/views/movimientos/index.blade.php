@extends('layouts.dashboard')
{{--
  Vista: resources/views/movimientos/index.blade.php
  Propósito: Registro de movimientos de inventario (Entrada, Salida, Ajuste + y Ajuste −) con trazabilidad por lote y fecha de vencimiento.

  Datos esperados (inyectados por el controlador):
    - $productos: colección de productos para el selector principal.
    - $destinos: (opcional) destinos para egresos; si faltan, se muestra una advertencia.
    - $ultimos: paginación de movimientos recientes para el panel inferior.

  Comportamiento clave:
    - Para Entrada y Ajuste +, el formulario solicita (opcional) “Fecha de vencimiento” y “Número de lote” y valida que la fecha no sea pasada.
    - Panel de lotes: muestra lotes/fechas del producto seleccionado, ordenados FEFO/FIFO; ayuda a seleccionar un lote existente.
    - Ajax: consulta de inventarios por producto vía la ruta `movimientos.inventarios`.
    - La lógica de negocio del procesamiento está centralizada en InventarioService; esta vista solo recolecta datos y guía al usuario.
--}}

@section('content')
<div class="container mt-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="m-0">Registrar Movimiento</h2>
    <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary">Ver Inventario</a>
  </div>

  @php
    $allErrors = $errors->messages();
    $otherErrors = collect($allErrors)->except('destino_id')->flatten();
  @endphp
  @if ($otherErrors->isNotEmpty())
    {{-- Mensajes de validación del formulario (excluye destino_id, que va por toast) --}}
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($otherErrors as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif
  @if ($errors->has('destino_id'))
    {{-- Error específico de destino para egresos como toast en la esquina superior derecha --}}
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (typeof showToast === 'function') {
          showToast(@json($errors->first('destino_id')), 'error');
        }
      });
    </script>
  @endif
  @if (session('success'))
    {{-- Notificación de éxito usando toast global --}}
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        showToast(@json(session('success')), 'success');
      });
    </script>
  @endif
  @if (session('error'))
    {{-- Notificación de error como toast en el lado derecho --}}
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        showToast(@json(session('error')), 'error');
      });
    </script>
  @endif

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      {{-- Formulario principal de registro de movimientos --}}
      <form method="POST" action="{{ route('movimientos.store') }}" class="row g-3">
        @csrf
        <div class="col-md-5">
          <label class="form-label">Medicamento</label>
          <div class="position-relative">
            <input type="text" id="producto_buscar" class="form-control mb-2" placeholder="Buscar por nombre o código..." autocomplete="off">
            <div id="producto_sugerencias" class="list-group position-absolute w-100" style="z-index: 1000; display:none; max-height: 240px; overflow:auto;"></div>
          </div>
          <select name="producto_id" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach($productos as $p)
              <option value="{{ $p->id }}" @selected(old('producto_id')==$p->id) data-nombre="{{ $p->nombre }}" data-codigo="{{ $p->codigo }}">{{ $p->nombre }} ({{ $p->codigo }})</option>
            @endforeach
          </select>
          <small class="text-muted">Escribe para buscar, selecciona una sugerencia o usa el listado.</small>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipo</label>
          <select name="tipo" id="tipo" class="form-select" required>
            <option value="ingreso" @selected(old('tipo')==='ingreso')>Entrada</option>
            <option value="egreso" @selected(old('tipo')==='egreso')>Salida</option>
            <option value="ajuste_pos" @selected(old('tipo')==='ajuste_pos')>Ajuste +</option>
            <option value="ajuste_neg" @selected(old('tipo')==='ajuste_neg')>Ajuste -</option>
          </select>
        </div>
        <div class="col-md-4" id="destino-wrapper" style="{{ old('tipo','ingreso')==='egreso' ? '' : 'display:none;' }}">
          <label class="form-label">Destino (solo SALIDA)</label>
          @php $hayDestinos = isset($destinos) && count($destinos)>0; @endphp
          <select name="destino_id" id="destino_id" class="form-select">
            <option value="">Seleccione destino...</option>
            @if($hayDestinos)
              @foreach($destinos as $d)
                <option value="{{ $d->id }}" @selected(old('destino_id')==$d->id)>{{ $d->nombre }} ({{ $d->codigo }})</option>
              @endforeach
            @endif
          </select>
          @if(!$hayDestinos)
            <div class="alert alert-warning mt-2 p-2 small mb-0">No hay destinos cargados. Ejecute migraciones y seeders (php artisan migrate --seed) o verifique la tabla <code>destinos</code>.</div>
          @endif
        </div>
        <div class="col-md-2">
          <label class="form-label">Cantidad</label>
          <input type="number" min="1" class="form-control" name="cantidad" value="{{ old('cantidad',1) }}" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Fecha</label>
          <input type="date" class="form-control" name="fecha" value="{{ old('fecha', now()->toDateString()) }}">
        </div>

        <div class="col-md-3" id="fv-wrapper">
          <label class="form-label">Fecha de vencimiento (ENTRADA y AJUSTE +)</label>
          <input type="date" class="form-control" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}">
        </div>
        <div class="col-md-3" id="lote-wrapper">
          <label class="form-label">Número de lote </label>
          <input type="text" class="form-control" maxlength="50" name="lote" value="{{ old('lote') }}" placeholder="Ej: L-2025-AX13">
          <div class="form-text mt-1">Sugerencia: usa la tabla inferior para elegir un lote con los botones “+” o “Elegir lote”, o escribe uno nuevo.</div>
          <div id="lote-advice" class="small mt-1 text-muted"></div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Motivo</label>
          <input type="text" class="form-control" name="motivo" value="{{ old('motivo') }}" placeholder="Opcional">
        </div>
        <div class="col-md-6">
          <label class="form-label">Observaciones</label>
          <input type="text" class="form-control" name="observaciones" value="{{ old('observaciones') }}" placeholder="Opcional">
        </div>

        <div class="col-12 d-flex justify-content-end">
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      {{-- Panel auxiliar: lotes y vencimientos del producto seleccionado.
           Orden FEFO/FIFO y selector para autocompletar lote+fecha en el formulario. --}}
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="m-0">Lotes y vencimientos del producto seleccionado</h5>
        <div class="d-flex align-items-center gap-2">
          <small class="text-muted me-2">Ayuda visual para no afectar registros previos</small>
          <button type="button" id="btn-clear-lote" class="btn btn-sm btn-outline-secondary" style="display:none;">Quitar selección</button>
        </div>
      </div>
      <div id="inventarios-producto" class="table-responsive">
        <table class="table table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Numero de lote</th>
              <th>Fecha de vencimiento</th>
              <th>Cantidad</th>
              <th>Creado</th>
              <th class="text-end" style="width: 160px;">Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="6" class="text-muted">Seleccione un medicamento para ver sus lotes...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      {{-- Panel de últimos movimientos: tabla paginada con metadatos de cada movimiento. --}}
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="m-0">Últimos movimientos</h5>
        <form method="GET" class="d-flex align-items-center gap-2">
          <label class="text-muted small">Ver</label>
          <select class="form-select form-select-sm" name="per_page" onchange="this.form.submit()" style="width: 90px;">
            @foreach([10,20,50] as $pp)
              <option value="{{ $pp }}" @selected((int)request('per_page',10) === $pp)>{{ $pp }}</option>
            @endforeach
          </select>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Fecha</th>
              <th>Producto</th>
              <th>Tipo</th>
              <th>Cant.</th>
              <th>Área</th>
              <th>Vencimiento</th>
              <th>Usuario</th>
              <th>Motivo</th>
            </tr>
          </thead>
          <tbody>
            @forelse($ultimos as $m)
              @php
                $badge = [
                  'ingreso' => 'success',
                  'egreso' => 'danger',
                  'ajuste_pos' => 'primary',
                  'ajuste_neg' => 'warning',
                ][$m->tipo] ?? 'secondary';
                $fv = optional($m->inventario)->fecha_vencimiento;
              @endphp
              <tr>
                <td>{{ \Carbon\Carbon::parse($m->fecha)->format('d/m/Y') }}</td>
                <td>
                  <strong>{{ $m->producto->nombre ?? '—' }}</strong>
                  <span class="text-muted">({{ $m->producto->codigo ?? '' }})</span>
                </td>
                <td><span class="badge bg-{{ $badge }} text-uppercase">{{ $m->tipo }}</span></td>
                <td>{{ $m->cantidad }}</td>
                <td>
                  @php $dest = $m->destino; @endphp
                  @if($m->tipo==='egreso')
                    <span class="badge bg-dark" title="Destino normalizado">{{ $dest?->nombre ?? $m->salida ?? '-' }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  @if($fv)
                    @php $fvC = \Carbon\Carbon::parse($fv); $dias = now()->diffInDays($fvC, false); @endphp
                    <span class="badge bg-{{ $dias < 0 ? 'danger' : ($dias <= 30 ? 'warning text-dark' : 'info') }}">{{ $fvC->format('d/m/Y') }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>{{ $m->usuario->name ?? '-' }}</td>
                <td class="text-truncate" style="max-width:240px;" title="{{ $m->motivo }}">{{ $m->motivo ?? '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted">Aún no hay movimientos registrados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-end">
        <!-- Paginador moderno igual que bitácora -->
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap w-100">
          <div class="text-muted small mb-2 mb-md-0">
            @php
              $from = $ultimos->firstItem();
              $to = $ultimos->lastItem();
              $total = $ultimos->total();
            @endphp
            Mostrando <b>{{ $from }}</b> - <b>{{ $to }}</b> de <b>{{ $total }}</b> movimientos
          </div>
          <nav aria-label="Paginador movimientos">
            <ul class="pagination mb-0">
              <!-- Primera página -->
              <li class="page-item {{ $ultimos->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $ultimos->url(1) }}" aria-label="Primera">
                  <i class="fa fa-angle-double-left"></i>
                </a>
              </li>
              <!-- Página anterior -->
              <li class="page-item {{ $ultimos->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $ultimos->previousPageUrl() }}" aria-label="Anterior">
                  <i class="fa fa-angle-left"></i>
                </a>
              </li>
              <!-- Páginas -->
              @foreach ($ultimos->getUrlRange(max(1, $ultimos->currentPage()-2), min($ultimos->lastPage(), $ultimos->currentPage()+2)) as $page => $url)
                <li class="page-item {{ $page == $ultimos->currentPage() ? 'active' : '' }}">
                  <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
              @endforeach
              <!-- Página siguiente -->
              <li class="page-item {{ $ultimos->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $ultimos->nextPageUrl() }}" aria-label="Siguiente">
                  <i class="fa fa-angle-right"></i>
                </a>
              </li>
              <!-- Última página -->
              <li class="page-item {{ $ultimos->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $ultimos->url($ultimos->lastPage()) }}" aria-label="Última">
                  <i class="fa fa-angle-double-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  /**
   * Script de apoyo para la vista de movimientos.
   * - Variables de referencia de elementos del DOM.
   * - Helpers para cálculo de días y badges de vencimiento.
   * - Render de tabla de inventarios (panel auxiliar) y opciones de lote.
   * - Validación de fecha de vencimiento (Entrada y Ajuste +).
   * - Eventos de cambio/submit y carga inicial vía AJAX.
   */
  const tipoSel = document.getElementById('tipo');
  const fvWrap = document.getElementById('fv-wrapper');
  const loteWrap = document.getElementById('lote-wrapper');
  const destinoWrap = document.getElementById('destino-wrapper');
  const productoSel = document.querySelector('select[name="producto_id"]');
  const productoBuscar = document.getElementById('producto_buscar');
  const productoSugerencias = document.getElementById('producto_sugerencias');
  const invTableBody = document.querySelector('#inventarios-producto tbody');
  const loteInput = document.querySelector('input[name="lote"]');
  const fvInput = document.querySelector('input[name="fecha_vencimiento"]');
  const loteAdvice = document.getElementById('lote-advice');
  const form = document.querySelector('form[action="{{ route('movimientos.store') }}"]');
  // Campo oculto para lote objetivo sólo en ajuste_neg
  let hiddenTarget = document.querySelector('input[name="inventario_objetivo_id"]');
  if (!hiddenTarget) {
    hiddenTarget = document.createElement('input');
    hiddenTarget.type = 'hidden';
    hiddenTarget.name = 'inventario_objetivo_id';
    form.appendChild(hiddenTarget);
  }
  const inputCantidad = document.querySelector('input[name="cantidad"]');
  const btnClearLote = document.getElementById('btn-clear-lote');
  let selectedInventarioId = null; // selección negativa (ajuste −)
  let selectionByQuick = false; // true si proviene del botón "−"
  let prevTipoValue = tipoSel.value; // almacena el tipo antes de forzar egreso
  // Estado paralelo para selección en AJUSTE + / ENTRADA
  let selectedInventarioIdPos = null; // id de inventario seleccionado para sumar
  let selectionByQuickPos = false; // true si proviene del botón "+"
  let prevTipoValuePos = tipoSel.value; // tipo previo antes de forzar ajuste_pos

  function updateClearButtonVisibility(){
    // Mostrar el botón si hay selección de lote objetivo (egreso/ajuste_neg)
    // o si hay selección de lote para sumar (ingreso/ajuste_pos)
    btnClearLote.style.display = (hiddenTarget.value || selectedInventarioIdPos) ? 'inline-block' : 'none';
  }

  function clearSelection(){
    hiddenTarget.value = '';
    selectedInventarioId = null;
    document.querySelectorAll('#inventarios-producto tbody tr').forEach(tr => tr.classList.remove('table-primary'));
    inputCantidad.removeAttribute('max');
    if (selectionByQuick) {
      // Restaurar el tipo previo si la selección vino del botón "−" (ajuste −)
      tipoSel.value = prevTipoValue || 'ingreso';
      tipoSel.dispatchEvent(new Event('change'));
    }
    selectionByQuick = false;
    // Limpiar selección positiva (Ajuste + / Ingreso)
    if (selectedInventarioIdPos) {
      // Sólo limpiar los campos si efectivamente provenían de una selección
      if (loteInput) loteInput.value = '';
      if (fvInput) fvInput.value = '';
    }
    if (selectionByQuickPos) {
      tipoSel.value = prevTipoValuePos || 'ingreso';
      tipoSel.dispatchEvent(new Event('change'));
    }
    selectedInventarioIdPos = null;
    selectionByQuickPos = false;
    // Asegurar que el mensaje de asesoría de lote también se limpie
    if (typeof updateLoteAdvice === 'function') { updateLoteAdvice(); }
    updateClearButtonVisibility();
  }

  function applySelectionNeg(id, cant, { forceAjusteNeg } = { forceAjusteNeg: false }){
    hiddenTarget.value = String(id);
    selectedInventarioId = Number(id);
    document.querySelectorAll('#inventarios-producto tbody tr').forEach(tr => tr.classList.remove('table-primary'));
    const btn = document.querySelector(`.select-lote-neg[data-id="${id}"]`) || document.querySelector(`.quick-ajuste-neg[data-id="${id}"]`);
    if (btn) btn.closest('tr').classList.add('table-primary');
    inputCantidad.removeAttribute('max');
    if (cant > 0) inputCantidad.setAttribute('max', String(cant));
    if (forceAjusteNeg) {
      prevTipoValue = tipoSel.value;
      selectionByQuick = true;
      tipoSel.value = 'ajuste_neg';
      tipoSel.dispatchEvent(new Event('change'));
    }
    updateClearButtonVisibility();
  }

  let inventariosActuales = [];

  function daysBetween(d1, d2) {
    // Retorna el número entero de días entre dos fechas
    const ms = d2.getTime() - d1.getTime();
    return Math.floor(ms / (1000*60*60*24));
  }

  function badgeForDate(fv) {
    // Determina la clase (color) de badge según días a vencer: vencido/30 días/normal
    if (!fv) return {cls:'secondary', text:'—'};
    const hoy = new Date();
    const dias = daysBetween(hoy, fv);
    const cls = dias < 0 ? 'danger' : (dias <= 30 ? 'warning text-dark' : 'info');
    return {cls, text: fv.toLocaleDateString()};
  }

  function renderInventariosTable() {
    // Construye la tabla del panel de lotes/fechas, marcando el primero como “A consumir primero”
    if (!inventariosActuales.length) {
      invTableBody.innerHTML = '<tr><td colspan="6" class="text-muted">Sin lotes para este producto.</td></tr>';
      return;
    }
    // Encuentra el primer índice con cantidad > 0 para marcar el consumo prioritario
    const firstIdx = inventariosActuales.findIndex(r => Number(r.cantidad) > 0);
    const rows = inventariosActuales.map((r, idx) => {
      const fv = r.fecha_vencimiento ? new Date(r.fecha_vencimiento) : null;
      const b = badgeForDate(fv);
      const firstMark = (firstIdx >= 0 && idx === firstIdx) ? '<span class="badge bg-primary me-2">A consumir primero</span>' : '';
      const sinVenc = !r.fecha_vencimiento;
      const hasLote = !!(r.lote && String(r.lote).trim().length);
      const loteCell = hasLote
        ? String(r.lote)
        : `<span class="badge bg-secondary" title="Registro sin lote${sinVenc ? ' y sin vencimiento' : ''}. Puede provenir de regularización inicial o ingresos sin lote.">Sin lote${sinVenc ? ' / sin vencimiento' : ''}</span>`;
      const agotado = Number(r.cantidad) <= 0;
      const esNeg = (tipoSel.value === 'ajuste_neg');
      const esPos = (tipoSel.value === 'ingreso' || tipoSel.value === 'ajuste_pos');
      let accionesHtml = '';
      if (esNeg) {
        // Botón rápido "-" para preparar un ajuste negativo con este lote
        const btnMenos = !agotado
          ? `<button type="button" class="btn btn-sm btn-outline-danger me-1 quick-ajuste-neg" title="Usar este lote para un AJUSTE −" data-id="${r.id}" data-cant="${r.cantidad}">−</button>`
          : `<button type="button" class="btn btn-sm btn-outline-secondary me-1" disabled>−</button>`;
        const elegirBtn = !agotado
          ? `<button type="button" class="btn btn-sm btn-outline-primary select-lote-neg" title="Seleccionar este lote para AJUSTE −" data-id="${r.id}" data-cant="${r.cantidad}">Elegir lote</button>`
          : `<button type="button" class="btn btn-sm btn-outline-secondary" disabled>${agotado ? 'Agotado' : '—'}</button>`;
        accionesHtml = `${btnMenos}${elegirBtn}`;
      } else if (esPos) {
        // Botón rápido "+" para preparar un ajuste positivo a este mismo lote
        const btnMas = `<button type="button" class="btn btn-sm btn-outline-success me-1 quick-ajuste-pos" title="Sumar a este lote (forzar AJUSTE +)" data-id="${r.id}" data-lote="${r.lote ?? ''}" data-fv="${r.fecha_vencimiento ?? ''}">+</button>`;
        const elegirPos = `<button type="button" class="btn btn-sm btn-outline-primary select-lote-pos" title="Usar este lote en Entrada/Ajuste +" data-id="${r.id}" data-lote="${r.lote ?? ''}" data-fv="${r.fecha_vencimiento ?? ''}">Elegir lote</button>`;
        accionesHtml = `${btnMas}${elegirPos}`;
      } else {
        accionesHtml = `<button type="button" class="btn btn-sm btn-outline-secondary" disabled>—</button>`;
      }
      return `<tr>
        <td>${idx+1}</td>
        <td>${firstMark}${loteCell}</td>
        <td>${fv ? `<span class="badge bg-${b.cls}" title="Fecha de vencimiento">${b.text}</span>` : '<span class="text-muted" title="Sin vencimiento">Sin vencimiento</span>'}</td>
        <td>${agotado ? `<strong>0</strong> <span class="badge bg-secondary ms-2" title="Sin stock">Agotado</span>` : `<strong>${r.cantidad}</strong>`}</td>
        <td>${new Date(r.created_at).toLocaleDateString()}</td>
        <td class="text-end">${accionesHtml}</td>
      </tr>`;
    });
    invTableBody.innerHTML = rows.join('');
    // Enlazar eventos de selección de lote (negativo: solo ajuste −)
    document.querySelectorAll('.select-lote-neg').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = Number(btn.getAttribute('data-id'));
        const cant = Number(btn.getAttribute('data-cant') || 0);
        // Toggle: si ya está seleccionado, quitar; si no, aplicar sin forzar egreso
        if (selectedInventarioId === id) { clearSelection(); return; }
        // Al activar selección negativa, limpiar cualquier selección positiva
        selectedInventarioIdPos = null;
        selectionByQuick = false; // selección manual no forzada
        applySelectionNeg(id, cant, { forceAjusteNeg: false });
      });
    });
    // Enlazar evento rápido para ajuste − con "-"
    document.querySelectorAll('.quick-ajuste-neg').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = Number(btn.getAttribute('data-id'));
        const cant = Number(btn.getAttribute('data-cant') || 0);
        // Toggle: si ya está seleccionado, quitar; si no, aplicar forzando AJUSTE −
        if (selectedInventarioId === id) { clearSelection(); return; }
        // Al activar selección negativa, limpiar cualquier selección positiva
        selectedInventarioIdPos = null;
        applySelectionNeg(id, cant, { forceAjusteNeg: true });
        inputCantidad.focus();
      });
    });
    // Enlazar eventos de selección de lote (positivo)
    document.querySelectorAll('.select-lote-pos').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = Number(btn.getAttribute('data-id'));
        const lote = btn.getAttribute('data-lote') || '';
        const fv = btn.getAttribute('data-fv') || '';
        // Toggle: si ya está seleccionado, quitar
        if (selectedInventarioIdPos === id) { clearSelection(); return; }
        // Cancelar selección negativa si hubiera
        hiddenTarget.value = '';
        selectedInventarioId = null;
        selectionByQuick = false;
        // Aplicar selección positiva sin forzar tipo
        applySelectionPos(id, lote, fv, { forceAjustePos: false });
      });
    });
    // Evento rápido para forzar AJUSTE + con "+"
    document.querySelectorAll('.quick-ajuste-pos').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = Number(btn.getAttribute('data-id'));
        const lote = btn.getAttribute('data-lote') || '';
        const fv = btn.getAttribute('data-fv') || '';
        if (selectedInventarioIdPos === id && selectionByQuickPos) { clearSelection(); return; }
        // Cancelar selección negativa si hubiera
        hiddenTarget.value = '';
        selectedInventarioId = null;
        selectionByQuick = false;
        applySelectionPos(id, lote, fv, { forceAjustePos: true });
        inputCantidad.focus();
      });
    });
    updateClearButtonVisibility();
  }

  function applySelectionPos(id, lote, fv, { forceAjustePos } = { forceAjustePos: false }){
    selectedInventarioIdPos = Number(id);
    // Resaltar fila
    document.querySelectorAll('#inventarios-producto tbody tr').forEach(tr => tr.classList.remove('table-primary'));
    const btn = document.querySelector(`.select-lote-pos[data-id="${id}"]`) || document.querySelector(`.quick-ajuste-pos[data-id="${id}"]`);
    if (btn) btn.closest('tr').classList.add('table-primary');
    // Rellenar campos de lote y fecha
    if (loteInput) loteInput.value = lote || '';
    if (fvInput) fvInput.value = fv || '';
    // Forzar tipo a AJUSTE + si corresponde
    if (forceAjustePos) {
      prevTipoValuePos = tipoSel.value;
      selectionByQuickPos = true;
      tipoSel.value = 'ajuste_pos';
      tipoSel.dispatchEvent(new Event('change'));
    }
    updateLoteAdvice();
    updateClearButtonVisibility();
  }

  function reapplyHighlights() {
    // Reaplica resaltado según selecciones activas tras re-render o cambio de tipo
    document.querySelectorAll('#inventarios-producto tbody tr').forEach(tr => tr.classList.remove('table-primary'));
    if (selectedInventarioId && (tipoSel.value === 'ajuste_neg')) {
      const btnNeg = document.querySelector(`.select-lote-neg[data-id="${selectedInventarioId}"]`) || document.querySelector(`.quick-ajuste-neg[data-id="${selectedInventarioId}"]`);
      if (btnNeg) btnNeg.closest('tr').classList.add('table-primary');
    }
    if (selectedInventarioIdPos && (tipoSel.value === 'ingreso' || tipoSel.value === 'ajuste_pos')) {
      const btnPos = document.querySelector(`.select-lote-pos[data-id="${selectedInventarioIdPos}"]`) || document.querySelector(`.quick-ajuste-pos[data-id="${selectedInventarioIdPos}"]`);
      if (btnPos) btnPos.closest('tr').classList.add('table-primary');
    }
  }

  // Eliminado: renderLoteOptions() y select de lotes. Ahora la selección se hace desde la tabla.

  function updateLoteAdvice() {
    // Muestra consejo si el lote + fecha coincide con un existente (se sumará al mismo)
    const loteVal = (loteInput.value || '').trim();
    const fvVal = (fvInput.value || '').trim();
    if (!loteVal && !fvVal) { loteAdvice.textContent=''; return; }
    const match = inventariosActuales.find(r =>
      (r.lote || '') === loteVal && (r.fecha_vencimiento || '') === fvVal
    );
    if (match) {
      const fv = match.fecha_vencimiento ? new Date(match.fecha_vencimiento) : null;
      const b = badgeForDate(fv);
      loteAdvice.innerHTML = `Se sumará al lote <b>${loteVal || '—'}</b> con vencimiento <b>${fv ? b.text : '—'}</b>.`;
      loteAdvice.className = 'small mt-1 text-success';
    } else {
      loteAdvice.textContent = '';
      loteAdvice.className = 'small mt-1 text-muted';
    }
  }

  function validateFechaVencimientoBeforeSubmit(e) {
    // En ENTRADA y AJUSTE +, la fecha de vencimiento es obligatoria y no puede ser pasada
    if (!(tipoSel.value === 'ingreso' || tipoSel.value === 'ajuste_pos')) return true;
    if (!fvInput.value) {
      if (typeof showToast === 'function') showToast('Debes ingresar la fecha de vencimiento para entradas y ajustes positivos.', 'error');
      e.preventDefault();
      return false;
    }
    const val = new Date(fvInput.value);
    const hoy = new Date();
    // normalizar a fecha sin hora
    val.setHours(0,0,0,0); hoy.setHours(0,0,0,0);
    const dias = daysBetween(hoy, val);
    // Señalización visual
    fvInput.classList.remove('is-invalid');
    fvInput.classList.remove('is-warning');
    if (dias < 0) {
      fvInput.classList.add('is-invalid');
      // Notificación no intrusiva en forma de toast
      if (typeof showToast === 'function') showToast('La fecha de vencimiento no puede ser pasada. Corrige para continuar.', 'error');
      e.preventDefault();
      return false;
    }
    if (dias <= 30) {
      // Bootstrap no tiene is-warning por defecto; usamos borde manual
      fvInput.classList.add('is-warning');
    }
    return true;
  }

  function toggleExtras(){
    // Muestra/oculta campos de lote y vencimiento según tipo de movimiento
    const esIngresoOPos = (tipoSel.value === 'ingreso' || tipoSel.value === 'ajuste_pos');
    fvWrap.style.display = esIngresoOPos ? 'block' : 'none';
    loteWrap.style.display = esIngresoOPos ? 'block' : 'none';
    destinoWrap.style.display = (tipoSel.value === 'egreso') ? 'block' : 'none';
    // Limpiar selección de lote objetivo si el tipo no lo usa
    if (!(tipoSel.value === 'ajuste_neg')) {
      hiddenTarget.value = '';
      document.querySelectorAll('#inventarios-producto tbody tr').forEach(tr => tr.classList.remove('table-primary'));
    }
    // Limpiar selección positiva si el tipo no lo usa
    if (!(tipoSel.value === 'ingreso' || tipoSel.value === 'ajuste_pos')) {
      selectedInventarioIdPos = null;
      selectionByQuickPos = false;
      // mantener valores escritos manualmente; sólo quitamos resalte
      document.querySelectorAll('#inventarios-producto tbody tr').forEach(tr => tr.classList.remove('table-primary'));
    }
    // Re-render de acciones según tipo y re-aplicar resaltado
    renderInventariosTable();
    reapplyHighlights();
    updateClearButtonVisibility();
  }
  document.addEventListener('DOMContentLoaded', toggleExtras);
  tipoSel.addEventListener('change', toggleExtras);
  async function cargarInventariosProducto() {
    // Carga vía AJAX los inventarios del producto seleccionado para el panel auxiliar
    const id = productoSel.value;
    if (!id) {
      invTableBody.innerHTML = '<tr><td colspan="6" class="text-muted">Seleccione un medicamento para ver sus lotes...</td></tr>';
      inventariosActuales = [];
      clearSelection();
      if (typeof updateLoteAdvice === 'function') { updateLoteAdvice(); }
      return;
    }
    invTableBody.innerHTML = '<tr><td colspan="6" class="text-muted">Cargando lotes...</td></tr>';
    try {
      const resp = await fetch(`{{ route('movimientos.inventarios', ['productoId' => 'ID_REPLACE']) }}`.replace('ID_REPLACE', id));
      if (!resp.ok) throw new Error('Error al cargar inventarios');
      const data = await resp.json();
      inventariosActuales = (data.inventarios || []);
      // Si el lote seleccionado ya no está en la lista, limpiar selección
      if (!inventariosActuales.some(r => Number(r.id) === selectedInventarioId)) {
        hiddenTarget.value = '';
        selectedInventarioId = null;
        selectionByQuick = false;
      }
      renderInventariosTable();
      if (typeof updateLoteAdvice === 'function') { updateLoteAdvice(); }
    } catch (e) {
      invTableBody.innerHTML = `<tr><td colspan="5" class="text-danger">${e.message}</td></tr>`;
    }
  }
  productoSel.addEventListener('change', () => { cargarInventariosProducto(); if (typeof updateLoteAdvice === 'function') { updateLoteAdvice(); } });
  document.addEventListener('DOMContentLoaded', cargarInventariosProducto);
  btnClearLote.addEventListener('click', clearSelection);
    // === Búsqueda profesional (tipoahead) basada en las opciones del select ===
    let productosDataset = [];
    let activeSugIdx = -1;
    function buildProductosDataset() {
      productosDataset = Array.from(productoSel.querySelectorAll('option'))
        .filter(opt => opt.value)
        .map(opt => ({ id: opt.value, nombre: opt.dataset.nombre || opt.textContent, codigo: opt.dataset.codigo || '', texto: opt.textContent }));
    }
    function renderProductoSugerencias(items) {
      productoSugerencias.innerHTML = '';
      if (!items.length) { productoSugerencias.style.display='none'; return; }
      items.slice(0, 20).forEach((it, idx) => {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action';
        a.innerHTML = `<div class="d-flex justify-content-between"><div><strong>${it.nombre}</strong> <span class="text-muted">(${it.codigo})</span></div></div>`;
        a.addEventListener('click', (e) => { e.preventDefault(); seleccionarProductoDesdeSug(it); });
        productoSugerencias.appendChild(a);
      });
      productoSugerencias.style.display='block';
      activeSugIdx = -1;
    }
    function seleccionarProductoDesdeSug(item) {
      productoSel.value = item.id;
      productoSugerencias.style.display='none';
      productoSel.dispatchEvent(new Event('change'));
    }
    function filtrarProductos(term) {
      const t = term.trim().toLowerCase();
      if (!t) return [];
      return productosDataset.filter(p => (
        (p.nombre || '').toLowerCase().includes(t) || (p.codigo || '').toLowerCase().includes(t)
      ));
    }
    function moverSugerenciaActiva(delta) {
      const children = Array.from(productoSugerencias.children);
      if (!children.length) return;
      activeSugIdx = Math.max(0, Math.min(children.length-1, activeSugIdx + delta));
      children.forEach((el, i) => el.classList.toggle('active', i === activeSugIdx));
    }
    document.addEventListener('DOMContentLoaded', buildProductosDataset);
    productoBuscar.addEventListener('input', () => {
      const items = filtrarProductos(productoBuscar.value);
      renderProductoSugerencias(items);
    });
    productoBuscar.addEventListener('keydown', (e) => {
      if (productoSugerencias.style.display !== 'block') return;
      switch (e.key) {
        case 'ArrowDown': e.preventDefault(); moverSugerenciaActiva(1); break;
        case 'ArrowUp': e.preventDefault(); moverSugerenciaActiva(-1); break;
        case 'Enter':
          e.preventDefault();
          const items = Array.from(productoSugerencias.children);
          if (activeSugIdx >= 0 && items[activeSugIdx]) { items[activeSugIdx].click(); }
          break;
        case 'Escape': productoSugerencias.style.display='none'; break;
      }
    });
    document.addEventListener('click', (e) => {
      if (!productoSugerencias.contains(e.target) && e.target !== productoBuscar) {
        productoSugerencias.style.display='none';
      }
    });
  loteInput.addEventListener('input', updateLoteAdvice);
  fvInput.addEventListener('change', updateLoteAdvice);
  form.addEventListener('submit', validateFechaVencimientoBeforeSubmit);
  // Validar que en ENTRADA y AJUSTE + se indique un número de lote (nuevo o seleccionado)
  form.addEventListener('submit', (e) => {
    if (!(tipoSel.value === 'ingreso' || tipoSel.value === 'ajuste_pos')) return;
    const loteVal = (loteInput.value || '').trim();
    if (!loteVal) {
      e.preventDefault();
      if (typeof showToast === 'function') showToast('Debes indicar un número de lote o elegir uno de la tabla para entradas y ajustes positivos.', 'error');
    }
  });
  // Validar que si hay lote elegido, la cantidad no supere su saldo
  form.addEventListener('submit', (e) => {
    if (!(tipoSel.value === 'ajuste_neg')) return;
    const target = hiddenTarget.value ? Number(hiddenTarget.value) : null;
    if (!target) return;
    const fila = inventariosActuales.find(r => Number(r.id) === target);
    if (!fila) return;
    const inputCant = document.querySelector('input[name="cantidad"]');
    const cant = Number(inputCant.value || 0);
    if (cant > Number(fila.cantidad)) {
      e.preventDefault();
        // Notificación como toast (stock insuficiente)
        if (typeof showToast === 'function') showToast('Stock insuficiente: la cantidad supera el saldo del lote seleccionado.', 'error');
    }
  });

  // Pequeño estilo para warning visual en el input de fecha
  const style = document.createElement('style');
  style.textContent = `.is-warning { border-color: #f1c40f !important; box-shadow: 0 0 0 .2rem rgba(241,196,15,.25) !important; }`;
  document.head.appendChild(style);
  
  // existing scripts...
</script>
@endpush
@endsection
 