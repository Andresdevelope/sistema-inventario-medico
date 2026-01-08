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
.alert-box.info{ background:rgba(33,158,188,.08); border-color:rgba(33,158,188,.35); color:#bfe7f4; }
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
</style>
@endpush

@section('content')
<div class="auth-page">
  <header class="auth-header" style="position:absolute;top:0;left:0;width:100%;padding:25px 50px;background:transparent;z-index:10;">
    <div class="brand" style="display:flex;align-items:center;gap:12px;">
      <div class="logo-placeholder" style="width:45px;height:45px;border-radius:8px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid rgba(0,0,0,.06);"></div>
      <h1 style="font-size:22px;color:var(--text);margin:0;font-weight:600;">Sistema de Inventario</h1>
    </div>
  </header>
  <div class="panel">
    <h1>Recuperar contraseña</h1>
    <p>Ingresa tu correo registrado para continuar</p>
    <form id="recover-email-form">
      @csrf
  <input type="email" id="recover-email" placeholder="Correo registrado" required autocomplete="username" />
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
  <input type="text" name="color" placeholder="¿Color favorito?" required autocomplete="off" />
  <input type="text" name="animal" placeholder="¿Animal favorito?" required autocomplete="off" />
        <div id="padre-container" style="display:none;">
          <input type="text" name="padre" placeholder="¿Nombre del padre?" autocomplete="off" />
        </div>
        <div class="actions">
          <button type="submit">Verificar</button>
          <button type="button" id="cancel-security">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
  <div id="change-password-modal" class="modal">
    <div class="card">
      <h3>Cambiar contraseña</h3>
      <form id="change-password-form">
  <input type="password" name="new_password" placeholder="Nueva contraseña" required autocomplete="new-password" />
  <input type="password" name="confirm_password" placeholder="Confirmar contraseña" required autocomplete="new-password" />
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
<script>
let recoverUserId = null;
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const recoverAlert = document.getElementById('recover-alert');

// Usar rutas generadas por Blade para máxima compatibilidad
const routeCheckEmail = "{{ url('/recover/check-email') }}";
const routeCheckSecurity = "{{ url('/recover/check-security') }}";
const routeChangePassword = "{{ url('/recover/change-password') }}";
const routeLogin = "{{ url('/login') }}";

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
  const email = document.getElementById('recover-email').value.trim();
  if (recoverAlert){ recoverAlert.style.display='none'; recoverAlert.textContent=''; recoverAlert.className='alert-box'; }
  const btn = this.querySelector('button[type="submit"]');
  const prev = btn?.textContent;
  if (btn){ btn.disabled = true; btn.textContent = 'Verificando…'; }
  fetch(routeCheckEmail, {
    method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept':'application/json' },
    body: JSON.stringify({ email })
  }).then(async r => {
  const data = await r.json().catch(() => null);
    if (data && data.success){
      recoverUserId = data.user_id;
      document.getElementById('security-recover-modal').style.display = 'flex';
    } else {
      if (recoverAlert){ recoverAlert.textContent = 'Correo no encontrado'; recoverAlert.style.display = 'block'; }
    }
  }).catch((err) => {
    if (recoverAlert){ recoverAlert.textContent = 'No se pudo contactar al servidor. Asegúrate de abrir la app en http://localhost (Laravel), no en el puerto de Vite.'; recoverAlert.style.display = 'block'; }
  }).finally(() => { if (btn){ btn.disabled = false; btn.textContent = prev; } });
});

document.getElementById('security-recover-form').addEventListener('submit', function(e){
  e.preventDefault();
  const color = this.color.value.trim();
  const animal = this.animal.value.trim();
  const padreInput = this.querySelector('input[name="padre"]');
  const padre = padreInput ? padreInput.value.trim() : '';
  const alertBox = document.getElementById('security-recover-alert');
  if (alertBox) { alertBox.style.display = 'none'; alertBox.textContent = ''; alertBox.className = 'alert-box'; }
  fetch(routeCheckSecurity, {
    method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept':'application/json' },
    body: JSON.stringify({ user_id: recoverUserId, color, animal, padre })
  }).then(r => r.json()).then(data => {
    if (data && data.success){
      document.getElementById('security-recover-modal').style.display = 'none';
      document.getElementById('change-password-modal').style.display = 'flex';
    } else {
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
          msg = 'Respuestas incorrectas. Intenta nuevamente.';
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

document.getElementById('change-password-form').addEventListener('submit', function(e){
  e.preventDefault();
  const newPassword = this.new_password.value;
  const confirmPassword = this.confirm_password.value;
  if (newPassword !== confirmPassword){
    alert('Las contraseñas no coinciden');
    return;
  }
  fetch(routeChangePassword, {
    method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept':'application/json' },
    body: JSON.stringify({ user_id: recoverUserId, password: newPassword })
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
      alert('Error al cambiar la contraseña');
    }
  }).catch(() => alert('Error de red.'));
});

document.getElementById('cancel-security').addEventListener('click', ()=>{
  document.getElementById('security-recover-modal').style.display = 'none';
});
document.getElementById('cancel-change').addEventListener('click', ()=>{
  document.getElementById('change-password-modal').style.display = 'none';
});
</script>
@endpush