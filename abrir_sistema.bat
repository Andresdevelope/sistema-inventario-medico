@echo off
title Lanzador de Sistema - Comunidad
echo Iniciando Servidores...

:: 1. Iniciar MySQL y Apache de XAMPP en segundo plano
start /b "" "C:\xampp\mysql\bin\mysqld.exe"
start /b "" "C:\xampp\apache\bin\httpd.exe"

echo Servidores de Base de Datos y Apache LISTOS.
echo.

:: 2. Entrar a la carpeta de tu proyecto Laravel
:: CAMBIA LA RUTA de abajo por la carpeta real de tu proyecto
cd /d "C:\Users\andres\Desktop\sistema_inventario_medico\laravel"

echo Iniciando Sistema Laravel...
echo NO CIERRES ESTA VENTANA MIENTRAS USES EL SISTEMA
echo Accede en tu navegador a: http://127.0.0.1:8000
echo.

:: 3. Abrir el navegador automáticamente
start http://127.0.0.1:8000

:: 4. Encender el servidor de Laravel
php artisan serve