@extends('layouts.dashboard')

@section('title', 'Detalle de Medicamento')

@section('content')
<!-- Contenedor principal de la vista de detalles del medicamento -->
<div class="container mt-4">
    <!-- Tarjeta visual para mostrar los datos -->
    <div class="card shadow-sm">
        <!-- Encabezado de la tarjeta con título y botón de volver -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-capsules me-2"></i>Detalle del Medicamento</h5>
            <!-- Botón para regresar al listado de productos -->
            <a href="{{ route('productos.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
        <div class="card-body">
            <!-- Tabla con los campos principales del medicamento -->
            <table class="table table-bordered">
                <!-- Nombre del medicamento -->
                <tr>
                    <th><i class="fas fa-pills"></i> Nombre</th>
                    <td>{{ $producto->nombre }}</td>
                </tr>
                <!-- Código interno del medicamento -->
                <tr>
                    <th><i class="fas fa-barcode"></i> Código</th>
                    <td>{{ $producto->codigo }}</td>
                </tr>
                <!-- Descripción del medicamento -->
                <tr>
                    <th><i class="fas fa-align-left"></i> Descripción</th>
                    <td>{{ $producto->descripcion ?? '-' }}</td>
                </tr>
                <!-- Categoría principal -->
                <tr>
                    <th><i class="fas fa-layer-group"></i> Categoría</th>
                    <td>{{ $producto->categoria->nombre ?? '-' }}</td>
                </tr>
                <!-- Subcategoría -->
                <tr>
                    <th><i class="fas fa-tags"></i> Subcategoría</th>
                    <td>{{ $producto->subcategoria->nombre ?? '-' }}</td>
                </tr>
                <!-- Presentación del medicamento -->
                <tr>
                    <th><i class="fas fa-flask"></i> Presentación</th>
                    <td>{{ $producto->presentacion }}</td>
                </tr>
                <!-- Unidad de medida -->
                <tr>
                    <th><i class="fas fa-balance-scale"></i> Unidad de Medida</th>
                    <td>{{ $producto->unidad_medida }}</td>
                </tr>
                <!-- Categoría de inventario -->
                <tr>
                    <th><i class="fas fa-warehouse"></i> Categoría de Inventario</th>
                    <td>
                        @if($producto->categoria_inventario === 'odontologia')
                            Odontología
                        @else
                            Inventario General
                        @endif
                    </td>
                </tr>
                <!-- Stock actual -->
                <tr>
                    <th><i class="fas fa-boxes"></i> Stock</th>
                    <td>{{ $producto->stock_total }}</td>
                </tr>
                <!-- Proveedor del medicamento -->
                <tr>
                    <th><i class="fas fa-user-md"></i> Proveedor</th>
                    <td>{{ $producto->proveedor->nombre ?? '-' }}</td>
                </tr>
                <!-- Fecha de ingreso -->
                <tr>
                    <th><i class="fas fa-calendar-day"></i> Fecha de Ingreso</th>
                    <td>{{ $producto->fecha_ingreso ? \Carbon\Carbon::parse($producto->fecha_ingreso)->format('d/m/Y') : '-' }}</td>
                </tr>
                <!-- Fecha de vencimiento -->
                <tr>
                    <th><i class="fas fa-calendar-alt"></i> Fecha de Vencimiento</th>
                    <td>{{ $producto->fecha_vencimiento ? \Carbon\Carbon::parse($producto->fecha_vencimiento)->format('d/m/Y') : '-' }}</td>
                </tr>
                <!-- Usuario que creó el registro -->
                <tr>
                    <th><i class="fas fa-user"></i> Creado por</th>
                    <td>{{ $producto->creador->name ?? '—' }}</td>
                </tr>
                <!-- Usuario que actualizó el registro -->
                <tr>
                    <th><i class="fas fa-user-edit"></i> Actualizado por</th>
                    <td>{{ $producto->editor->name ?? '—' }}</td>
                </tr>
                <!-- Fecha de creación del registro -->
                <tr>
                    <th><i class="fas fa-calendar-plus"></i> Fecha de creación</th>
                    <td>{{ $producto->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <!-- Fecha de última actualización -->
                <tr>
                    <th><i class="fas fa-calendar-check"></i> Última actualización</th>
                    <td>{{ $producto->updated_at->format('d/m/Y H:i') }}</td>
                </tr>
            </table>
        </div>
    </div>
</div>
<!-- Fin de la sección de detalles del medicamento -->
@endsection
