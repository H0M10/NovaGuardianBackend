<?php
/**
 * Archivo de configuración de entorno
 * Define variables según el entorno (local, producción)
 */

// Detectar entorno
$isProduction = isset($_SERVER['RAILWAY_ENVIRONMENT']) || isset($_SERVER['PRODUCTION']);

// DEBUG: Ver qué variables están disponibles
error_log("DEBUG env.php: RAILWAY_ENVIRONMENT=" . ($_SERVER['RAILWAY_ENVIRONMENT'] ?? 'NOT SET'));
error_log("DEBUG env.php: DB_HOST=" . ($_SERVER['DB_HOST'] ?? 'NOT SET'));
error_log("DEBUG env.php: DB_PORT=" . ($_SERVER['DB_PORT'] ?? 'NOT SET'));
error_log("DEBUG env.php: DB_NAME=" . ($_SERVER['DB_NAME'] ?? 'NOT SET'));
error_log("DEBUG env.php: DB_USER=" . ($_SERVER['DB_USER'] ?? 'NOT SET'));
error_log("DEBUG env.php: Verificando variables MYSQL_*");
error_log("DEBUG env.php: MYSQL_HOST=" . ($_SERVER['MYSQL_HOST'] ?? 'NOT SET'));
error_log("DEBUG env.php: MYSQL_PORT=" . ($_SERVER['MYSQL_PORT'] ?? 'NOT SET'));
error_log("DEBUG env.php: MYSQL_DATABASE=" . ($_SERVER['MYSQL_DATABASE'] ?? 'NOT SET'));
error_log("DEBUG env.php: MYSQL_USER=" . ($_SERVER['MYSQL_USER'] ?? 'NOT SET'));

return [
    'environment' => $isProduction ? 'production' : 'development',
    
    // Base de datos
    'database' => [
        'host' => $_SERVER['DB_HOST'] ?? $_SERVER['MYSQL_HOST'] ?? 'localhost',
        'port' => $_SERVER['DB_PORT'] ?? $_SERVER['MYSQL_PORT'] ?? '3306',
        'database' => $_SERVER['DB_NAME'] ?? $_SERVER['MYSQL_DATABASE'] ?? 'novaguardian',
        'username' => $_SERVER['DB_USER'] ?? $_SERVER['MYSQL_USER'] ?? 'root',
        'password' => $_SERVER['DB_PASSWORD'] ?? $_SERVER['MYSQL_PASSWORD'] ?? 'HANNIEL',
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
                'https://h0m10.github.io',
                $_SERVER['FRONTEND_URL'] ?? 'https://h0m10.github.io'
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
