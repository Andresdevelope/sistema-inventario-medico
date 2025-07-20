<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SERVICIOS MEDICOS - Dashboard</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', Arial, sans-serif;
            background: #f3f6fa;
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
            width: 220px;
            background: #222e36;
            color: #fff;
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            padding-top: 1rem;
            z-index: 100;
            transition: width 0.2s;
            overflow-x: hidden;
            height: calc(100vh - 60px);
        }
        .sidebar.collapsed {
            width: 60px !important;
        }
        .sidebar.collapsed .sidebar-label {
            display: none;
        }
        .sidebar.collapsed ul li a {
            justify-content: center;
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
            margin-left: 220px;
            padding: 2rem;
            padding-top: 2.5rem;
            min-height: calc(100vh - 60px - 40px);
            transition: margin-left 0.2s;
            height: calc(100vh - 60px - 40px);
            overflow-y: auto;
        }
        .cards {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .card {
            flex: 1 1 220px;
            min-width: 220px;
            max-width: 260px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 1.5rem 1rem 1rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
            overflow: hidden;
        }
        .card .icon-bg {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 3.5rem;
            color: rgba(0,0,0,0.07);
        }
        .card .count {
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .card .label {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .card .action {
            margin-top: auto;
            width: 100%;
            display: block;
            color: #fff;
            background: #2176ae;
            border: none;
            border-radius: 0 0 8px 8px;
            padding: 0.7rem 0;
            font-size: 1.08rem;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(33,118,174,0.08);
        }
        .card .action:hover {
            background: #4093c7;
            color: #fff;
            text-decoration: none;
        }
        .card.blue { background: #1eb6e7; color: #fff; }
        .card.green { background: #1abc9c; color: #fff; }
        .card.orange { background: #f39c12; color: #fff; }
        .card.red { background: #e74c3c; color: #fff; }
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
            z-index: 99;
            box-shadow: 0 -2px 10px rgba(180,180,180,0.10);
            letter-spacing: 0.5px;
        }
        .footer a {
            color: #2176ae;
            text-decoration: underline;
            font-weight: bold;
        }
        @media (max-width: 900px) {
            .main-content { padding: 1rem; }
            .cards { flex-direction: column; gap: 1rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="topbar">
        <div class="logo">
            <i class="fa-solid fa-capsules"></i> Servicios Médicos 
        </div>
        <button id="sidebar-toggle" style="background:none;border:none;color:#fff;font-size:1.7rem;cursor:pointer;outline:none;display:none;margin-left:1rem;" aria-label="Menú">
            <i class="fa fa-bars"></i>
        </button>
        <div class="user">
            <i class="fa-solid fa-user-circle"></i> {{ Auth::user()->username ?? 'Usuario' }}
        </div>
    </div>
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="/dashboard" class="active"><i class="fa fa-home"></i> <span class="sidebar-label">Inicio</span></a></li>
            <li><a href="#"><i class="fa fa-folder"></i> <span class="sidebar-label">Categorías</span></a></li>
            <li><a href="#"><i class="fa fa-pills"></i> <span class="sidebar-label">Productos</span></a></li>
            <li><a href="#"><i class="fa fa-exchange-alt"></i> <span class="sidebar-label">Movimientos</span></a></li>
            <li><a href="#"><i class="fa fa-warehouse"></i> <span class="sidebar-label">Inventario</span></a></li>
            <li><a href="#"><i class="fa fa-users"></i> <span class="sidebar-label">Usuarios</span></a></li>
            <li><a href="#"><i class="fa fa-chart-bar"></i> <span class="sidebar-label">Reportes</span></a></li>
            <li><a href="#"><i class="fa fa-key"></i> <span class="sidebar-label">Cambiar contraseña</span></a></li>
            <li><a href="/logout"><i class="fa fa-sign-out-alt"></i> <span class="sidebar-label">Cerrar sesión</span></a></li>
        </ul>
    </div>
    <div class="main-content" id="main-content">
        @yield('content')
    </div>
    <div class="footer">
        Copyright © 2025 - <a href="#">Sistemas Web</a>.
    </div>
    @stack('scripts')
    <script>
        // Botón para colapsar/expandir sidebar
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const toggleBtn = document.getElementById('sidebar-toggle');
        function updateSidebar() {
            if(window.innerWidth <= 1100) {
                sidebar.classList.add('collapsed');
                mainContent.style.marginLeft = '60px';
                toggleBtn.style.display = 'inline-block';
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.style.marginLeft = '220px';
                toggleBtn.style.display = 'none';
            }
        }
        updateSidebar();
        window.addEventListener('resize', updateSidebar);
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            if(sidebar.classList.contains('collapsed')) {
                mainContent.style.marginLeft = '60px';
            } else {
                mainContent.style.marginLeft = '220px';
            }
        });
    </script>
</body>
</html>
