<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\SubcategoriaController;
use Illuminate\Support\Facades\Auth;
// Dashboard
Route::get('/dashboard', function () {
    $totalCategorias = \App\Models\Categoria::count();
    return view('dashboard', compact('totalCategorias'));
})->middleware('auth');

Route::get('/', function () {
    return view('auth');
});

// Autenticación
Route::post('/register', [App\Http\Controllers\AuthController::class, 'register'])->name('register');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::get('/login', function () {
    return view('auth');
})->name('login');

// Recuperación de contraseña
Route::get('/recover', function () {
    return view('recover');
});
Route::post('/recover/check-email', [App\Http\Controllers\RecoverController::class, 'checkEmail']);
Route::post('/recover/check-security', [App\Http\Controllers\RecoverController::class, 'checkSecurity']);
Route::post('/recover/change-password', [App\Http\Controllers\RecoverController::class, 'changePassword']);

// Categorías
Route::get('/categorias', function() {
    return view('categoria');
})->middleware('auth')->name('categorias');
Route::resource('categorias', CategoriaController::class)->middleware('auth');
Route::resource('subcategorias', SubcategoriaController::class)->middleware('auth');
Route::get('categorias-listar', [App\Http\Controllers\CategoriaController::class, 'listar'])->middleware('auth');

// Ruta para actualizar subcategoría individualmente
Route::put('/subcategorias/{id}', [CategoriaController::class, 'updateSubcategoria']);

// Cerrar sesión
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login')->with('success', 'Sesión cerrada correctamente');
})->name('logout');
// Redirección amigable si alguien accede por GET a /logout
Route::get('/logout', function () {
    return redirect('/login');
});
