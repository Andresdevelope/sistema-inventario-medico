@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-capsules me-2"></i>Añadir Nuevo Medicamento</h4>
        </div>
    <div class="card-body p-4">
      <!-- Toast de errores de validación -->
      <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <div id="toastErrores" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
          <div class="d-flex">
            <div class="toast-body">
              <strong>¡Corrige los siguientes errores!</strong>
              <ul class="mb-0" id="toastErroresLista">
                <!-- Errores se insertan por JS -->
              </ul>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
          </div>
        </div>
      </div>
      <form action="{{ route('productos.store') }}" method="POST">
        @csrf
        <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="nombre" id="nombre" class="form-control ps-5" placeholder="Nombre" value="{{ old('nombre') }}" required>
                            <label for="nombre"><i class="fas fa-capsules me-2"></i> Nombre</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="codigo" id="codigo" class="form-control ps-5" placeholder="Código" value="{{ old('codigo') }}" required>
                            <label for="codigo"><i class="fas fa-barcode me-2"></i> Código</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <textarea name="descripcion" id="descripcion" class="form-control ps-5" placeholder="Descripción" style="height: 80px;">{{ old('descripcion') }}</textarea>
                            <label for="descripcion"><i class="fas fa-align-left me-2"></i> Descripción</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="text" name="presentacion" id="presentacion" class="form-control ps-5" placeholder="Presentación" value="{{ old('presentacion') }}" required>
                            <label for="presentacion"><i class="fas fa-box-open me-2"></i> Presentación</label>
                        </div>
            <div class="form-floating mb-3 position-relative">
              <input type="text" name="unidad_medida" id="unidad_medida" class="form-control ps-5" placeholder="Unidad de Medida" value="{{ old('unidad_medida') }}" required>
              <label for="unidad_medida"><i class="fas fa-ruler me-2"></i> Unidad de Medida</label>
            </div>
            <div class="form-floating mb-3 position-relative">
              <select name="categoria_inventario" id="categoria_inventario" class="form-select ps-5" required>
                <option value="general" {{ old('categoria_inventario', 'general') == 'general' ? 'selected' : '' }}>Inventario General</option>
                <option value="odontologia" {{ old('categoria_inventario') == 'odontologia' ? 'selected' : '' }}>Odontología</option>
              </select>
              <label for="categoria_inventario"><i class="fas fa-warehouse me-2"></i> Categoría de Inventario</label>
            </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3 position-relative">
              <select name="categoria_id" id="categoria_id" class="form-select ps-5" required>
                <option value="" disabled {{ old('categoria_id') ? '' : 'selected' }}>Selecciona una categoría</option>
                @foreach($categorias as $categoria)
                  <option value="{{ $categoria->id }}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>{{ $categoria->nombre }}</option>
                @endforeach
              </select>
                            <label for="categoria_id"><i class="fas fa-layer-group me-2"></i> Categoría</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
              <select name="subcategoria_id" id="subcategoria_id" class="form-select ps-5" required>
                <option value="" disabled {{ old('subcategoria_id') ? '' : 'selected' }}>Selecciona una subcategoría</option>
                @foreach($subcategorias as $subcategoria)
                  <option value="{{ $subcategoria->id }}" {{ old('subcategoria_id') == $subcategoria->id ? 'selected' : '' }}>{{ $subcategoria->nombre }}</option>
                @endforeach
              </select>
                            <label for="subcategoria_id"><i class="fas fa-sitemap me-2"></i> Subcategoría</label>
                        </div>
            <div class="form-floating mb-3 position-relative">
              <input type="number" name="stock" id="stock" class="form-control ps-5" min="0" placeholder="Stock" value="{{ old('stock') }}" required>
              <label for="stock"><i class="fas fa-boxes me-2"></i> Stock</label>
            </div>
            <div class="form-floating mb-3 position-relative">
              <input type="number" name="stock_minimo" id="stock_minimo" class="form-control ps-5" min="0" placeholder="Stock mínimo recomendado" value="{{ old('stock_minimo') }}">
              <label for="stock_minimo"><i class="fas fa-exclamation-triangle me-2"></i> Stock mínimo recomendado</label>
            </div>
                        <div class="form-floating mb-3 position-relative d-flex align-items-center gap-2">
              <select name="proveedor_id" id="proveedor_id" class="form-select ps-5" required style="max-width: 70%;">
                <option value="" disabled {{ old('proveedor_id') ? '' : 'selected' }}>Selecciona un proveedor</option>
                @foreach($proveedores as $proveedor)
                  <option value="{{ $proveedor->id }}" 
                      data-contacto="{{ $proveedor->contacto }}" 
                      data-direccion="{{ $proveedor->direccion }}" 
                      data-email="{{ $proveedor->email }}" {{ old('proveedor_id') == $proveedor->id ? 'selected' : '' }}>{{ $proveedor->nombre }}</option>
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
              <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control ps-5" placeholder="Fecha de Ingreso" value="{{ old('fecha_ingreso') }}" required>
              <label for="fecha_ingreso"><i class="fas fa-calendar-plus me-2"></i> Fecha de Ingreso</label>
            </div>
            <div class="form-floating mb-3 position-relative">
              <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" class="form-control ps-5" placeholder="Fecha de Vencimiento" value="{{ old('fecha_vencimiento') }}">
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

