@extends('layouts.dashboard')

@section('title', 'Editar Medicamento')

@section('content')
<!-- Formulario para editar medicamento -->

<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header med-header text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-capsules me-2"></i>Editar Medicamento</h4>
            <span class="small"><i class="fas fa-user-edit me-1"></i>Última edición por: <strong>{{ $producto->editor->name ?? $producto->creador->name ?? 'N/D' }}</strong></span>
        </div>
        <div class="card-body p-4">
            @if(session('success'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert" style="background:var(--accent);color:#fff;border:1px solid var(--accent-soft);">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert" style="background:var(--accent);color:#fff;border:1px solid var(--accent-soft);">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-warning alert-dismissible fade show" role="alert" style="background:var(--accent);color:#fff;border:1px solid var(--accent-soft);">
                    <i class="fas fa-exclamation-triangle me-2"></i>Por favor corrige los siguientes errores:<ul class="mb-0 mt-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif
            <form action="{{ route('productos.update', $producto->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="nombre" id="nombre" class="form-control ps-5 @error('nombre') is-invalid @enderror" placeholder="Nombre" value="{{ old('nombre', $producto->nombre) }}" minlength="3" maxlength="50" pattern="(?=(?:.*\d){0,4}$)(?=.*[A-Za-zÁÉÍÓÚáéíóúÑñ])[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s\-\.,\(\)/\+%]{3,50}" title="Ingresa un nombre real de medicamento (ej. Amoxicilina 500 mg). Máximo 4 números." required>
                            <label for="nombre"><i class="fas fa-capsules me-2"></i> Nombre</label>
                        </div>
                        @error('nombre')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="codigo" id="codigo" class="form-control ps-5 @error('codigo') is-invalid @enderror" placeholder="Código" value="{{ old('codigo', $producto->codigo) }}" minlength="3" maxlength="30" required>
                            <label for="codigo"><i class="fas fa-barcode me-2"></i> Código</label>
                        </div>
                        @error('codigo')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-floating mb-3 position-relative">
                            <textarea name="descripcion" id="descripcion" class="form-control ps-5 @error('descripcion') is-invalid @enderror" placeholder="Descripción" minlength="10" maxlength="100" style="height: 80px;">{{ old('descripcion', $producto->descripcion) }}</textarea>
                            <label for="descripcion"><i class="fas fa-align-left me-2"></i> Descripción</label>
                        </div>
                        @error('descripcion')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="presentacion" id="presentacion" class="form-control ps-5 @error('presentacion') is-invalid @enderror" placeholder="Presentación" value="{{ old('presentacion', $producto->presentacion) }}" minlength="2" maxlength="60" required>
                            <label for="presentacion"><i class="fas fa-box-open me-2"></i> Presentación</label>
                        </div>
                        @error('presentacion')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <!-- Input de texto libre para unidad de medida con datalist -->
                        <div class="form-floating mb-3 position-relative">
                            <input type="text"
                                   name="unidad_medida"
                                   id="unidad_medida"
                                   list="unidades-sugeridas"
                                   class="form-control ps-5 @error('unidad_medida') is-invalid @enderror"
                                   placeholder="Ej: mg, ml, blister, frasco"
                                   value="{{ old('unidad_medida', $producto->unidad_medida) }}"
                                   maxlength="20"
                                   autocomplete="off"
                                   required>
                            <label for="unidad_medida">
                                <i class="fas fa-ruler me-2"></i> Unidad de Medida
                            </label>
                            <datalist id="unidades-sugeridas">
                                <option value="blister">
                                <option value="unidad">
                                <option value="frasco">
                                <option value="ampolla">
                                <option value="mg">
                                <option value="ml">
                                <option value="g">
                                <option value="UI">
                                <option value="mcg">
                                <option value="mEq">
                                <option value="%">
                                <option value="U">
                            </datalist>
                        </div>
                        @error('unidad_medida')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        <!-- Toggle switch: ¿usa blíster? -->
                        <div class="card bg-light border-0 mb-3">
                          <div class="card-body py-2 px-3">
                            <div class="form-check form-switch ps-0 d-flex align-items-center">
                              <input class="form-check-input ms-0 me-3 cursor-pointer" type="checkbox"
                                     role="switch" id="usa_blister" name="usa_blister"
                                     value="1" {{ old('usa_blister', $producto->usa_blister) ? 'checked' : '' }} style="width: 2.5em; height: 1.25em;">
                              <label class="form-check-label d-flex flex-column cursor-pointer" for="usa_blister">
                                <span class="fw-bold text-dark"><i class="fas fa-prescription-bottle me-1"></i> ¿Se maneja en blíster?</span>
                                <span class="text-muted small">Actívalo si el producto viene en blíster (tabletas, cápsulas). Habilita "Contenido por blíster" en movimientos.</span>
                              </label>
                            </div>
                          </div>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <select name="tipo_producto" id="tipo_producto" class="form-select ps-5 @error('tipo_producto') is-invalid @enderror" required>
                                <option value="medicamento" {{ old('tipo_producto', $producto->tipo_producto ?? 'medicamento') == 'medicamento' ? 'selected' : '' }}>Medicamento</option>
                                <option value="insumo" {{ old('tipo_producto', $producto->tipo_producto ?? '') == 'insumo' ? 'selected' : '' }}>Insumo</option>
                            </select>
                            <label for="tipo_producto"><i class="fas fa-tags me-2"></i> Tipo de Producto</label>
                        </div>
                        @error('tipo_producto')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3 position-relative">
                            <select name="categoria_id" id="categoria_id" class="form-select ps-5 @error('categoria_id') is-invalid @enderror" required>
                                <option value="" disabled>Selecciona una categoría</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" {{ old('categoria_id', $producto->categoria_id) == $categoria->id ? 'selected' : '' }}>{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                            <label for="categoria_id"><i class="fas fa-layer-group me-2"></i> Categoría</label>
                        </div>
                        @error('categoria_id')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-floating mb-3 position-relative">
                            <select name="subcategoria_id" id="subcategoria_id" class="form-select ps-5 @error('subcategoria_id') is-invalid @enderror" required>
                                <option value="" disabled>Selecciona una subcategoría</option>
                                @foreach($subcategorias as $subcategoria)
                                    <option value="{{ $subcategoria->id }}" {{ old('subcategoria_id', $producto->subcategoria_id) == $subcategoria->id ? 'selected' : '' }}>{{ $subcategoria->nombre }}</option>
                                @endforeach
                            </select>
                            <label for="subcategoria_id"><i class="fas fa-sitemap me-2"></i> Subcategoría</label>
                        </div>
                        @error('subcategoria_id')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-floating mb-3 position-relative">
                            <input type="number" name="stock" id="stock" class="form-control ps-5 @error('stock') is-invalid @enderror" min="1" max="9999" step="1" inputmode="numeric" placeholder="Stock" value="{{ old('stock', $producto->stock) }}" required>
                            <label for="stock"><i class="fas fa-boxes me-2"></i> Stock</label>
                        </div>
                        @error('stock')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-floating mb-3 position-relative">
                            <input type="number" name="stock_minimo" id="stock_minimo" class="form-control ps-5 @error('stock_minimo') is-invalid @enderror" min="1" max="9999" step="1" inputmode="numeric" placeholder="Stock mínimo recomendado" value="{{ old('stock_minimo', $producto->stock_minimo) }}">
                            <label for="stock_minimo"><i class="fas fa-exclamation-triangle me-2"></i> Stock mínimo recomendado</label>
                        </div>
                        @error('stock_minimo')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-floating mb-3 position-relative d-flex align-items-center gap-2">
                            <select name="proveedor_id" id="proveedor_id" class="form-select ps-5 @error('proveedor_id') is-invalid @enderror" required style="max-width: 70%;">
                                <option value="" disabled>Selecciona un proveedor</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}" {{ old('proveedor_id', $producto->proveedor_id) == $proveedor->id ? 'selected' : '' }}>{{ $proveedor->nombre }}</option>
                                @endforeach
                            </select>
                            <label for="proveedor_id" class="form-label"><i class="fas fa-truck me-2"></i> Seleccionar proveedor</label>
                            <!-- Botones para agregar/editar/eliminar proveedor (opcional, igual que create) -->
                        </div>
                        @error('proveedor_id')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-floating mb-3 position-relative">
                            <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control ps-5 @error('fecha_ingreso') is-invalid @enderror" placeholder="Fecha de Ingreso" value="{{ old('fecha_ingreso', $producto->fecha_ingreso) }}" required>
                            <label for="fecha_ingreso"><i class="fas fa-calendar-plus me-2"></i> Fecha de Ingreso</label>
                        </div>
                        @error('fecha_ingreso')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-floating mb-3 position-relative">
                            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" class="form-control ps-5 @error('fecha_vencimiento') is-invalid @enderror" placeholder="Fecha de Vencimiento" value="{{ old('fecha_vencimiento', $producto->fecha_vencimiento) }}">
                            <label for="fecha_vencimiento"><i class="fas fa-calendar-alt me-2"></i> Fecha de Vencimiento</label>
                        </div>
                        @error('fecha_vencimiento')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                            <div class="form-floating mb-3 position-relative">
                                <select name="categoria_inventario" id="categoria_inventario" class="form-select ps-5 @error('categoria_inventario') is-invalid @enderror" required>
                                    <option value="" disabled>Selecciona tipo de inventario</option>
                                    <option value="general" {{ old('categoria_inventario', $producto->categoria_inventario) == 'general' ? 'selected' : '' }}>General</option>
                                    <option value="odontologia" {{ old('categoria_inventario', $producto->categoria_inventario) == 'odontologia' ? 'selected' : '' }}>Odontología</option>
                                </select>
                                <label for="categoria_inventario"><i class="fas fa-archive me-2"></i> Tipo de Inventario</label>
                            </div>
                            @error('categoria_inventario')
                              <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="alert alert-warning py-2 mb-3 small" role="status">
                            <i class="fas fa-user-clock me-1"></i> Se registrará <strong>{{ Auth::user()->name ?? 'Usuario' }}</strong> como último modificador.
                        </div>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-med-primary px-4"><i class="fas fa-save"></i> Guardar Cambios</button>
                        <a href="{{ route('productos.index') }}" class="btn btn-med-outline px-4">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Fin del formulario de edición de medicamento -->
@endsection

@push('styles')
<style>
    .med-header {
        background-color: #ff9800; /* naranja principal del sistema */
        border-color: #ff9800;
    }

    .btn-med-primary {
        background-color: #ff9800;
        border-color: #ff9800;
        color: #ffffff;
    }

    .btn-med-primary:hover,
    .btn-med-primary:focus {
        background-color: #fb8c00;
        border-color: #fb8c00;
        color: #ffffff;
    }

    .btn-med-outline {
        background-color: #ffffff;
        border-color: #ff9800;
        color: #ff9800;
    }

    /* Estilos para el switch de usa_blister naranja */
    #usa_blister:checked {
        background-color: #ff9800;
        border-color: #ff9800;
    }
    #usa_blister:focus {
        box-shadow: 0 0 0 0.25rem rgba(255, 152, 0, 0.25);
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const nombreInput = document.getElementById('nombre');
    const stockInput = document.getElementById('stock');
    const stockMinimoInput = document.getElementById('stock_minimo');

    const limitarDigitosMaximosEnTexto = (input, maxDigitos = 4) => {
        if (!input) return;
        input.addEventListener('input', function () {
            let digitosActuales = 0;
            let resultado = '';

            for (const char of this.value) {
                if (/\d/.test(char)) {
                    if (digitosActuales < maxDigitos) {
                        resultado += char;
                        digitosActuales++;
                    }
                } else {
                    resultado += char;
                }
            }

            this.value = resultado;
        });
    };

    const limitarMaximo4Digitos = (input) => {
        if (!input) return;
        input.addEventListener('input', function () {
            const soloDigitos = this.value.replace(/\D/g, '').slice(0, 4);
            this.value = soloDigitos;
        });
    };

    limitarMaximo4Digitos(stockInput);
    limitarMaximo4Digitos(stockMinimoInput);
    limitarDigitosMaximosEnTexto(nombreInput, 4);
});
</script>
@endpush

