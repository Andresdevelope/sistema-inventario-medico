# Guía de Despliegue a Producción (Laravel)

> Objetivo: tener un flujo repetible y seguro para desplegar en servidor usando PowerShell.

## 1) Flujo general

1. Desarrollar y probar en local.
2. Hacer commit y push a GitHub.
3. En el servidor: traer cambios, instalar/actualizar dependencias, compilar assets, migrar y optimizar.
4. Verificar salud funcional (login, dashboard, inventario, movimientos, reportes).

---

## 2) Pre-requisitos del servidor

- PHP 8.2+
- Composer
- Node.js + npm
- Motor de BD (MySQL/MariaDB)
- Servidor web (Nginx/Apache) apuntando a `public/`
- Certificado SSL (HTTPS)
- Permisos de escritura en `storage/` y `bootstrap/cache/`

---

## 3) Primera instalación en servidor

1. Clonar repositorio.
2. Copiar entorno de producción:
   - usar `.env.production.example` como base para crear `.env`.
3. Configurar variables reales en `.env`:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL`
   - credenciales DB, SMTP, reCAPTCHA
4. Generar clave de app (si no existe):
   - `php artisan key:generate --force`
5. Instalar dependencias:
   - `composer install --no-dev --optimize-autoloader`
   - `npm ci`
6. Build frontend:
   - `npm run build`
7. Migrar BD:
   - `php artisan migrate --force`
8. Link de storage (si aplica):
   - `php artisan storage:link`
9. Optimizar:
   - `composer run prod:optimize`

---

## 4) Despliegue de actualizaciones (rutina)

Cada vez que subas cambios a GitHub:

1. Traer cambios:
   - `git pull origin new-logica-dist-salida`
2. Dependencias PHP (si cambió `composer.lock`):
   - `composer install --no-dev --optimize-autoloader`
3. Dependencias frontend (si cambió `package-lock.json`):
   - `npm ci`
4. Build frontend:
   - `npm run build`
5. Migraciones + optimización:
   - `composer run prod:release`

> `prod:release` ejecuta migraciones forzadas y luego cachea config, rutas, vistas y eventos.

---

## 5) Verificación post-deploy (checklist)

- [ ] `php artisan about` sin errores.
- [ ] Login funciona.
- [ ] Dashboard carga conteos.
- [ ] CRUD de productos funciona.
- [ ] Movimientos de inventario funcionan.
- [ ] Reportes generan sin error.
- [ ] No hay errores críticos en `storage/logs/laravel.log`.

---

## 6) Recomendaciones operativas

- Configurar worker de colas persistente (`php artisan queue:work --tries=3 --timeout=90`) con Supervisor/systemd/PM2.
- Configurar scheduler (cron cada minuto):
  - `php artisan schedule:run`
- Respaldos automáticos de BD y archivos críticos.
- Monitoreo básico (CPU/RAM/disco/logs).

---

## 7) Rollback rápido (si algo sale mal)

1. Volver al commit anterior estable.
2. Ejecutar:
   - `composer install --no-dev --optimize-autoloader`
   - `npm ci`
   - `npm run build`
   - `php artisan migrate --force` (solo si el rollback lo requiere)
   - `composer run prod:optimize`

---

## 8) Notas específicas de este proyecto

- El modelo debe mantenerse como `App\\Models\\Inventario` y archivo `app/Models/Inventario.php`.
- Evitar referencias en minúscula por consistencia PSR-4 y despliegues Linux.
