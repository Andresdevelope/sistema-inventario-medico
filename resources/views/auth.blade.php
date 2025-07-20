@extends('layouts.app')

@section('content')
<div id="auth-container" class="auth-container">
    <div class="auth-panel" id="login-panel">
        <h2>Iniciar Sesión</h2>
        <form id="login-form">
            @csrf
            <input type="text" name="username" placeholder="Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit" id="login-btn">Entrar</button>
        </form>
        <div id="login-blocked-message" style="display:none; color:red; margin-top:10px;"></div>
        <p>¿No tienes cuenta? <a href="#" onclick="showRegister()">Regístrate</a></p>
        <p><a href="/recover">¿Olvidaste tu contraseña?</a></p>
    </div>
    <div class="auth-panel" id="register-panel" style="display:none;">
        <h2>Registro</h2>
        <form id="register-form">
            <input type="text" name="username" placeholder="Usuario" required>
            <input type="email" name="email" placeholder="Correo" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="button" id="continue-btn">Continuar</button>
        </form>
        <p>¿Ya tienes cuenta? <a href="#" onclick="showLogin()">Inicia sesión</a></p>
    </div>
</div>

<!-- Modal de preguntas de seguridad -->
<div id="security-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Preguntas de Seguridad</h3>
        <form id="security-form">
            <input type="text" name="color" placeholder="¿Color favorito?" required>
            <input type="text" name="animal" placeholder="¿Animal favorito?" required>
            <button type="submit" id="complete-register-btn">Completar Registro</button>
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
function showRegister() {
    document.getElementById('login-panel').style.display = 'none';
    document.getElementById('register-panel').style.display = 'block';
}
function showLogin() {
    document.getElementById('register-panel').style.display = 'none';
    document.getElementById('login-panel').style.display = 'block';
}
function showSecurityModal() {
    document.getElementById('security-modal').style.display = 'flex';
}

// Guardar datos del registro temporalmente
let tempRegisterData = {};

document.getElementById('continue-btn').onclick = function(e) {
    e.preventDefault();
    const form = document.getElementById('register-form');
    tempRegisterData.username = form.username.value;
    tempRegisterData.email = form.email.value;
    tempRegisterData.password = form.password.value;
    showSecurityModal();
};

document.getElementById('security-form').onsubmit = function(e) {
    e.preventDefault();
    const color = this.color.value;
    const animal = this.animal.value;
    // Enviar datos completos al backend
    fetch("/register", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('input[name="_token"]')?.value || ''
        },
        body: JSON.stringify({
            username: tempRegisterData.username,
            email: tempRegisterData.email,
            password: tempRegisterData.password,
            color: color,
            animal: animal
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            alert('¡Registro completado!');
            document.getElementById('security-modal').style.display = 'none';
            showLogin();
        }else{
            alert('Error en el registro');
        }
    });
};

    document.getElementById('login-form').onsubmit = function(e) {
        e.preventDefault();
        const username = this.username.value;
        const password = this.password.value;
        const btn = document.getElementById('login-btn');
        btn.disabled = true;
        fetch("/login", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('input[name="_token"]')?.value || ''
            },
            body: JSON.stringify({
                username: username,
                password: password
            })
        })
        .then(async res => {
            let data;
            try {
                data = await res.json();
            } catch {
                data = {};
            }
            btn.disabled = false;
            if(data.success && data.redirect){
                window.location.href = data.redirect;
            }else if(res.status === 403 || data.message?.includes('bloqueado')){
                mostrarBloqueo(data.message);
            }else{
                alert(data.message || 'Credenciales incorrectas');
            }
        });
    };

    function mostrarBloqueo(msg){
        const div = document.getElementById('login-blocked-message');
        let segundos = parseInt(msg.match(/\d+/));
        div.innerHTML = `Usuario bloqueado por intentos fallidos. Espera <span id='countdown'>${segundos}</span> segundos.`;
        div.style.display = 'block';
        document.getElementById('login-btn').disabled = true;
        let interval = setInterval(() => {
            segundos--;
            document.getElementById('countdown').innerText = segundos;
            if(segundos <= 0){
                clearInterval(interval);
                div.style.display = 'none';
                document.getElementById('login-btn').disabled = false;
            }
        }, 1000);
    }
</script>
@endpush
