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
      <a href="{{ route('inventario.index') }}" class="btn btn-orange d-flex align-items-center">
      <span class="me-2">
        <i class="fa fa-box-open fa-lg" style="color:#fff; background:rgba(255,255,255,0.15); border-radius:50%; padding:4px;"></i>
      </span>
      Ver Inventario
      </a>
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
      
    /// función de ayuda para formatear el destino de un movimiento de egreso, aplicando reglas de normalización y formato legible. Se usa en la tabla de últimos movimientos para mostrar el destino de forma más clara y consistente, incluso si los datos originales son variados o inconsistentes. 
    if (! function_exists('mov_formatear_destino')) {
      function mov_formatear_destino($texto) {
        $texto = trim((string) $texto);
        if ($texto === '') {
          return '-';
        }
        if (strpos($texto, '/') === false) {
          return $texto;
        }

        $partes = array_values(array_filter(array_map('trim', explode('/', $texto))));
        if (count($partes) <= 1) {
          return $texto;
        }

        $detalleParts = [];
        $principal = array_shift($partes);

        if (preg_match('/^([A-ZÁÉÍÓÚÜÑ0-9]{2,})\s+(.+)$/u', $principal, $matches)) {
          $detalleParts[] = trim($matches[2]);
          $principal = trim($matches[1]);
        }

        $detalleParts = array_merge($detalleParts, $partes);
        $detalleParts = array_map('trim', $detalleParts);

        if (empty($detalleParts)) {
          return $principal;
        }

        if (count($detalleParts) === 1) {
          $detalle = $detalleParts[0];
        } else {
          $ultimo = array_pop($detalleParts);
          $conector = ' y ';
          $normalizado = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(ltrim($ultimo)));
          if (\Illuminate\Support\Str::startsWith($normalizado, ['i', 'hi'])) {
            $conector = ' e ';
          }
          $detalle = implode(', ', array_filter($detalleParts, fn($p) => $p !== ''));
          $ultimo = trim($ultimo);
          $detalle = $detalle !== '' ? $detalle . $conector . $ultimo : $ultimo;
        }

        return trim($principal) . ' (' . $detalle . ')';
      }
    }
  @endphp
  @php
    $productosFrecuentes = $productosFrecuentes ?? [];
    $hasTopProductos = !empty($productosFrecuentes);
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
    #producto_sugerencias {
      border: 1px solid rgba(255,138,0,.3);
      border-radius: .75rem;
      background: linear-gradient(135deg, rgba(255,255,255,.98), rgba(255,247,237,.97));
      box-shadow: 0 12px 30px rgba(31,31,31,.15);
      padding: .25rem;
      top: calc(100% + .25rem) !important;
      bottom: auto !important;
      left: 0;
      right: auto;
      transform: none !important;
    }
    #producto_sugerencias .list-group-item {
      background-color: transparent;
      border: none;
      border-radius: .65rem;
      margin-bottom: .25rem;
    }
    #producto_sugerencias .list-group-item-action:hover,
    #producto_sugerencias .list-group-item-action.active {
      background-color: rgba(255,138,0,.12);
      color: #c04a00;
    }
    #producto_sugerencias .list-group-item-heading {
      font-size: .75rem;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: #a05900;
      font-weight: 600;
      cursor: default;
    }
    .producto-quick-tools {
      background: #fff;
      border: 1px solid rgba(0,0,0,.05);
      border-radius: .75rem;
      padding: .65rem;
      box-shadow: 0 4px 12px rgba(0,0,0,.04);
    }
    .producto-quick-tools .btn-group .btn.active {
      background-color: var(--color-orange-600, #FF7300);
      color: #fff;
      border-color: var(--color-orange-600, #FF7300);
    }
    .producto-top-chip {
      border-radius: 999px;
      background: rgba(255,115,0,.08);
      border-color: transparent;
      color: #b14a00;
    }
    .producto-top-chip:hover {
      background: rgba(255,115,0,.15);
      color: #a14200;
    }
    .producto-top-chip .badge {
      background: rgba(255,255,255,.7);
      color: #5a3200;
    }

    /* === Estética unificada para tablas de lotes y últimos movimientos === */
    .table-lotes,
    .table-mov-ultimos {
      margin-bottom: 0;
      border: 1px solid var(--slate-border, #d9e0e6);
      border-radius: .75rem;
      overflow: hidden;
      background: var(--slate-surface, #fff);
    }

    .table-lotes thead th,
    .table-mov-ultimos thead th {
      background: var(--slate-surface-soft, #f4f7fa);
      color: var(--txt, #1f2937);
      font-weight: 700;
      border-bottom: 1px solid var(--slate-border, #d9e0e6);
      white-space: nowrap;
    }

    .table-lotes tbody td,
    .table-mov-ultimos tbody td {
      background: var(--slate-surface, #fff);
      color: var(--txt, #1f2937);
      border-color: var(--slate-border, #e5e7eb);
      vertical-align: middle;
    }

    .table-lotes.table-hover tbody tr:hover > td,
    .table-mov-ultimos.table-hover tbody tr:hover > td {
      background: rgba(255, 106, 23, .08);
      transition: background-color .15s ease;
    }

    /* Fila seleccionada desde tabla de lotes (usa .table-primary desde JS) */
    .table-lotes tbody tr.table-primary > td {
      background: rgba(255, 106, 23, .16) !important;
      border-color: rgba(255, 106, 23, .35);
      color: var(--txt, #1f2937);
    }

    /* Badges y micro-estados específicos del panel de lotes */
    .table-lotes .badge-prioridad {
      background: linear-gradient(135deg, var(--accent, #FF6A17), #ff9f58) !important;
      color: #fff !important;
      border: 1px solid rgba(255, 106, 23, .55);
      box-shadow: 0 4px 10px rgba(255, 106, 23, .2);
      font-weight: 700;
      letter-spacing: .01em;
    }

    .table-lotes .badge-vencimiento.bg-info,
    .table-mov-ultimos .badge.bg-info {
      background: #ecfeff !important;
      color: #155e75 !important;
      border: 1px solid #67e8f9;
      font-weight: 700;
    }

    .table-lotes .badge-vencimiento.bg-warning,
    .table-lotes .badge-vencimiento.text-dark,
    .table-mov-ultimos .badge.bg-warning,
    .table-mov-ultimos .badge.text-dark {
      background: #fffbeb !important;
      color: #92400e !important;
      border: 1px solid #fcd34d;
      font-weight: 700;
    }

    .table-lotes .badge-vencimiento.bg-danger,
    .table-mov-ultimos .badge.bg-danger {
      background: #fff1f2 !important;
      color: #b42318 !important;
      border: 1px solid #fda4af;
      font-weight: 700;
    }

    .table-lotes .badge-agotado,
    .table-lotes .badge.bg-secondary {
      background: #eef2f6 !important;
      color: #475569 !important;
      border: 1px solid #cbd5e1;
      font-weight: 700;
    }

    .table-lotes .sin-vencimiento-chip {
      color: #64748b;
      font-weight: 600;
      background: #f8fafc;
      border: 1px dashed #cbd5e1;
      border-radius: .5rem;
      padding: .15rem .45rem;
      display: inline-block;
    }

    /* Botones de acciones dentro de lotes: misma familia visual del sistema */
    #inventarios-producto .btn-outline-primary,
    #inventarios-producto .btn-outline-success,
    #inventarios-producto .btn-outline-danger,
    #inventarios-producto .btn-outline-secondary {
      border-radius: .55rem;
      font-weight: 600;
      transition: all .15s ease;
    }

    #inventarios-producto .btn-outline-primary {
      color: var(--accent, #FF6A17);
      border-color: rgba(255, 106, 23, .45);
      background: rgba(255, 106, 23, .05);
    }
    #inventarios-producto .btn-outline-primary:hover {
      color: #fff;
      background: var(--accent, #FF6A17);
      border-color: var(--accent, #FF6A17);
    }

    #inventarios-producto .btn-outline-success {
      color: #0f766e;
      border-color: #5eead4;
      background: #f0fdfa;
    }
    #inventarios-producto .btn-outline-success:hover {
      color: #fff;
      background: #0f766e;
      border-color: #0f766e;
    }

    #inventarios-producto .btn-outline-danger {
      color: #b42318;
      border-color: #fda4af;
      background: #fff1f2;
    }
    #inventarios-producto .btn-outline-danger:hover {
      color: #fff;
      background: #b42318;
      border-color: #b42318;
    }

    #inventarios-producto .btn-outline-secondary {
      color: var(--txt-sec, #4b5563);
      border-color: var(--slate-border, #d1d5db);
      background: var(--slate-surface-soft, #f9fafb);
    }
    #inventarios-producto .btn-outline-secondary:hover {
      color: var(--txt, #111827);
      border-color: var(--slate-line, #9ca3af);
      background: var(--slate-surface, #fff);
    }

    /* Badges de tipo y vencimiento en últimos movimientos con contraste suave */
    .table-mov-ultimos .badge.bg-success { background: #ecfdf5 !important; color: #047857 !important; border: 1px solid #6ee7b7; }
    .table-mov-ultimos .badge.bg-primary { background: #eff6ff !important; color: #1d4ed8 !important; border: 1px solid #93c5fd; }
    .table-mov-ultimos .badge.bg-secondary { background: #f1f5f9 !important; color: #475569 !important; border: 1px solid #cbd5e1; }
    .table-mov-ultimos .badge.bg-dark { background: #1f2937 !important; color: #f9fafb !important; border: 1px solid #374151; }

    /* Badge naranja para el chip de tipo de producto */
    .badge.bg-orange {
      background: var(--color-orange-600, #FF7300) !important;
      color: #fff !important;
      border: 1px solid var(--color-orange-700, #E56200);
    }
  </style>

  {{-- Enlace al Historial de Consumo: visible solo cuando la pestaña Consumo está activa --}}
  <div class="text-end mb-2" id="consumo-hist-link" style="display:none;">
    <a href="{{ route('consumo.historial') }}" class="btn btn-sm btn-orange"><i class="fa fa-list me-1"></i> Historial de Consumo</a>
  </div>

  @php
    $allErrors = $errors->messages();
    $otherErrors = collect($allErrors)->except(['destino_id', 'tipo_identificacion', 'sexo'])->flatten();
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
  @if ($errors->has('tipo_identificacion'))
    {{-- Error específico de tipo de beneficiario para consumo como toast --}}
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (typeof showToast === 'function') {
          showToast(@json($errors->first('tipo_identificacion')), 'error');
        }
      });
    </script>
  @endif
  @if ($errors->has('sexo'))
    {{-- Error específico de sexo para consumo como toast --}}
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (typeof showToast === 'function') {
          showToast(@json($errors->first('sexo')), 'error');
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
        <div class="col-12 col-xl-8">
          <label class="form-label">Producto</label>
          <div class="position-relative">
            <input type="text" id="producto_buscar" class="form-control mb-2" maxlength="35" placeholder="Buscar por nombre o código..." autocomplete="off">
            <div id="producto_sugerencias" class="list-group position-absolute w-100" style="z-index: 1000; display:none; max-height: 240px; overflow:auto;"></div>
          </div>
          <small class="form-text text-muted" id="producto-buscar-hint">El buscador recorre todo el catálogo; los filtros solo acotan la lista desplegable inferior.</small>
          <select name="producto_id" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach($productos as $p)
              <option value="{{ $p->id }}" @selected(old('producto_id')==$p->id) data-nombre="{{ $p->nombre }}" data-codigo="{{ $p->codigo }}" data-tipo="{{ strtolower($p->tipo_producto ?? '') }}">{{ $p->nombre }} ({{ $p->codigo }})</option>
            @endforeach
          </select>

          <div class="d-flex align-items-center justify-content-between mt-2">
            <small class="text-muted">
              Puedes buscar por nombre o código, o seleccionar manualmente desde la lista de abajo.
            </small>
            <span id="tipo-chip" class="badge bg-secondary" title="Tipo de producto" style="display:none;">—</span>
          </div>
          <div class="producto-quick-tools mt-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
              <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small text-uppercase fw-semibold">Filtros rápidos:</span>
                <div class="btn-group btn-group-sm" role="group" aria-label="Filtro por tipo" id="tipo-filter-buttons">
                  <button type="button" class="btn btn-outline-secondary btn-tipo-filter active" data-tipo="">Todos</button>
                  <button type="button" class="btn btn-outline-secondary btn-tipo-filter" data-tipo="medicamento">Medicamentos</button>
                  <button type="button" class="btn btn-outline-secondary btn-tipo-filter" data-tipo="insumo">Insumos</button>
                </div>
              </div>
              <button type="button" class="btn btn-link btn-sm text-decoration-none" id="btn-ver-top-sugerencias" {{ $hasTopProductos ? '' : 'disabled' }}>
                <i class="fa fa-star text-warning me-1"></i>Ver más usados
              </button>
            </div>
            @if($hasTopProductos)
              <div class="mt-2 d-flex flex-wrap gap-2" id="producto-top-chips">
                @foreach($productosFrecuentes as $fav)
                  <button type="button" class="btn btn-outline-primary btn-sm producto-top-chip" data-id="{{ $fav['id'] }}" data-nombre="{{ $fav['nombre'] }}" data-codigo="{{ $fav['codigo'] }}" data-tipo="{{ $fav['tipo'] }}" title="{{ $fav['uso'] }} movimiento(s) registrados">
                    {{ \Illuminate\Support\Str::limit($fav['nombre'], 24) }}
                    <span class="badge ms-1">{{ $fav['uso'] }}</span>
                  </button>
                @endforeach
              </div>
            @else
              <small class="text-muted d-block mt-2">Se mostrarán atajos en cuanto registres movimientos frecuentes.</small>
            @endif
            <small id="producto-filter-summary" class="text-muted d-block mt-2" aria-live="polite"></small>
          </div>
        </div>

        <div class="col-12 col-xl-4">
          <div class="row g-3">
            {{-- Eliminado select visible de Tipo: se controla por las pestañas superiores. --}}
            <div class="col-12" id="destino-wrapper" style="{{ $oldTipo==='egreso' ? '' : 'display:none;' }}">
              <label class="form-label" id="destino-label">Destino</label>
              @php $hayDestinos = isset($destinos) && count($destinos)>0; @endphp
              <select name="destino_id" id="destino_id" class="form-select">
                <option value="">Seleccione destino...</option>
                @if($hayDestinos)
                  @foreach($destinos as $d)
                    <option value="{{ $d->id }}" @selected(old('destino_id')==$d->id)>{{ mov_formatear_destino($d->nombre) }}</option>
                  @endforeach
                @endif
              </select>
              @if(!$hayDestinos)
                <div class="alert alert-warning mt-2 p-2 small mb-0">No hay destinos cargados. Ejecute migraciones y seeders (php artisan migrate --seed) o verifique la tabla <code>destinos</code>.</div>
              @endif
            </div>

            <div class="col-12" id="beneficiario-wrapper" style="display:none;">
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
                  @foreach(['F','M'] as $sx)
                    <option value="{{ $sx }}" @selected(old('sexo')===$sx)>{{ strtoupper($sx) }}</option>
                  @endforeach
                </select>
              </div>
              <small class="text-muted">Se registran solo métricas, sin datos personales.</small>
            </div>

            <div class="col-sm-6">
              <label class="form-label">Cantidad</label>
              <input type="number" min="1" class="form-control" name="cantidad" value="{{ old('cantidad',1) }}" required>
              <div id="calc-equivalente" class="form-text"></div>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Fecha</label>
              <input type="date" class="form-control" name="fecha" value="{{ old('fecha', now()->toDateString()) }}">
            </div>
          </div>
        </div>

        <div class="col-12">
          <div class="row g-3">
            <div class="col-md-4 col-xl-3" id="fv-wrapper">
              <label class="form-label">Fecha de vencimiento </label>
              <input type="date" class="form-control" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}">
            </div>
            <div class="col-md-8 col-xl-4" id="lote-wrapper">
              <label class="form-label">Número de lote </label>
              <input type="text" class="form-control" maxlength="35" name="lote" value="{{ old('lote') }}" placeholder="Ej: L-2025-AX13">
              <div class="form-text mt-1">Sugerencia: usa la tabla inferior para elegir un lote con los botones “+” o “Elegir lote”, o escribe uno nuevo.</div>
              <div id="lote-advice" class="small mt-1 text-muted"></div>
            </div>
            <div class="col-md-4 col-xl-2" id="contenido-blister-wrapper" style="display:none;">
              <label class="form-label">Contenido por blíster</label>
              <input type="number" min="1" class="form-control" name="contenido_por_blister" value="{{ old('contenido_por_blister') }}" placeholder="Ej: 10">
              <div class="form-text">Medicamentos: obligatorio. Insumos: oculto.</div>
            </div>
            <div class="col-md-4 col-xl-3">
              <label class="form-label">Motivo</label>
              <input type="text" class="form-control" maxlength="40" name="motivo" value="{{ old('motivo') }}" placeholder="Opcional">
            </div>
            <div class="col-md-8 col-xl-6">
              <label class="form-label">Observaciones</label>
              <input type="text" class="form-control" maxlength="60" name="observaciones" value="{{ old('observaciones') }}" placeholder="Opcional">
            </div>
          </div>
        </div>

        <div class="col-12" id="banner-blister" style="display:none;">
          <div class="alert py-2 mb-0" id="banner-blister-text" style="background: var(--color-orange-100, #FFF7ED); color: var(--color-orange-700, #B45309); border: 1px solid var(--color-orange-300, #FDBA74);">
            <strong>Nota:</strong> Operamos solo en blíster (sólidos). No se registran pastillas sueltas.
          </div>
        </div>

        <div class="col-12 d-flex justify-content-end">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-orange d-flex align-items-center gap-2">
              <i class="fa fa-save"></i>
              <span>Registrar Movimiento</span>
            </button>
            
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      {{-- Panel auxiliar: lotes y vencimientos del producto seleccionado.
           Orden FEFO/FIFO y selector para autocompletar lote+fecha en el formulario. --}}
          <div class="d-flex align-items-center justify-content-between mt-2">
            <small class="text-muted">Escribe para buscar (consulta en vivo) o usa los últimos 50 listados.</small>
        <div class="d-flex align-items-center gap-2">
          <small class="text-muted me-2">Ayuda visual para no afectar registros previos</small>
          <button type="button" id="btn-clear-lote" class="btn btn-sm btn-outline-secondary" style="display:none;">Quitar selección</button>
        </div>
      </div>
      <div id="inventarios-producto" class="table-responsive">
        <table class="table table-sm table-hover align-middle table-lotes">
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
      <div id="expired-action-guide" class="alert mt-3 mb-0" style="display:none; border:1px solid var(--color-orange-300, #FDBA74); background:var(--color-orange-100, #FFF7ED); color:var(--color-orange-700, #B45309);">
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
          <div>
            <strong>Lote vencido con stock detectado.</strong>
            <div class="small mt-1">
              Para volver a operar este producto:
              <ol class="mb-0 mt-1">
                <li>Abre <b>Ajuste -</b> y deja en cero el lote vencido (motivo sugerido: <i>Baja por vencimiento</i>).</li>
                <li>Registra una <b>Entrada</b> con lote nuevo y fecha vigente.</li>
                <li>Luego podrás hacer <b>Distribución</b> y <b>Consumo</b> normalmente.</li>
              </ol>
            </div>
          </div>
          <button type="button" id="btn-go-ajuste-neg" class="btn btn-sm btn-outline-danger">
            Ir a Ajuste -
          </button>
        </div>
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
        <table class="table table-hover align-middle table-mov-ultimos">
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
                  @php
                    $dest = $m->destino;
                    $destinoCrudo = $dest?->nombre ?? $m->salida ?? '-';
                    $destinoMostrar = mov_formatear_destino($destinoCrudo);
                  @endphp
                  @if($m->tipo==='egreso')
                    <span class="badge bg-secondary" title="Destino normalizado">{{ $destinoMostrar }}</span>
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
  const expiredActionGuide = document.getElementById('expired-action-guide');
  const btnGoAjusteNeg = document.getElementById('btn-go-ajuste-neg');
  let selectedInventarioId = null; // selección negativa (ajuste −)
  let selectionByQuick = false; // true si proviene del botón "−"
  let prevTipoValue = tipoSel.value; // almacena el tipo antes de forzar egreso
  // Estado paralelo para selección en AJUSTE + / ENTRADA
  let selectedInventarioIdPos = null; // id de inventario seleccionado para sumar
  let selectionByQuickPos = false; // true si proviene del botón "+"
  let prevTipoValuePos = tipoSel.value; // tipo previo antes de forzar ajuste_pos

  const INVENTARIO_ROW_SELECTOR = '#inventarios-producto tbody tr';

  function clearInventarioRowSelection() {
    document.querySelectorAll(INVENTARIO_ROW_SELECTOR).forEach(tr => tr.classList.remove('table-primary'));
  }

  function findInventarioButtonById(id, selectors) {
    for (const selector of selectors) {
      const btn = document.querySelector(`${selector}[data-id="${id}"]`);
      if (btn) return btn;
    }
    return null;
  }

  function highlightInventarioRowFromButton(btn) {
    if (btn) btn.closest('tr')?.classList.add('table-primary');
  }

  function bindClickActions(selector, callback) {
    document.querySelectorAll(selector).forEach(btn => {
      btn.addEventListener('click', () => callback(btn));
    });
  }

  function updateClearButtonVisibility(){
    // Mostrar el botón si hay selección de lote objetivo (egreso/ajuste_neg)
    // o si hay selección de lote para sumar (ingreso/ajuste_pos)
    btnClearLote.style.display = (hiddenTarget.value || selectedInventarioIdPos) ? 'inline-block' : 'none';
  }

  function clearSelection(){
    hiddenTarget.value = '';
    selectedInventarioId = null;
    clearInventarioRowSelection();
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
    clearInventarioRowSelection();
    const btn = findInventarioButtonById(id, ['.select-lote-neg', '.quick-ajuste-neg']);
    highlightInventarioRowFromButton(btn);
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
      const firstMark = (firstIdx >= 0 && idx === firstIdx) ? '<span class="badge bg-primary badge-prioridad me-2">A consumir primero</span>' : '';
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
        <td>${fv ? `<span class="badge bg-${b.cls} badge-vencimiento" title="Fecha de vencimiento">${b.text}</span>` : '<span class="sin-vencimiento-chip" title="Sin vencimiento">Sin vencimiento</span>'}</td>
        <td>
          ${agotado ? `<strong>0</strong> <span class="badge bg-secondary badge-agotado ms-2" title="Sin stock">Agotado</span>` : `<strong>${r.cantidad}</strong>`}
          ${um === 'blister' && cont > 0 ? `<span class="badge bg-info ms-2" title="Contenido por blíster">1 blíster = ${cont}</span>` : ''}
        </td>
        <td>${new Date(r.created_at).toLocaleDateString()}</td>
        <td class="text-end">${accionesHtml}</td>
      </tr>`;
    });
    invTableBody.innerHTML = rows.join('');
    // Enlazar eventos de selección de lote (negativo: solo ajuste −)
    bindClickActions('.select-lote-neg', (btn) => {
      const id = Number(btn.getAttribute('data-id'));
      const cant = Number(btn.getAttribute('data-cant') || 0);
      // Toggle: si ya está seleccionado, quitar; si no, aplicar sin forzar egreso
      if (selectedInventarioId === id) { clearSelection(); return; }
      // Al activar selección negativa, limpiar cualquier selección positiva
      selectedInventarioIdPos = null;
      selectionByQuick = false; // selección manual no forzada
      applySelectionNeg(id, cant, { forceAjusteNeg: false });
    });
    // Enlazar evento rápido para ajuste − con "-"
    bindClickActions('.quick-ajuste-neg', (btn) => {
      const id = Number(btn.getAttribute('data-id'));
      const cant = Number(btn.getAttribute('data-cant') || 0);
      // Toggle: si ya está seleccionado, quitar; si no, aplicar forzando AJUSTE −
      if (selectedInventarioId === id) { clearSelection(); return; }
      // Al activar selección negativa, limpiar cualquier selección positiva
      selectedInventarioIdPos = null;
      applySelectionNeg(id, cant, { forceAjusteNeg: true });
      inputCantidad.focus();
    });
    // Enlazar eventos de selección de lote (positivo)
    bindClickActions('.select-lote-pos', (btn) => {
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
    // Evento rápido para forzar AJUSTE + con "+"
    bindClickActions('.quick-ajuste-pos', (btn) => {
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
    updateClearButtonVisibility();
  }

  function applySelectionPos(id, lote, fv, { forceAjustePos } = { forceAjustePos: false }){
    selectedInventarioIdPos = Number(id);
    // Resaltar fila
    clearInventarioRowSelection();
    const btn = findInventarioButtonById(id, ['.select-lote-pos', '.quick-ajuste-pos']);
    highlightInventarioRowFromButton(btn);
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
    clearInventarioRowSelection();
    if (selectedInventarioId && (tipoSel.value === 'ajuste_neg')) {
      const btnNeg = findInventarioButtonById(selectedInventarioId, ['.select-lote-neg', '.quick-ajuste-neg']);
      highlightInventarioRowFromButton(btnNeg);
    }
    if (selectedInventarioIdPos && (tipoSel.value === 'ingreso' || tipoSel.value === 'ajuste_pos')) {
      const btnPos = findInventarioButtonById(selectedInventarioIdPos, ['.select-lote-pos', '.quick-ajuste-pos']);
      highlightInventarioRowFromButton(btnPos);
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

  function hasExpiredInventarioWithStock() {
    if (!Array.isArray(inventariosActuales) || !inventariosActuales.length) return false;
    const today = new Date().toISOString().slice(0, 10);
    return inventariosActuales.some(r => {
      const fv = (r.fecha_vencimiento || '').toString().slice(0, 10);
      return fv && Number(r.cantidad || 0) > 0 && fv < today;
    });
  }

  function updateExpiredActionGuide() {
    if (!expiredActionGuide) return;
    const hasExpired = hasExpiredInventarioWithStock();
    expiredActionGuide.style.display = hasExpired ? 'block' : 'none';
  }

  function showExpiredLotsHint() {
    const hasExpired = hasExpiredInventarioWithStock();
    if (!hasExpired) return;
    if (typeof showToast !== 'function') return;
    showToast('Hay lotes vencidos con stock. Usa Ajuste - para dejar el lote vencido en cero y luego registra un lote vigente.', 'error');
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
      clearInventarioRowSelection();
    }
    // Limpiar selección positiva si el tipo no lo usa
    if (!(tipoSel.value === 'ingreso' || tipoSel.value === 'ajuste_pos')) {
      selectedInventarioIdPos = null;
      selectionByQuickPos = false;
      // mantener valores escritos manualmente; sólo quitamos resalte
      clearInventarioRowSelection();
    }
    // Re-render de acciones según tipo y re-aplicar resaltado
    renderInventariosTable();
    reapplyHighlights();
    updateClearButtonVisibility();
    // Sincronizar el estado visual del tab y el indicador cuando el cambio no proviene de un click de tab
    updateTabActiveFromState();
    updateSectionIndicatorFromCurrent();
    updateExpiredActionGuide();
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
      showExpiredLotsHint();
      updateExpiredActionGuide();
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
      if (expiredActionGuide) expiredActionGuide.style.display = 'none';
    }
  }
  productoSel.addEventListener('change', () => { cargarInventariosProducto(); if (typeof updateLoteAdvice === 'function') { updateLoteAdvice(); } });
  document.addEventListener('DOMContentLoaded', cargarInventariosProducto);
  btnClearLote.addEventListener('click', clearSelection);
  if (btnGoAjusteNeg) {
    btnGoAjusteNeg.addEventListener('click', () => {
      setActiveTab('ajuste_neg');
      if (inputCantidad) inputCantidad.focus();
    });
  }
    // === Búsqueda incremental conectada al backend + atajos visuales ===
    const productosSearchUrl = @json(route('productos.search'));
    const quickTopProductos = @json($productosFrecuentes ?? []);
    const productosCache = new Map();
    const MIN_CHARS_BUSCADOR = 2;
    let currentSearchResults = [];
    let currentSearchTerm = '';
    let nextSearchPage = null;
    let activeSugIdx = -1;
    let searchAbortCtrl = null;
    let debounceTimerId = null;
    let currentTipoFilter = '';
    const tipoFilterButtons = document.querySelectorAll('.btn-tipo-filter');
    const btnVerTopSugerencias = document.getElementById('btn-ver-top-sugerencias');
    const productoTopChips = document.querySelectorAll('.producto-top-chip');
    const productoFilterSummary = document.getElementById('producto-filter-summary');
    const tipoFilterLabels = { medicamento: 'Medicamentos', insumo: 'Insumos' };

    function normalizeTipo(value) {
      return (value || '').toLowerCase();
    }

    function labelForTipo(tipo) {
      return tipoFilterLabels[tipo] || 'Todos';
    }

    function updateFilterSummary(tipo, count) {
      if (!productoFilterSummary) return;
      if (!tipo) {
        if (count > 0) {
          productoFilterSummary.textContent = `Mostrando todos los productos del catálogo (${count}). Usa el buscador para filtrar por nombre o código.`;
          productoFilterSummary.classList.remove('text-danger');
        } else {
          productoFilterSummary.textContent = 'Aún no hay productos cargados o todos están ocultos. Agrega uno nuevo o revisa los filtros.';
          productoFilterSummary.classList.add('text-danger');
        }
        return;
      }
      if (count === 0) {
        productoFilterSummary.innerHTML = `No hay registros locales para <strong>${labelForTipo(tipo)}</strong>. Usa el buscador o quita el filtro.`;
        productoFilterSummary.classList.add('text-danger');
      } else {
        productoFilterSummary.innerHTML = `Filtro aplicado: <strong>${labelForTipo(tipo)}</strong> (${count} en catálogo local).`;
        productoFilterSummary.classList.remove('text-danger');
      }
    }

    function applyDropdownFilter(tipo = '') {
      const normalized = normalizeTipo(tipo);
      let visibleCount = 0;
      Array.from(productoSel.options).forEach(opt => {
        if (!opt.value) {
          opt.hidden = false;
          return;
        }
        const optTipo = normalizeTipo(opt.dataset?.tipo);
        const matches = !normalized || optTipo === normalized;
        opt.hidden = !matches;
        if (matches) visibleCount++;
      });
      if (normalized && productoSel.value) {
        const selectedOpt = productoSel.options[productoSel.selectedIndex];
        if (selectedOpt && selectedOpt.hidden) {
          productoSel.value = '';
          productoSel.dispatchEvent(new Event('change'));
        }
      }
      updateFilterSummary(normalized, visibleCount);
      return visibleCount;
    }

    function optionToItem(opt) {
      return {
        id: opt.value,
        nombre: opt.dataset?.nombre || opt.textContent.replace(/\s*\([^)]*\)\s*$/, '').trim(),
        codigo: opt.dataset?.codigo || (opt.textContent.match(/\(([^)]+)\)/)?.[1] ?? ''),
        tipo: opt.dataset?.tipo || ''
      };
    }

    function searchLocalOptions(term = '', tipo = '', limit = 8) {
      const normalizedTipo = normalizeTipo(tipo);
      const normalizedTerm = (term || '').trim().toLowerCase();
      return Array.from(productoSel.options)
        .filter(opt => opt.value && (!normalizedTipo || normalizeTipo(opt.dataset?.tipo) === normalizedTipo))
        .filter(opt => {
          if (!normalizedTerm) return true;
          const nombre = (opt.dataset?.nombre || '').toLowerCase();
          const codigo = (opt.dataset?.codigo || '').toLowerCase();
          return nombre.includes(normalizedTerm) || codigo.includes(normalizedTerm);
        })
        .slice(0, limit)
        .map(optionToItem);
    }

    function buildFallbackFromSelect(tipo) {
      return searchLocalOptions('', tipo, 8);
    }

    function createClearFilterButton(label = 'Ver todos los tipos') {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'list-group-item list-group-item-action text-center clear-filter-btn';
      btn.textContent = label;
      btn.addEventListener('click', () => setTipoFilter(''));
      return btn;
    }

    function setTipoFilter(tipo = '', { silent = false } = {}) {
      const normalized = normalizeTipo(tipo);
      currentTipoFilter = normalized;
      tipoFilterButtons.forEach(btn => {
        const btnTipo = normalizeTipo(btn.dataset?.tipo);
        const isActive = btnTipo === normalized || (!btnTipo && !normalized);
        btn.classList.toggle('active', isActive);
      });
      productosCache.clear();
      applyDropdownFilter(normalized);
      const hasSearchTerm = productoBuscar.value.trim().length >= MIN_CHARS_BUSCADOR;
      if (hasSearchTerm) {
        requestProductos(productoBuscar.value, { page: 1, append: false });
        return;
      }
      if (silent) {
        productoSugerencias.style.display = 'none';
        return;
      }
      const fallback = buildFallbackFromSelect(normalized);
      if (fallback.length) {
        const headerBase = normalized ? `Catálogo local · ${labelForTipo(normalized)}` : 'Catálogo local · Todos';
        renderProductoSugerencias(fallback, { header: headerBase, allowClearFilter: !!normalized });
        return;
      }
      if (normalized) {
        renderProductoSugerencias([], { message: `No hay ${labelForTipo(normalized).toLowerCase()} registrados localmente.`, allowClearFilter: true });
      } else {
        const totalCatalogo = Array.from(productoSel.options).filter(opt => opt.value).length;
        const emptyMessage = totalCatalogo > 0
          ? 'Selecciona un producto desde la lista o escribe al menos 2 letras para buscar.'
          : 'Aún no hay productos registrados. Usa el módulo de Productos para crear el primero.';
        renderProductoSugerencias([], { message: emptyMessage });
      }
    }

    function syncProductoBuscadorConSelect() {
      const opt = productoSel.options[productoSel.selectedIndex];
      if (!opt || !opt.value) {
        productoBuscar.value = '';
        productoSugerencias.style.display = 'none';
        return;
      }
      productoBuscar.value = opt.dataset?.nombre || opt.textContent?.trim() || '';
    }
    document.addEventListener('DOMContentLoaded', syncProductoBuscadorConSelect);
    productoSel.addEventListener('change', syncProductoBuscadorConSelect);

    function renderProductoSugerencias(items, { message = null, loading = false, header = null, allowClearFilter = false } = {}) {
      forceProductoSugerenciasAbajo();
      productoSugerencias.innerHTML = '';
      if (loading) {
        productoSugerencias.innerHTML = '<div class="list-group-item text-muted">Buscando...</div>';
        productoSugerencias.style.display = 'block';
        return;
      }
      const shouldOfferClear = allowClearFilter && !!currentTipoFilter;
      if (message && !items.length) {
        const msg = document.createElement('div');
        msg.className = 'list-group-item text-muted';
        msg.textContent = message;
        productoSugerencias.appendChild(msg);
        if (shouldOfferClear) {
          productoSugerencias.appendChild(createClearFilterButton());
        }
        productoSugerencias.style.display = 'block';
        return;
      }
      if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'list-group-item text-muted';
        empty.textContent = 'Sin resultados';
        productoSugerencias.appendChild(empty);
        if (shouldOfferClear) {
          productoSugerencias.appendChild(createClearFilterButton());
        }
        productoSugerencias.style.display = 'block';
        return;
      }
      if (header) {
        const headerEl = document.createElement('div');
        headerEl.className = 'list-group-item list-group-item-heading';
        headerEl.textContent = header;
        productoSugerencias.appendChild(headerEl);
      }
      items.forEach((it, idx) => {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action suggestion-item';
        a.dataset.index = idx;
        const tipoText = (it.tipo || '—').toUpperCase();
        const tipoBadge = `<span class="badge bg-secondary ms-2" title="Tipo">${tipoText}</span>`;
        const usoBadge = typeof it.uso !== 'undefined'
          ? `<span class="badge bg-light text-dark ms-2" title="Movimientos registrados"><i class="fa fa-star text-warning me-1"></i>${it.uso}</span>`
          : '';
        a.innerHTML = `<div class="d-flex flex-column">
          <div><strong>${it.nombre}</strong> <span class="text-muted">(${it.codigo})</span>${tipoBadge}${usoBadge}</div>
        </div>`;
        a.addEventListener('click', (e) => { e.preventDefault(); seleccionarProductoDesdeSug(it); });
        productoSugerencias.appendChild(a);
      });
      if (nextSearchPage) {
        const loadMoreBtn = document.createElement('button');
        loadMoreBtn.type = 'button';
        loadMoreBtn.className = 'list-group-item list-group-item-action text-center load-more-suggestions';
        loadMoreBtn.textContent = 'Cargar más resultados';
        loadMoreBtn.addEventListener('click', () => requestProductos(currentSearchTerm, { page: nextSearchPage, append: true }));
        productoSugerencias.appendChild(loadMoreBtn);
      }
      productoSugerencias.style.display = 'block';
      activeSugIdx = -1;
    }

    function forceProductoSugerenciasAbajo() {
      if (!productoBuscar || !productoSugerencias) return;
      productoSugerencias.style.top = `${productoBuscar.offsetHeight + 6}px`;
      productoSugerencias.style.bottom = 'auto';
      productoSugerencias.style.left = '0';
      productoSugerencias.style.right = 'auto';
      productoSugerencias.style.transform = 'none';
    }

    function requestProductos(term, { page = 1, append = false } = {}) {
      const normalized = (term || '').trim();
      if (!append) {
        currentSearchResults = [];
        nextSearchPage = null;
      }
      currentSearchTerm = normalized;
      if (normalized.length < MIN_CHARS_BUSCADOR) {
        if (!normalized) {
          productoSugerencias.style.display = 'none';
        } else {
          renderProductoSugerencias([], { message: `Escribe al menos ${MIN_CHARS_BUSCADOR} caracteres o utiliza la lista inferior.`, allowClearFilter: !!currentTipoFilter });
        }
        return;
      }
      const cacheKey = `${normalized}|${page}|${currentTipoFilter || 'all'}`;
      if (productosCache.has(cacheKey)) {
        const cached = productosCache.get(cacheKey);
        nextSearchPage = cached.nextPage;
        currentSearchResults = append ? currentSearchResults.concat(cached.data) : cached.data.slice();
        renderProductoSugerencias(currentSearchResults, { allowClearFilter: !!currentTipoFilter });
        return;
      }
      if (!append) {
        renderProductoSugerencias([], { loading: true });
      }
      searchAbortCtrl?.abort();
      searchAbortCtrl = new AbortController();
      const params = new URLSearchParams({ q: normalized, page: String(page) });
      if (currentTipoFilter) params.append('tipo', currentTipoFilter);
      fetch(`${productosSearchUrl}?${params.toString()}`, { signal: searchAbortCtrl.signal })
        .then(resp => {
          if (!resp.ok) throw new Error('No se pudo obtener la lista de productos.');
          return resp.json();
        })
        .then(json => {
          if (currentSearchTerm !== normalized) return;
          const data = Array.isArray(json.data) ? json.data : [];
          const meta = json.meta || {};
          const nextPage = meta.has_more ? meta.next_page : null;
          productosCache.set(cacheKey, { data, nextPage });
          nextSearchPage = nextPage;
          currentSearchResults = append ? currentSearchResults.concat(data) : data.slice();
          if (!currentSearchResults.length && !nextPage) {
            const locales = searchLocalOptions(normalized, currentTipoFilter, 10);
            if (locales.length) {
              renderProductoSugerencias(locales, { header: 'Coincidencias locales disponibles', allowClearFilter: !!currentTipoFilter });
              return;
            }
          }
          renderProductoSugerencias(currentSearchResults, { allowClearFilter: !!currentTipoFilter });
        })
        .catch(err => {
          if (err.name === 'AbortError') return;
          renderProductoSugerencias([], { message: (err.message || 'Error al buscar productos') + '. Usa la lista inferior o ajusta el filtro.', allowClearFilter: !!currentTipoFilter });
        });
    }

    function seleccionarProductoDesdeSug(item) {
      const itemTipo = normalizeTipo(item.tipo);
      if (itemTipo && currentTipoFilter && currentTipoFilter !== itemTipo) {
        setTipoFilter(itemTipo);
      }
      ensureOptionExists(item);
      productoSel.value = String(item.id);
      productoSel.dispatchEvent(new Event('change'));
      productoBuscar.value = item.nombre;
      productoSugerencias.style.display = 'none';
    }

    function ensureOptionExists(item) {
      let opt = productoSel.querySelector(`option[value="${item.id}"]`);
      if (!opt) {
        opt = document.createElement('option');
        productoSel.appendChild(opt);
      }
      opt.value = item.id;
      opt.dataset.nombre = item.nombre || '';
      opt.dataset.codigo = item.codigo || '';
      opt.dataset.tipo = (item.tipo || '').toLowerCase();
      opt.textContent = `${item.nombre} (${item.codigo})`;
    }

    function getSuggestionElements() {
      return Array.from(productoSugerencias.querySelectorAll('.suggestion-item'));
    }

    function moverSugerenciaActiva(delta) {
      const items = getSuggestionElements();
      if (!items.length) return;
      activeSugIdx = Math.max(0, Math.min(items.length - 1, activeSugIdx + delta));
      items.forEach((el, idx) => el.classList.toggle('active', idx === activeSugIdx));
    }

    productoBuscar.addEventListener('input', () => {
      clearTimeout(debounceTimerId);
      debounceTimerId = setTimeout(() => requestProductos(productoBuscar.value), 250);
    });
    productoBuscar.addEventListener('focus', () => {
      forceProductoSugerenciasAbajo();
      if (productoBuscar.value.trim().length >= MIN_CHARS_BUSCADOR) {
        requestProductos(productoBuscar.value, { page: 1, append: false });
      }
    });
    productoBuscar.addEventListener('keydown', (e) => {
      if (productoSugerencias.style.display !== 'block') return;
      if (e.key === 'ArrowDown') { e.preventDefault(); moverSugerenciaActiva(1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); moverSugerenciaActiva(-1); }
      else if (e.key === 'Enter') {
        const items = getSuggestionElements();
        if (items.length && activeSugIdx >= 0) {
          e.preventDefault();
          items[activeSugIdx].click();
        }
      } else if (e.key === 'Escape') {
        productoSugerencias.style.display = 'none';
      }
    });
    document.addEventListener('click', (e) => {
      if (!productoSugerencias.contains(e.target) && e.target !== productoBuscar) {
        productoSugerencias.style.display = 'none';
      }
    });
    window.addEventListener('resize', forceProductoSugerenciasAbajo);
    window.addEventListener('scroll', forceProductoSugerenciasAbajo, true);

    tipoFilterButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const targetTipo = btn.dataset.tipo || '';
        if (normalizeTipo(targetTipo) === currentTipoFilter) return;
        setTipoFilter(targetTipo);
      });
    });

    if (btnVerTopSugerencias) {
      btnVerTopSugerencias.addEventListener('click', (e) => {
        e.preventDefault();
        if (!quickTopProductos.length) {
          renderProductoSugerencias([], { message: 'Aún no hay suficientes movimientos para destacar productos.' });
          return;
        }
        const filtered = quickTopProductos.filter(item => {
          const itemTipo = normalizeTipo(item.tipo);
          return !currentTipoFilter || itemTipo === currentTipoFilter;
        });
        if (!filtered.length) {
          renderProductoSugerencias([], { message: `Tus favoritos aún no incluyen ${labelForTipo(currentTipoFilter).toLowerCase()}.`, allowClearFilter: true });
          return;
        }
        const headerText = currentTipoFilter ? `Más usados · ${labelForTipo(currentTipoFilter)}` : 'Más usados';
        renderProductoSugerencias(filtered, { header: headerText });
      });
    }
    productoTopChips.forEach(chip => {
      chip.addEventListener('click', () => {
        const chipTipo = normalizeTipo(chip.dataset.tipo || '');
        if (chipTipo && chipTipo !== currentTipoFilter) {
          setTipoFilter(chipTipo);
        }
        seleccionarProductoDesdeSug({
          id: chip.dataset.id,
          nombre: chip.dataset.nombre,
          codigo: chip.dataset.codigo,
          tipo: chip.dataset.tipo
        });
      });
    });
    document.addEventListener('DOMContentLoaded', () => {
      setTipoFilter(currentTipoFilter, { silent: true });
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
    if (tipo === 'medicamento') { chip.classList.add('bg-orange'); text = 'MEDICAMENTO'; }
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
 