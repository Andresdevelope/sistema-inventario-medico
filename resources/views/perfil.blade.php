@extends('layouts.dashboard')

@section('content')
@php($user = Auth::user())
@php($__name = $user->username ?? $user->name ?? 'U')
@php($bloqueada = $user->locked_until && $user->locked_until->isFuture())
@php($rolLabel = strtoupper($user->role ?? 'USUARIO'))
@php($estadoCuenta = $bloqueada ? 'Cuenta bloqueada temporalmente' : 'Cuenta activa y segura')

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card shadow border-0" style="border-radius:1rem;background:var(--slate-surface);">
        <div class="row g-0">
          {{-- Barra lateral izquierda (resumen) --}}
          <div class="col-md-3 text-white d-flex flex-column align-items-center justify-content-between p-4" style="border-radius:1rem 0 0 1rem; background:linear-gradient(160deg, var(--accent) 0%, #FF8F48 45%, #8c3c14 100%);">
            <div class="d-flex flex-column align-items-center">
              <span class="rounded-circle mb-3 shadow d-inline-flex align-items-center justify-content-center fw-bold" style="width:64px;height:64px;font-size:1.8rem;background:var(--slate-bg);color:var(--accent);">
                {{ strtoupper(mb_substr(trim($__name),0,1,'UTF-8')) }}
              </span>
              <h5 class="fw-bold mb-1 text-center">{{ $user->username ?? $user->name ?? '-' }}</h5>
              <span class="small mb-1 text-center"><i class="fa fa-envelope me-1"></i> {{ $user->email ?? '-' }}</span>
              <span class="badge rounded-pill mt-2" style="background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.25);font-size:.7rem;">
                <i class="fa fa-id-badge me-1"></i>ROL: {{ $rolLabel }}
              </span>
            </div>
            <div class="w-100 mt-3 d-flex flex-column gap-2">
              <a href="{{ route('bitacora.index') }}?user={{ $user->id }}" class="btn btn-sm w-100" style="font-size:.75rem;background:rgba(0,0,0,.45);border-color:rgba(255,255,255,.6);color:#fff;">
                <i class="fa fa-book me-1"></i> Registro personal
              </a>
              <a href="/dashboard" class="btn btn-sm w-100" style="font-size:.75rem;background:rgba(0,0,0,.45);border-color:rgba(255,255,255,.6);color:#fff;">
                <i class="fa fa-arrow-left me-1"></i> Volver al inicio
              </a>
            </div>
          </div>

          {{-- Contenido principal a la derecha --}}
          <div class="col-md-9 p-4">
            <h4 class="fw-bold mb-3"><i class="fa fa-user me-2" style="color:var(--accent);"></i>Perfil de usuario</h4>
            @if(session('success'))
              <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert" style="font-size:.85rem;">
                <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
              </div>
            @endif
            @if($errors->any())
              <div class="alert alert-danger alert-dismissible fade show py-2 mb-3" role="alert" style="font-size:.85rem;">
                <i class="fa fa-exclamation-triangle me-2"></i>
                @foreach($errors->all() as $error)
                  <div>{{ $error }}</div>
                @endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
              </div>
            @endif

            {{-- Sección: Datos de la cuenta --}}
            <div class="mb-3 pb-2 border-bottom" style="border-color:var(--slate-border);">
              <span class="text-uppercase d-block mb-2" style="font-size:.7rem;color:var(--txt-dim);letter-spacing:.06em;">Datos de la cuenta</span>
              <div class="row g-3" style="font-size:.9rem;">
                <div class="col-md-6">
                  <div class="text-muted small">Nombre</div>
                  <div>{{ $user->name ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                  <div class="text-muted small">Correo</div>
                  <div>{{ $user->email ?? '-' }}</div>
                </div>
                <div class="col-md-6 mt-2">
                  <div class="text-muted small">Rol</div>
                  <div>{{ $rolLabel }}</div>
                </div>
                <div class="col-md-6 mt-2">
                  <div class="text-muted small">Fecha de registro</div>
                  <div>{{ $user->created_at ? $user->created_at->format('d/m/Y') : '-' }}</div>
                </div>
                <div class="col-md-6 mt-2">
                  <div class="text-muted small">Intentos fallidos de acceso</div>
                  <div>{{ $user->login_attempts ?? 0 }}</div>
                </div>
                <div class="col-md-6 mt-2">
                  <div class="text-muted small">Estado</div>
                  <div>{{ $estadoCuenta }}</div>
                </div>
              </div>
            </div>

            {{-- Sección: Seguridad --}}
            <div class="mb-3 pb-2 border-bottom" style="border-color:var(--slate-border);">
              <span class="text-uppercase d-block mb-2" style="font-size:.7rem;color:var(--txt-dim);letter-spacing:.06em;">Seguridad</span>
              <div class="d-flex flex-wrap gap-2 mb-2" style="font-size:.75rem;">
                <span class="badge rounded-pill" style="background:var(--slate-surface-soft);color:var(--txt-sec);">
                  <i class="fa fa-lock me-1"></i>Contraseña protegida
                </span>
                <span class="badge rounded-pill" style="background:var(--slate-surface-soft);color:var(--txt-sec);">
                  <i class="fa fa-question-circle me-1"></i>Preguntas de seguridad configuradas
                </span>
                @if($bloqueada)
                  <span class="badge rounded-pill bg-danger"><i class="fa fa-clock me-1"></i>Bloqueada hasta {{ $user->locked_until->format('d/m H:i') }}</span>
                @else
                  <span class="badge rounded-pill" style="background:rgba(46,204,113,.12);color:#2ecc71;border:1px solid rgba(46,204,113,.4);">
                    <i class="fa fa-check-circle me-1"></i>Acceso disponible
                  </span>
                @endif
              </div>
              <p class="text-muted small mb-3">
                Antes de cambiar tu contraseña se verificará tu identidad mediante las preguntas de seguridad. Esto ayuda a proteger tu cuenta frente a accesos no autorizados.
              </p>
              <button type="button" class="btn" style="background:var(--accent);border-color:var(--accent);color:#fff;font-weight:600;" data-bs-toggle="modal" data-bs-target="#modalCambiarContrasena">
                <i class="fa fa-key me-1"></i> Cambiar contraseña
              </button>
            </div>

            {{-- Sección: Actividad reciente --}}
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-uppercase" style="font-size:.7rem;color:var(--txt-dim);letter-spacing:.06em;">Actividad reciente</span>
                <a href="{{ route('bitacora.index') }}?user={{ $user->id }}" class="small" style="color:var(--accent);">Ver todo</a>
              </div>
              @php($actividadReciente = \App\Models\Bitacora::where('user_id', $user->id)
                  ->orderBy('fecha_hora','desc')
                  ->limit(5)
                  ->get())
              @if($actividadReciente->isEmpty())
                <div class="text-muted small">Sin registros recientes.</div>
              @else
                <ul class="list-unstyled mb-0" style="font-size:.8rem;">
                  @foreach($actividadReciente as $b)
                    <li class="d-flex justify-content-between align-items-start py-1 border-bottom" style="border-color:var(--slate-border);">
                      <div>
                        <div><span class="badge bg-secondary me-1">{{ $b->accion }}</span></div>
                        <div class="text-muted small">
                          {{ \Carbon\Carbon::parse($b->fecha_hora)->format('d/m/Y H:i') }}
                        </div>
                      </div>
                    </li>
                  @endforeach
                </ul>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Cambiar Contraseña -->
<div class="modal fade" id="modalCambiarContrasena" tabindex="-1" aria-labelledby="modalCambiarContrasenaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--slate-surface);border-radius:1rem;">
      <div class="modal-header text-white" style="background:var(--accent);border-radius:1rem 1rem 0 0;">
        <h5 class="modal-title" id="modalCambiarContrasenaLabel"><i class="fa fa-key me-2"></i>Verificación y cambio de contraseña</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formSeguridad" autocomplete="off">
          <div class="mb-3">
            <label class="form-label fw-bold">¿Cuál es tu color favorito?</label>
            <input type="text" class="form-control" name="color" required maxlength="50" id="inputColor">
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">¿Cuál es tu animal favorito?</label>
            <input type="text" class="form-control" name="animal" required maxlength="50" id="inputAnimal">
          </div>
          <div id="errorSeguridad" class="text-danger mb-2 d-none"></div>
          <div id="successSeguridad" class="text-success mb-2 d-none"></div>
          <button type="button" class="btn w-100" style="background:var(--accent);border-color:var(--accent);color:#fff;font-weight:600;" id="btnValidarSeguridad">Validar respuestas</button>
        </form>
        <form id="formContrasena" class="d-none mt-3" method="POST" action="{{ route('cambiar.contrasena') }}" autocomplete="off">
          @csrf
          <div class="mb-3 position-relative">
            <label class="form-label fw-bold">Contraseña actual</label>
            <input type="password" class="form-control pe-5" name="actual" required minlength="8" maxlength="50" id="actualContrasena">
            <span class="position-absolute d-flex align-items-center" style="height:100%; right:18px; top:0; cursor:pointer;" onclick="togglePassword('actualContrasena', this)"><i class="fa fa-eye text-secondary"></i></span>
          </div>
          <div class="mb-3 position-relative">
            <label class="form-label fw-bold">Nueva contraseña</label>
            <input type="password" class="form-control pe-5" name="nueva" required minlength="8" maxlength="50" id="nuevaContrasena">
            <span class="position-absolute d-flex align-items-center" style="height:100%; right:18px; top:0; cursor:pointer;" onclick="togglePassword('nuevaContrasena', this)"><i class="fa fa-eye text-secondary"></i></span>
          </div>
          <div class="mb-3 position-relative">
            <label class="form-label fw-bold">Confirmar nueva contraseña</label>
            <input type="password" class="form-control pe-5" name="confirmar" required minlength="8" maxlength="50" id="confirmarContrasena">
            <span class="position-absolute d-flex align-items-center" style="height:100%; right:18px; top:0; cursor:pointer;" onclick="togglePassword('confirmarContrasena', this)"><i class="fa fa-eye text-secondary"></i></span>
          </div>
          <div id="errorContrasena" class="text-danger mb-2 d-none">Las contraseñas no coinciden o son menores a 8 caracteres.</div>
          <button type="submit" class="btn w-100" style="background:var(--accent);border-color:var(--accent);color:#fff;font-weight:600;">Cambiar contraseña</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('btnValidarSeguridad').onclick = function() {
  var color = document.getElementById('inputColor').value.trim();
  var animal = document.getElementById('inputAnimal').value.trim();
  var errorDiv = document.getElementById('errorSeguridad');
  var successDiv = document.getElementById('successSeguridad');
  errorDiv.classList.add('d-none');
  successDiv.classList.add('d-none');
  fetch("{{ route('perfil.validarSeguridad') }}", {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({ color: color, animal: animal })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      successDiv.textContent = data.message;
      successDiv.classList.remove('d-none');
      setTimeout(function() {
        document.getElementById('formSeguridad').classList.add('d-none');
        document.getElementById('formContrasena').classList.remove('d-none');
      }, 1000);
    } else {
      errorDiv.textContent = data.message;
      errorDiv.classList.remove('d-none');
    }
  })
  .catch(() => {
    errorDiv.textContent = 'Error de conexión. Intenta de nuevo.';
    errorDiv.classList.remove('d-none');
  });
};
document.getElementById('formContrasena').onsubmit = function(e) {
    var nueva = document.getElementById('nuevaContrasena').value;
    var confirmar = document.getElementById('confirmarContrasena').value;
    var error = document.getElementById('errorContrasena');
    if (nueva.length < 8 || confirmar.length < 8 || nueva !== confirmar) {
        error.classList.remove('d-none');
        e.preventDefault();
    } else {
        error.classList.add('d-none');
    }
};
function togglePassword(id, el) {
  var input = document.getElementById(id);
  var icon = el.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}
</script>
@endsection
