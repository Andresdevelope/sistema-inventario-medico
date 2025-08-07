@extends('layouts.dashboard')

@section('content')
<div class="container mt-5 d-flex justify-content-center">
  <div class="card shadow border-0" style="max-width: 700px; width:100%; border-radius: 1rem;">
    <div class="row g-0">
      <div class="col-md-4 bg-primary text-white d-flex flex-column align-items-center justify-content-center p-4" style="border-radius: 1rem 0 0 1rem;">
        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? Auth::user()->username ?? 'U') }}&size=64&background=4093c7&color=fff" alt="avatar" class="rounded-circle mb-3 shadow" width="64" height="64">
        <h5 class="fw-bold mb-1">{{ Auth::user()->username ?? Auth::user()->name ?? '-' }}</h5>
        <span class="small"><i class="fa fa-envelope me-1"></i> {{ Auth::user()->email ?? '-' }}</span>
      </div>
      <div class="col-md-8 p-4">
        <h4 class="fw-bold mb-3"><i class="fa fa-user me-2 text-primary"></i>Perfil de usuario</h4>
        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
          </div>
        @endif
        @if($errors->any())
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-triangle me-2"></i>
            @foreach($errors->all() as $error)
              <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
          </div>
        @endif
        <div class="mb-3">
          <label class="form-label fw-bold mb-0">Fecha de registro:</label>
          <div class="text-secondary">{{ Auth::user()->created_at ? Auth::user()->created_at->format('d/m/Y') : '-' }}</div>
        </div>
        <div class="d-flex flex-column gap-2 mt-4">
          <a href="/dashboard" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Volver</a>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCambiarContrasena">
            <i class="fa fa-key me-1"></i> Cambiar contraseña
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Cambiar Contraseña -->
<div class="modal fade" id="modalCambiarContrasena" tabindex="-1" aria-labelledby="modalCambiarContrasenaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
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
          <button type="button" class="btn btn-primary w-100" id="btnValidarSeguridad">Validar respuestas</button>
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
          <button type="submit" class="btn btn-success w-100">Cambiar contraseña</button>
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
        </div>
    </div>
</div>
@endsection
