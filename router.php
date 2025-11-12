<?php
/**
 * Router for PHP built-in server
 * Redirects all requests to index.php
 */

// Si el archivo solicitado existe y no es un directorio, servirlo directamente
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    
    // Si es un archivo estático que existe, servirlo
    if (is_file($file)) {
        return false;
    }
}

// Para cualquier otra petición, usar index.php
require_once __DIR__ . '/index.php';
