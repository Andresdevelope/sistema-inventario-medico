@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="m-0">Inventario Consolidado</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('movimientos.index') }}" class="btn btn-success">Agregar Movimiento</a>
            <a href="{{ route('inventario.export', request()->query()) }}" class="btn btn-outline-secondary">
                Exportar CSV
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Productos registrados</div>
                        <div class="h4 m-0">{{ $totalItems ?? 0 }}</div>
                    </div>
                    <i class="fa fa-boxes text-secondary fs-3"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Con stock bajo</div>
                        <div class="h4 m-0 text-danger">{{ $stockBajo ?? 0 }}</div>
                    </div>
                    <i class="fa fa-triangle-exclamation text-danger fs-3"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Por vencer (≤ 30 días)</div>
                        <div class="h4 m-0 text-warning">{{ $proximosAVencer ?? 0 }}</div>
                    </div>
                    <i class="fa fa-hourglass-half text-warning fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Formulario de búsqueda y filtros -->
    <form method="GET" class="row g-3 mb-4 align-items-end">
        <div class="col-md-3">
            <label for="search" class="form-label">Nombre</label>
            <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por nombre...">
        </div>
        <div class="col-md-3">
            <label for="categoria" class="form-label">Categoría</label>
            <select name="categoria" id="categoria" class="form-select">
                <option value="">Todas</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" @if(request('categoria') == $cat->id) selected @endif>{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="categoria_inventario" class="form-label">Área (Inventario)</label>
            <select name="categoria_inventario" id="categoria_inventario" class="form-select">
                <option value="">Todas</option>
                <option value="general" @selected(request('categoria_inventario')==='general')>General</option>
                <option value="odontologia" @selected(request('categoria_inventario')==='odontologia')>Odontología</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="fecha" class="form-label">Fecha de Ingreso</label>
            <input type="date" name="fecha" id="fecha" value="{{ request('fecha') }}" class="form-control">
        </div>
        <div class="col-md-1">
            <label for="per_page" class="form-label">Ver</label>
            <select class="form-select" name="per_page" id="per_page" onchange="this.form.submit()">
                @foreach([10,25,50,100] as $pp)
                    <option value="{{ $pp }}" @selected((int)request('per_page',25) === $pp)>{{ $pp }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary w-100">Buscar</button>
        </div>
        <div class="col-md-1">
            <a href="{{ route('inventario.index') }}" class="btn btn-secondary w-100">Limpiar</a>
        </div>
    </form>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th>Código</th>
                        <th>Categoría</th>
                        <!-- Proveedor eliminado para simplificar la vista -->
                        <th>Presentación</th>
                        <th>Unidad</th>
                        <!-- Categoría Inventario eliminada -->
                        <th>Stock Total</th>
                        <th>Vencimiento más próximo</th>
                        <th>Estado</th> 
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($productos as $producto)
                    @php
                        $inventarios = $producto->inventarios;
                        $stockMin = optional($inventarios->whereNotNull('stock_minimo'))->min('stock_minimo');
                        // Fallback al mínimo definido en producto si no hay en inventarios
                        if (is_null($stockMin)) {
                            $stockMin = $producto->stock_minimo;
                        }
                        // Fuente de stock: preferir inventarios; si no hay registros, usar stock del producto (transición)
                        $tieneInventarios = $inventarios && $inventarios->count() > 0;
                        $stockMostrar = $tieneInventarios ? $producto->stock_total : ($producto->stock ?? 0);
                        $isLow = $stockMin !== null && $stockMostrar < $stockMin;

                        // Calcular vencimiento más próximo con saldo > 0
                        $vencimientoProximo = $inventarios
                            ->where('cantidad', '>', 0)
                            ->whereNotNull('fecha_vencimiento')
                            ->sortBy('fecha_vencimiento')
                            ->first();
                        $fechaVencimiento = optional($vencimientoProximo)->fecha_vencimiento;
                        $badgeClass = 'secondary';
                        $labelVence = '-';
                        if ($fechaVencimiento) {
                            $hoy = \Carbon\Carbon::now()->startOfDay();
                            $fv = \Carbon\Carbon::parse($fechaVencimiento)->startOfDay();
                            $dias = (int) $hoy->diffInDays($fv, false);
                            if ($dias < 0) {
                                $badgeClass = 'danger';
                                $labelVence = 'Vencido';
                            } elseif ($dias <= 30) {
                                $badgeClass = 'warning';
                                $labelVence = 'Próx. (' . $dias . ' días)';
                            } else {
                                $badgeClass = 'success';
                                $labelVence = 'Activo';
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $producto->nombre }}</td>
                        <td><span class="badge bg-dark">{{ $producto->codigo }}</span></td>
                        <td>{{ $producto->categoria->nombre ?? '-' }}</td>
                        <!-- Proveedor oculto -->
                        <td>{{ $producto->presentacion }}</td>
                        <td>{{ $producto->unidad_medida }}</td>
                        <!-- Categoría Inventario ocultada -->
                        <td>
                            <span class="badge bg-{{ $isLow ? 'danger' : 'primary' }}">
                                {{ $stockMostrar }}
                            </span>
                            @if($stockMin)
                                <small class="text-muted">min {{ $stockMin }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $badgeClass }}" title="{{ $fechaVencimiento ? 'Vence: ' . \Carbon\Carbon::parse($fechaVencimiento)->format('d/m/Y') : 'Sin fecha' }}">{{ $labelVence }}</span>
                        </td>
                        <td>
                            @if($isLow)
                                <span class="badge bg-danger">Stock bajo</span>
                            @else
                                <span class="badge bg-success">OK</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('productos.show', $producto) }}" class="btn btn-outline-info btn-sm">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">No hay productos registrados en inventario.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $productos->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
