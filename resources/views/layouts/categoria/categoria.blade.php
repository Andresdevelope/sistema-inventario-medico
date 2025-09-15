<!-- Modal de confirmación genérico -->
<div id="modal-confirmar" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:380px; width:100%; text-align:center; box-sizing:border-box; padding:1.5rem 1.2rem;">
        <h3 id="confirmar-titulo" style="color:#e74c3c; margin-bottom:1rem;">¿Confirmar acción?</h3>
        <div id="confirmar-mensaje" style="font-size:1.08rem; margin-bottom:1.5rem; color:#333;">¿Estás seguro de que deseas continuar?</div>
        <div style="display:flex; justify-content:center; gap:1.2rem;">
            <button id="btn-cancelar-confirmar" style="background:#ccc;color:#222;padding:0.5rem 1.5rem;border:none;border-radius:5px;">Cancelar</button>
            <button id="btn-aceptar-confirmar" style="background:#e74c3c;color:#fff;padding:0.5rem 1.5rem;border:none;border-radius:5px;font-weight:bold;">Sí, eliminar</button>
        </div>
    </div>
</div>
@extends('layouts.dashboard')

@section('content')
<div style="padding:2rem; padding-bottom:5rem;">
    <h2 style="color:#2176ae; margin-bottom:1.5rem;"><i class="fa fa-folder" style="margin-right:10px;"></i>Gestión de Categorías</h2>
    <div style="background:#e3f4fd; padding:1.2rem; border-radius:8px; color:#2176ae; font-size:1.1rem; margin-bottom:1.5rem;">
        Aquí podrás crear, editar y eliminar categorías y subcategorías de productos.
    </div>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem; gap:1rem; flex-wrap:wrap;">
        <div style="flex:1; min-width:220px; display:flex; align-items:center;">
            <input id="buscador-general" type="text" placeholder="Buscar categoría o subcategoría" style="flex:1; padding:0.5rem; border-radius:5px; border:1px solid #ccc; min-width:220px;">
        </div>
        <button class="btn btn-primary" style="background:#2176ae; color:#fff; border:none; border-radius:6px; padding:0.6rem 1.2rem; font-weight:bold; cursor:pointer; min-width:180px;" onclick="showCategoriaModal()">
            <i class="fa fa-plus"></i> Añadir categoría
        </button>
    </div>
    <div class="accordion" id="categoriasAccordion">
        @foreach($categorias as $cat)
            <div class="accordion-item mb-2">
                <h2 class="accordion-header" id="heading{{ $cat->id }}">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div class="d-flex align-items-center flex-grow-1">
                            <button class="accordion-button collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $cat->id }}" aria-expanded="false" aria-controls="collapse{{ $cat->id }}">
                                <span class="fw-semibold">{{ $cat->nombre }}</span>
                                <span class="badge bg-info ms-3">{{ $cat->subcategorias->count() }} subcategorías</span>
                            </button>
                            <div class="d-flex align-items-center ms-3" style="gap: 0.5rem;">
                                <button type="button" class="btn btn-sm btn-warning d-flex align-items-center btn-editar-categoria" title="Editar" data-id="{{ $cat->id }}" data-nombre="{{ $cat->nombre }}">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger d-flex align-items-center btn-eliminar-categoria" title="Eliminar" data-id="{{ $cat->id }}" data-nombre="{{ $cat->nombre }}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </h2>
                <div id="collapse{{ $cat->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $cat->id }}" data-bs-parent="#categoriasAccordion">
                    <div class="accordion-body">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Subcategoría</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cat->subcategorias as $sub)
                                    <tr>
                                        <td>{{ $sub->nombre }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-warning me-1 btn-editar-subcategoria" title="Editar" data-id="{{ $sub->id }}" data-nombre="{{ $sub->nombre }}" data-categoria="{{ $cat->nombre }}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-subcategoria" title="Eliminar" data-id="{{ $sub->id }}" data-nombre="{{ $sub->nombre }}" data-categoria="{{ $cat->nombre }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted text-center">Sin subcategorías</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal para añadir categoría y subcategoría -->
<div id="modal-categoria" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:380px; width:100%; box-sizing:border-box; padding:1.5rem 1.2rem;">
        <h3 id="modal-titulo" style="color:#2176ae; font-size:1.25rem; margin-bottom:1.2rem;">Añadir Categoría</h3>
        <form id="form-categoria">
            <input type="hidden" name="categoria_id" id="categoria_id">
            <div style="margin-bottom:1rem;">
                <label for="select-categoria-modal" style="font-weight:bold;">Categoría</label>
                <select id="select-categoria-modal" name="categoria_select" style="width:100%;padding:0.5rem;border-radius:5px;border:1px solid #ccc;">
                    <option value="">-- Nueva categoría --</option>
                </select>
            </div>
            <div id="div-nueva-categoria" style="margin-bottom:1rem;">
                <input type="text" name="nombre_categoria" id="input-nombre-categoria" placeholder="Nombre de la categoría" style="width:100%;padding:0.5rem;border-radius:5px;border:1px solid #ccc;" autocomplete="off">
            </div>
            <div id="div-nombre-subcategoria">
                <input type="text" name="nombre_subcategoria" placeholder="Nombre de la subcategoría (opcional)" style="width:100%;margin-bottom:1rem;padding:0.5rem;border-radius:5px;border:1px solid #ccc;">
            </div>
            <div id="error-subcategoria" style="color:#e74c3c; font-size:0.98rem; margin-bottom:0.5rem; display:none;"></div>
            <div style="display:flex;justify-content:flex-end;gap:0.7rem;">
                <button type="button" onclick="closeCategoriaModal()" style="background:#ccc;color:#222;padding:0.5rem 1.2rem;border:none;border-radius:5px;">Cancelar</button>
                <button type="submit" style="background:#2176ae;color:#fff;padding:0.5rem 1.2rem;border:none;border-radius:5px;font-weight:bold;">Guardar</button>
            </div>
        </form>
    </div>
</div>
<!-- Modal para editar subcategoría -->
<div id="modalEditarSubcategoria" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:380px; width:100%; box-sizing:border-box; padding:1.5rem 1.2rem;">
        <h3 style="color:#2176ae; font-size:1.25rem; margin-bottom:1.2rem;">Editar subcategoría</h3>
    <input type="hidden" id="editSubId">
    <div class="form-group" style="margin-bottom:1rem;">
      <label for="editSubNombre">Nombre de la subcategoría</label>
      <input type="text" class="form-control" id="editSubNombre" style="width:100%;padding:0.5rem;border-radius:5px;border:1px solid #ccc;">
    </div>
    <div style="display:flex;justify-content:flex-end;gap:0.7rem;">
      <button type="button" onclick="cerrarModalEditarSubcategoria()" style="background:#ccc;color:#222;padding:0.5rem 1.2rem;border:none;border-radius:5px;">Cancelar</button>
      <button type="button" onclick="guardarEdicionSubcategoria()" style="background:#2176ae;color:#fff;padding:0.5rem 1.2rem;border:none;border-radius:5px;font-weight:bold;">Guardar cambios</button>
    </div>
  </div>
</div>
<!-- Contenedor de notificaciones toast -->
<div id="toast-container" style="position:fixed;top:30px;right:30px;z-index:2000;"></div>
@endsection

@push('styles')
<style>
.modal {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}
.modal-content {
    background: #fff;
    padding: 2rem;
    border-radius: 10px;
    min-width: 300px;
}
.table-lista {
  width: 100%;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(33,118,174,0.08);
  border-collapse: separate;
  border-spacing: 0 0.5rem;
}
.table-lista td {
  border: none;
  padding: 1.1rem 0.7rem;
  vertical-align: middle;
}
.table-lista .fa-folder {
  font-size: 1.5rem;
  color: #2176ae;
}
.badge-subcat {
  background: #e3f4fd;
  color: #2176ae;
  border-radius: 8px;
  padding: 0.2rem 0.7rem;
  margin-right: 0.4rem;
  font-size: 0.97rem;
  font-weight: 500;
  display: inline-block;
}
.badge-subcat .btn-delete-sub {
  padding: 0;
  margin-left: 4px;
  font-size: 1rem;
  vertical-align: middle;
  line-height: 1;
}
button,
.btn,
.btn-edit,
.btn-delete,
.btn-edit-sub,
.btn-delete-sub {
  cursor: pointer !important;
}
.table-lista .btn-edit {
  color: #2176ae;
  background: none;
  border: none;
  font-size: 1.1rem;
  margin-right: 0.5rem;
}
.table-lista .btn-delete {
  color: #e74c3c;
  background: none;
  border: none;
  font-size: 1.1rem;
}
</>
@endpush

@push('scripts')
<script>
// Modal de confirmación reutilizable
let confirmarCallback = null;
function showConfirmar(mensaje, callback) {
    document.getElementById('modal-confirmar').style.display = 'flex';
    document.getElementById('confirmar-mensaje').textContent = mensaje;
    confirmarCallback = callback;
}
document.getElementById('btn-cancelar-confirmar').onclick = function() {
    document.getElementById('modal-confirmar').style.display = 'none';
    confirmarCallback = null;
};
document.getElementById('btn-aceptar-confirmar').onclick = function() {
    document.getElementById('modal-confirmar').style.display = 'none';
    if (typeof confirmarCallback === 'function') confirmarCallback();
    confirmarCallback = null;
};
// Notificación tipo toast
function showToast(mensaje, tipo = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.textContent = mensaje;
    toast.style.background = tipo === 'success' ? '#2176ae' : '#e74c3c';
    toast.style.color = '#fff';
    toast.style.padding = '1rem 1.5rem';
    toast.style.marginBottom = '1rem';
    toast.style.borderRadius = '7px';
    toast.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
    toast.style.fontWeight = 'bold';
    toast.style.fontSize = '1.05rem';
    toast.style.opacity = '0.97';
    toast.style.transition = 'opacity 0.4s';
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 400);
    }, 2600);
}

