@extends('layouts.dashboard')

@section('content')
<!-- ========================= MODALES REUTILIZABLES ========================= -->
<!-- Modal Confirmación -->
<div id="modal-confirmar" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:380px; width:100%; text-align:center; box-sizing:border-box; padding:1.5rem 1.2rem;">
        <h3 style="color:#e74c3c; margin-bottom:1rem;">Confirmar acción</h3>
        <div id="confirmar-mensaje" style="font-size:1.05rem; margin-bottom:1.5rem; color:#333;">¿Seguro que deseas continuar?</div>
        <div style="display:flex; justify-content:center; gap:1rem; flex-wrap:wrap;">
            <button id="btn-cancelar-confirmar" class="btn btn-secondary btn-sm">Cancelar</button>
            <button id="btn-aceptar-confirmar" class="btn btn-danger btn-sm fw-bold">Sí, continuar</button>
        </div>
    </div>
</div>
<!-- Modal Crear / Editar Categoría y Subcategoría -->
<div id="modal-categoria" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:420px; width:100%; box-sizing:border-box; padding:1.5rem 1.2rem;">
        <h5 id="modal-titulo" class="fw-bold mb-3 text-primary">Nueva Categoría</h5>
        <form id="form-categoria">
            <input type="hidden" id="categoria_id" name="categoria_id" />
            <div class="mb-3">
                <label class="form-label fw-semibold">Nombre de la categoría</label>
                <input type="text" id="input-nombre-categoria" name="nombre_categoria" class="form-control" placeholder="Ej: Medicamentos" autocomplete="off" required>
            </div>
            <div class="mb-2">
                <label class="form-label fw-semibold">Subcategoría inicial (opcional)</label>
                <input type="text" id="input-nombre-subcategoria" name="nombre_subcategoria" class="form-control" placeholder="Ej: Analgésicos" autocomplete="off">
            </div>
            <div id="error-subcategoria" class="text-danger small mb-2" style="display:none;"></div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" onclick="closeCategoriaModal()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm fw-bold">Guardar</button>
            </div>
        </form>
    </div>
</div>
<!-- Modal Editar Subcategoría -->
<div id="modalEditarSubcategoria" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:380px; width:100%; box-sizing:border-box; padding:1.5rem 1.2rem;">
        <h5 class="fw-bold text-primary mb-3">Editar Subcategoría</h5>
        <input type="hidden" id="editSubId">
        <div class="mb-3">
            <label class="form-label fw-semibold">Nombre</label>
            <input type="text" id="editSubNombre" class="form-control" autocomplete="off">
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" onclick="cerrarModalEditarSubcategoria()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
            <button type="button" onclick="guardarEdicionSubcategoria()" class="btn btn-primary btn-sm fw-bold">Guardar</button>
        </div>
    </div>
</div>
<div id="toast-container" style="position:fixed;top:30px;right:30px;z-index:3000;"></div>
<!-- ========================= /MODALES ========================= -->

<div class="container-fluid py-3">
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <h3 class="m-0 text-primary fw-semibold d-flex align-items-center gap-2"><i class="fa fa-folder-open"></i> Categorías</h3>
        <span class="text-muted small">Administración de categorías y sus subcategorías</span>
    </div>
    <div class="row g-3">
        <!-- LISTA CATEGORÍAS -->
        <div class="col-lg-4 col-md-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 fw-bold">Listado</h6>
                    <button id="btn-nueva-categoria" class="btn btn-sm btn-primary"><i class="fa fa-plus me-1"></i>Nueva</button>
                </div>
                <div class="card-body p-2">
                    <input type="search" id="buscador-categorias" class="form-control form-control-sm mb-2" placeholder="Buscar...">
                    <div id="lista-categorias" class="list-group small" style="max-height:68vh;overflow-y:auto;">
                        <div class="p-3 text-center text-muted">Cargando...</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- DETALLE -->
        <div class="col-lg-8 col-md-7">
            <div id="detalle-categoria-container" class="h-100">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-muted">
                        <i class="fa fa-hand-pointer fa-2x mb-3"></i>
                        <p class="text-center m-0">Selecciona una categoría para ver y gestionar sus subcategorías.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.modal {position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.45);display:flex;justify-content:center;align-items:center;z-index:2000;}
