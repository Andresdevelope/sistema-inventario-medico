@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="mb-0">Gestión de Usuarios</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="fa fa-user-plus me-1"></i> Nuevo Usuario
        </button>
    </div>
    <!-- Toast container para notificaciones -->
    <div id="toast-container" style="position:fixed;top:30px;right:30px;z-index:3000;"></div>
    <script>
        function showToast(msg, tipo='success') {
            const c = document.getElementById('toast-container');
            if (!c) return;
            const d = document.createElement('div');
            d.textContent = msg;
            d.setAttribute('role', 'alert');
            d.style.cssText = `background:${tipo==='success'?'#2176ae':'#e74c3c'};color:#fff;padding:.8rem 1rem;margin-bottom:.6rem;border-radius:8px;font-size:.85rem;font-weight:600;box-shadow:0 4px 14px -3px rgba(0,0,0,.25);opacity:0;transform:translateX(40px);transition:.35s;`;
            c.appendChild(d);
            requestAnimationFrame(()=>{d.style.opacity='1';d.style.transform='translateX(0)';});
            setTimeout(()=>{d.style.opacity='0';d.style.transform='translateX(40px)'; setTimeout(()=>d.remove(),400);},2700);
        }
        // Mostrar notificaciones flash
        @if(session('success'))
            showToast(@json(session('success')), 'success');
        @endif
        @if(session('error'))
            showToast(@json(session('error')), 'error');
        @endif
        @if($errors->any())
            showToast(@json($errors->first()), 'error');
        @endif
    </script>
    <script>
        window.__editFailed = @json(session('edit_failed', false));
        window.__editUserId = @json(session('edit_user_id'));
        window.__createFailed = @json(session('create_failed', false));
    </script>
    <table class="table table-bordered table-hover mt-3 align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
                        @foreach($users as $user)
                                <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td><span class="badge {{ $user->role === 'admin' ? 'bg-dark' : 'bg-secondary' }}">{{ $user->role }}</span></td>
                                        <td>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                        data-id="{{ $user->id }}"
                                                        data-name="{{ $user->name }}"
                                                        data-email="{{ $user->email }}"
                                                        data-role="{{ $user->role }}">
                                                        <i class="fa fa-edit"></i>
                                                </button>
                                                @if(auth()->id() !== $user->id)
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                        data-id="{{ $user->id }}" data-name="{{ $user->name }}">
                                                        <i class="fa fa-trash"></i>
                                                </button>
                                                @endif
                                        </td>
                                </tr>
                        @endforeach
        </tbody>
    </table>

        <!-- Modal Crear Usuario -->
        <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Crear Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="createUserForm" action="{{ route('usuarios.store') }}" method="POST" autocomplete="off">
                        @csrf
                        <div class="modal-body">
                            <div id="createUserAlert" class="alert alert-danger d-none"></div>
                            <div class="mb-3">
                                <label class="form-label">Nombre de usuario</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Contraseña</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Mínimo 8 caracteres, debe incluir letras y números.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmar contraseña</label>
                                <input type="password" class="form-control" name="password_confirmation" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">¿Color favorito?</label>
                                    <input type="text" class="form-control @error('color') is-invalid @enderror" name="color" value="{{ old('color') }}" required>
                                    @error('color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">¿Animal favorito?</label>
                                    <input type="text" class="form-control @error('animal') is-invalid @enderror" name="animal" value="{{ old('animal') }}" required>
                                    @error('animal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select @error('role') is-invalid @enderror" name="role" required>
                                    <option value="operador" {{ old('role')==='operador' ? 'selected' : '' }}>operador</option>
                                    <option value="admin" {{ old('role')==='admin' ? 'selected' : '' }}>admin</option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Crear usuario</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function(){
            const form = document.getElementById('createUserForm');
            const modalEl = document.getElementById('createUserModal');
            const alertBox = document.getElementById('createUserAlert');
            if(form && modalEl){
                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    alertBox.classList.add('d-none');
                    alertBox.innerHTML = '';
                    // Limpiar errores previos
                    form.querySelectorAll('.is-invalid').forEach(el=>el.classList.remove('is-invalid'));
                    const fd = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': fd.get('_token'), 'Accept':'application/json' },
                        body: fd
                    })
                    .then(async res => {
                        if(res.ok){
                            // Éxito: cerrar modal y mostrar toast
                            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                            showToast('Usuario creado correctamente.', 'success');
                            actualizarTablaUsuarios();
        // Función para actualizar la tabla de usuarios vía AJAX
        function actualizarTablaUsuarios() {
            fetch('/usuarios-lista', {headers: {'Accept': 'application/json'}})
                .then(async res => {
                    if (!res.ok) throw new Error('Error al obtener usuarios');
                    const data = await res.json();
                    if (!Array.isArray(data)) throw new Error('Formato inesperado');
                    const tbody = document.querySelector('table tbody');
                    if (!tbody) return;
                    tbody.innerHTML = '';
                    data.forEach(user => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${user.id}</td>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td><span class="badge ${user.role === 'admin' ? 'bg-dark' : 'bg-secondary'}">${user.role}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                    data-id="${user.id}"
                                    data-name="${user.name}"
                                    data-email="${user.email}"
                                    data-role="${user.role}">
                                    <i class="fa fa-edit"></i>
                                </button>
                                ${(window.authUserId !== user.id ? `<button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-id="${user.id}" data-name="${user.name}"><i class="fa fa-trash"></i></button>` : '')}
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                })
                .catch(err => {
                    showToast('Error actualizando usuarios', 'error');
                });
        }
                        }else if(res.status===422){
                            // Errores de validación
                            const data = await res.json();
                            let firstError = null;
                            Object.entries(data.errors).forEach(([field,msg])=>{
                                const input = form.querySelector(`[name="${field}"]`);
                                if(input){
                                    input.classList.add('is-invalid');
                                    if(!firstError) firstError = input;
                                    let feedback = input.parentElement.querySelector('.invalid-feedback');
                                    if(feedback){ feedback.textContent = msg[0]; feedback.style.display='block'; }
                                }
                            });
                            if(firstError) firstError.focus();
                            alertBox.innerHTML = Object.values(data.errors).map(e=>e[0]).join('<br>');
                            alertBox.classList.remove('d-none');
                        }else{
                            alertBox.textContent = 'Error inesperado. Intenta nuevamente.';
                            alertBox.classList.remove('d-none');
                        }
                    })
                    .catch(()=>{
                        alertBox.textContent = 'Error de red. Intenta nuevamente.';
                        alertBox.classList.remove('d-none');
                    });
                });
            }
        });
        </script>

        <!-- Modal Editar Usuario -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Editar Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editUserForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <input type="hidden" id="editUserId">
                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="editName" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="editEmail" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select @error('role') is-invalid @enderror" name="role" id="editRole" required>
                                    <option value="admin">admin</option>
                                    <option value="operador">operador</option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <hr>
                            <div class="mb-2 text-muted small">Seguridad</div>
                            <div class="mb-2">
                                <label class="form-label">Nueva contraseña (opcional)</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="new-password" placeholder="Dejar en blanco para no cambiar">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Mínimo 8 caracteres, incluir letras y números.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmar nueva contraseña</label>
                                <input type="password" class="form-control" name="password_confirmation" autocomplete="new-password" placeholder="Repite la contraseña si vas a cambiarla">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">¿Color favorito?</label>
                                <input type="text" class="form-control @error('color_favorito') is-invalid @enderror" name="color_favorito" value="{{ old('color_favorito') }}" placeholder="Actualizar respuesta (opcional)">
                                @error('color_favorito')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">¿Animal favorito?</label>
                                <input type="text" class="form-control @error('animal_favorito') is-invalid @enderror" name="animal_favorito" value="{{ old('animal_favorito') }}" placeholder="Actualizar respuesta (opcional)">
                                @error('animal_favorito')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div id="editAlert" class="d-none alert alert-warning small py-2"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Eliminar Usuario -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirmar eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="deleteUserForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">
                            <p class="mb-1">Esta acción no se puede deshacer.</p>
                            <p>¿Eliminar al usuario <strong id="deleteUserName"></strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
    const editModal = document.getElementById('editUserModal');
        let lastEditTrigger = null;
        editModal?.addEventListener('show.bs.modal', event => {
            // Guardar el botón que abrió el modal para devolverle el foco al cerrar
            lastEditTrigger = event.relatedTarget || lastEditTrigger;
            const button = event.relatedTarget;
            const form = document.getElementById('editUserForm');
            if (button) {
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const email = button.getAttribute('data-email');
                const role = button.getAttribute('data-role');
                form.setAttribute('action', `/usuarios/${id}`);

                // Prefill con datos del botón, excepto cuando reabrimos por error del mismo usuario
                const isSameFailedUser = (window.__editFailed && String(window.__editUserId) === String(id));
                if (!isSameFailedUser) {
                    document.getElementById('editName').value = name ?? '';
                    document.getElementById('editEmail').value = email ?? '';
                    document.getElementById('editRole').value = role ?? 'operador';
                    // Limpiar campos opcionales
                    const pw = document.querySelector('input[name="password"]'); if (pw) pw.value = '';
                    const col = document.querySelector('input[name="color_favorito"]'); if (col) col.value = '';
                    const ani = document.querySelector('input[name="animal_favorito"]'); if (ani) ani.value = '';
                }
            } else {
                // Apertura programática tras error de validación
                if (window.__editFailed && window.__editUserId) {
                    form.setAttribute('action', `/usuarios/${window.__editUserId}`);
                }
            }
        });
        // Mover el foco inmediatamente en hide (antes de que Bootstrap marque aria-hidden)
        editModal?.addEventListener('hide.bs.modal', () => {
            const active = document.activeElement;
            if (active && editModal.contains(active)) {
                try { active.blur(); } catch (e) {}
            }
            if (lastEditTrigger && document.body.contains(lastEditTrigger)) {
                try { lastEditTrigger.focus(); } catch (e) {}
            } else {
                try { document.body.focus(); } catch (e) {}
            }
        });
        editModal?.addEventListener('hidden.bs.modal', () => {
            // Al ocultar el modal, devolver foco al trigger si existe para evitar foco dentro de aria-hidden
            if (lastEditTrigger && document.body.contains(lastEditTrigger)) {
                try { lastEditTrigger.focus(); } catch (e) {}
            } else {
                // Fallback
                try { document.body.focus(); } catch (e) {}
            }
        });
        // Evitar foco en elementos con data-bs-dismiss justo antes de ocultar (capturando)
        if (editModal) {
            // Botones de cierre: blur en mousedown y también en keydown (Enter/Espacio)
            editModal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
                btn.addEventListener('mousedown', (ev) => { try { ev.target.blur(); } catch (e) {} }, true);
                btn.addEventListener('keydown', (ev) => {
                    if (ev.key === 'Enter' || ev.key === ' ') { try { ev.target.blur(); } catch (e) {} }
                }, true);
            });
            // Cuando se presiona Escape con el modal abierto, blur activo antes del hide
            editModal.addEventListener('keydown', (ev) => {
                if (ev.key === 'Escape') {
                    const active = document.activeElement;
                    if (active && editModal.contains(active)) { try { active.blur(); } catch (e) {} }
                }
            }, true);
            // Click en backdrop: también blurear actual
            editModal.addEventListener('mousedown', (ev) => {
                if (ev.target === editModal) {
                    const active = document.activeElement;
                    if (active && editModal.contains(active)) { try { active.blur(); } catch (e) {} }
                }
            }, true);
        }

        // Si hubo error de validación en edición, reabrir el modal con el usuario objetivo
        @if(session('edit_failed'))
        (function(){
            const buttons = document.querySelectorAll('button[data-bs-target="#editUserModal"]');
            const targetId = @json(session('edit_user_id'));
            const oldRole = @json(old('role'));
            let triggerBtn = null;
            buttons.forEach(btn => { if (btn.getAttribute('data-id') == targetId) triggerBtn = btn; });
            const modalEl = document.getElementById('editUserModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                const form = document.getElementById('editUserForm');
                if (form) form.setAttribute('action', `/usuarios/${targetId}`);
                // Asegurar que el select de rol refleje old('role') si existe
                const roleSelect = document.getElementById('editRole');
                if (roleSelect && oldRole) roleSelect.value = oldRole;
                // Registrar trigger para devolver foco cuando cierre
                lastEditTrigger = triggerBtn;
                modal.show();
            }
        })();
        @endif

        // Si hubo error de validación al crear, reabrir el modal de creación
        @if(session('create_failed'))
        (function(){
            const modalEl = document.getElementById('createUserModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
                // Enfocar el primer campo con error
                setTimeout(() => {
                    const errorField = modalEl.querySelector('.is-invalid');
                    if (errorField) errorField.focus();
                }, 350);
                // Bloquear cierre del modal si hay errores
                let preventClose = true;
                modalEl.addEventListener('hide.bs.modal', function(ev){
                    if (preventClose) ev.preventDefault();
                });
                // Interceptar submit para permitir cierre solo si no hay errores
                const form = modalEl.querySelector('form');
                if (form) {
                    form.addEventListener('submit', function(){
                        preventClose = false;
                    }, { once: true });
                }
            }
        })();
        @endif

        const deleteModal = document.getElementById('deleteUserModal');
        let lastDeleteTrigger = null;
        deleteModal?.addEventListener('show.bs.modal', event => {
            lastDeleteTrigger = event.relatedTarget || lastDeleteTrigger;
        });
        // Gestión de foco para delete modal
        deleteModal?.addEventListener('hide.bs.modal', () => {
            const active = document.activeElement;
            if (active && deleteModal.contains(active)) {
                try { active.blur(); } catch (e) {}
            }
            if (lastDeleteTrigger && document.body.contains(lastDeleteTrigger)) {
                try { lastDeleteTrigger.focus(); } catch (e) {}
            } else {
                try { document.body.focus(); } catch (e) {}
            }
        });
        deleteModal?.addEventListener('hidden.bs.modal', () => {
            if (lastDeleteTrigger && document.body.contains(lastDeleteTrigger)) {
                try { lastDeleteTrigger.focus(); } catch (e) {}
            } else {
                try { document.body.focus(); } catch (e) {}
            }
        });
        if (deleteModal) {
            deleteModal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
                btn.addEventListener('mousedown', (ev) => { try { ev.target.blur(); } catch (e) {} }, true);
                btn.addEventListener('keydown', (ev) => {
                    if (ev.key === 'Enter' || ev.key === ' ') { try { ev.target.blur(); } catch (e) {} }
                }, true);
            });
            deleteModal.addEventListener('keydown', (ev) => {
                if (ev.key === 'Escape') {
                    const active = document.activeElement;
                    if (active && deleteModal.contains(active)) { try { active.blur(); } catch (e) {} }
                }
            }, true);
            deleteModal.addEventListener('mousedown', (ev) => {
                if (ev.target === deleteModal) {
                    const active = document.activeElement;
                    if (active && deleteModal.contains(active)) { try { active.blur(); } catch (e) {} }
                }
            }, true);
        }
        deleteModal?.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                document.getElementById('deleteUserName').innerText = name;
                const form = document.getElementById('deleteUserForm');
                form.setAttribute('action', `/usuarios/${id}`);
        });
        </script>
</div>
@endsection