function showCategoriaModal(modo = 'crear', id = null, nombre = '', subnombre = '') {
    document.getElementById('modal-categoria').style.display = 'flex';
    const titulo = document.getElementById('modal-titulo');
    // Limpiar mensaje de error siempre que se abre el modal
    const errorDiv = document.getElementById('error-subcategoria');
    errorDiv.style.display = 'none';
    errorDiv.textContent = '';
    const subcatDiv = document.getElementById('div-nombre-subcategoria');
    const selectCat = document.getElementById('select-categoria-modal');
    const divNuevaCat = document.getElementById('div-nueva-categoria');
    const inputNombreCat = document.getElementById('input-nombre-categoria');
    if(modo === 'editar') {
        titulo.textContent = 'Editar Categoría';
        form.categoria_id.value = id;
        // Ocultar select y subcategoría, mostrar solo input de nombre
        selectCat.style.display = 'none';
        divNuevaCat.style.display = '';
        inputNombreCat.value = nombre;
        form.nombre_categoria.value = nombre;
        subcatDiv.style.display = 'none';
        // El label de categoría también se oculta
        selectCat.previousElementSibling.style.display = 'none';
    } else {
        titulo.textContent = 'Añadir Categoría';
        form.categoria_id.value = '';
        // Mostrar select y subcategoría
        selectCat.style.display = '';
        divNuevaCat.style.display = '';
        inputNombreCat.value = '';
        form.nombre_categoria.value = '';
        form.nombre_subcategoria.value = '';
        subcatDiv.style.display = '';
        // Mostrar label
        selectCat.previousElementSibling.style.display = '';
        // Cargar categorías en el select
        fetch('/categorias-listar')
            .then(res => res.json())
            .then(data => {
                selectCat.innerHTML = '<option value="">-- Nueva categoría --</option>';
                data.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.id;
                    opt.textContent = cat.nombre;
                    selectCat.appendChild(opt);
                });
            });
        selectCat.value = '';
    }
    // Mostrar/ocultar input de nueva categoría según selección (solo en crear)
    if(modo !== 'editar') {
        selectCat.onchange = function() {
            if(this.value) {
                divNuevaCat.style.display = 'none';
                inputNombreCat.removeAttribute('required');
                inputNombreCat.value = '';
            } else {
                divNuevaCat.style.display = '';
                inputNombreCat.setAttribute('required', 'required');
            }
        };
        // Inicializar required correctamente
        if(selectCat.value) {
            inputNombreCat.removeAttribute('required');
        } else {
            inputNombreCat.setAttribute('required', 'required');
        }
    }
    form.setAttribute('data-modo', modo);
}
function closeCategoriaModal() {
    document.getElementById('modal-categoria').style.display = 'none';
    form.reset();
    form.categoria_id.value = '';
    form.setAttribute('data-modo', 'crear');
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.content;
    alert('Error: No se encontró el token CSRF en el head.');
    throw new Error('No CSRF token');
}

