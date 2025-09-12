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
        <div class="neomorph-content" id="neo-login" style="display:none;">
            <h2 class="neomorph-title">Iniciar Sesión</h2>
            <form id="login-form">
                @csrf
                <input type="text" name="username" placeholder="Usuario" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit" id="login-btn">Entrar</button>
            </form>
            <div id="login-blocked-message" style="display:none; color:red; margin-top:10px;"></div>
            <div id="login-error-message" style="display:none; color:#e74c3c; background:#fff3f3; border-radius:10px; padding:10px 16px; margin-top:10px; font-weight:500; box-shadow:0 2px 8px #e74c3c22;"></div>
            <div class="neomorph-links">
                <a href="#" onclick="showNeo('register')">¿No tienes cuenta? Regístrate</a>
                <a href="/recover">¿Olvidaste tu contraseña?</a>
            </div>
        </div>
        <div class="neomorph-content" id="neo-register" style="display:none;">
            <h2 class="neomorph-title">Registro</h2>
            <form id="register-form">
                <input type="text" name="username" placeholder="Usuario" required>
                <input type="email" name="email" placeholder="Correo" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="button" id="continue-btn">Continuar</button>
            </form>
            <div class="neomorph-links">
                <a href="#" onclick="showNeo('login')">¿Ya tienes cuenta? Inicia sesión</a>
            </div>
                    <!-- El botón de preguntas de seguridad se elimina, el modal aparecerá automáticamente -->
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
        </div>
        <div class="neomorph-content" id="neo-recover" style="display:none;">
            <h2 class="neomorph-title">Recuperar Contraseña</h2>
            <form id="redirect-recover-form">
                <input type="email" name="email" placeholder="Correo" required>
                <button type="submit" id="redirect-recover-btn">Recuperar</button>
            </form>
            <div class="neomorph-links">
                <a href="#" onclick="showNeo('login')">Volver a iniciar sesión</a>
            </div>
            <script>
            document.getElementById('redirect-recover-form')?.addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.email.value.trim();
                if(email){
                    window.location.replace(`/recover?email=${encodeURIComponent(email)}`);
                }
            });
            </script>
        </div>
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
.neomorph-content form {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.neomorph-content.active {
    display: flex;
    animation: scaleInNeo 0.5s;
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
.neomorph-links {
    display: flex;
    flex-direction: column;
    gap: 7px;
    margin-top: 14px;
    text-align: center;
}
.neomorph-links a {
    color: #2193b0;
    text-decoration: underline;
    font-size: 1rem;
    transition: color 0.2s;
}
.neomorph-links a:hover {
    color: #1769aa;
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
function showNeo(panel) {
    const login = document.getElementById('neo-login');
    const register = document.getElementById('neo-register');
    const recover = document.getElementById('neo-recover');
    // Ocultar todos
    login.style.display = 'none';
    register.style.display = 'none';
    recover.style.display = 'none';
    login.classList.remove('active');
    register.classList.remove('active');
    recover.classList.remove('active');
    // Mostrar el seleccionado
    if(panel === 'register') {
        register.style.display = 'flex';
        register.classList.add('active');
    } else if(panel === 'recover') {
        recover.style.display = 'flex';
        recover.classList.add('active');
    } else {
        login.style.display = 'flex';
        login.classList.add('active');
    }
}

window.onload = function() {
    showNeo('login');
    document.querySelectorAll('.neomorph-links a').forEach(function(link) {
        // Solo bloquear enlaces internos, no los que tienen href directo
        if (!link.hasAttribute('href') || link.getAttribute('href') === '#') {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                if (this.textContent.includes('Regístrate')) {
                    showNeo('register');
                } else if (this.textContent.includes('recuper')) {
                    showNeo('recover');
                } else if (this.textContent.includes('Inicia sesión') || this.textContent.includes('Volver')) {
                    showNeo('login');
                }
            });
        }
    });
};


// Mostrar modal de preguntas de seguridad automáticamente tras completar registro
document.getElementById('continue-btn')?.addEventListener('click', function(e) {
    e.preventDefault();
    const form = document.getElementById('register-form');
    const username = form.username.value.trim();
    const email = form.email.value.trim();
    const password = form.password.value.trim();
    if(username && email && password) {
        document.getElementById('security-modal').style.display = 'flex';
    } else {
        alert('Por favor completa todos los campos antes de continuar.');
    }
});

document.getElementById('security-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const color = this.color.value;
    const animal = this.animal.value;
    alert('¡Registro completado!');
    document.getElementById('security-modal').style.display = 'none';
    showNeo('login');
});

document.getElementById('login-form').onsubmit = function(e) {
    e.preventDefault();
    const username = this.username.value;
    const password = this.password.value;
    const btn = document.getElementById('login-btn');
    const errorDiv = document.getElementById('login-error-message');
    btn.disabled = true;
    errorDiv.style.display = 'none';
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
            errorDiv.innerHTML = 'Contraseña incorrecta. Vuelve a intentarlo.';
            errorDiv.style.display = 'block';
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