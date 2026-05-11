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
/* ── Responsive Mobile: Nuevo diseño logo arriba + card formulario abajo ── */
@media (max-width: 850px) {

  /* Ocultar el header de desktop */
  .auth-header { display: none !important; }

  /* Fondo degradado suave */
  .auth-page {
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 40px 20px 32px;
    min-height: 100svh;
    background: linear-gradient(160deg, #fff9f2 0%, #fff 40%, #f0f6ff 100%);
  }
  .auth-page::before, .auth-page::after { display: none; }

  /* ── Bloque logo + título mobile (solo visible en mobile) ── */
  .mobile-brand {
    display: flex !important;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
    text-align: center;
  }
  .mobile-brand img {
    width: 76px;
    height: 76px;
    border-radius: 18px;
    object-fit: cover;
    box-shadow: 0 10px 28px rgba(255,140,0,0.28), 0 2px 8px rgba(0,0,0,0.08);
    border: 2px solid rgba(255,255,255,0.9);
  }
  .mobile-brand h2 {
    font-size: 20px;
    font-weight: 800;
    color: var(--text);
    margin: 0;
    letter-spacing: -0.3px;
  }
  .mobile-brand p {
    font-size: 13px;
    color: var(--muted);
    margin: 0;
    font-weight: 400;
    line-height: 1.4;
  }
  /* Asegurar que h2 del brand quede centrado (override del global h2) */
  .mobile-brand h2 { text-align: center; }

  /* ── Card principal mobile ── */
  .container {
    width: 100% !important;
    max-width: 430px !important;
    min-height: auto !important;
    border-radius: 22px !important;
    box-shadow: 0 16px 48px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06) !important;
    overflow: hidden !important;
    margin: 0 auto;
    background: #fff !important;
    position: relative !important;
  }

  /* Ocultar el panel lateral animado */
  .overlay-container { display: none !important; }

  /* ── Tabs mobile en la parte superior del card ── */
  .mobile-tabs {
    display: flex !important;
    border-radius: 22px 22px 0 0;
    overflow: hidden;
    border-bottom: 2px solid #f0f4f8;
  }
  .mobile-tab-btn {
    flex: 1;
    padding: 15px 10px;
    font-family: 'Montserrat', sans-serif;
    font-size: 13px;
    font-weight: 700;
    color: var(--muted);
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    transition: color .2s, border-color .2s;
    letter-spacing: 0.3px;
    text-transform: uppercase;
  }
  .mobile-tab-btn.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
  }

  /* ── Formularios mobile ── */
  .form-container {
    position: relative !important;
    width: 100% !important;
    height: auto !important;
    left: 0 !important;
    top: auto !important;
    opacity: 1 !important;
    transform: none !important;
    z-index: 1 !important;
    transition: none !important;
  }

  /* Por defecto: login visible, registro oculto */
  .sign-up-container { display: none !important; }
  .sign-in-container { display: block !important; }

  @keyframes fadeInMobile { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

  .container.right-panel-active .sign-in-container { display: none !important; }
  .container.right-panel-active .sign-up-container {
    display: block !important;
    opacity: 1 !important;
    animation: fadeInMobile .25s ease-out forwards;
  }

  /* Formulario interior */
  form {
    border-radius: 0;
    padding: 24px 24px 28px;
    height: auto;
    background: transparent;
    text-align: left;
  }
  form h1 { display: none; } /* Título reemplazado por tabs */

  /* Inputs */
  input {
    background: #f4f6f9;
    border: 1.5px solid transparent;
    border-radius: 10px;
    padding: 13px 14px;
    margin: 0 0 12px;
    font-size: 14px;
    transition: border-color .2s, box-shadow .2s;
  }
  input:focus {
    background: #fff;
    border-color: var(--accentH);
    box-shadow: 0 0 0 3px rgba(230,126,0,0.15);
  }

  .input-with-eye { max-width: 100%; margin-bottom: 12px; }
  .input-with-eye input { margin: 0; }

  /* reCAPTCHA centrado */
  .g-recaptcha {
    display: flex !important;
    justify-content: center !important;
    margin: 4px 0 14px !important;
    transform: scale(0.9);
    transform-origin: center;
  }
  div[style*="z-index: 2000000000"] {
    left: 50% !important;
    transform: translateX(-50%) !important;
  }

  /* Botón submit */
  button[type="submit"] {
    width: 100%;
    border-radius: 12px !important;
    padding: 14px !important;
    font-size: 14px !important;
    letter-spacing: 0.8px;
    background: linear-gradient(135deg, var(--accentH) 0%, var(--accent) 60%, #ff9f1c 100%) !important;
    box-shadow: 0 6px 20px rgba(255,140,0,0.30);
    margin-top: 4px;
  }
  button[type="submit"]:hover { opacity: .92; }

  /* Link olvidé contraseña */
  a[href*="recover"] {
    display: block;
    text-align: right;
    font-size: 12px;
    margin: -4px 0 14px;
  }

  /* Switch mobile */
  .mobile-auth-switch {
    display: block !important;
    text-align: center;
    margin-top: 14px;
    font-size: 13px;
    color: var(--muted);
  }
}

/* El switch mobile se oculta en desktop */
.mobile-auth-switch { display:none; margin-top:.75rem; font-size:13px; color:var(--muted); text-align:center; }
/* mobile-brand y mobile-tabs ocultos en desktop */
.mobile-brand { display: none; }
.mobile-tabs { display: none; }
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
  {{-- Header desktop (oculto en mobile por CSS) --}}
  <header class="auth-header">
    <div class="brand">
      <img src="{{ asset('logouptag.png') }}" alt="Logo UPTAG" style="width:45px;height:45px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid rgba(0,0,0,.06);object-fit:cover;background:#fff;" />
      <h1>Servicios Medico </h1>
    </div>
  </header>

  {{-- Bloque logo + título SOLO MOBILE (oculto en desktop por CSS) --}}
  <div class="mobile-brand">
    <img src="{{ asset('logouptag.png') }}" alt="Logo UPTAG" />
    <h2>Servicios Médicos UPTAG</h2>
    <p>Sistema de gestión de inventario médico</p>
  </div>

  <div class="container" id="container">

    {{-- Tabs SOLO MOBILE (ocultos en desktop por CSS) --}}
    <div class="mobile-tabs" id="mobileTabs">
      <button class="mobile-tab-btn active" id="mobileTabLogin">Iniciar Sesión</button>
      <button class="mobile-tab-btn" id="mobileTabRegister">Crear Cuenta</button>
    </div>
    <div class="form-container sign-up-container">
      <form method="POST" action="{{ route('register') }}" id="register-form">
        @csrf
        <h1>Crear Cuenta</h1>
        <div id="register-alert" class="alert-box" role="alert"></div>
        <input type="text" name="username" placeholder="Usuario" required maxlength="40" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+" title="Nombre de usuario: solo letras y espacios (máx. 40)." />
        <input type="email" name="email" placeholder="Correo" required maxlength="60" title="Correo válido, máximo 60 caracteres." />
        <div class="input-with-eye">
          <input type="password" name="password" id="register_password" placeholder="Contraseña" required minlength="16" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])\S+" title="Contraseña: mínimo 16 caracteres, al menos una mayúscula, una minúscula, un número, un símbolo y sin espacios." />
          <span class="toggle-pwd" data-target="register_password">
            <svg width="24" height="24" fill="none" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </span>
        </div>
        <input type="text" name="color" placeholder="¿Color favorito?" required maxlength="40" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+" title="Color favorito: solo letras y espacios (máx. 40)." />
        <input type="text" name="animal" placeholder="¿Animal favorito?" required maxlength="40" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+" title="Animal favorito: solo letras y espacios (máx. 40)." />
        <input type="text" name="padre" placeholder="¿Nombre del padre?" required maxlength="40" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+" title="Nombre del padre: solo letras y espacios (máx. 40)." />
        {{-- reCAPTCHA v2 para registro (solo si está habilitado) --}}
        @if(config('services.recaptcha.enabled') && config('services.recaptcha.site_key'))
          <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}" style="margin:8px 0 12px;"></div>
        @endif
        <button type="submit">Registrarse</button>
        {{-- Link visible solo en mobile para volver al login --}}
        <div class="mobile-auth-switch">
          ¿Ya tienes cuenta? <a href="#" id="mobileGoSignIn">Inicia sesión</a>
        </div>
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
        {{-- reCAPTCHA v2 checkbox (solo si está habilitado) --}} 
        @if(config('services.recaptcha.enabled') && config('services.recaptcha.site_key'))
          <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}" style="margin:8px 0 12px;"></div>
        @endif
        <a href="{{ url('/recover') }}">¿Olvidaste tu contraseña?</a>
        <button type="submit">Entrar</button>
        {{-- Link visible solo en mobile para cambiar a registro --}}
        <div class="mobile-auth-switch">
          ¿No tienes cuenta? <a href="#" id="mobileGoSignUp">Regístrate aquí</a>
        </div>
      </form>
    </div>
    <div class="overlay-container">
      <div class="overlay">
        <div class="overlay-panel overlay-left">
          <h1>¡Bienvenido al Sistema Servicios medicos UPTAG!</h1>
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
{{-- Carga del script de reCAPTCHA v2 (solo si está habilitado) --}}
@if(config('services.recaptcha.enabled') && config('services.recaptcha.site_key'))
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
<script>
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const recaptchaEnabled = {{ config('services.recaptcha.enabled') && config('services.recaptcha.site_key') ? 'true' : 'false' }};
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
  // Transición de paneles (desktop)
  signUpButton?.addEventListener('click', () => {
    container.classList.add('right-panel-active');
  });
  signInButton?.addEventListener('click', () => {
    container.classList.remove('right-panel-active');
  });
  // Switch mobile (links dentro del form)
  document.getElementById('mobileGoSignUp')?.addEventListener('click', (e) => {
    e.preventDefault();
    container.classList.add('right-panel-active');
    document.getElementById('mobileTabLogin')?.classList.remove('active');
    document.getElementById('mobileTabRegister')?.classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  document.getElementById('mobileGoSignIn')?.addEventListener('click', (e) => {
    e.preventDefault();
    container.classList.remove('right-panel-active');
    document.getElementById('mobileTabLogin')?.classList.add('active');
    document.getElementById('mobileTabRegister')?.classList.remove('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  // Tabs mobile
  document.getElementById('mobileTabLogin')?.addEventListener('click', () => {
    container.classList.remove('right-panel-active');
    document.getElementById('mobileTabLogin').classList.add('active');
    document.getElementById('mobileTabRegister').classList.remove('active');
  });
  document.getElementById('mobileTabRegister')?.addEventListener('click', () => {
    container.classList.add('right-panel-active');
    document.getElementById('mobileTabLogin').classList.remove('active');
    document.getElementById('mobileTabRegister').classList.add('active');
  });

  // Interceptar submit de registro para manejar respuesta JSON y redirigir
  const registerForm = document.getElementById('register-form');
  // (Sin medidor en la página de autenticación para mantener diseño compacto)
  const registerAlert = document.getElementById('register-alert');
  if (registerForm) {
    registerForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      if (registerAlert) { registerAlert.style.display = 'none'; registerAlert.textContent = ''; registerAlert.className = 'alert-box'; }
      const onlyLettersRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/;
      const typoDomains = ['gmai.com', 'gmial.com', 'gmal.com', 'hotnail.com', 'yaho.com'];
      const sanitizeText = (value) => (value || '').trim().replace(/\s+/g, ' ');
      const isSuspiciousText = (value) => {
        const clean = sanitizeText(value);
        const compact = clean.replace(/\s+/g, '');
        if (!clean) return true;
        if (/(.)\1{3,}/u.test(compact)) return true;
        if (!clean.includes(' ') && compact.length > 12) return true;
        return false;
      };
      const isSuspiciousEmail = (value) => {
        const email = (value || '').trim().toLowerCase();
        const parts = email.split('@');
        if (parts.length !== 2) return true;
        const localPart = parts[0] || '';
        const domain = parts[1] || '';
        if (typoDomains.includes(domain)) return true;
        if (!localPart || /^\d+$/.test(localPart)) return true;
        if (/(.)\1{4,}/.test(localPart)) return true;
        if (localPart.length > 18 && !/[._-]/.test(localPart)) return true;
        return false;
      };

      const usernameInput = registerForm.querySelector('input[name="username"]');
      const emailInput = registerForm.querySelector('input[name="email"]');
      const pwdInput = registerForm.querySelector('input[name="password"]');
      const colorInput = registerForm.querySelector('input[name="color"]');
      const animalInput = registerForm.querySelector('input[name="animal"]');
      const padreInput = registerForm.querySelector('input[name="padre"]');

      const usernameVal = sanitizeText(usernameInput?.value || '');
      const emailVal = (emailInput?.value || '').trim().toLowerCase();
      const pwdVal = (pwdInput?.value || '').trim();
      const colorVal = sanitizeText(colorInput?.value || '');
      const animalVal = sanitizeText(animalInput?.value || '');
      const padreVal = sanitizeText(padreInput?.value || '');

      if (usernameInput) usernameInput.value = usernameVal;
      if (emailInput) emailInput.value = emailVal;
      if (pwdInput) pwdInput.value = pwdVal;
      if (colorInput) colorInput.value = colorVal;
      if (animalInput) animalInput.value = animalVal;
      if (padreInput) padreInput.value = padreVal;

      if (usernameVal.length < 3 || usernameVal.length > 40 || !onlyLettersRegex.test(usernameVal) || isSuspiciousText(usernameVal)) {
        registerAlert.textContent = 'Nombre de usuario inválido: solo texto real, sin números, máximo 40 caracteres.';
        registerAlert.style.display = 'block';
        usernameInput?.focus();
        return;
      }

      if (!emailVal || emailVal.length > 60 || isSuspiciousEmail(emailVal)) {
        registerAlert.textContent = 'Correo inválido: verifica formato/dominio y máximo 60 caracteres.';
        registerAlert.style.display = 'block';
        emailInput?.focus();
        return;
      }

      const questionValues = [
        { input: colorInput, value: colorVal, label: 'Color favorito' },
        { input: animalInput, value: animalVal, label: 'Animal favorito' },
        { input: padreInput, value: padreVal, label: 'Nombre del padre' },
      ];
      for (const field of questionValues) {
        if (field.value.length < 2 || field.value.length > 40 || !onlyLettersRegex.test(field.value) || isSuspiciousText(field.value)) {
          registerAlert.textContent = `${field.label} inválido: solo texto real, sin números, máximo 40 caracteres.`;
          registerAlert.style.display = 'block';
          field.input?.focus();
          return;
        }
      }

      // Validación previa de contraseña (UX)
      const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])\S+$/;
      const suspiciousPassword = (value) => {
        if (/^\d+$/.test(value)) return true;
        if (/(.)\1{4,}/u.test(value)) return true;
        if (new Set(value.split('')).size < 4) return true;
        return false;
      };
      if (pwdVal.length < 16 || !strongRegex.test(pwdVal) || suspiciousPassword(pwdVal)) {
        registerAlert.textContent = 'Contraseña inválida: usa mínimo 16 caracteres, con mayúscula, minúscula, número, símbolo y sin patrones repetitivos.';
        registerAlert.style.display = 'block';
        pwdInput?.focus();
        return;
      }
      // Validación reCAPTCHA para registro (si está activo)
      try {
        if (recaptchaEnabled) {
          if (!window.grecaptcha || typeof grecaptcha.getResponse !== 'function') {
            registerAlert.textContent = 'El reCAPTCHA no está disponible. Intenta recargar la página.';
            registerAlert.style.display = 'block';
            return;
          }
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
      } catch(_){
        registerAlert.textContent = 'No se pudo validar el reCAPTCHA. Intenta nuevamente.';
        registerAlert.style.display = 'block';
        return;
      }
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
    let redirecting = false;
    // Enforce reCAPTCHA resuelto cuando esté activo
    try {
      if (recaptchaEnabled) {
        if (!window.grecaptcha || typeof grecaptcha.getResponse !== 'function') {
          if (loginAlert){
            loginAlert.className = 'alert-box';
            loginAlert.style.display = 'block';
            loginAlert.textContent = 'El reCAPTCHA no está disponible. Intenta recargar la página.';
          }
          if (window.hideAuthLoader) window.hideAuthLoader();
          return;
        }
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
          if (window.hideAuthLoader) window.hideAuthLoader();
          return;
        }
      }
    } catch(_){
      if (loginAlert){
        loginAlert.className = 'alert-box';
        loginAlert.style.display = 'block';
        loginAlert.textContent = 'No se pudo validar el reCAPTCHA. Intenta nuevamente.';
      }
      if (window.hideAuthLoader) window.hideAuthLoader();
      return;
    }
    // Ocultar aviso anterior y limpiar contador
    if (lockInterval) { clearInterval(lockInterval); lockInterval = null; }
    if (loginAlert){
      loginAlert.style.display = 'none';
      loginAlert.textContent = '';
      loginAlert.className = 'alert-box';
    }
    if (window.showAuthLoader) window.showAuthLoader();
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
        if (typeof data?.notice === 'string' && data.notice.trim() !== '') {
          try {
            sessionStorage.setItem('post_login_notice', data.notice);
          } catch(_){ }
        }
        redirecting = true;
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
          loginAlert.textContent = data?.message || 'No se pudo validar tu acceso. Verifica tus datos e intenta nuevamente.';
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
      loginAlert.textContent = 'No se pudo validar tu acceso. Revisa tu conexión e intenta nuevamente.';
      // Reset también ante errores de red
      try {
        if (window.grecaptcha && typeof grecaptcha.reset === 'function'){
          if (recaptchaLoginIndex === null) detectRecaptchaIndexes();
          if (typeof recaptchaLoginIndex === 'number') grecaptcha.reset(recaptchaLoginIndex); else grecaptcha.reset();
        }
      } catch(_){}
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = originalText; }
      if (!redirecting && window.hideAuthLoader) window.hideAuthLoader();
    }
  });

  // Mostrar/ocultar contraseña en login y registro
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
</script>
@endpush