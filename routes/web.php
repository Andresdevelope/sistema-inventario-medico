<?php
use Illuminate\Support\Facades\Route;
// Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth');

Route::get('/', function () {
    return view('auth');
});

// Autenticación
Route::post('/register', [App\Http\Controllers\AuthController::class, 'register'])->name('register');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('login');

// Recuperación de contraseña
Route::get('/recover', function () {
    return view('recover');
});
Route::post('/recover/check-email', [App\Http\Controllers\RecoverController::class, 'checkEmail']);
Route::post('/recover/check-security', [App\Http\Controllers\RecoverController::class, 'checkSecurity']);
Route::post('/recover/change-password', [App\Http\Controllers\RecoverController::class, 'changePassword']);