// Cargar y filtrar categorías y subcategorías
let categoriasData = [];
// Renderizar categorías y subcategorías en el acordeón dinámicamente
function renderCategoriasAccordion(data, expandAll = false) {
    const container = document.getElementById('categoriasAccordion');
    if (!container) return;
    container.innerHTML = '';
    data.forEach(cat => {
        // Si la categoría tiene subcategorías filtradas, expandir el acordeón
        const expand = expandAll || (cat.subcategorias.length > 0 && cat.subcategorias.length !== (cat.total_subcategorias || cat.subcategorias.length));
        const item = document.createElement('div');
        item.className = 'accordion-item mb-2';
        // Resaltar subcategoría buscada si hay filtro
        let searchVal = document.getElementById('buscador-general')?.value.trim().toLowerCase();
        item.innerHTML = `
            <h2 class="accordion-header" id="heading${cat.id}">
                <div class="d-flex align-items-center justify-content-between w-100">
                    <div class="d-flex align-items-center flex-grow-1">
                        <button class="accordion-button${expand ? '' : ' collapsed'} flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${cat.id}" aria-expanded="${expand ? 'true' : 'false'}" aria-controls="collapse${cat.id}">
                            <span class="fw-semibold">${cat.nombre}</span>
                            <span class="badge bg-info ms-3">${cat.subcategorias.length} subcategorías</span>
                        </button>
                        <div class="d-flex align-items-center ms-3" style="gap: 0.5rem;">
                            <button type="button" class="btn btn-sm btn-warning d-flex align-items-center btn-editar-categoria" title="Editar" data-id="${cat.id}" data-nombre="${cat.nombre}">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger d-flex align-items-center btn-eliminar-categoria" title="Eliminar" data-id="${cat.id}" data-nombre="${cat.nombre}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </h2>
            <div id="collapse${cat.id}" class="accordion-collapse collapse${expand ? ' show' : ''}" aria-labelledby="heading${cat.id}" data-bs-parent="#categoriasAccordion">
                <div class="accordion-body">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Subcategoría</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${cat.subcategorias.length > 0 ? cat.subcategorias.map(sub => {
                                let nombreSub = sub.nombre;
                                if(searchVal && nombreSub.toLowerCase().includes(searchVal)) {
                                    nombreSub = `<span style='background: #ffe066; color: #222; border-radius: 4px; padding: 2px 6px;'>${nombreSub}</span> <span class='text-muted' style='font-size:0.95em;'>(en categoría: ${cat.nombre})</span>`;
                                }
                                return `
                                    <tr>
                                        <td>${nombreSub}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-warning me-1 btn-editar-subcategoria" title="Editar" data-id="${sub.id}" data-nombre="${sub.nombre}" data-categoria="${cat.nombre}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-subcategoria" title="Eliminar" data-id="${sub.id}" data-nombre="${sub.nombre}" data-categoria="${cat.nombre}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            }).join('') : `
                                <tr>
                                    <td colspan="2" class="text-muted text-center">Sin subcategorías</td>
                                </tr>
                            `}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        container.appendChild(item);
    });
    // Asignar eventos a los botones de acción generados
    container.querySelectorAll('.btn-editar-categoria').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            editarCategoria(id, nombre, '');
        });
    });
    container.querySelectorAll('.btn-eliminar-categoria').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            eliminarCategoria(id);
        });
    });
    container.querySelectorAll('.btn-editar-subcategoria').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            abrirModalEditarSubcategoria(id, nombre);
        });
    });
    container.querySelectorAll('.btn-eliminar-subcategoria').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            eliminarSubcategoria(id);
        });
    });
}
function cargarCategorias() {
    fetch('/categorias-listar')
        .then(res => res.json())
        .then(data => {
            categoriasData = data;
            renderCategoriasAccordion(data);
        });
}
cargarCategorias();

