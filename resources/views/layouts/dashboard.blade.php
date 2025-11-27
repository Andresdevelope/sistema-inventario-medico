{{-- Layout Dashboard - Diseño 3: Slate Accent
    Este archivo define la estructura principal del dashboard para el sistema de inventario médico.
    Incluye topbar, sidebar, área de contenido, accesos rápidos, notificaciones y utilidades globales.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    {{-- Metadatos y configuración global --}}
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>SERVICIOS MEDICOS - Dashboard</title>
    {{-- Carga de estilos y scripts principales con Vite --}}
    @vite(['resources/css/app.css','resources/js/app.js'])
        {{--
                Solución al bug de parpadeo del tema claro/oscuro:
                Se aplica la clase 'theme-light' directamente en <head> mediante un script inline,
                antes de que se renderice el contenido visual. Esto asegura que el fondo claro
                se muestre instantáneamente si el usuario tiene el modo claro guardado en localStorage,
                evitando el flash de fondo oscuro al navegar entre secciones o recargar.
                La clase se aplica tanto a <html> como a <body> para máxima compatibilidad CSS.
        --}}
        <script>
        // Aplicar tema claro/oscuro ANTES del renderizado visual para evitar parpadeo
        (function(){
            try {
                var mode = localStorage.getItem('dashTheme');
                if(mode==='light') {
                    document.documentElement.classList.add('theme-light');
                    document.body.classList.add('theme-light');
                }
            } catch(e){}
        })();
        </script>
    <style>
        /*
            Variables CSS para paleta Slate Accent y layout general
            - Usar :root para definir colores, radios, sombras y animaciones
        */
        :root {
            --slate-bg:#10151B; /* fondo general */
            --slate-surface:#182129; /* paneles */
            --slate-surface-soft:#1F2933; /* hover suavizado */
            --slate-border:#2A3742; /* bordes */
            --slate-line:#33424E; /* divisores */
            --accent:#FF6A17; /* acento principal */
            --accent-soft:#FFA057; /* acento hover */
            --txt:#F5F7FA; /* texto principal */
            --txt-sec:#C4CFD6; /* texto secundario */
            --txt-dim:#7D888F; /* texto tenue */
            --r-sm:6px; --r-md:10px; --r-lg:18px; --speed:160ms cubic-bezier(.25,.4,.25,1);
            --shadow-deep:0 8px 28px -6px rgba(0,0,0,.45);
        }
        * { box-sizing:border-box; }
        body { margin:0; font-family:'Inter','Roboto',Arial,sans-serif; background:var(--slate-bg); color:var(--txt); min-height:100vh; -webkit-font-smoothing:antialiased; font-size:14px; }
        /* Tema claro alternativo */
        .theme-light { --slate-bg:#F5F7FA; --slate-surface:#FFFFFF; --slate-surface-soft:#F0F3F6; --slate-border:#D9E0E6; --slate-line:#CBD4DC; --txt:#1A1F24; --txt-sec:#4B5A65; --txt-dim:#7A8690; --shadow-deep:0 8px 28px -6px rgba(120,140,160,.25); }
        a { text-decoration:none; color:inherit; }
        .layout-shell { display:flex; min-height:100vh; flex-direction:column; }
        /*
            Topbar: barra superior fija con logo, acciones y menú de usuario
        */
        .topbar { display:flex; align-items:center; height:66px; padding:0 2rem; background:var(--slate-surface); border-bottom:1px solid var(--slate-line); position:sticky; top:0; z-index:1200; }
        .topbar .logo { font-weight:700; font-size:1.15rem; letter-spacing:.6px; display:flex; align-items:center; gap:.65rem; }
        .topbar .logo i { color:var(--accent); font-size:1.3rem; }
        .topbar-actions { margin-left:auto; display:flex; align-items:center; gap:.9rem; }
        .icon-btn { width:40px; height:40px; border:1px solid var(--slate-border); background:var(--slate-surface-soft); color:var(--txt-sec); display:flex; align-items:center; justify-content:center; border-radius:var(--r-md); font-size:1.05rem; cursor:pointer; position:relative; transition:background var(--speed), color var(--speed), border-color var(--speed); user-select:none; }
        .icon-btn:hover { background:var(--slate-surface); color:var(--accent); border-color:var(--accent); }
        #notifCount { display:none; position:absolute; top:4px; right:4px; background:var(--accent); color:#fff; font-size:.55rem; padding:2px 5px; border-radius:10px; font-weight:600; }
        .user-pill { display:flex; align-items:center; gap:.55rem; background:var(--slate-surface-soft); border:1px solid var(--slate-border); padding:.45rem .9rem; border-radius:999px; font-size:.7rem; cursor:pointer; color:var(--txt-sec); transition:background var(--speed), border-color var(--speed); }
        .user-pill:hover { background:var(--slate-surface); border-color:var(--accent); }
        .user-pill .avatar { width:34px; height:34px; border-radius:50%; background:var(--slate-line); display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:600; letter-spacing:.5px; }
        /*
            Panel de notificaciones desplegable
        */
        .notif-wrapper { position:relative; }
        .notif-panel { position:absolute; right:0; top:50px; width:330px; background:var(--slate-surface); border:1px solid var(--slate-border); border-radius:var(--r-lg); padding:.75rem .75rem .85rem; box-shadow:var(--shadow-deep); display:none; }
        .notif-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem; }
        .notif-header .title { font-size:.65rem; font-weight:700; letter-spacing:.5px; display:flex; align-items:center; gap:.4rem; color:var(--txt); }
        .notif-header .title i { color:var(--accent); }
        .btn-mark { background:var(--accent); border:none; color:#fff; font-size:.55rem; padding:4px 10px; border-radius:999px; font-weight:600; letter-spacing:.4px; cursor:pointer; }
        .notif-items { max-height:250px; overflow-y:auto; font-size:.6rem; }
        .notif-empty { display:none; text-align:center; padding:.8rem 0; font-size:.6rem; color:var(--txt-dim); }
        /*
            Layout principal: sidebar y área de contenido
        */
        .main-layout { display:flex; flex:1; min-height:calc(100vh - 66px); }
        .sidebar { width:230px; background:var(--slate-surface); border-right:1px solid var(--slate-line); display:flex; flex-direction:column; padding:1.2rem 0 .8rem 0; }
        .nav-section { padding:0 1.1rem; }
        .nav-title { font-size:.55rem; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--txt-dim); margin:0 0 .55rem .2rem; }
        .nav-list { list-style:none; margin:0; padding:0; }
        .nav-list li { margin-bottom:.35rem; }
        .nav-list a { display:flex; align-items:center; gap:.75rem; padding:.65rem .8rem; font-size:.7rem; font-weight:600; border-radius:var(--r-md); color:var(--txt-sec); position:relative; transition:background var(--speed), color var(--speed); }
        .nav-list a i { font-size:.95rem; color:var(--accent); width:18px; text-align:center; }
        .nav-list a:hover { background:var(--slate-surface-soft); color:var(--txt); }
        .nav-list a.active { background:var(--slate-bg); color:var(--txt); box-shadow:inset 0 0 0 1px var(--slate-border); }
        .logout-box { margin-top:auto; padding:1rem 1.1rem 0 1.1rem; }
        .logout-box button { width:100%; background:var(--accent); border:none; color:#fff; font-size:.65rem; font-weight:600; padding:.6rem .9rem; border-radius:var(--r-md); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:.45rem; }
        .logout-box button:hover { background:var(--accent-soft); }
        .content-area { flex:1; padding:1.7rem 2.1rem 3.8rem 2.1rem; font-size:14px; }
        .content-wrapper { max-width:1280px; margin:0 auto; }
        /*
            Dashboard: bienvenida, accesos rápidos y badges
        */
        .quick-strip { display:flex; align-items:center; justify-content:space-between; background:var(--slate-surface); border:1px solid var(--slate-border); border-radius:var(--r-lg); padding:1rem 1.2rem; margin-bottom:1.3rem; position:relative; }
        .strip-toggle { position:static !important; margin-bottom:10px; float:right; width:28px; height:28px; display:flex; align-items:center; justify-content:center; background:var(--slate-surface-soft); border:1px solid var(--slate-border); border-radius:8px; cursor:pointer; font-size:.75rem; color:var(--txt-dim); transition:background var(--speed), color var(--speed); }
        .strip-toggle:hover { background:var(--slate-surface); color:var(--accent); }
        .qs-left { display:flex; align-items:center; gap:.85rem; }
        .qs-avatar { width:46px; height:46px; border-radius:50%; background:var(--slate-line); display:flex; align-items:center; justify-content:center; font-size:.85rem; font-weight:700; }
        .qs-text .qs-title { font-size:.8rem; font-weight:700; letter-spacing:.4px; }
        .qs-text .qs-sub { font-size:.58rem; color:var(--txt-dim); margin-top:.25rem; }
        .qs-role { background:var(--accent); color:#fff; font-size:.55rem; padding:.4rem .7rem; border-radius:999px; font-weight:700; letter-spacing:.6px; }
        .tiles-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:.9rem; margin-bottom:1.2rem; transition:max-height var(--speed), opacity var(--speed); }
        .tiles-hidden { max-height:0 !important; opacity:0 !important; overflow:hidden; pointer-events:none; }
        .tile { background:var(--slate-surface); border:1px solid var(--slate-border); border-radius:var(--r-md); padding:.75rem .75rem .7rem .75rem; display:flex; flex-direction:column; gap:.45rem; font-size:.68rem; font-weight:600; color:var(--txt); position:relative; transition:border-color var(--speed), background var(--speed); }
        .tile .badge-count { position:absolute; top:8px; right:8px; background:var(--accent); color:#fff; font-size:.55rem; padding:3px 6px; border-radius:10px; font-weight:700; letter-spacing:.4px; }
        .tile i { font-size:1.1rem; color:var(--accent); }
        .tile .t-label { font-size:.64rem; letter-spacing:.45px; }
        .tile::after { content:""; position:absolute; left:.75rem; right:.75rem; bottom:.55rem; height:2px; background:linear-gradient(90deg,var(--accent),transparent 70%); opacity:.5; }
        .tile:hover { background:var(--slate-surface-soft); border-color:var(--accent); }
        /*
            Footer: pie de página
        */
        .footer { height:40px; display:flex; align-items:center; justify-content:center; font-size:.6rem; color:var(--txt-dim); border-top:1px solid var(--slate-line); background:var(--slate-surface); }
        .footer a { color:var(--accent); font-weight:600; }
        .footer a:hover { color:var(--accent-soft); }
        /* Accesibilidad foco */
        a:focus-visible, button:focus-visible { outline:2px solid var(--accent); outline-offset:3px; }
        /*
            Responsividad para pantallas menores a 900px
        */
        @media (max-width:900px){ .sidebar{width:200px;} .content-area{padding:1.2rem 1.1rem 3.2rem 1.1rem;} .quick-strip{flex-direction:column; align-items:flex-start; gap:.8rem;} .topbar{padding:0 1rem;} }
    </style>
    @stack('styles')
</head>
<body>
{{--
    layout-shell: contenedor raíz del dashboard
--}}
<div class="layout-shell">
    {{-- Topbar: barra superior con logo, acciones y usuario --}}
    <div class="topbar" role="banner" aria-label="Barra superior">
        <div class="logo"><i class="fa-solid fa-capsules"></i> Servicios Médicos</div>
        <div class="topbar-actions" role="navigation" aria-label="Acciones de usuario">
            <button id="themeToggle" type="button" class="icon-btn" aria-label="Cambiar tema"><i class="fa fa-sun" id="themeIcon"></i></button>
            <div class="notif-wrapper">
                <button id="notifBell" type="button" class="icon-btn" aria-label="Abrir notificaciones" aria-haspopup="true" aria-expanded="false"><i class="fa fa-bell" aria-hidden="true"></i><span id="notifCount">0</span></button>
                <div id="notifPanel" class="notif-panel" aria-live="polite" aria-label="Panel de notificaciones">
                    <div class="notif-header">
                        <div class="title"><i class="fa fa-bell"></i> Movimientos</div>
                        <button id="notifMarkAll" type="button" class="btn-mark">Marcar</button>
                    </div>
                    <div id="notifItems" class="notif-items"></div>
                    <div id="notifEmpty" class="notif-empty">Sin movimientos registrados.</div>
                </div>
            </div>
            {{-- Menú de usuario desplegable --}}
            <div class="dropdown" aria-label="Menú de usuario">
                <a href="#" class="user-pill dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    @php($__name = Auth::user()->name ?? Auth::user()->username ?? 'U')
                    <span class="avatar">{{ strtoupper(mb_substr(trim($__name),0,1,'UTF-8')) }}</span>
                    <span class="name">{{ Auth::user()->name ?? 'Usuario' }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow p-3" aria-labelledby="userDropdown" style="min-width:240px; font-size:.7rem;">
                    <li class="text-center mb-2">
                        @php($__name = Auth::user()->name ?? Auth::user()->username ?? 'U')
                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:58px;height:58px; background:var(--slate-line); color:#fff; font-size:1.15rem; font-weight:700;">{{ strtoupper(mb_substr(trim($__name),0,1,'UTF-8')) }}</span>
                        <div class="fw-bold mt-2" style="font-size:.8rem;">{{ Auth::user()->name }}</div>
                        <div class="text-muted" style="font-size:.55rem;">{{ Auth::user()->email }}</div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="mb-2"><a class="btn w-100 d-flex align-items-center justify-content-center gap-2" href="{{ route('perfil') }}" style="background:var(--accent); color:#fff; font-weight:600; font-size:.6rem; border-radius:var(--r-md);"><i class="fa fa-user"></i> Perfil</a></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn w-100 d-flex align-items-center justify-content-center gap-2" style="background:#e74c3c; color:#fff; font-weight:600; font-size:.6rem; border-radius:var(--r-md);"><i class="fa fa-sign-out-alt"></i> Salir</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    {{-- Main layout: sidebar y área de contenido --}}
    <div class="main-layout">
        {{-- Sidebar: menú lateral de navegación y logout --}}
        <aside class="sidebar" role="navigation" aria-label="Menú lateral">
            <div class="nav-section">
                <div class="nav-title">Navegación</div>
                <ul class="nav-list">
                    <li><a href="/dashboard" class="{{ request()->is('dashboard') ? 'active' : '' }}"><i class="fa fa-home"></i>Inicio</a></li>
                    <li><a href="/categorias" class="{{ request()->is('categorias*') ? 'active' : '' }}"><i class="fa fa-folder"></i>Categorías</a></li>
                    <li><a href="/productos" class="{{ request()->is('productos*') ? 'active' : '' }}"><i class="fa fa-pills"></i>Medicamentos</a></li>
                    <li><a href="{{ route('inventario.index') }}" class="{{ request()->is('inventario*') ? 'active' : '' }}"><i class="fa fa-warehouse"></i>Inventario</a></li>
                    <li><a href="{{ route('movimientos.index') }}" class="{{ request()->is('movimientos*') ? 'active' : '' }}"><i class="fa fa-exchange-alt"></i>Movimientos</a></li>
                    <li><a href="{{ route('reportes.index') }}" class="{{ request()->is('reportes*') ? 'active' : '' }}"><i class="fa fa-chart-bar"></i>Reportes</a></li>
                    @if(Auth::user() && Auth::user()->role === 'admin')
                        <li><a href="{{ route('usuarios.index') }}" class="{{ request()->is('usuarios*') ? 'active' : '' }}"><i class="fa fa-users"></i>Usuarios</a></li>
                        <li><a href="{{ route('bitacora.index') }}" class="{{ request()->is('bitacora*') ? 'active' : '' }}"><i class="fa fa-book"></i>Registro</a></li>
                    @endif
                </ul>
            </div>
            <div class="logout-box">
                <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit"><i class="fa fa-sign-out-alt"></i>Salir</button></form>
            </div>
        </aside>
        {{-- Área de contenido principal --}}
        <section class="content-area" role="main">
            <div class="content-wrapper">
            {{--
                Dashboard: bienvenida, accesos rápidos y badges de conteo
                Solo se muestra en la ruta /dashboard
            --}}
            @if(request()->is('dashboard'))
                <button class="strip-toggle" id="toggleTiles" aria-expanded="true" aria-controls="tilesGrid" title="Ocultar accesos">−</button>
                <div class="quick-strip" aria-label="Bienvenida" id="quickStrip">
                    <div class="qs-left">
                        @php($__name = Auth::user()->name ?? Auth::user()->username ?? 'U')
                        <div class="qs-avatar">{{ strtoupper(mb_substr(trim($__name),0,1,'UTF-8')) }}</div>
                        <div class="qs-text">
                            <div class="qs-title">Hola, {{ Auth::user()->name ?? 'Usuario' }}</div>
                            <div class="qs-sub">Resumen rápido de módulos</div>
                        </div>
                    </div>
                    <div><span class="qs-role">{{ strtoupper(Auth::user()->role ?? 'USUARIO') }}</span></div>
                </div>
                <div class="tiles-grid" aria-label="Accesos rápidos" id="tilesGrid">
                    {{-- Conteos dinámicos de modelos y tablas --}}
                    @php($countCategorias = \App\Models\Categoria::count())
                    @php($countProductos = \App\Models\Producto::count())
                    @php($countInventario = class_exists('App\\Models\\inventario') ? \App\Models\inventario::count() : 0)
                    @php($countMovimientos = DB::table('movimientos')->count())
                    @php($countReportes = DB::table('reportes')->count())
                    @php($countUsuarios = DB::table('users')->count())
                    @php($countBitacora = DB::table('bitacora')->count())
                    <a href="/dashboard" class="tile" aria-label="Inicio"><i class="fa fa-home"></i><span class="t-label">Inicio</span></a>
                    <a href="/categorias" class="tile" aria-label="Categorías"><span class="badge-count">{{ $countCategorias }}</span><i class="fa fa-folder"></i><span class="t-label">Categorías</span></a>
                    <a href="/productos" class="tile" aria-label="Medicamentos"><span class="badge-count">{{ $countProductos }}</span><i class="fa fa-pills"></i><span class="t-label">Medicamentos</span></a>
                    <a href="{{ route('inventario.index') }}" class="tile" aria-label="Inventario"><span class="badge-count">{{ $countInventario }}</span><i class="fa fa-warehouse"></i><span class="t-label">Inventario</span></a>
                    <a href="{{ route('movimientos.index') }}" class="tile" aria-label="Movimientos"><span class="badge-count">{{ $countMovimientos }}</span><i class="fa fa-exchange-alt"></i><span class="t-label">Movimientos</span></a>
                    <a href="{{ route('reportes.index') }}" class="tile" aria-label="Reportes"><span class="badge-count">{{ $countReportes }}</span><i class="fa fa-chart-bar"></i><span class="t-label">Reportes</span></a>
                    @if(Auth::user() && Auth::user()->role === 'admin')
                        <a href="{{ route('usuarios.index') }}" class="tile" aria-label="Usuarios"><span class="badge-count">{{ $countUsuarios }}</span><i class="fa fa-users"></i><span class="t-label">Usuarios</span></a>
                        <a href="{{ route('bitacora.index') }}" class="tile" aria-label="Registro"><span class="badge-count">{{ $countBitacora }}</span><i class="fa fa-book"></i><span class="t-label">Registro</span></a>
                    @endif
                </div>
            @endif
            {{--
                Aquí se inyecta el contenido de cada vista hija con @yield('content')
            --}}
            @yield('content')
            </div>
        </section>
    </div>
    {{-- Footer: pie de página --}}
    <div class="footer">© 2025 - <a href="#">Sistemas inventario SERVICIOS MEDICOS</a></div>
</div>
{{-- Toast global para notificaciones --}}
<div id="toast-container" style="position:fixed;top:30px;right:30px;z-index:3000;"></div>
<script>
// Utilidad para mostrar toasts globales
window.showToast = function(msg, tipo='success') {
    const c = document.getElementById('toast-container'); if(!c) return;
    const d=document.createElement('div'); d.textContent=msg; d.setAttribute('role','alert');
    d.style.cssText=`background:${tipo==='success'? 'var(--accent)':'#e74c3c'};color:#fff;padding:.75rem .95rem;margin-bottom:.55rem;border-radius:8px;font-size:.68rem;font-weight:600;box-shadow:0 4px 14px -3px rgba(0,0,0,.45);opacity:0;transform:translateY(-6px);transition:.35s;`;
    c.appendChild(d); requestAnimationFrame(()=>{d.style.opacity='1';d.style.transform='translateY(0)';});
    setTimeout(()=>{d.style.opacity='0';d.style.transform='translateY(-6px)'; setTimeout(()=>d.remove(),400);},2600);
}
// Toggle panel notificaciones y simulación de lista de movimientos
const bell=document.getElementById('notifBell'); const panel=document.getElementById('notifPanel'); if(bell&&panel){ bell.addEventListener('click',()=>{ const open=panel.style.display==='block'; panel.style.display=open?'none':'block'; bell.setAttribute('aria-expanded', open?'false':'true'); }); }
document.getElementById('notifMarkAll')?.addEventListener('click',()=>{ const items=document.getElementById('notifItems'); const empty=document.getElementById('notifEmpty'); const count=document.getElementById('notifCount'); if(items&&empty){ items.innerHTML=''; empty.style.display='block'; } if(count){ count.style.display='none'; count.textContent='0'; } });
// Sin simulación: el notificador solo mostrará datos reales cuando se integren
const items=document.getElementById('notifItems'); const empty=document.getElementById('notifEmpty'); const badge=document.getElementById('notifCount');
if(items && empty){ empty.style.display='block'; items.innerHTML=''; }
if(badge){ badge.style.display='none'; badge.textContent='0'; }
// Toggle de accesos rápidos (tiles)
const toggleTilesBtn=document.getElementById('toggleTiles'); const tilesGrid=document.getElementById('tilesGrid'); if(toggleTilesBtn&&tilesGrid){ toggleTilesBtn.addEventListener('click',()=>{ const hidden=tilesGrid.classList.toggle('tiles-hidden'); toggleTilesBtn.setAttribute('aria-expanded', hidden?'false':'true'); toggleTilesBtn.textContent= hidden? '+' : '−'; toggleTilesBtn.title = hidden? 'Mostrar accesos' : 'Ocultar accesos'; }); }
// Toggle de tema claro/oscuro
const themeBtn=document.getElementById('themeToggle');
const themeIcon=document.getElementById('themeIcon');
function applyTheme(mode){
    if(mode==='light'){
        document.documentElement.classList.add('theme-light');
        document.body.classList.add('theme-light');
        themeIcon.classList.remove('fa-sun');
        themeIcon.classList.add('fa-moon');
    } else {
        document.documentElement.classList.remove('theme-light');
        document.body.classList.remove('theme-light');
        themeIcon.classList.remove('fa-moon');
        themeIcon.classList.add('fa-sun');
    }
    localStorage.setItem('dashTheme', mode);
}
const stored=localStorage.getItem('dashTheme');
if(stored==='light'){
    document.documentElement.classList.add('theme-light');
    document.body.classList.add('theme-light');
}else{
    document.documentElement.classList.remove('theme-light');
    document.body.classList.remove('theme-light');
}
applyTheme(stored||'dark');
themeBtn?.addEventListener('click',()=>{
    const current=document.body.classList.contains('theme-light')?'light':'dark';
    applyTheme(current==='light'?'dark':'light');
    // Sincroniza ambas clases tras el cambio
    if(document.body.classList.contains('theme-light')){
        document.documentElement.classList.add('theme-light');
    }else{
        document.documentElement.classList.remove('theme-light');
    }
});
</script>
@stack('modals')
@stack('scripts')
</body>
</html>
