
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SERVICIOS MEDICOS - Dashboard</title>
    {{-- Assets locales compilados con Vite (fonts, bootstrap, fontawesome) --}}
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', Arial, sans-serif;
            background: #f3f6fa;
            min-height: 100vh;
        }
        .layout-wrapper {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .topbar {
            background: #4093c7;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: 60px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            z-index: 1020;
            position: relative;
        }
        .topbar .logo {
            display: flex;
            align-items: center;
            font-size: 1.3rem;
            font-weight: bold;
        }
        .topbar .logo i {
            margin-right: 10px;
            font-size: 1.5rem;
        }
        .topbar .user {
            display: flex;
            align-items: center;
        }
        .topbar .user i {
            margin-right: 8px;
        }
        .sidebar {
            width: 60px;
            background: #222e36;
            color: #fff;
            position: relative;
            top: 0;
            left: 0;
            bottom: 0;
            padding-top: 1rem;
            z-index: 1010;
            transition: width 0.2s;
            overflow-x: hidden;
            height: auto;
            min-height: calc(100vh - 60px);
        }
        .sidebar:hover {
            width: 220px;
        }
        .sidebar .sidebar-label {
            display: none;
            transition: opacity 0.2s;
        }
        .sidebar:hover .sidebar-label {
            display: inline;
            opacity: 1;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar ul li {
            margin-bottom: 8px;
        }
        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 24px;
            border-left: 4px solid transparent;
            transition: background 0.2s, border-color 0.2s;
        }
        .sidebar ul li a.active, .sidebar ul li a:hover {
            background: #31404b;
            border-left: 4px solid #4093c7;
        }
        .sidebar ul li a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        .main-content {
            /* margin-left eliminado, ahora usa flexbox */
            flex: 1;
            padding: 2rem 1rem 2.5rem 1rem;
            min-height: calc(100vh - 60px - 44px);
            transition: margin-left 0.2s;
            overflow-x: auto;
            background: #f3f6fa;
        }
        @media (max-width: 900px) {
            .sidebar { width: 60px; }
            .sidebar .sidebar-label { display: none; }
            .main-content { margin-left: 60px; padding: 1rem 0.5rem 2.5rem 0.5rem; }
        }
        .footer {
            height: 44px;
            background: linear-gradient(90deg, #e0e3e8 0%, #cfd4db 100%);
            text-align: center;
            color: #444;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1030;
            box-shadow: 0 -2px 10px rgba(180,180,180,0.10);
            letter-spacing: 0.5px;
        }
        .footer a {
            color: #2176ae;
            text-decoration: underline;
            font-weight: bold;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="layout-wrapper">
        <div class="topbar">
            <div class="logo">
                <i class="fa-solid fa-capsules"></i> Servicios Médicos
            </div>
            <div class="user dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="background: #2196f3; border-radius: 2rem; padding: 0.3rem 1rem;">
                    @php($__name = Auth::user()->name ?? Auth::user()->username ?? 'U')
                    <span class="rounded-circle me-2 d-inline-flex align-items-center justify-content-center bg-light text-primary fw-bold" style="width:36px;height:36px; font-size:0.9rem;">
                        {{ strtoupper(mb_substr(trim($__name),0,1,'UTF-8')) }}
                    </span>
                    <span class="fw-bold" style="font-size:1.1rem;">{{ Auth::user()->name ?? 'Usuario' }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg p-3" aria-labelledby="userDropdown" style="min-width: 270px;">
                    <li class="text-center mb-2">
                        @php($__name = Auth::user()->name ?? Auth::user()->username ?? 'U')
                        <span class="rounded-circle mb-2 d-inline-flex align-items-center justify-content-center bg-light text-primary fw-bold shadow" style="width:60px;height:60px; font-size:1.4rem;">
                            {{ strtoupper(mb_substr(trim($__name),0,1,'UTF-8')) }}
                        </span>
                        <div class="fw-bold" style="font-size:1.1rem;">{{ Auth::user()->name }}</div>
                        <div class="text-muted small mb-2">{{ Auth::user()->email }}</div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="mb-2">
                        <a class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" href="{{ route('perfil') }}" style="font-weight:500;">
                            <i class="fa fa-user"></i> Ver perfil
                        </a>
                    </li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100 d-flex align-items-center justify-content-center gap-2" style="font-weight:500;">
                                <i class="fa fa-sign-out-alt"></i> Cerrar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
    <style>
        .topbar .user .dropdown-toggle::after {
            margin-left: 0.5em;
        }
        .dropdown-menu {
            min-width: 180px;
        }
    </style>
        </div>
        <div style="display: flex; flex: 1; min-height: calc(100vh - 60px);">
            <div class="sidebar" id="sidebar">
                <ul>
                    <li><a href="/dashboard" class="{{ request()->is('dashboard') ? 'active' : '' }}"><i class="fa fa-home"></i> <span class="sidebar-label">Inicio</span></a></li>
                    <li><a href="/categorias" class="{{ request()->is('categorias*') ? 'active' : '' }}"><i class="fa fa-folder"></i> <span class="sidebar-label">Categorías</span></a></li>
                    <li><a href="/productos" class="{{ request()->is('productos*') ? 'active' : '' }}"><i class="fa fa-pills"></i> <span class="sidebar-label">Medicamentos</span></a></li>
                    <li><a href="{{ route('inventario.index') }}" class="{{ request()->is('inventario*') ? 'active' : '' }}"><i class="fa fa-warehouse"></i> <span class="sidebar-label">Inventario</span></a></li>
                    @if(Auth::user() && Auth::user()->role === 'admin')
                        <li><a href="{{ route('usuarios.index') }}" class="{{ request()->is('usuarios*') ? 'active' : '' }}"><i class="fa fa-users"></i> <span class="sidebar-label">Usuarios</span></a></li>
                        <li><a href="{{ route('bitacora.index') }}" class="{{ request()->is('bitacora*') ? 'active' : '' }}"><i class="fa fa-book"></i> <span class="sidebar-label">Registro De Movimientos</span></a></li>
                    @endif
                   
                    <li><a href="{{ route('movimientos.index') }}" class="{{ request()->is('movimientos*') ? 'active' : '' }}"><i class="fa fa-exchange-alt"></i> <span class="sidebar-label">Movimientos</span></a></li>
                    <li><a href="{{ route('reportes.index') }}" class="{{ request()->is('reportes*') ? 'active' : '' }}"><i class="fa fa-chart-bar"></i> <span class="sidebar-label">Reportes</span></a></li>

                    <li>
                        <form action="{{ route('logout') }}" method="POST" style="display:inline; margin:0; padding:0;">
                            @csrf
                            <button type="submit" style="background:none; border:none; cursor:pointer; display:flex; align-items:center; gap:8px; color:inherit; font:inherit; padding:12px 24px; width:100%; text-align:left;">
                                <i class="fa fa-sign-out-alt"></i> <span class="sidebar-label">Cerrar sesión</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            <div class="main-content" id="main-content">
                @yield('content')
            </div>
        </div>
        <div class="footer">
            Copyright © 2025 - <a href="#">Sistemas inventario SERVICIOS MEDICOS</a>.
        </div>
    </div>
    {{-- JS ya incluido vía Vite (bootstrap bundle) --}}
</div>
    <!-- Contenedor global para notificaciones tipo toast -->
    <div id="toast-container" style="position:fixed;top:30px;right:30px;z-index:3000;"></div>
    <script>
    // Notificación tipo toast global
    window.showToast = function(msg, tipo='success') {
        const c = document.getElementById('toast-container');
        if (!c) return;
        const d = document.createElement('div');
        d.textContent = msg;
        d.setAttribute('role', 'alert');
        d.style.cssText = `background:${tipo==='success'?'#2176ae':'#e74c3c'};color:#fff;padding:.8rem 1rem;margin-bottom:.6rem;border-radius:8px;font-size:.85rem;font-weight:600;box-shadow:0 4px 14px -3px rgba(0,0,0,.25);opacity:0;transform:translateX(40px);transition:.35s;`;
        c.appendChild(d);
        requestAnimationFrame(()=>{d.style.opacity='1';d.style.transform='translateX(0)';});
        setTimeout(()=>{d.style.opacity='0';d.style.transform='translateX(40px)'; setTimeout(()=>d.remove(),400);},2700);
    }
    </script>
    @stack('modals')
    @stack('scripts')
</body>
</html>
