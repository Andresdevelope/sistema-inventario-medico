
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

// ================= AUTENTICACIÓN =================
Route::get('/', function () {
    return view('auth.auth');
});
Route::post('/register', [App\Http\Controllers\AuthController::class, 'register'])->name('register');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('login.post');
Route::get('/login', function () {
    return view('auth.auth');
})->name('login');

// ================= PERFIL DE USUARIO =================
Route::post('/perfil/validar-seguridad', [App\Http\Controllers\PerfilController::class, 'validarSeguridad'])->name('perfil.validarSeguridad')->middleware('auth');
Route::get('/perfil', function () {
    return view('auth.perfil');
})->name('perfil')->middleware('auth');
Route::post('/perfil/cambiar-contrasena', [App\Http\Controllers\PerfilController::class, 'cambiarContrasena'])->name('cambiar.contrasena')->middleware('auth');

// ================= RECUPERACIÓN DE CONTRASEÑA =================
Route::get('/recover', function () {
    return view('auth.recover');
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
// ================= PROVEEDORES =================
// Endpoint AJAX para editar proveedor desde modal
Route::put('/proveedores/ajax/{id}', [App\Http\Controllers\ProveedorController::class, 'updateAjax'])->name('proveedores.ajax.update');
// Endpoint AJAX para eliminar proveedor desde modal
Route::delete('/proveedores/ajax/{id}', [App\Http\Controllers\ProveedorController::class, 'destroyAjax'])->name('proveedores.ajax.destroy');


// Endpoint AJAX para generar código candidato basado en nombre
Route::post('/productos/generar-codigo', [ProductoController::class, 'generarCodigo'])->middleware('auth')->name('productos.generarCodigo');

// ================= PRODUCTOS O MEDICAMENTOS =================
Route::resource('productos', ProductoController::class)->middleware('auth');

// ================= PROVEEDORES =================
// Endpoint AJAX para crear proveedor desde modal
Route::post('/proveedores/ajax', [App\Http\Controllers\ProveedorController::class, 'storeAjax'])->name('proveedores.ajax');
Route::put('/proveedores/ajax/{id}', [App\Http\Controllers\ProveedorController::class, 'updateAjax'])->name('proveedores.ajax.update');
Route::delete('/proveedores/ajax/{id}', [App\Http\Controllers\ProveedorController::class, 'destroyAjax'])->name('proveedores.ajax.destroy');
// ================= SESIÓN =================
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login')->with('success', 'Sesión cerrada correctamente');
})->name('logout');
// Redirección amigable si alguien accede por GET a /logout
Route::get('/logout', function () {
    return redirect('/login');
});
