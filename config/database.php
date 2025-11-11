<?php
/**
 * Configuración de Base de Datos
 * NovaGuardian - Sistema IoTjj
 */

// Cargar configuración de entorno
$env = require __DIR__ . '/env.php';

return [
    'host' => $env['database']['host'],
    'port' => $env['database']['port'],
    'database' => $env['database']['database'],
    'username' => $env['database']['username'],
    'password' => $env['database']['password'],
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
