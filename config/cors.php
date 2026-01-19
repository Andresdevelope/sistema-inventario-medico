<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar CORS para responder a solicitudes cross-origin.
    | En producción, se recomienda restringir orígenes mediante variables .env.
    |
    */

    // Rutas aplicables (usar '*' si hay endpoints web que atienden XHR cross-origin)
    'paths' => ['*'],

    // Métodos permitidos
    'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')),

    // Orígenes permitidos (separados por coma). Ej: "https://inventario.example.com,https://admin.example.com"
    'allowed_origins' => array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '*'))),

    // Patrones de origen permitidos (opcional)
    'allowed_origins_patterns' => [],

    // Headers permitidos
    'allowed_headers' => array_map('trim', explode(',', env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With,X-XSRF-TOKEN'))),

    // Headers expuestos al navegador
    'exposed_headers' => [],

    // Cache de preflight (segundos)
    'max_age' => (int) env('CORS_MAX_AGE', 0),

    // Soporte de credenciales (cookies/autenticación). Requiere orígenes específicos, no '*'.
    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),
];
