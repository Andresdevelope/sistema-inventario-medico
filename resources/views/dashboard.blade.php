@extends('layouts.dashboard')

@section('content')
    <div style="margin-bottom: 1.5rem;">
        <div style="background: #e3f4fd; color: #2176ae; padding: 1rem 1.5rem; border-radius: 7px; font-size: 1.1rem; display: flex; align-items: center;">
            <i class="fa fa-user" style="margin-right: 10px;"></i>
            Bienvenido <b style="margin: 0 5px;">{{ Auth::user()->name ?? 'Usuario' }}</b> a la aplicación de inventario de medicamentos.
        </div>
    </div>
    <div class="cards">
        <div class="card blue">
            <span class="count">{{ $totalCategorias ?? '-' }}</span>
            <span class="label">Categorías</span>
            <i class="fa fa-folder icon-bg"></i>
            <a href="/categorias" style="margin-top:auto; width:100%; text-align:center; color:#fff; background:none; border:none; font-size:1.08rem; text-decoration:underline; font-weight:bold; cursor:pointer; padding:0.7rem 0; border-radius:0 0 8px 8px; transition:color 0.2s;" onmouseover="this.style.color='#1eb6e7'" onmouseout="this.style.color='#fff'">Ver categorías</a>
        </div>
        <div class="card green">
            <span class="count">-</span>
            <span class="label">Productos</span>
            <i class="fa fa-pills icon-bg"></i>
            <a href="#" style="margin-top:auto; width:100%; text-align:center; color:#fff; background:none; border:none; font-size:1.08rem; text-decoration:underline; font-weight:bold; cursor:pointer; padding:0.7rem 0; border-radius:0 0 8px 8px; transition:color 0.2s;" onmouseover="this.style.color='#1abc9c'" onmouseout="this.style.color='#fff'">Ver productos</a>
        </div>
        <div class="card orange">
            <span class="count">-</span>
            <span class="label">Movimientos</span>
            <i class="fa fa-exchange-alt icon-bg"></i>
            <a href="#" style="margin-top:auto; width:100%; text-align:center; color:#fff; background:none; border:none; font-size:1.08rem; text-decoration:underline; font-weight:bold; cursor:pointer; padding:0.7rem 0; border-radius:0 0 8px 8px; transition:color 0.2s;" onmouseover="this.style.color='#f39c12'" onmouseout="this.style.color='#fff'">Ver movimientos</a>
        </div>
        <div class="card red">
            <span class="count">-</span>
            <span class="label">Inventario</span>
            <i class="fa fa-warehouse icon-bg"></i>
            <a href="#" style="margin-top:auto; width:100%; text-align:center; color:#fff; background:none; border:none; font-size:1.08rem; text-decoration:underline; font-weight:bold; cursor:pointer; padding:0.7rem 0; border-radius:0 0 8px 8px; transition:color 0.2s;" onmouseover="this.style.color='#e74c3c'" onmouseout="this.style.color='#fff'">Ver inventario</a>
        </div>
        <div class="card" style="background:#31404b; color:#fff;">
            <span class="count">-</span>
            <span class="label">Usuarios</span>
            <i class="fa fa-users icon-bg"></i>
            <a href="#" style="margin-top:auto; width:100%; text-align:center; color:#fff; background:none; border:none; font-size:1.08rem; text-decoration:underline; font-weight:bold; cursor:pointer; padding:0.7rem 0; border-radius:0 0 8px 8px; transition:color 0.2s;" onmouseover="this.style.color='#31404b'" onmouseout="this.style.color='#fff'">Ver usuarios</a>
        </div>
        <div class="card" style="background:#6c3483; color:#fff;">
            <span class="count">-</span>
            <span class="label">Reportes</span>
            <i class="fa fa-chart-bar icon-bg"></i>
            <a href="#" style="margin-top:auto; width:100%; text-align:center; color:#fff; background:none; border:none; font-size:1.08rem; text-decoration:underline; font-weight:bold; cursor:pointer; padding:0.7rem 0; border-radius:0 0 8px 8px; transition:color 0.2s;" onmouseover="this.style.color='#6c3483'" onmouseout="this.style.color='#fff'">Ver reportes</a>
        </div>
        <div class="card" style="background:#117864; color:#fff;">
            <span class="count">-</span>
            <span class="label">Cambiar contraseña</span>
            <i class="fa fa-key icon-bg"></i>
            <a href="#" style="margin-top:auto; width:100%; text-align:center; color:#fff; background:none; border:none; font-size:1.08rem; text-decoration:underline; font-weight:bold; cursor:pointer; padding:0.7rem 0; border-radius:0 0 8px 8px; transition:color 0.2s;" onmouseover="this.style.color:'#117864'" onmouseout="this.style.color='#fff'">Cambiar contraseña</a>
        </div>
    </div>
@endsection