@push('modals')
  @include('productos.partials.modales-proveedores')
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Mostrar errores de validación como toast
  @if ($errors->any())
    var toastErrores = document.getElementById('toastErrores');
    var toastErroresLista = document.getElementById('toastErroresLista');
    if (toastErrores && toastErroresLista) {
      toastErroresLista.innerHTML = '';
      @foreach ($errors->all() as $error)
        toastErroresLista.innerHTML += '<li>{{ $error }}'</li>;
      @endforeach
      var toast = new bootstrap.Toast(toastErrores, { delay: 6000 });
      toast.show();
    }
  @endif
});
</script>
@endpush

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
          option.setAttribute('data-contacto', data.proveedor.contacto || '');
          option.setAttribute('data-direccion', data.proveedor.direccion || '');
          option.setAttribute('data-email', data.proveedor.email || '');
          if (proveedorSelect) {
            proveedorSelect.appendChild(option);
            proveedorSelect.value = data.proveedor.id;
          }
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
      if (!selected) {
        mostrarToast('Selecciona un proveedor antes de editar.', false);
        return;
      }
      const formData = new FormData(formEditarProveedor);
      const url = `/proveedores/ajax/${selected}`;
      console.debug('[EditarProveedor] ID seleccionado:', selected, 'URL:', url);
      // Asegurar que el campo nombre no esté vacío
      const nombreInputEdit = formEditarProveedor.querySelector('input[name="nombre"]');
      if (nombreInputEdit && !nombreInputEdit.value.trim()) {
        const optSel = proveedorSelect ? proveedorSelect.querySelector(`option[value='${selected}']`) : null;
        if (optSel) {
          nombreInputEdit.value = (optSel.textContent || '').trim();
          console.debug('[EditarProveedor] Campo nombre estaba vacío. Valor recuperado desde option:', nombreInputEdit.value);
        }
      }
      // Refrescar formData por si modificamos el input
      formData.set('nombre', (nombreInputEdit ? nombreInputEdit.value : formData.get('nombre') || '').trim());
      // Método de refuerzo para algunos servidores que esperan _method
  formData.set('_method','PUT');
      // Log de pares clave-valor
      try {
        const entries = {};
        for (const [k,v] of formData.entries()) { entries[k]=v; }
        console.debug('[EditarProveedor] FormData a enviar:', entries);
      } catch(e) { /* no-op */ }
      fetch(url, {
        // IMPORTANTE: usar POST + _method=PUT porque PHP solo parsea multipart/form-data en POST.
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
      .then(async r => {
        if (!r.ok) {
          // Intentar extraer texto/JSON para diagnosticar.
          const ct = r.headers.get('Content-Type') || '';
          let payload;
          try { payload = ct.includes('application/json') ? await r.json() : await r.text(); } catch { payload = null; }
          const statusMsg = r.status === 404 ? 'Proveedor no encontrado (puede haber sido eliminado o el ID es inválido).' : `Error ${r.status}`;
          throw new Error(statusMsg + (payload ? `: ${typeof payload === 'string' ? payload.slice(0,180) : JSON.stringify(payload).slice(0,180)}` : ''));
        }
        return r.json();
      })
      .then(data => {
        if (data && data.success) {
          const option = proveedorSelect ? proveedorSelect.querySelector(`option[value='${selected}']`) : null;
          if (option) {
            option.textContent = data.proveedor.nombre;
            option.setAttribute('data-contacto', data.proveedor.contacto || '');
            option.setAttribute('data-direccion', data.proveedor.direccion || '');
            option.setAttribute('data-email', data.proveedor.email || '');
          }
          mostrarToast('Proveedor editado correctamente.', true);
          setTimeout(() => {
            const modalEl = document.getElementById('modalEditarProveedor');
            const m = bootstrap.Modal.getInstance(modalEl);
            if (modalEl && modalEl.contains(document.activeElement)) {
              document.activeElement.blur();
            }
            if (m) m.hide();
          }, 500);
        } else {
          mostrarToast('Respuesta inesperada al editar proveedor.', false);
        }
      })
      .catch(err => {
        console.error('Fallo al editar proveedor:', err.message);
        mostrarToast(err.message.includes('no encontrado') ? err.message : 'Error de conexión/edición.', false);
      });
    });
  }

  let proveedorAEliminar = null;
  if (btnEliminar) btnEliminar.addEventListener('click', function() {
    const selected = proveedorSelect ? proveedorSelect.value : null;
    if (!selected) return;
    proveedorAEliminar = selected;
  const modalConfirmEl = document.getElementById('modalConfirmarEliminar');
  const modalConfirm = new bootstrap.Modal(modalConfirmEl);
  modalConfirm.show();
  });

  const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminarProveedor');
  if (btnConfirmarEliminar) btnConfirmarEliminar.addEventListener('click', function() {
    if (!proveedorAEliminar) return;
  fetch(`/proveedores/ajax/${proveedorAEliminar}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken } })
    .then(r => r.json())
    .then(data => {
      const modalConfirmEl = document.getElementById('modalConfirmarEliminar');
      const modalConfirm = bootstrap.Modal.getInstance(modalConfirmEl);
      if (modalConfirmEl && modalConfirmEl.contains(document.activeElement)) {
        document.activeElement.blur();
      }
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
    .catch(() => { 
      const modalConfirmEl = document.getElementById('modalConfirmarEliminar');
      const modalConfirm = bootstrap.Modal.getInstance(modalConfirmEl);
      if (modalConfirmEl && modalConfirmEl.contains(document.activeElement)) {
        document.activeElement.blur();
      }
      if (modalConfirm) modalConfirm.hide();
      mostrarToast('Error de conexión.', false); 
      proveedorAEliminar = null; 
    });
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
