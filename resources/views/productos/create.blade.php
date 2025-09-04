@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-capsules me-2"></i>Añadir Nuevo Medicamento</h4>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('productos.store') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="nombre" id="nombre" class="form-control ps-5" placeholder="Nombre" required>
                            <label for="nombre"><i class="fas fa-capsules me-2"></i> Nombre</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="codigo" id="codigo" class="form-control ps-5" placeholder="Código" required>
                            <label for="codigo"><i class="fas fa-barcode me-2"></i> Código</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <textarea name="descripcion" id="descripcion" class="form-control ps-5" placeholder="Descripción" style="height: 80px;"></textarea>
                            <label for="descripcion"><i class="fas fa-align-left me-2"></i> Descripción</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="presentacion" id="presentacion" class="form-control ps-5" placeholder="Presentación" required>
                            <label for="presentacion"><i class="fas fa-box-open me-2"></i> Presentación</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="unidad_medida" id="unidad_medida" class="form-control ps-5" placeholder="Unidad de Medida" required>
                            <label for="unidad_medida"><i class="fas fa-ruler me-2"></i> Unidad de Medida</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3 position-relative">
                            <select name="categoria_id" id="categoria_id" class="form-select ps-5" required>
                                <option value="" disabled selected>Selecciona una categoría</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                            <label for="categoria_id"><i class="fas fa-layer-group me-2"></i> Categoría</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <select name="subcategoria_id" id="subcategoria_id" class="form-select ps-5" required>
                                <option value="" disabled selected>Selecciona una subcategoría</option>
                                @foreach($subcategorias as $subcategoria)
                                    <option value="{{ $subcategoria->id }}">{{ $subcategoria->nombre }}</option>
                                @endforeach
                            </select>
                            <label for="subcategoria_id"><i class="fas fa-sitemap me-2"></i> Subcategoría</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="number" name="stock" id="stock" class="form-control ps-5" min="0" placeholder="Stock" required>
                            <label for="stock"><i class="fas fa-boxes me-2"></i> Stock</label>
                        </div>
                        <div class="form-floating mb-3 position-relative d-flex align-items-center gap-2">
                            <select name="proveedor_id" id="proveedor_id" class="form-select ps-5" required style="max-width: 70%;">
                                <option value="" disabled selected>Selecciona un proveedor</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                @endforeach
                            </select>
                            <label for="proveedor_id" class="form-label"><i class="fas fa-truck me-2"></i> Seleccionar proveedor</label>
                            <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#modalProveedor">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-warning" id="btnEditarProveedor" data-bs-toggle="modal" data-bs-target="#modalEditarProveedor" disabled>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger" id="btnEliminarProveedor" disabled>
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" class="form-control ps-5" placeholder="Fecha de Vencimiento">
                            <label for="fecha_vencimiento"><i class="fas fa-calendar-alt me-2"></i> Fecha de Vencimiento</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" class="btn btn-success px-4">Guardar</button>
                    <a href="{{ route('productos.index') }}" class="btn btn-secondary px-4">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast y modales -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
  <div id="toastNotificacion" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastMsg"><!-- Mensaje dinámico --></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>
  </div>
</div>

<!-- Modal Confirmar Eliminación Proveedor -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-labelledby="modalConfirmarEliminarLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalConfirmarEliminarLabel"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar Eliminación</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <p class="fs-5">¿Estás seguro que deseas eliminar este proveedor?</p>
        <div class="d-flex justify-content-center gap-3 mt-3">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="btnConfirmarEliminarProveedor">Eliminar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Añadir Proveedor -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalProveedorLabel">Añadir Proveedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formProveedor">
          @csrf
          <div class="mb-3">
            <label for="proveedor_nombre" class="form-label">Nombre De la Empresa</label>
            <input type="text" class="form-control" id="proveedor_nombre" name="nombre" required>
          </div>
          <div class="mb-3">
            <label for="proveedor_contacto" class="form-label">Contacto</label>
            <input type="text" class="form-control" id="proveedor_contacto" name="contacto">
          </div>
          <div class="mb-3">
            <label for="proveedor_direccion" class="form-label">Dirección</label>
            <input type="text" class="form-control" id="proveedor_direccion" name="direccion">
          </div>
          <div class="mb-3">
            <label for="proveedor_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="proveedor_email" name="email">
          </div>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>
        <div id="proveedorMsg" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar Proveedor -->
