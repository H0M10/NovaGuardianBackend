<?php
/**
 * Archivo de configuración de entorno
 * Define variables según el entorno (local, producción)
 */

// Detectar entorno
$isProduction = getenv('RAILWAY_ENVIRONMENT') !== false || getenv('PRODUCTION') !== false;

// DEBUG: Ver qué variables están disponibles
error_log("DEBUG env.php: Usando getenv()");
error_log("DEBUG env.php: DB_HOST=" . (getenv('DB_HOST') ?: 'NOT SET'));
error_log("DEBUG env.php: DB_PORT=" . (getenv('DB_PORT') ?: 'NOT SET'));
error_log("DEBUG env.php: DB_NAME=" . (getenv('DB_NAME') ?: 'NOT SET'));

return [
    'environment' => $isProduction ? 'production' : 'development',
    
    // Base de datos - Usar getenv() para Railway
    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_NAME') ?: 'novaguardian',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: 'HANNIEL',
    ],
    
    // JWT
    'jwt' => [
        'secret_key' => getenv('JWT_SECRET') ?: 'NovaGuardian_2025_UTQ_Secret_Key_Change_In_Production',
        'expiration_time' => 86400, // 24 horas
    ],
    
    // CORS
    'cors' => [
        'allowed_origins' => $isProduction 
            ? [
                'https://h0m10.github.io',
                getenv('FRONTEND_URL') ?: 'https://h0m10.github.io'
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
