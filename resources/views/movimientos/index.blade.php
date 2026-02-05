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
    <div class="d-flex gap-2">
      <a href="{{ route('inventario.index') }}" class="btn btn-orange">Ver Inventario <span class="btn-badge ms-1">INV</span></a>
    </div>
  </div>

  @php
    $oldTipo = old('tipo', 'ingreso');
    $oldModalidad = old('modalidad');
    $isEntrada = $oldTipo === 'ingreso';
    $isDistribucion = ($oldTipo === 'egreso' && $oldModalidad === 'distribucion');
    $isConsumo = ($oldTipo === 'egreso' && $oldModalidad === 'consumo');
    $isAjustePos = $oldTipo === 'ajuste_pos';
    $isAjusteNeg = $oldTipo === 'ajuste_neg';
  @endphp

  <ul class="nav nav-tabs mb-3" id="tabs-mov" role="tablist" aria-label="Seleccionar tipo de movimiento">
    <li class="nav-item"><a href="#" class="nav-link {{ $isEntrada ? 'active' : '' }}" role="tab" aria-selected="{{ $isEntrada ? 'true' : 'false' }}" tabindex="0" data-tab="entrada"><i class="fa fa-plus me-1"></i>Entrada</a></li>
    <li class="nav-item"><a href="#" class="nav-link {{ $isDistribucion ? 'active' : '' }}" role="tab" aria-selected="{{ $isDistribucion ? 'true' : 'false' }}" tabindex="0" data-tab="distribucion"><i class="fa fa-share me-1"></i>Distribución <span class="badge tab-badge ms-1">DESTINOS</span></a></li>
    <li class="nav-item"><a href="#" class="nav-link {{ $isConsumo ? 'active' : '' }}" role="tab" aria-selected="{{ $isConsumo ? 'true' : 'false' }}" tabindex="0" data-tab="consumo"><i class="fa fa-user me-1"></i>Consumo <span class="badge tab-badge ms-1">BENEFICIARIOS</span></a></li>
    <li class="nav-item"><a href="#" class="nav-link {{ $isAjustePos ? 'active' : '' }}" role="tab" aria-selected="{{ $isAjustePos ? 'true' : 'false' }}" tabindex="0" data-tab="ajuste_pos"><i class="fa fa-plus-circle me-1"></i>Ajuste +</a></li>
    <li class="nav-item"><a href="#" class="nav-link {{ $isAjusteNeg ? 'active' : '' }}" role="tab" aria-selected="{{ $isAjusteNeg ? 'true' : 'false' }}" tabindex="0" data-tab="ajuste_neg"><i class="fa fa-minus-circle me-1"></i>Ajuste -</a></li>
  </ul>
  <!-- Indicador persistente de sección actual para orientación del usuario -->
  <div class="d-flex align-items-center mb-3" id="section-indicator-wrap">
    <span id="section-indicator" class="section-indicator" aria-live="polite">Sección actual: {{ $isEntrada ? 'Entrada' : ($isDistribucion ? 'Distribución' : ($isConsumo ? 'Consumo' : ($isAjustePos ? 'Ajuste +' : 'Ajuste -'))) }}</span>
    <span id="section-indicator-live" class="visually-hidden" aria-live="polite"></span>
  </div>
  <style>
    /* Botones de tabs usando paleta del sistema (orange) con fallback */
    #tabs-mov .nav-link {
      background-color: var(--color-orange-500, #FF8A00);
      color: #fff;
      border-radius: .5rem;
      margin-right: .25rem;
      border: 1px solid rgba(0,0,0,.05);
    }
    #tabs-mov .nav-link.active {
      background-color: var(--color-orange-600, #FF7300);
      color: #fff;
      box-shadow: 0 2px 6px rgba(0,0,0,.08);
    }
    #tabs-mov .nav-link:hover { background-color: var(--color-orange-400, #FF9E33); color: #fff; }
    #tabs-mov { border-bottom: none; }
    /* Badges dentro de tabs: pill translúcido sobre fondo naranja */
    #tabs-mov .tab-badge {
      background-color: rgba(255,255,255,.18);
      border: 1px solid rgba(255,255,255,.35);
      color: #fff;
      font-weight: 600;
      letter-spacing: .02em;
    }
    /* Botón naranja del sistema reutilizable */
    .btn-orange {
      background-color: var(--color-orange-600, #FF7300);
      color: #fff !important;
      border: 1px solid var(--color-orange-700, #E56200);
      transition: background-color .2s ease, box-shadow .2s ease, transform .05s ease;
    }
    .btn-orange:hover {
      background-color: var(--color-orange-500, #FF8A00);
      color: #fff !important;
      box-shadow: 0 2px 6px rgba(0,0,0,.1);
    }
    .btn-orange:active { transform: translateY(1px); }
    /* Badge decorativo dentro del botón (similar a tabs) */
    .btn-badge {
      display: inline-block;
      background-color: rgba(255,255,255,.18);
      border: 1px solid rgba(255,255,255,.35);
      color: #fff;
      font-size: .75rem;
      padding: .1rem .35rem;
      border-radius: .5rem;
      vertical-align: middle;
    }
    /* Indicador visible y accesible de la sección actual */
    .section-indicator {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      background: linear-gradient(90deg, var(--color-orange-600, #FF7300), var(--color-orange-500, #FF8A00));
      color: #fff;
      padding: .35rem .6rem;
      border-radius: .5rem;
      font-weight: 600;
      box-shadow: 0 2px 6px rgba(0,0,0,.08);
    }
    /* Utilidad de accesibilidad para contenido sólo para lectores de pantalla */
    .visually-hidden {
      position: absolute !important;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap; /* avoid text wrapping */
      border: 0;
    }
  </style>

  {{-- Enlace al Historial de Consumo: visible solo cuando la pestaña Consumo está activa --}}
  <div class="text-end mb-2" id="consumo-hist-link" style="display:none;">
    <a href="{{ route('consumo.historial') }}" class="btn btn-sm btn-orange"><i class="fa fa-list me-1"></i> Historial de Consumo</a>
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
        <input type="hidden" name="modalidad" id="modalidad" value="{{ old('modalidad') }}">
        <input type="hidden" name="tipo" id="tipo" value="{{ $oldTipo }}">
        <div class="col-md-5">
          <label class="form-label">Producto</label>
          <div class="position-relative">
            <input type="text" id="producto_buscar" class="form-control mb-2" placeholder="Buscar por nombre o código..." autocomplete="off">
            <div id="producto_sugerencias" class="list-group position-absolute w-100" style="z-index: 1000; display:none; max-height: 240px; overflow:auto;"></div>
          </div>
          <select name="producto_id" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach($productos as $p)
              <option value="{{ $p->id }}" @selected(old('producto_id')==$p->id) data-nombre="{{ $p->nombre }}" data-codigo="{{ $p->codigo }}" data-tipo="{{ strtolower($p->tipo_producto ?? '') }}">{{ $p->nombre }} ({{ $p->codigo }})</option>
            @endforeach
          </select>
          <div class="d-flex align-items-center justify-content-between mt-2">
            <small class="text-muted">Escribe para buscar, selecciona una sugerencia o usa el listado.</small>
            <span id="tipo-chip" class="badge bg-secondary" title="Tipo de producto" style="display:none;">—</span>
          </div>
        </div>
        {{-- Eliminado select visible de Tipo: se controla por las pestañas superiores. --}}
        <div class="col-md-4" id="destino-wrapper" style="{{ $oldTipo==='egreso' ? '' : 'display:none;' }}">
          <label class="form-label" id="destino-label">Destino</label>
          @php $hayDestinos = isset($destinos) && count($destinos)>0; @endphp
          <select name="destino_id" id="destino_id" class="form-select">
            <option value="">Seleccione destino...</option>
            @if($hayDestinos)
              @foreach($destinos as $d)
                <option value="{{ $d->id }}" @selected(old('destino_id')==$d->id)>{{ $d->nombre }}</option>
              @endforeach
            @endif
          </select>
          @if(!$hayDestinos)
            <div class="alert alert-warning mt-2 p-2 small mb-0">No hay destinos cargados. Ejecute migraciones y seeders (php artisan migrate --seed) o verifique la tabla <code>destinos</code>.</div>
          @endif
        </div>
        <div class="col-md-4" id="beneficiario-wrapper" style="display:none;">
          <label class="form-label">Datos del beneficiario (Consumo)</label>
          <div class="d-flex gap-2">
            <select name="tipo_identificacion" id="tipo_identificacion" class="form-select">
              <option value="">Tipo de identificación...</option>
              @foreach(['estudiante','trabajador','profesor','comunidad'] as $ti)
                <option value="{{ $ti }}" @selected(old('tipo_identificacion')===$ti)>{{ strtoupper($ti) }}</option>
              @endforeach
            </select>
            <select name="sexo" id="sexo" class="form-select" style="max-width: 140px;">
              <option value="">Sexo...</option>
              @foreach(['F','M','otro'] as $sx)
                <option value="{{ $sx }}" @selected(old('sexo')===$sx)>{{ strtoupper($sx) }}</option>
              @endforeach
            </select>
          </div>
          <small class="text-muted">Se registran solo métricas, sin datos personales.</small>
        </div>
        <div class="col-md-2">
          <label class="form-label">Cantidad</label>
          <input type="number" min="1" class="form-control" name="cantidad" value="{{ old('cantidad',1) }}" required>
          <div id="calc-equivalente" class="form-text"></div>
        </div>
        <div class="col-md-2">
          <label class="form-label">Fecha</label>
          <input type="date" class="form-control" name="fecha" value="{{ old('fecha', now()->toDateString()) }}">
        </div>

        <div class="col-md-3" id="fv-wrapper">
          <label class="form-label">Fecha de vencimiento </label>
          <input type="date" class="form-control" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}">
        </div>
        <div class="col-md-3" id="lote-wrapper">
          <label class="form-label">Número de lote </label>
          <input type="text" class="form-control" maxlength="50" name="lote" value="{{ old('lote') }}" placeholder="Ej: L-2025-AX13">
          <div class="form-text mt-1">Sugerencia: usa la tabla inferior para elegir un lote con los botones “+” o “Elegir lote”, o escribe uno nuevo.</div>
          <div id="lote-advice" class="small mt-1 text-muted"></div>
        </div>
        <div class="col-md-3" id="contenido-blister-wrapper" style="display:none;">
          <label class="form-label">Contenido por blíster</label>
          <input type="number" min="1" class="form-control" name="contenido_por_blister" value="{{ old('contenido_por_blister') }}" placeholder="Ej: 10">
          <div class="form-text">Medicamentos: obligatorio. Insumos: oculto.</div>
        </div>
        <div class="col-12" id="banner-blister" style="display:none;">
          <div class="alert alert-info py-2 mb-0" id="banner-blister-text"><strong>Nota:</strong> Operamos solo en blíster (sólidos). No se registran pastillas sueltas.</div>
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
          <button type="submit" class="btn btn-orange">Registrar movimiento <span class="btn-badge ms-1">MOV</span></button>
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
                $tipoLabel = [
                  'ingreso' => 'Ingreso',
                  'egreso' => 'Egreso',
                  'ajuste_pos' => 'Ajuste +',
                  'ajuste_neg' => 'Ajuste -',
                ][$m->tipo] ?? ucfirst($m->tipo);
                $modalidadLabel = null;
                if ($m->tipo === 'egreso') {
                  $modalidadLabel = match($m->modalidad) {
                    'distribucion' => 'Distribución',
                    'consumo' => 'Consumo',
                    default => null,
                  };
                }
              @endphp
              <tr>
                <td>{{ \Carbon\Carbon::parse($m->fecha)->format('d/m/Y') }}</td>
                <td>
                  <strong>{{ $m->producto->nombre ?? '—' }}</strong>
                  <span class="text-muted">({{ $m->producto->codigo ?? '' }})</span>
                </td>
                <td>
                  <div class="d-flex flex-column gap-1">
                    <span class="badge bg-{{ $badge }} text-uppercase">{{ $tipoLabel }}@if($modalidadLabel) · {{ $modalidadLabel }} @endif</span>
                    @if($modalidadLabel === 'Distribución')
                      <small class="text-muted">No descuenta stock real</small>
                    @elseif($modalidadLabel === 'Consumo')
                      <small class="text-muted">Entrega directa a beneficiario</small>
                    @endif
                  </div>
                </td>
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
  const beneficiarioWrap = document.getElementById('beneficiario-wrapper');
  const modalidadInput = document.getElementById('modalidad');
  const bannerBlister = document.getElementById('banner-blister');
  const contenidoBlisterWrap = document.getElementById('contenido-blister-wrapper');
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
  const contenidoBlisterInput = document.querySelector('input[name="contenido_por_blister"]');
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
      const um = (r.um_operativa || '').toLowerCase();
      const cont = Number(r.contenido_por_blister || 0);
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
        <td>
          ${agotado ? `<strong>0</strong> <span class="badge bg-secondary ms-2" title="Sin stock">Agotado</span>` : `<strong>${r.cantidad}</strong>`}
          ${um === 'blister' && cont > 0 ? `<span class="badge bg-info ms-2" title="Contenido por blíster">1 blíster = ${cont}</span>` : ''}
        </td>
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
    // Si el lote seleccionado es blíster y tiene contenido, bloquear edición y prefijar
    lockContenidoPorBlisterFromInventario(id);
    // Forzar tipo a AJUSTE + si corresponde
    if (forceAjustePos) {
      prevTipoValuePos = tipoSel.value;
      selectionByQuickPos = true;
      tipoSel.value = 'ajuste_pos';
      tipoSel.dispatchEvent(new Event('change'));
    }
    updateLoteAdvice();
    updateEquivalenteUnidades();
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
      // Bloquear contenido_por_blister si el lote existente ya tiene uno definido
      if (contenidoBlisterInput) {
        if ((match.um_operativa || '').toLowerCase() === 'blister' && Number(match.contenido_por_blister || 0) > 0) {
          contenidoBlisterInput.value = String(Number(match.contenido_por_blister));
          contenidoBlisterInput.setAttribute('readonly', 'readonly');
          contenidoBlisterInput.classList.add('disabled');
        } else {
          contenidoBlisterInput.removeAttribute('readonly');
          contenidoBlisterInput.classList.remove('disabled');
        }
      }
    } else {
      loteAdvice.textContent = '';
      loteAdvice.className = 'small mt-1 text-muted';
      if (contenidoBlisterInput) {
        contenidoBlisterInput.removeAttribute('readonly');
        contenidoBlisterInput.classList.remove('disabled');
      }
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
    const esConsumo = (tipoSel.value === 'egreso' && (modalidadInput.value || '') === 'consumo');
    beneficiarioWrap.style.display = esConsumo ? 'block' : 'none';
    // Banner blíster visible en Entrada y Consumo
    bannerBlister.style.display = (esIngresoOPos || esConsumo) ? 'block' : 'none';
    // Mostrar el campo "Contenido por blíster" sólo para medicamentos en Entrada/Ajuste +
    const opt = productoSel.options[productoSel.selectedIndex];
    const tipo = (opt?.dataset?.tipo || '').toLowerCase();
    contenidoBlisterWrap.style.display = (esIngresoOPos && tipo === 'medicamento') ? 'block' : 'none';
    // Enlace a Historial de Consumo sólo cuando está activa la pestaña Consumo
    const consumoHistLink = document.getElementById('consumo-hist-link');
    if (consumoHistLink) consumoHistLink.style.display = esConsumo ? 'block' : 'none';
    // Cambiar etiqueta de destino: "Distribución" cuando modalidad es distribucion
    const destLabel = document.getElementById('destino-label');
    if (tipoSel.value === 'egreso') {
      destLabel.textContent = (modalidadInput.value === 'distribucion') ? 'Distribución' : 'Destino';
    }
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
    // Sincronizar el estado visual del tab y el indicador cuando el cambio no proviene de un click de tab
    updateTabActiveFromState();
    updateSectionIndicatorFromCurrent();
  }
  document.addEventListener('DOMContentLoaded', toggleExtras);
  // tipoSel es oculto; toggleExtras se invoca desde setActiveTab
  // Tabs de navegación
  const tabs = document.querySelectorAll('#tabs-mov a.nav-link');
  function setActiveTab(tab) {
    tabs.forEach(a => {
      const isActive = (a.dataset.tab === tab);
      a.classList.toggle('active', isActive);
      a.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
    switch(tab){
      case 'entrada': tipoSel.value='ingreso'; modalidadInput.value=''; break;
      case 'distribucion': tipoSel.value='egreso'; modalidadInput.value='distribucion'; break;
      case 'consumo': tipoSel.value='egreso'; modalidadInput.value='consumo'; break;
      case 'ajuste_pos': tipoSel.value='ajuste_pos'; modalidadInput.value=''; break;
      case 'ajuste_neg': tipoSel.value='ajuste_neg'; modalidadInput.value=''; break;
    }
    // Actualizar visibilidad de campos tras cambiar tipo/modalidad
    toggleExtras();
    // Actualizar indicador visible y anuncio accesible
    updateSectionIndicatorFromCurrent();
  }
  tabs.forEach(a => a.addEventListener('click', (e) => { e.preventDefault(); setActiveTab(a.dataset.tab); }));
  // Navegación con teclado en tabs (Izquierda/Derecha para moverse, Enter/Espacio para activar)
  tabs.forEach((a, idx) => {
    a.addEventListener('keydown', (e) => {
      const total = tabs.length;
      let targetIdx = idx;
      if (e.key === 'ArrowRight') { e.preventDefault(); targetIdx = Math.min(total-1, idx+1); tabs[targetIdx].focus(); }
      else if (e.key === 'ArrowLeft') { e.preventDefault(); targetIdx = Math.max(0, idx-1); tabs[targetIdx].focus(); }
      else if (e.key === 'Home') { e.preventDefault(); tabs[0].focus(); }
      else if (e.key === 'End') { e.preventDefault(); tabs[total-1].focus(); }
      else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setActiveTab(a.dataset.tab); }
    });
  });
  function currentTabKey(){
    if (tipoSel.value === 'ingreso') return 'entrada';
    if (tipoSel.value === 'egreso' && modalidadInput.value === 'distribucion') return 'distribucion';
    if (tipoSel.value === 'egreso' && modalidadInput.value === 'consumo') return 'consumo';
    if (tipoSel.value === 'ajuste_pos') return 'ajuste_pos';
    if (tipoSel.value === 'ajuste_neg') return 'ajuste_neg';
    return 'entrada';
  }
  function updateTabActiveFromState(){
    const key = currentTabKey();
    tabs.forEach(a => {
      const isActive = (a.dataset.tab === key);
      a.classList.toggle('active', isActive);
      a.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
  }
  function labelForCurrentSection(){
    if (tipoSel.value === 'ingreso') return 'Entrada';
    if (tipoSel.value === 'egreso' && modalidadInput.value === 'distribucion') return 'Distribución';
    if (tipoSel.value === 'egreso' && modalidadInput.value === 'consumo') return 'Consumo';
    if (tipoSel.value === 'ajuste_pos') return 'Ajuste +';
    if (tipoSel.value === 'ajuste_neg') return 'Ajuste -';
    return 'Movimiento';
  }
  function updateSectionIndicatorFromCurrent(){
    const label = labelForCurrentSection();
    const ind = document.getElementById('section-indicator');
    const live = document.getElementById('section-indicator-live');
    if (ind) ind.textContent = `Sección actual: ${label}`;
    if (live) live.textContent = `Sección actual: ${label}`;
  }
  // Activar tab inicial a partir del estado del select y modalidad
  document.addEventListener('DOMContentLoaded', () => {
    let initial = 'entrada';
    if (tipoSel.value === 'egreso' && modalidadInput.value === 'distribucion') initial = 'distribucion';
    else if (tipoSel.value === 'egreso' && modalidadInput.value === 'consumo') initial = 'consumo';
    else if (tipoSel.value === 'ajuste_pos') initial = 'ajuste_pos';
    else if (tipoSel.value === 'ajuste_neg') initial = 'ajuste_neg';
    setActiveTab(initial);
    toggleExtras();
    updateSectionIndicatorFromCurrent();
  });
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
      // Actualizar chip de tipo y banner según producto
      actualizarTipoChipYBanner();
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
        .map(opt => ({ id: opt.value, nombre: opt.dataset.nombre || opt.textContent, codigo: opt.dataset.codigo || '', tipo: (opt.dataset.tipo || '').toLowerCase(), texto: opt.textContent }));
    }
    function renderProductoSugerencias(items) {
      productoSugerencias.innerHTML = '';
      if (!items.length) { productoSugerencias.style.display='none'; return; }
      items.slice(0, 20).forEach((it, idx) => {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action';
        const tipoText = it.tipo ? it.tipo.toUpperCase() : '—';
        const tipoBadge = `<span class="badge bg-secondary ms-2" title="Tipo">${tipoText}</span>`;
        a.innerHTML = `<div class="d-flex justify-content-between"><div><strong>${it.nombre}</strong> <span class="text-muted">(${it.codigo})</span>${tipoBadge}</div></div>`;
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
  // Sincronizar cálculo de equivalente a unidades
  if (inputCantidad) {
    inputCantidad.addEventListener('input', updateEquivalenteUnidades);
  }
  if (contenidoBlisterInput) {
    contenidoBlisterInput.addEventListener('input', () => { updateEquivalenteUnidades(); validarContenidoVsInventario(); });
  }
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
  // Encuentra inventario por ID y bloquea el contenido por blíster si aplica
  function lockContenidoPorBlisterFromInventario(id) {
    if (!contenidoBlisterInput) return;
    const inv = inventariosActuales.find(r => Number(r.id) === Number(id));
    if (!inv) return;
    const tipo = (productoSel.options[productoSel.selectedIndex]?.dataset?.tipo || '').toLowerCase();
    if (tipo !== 'medicamento') { return; }
    if ((inv.um_operativa || '').toLowerCase() === 'blister' && Number(inv.contenido_por_blister || 0) > 0) {
      contenidoBlisterInput.value = String(Number(inv.contenido_por_blister));
      contenidoBlisterInput.setAttribute('readonly', 'readonly');
      contenidoBlisterInput.classList.add('disabled');
    } else {
      contenidoBlisterInput.removeAttribute('readonly');
      contenidoBlisterInput.classList.remove('disabled');
    }
  }

  // Muestra equivalente en unidades para medicamentos (cantidad × contenido_por_blister)
  function updateEquivalenteUnidades() {
    if (!contenidoBlisterInput || !inputCantidad) return;
    const tipo = (productoSel.options[productoSel.selectedIndex]?.dataset?.tipo || '').toLowerCase();
    const calc = document.getElementById('calc-equivalente');
    if (!calc) return;
    if (tipo !== 'medicamento') { calc.textContent = ''; return; }
    const cant = Number(inputCantidad.value || 0);
    const cont = Number(contenidoBlisterInput.value || 0);
    if (cant > 0 && cont > 0) {
      calc.textContent = `Equivalente aproximado: ${cant * cont} unidades`;
    } else {
      calc.textContent = '';
    }
  }

  // Valida que, si se está usando un lote existente, el contenido por blíster coincida
  function validarContenidoVsInventario() {
    if (!contenidoBlisterInput) return true;
    const tipo = (productoSel.options[productoSel.selectedIndex]?.dataset?.tipo || '').toLowerCase();
    if (tipo !== 'medicamento') return true;
    const loteVal = (loteInput.value || '').trim();
    const fvVal = (fvInput.value || '').trim();
    const match = inventariosActuales.find(r => (r.lote || '') === loteVal && (r.fecha_vencimiento || '') === fvVal);
    if (!match) return true;
    const contInput = Number(contenidoBlisterInput.value || 0);
    const contInv = Number(match.contenido_por_blister || 0);
    if (contInv > 0 && contInput > 0 && contInput !== contInv) {
      contenidoBlisterInput.classList.add('is-invalid');
      if (typeof showToast === 'function') showToast('El contenido por blíster no coincide con el lote existente. Corrige o usa un nuevo lote.', 'error');
      return false;
    }
    contenidoBlisterInput.classList.remove('is-invalid');
    return true;
  }

  // Validación adicional en submit para medicamentos: contenido por blíster coherente
  form.addEventListener('submit', (e) => {
    if (!contenidoBlisterInput) return;
    if (!(tipoSel.value === 'ingreso' || tipoSel.value === 'ajuste_pos')) return;
    const tipo = (productoSel.options[productoSel.selectedIndex]?.dataset?.tipo || '').toLowerCase();
    if (tipo !== 'medicamento') return;
    const cont = Number(contenidoBlisterInput.value || 0);
    if (cont <= 0) {
      e.preventDefault();
      if (typeof showToast === 'function') showToast('Debes indicar el contenido por blíster (entero > 0) para medicamentos.', 'error');
      return;
    }
    if (!validarContenidoVsInventario()) {
      e.preventDefault();
      return;
    }
  });

  // Mostrar chip de tipo (Medicamento/Insumo) y ajustar banner segun producto seleccionado
  function actualizarTipoChipYBanner() {
    const chip = document.getElementById('tipo-chip');
    if (!chip) return;
    const opt = productoSel.options[productoSel.selectedIndex];
    const tipo = (opt?.dataset?.tipo || '').toLowerCase();
    if (!opt || !tipo) { chip.style.display='none'; return; }
    chip.style.display='inline-block';
    chip.className = 'badge';
    let text = '—';
    if (tipo === 'medicamento') { chip.classList.add('bg-primary'); text = 'MEDICAMENTO'; }
    else if (tipo === 'insumo') { chip.classList.add('bg-dark'); text = 'INSUMO'; }
    else { chip.classList.add('bg-secondary'); }
    chip.textContent = text;
    // Ajustar banner de UM operativa
    const bannerText = document.getElementById('banner-blister-text');
    const mostrarBanner = (tipoSel.value === 'ingreso' || tipoSel.value === 'ajuste_pos' || (tipoSel.value === 'egreso' && (modalidadInput.value || '') === 'consumo'));
    bannerBlister.style.display = mostrarBanner ? 'block' : 'none';
    if (bannerText) {
      if (tipo === 'medicamento') {
        bannerText.innerHTML = '<strong>Nota:</strong> Este producto opera en <b>blíster</b>. No se registran pastillas sueltas.';
        contenidoBlisterWrap.style.display = (tipoSel.value === 'ingreso' || tipoSel.value === 'ajuste_pos') ? 'block' : 'none';
      } else if (tipo === 'insumo') {
        bannerText.innerHTML = '<strong>Nota:</strong> Este producto opera en <b>unidad</b>.';
        contenidoBlisterWrap.style.display = 'none';
      } else {
        bannerText.innerHTML = '<strong>Nota:</strong> Selecciona un producto para ver su unidad operativa.';
        contenidoBlisterWrap.style.display = 'none';
      }
    }
  }
  document.addEventListener('DOMContentLoaded', actualizarTipoChipYBanner);
  productoSel.addEventListener('change', actualizarTipoChipYBanner);
  // Cuando cambie tipo/modalidad, re-sincronizar banner
  document.addEventListener('DOMContentLoaded', () => {
    const observer = new MutationObserver(() => actualizarTipoChipYBanner());
    observer.observe(tipoSel, { attributes: true, attributeFilter: ['value'] });
  });
  
  // existing scripts...
</script>
@endpush
@endsection
 