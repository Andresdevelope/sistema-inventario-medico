@extends('layouts.dashboard')

@section('content')
    @if(session('error'))
        <script>document.addEventListener('DOMContentLoaded',()=>{ if(window.showToast){ showToast("{{ addslashes(session('error')) }}","error"); } });</script>
    @endif
    {{-- Contenido principal del dashboard (analíticas, gráficas, etc.) se puede añadir aquí más adelante. --}}
@endsection
