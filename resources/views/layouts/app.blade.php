<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('logouptag.png') }}">
    <title>Sistema Inventario Médico</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        #auth-loader-overlay{
            position:fixed;
            inset:0;
            background:rgba(7,7,12,.88);
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            gap:18px;
            color:#f9fafb;
            z-index:9999;
            font-family:'Montserrat','Inter',sans-serif;
            opacity:0;
            pointer-events:none;
            transition:opacity .3s ease;
        }
        #auth-loader-overlay.active{opacity:1;pointer-events:all;}
        .loader-ring{
            width:110px;
            height:110px;
            border-radius:50%;
            border:3px solid rgba(255,255,255,.15);
            border-top-color:#ff8c00;
            animation:spin 1.2s linear infinite;
            display:flex;
            align-items:center;
            justify-content:center;
            position:relative;
        }
        .loader-ring img{
            width:72px;
            height:72px;
            border-radius:20px;
            box-shadow:0 15px 45px rgba(0,0,0,.35);
            object-fit:cover;
        }
        .loader-text{font-size:1rem;font-weight:600;letter-spacing:.4px;text-transform:uppercase;}
        .loader-sub{font-size:.85rem;color:#cfd5e4;}
        @keyframes spin{to{transform:rotate(360deg);}}
    </style>
    @stack('styles')
</head>
<body>
    <div id="auth-loader-overlay" aria-hidden="true">
        <div class="loader-ring">
            <img src="{{ asset('logouptag.png') }}" alt="Logo Servicios Médicos">
        </div>
        <div class="loader-text">Validando tu acceso…</div>
        <div class="loader-sub">Preparando el panel principal</div>
    </div>
    @yield('content')
    @stack('scripts')
    <script>
        (function(){
            const overlay=document.getElementById('auth-loader-overlay');
            if(!overlay) return;
            window.showAuthLoader = function(){
                overlay.classList.add('active');
                overlay.setAttribute('aria-hidden','false');
            };
            window.hideAuthLoader = function(){
                overlay.classList.remove('active');
                overlay.setAttribute('aria-hidden','true');
            };
        })();
    </script>
</body>
</html>
