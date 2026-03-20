@extends('layouts.app')

@push('styles')
<style>
/* Paleta clara + naranja, encapsulada solo para recuperación */
.auth-page{ --bg:#ffffff; --panel:#ffffff; --input:#eef2f5; --text:#222831; --muted:#6c757d; --accent:#ff8c00; --accentH:#e67e00; }
html, body{ height:100%; margin:0; }
.auth-page, .auth-page *{ font-family:'Montserrat',sans-serif; box-sizing:border-box; }
.auth-page{
  min-height:100vh; min-height:100svh; display:flex; align-items:center; justify-content:center; padding:24px;
  position:relative; overflow:hidden;
  /* Fondo claro con acentos naranjas muy sutiles */
  background:
    radial-gradient(900px 600px at 5% -10%, rgba(255,140,0,0.08) 0%, transparent 50%),
    radial-gradient(700px 500px at 105% 10%, rgba(255,193,7,0.06) 0%, transparent 50%),
    linear-gradient(180deg, #ffffff 0%, #f7f9fb 55%, #f4f7f6 100%);
}
.auth-page::before,
.auth-page::after{
  content:""; position:absolute; inset:-20%; z-index:0;
  background:
    radial-gradient(circle at 30% 20%, rgba(142,202,230,.12) 0%, transparent 40%),
    radial-gradient(circle at 80% 60%, rgba(33,158,188,.12) 0%, transparent 45%);
  filter: blur(60px);
}
.panel{ background:var(--panel); border-radius:16px; box-shadow:0 18px 40px rgba(0,0,0,.06); width:480px; max-width:calc(100vw - 32px); padding:36px 28px; text-align:center; position:relative; z-index:1; }
h1{ color:var(--text); margin:0 0 16px; font-size:24px; font-weight:800; }
p{ color:var(--muted); margin:0 0 24px; }
input{
  background:var(--input);
  border:1px solid rgba(0,0,0,.06);
  color:var(--text);
  padding:12px 18px;
  margin:10px auto;
  width:100%;
  max-width:360px;
  display:block;
  border-radius:8px;
  box-sizing:border-box;
}
input::placeholder{ color:var(--muted); }
input:focus{ outline:2px solid var(--accentH); box-shadow:0 0 0 3px rgba(230,126,0,0.2); }
button{ border-radius:20px; border:1px solid var(--accent); background:var(--accent); color:#fff; font-size:12px; font-weight:700; padding:12px 45px; letter-spacing:1px; text-transform:uppercase; cursor:pointer; transition:transform 80ms ease-in, background .2s, color .2s; }
button:hover{ background:var(--accentH); }
.alert-box{ width:100%; margin:8px 0 0; padding:10px 12px; border-radius:8px; background:rgba(220,53,69,.08); border:1px solid rgba(220,53,69,.35); color:#ffb3b9; text-align:left; font-size:13px; display:none; }
.alert-box.info{ background:rgba(255,140,0,.10); border-color:rgba(255,140,0,.45); color:var(--accentH); }
.alert-box.success{ background:rgba(40,167,69,.08); border-color:rgba(40,167,69,.35); color:#b7eac6; }
 .modal {
   position: fixed;
   inset: 0;
   background: rgba(0,0,0,.5);
   display: none;
   align-items: center;
   justify-content: center;
   z-index: 9999 !important;
 }
 .modal[style*="display: flex"] {
   display: flex !important;
 }
 .modal .card {
   background: var(--panel);
   padding: 28px;
   border-radius: 16px;
   width: 480px;
   max-width: calc(100vw - 32px);
   box-shadow: 0 18px 40px rgba(0,0,0,.06);
   z-index: 10000;
 }
 .modal h3 { color: var(--text); margin: 0 0 12px; font-weight:800; }
 .modal .actions { margin-top: 8px; display: flex; gap: 8px; justify-content: center; }
 .modal .actions button:first-child { background: var(--accent); border:1px solid var(--accent); color:#fff; }
 .modal .actions button:last-child { background: transparent; border:1px solid var(--accent); color: var(--accent); }
.link{ color:var(--accent); text-decoration:none; }
.link:hover{ color:var(--accentH); }

/* Toasts de recuperación */
.recover-toast-wrap{
  position:fixed;
  top:18px;
  right:18px;
  z-index:12000;
  display:flex;
  flex-direction:column;
  gap:0;
  pointer-events:none;
  width:min(92vw, 380px);
}
.recover-toast{
  width:100%;
  background:#1f2937;
  color:#f9fafb;
  border:1px solid rgba(255,255,255,.18);
  border-left:6px solid var(--accent);
  border-radius:12px;
  box-shadow:0 14px 30px rgba(0,0,0,.35);
  padding:14px 16px;
  font-size:14px;
  font-weight:600;
  line-height:1.45;
  opacity:0;
  transition:opacity .22s ease;
}
.recover-toast.show{ opacity:1; }
.recover-toast.error{ border-left-color:#ef4444; background:#7f1d1d; color:#fee2e2; }
.recover-toast.success{ border-left-color:#22c55e; background:#14532d; color:#dcfce7; }
.recover-toast.info{ border-left-color:#f59e0b; background:#78350f; color:#fef3c7; }
.recover-toast-content{ display:flex; align-items:flex-start; gap:10px; }
.recover-toast-icon{ font-size:18px; line-height:1; margin-top:1px; }
.recover-toast-message{ flex:1; }
.recover-toast-details{ margin:6px 0 0 16px; padding:0; }
.recover-toast-details li{ margin:2px 0; }
</style>
@endpush

@section('content')
<div class="auth-page">
  <header class="auth-header" style="position:absolute;top:0;left:0;width:100%;padding:25px 50px;background:transparent;z-index:10;">
    <div class="brand" style="display:flex;align-items:center;gap:12px;">
      <img src="{{ asset('logouptag.png') }}" alt="Logo UPTAG" style="width:45px;height:45px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid rgba(0,0,0,.06);object-fit:cover;background:#fff;" />
      <h1 style="font-size:22px;color:var(--text);margin:0;font-weight:600;">Sistema de Inventario</h1>
    </div>
  </header>
  <div class="panel">
    <h1>Recuperar contraseña</h1>
    <p>Ingresa tu correo registrado para continuar</p>
    <form id="recover-email-form">
      @csrf
  <input type="email" id="recover-email" placeholder="Correo registrado" required autocomplete="username" maxlength="60" title="Correo válido, máximo 60 caracteres." />
      {{-- reCAPTCHA v2 para recuperación (paso de correo, solo si está habilitado) --}}
      @if(config('services.recaptcha.enabled') && config('services.recaptcha.site_key'))
        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}" style="margin:8px auto 14px;display:inline-block;"></div>
      @endif
      <button type="submit">Continuar</button>
    </form>
    <div id="recover-alert" class="alert-box" role="alert"></div>
    <p style="margin-top:12px"><a class="link" href="{{ url('/login') }}">Volver al inicio de sesión</a></p>
  </div>
  <!-- Modales -->
  <div id="security-recover-modal" class="modal">
    <div class="card">
      <h3>Verificación de seguridad</h3>
      <form id="security-recover-form">
        <div id="security-recover-alert" class="alert-box" role="alert" style="margin-bottom:8px;"></div>
  <input type="text" name="color" placeholder="¿Color favorito?" required autocomplete="off" maxlength="40" title="Color favorito: mínimo 2 y máximo 40 caracteres." />
  <input type="text" name="animal" placeholder="¿Animal favorito?" required autocomplete="off" maxlength="40" title="Animal favorito: mínimo 2 y máximo 40 caracteres." />
        <div id="padre-container" style="display:none;">
          <input type="text" name="padre" placeholder="¿Nombre del padre?" autocomplete="off" maxlength="40" title="Nombre del padre: mínimo 2 y máximo 40 caracteres." />
        </div>
        <div class="actions">
          <button type="submit">Verificar</button>
          <button type="button" id="cancel-security">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <div id="email-token-modal" class="modal">
    <div class="card">
      <h3>Verificación por correo</h3>
      <p id="email-token-hint" style="margin:0 0 10px;color:var(--muted);font-size:13px;">Te enviamos un código de 6 dígitos a tu correo.</p>
      <p id="email-token-countdown" style="margin:0 0 12px;color:#b45309;font-size:13px;font-weight:700;display:none;">Tiempo restante: 01:00</p>
      <form id="email-token-form">
        <div id="email-token-alert" class="alert-box" role="alert" style="margin-bottom:8px;"></div>
        <input
          type="text"
          name="email_token"
          id="email_token"
          placeholder="Código de 6 dígitos"
          required
          inputmode="numeric"
          maxlength="6"
          pattern="\d{6}"
          title="Ingresa el código de 6 dígitos enviado a tu correo"
          autocomplete="one-time-code"
        />
        <div class="actions" style="flex-wrap:wrap;">
          <button type="submit">Validar código</button>
          <button type="button" id="resend-email-token">Reenviar código</button>
          <button type="button" id="cancel-email-token">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <div id="change-password-modal" class="modal">
    <div class="card">
      <h3>Cambiar contraseña</h3>
      <form id="change-password-form">
        <!-- Campo nueva contraseña con ojito -->
        <div style="position:relative;max-width:360px;margin:0 auto 10px auto;">
          <input type="password" name="new_password" id="new_password" placeholder="Nueva contraseña" required autocomplete="new-password" minlength="16" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])\S+" title="Contraseña: mínimo 16 caracteres, al menos una mayúscula, una minúscula, un número, un símbolo y sin espacios." style="padding-right:40px;" />
          <span class="toggle-pwd" data-target="new_password" style="position:absolute;top:50%;right:12px;transform:translateY(-50%);cursor:pointer;">
            <svg width="24" height="24" fill="none" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </span>
        </div>
        <div id="recover-pwd-meter" style="width:100%;margin-top:6px;">
          <div style="height:8px;border-radius:6px;background:#e9ecef;overflow:hidden;">
            <div id="recover-pwd-fill" style="height:100%;width:0%;background:#dc3545;transition:width .2s ease, background .2s ease;"></div>
          </div>
          <div id="recover-pwd-hint" style="font-size:12px;color:#6c757d;margin-top:4px;">Fortaleza: Débil</div>
        </div>
        <!-- Campo confirmar contraseña con ojito -->
        <div style="position:relative;max-width:360px;margin:0 auto 10px auto;">
          <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirmar contraseña" required autocomplete="new-password" minlength="16" title="Debe coincidir exactamente con la nueva contraseña." style="padding-right:40px;" />
          <span class="toggle-pwd" data-target="confirm_password" style="position:absolute;top:50%;right:12px;transform:translateY(-50%);cursor:pointer;">
            <svg width="24" height="24" fill="none" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </span>
        </div>
        <div class="actions">
          <button type="submit">Cambiar</button>
          <button type="button" id="cancel-change">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@if(config('services.recaptcha.enabled') && config('services.recaptcha.site_key'))
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
<script>
let recoverFlowToken = null;
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const recoverAlert = document.getElementById('recover-alert');

// Usar rutas generadas por Blade para máxima compatibilidad
const routeCheckEmail = "{{ url('/recover/check-email') }}";
const routeCheckSecurity = "{{ url('/recover/check-security') }}";
const routeVerifyEmailToken = "{{ url('/recover/verify-email-token') }}";
const routeResendEmailToken = "{{ url('/recover/resend-email-token') }}";
const routeChangePassword = "{{ url('/recover/change-password') }}";
const routeLogin = "{{ url('/login') }}";
const typoDomains = ['gmai.com', 'gmial.com', 'gmal.com', 'hotnail.com', 'yaho.com'];
function sanitizeText(value){ return (value || '').trim().replace(/\s+/g, ' '); }
function isSuspiciousEmail(value){
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
}
function isSuspiciousPassword(value){
  const pwd = (value || '').trim();
  if (/^\d+$/.test(pwd)) return true;
  if (/(.)\1{4,}/u.test(pwd)) return true;
  if (new Set(pwd.split('')).size < 4) return true;
  return false;
}

function getPasswordFeedback(password){
  const pwd = (password || '').trim();
  const missing = [];
  if (pwd.length < 16) missing.push('Debe tener al menos 16 caracteres.');
  if (!/[A-Z]/.test(pwd)) missing.push('Debe incluir al menos una letra mayúscula.');
  if (!/[a-z]/.test(pwd)) missing.push('Debe incluir al menos una letra minúscula.');
  if (!/\d/.test(pwd)) missing.push('Debe incluir al menos un número.');
  if (!/[^A-Za-z0-9]/.test(pwd)) missing.push('Debe incluir al menos un símbolo.');
  if (/\s/.test(pwd)) missing.push('No debe contener espacios.');
  if (/^\d+$/.test(pwd) || /(.)\1{4,}/u.test(pwd) || new Set(pwd.split('')).size < 4) {
    missing.push('Evita secuencias o repeticiones simples.');
  }
  return { valid: missing.length === 0, missing };
}

function showRecoverToast(message, type = 'error', timeout = 3600, details = []){
  let wrap = document.getElementById('recover-toast-wrap');
  if (!wrap) {
    wrap = document.createElement('div');
    wrap.id = 'recover-toast-wrap';
    wrap.className = 'recover-toast-wrap';
    document.body.appendChild(wrap);
  }

  let toast = document.getElementById('recover-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'recover-toast';
    wrap.appendChild(toast);
  }
  toast.className = `recover-toast ${type}`;
  toast.setAttribute('role', 'alert');
  toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

  const iconMap = {
    error: '❌',
    success: '✅',
    info: '⚠️',
  };

  const content = document.createElement('div');
  content.className = 'recover-toast-content';

  const icon = document.createElement('span');
  icon.className = 'recover-toast-icon';
  icon.setAttribute('aria-hidden', 'true');
  icon.textContent = iconMap[type] || 'ℹ️';

  const messageWrap = document.createElement('div');
  messageWrap.className = 'recover-toast-message';

  const messageNode = document.createElement('div');
  messageNode.textContent = message;
  messageWrap.appendChild(messageNode);

  if (Array.isArray(details) && details.length > 0) {
    const list = document.createElement('ul');
    list.className = 'recover-toast-details';
    details.forEach((item) => {
      const li = document.createElement('li');
      li.textContent = item;
      list.appendChild(li);
    });
    messageWrap.appendChild(list);
  }

  content.appendChild(icon);
  content.appendChild(messageWrap);
  toast.innerHTML = '';
  toast.appendChild(content);

  if (window.recoverToastTimer) {
    clearTimeout(window.recoverToastTimer);
    window.recoverToastTimer = null;
  }

  toast.classList.add('show');
  window.recoverToastTimer = setTimeout(() => {
    toast.classList.remove('show');
  }, timeout);
}

let lastPwdToastSignature = '';
let lastPwdToastAt = 0;
let pwdSuccessShown = false;
let emailTokenTimerId = null;
let emailTokenRemainingSeconds = 0;

function formatCountdown(totalSeconds){
  const safe = Math.max(0, Number(totalSeconds) || 0);
  const minutes = Math.floor(safe / 60);
  const seconds = safe % 60;
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function stopEmailTokenCountdown(){
  if (emailTokenTimerId) {
    clearInterval(emailTokenTimerId);
    emailTokenTimerId = null;
  }
}

function renderEmailTokenCountdown(){
  const countdownEl = document.getElementById('email-token-countdown');
  if (!countdownEl) return;
  if (emailTokenRemainingSeconds <= 0) {
    countdownEl.textContent = 'El código expiró. Solicita uno nuevo.';
    countdownEl.style.display = 'block';
    countdownEl.style.color = '#b91c1c';
    stopEmailTokenCountdown();
    return;
  }
  countdownEl.textContent = `Tiempo restante: ${formatCountdown(emailTokenRemainingSeconds)}`;
  countdownEl.style.display = 'block';
  countdownEl.style.color = '#b45309';
}

function startEmailTokenCountdown(seconds){
  stopEmailTokenCountdown();
  emailTokenRemainingSeconds = Math.max(0, Number(seconds) || 0);
  renderEmailTokenCountdown();
  if (emailTokenRemainingSeconds <= 0) return;
  emailTokenTimerId = setInterval(() => {
    emailTokenRemainingSeconds -= 1;
    renderEmailTokenCountdown();
  }, 1000);
}

window.addEventListener('load', () => {
  const params = new URLSearchParams(window.location.search);
  const email = params.get('email');
  if (email) {
    const emailInput = document.getElementById('recover-email');
    emailInput.value = email;
    emailInput.readOnly = true;
  }
});

document.getElementById('recover-email-form').addEventListener('submit', function(e){
  e.preventDefault();
  const emailInput = document.getElementById('recover-email');
  const email = (emailInput.value || '').trim().toLowerCase();
  emailInput.value = email;
  if (recoverAlert){ recoverAlert.style.display='none'; recoverAlert.textContent=''; recoverAlert.className='alert-box'; }

  if (!email || email.length > 60 || isSuspiciousEmail(email)) {
    if (recoverAlert) {
      recoverAlert.textContent = 'Correo inválido: verifica formato/dominio y máximo 60 caracteres.';
      recoverAlert.style.display = 'block';
    }
    emailInput.focus();
    return;
  }

  // Validación reCAPTCHA para el paso de correo (si está activo)
  let captchaToken = null;
  try {
    if (window.grecaptcha && typeof grecaptcha.getResponse === 'function'){
      captchaToken = grecaptcha.getResponse();
      if (!captchaToken){
        if (recoverAlert){
          recoverAlert.textContent = 'Por favor completa el reCAPTCHA.';
          recoverAlert.style.display = 'block';
        }
        return;
      }
    }
  } catch(_){}
  const btn = this.querySelector('button[type="submit"]');
  const prev = btn?.textContent;
  if (btn){ btn.disabled = true; btn.textContent = 'Verificando…'; }
  fetch(routeCheckEmail, {
    method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept':'application/json' },
    body: JSON.stringify({ email, 'g-recaptcha-response': captchaToken })
  }).then(async r => {
  const data = await r.json().catch(() => null);
    if (data && data.success){
      recoverFlowToken = data.flow_token || null;
      document.getElementById('security-recover-modal').style.display = 'flex';
      document.getElementById('email-token-modal').style.display = 'none';
      document.getElementById('change-password-modal').style.display = 'none';
    } else {
      if (recoverAlert){
        const msg = (data && data.message) ? data.message : 'Correo no encontrado';
        recoverAlert.textContent = msg;
        recoverAlert.style.display = 'block';
      }
    }
  }).catch((err) => {
    if (recoverAlert){ recoverAlert.textContent = 'No se pudo contactar al servidor. Asegúrate de abrir la app en http://localhost (Laravel), no en el puerto de Vite.'; recoverAlert.style.display = 'block'; }
  }).finally(() => {
    if (btn){ btn.disabled = false; btn.textContent = prev; }
    // Resetear reCAPTCHA para permitir nuevos intentos
    try {
      if (window.grecaptcha && typeof grecaptcha.reset === 'function'){
        grecaptcha.reset();
      }
    } catch(_){ }
  });
});

document.getElementById('security-recover-form').addEventListener('submit', function(e){
  e.preventDefault();
  const color = sanitizeText(this.color.value);
  const animal = sanitizeText(this.animal.value);
  const padreInput = this.querySelector('input[name="padre"]');
  const padre = padreInput ? sanitizeText(padreInput.value) : '';
  const alertBox = document.getElementById('security-recover-alert');
  if (alertBox) { alertBox.style.display = 'none'; alertBox.textContent = ''; alertBox.className = 'alert-box'; }

  this.color.value = color;
  this.animal.value = animal;
  if (padreInput) padreInput.value = padre;

  const baseFields = [
    { value: color, input: this.color, label: 'Color favorito' },
    { value: animal, input: this.animal, label: 'Animal favorito' },
  ];
  for (const field of baseFields) {
    if (field.value.length < 2 || field.value.length > 40) {
      if (alertBox) {
        alertBox.textContent = `${field.label} inválido: debe tener entre 2 y 40 caracteres.`;
        alertBox.style.display = 'block';
      }
      field.input?.focus();
      return;
    }
  }

  const padreVisible = document.getElementById('padre-container')?.style.display === 'block';
  if (padreVisible && padreInput) {
    if (padre.length < 2 || padre.length > 40) {
      if (alertBox) {
        alertBox.textContent = 'Nombre del padre inválido: debe tener entre 2 y 40 caracteres.';
        alertBox.style.display = 'block';
      }
      padreInput.focus();
      return;
    }
  }

  fetch(routeCheckSecurity, {
    method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept':'application/json' },
    body: JSON.stringify({ flow_token: recoverFlowToken, color, animal, padre })
  }).then(async r => {
    const data = await r.json().catch(() => null);
    return { ok: r.ok, status: r.status, data };
  }).then(({ ok, status, data }) => {
    if (data && data.success){
      document.getElementById('security-recover-modal').style.display = 'none';
      if (data.require_email_token) {
        const hint = document.getElementById('email-token-hint');
        if (hint) {
          hint.textContent = data.email_hint
            ? `Te enviamos un código de 6 dígitos a ${data.email_hint}.`
            : 'Te enviamos un código de 6 dígitos a tu correo.';
        }
        startEmailTokenCountdown(data.token_expires_in_seconds);
        const tokenInput = document.getElementById('email_token');
        if (tokenInput) tokenInput.value = '';
        const alertToken = document.getElementById('email-token-alert');
        if (alertToken) {
          alertToken.style.display = 'none';
          alertToken.textContent = '';
          alertToken.className = 'alert-box';
        }
        document.getElementById('email-token-modal').style.display = 'flex';
        showRecoverToast(data.message || 'Te enviamos un código de verificación por correo.', 'info', 3200);
      } else {
        document.getElementById('change-password-modal').style.display = 'flex';
      }
    } else {
      if (!ok && data?.message) {
        if (alertBox) {
          alertBox.className = 'alert-box';
          alertBox.textContent = data.message;
          alertBox.style.display = 'block';
        }
        return;
      }

      const padreContainer = document.getElementById('padre-container');
      if (data && data.require_padre) {
        // Mostrar la tercera pregunta
        if (padreContainer) padreContainer.style.display = 'block';
        if (alertBox) {
          alertBox.className = 'alert-box info';
          // Mostrar el mensaje específico que viene del backend
          alertBox.textContent = data.message || 'Necesitamos una verificación adicional.';
          alertBox.style.display = 'block';
        }
      } else if (alertBox) {
        // Mensajes más específicos
        let msg = '';
        if (data && Array.isArray(data.incorrect)) {
          if (data.incorrect.includes('color') && data.incorrect.includes('animal') && data.incorrect.includes('padre')) {
            msg = 'Las respuestas proporcionadas no coinciden. Intenta nuevamente.';
          } else if (data.incorrect.includes('padre')) {
            msg = 'La verificación adicional no fue correcta.';
          } else if (data.incorrect.includes('color') && data.incorrect.includes('animal')) {
            msg = 'Ambas respuestas son incorrectas.';
          } else if (data.incorrect.includes('color')) {
            msg = 'El color favorito es incorrecto.';
          } else if (data.incorrect.includes('animal')) {
            msg = 'El animal favorito es incorrecto.';
          } else {
            msg = 'Respuestas incorrectas. Intenta nuevamente.';
          }
        } else {
          msg = (data && data.message)
            ? data.message
            : 'Respuestas incorrectas. Intenta nuevamente.';
        }
        alertBox.textContent = msg;
        alertBox.style.display = 'block';
      }
    }
  }).catch(() => {
    if (alertBox) {
      alertBox.textContent = 'Error de red. Intenta nuevamente.';
      alertBox.style.display = 'block';
    }
  });
});

document.getElementById('email-token-form').addEventListener('submit', function(e){
  e.preventDefault();
  const input = document.getElementById('email_token');
  const code = (input?.value || '').replace(/\D+/g, '').slice(0, 6);
  if (input) input.value = code;

  const alertBox = document.getElementById('email-token-alert');
  if (alertBox) {
    alertBox.style.display = 'none';
    alertBox.textContent = '';
    alertBox.className = 'alert-box';
  }

  if (!/^\d{6}$/.test(code)) {
    if (alertBox) {
      alertBox.textContent = 'El código debe tener exactamente 6 dígitos.';
      alertBox.style.display = 'block';
    }
    input?.focus();
    return;
  }

  const btn = this.querySelector('button[type="submit"]');
  const prev = btn?.textContent;
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Validando…';
  }

  fetch(routeVerifyEmailToken, {
    method: 'POST',
    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept':'application/json' },
    body: JSON.stringify({ flow_token: recoverFlowToken, email_token: code })
  })
  .then(r => r.json().catch(() => null))
  .then(data => {
    if (data && data.success) {
      stopEmailTokenCountdown();
      document.getElementById('email-token-modal').style.display = 'none';
      document.getElementById('change-password-modal').style.display = 'flex';
      showRecoverToast(data.message || 'Código validado correctamente.', 'success', 2200);
    } else {
      const msg = (data && data.message) ? data.message : 'No se pudo validar el código.';
      if (alertBox) {
        alertBox.textContent = msg;
        alertBox.style.display = 'block';
      }
    }
  })
  .catch(() => {
    if (alertBox) {
      alertBox.textContent = 'Error de red. Intenta nuevamente.';
      alertBox.style.display = 'block';
    }
  })
  .finally(() => {
    if (btn) {
      btn.disabled = false;
      btn.textContent = prev;
    }
  });
});

document.getElementById('resend-email-token').addEventListener('click', function(){
  const alertBox = document.getElementById('email-token-alert');
  if (alertBox) {
    alertBox.style.display = 'none';
    alertBox.textContent = '';
    alertBox.className = 'alert-box';
  }

  const btn = this;
  const prev = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Reenviando…';

  fetch(routeResendEmailToken, {
    method: 'POST',
    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept':'application/json' },
    body: JSON.stringify({ flow_token: recoverFlowToken })
  })
  .then(r => r.json().catch(() => null))
  .then(data => {
    if (data && data.success) {
      const hint = document.getElementById('email-token-hint');
      if (hint && data.email_hint) {
        hint.textContent = `Te enviamos un nuevo código a ${data.email_hint}.`;
      }
      startEmailTokenCountdown(data.token_expires_in_seconds);
      showRecoverToast(data.message || 'Código reenviado.', 'success', 2400);
    } else {
      const msg = (data && data.message) ? data.message : 'No se pudo reenviar el código.';
      if (alertBox) {
        alertBox.textContent = msg;
        alertBox.style.display = 'block';
      }
    }
  })
  .catch(() => {
    if (alertBox) {
      alertBox.textContent = 'Error de red. Intenta nuevamente.';
      alertBox.style.display = 'block';
    }
  })
  .finally(() => {
    btn.disabled = false;
    btn.textContent = prev;
  });
});

document.getElementById('change-password-form').addEventListener('submit', function(e){
  e.preventDefault();
  const newPassword = (this.new_password.value || '').trim();
  const confirmPassword = (this.confirm_password.value || '').trim();
  this.new_password.value = newPassword;
  this.confirm_password.value = confirmPassword;
  const pwdFeedback = getPasswordFeedback(newPassword || '');
  // Medidor visual
  (function(){
    const fill = document.getElementById('recover-pwd-fill');
    const hint = document.getElementById('recover-pwd-hint');
    function score(p){ if(!p) return 0; let s=0; if(p.length>=16) s+=2; else if(p.length>=12) s+=1; if(/[a-z]/.test(p)) s+=1; if(/[A-Z]/.test(p)) s+=1; if(/\d/.test(p)) s+=1; if(/[^A-Za-z0-9]/.test(p)) s+=1; return Math.min(s,6); }
    const sc = score(newPassword||''); let pct = Math.round((sc/6)*100);
    let label='Débil', color='#dc3545';
    if ((newPassword||'').length >= 16) { label='Fuerte'; color='#28a745'; pct = 100; }
    if(sc===6){ label='Excelente'; color='#20c997'; pct = 100; }
    if (fill) fill.style.width = pct+'%';
    if (fill) fill.style.background=color; if (hint) hint.textContent='Fortaleza: '+label;
  })();
  if (!pwdFeedback.valid || isSuspiciousPassword(newPassword||'')){
    showRecoverToast('Tu contraseña aún no cumple los requisitos.', 'error', 5200, pwdFeedback.missing);
    return;
  }
  if (newPassword !== confirmPassword){
    showRecoverToast('Las contraseñas no coinciden.', 'error');
    return;
  }
  fetch(routeChangePassword, {
    method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept':'application/json' },
    body: JSON.stringify({ flow_token: recoverFlowToken, password: newPassword })
  }).then(r => r.json()).then(data => {
    if (data && data.success){
      document.getElementById('change-password-modal').style.display = 'none';
      // Mostrar mensaje bonito centrado
      let successMsg = document.getElementById('recover-success-msg');
      if (!successMsg) {
        successMsg = document.createElement('div');
        successMsg.id = 'recover-success-msg';
        successMsg.innerHTML = `<div style="position:fixed;inset:0;z-index:10001;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.55);">
          <div style="background:var(--panel,#ffffff);padding:36px 32px 28px 32px;border-radius:16px;box-shadow:0 18px 40px rgba(0,0,0,.06);text-align:center;max-width:90vw;min-width:320px;">
            <div style='font-size:2.5rem;line-height:1;margin-bottom:12px;color:var(--accent,#ff8c00);'><i class='fa fa-check-circle'></i></div>
            <h2 style='color:var(--text,#222831);margin:0 0 10px;font-size:1.4rem;'>¡Contraseña cambiada!</h2>
            <div style='color:var(--muted,#6c757d);font-size:1.1rem;margin-bottom:10px;'>Ahora puedes iniciar sesión con tu nueva contraseña.</div>
            <div style='color:var(--accent,#ff8c00);font-size:1rem;'>Redirigiendo al inicio de sesión…</div>
          </div>
        </div>`;
        document.body.appendChild(successMsg);
      } else {
        successMsg.style.display = 'flex';
      }
      setTimeout(() => { window.location.href = routeLogin; }, 2200);
    } else {
      showRecoverToast((data && data.message) ? data.message : 'No se pudo cambiar la contraseña. Intenta nuevamente.', 'error');
    }
  }).catch(() => showRecoverToast('Error de red. Verifica tu conexión e intenta nuevamente.', 'error'));
});

document.getElementById('cancel-security').addEventListener('click', ()=>{
  document.getElementById('security-recover-modal').style.display = 'none';
});
document.getElementById('cancel-email-token').addEventListener('click', ()=>{
  stopEmailTokenCountdown();
  const countdownEl = document.getElementById('email-token-countdown');
  if (countdownEl) {
    countdownEl.style.display = 'none';
  }
  document.getElementById('email-token-modal').style.display = 'none';
});
document.getElementById('cancel-change').addEventListener('click', ()=>{
  document.getElementById('change-password-modal').style.display = 'none';
});
// Medidor en tiempo real para recuperación
document.querySelector('#change-password-form input[name="new_password"]').addEventListener('input', function(){
  const p = this.value||'';
  const fill = document.getElementById('recover-pwd-fill');
  const hint = document.getElementById('recover-pwd-hint');
  function score(p){ if(!p) return 0; let s=0; if(p.length>=16) s+=2; else if(p.length>=12) s+=1; if(/[a-z]/.test(p)) s+=1; if(/[A-Z]/.test(p)) s+=1; if(/\d/.test(p)) s+=1; if(/[^A-Za-z0-9]/.test(p)) s+=1; return Math.min(s,6); }
  const sc = score(p); let pct = Math.round((sc/6)*100);
  let label='Débil', color='#dc3545';
  if (p.length >= 16) { label='Fuerte'; color:'#28a745'; pct = 100; }
  if(sc===6){label='Excelente';color='#20c997'; pct = 100; }
  if (fill) fill.style.width = pct+'%';
  if (fill) fill.style.background=color; if (hint) hint.textContent='Fortaleza: '+label;

  if (!p) {
    lastPwdToastSignature = '';
    pwdSuccessShown = false;
    return;
  }

  const feedback = getPasswordFeedback(p);
  const signature = feedback.missing.join('|');
  const now = Date.now();

  if (!feedback.valid) {
    pwdSuccessShown = false;
    if (signature !== lastPwdToastSignature && (now - lastPwdToastAt) > 1200) {
      showRecoverToast('Te falta cumplir estos requisitos de contraseña:', 'info', 3400, feedback.missing);
      lastPwdToastSignature = signature;
      lastPwdToastAt = now;
    }
  } else if (!pwdSuccessShown) {
    showRecoverToast('¡Perfecto! La contraseña cumple todos los requisitos.', 'success', 2200);
    pwdSuccessShown = true;
    lastPwdToastSignature = '';
    lastPwdToastAt = now;
  }
});
// Mostrar/ocultar contraseña
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