.modal-content {background:#fff;border-radius:12px;box-shadow:0 10px 35px -5px rgba(0,0,0,.25);} 
.list-group-item-action {cursor:pointer;}
.list-group-item-action.active {background:#e8f2ff;color:#0d6efd;border-left:4px solid #0d6efd;font-weight:600;}
.list-group-item-action .badge {float:right;}
.subcat-row-actions button {border:none;background:transparent;color:#ff9800 !important;}
.subcat-row-actions button:hover {color:#fb8c00 !important;}
.table-subcats tbody tr:hover {background:#fafbfd;}
#detalle-categoria-container .card-header {background:#fff;border-bottom:1px solid #eef2f5;}
.badge-soft {background:#eef7ff;color:#0d6efd;font-weight:500;border-radius:20px;padding:.3rem .65rem;font-size:.7rem;}
/* ========================= ESTILOS NARANJA PARA CATEGORÍAS (SCOPED) ========================= */
/* Nota: Se evita modificar .text-primary global. Solo se estiliza dentro del header y botones específicos. */
.card-header h6,
.mb-3 h3.text-primary,
.card-header h5.text-primary { color: #ff9800 !important; }
.card-header h5.text-primary i { color: inherit !important; }
.btn-primary { background-color: #ff9800 !important; border-color: #ff9800 !important; color: #fff !important; }
.list-group-item-action.active { background: #fff3e0 !important; color: #ff9800 !important; border-left: 4px solid #ff9800 !important; }
.badge-soft { background: #fff3e0 !important; color: #ff9800 !important; }

/* Botones del header de categoría: Editar y Subcategoría con fondo blanco y hover naranja */
.modal-content #modal-titulo.text-primary { color: #ff9800 !important; }
/* Título del modal Editar Subcategoría en naranja */
#modalEditarSubcategoria .modal-content h5.text-primary { color: #ff9800 !important; }
.card-header button[data-action="editar-categoria"],
.card-header button[data-action="nueva-sub"] {
    background-color: #ffffff !important;
    border-color: #ff9800 !important;
    color: #ff9800 !important;
}
.card-header button[data-action="editar-categoria"]:hover,
.card-header button[data-action="nueva-sub"]:hover,
.card-header button[data-action="editar-categoria"]:focus,
.card-header button[data-action="nueva-sub"]:focus {
    background-color: #ff9800 !important;
    border-color: #ff9800 !important;
    color: #ffffff !important;
    box-shadow: none !important;
}
/* Mantener eliminar (danger) en rojo por semántica */
.card-header .btn i { color: inherit !important; }
</style>
@endpush

@push('scripts')
<script>
(function(){
    const LS_KEY = 'categorias.selectedId';
    const state = {
        categorias: [],
        selectedCategoryId: null,
        filtro: '',
        cargando: false,
        ultimoFetchOk: true
    };

    const els = {
        lista: () => document.getElementById('lista-categorias'),
        buscador: () => document.getElementById('buscador-categorias'),
        detalle: () => document.getElementById('detalle-categoria-container'),
        btnNueva: () => document.getElementById('btn-nueva-categoria'),
        formCategoria: () => document.getElementById('form-categoria'),
        modalCategoria: () => document.getElementById('modal-categoria'),
        modalEditarSub: () => document.getElementById('modalEditarSubcategoria'),
        toastContainer: () => document.getElementById('toast-container')
    };

    // ===================== UTILIDADES =====================
    function debounce(fn, delay=300){ let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), delay); }; }
    function showToast(msg, tipo='success'){
        const c = els.toastContainer(); if(!c) return;
        const d = document.createElement('div');
        d.textContent = msg;
        d.setAttribute('role','alert');
        const bg = tipo==='success' ? '#ff9800' : '#e74c3c'; // naranja para éxito, rojo para error
        d.style.cssText = `background:${bg};color:#fff;padding:.8rem 1rem;margin-bottom:.6rem;border-radius:8px;font-size:.85rem;font-weight:600;box-shadow:0 4px 14px -3px rgba(0,0,0,.25);opacity:0;transform:translateX(40px);transition:.35s;`;
        c.appendChild(d);
        requestAnimationFrame(()=>{ d.style.opacity='1'; d.style.transform='translateX(0)'; });
        setTimeout(()=>{ d.style.opacity='0'; d.style.transform='translateX(40px)'; setTimeout(()=>d.remove(),400); },2700);
    }
    function confirmar(mensaje, cb){ const modal=document.getElementById('modal-confirmar'); modal.style.display='flex'; document.getElementById('confirmar-mensaje').textContent=mensaje; const btnOk=document.getElementById('btn-aceptar-confirmar'); const btnNo=document.getElementById('btn-cancelar-confirmar'); const close=()=>{modal.style.display='none'; btnOk.removeEventListener('click',okH); btnNo.removeEventListener('click',noH);} ; const okH=()=>{cb&&cb(); close();}; const noH=()=>close(); btnOk.addEventListener('click',okH,{once:true}); btnNo.addEventListener('click',noH,{once:true}); }
    function csrf(){ const m=document.querySelector('meta[name="csrf-token"]'); return m?m.content:''; }

    // ===================== PERSISTENCIA =====================
    function loadPersisted(){ try { const v=localStorage.getItem(LS_KEY); if(v) state.selectedCategoryId = parseInt(v); } catch{} }
    function savePersisted(){ try { if(state.selectedCategoryId) localStorage.setItem(LS_KEY, state.selectedCategoryId); } catch{} }

    // ===================== FETCH =====================
    async function cargarCategorias({silencioso=false}={}){
        state.cargando = true; if(!silencioso) renderListaSkeleton();
        try { const res= await fetch('/categorias-listar',{headers:{'Accept':'application/json'}}); if(!res.ok) throw new Error('Error HTTP '+res.status); const data = await safeJson(res); if(!Array.isArray(data)) throw new Error('Formato inesperado'); state.categorias = data.map(c=>({...c, subcategorias: c.subcategorias||[]})); state.ultimoFetchOk = true; reconcileSelected(); renderLista(); renderDetalle(); }
        catch(e){ console.error(e); state.ultimoFetchOk=false; renderListaError(e.message); renderDetallePlaceholder(); if(!silencioso) showToast('Error cargando categorías','error'); }
        finally { state.cargando=false; }
    }
    async function safeJson(res){ try { return await res.json(); } catch{ throw new Error('JSON inválido'); } }
    function reconcileSelected(){ if(state.selectedCategoryId && !state.categorias.find(c=>c.id===state.selectedCategoryId)) state.selectedCategoryId=null; if(state.selectedCategoryId===null && state.categorias.length>0) state.selectedCategoryId=state.categorias[0].id; savePersisted(); }

    // ===================== RENDER LISTA =====================
    function renderListaSkeleton(){ const cont=els.lista(); if(!cont) return; cont.innerHTML = `<div class='p-3'><div class='placeholder-glow'>${'<div class="placeholder col-12 mb-2" style="height:32px;"></div>'.repeat(5)}</div></div>`; }
    function renderListaError(msg){ const cont=els.lista(); if(!cont) return; cont.innerHTML = `<div class='p-3 text-center small text-danger'>${msg || 'Error'}<div class='mt-2'><button class='btn btn-sm btn-outline-primary' data-action='reintentar-cargar'>Reintentar</button></div></div>`; }
    function renderLista(){ const cont = els.lista(); if(!cont) return; const filtro = state.filtro.trim().toLowerCase(); let data = state.categorias; if(filtro){ data = data.filter(c => c.nombre.toLowerCase().includes(filtro)); }
        if(data.length===0){ cont.innerHTML = `<div class='p-3 text-center text-muted'>${filtro? 'Sin coincidencias' : 'No hay categorías'}</div>`; return; }
        cont.innerHTML=''; data.forEach(cat => { const a=document.createElement('button'); a.type='button'; a.className='list-group-item list-group-item-action d-flex align-items-center justify-content-between'; if(cat.id===state.selectedCategoryId) a.classList.add('active'); a.dataset.id=cat.id; const resaltado = filtro? highlight(cat.nombre,filtro): cat.nombre; a.innerHTML=`<span class='d-flex align-items-center gap-2'><i class="fa ${cat.id===state.selectedCategoryId? 'fa-folder-open':'fa-folder'}"></i>${resaltado}</span><span class='badge bg-secondary rounded-pill'>${cat.subcategorias.length}</span>`; cont.appendChild(a); }); }
    function highlight(text, term){ const idx=text.toLowerCase().indexOf(term); if(idx===-1) return text; return text.substring(0,idx)+`<mark style='background:#ffe69c;'>`+text.substring(idx, idx+term.length)+`</mark>`+text.substring(idx+term.length); }

    // ===================== RENDER DETALLE =====================
    // ====== PAGINACIÓN SUBCATEGORÍAS ======
    if(!state.subcatPag) state.subcatPag = {};
    function getSubcatPage(catId){ return state.subcatPag[catId]?.page || 1; }
    function getSubcatSize(catId){ return state.subcatPag[catId]?.size || 5; }
    function setSubcatPage(catId, page){ state.subcatPag[catId] = state.subcatPag[catId]||{}; state.subcatPag[catId].page = page; }
    function setSubcatSize(catId, size){ state.subcatPag[catId] = state.subcatPag[catId]||{}; state.subcatPag[catId].size = size; }

    function renderDetalle(){
        if(state.selectedCategoryId===null){ renderDetallePlaceholder(); return; }
        const cat = state.categorias.find(c=>c.id===state.selectedCategoryId);
        if(!cat){ renderDetallePlaceholder(); return; }
        const cont = els.detalle();
        // Paginación
        const page = getSubcatPage(cat.id);
        const size = getSubcatSize(cat.id);
        const total = cat.subcategorias.length;
        const lastPage = Math.max(1, Math.ceil(total/size));
        const start = (page-1)*size;
        const end = start+size;
        const subcats = cat.subcategorias.slice(start,end);
        cont.innerHTML = `
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center py-2">
                    <div class="d-flex flex-column">
                        <h5 class="mb-0 fw-bold text-primary d-flex align-items-center gap-2"><i class="fa fa-folder-open"></i> ${escapeHtml(cat.nombre)}</h5>
                        <small class="text-muted">${cat.subcategorias.length} subcategoría(s)</small>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-warning btn-sm" data-action="editar-categoria" data-id="${cat.id}" data-nombre="${escapeHtml(cat.nombre)}"><i class="fa fa-edit"></i> Editar</button>
                        <button class="btn btn-outline-danger btn-sm" data-action="eliminar-categoria" data-id="${cat.id}" data-nombre="${escapeHtml(cat.nombre)}"><i class="fa fa-trash"></i></button>
                        <button class="btn btn-success btn-sm" data-action="nueva-sub" data-id="${cat.id}"><i class="fa fa-plus"></i> Subcategoría</button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div></div>
                        <div class="d-flex align-items-center gap-2">
                            <label class="mb-0 small">Mostrar</label>
                            <select id="subcat-size" class="form-select form-select-sm" style="width:auto;">
                                <option value="5" ${size==5?'selected':''}>5</option>
                                <option value="10" ${size==10?'selected':''}>10</option>
                                <option value="15" ${size==15?'selected':''}>15</option>
                            </select>
                            <span class="small">por página</span>
                        </div>
                    </div>
                    ${subcats.length? renderTablaSubcats(cat, subcats, start) : `<div class='text-center text-muted py-5'>Sin subcategorías aún. <br><button class='btn btn-sm btn-primary mt-3' data-action='nueva-sub' data-id='${cat.id}'><i class=\"fa fa-plus\"></i> Crear la primera</button></div>`}
                    <div class="d-flex justify-content-end align-items-center mt-2" id="subcat-paginador">
                        <button class="btn btn-outline-secondary btn-sm me-2" id="subcat-prev" ${page<=1?'disabled':''}>&laquo; Anterior</button>
                        <span class="small">Página ${page} de ${lastPage}</span>
                        <button class="btn btn-outline-secondary btn-sm ms-2" id="subcat-next" ${page>=lastPage?'disabled':''}>Siguiente &raquo;</button>
                    </div>
                </div>
            </div>`;
        // Eventos paginación
        const sel = document.getElementById('subcat-size');
        sel && sel.addEventListener('change', e=>{ setSubcatSize(cat.id, parseInt(sel.value)); setSubcatPage(cat.id,1); renderDetalle(); });
        document.getElementById('subcat-prev').addEventListener('click', ()=>{ if(page>1){ setSubcatPage(cat.id, page-1); renderDetalle(); } });
        document.getElementById('subcat-next').addEventListener('click', ()=>{ if(page<lastPage){ setSubcatPage(cat.id, page+1); renderDetalle(); } });
    }
    function renderTablaSubcats(cat, subcats, startIdx){ return `<div class='table-responsive'>
            <table class='table table-sm align-middle table-subcats mb-0'>
                <thead><tr><th style='width:40px;'>#</th><th>Subcategoría</th><th class='text-end' style='width:140px;'>Acciones</th></tr></thead>
                <tbody>
                    ${subcats.map((s,i)=>`<tr>
                        <td class='text-muted small'>${startIdx+i+1}</td>
                        <td>${escapeHtml(s.nombre)}</td>
                        <td class='text-end'>
                            <button class='btn btn-light btn-sm' title='Editar' data-action='editar-sub' data-id='${s.id}' data-nombre='${escapeAttr(s.nombre)}'><i class='fa fa-edit'></i></button>
                            <button class='btn btn-light btn-sm' title='Eliminar' data-action='eliminar-sub' data-id='${s.id}' data-nombre='${escapeAttr(s.nombre)}'><i class='fa fa-trash text-danger'></i></button>
                        </td>
                    </tr>`).join('')}
                </tbody>
            </table>
        </div>`; }
    function renderDetallePlaceholder(){ els.detalle().innerHTML = `<div class='card shadow-sm h-100'><div class='card-body d-flex flex-column justify-content-center align-items-center text-muted'><i class='fa fa-hand-pointer fa-2x mb-3'></i><p class='m-0 text-center'>Selecciona una categoría para ver y gestionar sus subcategorías.</p></div></div>`; }

    // ===================== EVENTOS =====================
    document.addEventListener('click', (e)=>{ const btn = e.target.closest('[data-action]'); if(btn){ const action = btn.dataset.action; if(action==='editar-categoria'){ showCategoriaModal('editar', btn.dataset.id, btn.dataset.nombre); }
            else if(action==='eliminar-categoria'){ validarYConfirmarEliminarCategoria(btn.dataset.id, btn.dataset.nombre); }
            else if(action==='nueva-sub'){ showCategoriaModal('sub', btn.dataset.id); }
            else if(action==='editar-sub'){ abrirModalEditarSubcategoria(btn.dataset.id, btn.dataset.nombre); }
            else if(action==='eliminar-sub'){ validarYConfirmarEliminarSubcategoria(btn.dataset.id, btn.dataset.nombre); }
            else if(action==='reintentar-cargar'){ cargarCategorias(); }
        }
        const item = e.target.closest('#lista-categorias button.list-group-item'); if(item){ const id=parseInt(item.dataset.id); if(state.selectedCategoryId!==id){ state.selectedCategoryId=id; savePersisted(); renderLista(); renderDetalle(); } }
    });

    // Botón NUEVA CATEGORÍA (faltaba el listener)
    els.btnNueva() && els.btnNueva().addEventListener('click', ()=> showCategoriaModal('crear'));

    els.buscador() && els.buscador().addEventListener('input', debounce(()=>{ state.filtro = els.buscador().value; renderLista(); }, 250));

    // ===================== CRUD =====================
    function showCategoriaModal(modo='crear', id=null, nombre=''){
        const modal = els.modalCategoria();
        modal.style.display='flex';
        const form = els.formCategoria();
        form.reset();
        document.getElementById('error-subcategoria').style.display='none';
        form.setAttribute('data-modo', modo);
        document.getElementById('categoria_id').value = id || '';
        const titulo = document.getElementById('modal-titulo');
        const subInput = document.getElementById('input-nombre-subcategoria');
        const catInput = document.getElementById('input-nombre-categoria');
        catInput.parentElement.style.display='block';
        subInput.parentElement.style.display='block';
        // Restaurar estado por defecto
        catInput.disabled = false;
        catInput.required = true;
        if(modo==='editar'){
            titulo.textContent='Editar Categoría';
            catInput.value = nombre;
            subInput.parentElement.style.display='none';
        } else if(modo==='sub'){
            titulo.textContent='Nueva Subcategoría';
            catInput.parentElement.style.display='none';
            catInput.disabled = true;
            catInput.required = false;
        } else {
            titulo.textContent='Nueva Categoría';
        }
    }
    window.closeCategoriaModal = function(){ els.modalCategoria().style.display='none'; };

    // ===== Validación en vivo duplicados =====
    const inputCat = document.getElementById('input-nombre-categoria');
    const inputSub = document.getElementById('input-nombre-subcategoria');
    const errorSub = document.getElementById('error-subcategoria');
    function existeCategoria(nombre){ if(!nombre) return false; const n=nombre.trim().toLowerCase(); return state.categorias.some(c=> c.nombre.trim().toLowerCase()===n); }
    function existeSubEnCategoria(catId, nombre){ if(!catId || !nombre) return false; const n=nombre.trim().toLowerCase(); const cat = state.categorias.find(c=> c.id==catId); if(!cat) return false; return cat.subcategorias.some(s=> s.nombre.trim().toLowerCase()===n); }
    function validarDuplicados(){ const modo = els.formCategoria().getAttribute('data-modo'); const catNombre = inputCat.value.trim(); const subNombre = inputSub.value.trim(); let ok=true; errorSub.style.display='none'; errorSub.textContent=''; inputCat.classList.remove('is-invalid'); inputSub.classList.remove('is-invalid');
        if(modo==='crear'){ if(catNombre && existeCategoria(catNombre)){ inputCat.classList.add('is-invalid'); ok=false; errorSub.textContent='Ya existe una categoría con ese nombre.'; errorSub.style.display='block'; }
            if(ok && subNombre && existeSubEnCategoria(state.categorias.find(c=> c.nombre.trim().toLowerCase()===catNombre?.toLowerCase())?.id, subNombre)){ inputSub.classList.add('is-invalid'); ok=false; errorSub.textContent='Ya existe esa subcategoría en la categoría.'; errorSub.style.display='block'; }
        } else if(modo==='sub'){ const catId = document.getElementById('categoria_id').value; if(subNombre && existeSubEnCategoria(catId, subNombre)){ inputSub.classList.add('is-invalid'); ok=false; errorSub.textContent='Subcategoría duplicada.'; errorSub.style.display='block'; }
        } else if(modo==='editar'){ /* edición de nombre de categoría: se valida al enviar */ if(catNombre && existeCategoria(catNombre) && state.categorias.find(c=> c.id==document.getElementById('categoria_id').value)?.nombre.toLowerCase()!==catNombre.toLowerCase()){ inputCat.classList.add('is-invalid'); ok=false; errorSub.textContent='Otro registro ya usa ese nombre.'; errorSub.style.display='block'; }
        }
        return ok;
    }
    ['input','blur'].forEach(ev=>{ inputCat.addEventListener(ev, ()=>{ validarDuplicados(); }); inputSub.addEventListener(ev, ()=>{ validarDuplicados(); }); });

    els.formCategoria().addEventListener('submit', async (e)=>{ e.preventDefault(); if(!validarDuplicados()) return; const form = e.currentTarget; const modo = form.getAttribute('data-modo'); const catId = document.getElementById('categoria_id').value; const nombreCat = document.getElementById('input-nombre-categoria').value.trim(); const subNombre = document.getElementById('input-nombre-subcategoria').value.trim(); try { if(modo==='editar'){ if(!nombreCat) return showToast('Nombre requerido','error'); await peticion(`/categorias/${catId}`, 'PUT', { nombre_categoria: nombreCat }); showToast('Categoría actualizada'); }
            else if(modo==='sub'){ if(!subNombre) return showToast('Nombre subcategoría requerido','error'); await peticion(`/subcategorias`, 'POST', { nombre: subNombre, categoria_id: catId }); showToast('Subcategoría creada'); }
            else { if(!nombreCat) return showToast('Nombre requerido','error'); const payload={nombre_categoria:nombreCat}; if(subNombre) payload.nombre_subcategoria=subNombre; await peticion('/categorias','POST', payload); showToast('Categoría creada'); }
            closeCategoriaModal(); await cargarCategorias({silencioso:true}); }
        catch(err){ showToast(err.message || 'Error','error'); }
    });

    window.abrirModalEditarSubcategoria = function(id,nombre){ els.modalEditarSub().style.display='flex'; document.getElementById('editSubId').value=id; document.getElementById('editSubNombre').value=nombre; };
    window.cerrarModalEditarSubcategoria = function(){ els.modalEditarSub().style.display='none'; };
    window.guardarEdicionSubcategoria = async function(){ const id=document.getElementById('editSubId').value; const nombre=document.getElementById('editSubNombre').value.trim(); if(!nombre) return showToast('Nombre requerido','error'); try { await peticion(`/subcategorias/${id}`,'PUT',{ nombre }); showToast('Subcategoría actualizada'); cerrarModalEditarSubcategoria(); await cargarCategorias({silencioso:true}); } catch(e){ showToast(e.message || 'Error','error'); } };

    async function eliminarCategoria(id){ try{ await peticion(`/categorias/${id}`,'DELETE'); showToast('Categoría eliminada'); if(state.selectedCategoryId==id) state.selectedCategoryId=null; await cargarCategorias({silencioso:true}); } catch(e){ showToast(e.message || 'No se pudo eliminar','error'); } }
    async function eliminarSubcategoria(id){ try{ await peticion(`/subcategorias/${id}`,'DELETE'); showToast('Subcategoría eliminada'); await cargarCategorias({silencioso:true}); } catch(e){ showToast(e.message || 'No se pudo eliminar','error'); } }

    async function validarYConfirmarEliminarCategoria(id, nombre){
        try {
            const res = await fetch(`/categorias/${id}/dependencias`, { headers:{'Accept':'application/json'} });
            const js = await res.json();
            const deps = js.dependencias || {medicamentos:0, subcategorias:0};
            if(deps.medicamentos>0 || deps.subcategorias>0){
                const msg = `La categoría "${nombre}" tiene ${deps.medicamentos} medicamento(s) y ${deps.subcategorias} subcategoría(s) asociadas.\nNo se recomienda eliminarla. Reasigna los medicamentos y/o elimina las subcategorías primero.`;
                confirmar(msg, ()=>{}); // solo mostrar advertencia, no eliminar
            } else {
                confirmar(`¿Eliminar la categoría "${nombre}"?`, ()=> eliminarCategoria(id));
            }
        } catch(e){ confirmar(`¿Eliminar la categoría "${nombre}"?`, ()=> eliminarCategoria(id)); }
    }

    async function validarYConfirmarEliminarSubcategoria(id, nombre){
        try {
            const res = await fetch(`/subcategorias/${id}/dependencias`, { headers:{'Accept':'application/json'} });
            const js = await res.json();
            const deps = js.dependencias || {medicamentos:0};
            if(deps.medicamentos>0){
                const msg = `La subcategoría "${nombre}" tiene ${deps.medicamentos} medicamento(s) asociados.\nNo se recomienda eliminarla. Reasigna los medicamentos primero.`;
                confirmar(msg, ()=>{});
            } else {
                confirmar(`¿Eliminar subcategoría "${nombre}"?`, ()=> eliminarSubcategoria(id));
            }
        } catch(e){ confirmar(`¿Eliminar subcategoría "${nombre}"?`, ()=> eliminarSubcategoria(id)); }
    }

    async function peticion(url, method='GET', data=null){ const opts={ method, headers:{ 'Accept':'application/json','X-CSRF-TOKEN':csrf() } }; if(data){ opts.headers['Content-Type']='application/json'; opts.body=JSON.stringify(data); } const res = await fetch(url, opts); let js; try { js = await res.json(); } catch { throw new Error('Respuesta no válida'); } if(!res.ok || js.success===false){ throw new Error(js.message || 'Error en servidor'); } return js; }

    function escapeHtml(str=''){ return str.replace(/[&<>"] /g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',' ':' '}[c])); }
    function escapeAttr(str=''){ return str.replace(/"/g,'&quot;'); }

    // ===================== INIT =====================
    loadPersisted();
    cargarCategorias();
})();
</script>
@endpush
