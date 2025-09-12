@extends('layouts.app')

@section('content')

<div class="gradient-bg" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:-1;">
    <svg class="wave-bg-svg" viewBox="0 0 1440 320" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:0;pointer-events:none;">
        <path id="wave1" fill="#222e3c" fill-opacity="0.7" d="M0,160L60,165.3C120,171,240,181,360,186.7C480,192,600,192,720,186.7C840,181,960,171,1080,176C1200,181,1320,203,1380,213.3L1440,224L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z">
            <animate attributeName="d" dur="12s" repeatCount="indefinite"
                values="M0,160L60,165.3C120,171,240,181,360,186.7C480,192,600,192,720,186.7C840,181,960,171,1080,176C1200,181,1320,203,1380,213.3L1440,224L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z;
                M0,180L60,185.3C120,191,240,201,360,206.7C480,212,600,212,720,206.7C840,201,960,191,1080,196C1200,201,1320,223,1380,233.3L1440,244L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z;
                M0,160L60,165.3C120,171,240,181,360,186.7C480,192,600,192,720,186.7C840,181,960,171,1080,176C1200,181,1320,203,1380,213.3L1440,224L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z" />
        </path>
        <path id="wave2" fill="#0d1b2a" fill-opacity="0.5" d="M0,224L60,218.7C120,213,240,203,360,186.7C480,171,600,149,720,154.7C840,160,960,192,1080,186.7C1200,181,1320,139,1380,117.3L1440,96L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z">
            <animate attributeName="d" dur="16s" repeatCount="indefinite"
                values="M0,224L60,218.7C120,213,240,203,360,186.7C480,171,600,149,720,154.7C840,160,960,192,1080,186.7C1200,181,1320,139,1380,117.3L1440,96L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z;
                M0,244L60,238.7C120,233,240,223,360,206.7C480,191,600,169,720,174.7C840,180,960,212,1080,206.7C1200,201,1320,159,1380,137.3L1440,116L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z;
                M0,224L60,218.7C120,213,240,203,360,186.7C480,171,600,149,720,154.7C840,160,960,192,1080,186.7C1200,181,1320,139,1380,117.3L1440,96L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z" />
        </path>
    </svg>
    <div class="neomorph-panel">
        <div class="neomorph-logo">
            <img src="/favicon.ico" alt="Logo farmacia" style="width:50px; margin-bottom:10px;">
        </div>
        <div class="neomorph-content active" id="neo-recover">
            <h2 class="neomorph-title">Recuperar Contraseña</h2>
            <form id="recover-email-form">
                <input type="email" name="email" id="recover-email" placeholder="Correo registrado" required>
                <button type="submit">Continuar</button>
            </form>
        </div>
        <!-- Modal preguntas de seguridad -->
        <div id="security-recover-modal" class="modal" style="display:none;">
            <div class="modal-content">
                <h3>Verificación de Seguridad</h3>
                <form id="security-recover-form">
                    <input type="text" name="color" class="modal-input" placeholder="¿Color favorito?" required>
                    <input type="text" name="animal" class="modal-input" placeholder="¿Animal favorito?" required>
                    <button type="submit" class="modal-btn">Verificar</button>
                </form>
            </div>
        </div>
        <!-- Modal cambio de contraseña -->
        <div id="change-password-modal" class="modal" style="display:none;">
            <div class="modal-content">
                <h3>Cambiar Contraseña</h3>
                <form id="change-password-form">
                    <input type="password" name="new_password" class="modal-input" placeholder="Nueva contraseña" required>
                    <input type="password" name="confirm_password" class="modal-input" placeholder="Confirmar contraseña" required>
                    <button type="submit" class="modal-btn">Cambiar contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal preguntas de seguridad -->
<div id="security-recover-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Verificación de Seguridad</h3>
        <form id="security-recover-form">
            <input type="text" name="color" class="modal-input" placeholder="¿Color favorito?" required>
            <input type="text" name="animal" class="modal-input" placeholder="¿Animal favorito?" required>
            <button type="submit" class="modal-btn">Verificar</button>
        </form>
    </div>
</div>

