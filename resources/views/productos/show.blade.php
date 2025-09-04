@extends('layouts.dashboard')

@section('title', 'Detalle de Medicamento')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-capsules me-2"></i>Detalle del Medicamento</h5>
            <a href="{{ route('productos.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9">{{ $producto->nombre }}</dd>

                <dt class="col-sm-3">Código</dt>
                <dd class="col-sm-9">{{ $producto->codigo }}</dd>

                <dt class="col-sm-3">Descripción</dt>
                <dd class="col-sm-9">{{ $producto->descripcion ?? '-' }}</dd>

                <dt class="col-sm-3">Categoría</dt>
                <dd class="col-sm-9">{{ $producto->categoria->nombre ?? '-' }}</dd>

                <dt class="col-sm-3">Subcategoría</dt>
                <dd class="col-sm-9">{{ $producto->subcategoria->nombre ?? '-' }}</dd>

                <dt class="col-sm-3">Presentación</dt>
                <dd class="col-sm-9">{{ $producto->presentacion }}</dd>

                <dt class="col-sm-3">Unidad de Medida</dt>
                <dd class="col-sm-9">{{ $producto->unidad_medida }}</dd>

                <dt class="col-sm-3">Stock</dt>
                <dd class="col-sm-9">{{ $producto->stock }}</dd>

                <dt class="col-sm-3">Proveedor</dt>
                <dd class="col-sm-9">{{ $producto->proveedor->nombre ?? '-' }}</dd>

                <dt class="col-sm-3">Fecha de Vencimiento</dt>
                <dd class="col-sm-9">{{ $producto->fecha_vencimiento ? \Carbon\Carbon::parse($producto->fecha_vencimiento)->format('d/m/Y') : '-' }}</dd>

                <dt class="col-sm-3">Creado por</dt>
                <dd class="col-sm-9">{{ $producto->createdBy->name ?? '-' }}</dd>

                <dt class="col-sm-3">Actualizado por</dt>
                <dd class="col-sm-9">{{ $producto->updatedBy->name ?? '-' }}</dd>

                <dt class="col-sm-3">Fecha de creación</dt>
                <dd class="col-sm-9">{{ $producto->created_at->format('d/m/Y H:i') }}</dd>

                <dt class="col-sm-3">Última actualización</dt>
                <dd class="col-sm-9">{{ $producto->updated_at->format('d/m/Y H:i') }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
