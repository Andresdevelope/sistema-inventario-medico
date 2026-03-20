


# Sistema de Inventario Médico

Sistema web integral para la gestión de inventario, movimientos y reportes en servicios médicos, desarrollado por el equipo de proyecto como solución propia y original.

## Descripción

Esta aplicación permite administrar productos, medicamentos, insumos y movimientos por lotes, con trazabilidad completa, control de stock, reportes, exportaciones y gestión de usuarios y permisos. Incluye reglas de negocio específicas para el sector salud y controles de seguridad avanzados.

## Características principales

- Gestión de catálogo: categorías, subcategorías, productos y proveedores.
- Inventario por lotes, entradas, salidas, ajustes y trazabilidad.
- KPIs y reportes operativos, exportación CSV/PDF.
- Control de usuarios, roles y permisos granulares.
- Bitácora de auditoría y registro de acciones.
- Seguridad reforzada: hashing seguro, CSRF, validaciones, reCAPTCHA opcional.
- Interfaz moderna, responsive y preparada para uso offline.

## Documentación y soporte

Consulta la carpeta `docs/` para:
- Casos de uso y reglas de negocio (`docs/casos_de_uso.txt`)
- Guía de despliegue y producción (`docs/deploy_produccion.md`)
- Lógica de inventario y migraciones (`docs/actualizacion_logica_inventario.md`, `docs/migracion_lotes.md`)
- Guía de UX/UI y diagramas (`docs/guia_aplicacion_codigo_ux_ui.md`, `docs/diagrama_entidad_relacion.txt`)

## Instalación y primer uso

1. Clona el repositorio:
   ```
   git clone https://github.com/usuario/tu-repo.git
   cd tu-repo/laravel
   ```
2. Instala dependencias PHP:
   ```
   composer install
   ```
3. Copia el archivo de entorno y configura tus variables:
   ```
   cp .env.example .env
   ```
4. Genera la clave de la aplicación:
   ```
   php artisan key:generate
   ```
5. Ejecuta migraciones y seeders:
   ```
   php artisan migrate --seed
   ```
6. Instala dependencias frontend y compila assets:
   ```
   npm install && npm run build
   ```

> **IMPORTANTE:**  
> Cada vez que descargues el proyecto en un nuevo equipo, ejecuta `composer install` para que todas las librerías (como dompdf) se descarguen correctamente.

Más detalles y checklist de producción en `docs/deploy_produccion.md`.

## Despliegue, producción y funcionamiento offline

Consulta `docs/deploy_produccion.md` para checklist detallado. Resumen de pasos clave:

1. Copia `.env.production.example` a `.env` y configura variables reales (`APP_URL`, BD, SMTP, reCAPTCHA, etc.).
2. Instala dependencias optimizadas:
   - `composer install --no-dev --optimize-autoloader`
   - `npm ci && npm run build`
3. Migrar y cachear configuración:
   - `php artisan migrate --force`
   - `composer run prod:optimize`
4. Permisos y enlaces:
   - Asegura permisos de escritura en `storage/` y `bootstrap/cache/`.
   - Ejecuta `php artisan storage:link` si usas archivos públicos.
5. (Opcional) Worker de colas: `php artisan queue:work --tries=3 --timeout=90`

### Scripts incluidos para release
- `composer run prod:optimize`: limpia y regenera cachés de config, rutas, vistas y eventos.
- `composer run prod:release`: ejecuta migraciones y optimización.

### Funcionamiento offline
El sistema funciona sin conexión a internet: todos los estilos, fuentes e iconos están auto-hospedados. Ejecuta el build (`npm run build`) y limpia cachés para evitar parpadeos de diseño.

### reCAPTCHA v2 (opcional)
Puedes activar o desactivar reCAPTCHA en login/registro/recuperación según tu entorno:
- Actívalo en `.env` con `RECAPTCHA_ENABLED=true` y define tus claves.
- Desactívalo con `RECAPTCHA_ENABLED=false`.
Limpia la caché de configuración tras cualquier cambio: `php artisan config:clear`.

### Migraciones y reglas de negocio
Consulta `docs/actualizacion_logica_inventario.md` y `docs/guia_aplicacion_codigo_ux_ui.md` para detalles de reglas, migraciones y novedades de BD.

---

## Créditos y Licencia de Creación

Este sistema fue diseñado y desarrollado íntegramente por el siguiente equipo de proyecto:

- **Andres Rivero** (programador y desarrollador principal)
- **Jesus Morillo**
- **Angel Diaz**
- **Joswar Capielo**

Todos los derechos de diseño, código y documentación pertenecen a los autores mencionados. Queda prohibida la copia, redistribución o uso comercial sin autorización expresa del equipo creador.

© 2026 Equipo de Proyecto Servicios Médicos. Todos los derechos reservados.

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
