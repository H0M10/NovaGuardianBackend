<?php
/**
 * Archivo de configuración de entorno
 * Define variables según el entorno (local, producción)
 */

// Detectar entorno
$isProduction = isset($_SERVER['RAILWAY_ENVIRONMENT']) || isset($_SERVER['PRODUCTION']);

return [
    'environment' => $isProduction ? 'production' : 'development',
    
    // Base de datos
    'database' => [
        'host' => $_SERVER['DB_HOST'] ?? 'localhost',
        'port' => $_SERVER['DB_PORT'] ?? '3306',
        'database' => $_SERVER['DB_NAME'] ?? 'novaguardian',
        'username' => $_SERVER['DB_USER'] ?? 'root',
        'password' => $_SERVER['DB_PASSWORD'] ?? 'HANNIEL',
    ],
    
    // JWT
    'jwt' => [
        'secret_key' => $_SERVER['JWT_SECRET'] ?? 'NovaGuardian_2025_UTQ_Secret_Key_Change_In_Production',
        'expiration_time' => 86400, // 24 horas
    ],
    
    // CORS
    'cors' => [
        'allowed_origins' => $isProduction 
            ? [
                'https://tu-usuario.github.io', // Cambiar por tu URL de GitHub Pages
                $_SERVER['FRONTEND_URL'] ?? '*'
              ]
            : [
                'http://localhost',
                'http://localhost:3000',
                'http://localhost:5500',
                'http://127.0.0.1',
                'http://127.0.0.1:5500',
              ]
    ],
    
    // Mostrar errores
    'display_errors' => !$isProduction,
    'error_reporting' => $isProduction ? 0 : E_ALL,
];
