@extends('layouts.app')

@section('content')
<div id="recover-container" class="auth-container">
    <div class="auth-panel">
        <h2>Recuperar Contraseña</h2>
        <form id="recover-email-form">
            <input type="email" name="email" placeholder="Correo registrado" required>
            <button type="submit">Continuar</button>
        </form>
    </div>
</div>

<!-- Modal preguntas de seguridad -->
<div id="security-recover-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Verificación de Seguridad</h3>
        <form id="security-recover-form">
            <input type="text" name="color" placeholder="¿Color favorito?" required>
            <input type="text" name="animal" placeholder="¿Animal favorito?" required>
            <button type="submit">Verificar</button>
        </form>
    </div>
</div>

<!-- Modal cambio de contraseña -->
<div id="change-password-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Cambiar Contraseña</h3>
        <form id="change-password-form">
            <input type="password" name="new_password" placeholder="Nueva contraseña" required>
            <input type="password" name="confirm_password" placeholder="Confirmar contraseña" required>
            <button type="submit">Cambiar contraseña</button>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background: linear-gradient(135deg, #6dd5ed, #2193b0);
    transition: background 0.5s;
}
.auth-panel {
    background: #fff;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    min-width: 300px;
    transition: box-shadow 0.3s;
}
.auth-panel h2 {
    margin-bottom: 1rem;
}
.auth-panel input {
    width: 100%;
    margin-bottom: 1rem;
    padding: 0.5rem;
    border-radius: 5px;
    border: 1px solid #ccc;
}
.auth-panel button {
    width: 100%;
    padding: 0.7rem;
    background: #2193b0;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s;
}
.auth-panel button:hover {
    background: #6dd5ed;
}
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
</style>
@endpush

@push('scripts')
<script>
let recoverEmail = '';
let recoverUserId = null;
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

document.getElementById('recover-email-form').onsubmit = function(e) {
    e.preventDefault();
    recoverEmail = this.email.value;
    fetch('/recover/check-email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ email: recoverEmail })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            recoverUserId = data.user_id;
            document.getElementById('security-recover-modal').style.display = 'flex';
        }else{
            alert('Correo no encontrado');
        }
    });
};

document.getElementById('security-recover-form').onsubmit = function(e) {
    e.preventDefault();
    const color = this.color.value;
    const animal = this.animal.value;
    fetch('/recover/check-security', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            user_id: recoverUserId,
            color: color,
            animal: animal
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            document.getElementById('security-recover-modal').style.display = 'none';
            document.getElementById('change-password-modal').style.display = 'flex';
        }else{
            alert('Respuestas incorrectas');
        }
    });
};

document.getElementById('change-password-form').onsubmit = function(e) {
    e.preventDefault();
    const newPassword = this.new_password.value;
    const confirmPassword = this.confirm_password.value;
    if(newPassword !== confirmPassword){
        alert('Las contraseñas no coinciden');
        return;
    }
    fetch('/recover/change-password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            user_id: recoverUserId,
            password: newPassword
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            alert('Contraseña cambiada correctamente');
            document.getElementById('change-password-modal').style.display = 'none';
            window.location.href = '/';
        }else{
            alert('Error al cambiar la contraseña');
        }
    });
};
</script>
@endpush