<div class="modal fade" id="modalEditarProveedor" tabindex="-1" aria-labelledby="modalEditarProveedorLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarProveedorLabel">Editar Proveedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarProveedor">
          @csrf
          <div class="mb-3">
            <label for="editar_proveedor_nombre" class="form-label">Nombre De la Empresa</label>
            <input type="text" class="form-control" id="editar_proveedor_nombre" name="nombre" required>
          </div>
          <div class="mb-3">
            <label for="editar_proveedor_contacto" class="form-label">Contacto</label>
            <input type="text" class="form-control" id="editar_proveedor_contacto" name="contacto">
          </div>
          <div class="mb-3">
            <label for="editar_proveedor_direccion" class="form-label">Dirección</label>
            <input type="text" class="form-control" id="editar_proveedor_direccion" name="direccion">
          </div>
          <div class="mb-3">
            <label for="editar_proveedor_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="editar_proveedor_email" name="email">
          </div>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
          </div>
        </form>
        <div id="editarProveedorMsg" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const categoriaSelect = document.getElementById('categoria_id');
  const subcategoriaSelect = document.getElementById('subcategoria_id');
  const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
  // Generar código en tiempo real al escribir el nombre (debounce)
  const nombreInput = document.getElementById('nombre');
  const codigoInput = document.getElementById('codigo');
  let debounceTimer = null;
  if (nombreInput && codigoInput) {
    nombreInput.addEventListener('input', function() {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        const nombre = nombreInput.value.trim();
        if (!nombre) return;
  fetch('/productos/generar-codigo', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
          body: JSON.stringify({ nombre })
        })
        .then(r => r.json())
        .then(data => {
          if (data && data.codigo) {
            codigoInput.value = data.codigo;
          }
        })
        .catch(() => { /* no interrumpir la UX si falla */ });
      }, 450);
    });
  }
  if (categoriaSelect && subcategoriaSelect) {
    categoriaSelect.addEventListener('change', function() {
      const categoriaId = this.value;
      subcategoriaSelect.innerHTML = '<option value="" disabled selected>Cargando...</option>';
      fetch(`/subcategorias/by-categoria/${categoriaId}`)
        .then(response => response.json())
        .then(data => {
          let options = '<option value="" disabled selected>Selecciona una subcategoría</option>';
          if (Array.isArray(data) && data.length > 0) {
            data.forEach(sub => {
              options += `<option value="${sub.id}">${sub.nombre}</option>`;
            });
          } else {
            options += '<option value="" disabled>No hay subcategorías</option>';
          }
          subcategoriaSelect.innerHTML = options;
        })
        .catch(() => {
          subcategoriaSelect.innerHTML = '<option value="" disabled>Error al cargar</option>';
        });
    });
  }

  // proveedor JS
  const formProveedor = document.getElementById('formProveedor');
  const proveedorSelect = document.getElementById('proveedor_id');
  const proveedorMsg = document.getElementById('proveedorMsg');
  const btnEditar = document.getElementById('btnEditarProveedor');
  const btnEliminar = document.getElementById('btnEliminarProveedor');

  if (formProveedor) {
    formProveedor.addEventListener('submit', function(e) {
      e.preventDefault();
      if (proveedorMsg) proveedorMsg.textContent = '';
      const formData = new FormData(formProveedor);
      fetch('/proveedores/ajax', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const option = document.createElement('option');
          option.value = data.proveedor.id;
          option.textContent = data.proveedor.nombre;
          if (proveedorSelect) proveedorSelect.appendChild(option);
          if (proveedorSelect) proveedorSelect.value = data.proveedor.id;
          mostrarToast('Proveedor añadido correctamente.', true);
          formProveedor.reset();
          setTimeout(() => { const m = bootstrap.Modal.getInstance(document.getElementById('modalProveedor')); if (m) m.hide(); }, 800);
        } else {
          mostrarToast('Error al guardar proveedor.', false);
        }
      })
      .catch(() => mostrarToast('Error de conexión.', false));
    });
  }

  if (proveedorSelect) {
    proveedorSelect.addEventListener('change', function() {
      const selected = proveedorSelect.value;
      if (btnEditar) btnEditar.disabled = !selected;
      if (btnEliminar) btnEliminar.disabled = !selected;
    });
  }

  if (btnEditar) btnEditar.addEventListener('click', function() {
    const selected = proveedorSelect ? proveedorSelect.value : null;
    if (!selected) return;
    const option = proveedorSelect.querySelector(`option[value='${selected}']`);
    const nombre = option ? option.textContent : '';
    const contacto = option ? option.getAttribute('data-contacto') || '' : '';
    const direccion = option ? option.getAttribute('data-direccion') || '' : '';
    const email = option ? option.getAttribute('data-email') || '' : '';
    const inpNombre = document.getElementById('editar_proveedor_nombre');
    if (inpNombre) inpNombre.value = nombre;
    const inpContacto = document.getElementById('editar_proveedor_contacto'); if (inpContacto) inpContacto.value = contacto;
    const inpDireccion = document.getElementById('editar_proveedor_direccion'); if (inpDireccion) inpDireccion.value = direccion;
    const inpEmail = document.getElementById('editar_proveedor_email'); if (inpEmail) inpEmail.value = email;
  });

  const formEditarProveedor = document.getElementById('formEditarProveedor');
  if (formEditarProveedor) {
    formEditarProveedor.addEventListener('submit', function(e) {
      e.preventDefault();
      const selected = proveedorSelect ? proveedorSelect.value : null;
      const formData = new FormData(formEditarProveedor);
  fetch(`/proveedores/ajax/${selected}`, { method: 'PUT', headers: { 'X-CSRF-TOKEN': csrfToken }, body: formData })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const option = proveedorSelect ? proveedorSelect.querySelector(`option[value='${selected}']`) : null;
          if (option) option.textContent = data.proveedor.nombre;
          mostrarToast('Proveedor editado correctamente.', true);
          setTimeout(() => { const m = bootstrap.Modal.getInstance(document.getElementById('modalEditarProveedor')); if (m) m.hide(); }, 800);
        } else mostrarToast('Error al editar proveedor.', false);
      })
      .catch(() => mostrarToast('Error de conexión.', false));
    });
  }

  let proveedorAEliminar = null;
  if (btnEliminar) btnEliminar.addEventListener('click', function() {
    const selected = proveedorSelect ? proveedorSelect.value : null;
    if (!selected) return;
    proveedorAEliminar = selected;
    const modalConfirm = new bootstrap.Modal(document.getElementById('modalConfirmarEliminar'));
    modalConfirm.show();
  });

  const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminarProveedor');
  if (btnConfirmarEliminar) btnConfirmarEliminar.addEventListener('click', function() {
    if (!proveedorAEliminar) return;
  fetch(`/proveedores/ajax/${proveedorAEliminar}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken } })
    .then(r => r.json())
    .then(data => {
      const modalConfirm = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEliminar'));
      if (modalConfirm) modalConfirm.hide();
      if (data.success) {
        const opt = proveedorSelect ? proveedorSelect.querySelector(`option[value='${proveedorAEliminar}']`) : null;
        if (opt) opt.remove();
        if (proveedorSelect) proveedorSelect.value = '';
        if (btnEditar) btnEditar.disabled = true; if (btnEliminar) btnEliminar.disabled = true;
        mostrarToast('Proveedor eliminado correctamente.', true);
      } else mostrarToast('Error al eliminar proveedor.', false);
      proveedorAEliminar = null;
    })
    .catch(() => { const modalConfirm = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEliminar')); if (modalConfirm) modalConfirm.hide(); mostrarToast('Error de conexión.', false); proveedorAEliminar = null; });
  });

  function mostrarToast(mensaje, exito = true) {
    const toastEl = document.getElementById('toastNotificacion');
    const toastMsg = document.getElementById('toastMsg');
    if (!toastEl || !toastMsg) return;
    toastMsg.textContent = mensaje;
    toastEl.classList.remove('bg-success', 'bg-danger');
    toastEl.classList.add(exito ? 'bg-success' : 'bg-danger');
    const t = new bootstrap.Toast(toastEl);
    t.show();
  }
});
</script>
@endpush
