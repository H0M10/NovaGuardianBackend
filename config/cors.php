<?php
/**
 * Configuración CORS
 * Permite peticiones desde el frontend
 */

return [
    // Orígenes permitidos
    'allowed_origins' => [
        '*', // Permitir todos los orígenes en desarrollo
        'null', // Permitir file:// en desarrollo
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:5500',
        'http://127.0.0.1',
        'http://127.0.0.1:5500',
        // Agregar tu dominio de GitHub Pages cuando lo tengas
        // 'https://tu-usuario.github.io'
    ],
    
    // Métodos HTTP permitidos
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    
    // Headers permitidos
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    
    // Exponer headers
    'exposed_headers' => ['Content-Length', 'X-JSON'],
    
    // Credenciales
    'supports_credentials' => true,
    
    // Tiempo de cache de preflight
    'max_age' => 3600
];
