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
  background:var(--panel); display:flex; align-items:center; justify-content:center; flex-direction:column;
  padding:0 50px; height:100%; text-align:center; border-radius:10px;
}
input{ background:var(--input); border:1px solid rgba(0,0,0,.06); color:var(--text); padding:12px 15px; margin:8px 0; width:100%; border-radius:8px; }
input::placeholder{ color:var(--muted); }
input:focus{ outline:2px solid var(--accentH); box-shadow:0 0 0 3px rgba(230, 126, 0, 0.2); }
.alert-box{ width:100%; margin:8px 0 0; padding:10px 12px; border-radius:8px; background:rgba(220,53,69,.08); border:1px solid rgba(220,53,69,.35); color:#dc3545; text-align:left; font-size:13px; display:none; }
.alert-box.info{ background:rgba(33,158,188,.08); border-color:rgba(33,158,188,.35); color:#219ebc; }
.alert-box.success{ background:rgba(40,167,69,.08); border-color:rgba(40,167,69,.35); color:#28a745; }
.container{ background:var(--panel); border-radius:16px; box-shadow:0 18px 40px rgba(0,0,0,.06); position:relative; z-index:1; overflow:hidden; width:768px; max-width:100%; min-height:520px; }
.form-container{ position:absolute; top:0; height:100%; transition:all .3s ease-in-out; }
.sign-in-container{ left:0; width:50%; z-index:2; }
.container.right-panel-active .sign-in-container{ transform:translateX(100%); }
.sign-up-container{ left:0; width:50%; opacity:0; z-index:1; }
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
@media (max-width: 768px){ .container{ min-height:560px; } }
</style>
@endpush

@section('content')
<div class="auth-page">
  <header class="auth-header">
    <div class="brand">
      <div class="logo-placeholder"></div>
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
        <input type="password" name="password" placeholder="Contraseña" required />
        <input type="text" name="color" placeholder="¿Color favorito?" required />
        <input type="text" name="animal" placeholder="¿Animal favorito?" required />
        <input type="text" name="padre" placeholder="¿Nombre del padre?" required />
        <button type="submit">Registrarse</button>
      </form>
    </div>
    <div class="form-container sign-in-container">
      <form method="POST" action="{{ route('login.post') }}" id="username-login-form">
        @csrf
        <h1>Iniciar Sesión</h1>
        <div id="login-alert" class="alert-box" role="alert"></div>
        <input type="text" name="username" placeholder="Usuario" required />
        <input type="password" name="password" placeholder="Contraseña" required />
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
<script>
  const container = document.getElementById('container');
  const signUpButton = document.getElementById('signUp');
  const signInButton = document.getElementById('signIn');
  // Transición de paneles
  signUpButton?.addEventListener('click', () => {
    container.classList.add('right-panel-active');
  });
  signInButton?.addEventListener('click', () => {
    container.classList.remove('right-panel-active');
  });

  // Interceptar submit de registro para manejar respuesta JSON y redirigir
  const registerForm = document.getElementById('register-form');
  const registerAlert = document.getElementById('register-alert');
  if (registerForm) {
    registerForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      if (registerAlert) { registerAlert.style.display = 'none'; registerAlert.textContent = ''; registerAlert.className = 'alert-box'; }
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
          // Mensaje bonito y redirección
          let successMsg = document.getElementById('register-success-msg');
          if (!successMsg) {
            successMsg = document.createElement('div');
            successMsg.id = 'register-success-msg';
            successMsg.innerHTML = `<div style="position:fixed;inset:0;z-index:10001;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.65);">
              <div style='background:var(--panel,#232a36);padding:36px 32px 28px 32px;border-radius:16px;box-shadow:0 8px 32px rgba(31,38,135,.37);text-align:center;max-width:90vw;min-width:320px;'>
                <div style='font-size:2.5rem;line-height:1;margin-bottom:12px;color:#8ecae6;'><i class='fa fa-user-plus'></i></div>
                <h2 style='color:#b7eac6;margin:0 0 10px;font-size:1.4rem;'>¡Registro exitoso!</h2>
                <div style='color:#b0b8c1;font-size:1.1rem;margin-bottom:10px;'>Tu cuenta fue creada correctamente.</div>
                <div style='color:#8ecae6;font-size:1rem;'>Redirigiendo al inicio de sesión…</div>
              </div>
            </div>`;
            document.body.appendChild(successMsg);
          } else {
            successMsg.style.display = 'flex';
          }
          setTimeout(() => { window.location.href = '{{ url('/login') }}'; }, 2200);
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
      }
    });
  }
  const loginForm = document.getElementById('username-login-form');
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
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
        } else {
          loginAlert.className = 'alert-box';
          loginAlert.style.display = 'block';
          loginAlert.textContent = data?.message || 'Credenciales incorrectas';
        }
      }
    } catch (err) {
      loginAlert.className = 'alert-box';
      loginAlert.style.display = 'block';
      loginAlert.textContent = 'Error de red: intenta nuevamente.';
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = originalText; }
    }
  });
</script>
@endpush