// Buscador general de categoría o subcategoría
document.addEventListener('DOMContentLoaded', function() {
    const inputGeneral = document.getElementById('buscador-general');
    const acordeon = document.getElementById('categoriasAccordion');
    let backupHTML = acordeon.innerHTML;
    function filtrar() {
        const val = inputGeneral.value.trim().toLowerCase();
        if(val === '') {
            acordeon.innerHTML = backupHTML;
            return;
        }
        let filtradas = categoriasData.map(cat => {
            const subsFiltradas = cat.subcategorias.filter(sub =>
                sub.nombre.toLowerCase().includes(val)
            );
            const coincideCategoria = cat.nombre.toLowerCase().includes(val);
            if(coincideCategoria && subsFiltradas.length === 0 && cat.subcategorias.length > 0) {
                return {...cat};
            } else if(coincideCategoria) {
                return {...cat};
            } else if(subsFiltradas.length > 0) {
                return {...cat, subcategorias: subsFiltradas};
            }
            return null;
        }).filter(Boolean);
        renderCategoriasAccordion(filtradas);
    }
    inputGeneral.addEventListener('input', filtrar);
});

// Guardar nueva categoría y subcategoría
const form = document.getElementById('form-categoria');
form.onsubmit = function(e) {
    e.preventDefault();
    document.getElementById('error-subcategoria').style.display = 'none';
    document.getElementById('error-subcategoria').textContent = '';
    const modo = form.getAttribute('data-modo') || 'crear';
    const selectCat = document.getElementById('select-categoria-modal');
    const inputNombreCat = document.getElementById('input-nombre-categoria');
    let categoria_id = selectCat.value;
    let nombre_categoria = inputNombreCat.value;
    let csrf = '';
    try { csrf = getCsrfToken(); } catch { return; }
    if (categoria_id) {
        // Si selecciona una categoría existente, solo crear subcategoría
        const nombre_subcategoria = form.nombre_subcategoria.value;
        if (!nombre_subcategoria) {
            document.getElementById('error-subcategoria').textContent = 'El nombre de la subcategoría es obligatorio.';
            document.getElementById('error-subcategoria').style.display = 'block';
            return;
        }
        fetch('/subcategorias', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ nombre: nombre_subcategoria, categoria_id })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                closeCategoriaModal();
                form.reset();
                cargarCategorias();
                showToast('Subcategoría añadida correctamente.', 'success');
            }else if(data.message){
                document.getElementById('error-subcategoria').textContent = data.message;
                document.getElementById('error-subcategoria').style.display = 'block';
                showToast(data.message, 'error');
            }else{
                showToast('Error al guardar', 'error');
            }
        })
        .catch(() => showToast('Error de conexión o validación.', 'error'));
        return;
    }
    // Si es nueva categoría, crear categoría y opcionalmente subcategoría
    const fd = new URLSearchParams();
    fd.append('nombre_categoria', nombre_categoria);
    fd.append('nombre_subcategoria', form.nombre_subcategoria.value);
    let url = '/categorias';
    let method = 'POST';
    if(modo === 'editar' && form.categoria_id.value) {
        url = `/categorias/${form.categoria_id.value}`;
        method = 'PUT';
    }
    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: fd.toString()
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            closeCategoriaModal();
            form.reset();
            cargarCategorias();
            showToast(modo === 'editar' ? 'Categoría actualizada correctamente.' : 'Categoría creada correctamente.', 'success');
        }else if(data.message){
            document.getElementById('error-subcategoria').textContent = data.message;
            document.getElementById('error-subcategoria').style.display = 'block';
            showToast(data.message, 'error');
        }else{
            showToast('Error al guardar', 'error');
        }
    })
    .catch(() => showToast('Error de conexión o validación.', 'error'));
};

