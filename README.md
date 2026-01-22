## Funcionamiento Offline y Build Optimizado

Este sistema está preparado para funcionar completamente sin conexión a internet, incluyendo todos los estilos, fuentes e iconos. Para asegurar la mejor experiencia y evitar parpadeos de diseño (FOUC), sigue estos pasos:

1. Instala las dependencias locales:
	```powershell
	npm install
	```
2. Genera los assets optimizados para producción:
	```powershell
	npm run build
	```
3. Limpia cachés de Laravel:
	```powershell
	php artisan view:clear
	php artisan config:clear
	php artisan cache:clear
	php artisan route:clear
	```
4. Inicia el servidor de Laravel:
	```powershell
	php artisan serve
	```
5. Accede a la app desde el navegador. Puedes desconectar internet y el sistema seguirá mostrando todos los estilos y fuentes correctamente.

**Notas:**
- No es necesario ejecutar `npm run dev` para producción o uso offline.
- Todos los recursos (Bootstrap, Font Awesome, fuentes) están auto-hospedados y no dependen de CDNs.
- Los avatares de usuario se generan localmente con iniciales, sin llamadas externas.

Si ves algún parpadeo de diseño, asegúrate de haber ejecutado el build y limpiado cachés.

## reCAPTCHA v2 (opcional)

Este proyecto integra reCAPTCHA v2 en login/registro/recuperación, pero puedes activarlo o desactivarlo según tengas conexión a internet:

- Activarlo (con internet):
	1. En `.env`, establece `RECAPTCHA_ENABLED=true`.
	2. Define tus claves: `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET`.
	3. Limpia la caché de configuración:
		 ```powershell
		 php artisan config:clear
		 ```
	4. Refresca la página. Verás el widget y el backend validará el token.

- Desactivarlo (sin internet):
	1. En `.env`, establece `RECAPTCHA_ENABLED=false`.
	2. Limpia la caché de configuración:
		 ```powershell
		 php artisan config:clear
		 ```
	3. Refresca la página. No se cargará el widget y el backend no exigirá token.

Notas:
- En entornos no producción (local/testing), si reCAPTCHA está habilitado pero la red falla, el sistema permite continuar para no bloquear el desarrollo.
- En producción, si falla la verificación por red, se retornará error para mantener seguridad.
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

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
