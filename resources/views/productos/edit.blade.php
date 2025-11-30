@extends('layouts.dashboard')

@section('title', 'Editar Medicamento')

@section('content')
<!-- Formulario para editar medicamento -->

<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-capsules me-2"></i>Editar Medicamento</h4>
            <span class="small"><i class="fas fa-user-edit me-1"></i>Última edición por: <strong>{{ $producto->editor->name ?? $producto->creador->name ?? 'N/D' }}</strong></span>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('productos.update', $producto->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="nombre" id="nombre" class="form-control ps-5" placeholder="Nombre" value="{{ old('nombre', $producto->nombre) }}" required>
                            <label for="nombre"><i class="fas fa-capsules me-2"></i> Nombre</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="codigo" id="codigo" class="form-control ps-5" placeholder="Código" value="{{ old('codigo', $producto->codigo) }}" required>
                            <label for="codigo"><i class="fas fa-barcode me-2"></i> Código</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <textarea name="descripcion" id="descripcion" class="form-control ps-5" placeholder="Descripción" style="height: 80px;">{{ old('descripcion', $producto->descripcion) }}</textarea>
                            <label for="descripcion"><i class="fas fa-align-left me-2"></i> Descripción</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="presentacion" id="presentacion" class="form-control ps-5" placeholder="Presentación" value="{{ old('presentacion', $producto->presentacion) }}" required>
                            <label for="presentacion"><i class="fas fa-box-open me-2"></i> Presentación</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="unidad_medida" id="unidad_medida" class="form-control ps-5" placeholder="Unidad de Medida" value="{{ old('unidad_medida', $producto->unidad_medida) }}" required>
                            <label for="unidad_medida"><i class="fas fa-ruler me-2"></i> Unidad de Medida</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3 position-relative">
                            <select name="categoria_id" id="categoria_id" class="form-select ps-5" required>
                                <option value="" disabled>Selecciona una categoría</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" {{ $producto->categoria_id == $categoria->id ? 'selected' : '' }}>{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                            <label for="categoria_id"><i class="fas fa-layer-group me-2"></i> Categoría</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <select name="subcategoria_id" id="subcategoria_id" class="form-select ps-5" required>
                                <option value="" disabled>Selecciona una subcategoría</option>
                                @foreach($subcategorias as $subcategoria)
                                    <option value="{{ $subcategoria->id }}" {{ $producto->subcategoria_id == $subcategoria->id ? 'selected' : '' }}>{{ $subcategoria->nombre }}</option>
                                @endforeach
                            </select>
                            <label for="subcategoria_id"><i class="fas fa-sitemap me-2"></i> Subcategoría</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="number" name="stock" id="stock" class="form-control ps-5" min="0" placeholder="Stock" value="{{ old('stock', $producto->stock) }}" required>
                            <label for="stock"><i class="fas fa-boxes me-2"></i> Stock</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="number" name="stock_minimo" id="stock_minimo" class="form-control ps-5" min="0" placeholder="Stock mínimo recomendado" value="{{ old('stock_minimo', $producto->stock_minimo) }}">
                            <label for="stock_minimo"><i class="fas fa-exclamation-triangle me-2"></i> Stock mínimo recomendado</label>
                        </div>
                        <div class="form-floating mb-3 position-relative d-flex align-items-center gap-2">
                            <select name="proveedor_id" id="proveedor_id" class="form-select ps-5" required style="max-width: 70%;">
                                <option value="" disabled>Selecciona un proveedor</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}" {{ $producto->proveedor_id == $proveedor->id ? 'selected' : '' }}>{{ $proveedor->nombre }}</option>
                                @endforeach
                            </select>
                            <label for="proveedor_id" class="form-label"><i class="fas fa-truck me-2"></i> Seleccionar proveedor</label>
                            <!-- Botones para agregar/editar/eliminar proveedor (opcional, igual que create) -->
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control ps-5" placeholder="Fecha de Ingreso" value="{{ old('fecha_ingreso', $producto->fecha_ingreso) }}" required>
                            <label for="fecha_ingreso"><i class="fas fa-calendar-plus me-2"></i> Fecha de Ingreso</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" class="form-control ps-5" placeholder="Fecha de Vencimiento" value="{{ old('fecha_vencimiento', $producto->fecha_vencimiento) }}">
                            <label for="fecha_vencimiento"><i class="fas fa-calendar-alt me-2"></i> Fecha de Vencimiento</label>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="alert alert-warning py-2 mb-3 small" role="status">
                            <i class="fas fa-user-clock me-1"></i> Se registrará <strong>{{ Auth::user()->name ?? 'Usuario' }}</strong> como último modificador.
                        </div>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save"></i> Guardar Cambios</button>
                        <a href="{{ route('productos.index') }}" class="btn btn-secondary px-4">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Fin del formulario de edición de medicamento -->
@endsection
