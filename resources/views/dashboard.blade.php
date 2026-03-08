@extends('layouts.dashboard')

@section('content')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            try {
                const notice = sessionStorage.getItem('post_login_notice');
                if (notice && window.showToast) {
                    window.showToast(notice, 'success');
                    sessionStorage.removeItem('post_login_notice');
                }
            } catch (_) {}
        });
    </script>
    @if(session('error'))
        <script>document.addEventListener('DOMContentLoaded',()=>{ if(window.showToast){ showToast("{{ addslashes(session('error')) }}","error"); } });</script>
    @endif
    {{-- Contenido principal del dashboard (analíticas, gráficas, etc.) se puede añadir aquí más adelante. --}}
@endsection
