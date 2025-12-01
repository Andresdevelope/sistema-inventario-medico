
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\SubcategoriaController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Auth;

// ================= DASHBOARD =================
Route::get('/dashboard', function () {
    $totalCategorias = \App\Models\Categoria::count();
    $totalProductos = \App\Models\Producto::count();
    return view('dashboard', compact('totalCategorias', 'totalProductos'));
})->middleware('auth');

// ================= GESTIÓN DE USUARIOS (SOLO ADMIN) =================
use App\Http\Controllers\UserController;
Route::middleware(['auth', 'is_admin'])->group(function () {
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}/edit', [UserController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{id}', [UserController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    // Nueva ruta para AJAX
    Route::get('/usuarios-lista', [UserController::class, 'listaAjax'])->name('usuarios.listaAjax');
    // Bitácora
    Route::get('/bitacora', [\App\Http\Controllers\BitacoraController::class, 'index'])->name('bitacora.index');
});

// ================= AUTENTICACIÓN =================
Route::get('/', function () {
    return view('auth');
});
Route::post('/register', [App\Http\Controllers\AuthController::class, 'register'])->name('register');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('login.post');
Route::get('/login', function () {
    return view('auth');
})->name('login');

// ================= PERFIL DE USUARIO =================
Route::post('/perfil/validar-seguridad', [App\Http\Controllers\PerfilController::class, 'validarSeguridad'])->name('perfil.validarSeguridad')->middleware('auth');
Route::get('/perfil', function () {
    return view('perfil');
})->name('perfil')->middleware('auth');
Route::post('/perfil/cambiar-contrasena', [App\Http\Controllers\PerfilController::class, 'cambiarContrasena'])->name('cambiar.contrasena')->middleware('auth');

// ================= RECUPERACIÓN DE CONTRASEÑA =================
Route::get('/recover', function () {
    return view('recover');
});
Route::post('/recover/check-email', [App\Http\Controllers\RecoverController::class, 'checkEmail']);
Route::post('/recover/check-security', [App\Http\Controllers\RecoverController::class, 'checkSecurity']);
Route::post('/recover/change-password', [App\Http\Controllers\RecoverController::class, 'changePassword']);

// ================= CATEGORÍAS Y SUBCATEGORÍAS =================
Route::get('/categorias', function() {
    return view('layouts.categoria.categoria');
})->middleware('auth')->name('categorias');
Route::resource('categorias', CategoriaController::class)->middleware('auth');
Route::resource('subcategorias', SubcategoriaController::class)->middleware('auth');
Route::get('categorias-listar', [App\Http\Controllers\CategoriaController::class, 'listar'])->middleware('auth');
// Endpoint AJAX para subcategorías por categoría
Route::get('/subcategorias/by-categoria/{id}', [App\Http\Controllers\CategoriaController::class, 'subcategoriasPorCategoria'])->middleware('auth');
// Ruta para actualizar subcategoría individualmente
Route::put('/subcategorias/{id}', [CategoriaController::class, 'updateSubcategoria']);



// ================= PRODUCTOS O MEDICAMENTOS =================
Route::resource('productos', ProductoController::class)->middleware('auth');

// ================= INVENTARIO (placeholder) =================
Route::get('/inventario', [\App\Http\Controllers\InventarioController::class, 'index'])->middleware('auth')->name('inventario.index');
Route::get('/inventario/export', [\App\Http\Controllers\InventarioController::class, 'export'])->middleware('auth')->name('inventario.export');

// ================= MOVIMIENTOS (placeholder) =================
Route::get('/movimientos', [\App\Http\Controllers\MovimientosController::class, 'index'])->middleware('auth')->name('movimientos.index');
Route::post('/movimientos', [\App\Http\Controllers\MovimientosController::class, 'store'])->middleware('auth')->name('movimientos.store');

// ================= REPORTES (MVP) =================
Route::get('/reportes', [\App\Http\Controllers\ReportesController::class, 'index'])->middleware('auth')->name('reportes.index');
Route::get('/reportes/export-csv', [\App\Http\Controllers\ReportesController::class, 'exportCsv'])->middleware('auth')->name('reportes.export.csv');

// ================= NOTIFICACIONES (campana) =================
Route::get('/notificaciones/movimientos', [\App\Http\Controllers\NotificacionesController::class, 'movimientos'])->middleware('auth')->name('notificaciones.movimientos');
Route::post('/notificaciones/movimientos/leer', [\App\Http\Controllers\NotificacionesController::class, 'leerMovimientos'])->middleware('auth')->name('notificaciones.movimientos.leer');

// ================= PROVEEDORES (AJAX) =================
Route::prefix('proveedores/ajax')->name('proveedores.ajax')->group(function() {
    Route::post('/', [App\Http\Controllers\ProveedorController::class, 'storeAjax']);
    Route::put('/{id}', [App\Http\Controllers\ProveedorController::class, 'updateAjax'])->name('.update');
    Route::delete('/{id}', [App\Http\Controllers\ProveedorController::class, 'destroyAjax'])->name('.destroy');
});
// ================= SESIÓN =================
Route::post('/logout', function () {
    // Registrar en bitácora antes de cerrar sesión
    try { if (Auth::check()) { \App\Models\Bitacora::create(['user_id'=>Auth::id(),'accion'=>'auth.logout','detalles'=>null,'fecha_hora'=>now()]); } } catch (\Throwable $e) {}
    Auth::logout();
    return redirect('/login')->with('success', 'Sesión cerrada correctamente');
})->name('logout');
// Redirección amigable si alguien accede por GET a /logout
Route::get('/logout', function () {
    return redirect('/login');
});