<!-- Modal cambio de contraseña -->
<div id="change-password-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Cambiar Contraseña</h3>
        <form id="change-password-form">
            <input type="password" name="new_password" class="modal-input" placeholder="Nueva contraseña" required>
            <input type="password" name="confirm_password" class="modal-input" placeholder="Confirmar contraseña" required>
            <button type="submit" class="modal-btn">Cambiar contraseña</button>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.gradient-bg {
    min-height: 100vh;
    width: 100vw;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    overflow: hidden;
    background: linear-gradient(120deg, #1a2533 0%, #263a4f 100%);
    z-index: -1;
}
.neomorph-panel {
    background: #1a2533;
    border-radius: 28px;
    box-shadow: 8px 8px 24px #222e3c, -8px -8px 24px #0d1b2a;
    border: 2px solid #263a4f;
    min-width: 320px;
    max-width: 370px;
    margin: 1.5rem auto;
    transition: box-shadow 0.3s, border-color 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2.5rem 2rem 2rem 2rem;
    position: relative;
    z-index: 1;
}
.neomorph-logo {
    margin-bottom: 10px;
    text-align: center;
}
.neomorph-title {
    margin-bottom: 1.2rem;
    color: #2193b0;
    font-weight: 700;
    font-size: 1.6rem;
    letter-spacing: 1px;
    text-align: center;
}
.neomorph-content {
    width: 100%;
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    animation: fadeInNeo 0.5s;
}
.neomorph-content.active {
    display: flex;
    animation: scaleInNeo 0.5s;
}
.neomorph-content form {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
@keyframes scaleInNeo {
    0% { opacity: 0; transform: scale(0.9); }
    100% { opacity: 1; transform: scale(1); }
}
@keyframes fadeInNeo {
    0% { opacity: 0; }
    100% { opacity: 1; }
}
.neomorph-content input {
    width: 100%;
    max-width: 320px;
    margin-bottom: 1rem;
    padding: 0.8rem;
    border-radius: 14px;
    border: 1.5px solid #263a4f;
    font-size: 1.05rem;
    background: #f7fafd;
    box-shadow: 2px 2px 8px #222e3c, -2px -2px 8px #0d1b2a;
}
.neomorph-content button {
    width: 100%;
    max-width: 320px;
    padding: 0.9rem;
    background: linear-gradient(90deg, #263a4f 0%, #1a2533 100%);
    color: #fff;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    font-weight: bold;
    font-size: 1.1rem;
    box-shadow: 2px 2px 8px #222e3c, -2px -2px 8px #0d1b2a;
    transition: background 0.3s;
}
.neomorph-content button:hover {
    background: linear-gradient(90deg, #6dd5ed 0%, #2193b0 100%);
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
    background: #1a2533;
    padding: 2.5rem 2rem 2rem 2rem;
    border-radius: 28px;
    min-width: 320px;
    box-shadow: 8px 8px 24px #222e3c, -8px -8px 24px #0d1b2a;
    border: 2px solid #263a4f;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.modal-content h3 {
    color: #2193b0;
    font-weight: 700;
    font-size: 1.3rem;
    letter-spacing: 1px;
    text-align: center;
    margin-bottom: 1.2rem;
}
.modal-input {
    width: 100%;
    max-width: 320px;
    margin-bottom: 1rem;
    padding: 0.8rem;
    border-radius: 14px;
    border: 1.5px solid #263a4f;
    font-size: 1.05rem;
    background: #f7fafd;
    box-shadow: 2px 2px 8px #222e3c, -2px -2px 8px #0d1b2a;
}
.modal-btn {
    width: 100%;
    max-width: 320px;
    padding: 0.9rem;
    background: linear-gradient(90deg, #263a4f 0%, #1a2533 100%);
    color: #fff;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    font-weight: bold;
    font-size: 1.1rem;
    box-shadow: 2px 2px 8px #222e3c, -2px -2px 8px #0d1b2a;
    transition: background 0.3s;
    margin-bottom: 0.5rem;
}
.modal-btn:hover {
    background: linear-gradient(90deg, #6dd5ed 0%, #2193b0 100%);
}
@media (max-width: 600px) {
    .neomorph-panel {
        min-width: 95vw;
        max-width: 98vw;
        padding: 1.2rem 0.5rem 1rem 0.5rem;
    }
}
</style>
@endpush

@push('scripts')
<script>

let recoverUserId = null;
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Si el correo viene por la URL, lo autocompleta y deshabilita el input
window.onload = function() {
    const params = new URLSearchParams(window.location.search);
    const email = params.get('email');
    if(email) {
        const emailInput = document.getElementById('recover-email');
        emailInput.value = email;
        emailInput.readOnly = true;
    }
};

document.getElementById('recover-email-form').onsubmit = function(e) {
    e.preventDefault();
    const email = document.getElementById('recover-email').value;
    fetch('/recover/check-email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ email })
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