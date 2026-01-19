@extends('layouts.app')

@push('styles')
<style>
/* Montserrat local por Vite/@fontsource */
@import '@fontsource/montserrat/400.css';
@import '@fontsource/montserrat/800.css';

/* Paleta de colores encapsulada para la página de autenticación */
.auth-page { /* Paleta clara + naranja, encapsulada */
  --bg: #ffffff;
  --panel: #ffffff;
  --input: #eef2f5;
  --text: #222831;
  --muted: #6c757d;
  --accent: #ff8c00; /* Naranja principal */
  --accentH: #e67e00; /* Naranja hover */
}

.auth-page, .auth-page *{ box-sizing:border-box; font-family:'Montserrat',sans-serif; }

/* Header profesional */
.auth-header { /* Header con espacio para logo */
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  padding: 25px 50px;
  background-color: transparent;
  z-index: 10;
}
.auth-header .brand { display:flex; align-items:center; gap:12px; }
.auth-header .logo-placeholder { width:45px; height:45px; border-radius:8px; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.08); border:1px solid rgba(0,0,0,.06); }
.auth-header h1 {
  font-size: 22px;
  color: var(--text);
  margin: 0;
  font-weight: 600;
}

.auth-page{
  min-height:100vh;
  min-height:100svh;
  display:flex; align-items:center; justify-content:center;
  padding:24px;
  position:relative; overflow:hidden;
  /* Fondo claro con acentos azul muy sutiles */
  background:
    radial-gradient(900px 600px at 5% -10%, rgba(142,202,230,0.14) 0%, transparent 50%),
    radial-gradient(700px 500px at 105% 10%, rgba(33,158,188,0.10) 0%, transparent 50%),
    linear-gradient(180deg, #ffffff 0%, #f7fbff 55%, #eef7fd 100%);
}
.auth-page::before,
.auth-page::after{
  content:""; position:absolute; inset:-20%; z-index:0;
  background:
    radial-gradient(circle at 30% 20%, rgba(142,202,230,0.12) 0%, transparent 40%),
    radial-gradient(circle at 80% 60%, rgba(33,158,188,0.12) 0%, transparent 45%);
  filter: blur(80px);
}
p, span, a, input, button { font-weight:400; }
h1, h2 { font-weight:800; color:var(--text); }
h1{ margin:0; }
h2{ text-align:center; }
p{ font-size:14px; font-weight:100; line-height:20px; letter-spacing:0.5px; margin:20px 0 30px; color:var(--muted); }
span{ font-size:12px; color:var(--muted); }
a{ color:var(--accent); font-size:14px; text-decoration:none; margin:15px 0; transition:color .2s; }
a:hover{ color:var(--accentH); }
button{ border-radius:20px; border:1px solid var(--accent); background:var(--accent); color:var(--panel); font-size:12px; font-weight:bold; padding:12px 45px; letter-spacing:1px; text-transform:uppercase; transition:transform 80ms ease-in, background .2s, color .2s; cursor:pointer; }
button:active{ transform:scale(0.95); }
button:focus{ outline:none; }
button.ghost{ background:transparent; border-color:var(--panel); color:var(--panel); }
form{
  background:var(--panel);
  display:flex;
  flex-direction:column;
  align-items:stretch;
  justify-content:flex-start;
  padding:24px 36px 20px;
  height:100%;
  text-align:center;
  border-radius:10px;
}
input{ background:var(--input); border:1px solid rgba(0,0,0,.06); color:var(--text); padding:12px 15px; margin:8px 0; width:100%; border-radius:8px; }
input::placeholder{ color:var(--muted); }
input:focus{ outline:2px solid var(--accentH); box-shadow:0 0 0 3px rgba(230, 126, 0, 0.2); }
.input-with-eye{ position:relative; width:100%; max-width:360px; margin:0 auto 10px auto; }
.input-with-eye input{ padding-right:40px; }
.input-with-eye .toggle-pwd{ position:absolute; top:50%; right:12px; transform:translateY(-50%); cursor:pointer; }
.alert-box{ width:100%; margin:8px 0 0; padding:10px 12px; border-radius:8px; background:rgba(220,53,69,.08); border:1px solid rgba(220,53,69,.35); color:#dc3545; text-align:left; font-size:13px; display:none; }
.alert-box.info{ background:rgba(33,158,188,.08); border-color:rgba(33,158,188,.35); color:#219ebc; }
.alert-box.success{ background:rgba(40,167,69,.08); border-color:rgba(40,167,69,.35); color:#28a745; }
.container{ background:var(--panel); border-radius:16px; box-shadow:0 18px 40px rgba(0,0,0,.06); position:relative; z-index:1; overflow:hidden; width:768px; max-width:100%; min-height:650px; }
.form-container{ position:absolute; top:0; height:100%; width:50%; transition:all .3s ease-in-out; overflow:hidden; }
.sign-in-container{ left:0; z-index:2; }
.container.right-panel-active .sign-in-container{ transform:translateX(100%); }
.sign-up-container{ left:0; opacity:0; z-index:1; }
.container.right-panel-active .sign-up-container{ transform:translateX(100%); opacity:1; z-index:5; animation:show .3s; }
@keyframes show{ 0%,49.99%{opacity:0;z-index:1;} 50%,100%{opacity:1;z-index:5;} }
.overlay-container{ position:absolute; top:0; left:50%; width:50%; height:100%; overflow:hidden; transition:transform .3s ease-in-out; z-index:100; }
.container.right-panel-active .overlay-container{ transform:translateX(-100%); }
.overlay{ background:linear-gradient(135deg, var(--accentH) 0%, var(--accent) 60%, #ff9f1c 100%); color:var(--panel); position:relative; left:-100%; height:100%; width:200%; transform:translateX(0); transition:transform .3s ease-in-out; }
.container.right-panel-active .overlay{ transform:translateX(50%); }
.overlay-panel{ position:absolute; display:flex; align-items:center; justify-content:center; flex-direction:column; padding:0 40px; text-align:center; top:0; height:100%; width:50%; transform:translateX(0); transition:transform .3s ease-in-out; }
.overlay-panel p, .overlay-panel h1 { color: var(--panel); }
.overlay-left{ transform:translateX(-20%); }
.container.right-panel-active .overlay-left{ transform:translateX(0); }
.overlay-right{ right:0; transform:translateX(0); }
.container.right-panel-active .overlay-right{ transform:translateX(20%); }
@media (max-width: 768px){
  .container{ min-height:560px; }
  .form-container{ width:100%; }
}
/* Modal de éxito (registro) - diseño profesional y responsive */
.success-modal-overlay{ position:fixed; inset:0; background:rgba(0,0,0,.55); display:flex; align-items:center; justify-content:center; z-index:10001; padding:16px; }
.success-modal-card{ background:var(--panel); border-radius:16px; box-shadow:0 18px 40px rgba(0,0,0,.06); width:min(520px,92vw); max-width:92vw; padding:28px 24px; text-align:center; animation:modalIn .28s ease-out; }
@keyframes modalIn{ from{ transform:translateY(12px) scale(.98); opacity:0; } to{ transform:none; opacity:1; } }
.success-modal-icon{ width:64px; height:64px; margin:0 auto 12px; display:grid; place-items:center; border-radius:50%; background:linear-gradient(135deg, var(--accentH) 0%, var(--accent) 70%, #ff9f1c 100%); color:#fff; box-shadow:0 10px 24px rgba(255,140,0,.25); }
.success-modal-title{ font-size:clamp(20px,2.4vw,24px); font-weight:800; color:var(--text); margin:0 0 8px; }
.success-modal-text{ color:var(--muted); font-size:clamp(14px,2.1vw,16px); margin-bottom:10px; }
.success-modal-subtext{ color:var(--accent); font-weight:600; }
</style>
@endpush

@section('content')
<div class="auth-page">
  <header class="auth-header">
    <div class="brand">
      <img src="{{ asset('logouptag.png') }}" alt="Logo UPTAG" style="width:45px;height:45px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid rgba(0,0,0,.06);object-fit:cover;background:#fff;" />
      <h1>Sistema de Inventario</h1>
    </div>
  </header>
  <div class="container" id="container">
    <div class="form-container sign-up-container">
      <form method="POST" action="{{ route('register') }}" id="register-form">
        @csrf
        <h1>Crear Cuenta</h1>
        <div id="register-alert" class="alert-box" role="alert"></div>
        <input type="text" name="username" placeholder="Usuario" required />
        <input type="email" name="email" placeholder="Correo" required />
        <div class="input-with-eye">
          <input type="password" name="password" id="register_password" placeholder="Contraseña (mínimo 16 caracteres)" required minlength="16" pattern="(?=.*[A-Za-z])(?=.*\d).+" />
          <span class="toggle-pwd" data-target="register_password">
            <svg width="24" height="24" fill="none" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </span>
        </div>
        <input type="text" name="color" placeholder="¿Color favorito?" required />
        <input type="text" name="animal" placeholder="¿Animal favorito?" required />
        <input type="text" name="padre" placeholder="¿Nombre del padre?" required />
        {{-- reCAPTCHA v2 para registro --}}
        @if(config('services.recaptcha.site_key'))
          <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}" style="margin:8px 0 12px;"></div>
        @endif
        <button type="submit">Registrarse</button>
      </form>
    </div>
    <div class="form-container sign-in-container">
      <form method="POST" action="{{ route('login.post') }}" id="username-login-form">
        @csrf
        <h1>Iniciar Sesión</h1>
        <div id="login-alert" class="alert-box" role="alert"></div>
        <input type="text" name="username" placeholder="Usuario" required />
        <!-- Campo contraseña con ojito -->
        <div class="input-with-eye">
          <input type="password" name="password" id="login_password" placeholder="Contraseña" required />
          <span class="toggle-pwd" data-target="login_password">
            <svg width="24" height="24" fill="none" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </span>
        </div>
        {{-- reCAPTCHA v2 checkbox --}} 
        @if(config('services.recaptcha.site_key'))
          <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}" style="margin:8px 0 12px;"></div>
        @endif
        <a href="{{ url('/recover') }}">¿Olvidaste tu contraseña?</a>
        <button type="submit">Entrar</button>
      </form>
    </div>
    <div class="overlay-container">
      <div class="overlay">
        <div class="overlay-panel overlay-left">
          <h1>¡Bienvenido al SSM UPTAG!</h1>
          <p>Ingresa para gestionar inventario, productos y proveedores del sistema médico</p>
          <button class="ghost" id="signIn">Iniciar Sesión</button>
        </div>
        <div class="overlay-panel overlay-right">
          <h1>Sistema de Servicios Médicos UPTAG</h1>
          <p>Crea tu cuenta para administrar el almacén y servicios de la institución</p>
          <button class="ghost" id="signUp">Registrarse</button>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
{{-- Carga del script de reCAPTCHA v2 --}}
@if(config('services.recaptcha.site_key'))
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
<script>
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const container = document.getElementById('container');
  const signUpButton = document.getElementById('signUp');
  const signInButton = document.getElementById('signIn');
  let recaptchaLoginIndex = null;
  let recaptchaRegisterIndex = null;

  function detectRecaptchaIndexes(){
    if (!window.grecaptcha) return;
    const widgets = document.querySelectorAll('.g-recaptcha');
    widgets.forEach((el, idx) => {
      if (el.closest('#username-login-form')) recaptchaLoginIndex = idx;
      if (el.closest('#register-form')) recaptchaRegisterIndex = idx;
    });
  }
  // Intento inicial de detección; si el script de reCAPTCHA tarda, el usuario
  // al primer submit forzará la creación del widget y luego se detectará.
  setTimeout(detectRecaptchaIndexes, 600);
  // Transición de paneles
  signUpButton?.addEventListener('click', () => {
    container.classList.add('right-panel-active');
  });
  signInButton?.addEventListener('click', () => {
    container.classList.remove('right-panel-active');
  });

  // Interceptar submit de registro para manejar respuesta JSON y redirigir
  const registerForm = document.getElementById('register-form');
  // (Sin medidor en la página de autenticación para mantener diseño compacto)
  const registerAlert = document.getElementById('register-alert');
  if (registerForm) {
    registerForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      if (registerAlert) { registerAlert.style.display = 'none'; registerAlert.textContent = ''; registerAlert.className = 'alert-box'; }
      // Validación previa de contraseña (UX)
      const pwdInput = registerForm.querySelector('input[name="password"]');
      const pwdVal = pwdInput?.value || '';
      const strongRegex = /(?=.*[A-Za-z])(?=.*\d).+/;
      if (pwdVal.length < 16 || !strongRegex.test(pwdVal)){
        registerAlert.textContent = 'La contraseña debe tener al menos 16 caracteres e incluir letras y números.';
        registerAlert.style.display = 'block';
        pwdInput?.focus();
        return;
      }
      // Validación reCAPTCHA para registro (si está activo)
      try {
        if (window.grecaptcha && typeof grecaptcha.getResponse === 'function'){
          // Asegurar que tengamos índices mapeados
          if (recaptchaRegisterIndex === null) detectRecaptchaIndexes();
          let token = null;
          if (typeof recaptchaRegisterIndex === 'number') {
            token = grecaptcha.getResponse(recaptchaRegisterIndex);
          } else {
            token = grecaptcha.getResponse();
          }
          if (!token){
            registerAlert.textContent = 'Por favor completa el reCAPTCHA.';
            registerAlert.style.display = 'block';
            return;
          }
        }
      } catch(_){}
      const btn = registerForm.querySelector('button[type="submit"]');
      const originalText = btn?.textContent;
      if (btn) { btn.disabled = true; btn.textContent = 'Registrando…'; }
      try {
        const formData = new FormData(registerForm);
        const res = await fetch(registerForm.action, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept':'application/json' },
          body: formData
        });
        const data = await res.json();
        if (data?.success) {
          // Modal de bienvenida con paleta naranja consistente
          let successMsg = document.getElementById('register-success-msg');
          if (!successMsg) {
            successMsg = document.createElement('div');
            successMsg.id = 'register-success-msg';
            successMsg.innerHTML = `<div class="success-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="register-success-title">
              <div class="success-modal-card">
                <div class="success-modal-icon">
                  <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M20 6L9 17L4 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </div>
                <h2 id="register-success-title" class="success-modal-title">¡Bienvenido al sistema!</h2>
                <div class="success-modal-text">Tu cuenta fue creada correctamente.</div>
                <div class="success-modal-subtext">Redirigiendo al inicio de sesión…</div>
              </div>
            </div>`;
            document.body.appendChild(successMsg);
          } else {
            successMsg.style.display = 'flex';
          }
          // Resetear el reCAPTCHA de registro para evitar tokens expirados si el usuario regresa
          try {
            if (window.grecaptcha && typeof grecaptcha.reset === 'function'){
              if (typeof recaptchaRegisterIndex === 'number') grecaptcha.reset(recaptchaRegisterIndex); else grecaptcha.reset();
            }
          } catch(_){}
          setTimeout(() => { window.location.href = '{{ url('/login') }}'; }, 1800);
        } else {
          if (registerAlert) {
            registerAlert.textContent = data?.message || 'Error al registrar usuario.';
            registerAlert.style.display = 'block';
          }
        }
      } catch (err) {
        if (registerAlert) {
          registerAlert.textContent = 'Error de red: intenta nuevamente.';
          registerAlert.style.display = 'block';
        }
      } finally {
        if (btn) { btn.disabled = false; btn.textContent = originalText; }
        // Resetear reCAPTCHA tras intento (éxito o fallo) para asegurar nuevo desafío
        try {
          if (window.grecaptcha && typeof grecaptcha.reset === 'function'){
            if (typeof recaptchaRegisterIndex === 'number') grecaptcha.reset(recaptchaRegisterIndex); else grecaptcha.reset();
          }
        } catch(_){}
      }
    });
  }
  const loginForm = document.getElementById('username-login-form');
  const loginAlert = document.getElementById('login-alert');
  let lockInterval = null;
  const formatMMSS = (total) => {
    const t = Math.max(0, parseInt(total || 0, 10));
    const m = Math.floor(t / 60);
    const s = t % 60;
    const mm = m.toString().padStart(1, '0');
    const ss = s.toString().padStart(2, '0');
    return `${mm}:${ss}`;
  }
  loginForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    // Enforce reCAPTCHA resuelto cuando esté activo
    try {
      if (window.grecaptcha && typeof grecaptcha.getResponse === 'function'){
        if (recaptchaLoginIndex === null) detectRecaptchaIndexes();
        let token = null;
        if (typeof recaptchaLoginIndex === 'number') {
          token = grecaptcha.getResponse(recaptchaLoginIndex);
        } else {
          token = grecaptcha.getResponse();
        }
        if (!token){
          if (loginAlert){
            loginAlert.className = 'alert-box';
            loginAlert.style.display = 'block';
            loginAlert.textContent = 'Por favor completa el reCAPTCHA.';
          }
          return;
        }
      }
    } catch(_){}
    // Ocultar aviso anterior y limpiar contador
    if (lockInterval) { clearInterval(lockInterval); lockInterval = null; }
    if (loginAlert){
      loginAlert.style.display = 'none';
      loginAlert.textContent = '';
      loginAlert.className = 'alert-box';
    }
    const btn = loginForm.querySelector('button[type="submit"]');
    const originalText = btn?.textContent;
    if (btn) { btn.disabled = true; btn.textContent = 'Entrando…'; }
    try {
      const formData = new FormData(loginForm);
      const res = await fetch(loginForm.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept':'application/json' },
        body: formData
      });
      const data = await res.json();
      // Limpiar contador previo
      if (lockInterval) { clearInterval(lockInterval); lockInterval = null; }
      // Éxito: redirigir
      if (data?.success && data?.redirect){
        window.location.href = data.redirect;
      } else {
        // Mostrar inline
        if (res.status === 403 && typeof data?.message === 'string'){
          // Intentos agotados: el backend envía segundos restantes en el texto; extraerlos si es posible
          const secs = (data.message.match(/(\d+)/) || [])[1];
          let remaining = secs ? parseInt(secs, 10) : null;
          loginAlert.className = 'alert-box';
          loginAlert.style.display = 'block';
          const renderLocked = () => {
            if (remaining !== null && remaining >= 0){
              loginAlert.textContent = `Usuario bloqueado. Intenta en ${formatMMSS(remaining)}.`;
            } else {
              loginAlert.textContent = 'Usuario bloqueado por intentos fallidos. Solo un administrador puede desbloquear tu cuenta para acceder al sistema.';
            }
          }
          renderLocked();
          if (remaining !== null){
            lockInterval = setInterval(() => {
              remaining -= 1;
              if (remaining <= 0){
                clearInterval(lockInterval);
                lockInterval = null;
                loginAlert.className = 'alert-box info';
                loginAlert.textContent = 'Ya puedes intentar nuevamente.';
              } else {
                renderLocked();
              }
            }, 1000);
          }
        } else if (res.status === 429) {
          // Exceso de intentos: rate limiting del backend
          const retryAfter = parseInt(res.headers.get('Retry-After') || '0', 10);
          loginAlert.className = 'alert-box';
          loginAlert.style.display = 'block';
          if (!isNaN(retryAfter) && retryAfter > 0) {
            let remaining = retryAfter;
            loginAlert.textContent = `Demasiados intentos. Intenta en ${formatMMSS(remaining)}.`;
            if (lockInterval) { clearInterval(lockInterval); }
            lockInterval = setInterval(() => {
              remaining -= 1;
              if (remaining <= 0) {
                clearInterval(lockInterval);
                lockInterval = null;
                loginAlert.className = 'alert-box info';
                loginAlert.textContent = 'Ya puedes intentar nuevamente.';
              } else {
                loginAlert.textContent = `Demasiados intentos. Intenta en ${formatMMSS(remaining)}.`;
              }
            }, 1000);
          } else {
            loginAlert.textContent = data?.message || 'Demasiados intentos. Intenta nuevamente más tarde.';
          }
        } else {
          loginAlert.className = 'alert-box';
          loginAlert.style.display = 'block';
          loginAlert.textContent = data?.message || 'Credenciales incorrectas';
        }
        // Tras cualquier fallo de login, forzar refresh del reCAPTCHA
        try {
          if (window.grecaptcha && typeof grecaptcha.reset === 'function'){
            if (recaptchaLoginIndex === null) detectRecaptchaIndexes();
            if (typeof recaptchaLoginIndex === 'number') grecaptcha.reset(recaptchaLoginIndex); else grecaptcha.reset();
          }
        } catch(_){}
      }
    } catch (err) {
      loginAlert.className = 'alert-box';
      loginAlert.style.display = 'block';
      loginAlert.textContent = 'Error de red: intenta nuevamente.';
      // Reset también ante errores de red
      try {
        if (window.grecaptcha && typeof grecaptcha.reset === 'function'){
          if (recaptchaLoginIndex === null) detectRecaptchaIndexes();
          if (typeof recaptchaLoginIndex === 'number') grecaptcha.reset(recaptchaLoginIndex); else grecaptcha.reset();
        }
      } catch(_){}
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = originalText; }
    }
  });

  // Mostrar/ocultar contraseña en login y registro
  if (window.addEventListener) {
    window.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.toggle-pwd').forEach(function(eye){
        eye.addEventListener('click', function(){
          const targetId = eye.getAttribute('data-target');
          const input = document.getElementById(targetId);
          if (!input) return;
          if (input.type === 'password') {
            input.type = 'text';
            eye.querySelector('svg').style.stroke = '#ff8c00';
          } else {
            input.type = 'password';
            eye.querySelector('svg').style.stroke = '#6c757d';
          }
        });
      });
    });
  }
</script>
@endpush