// Lógica para eliminar categoría
function eliminarCategoria(id) {
    showConfirmar('¿Seguro que deseas eliminar esta categoría? Esta acción no se puede deshacer.', function() {
        fetch(`/categorias/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                cargarCategorias();
                showToast('Categoría eliminada correctamente.', 'success');
            }
            else showToast('No se pudo eliminar', 'error');
        });
    });
}
// Lógica para eliminar subcategoría
function eliminarSubcategoria(id) {
    showConfirmar('¿Seguro que deseas eliminar esta subcategoría? Esta acción no se puede deshacer.', function() {
        fetch(`/subcategorias/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                cargarCategorias();
                showToast('Subcategoría eliminada correctamente.', 'success');
            } else {
                showToast('No se pudo eliminar la subcategoría', 'error');
            }
        });
    });
}
// Lógica para editar (ahora permite editar categoría y subcategoría desde el modal)
function editarCategoria(id, nombre, subnombre) {
    showCategoriaModal('editar', id, nombre, subnombre);
}
function abrirModalEditarSubcategoria(id, nombre) {
    document.getElementById('editSubId').value = id;
    document.getElementById('editSubNombre').value = nombre;
    document.getElementById('modalEditarSubcategoria').style.display = 'flex';
}
function cerrarModalEditarSubcategoria() {
    document.getElementById('modalEditarSubcategoria').style.display = 'none';
}
function guardarEdicionSubcategoria() {
    const id = document.getElementById('editSubId').value;
    const nombre = document.getElementById('editSubNombre').value.trim();
    if (!nombre) {
        showToast('El nombre no puede estar vacío', 'error');
        return;
    }
    fetch(`/subcategorias/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ nombre })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Subcategoría actualizada correctamente', 'success');
            cerrarModalEditarSubcategoria();
            cargarCategorias();
        } else {
            showToast(data.message || 'No se pudo actualizar la subcategoría', 'error');
        }
    });
}
</script>
@endpush
