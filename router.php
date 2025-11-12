<?php
/**
 * Router for PHP built-in server
 * Redirects all requests to index.php
 */

// Si el archivo solicitado existe y no es un directorio, servirlo directamente
if (php_sapi_name() === 'cli-server') {
    // Obtener solo el path sin query string
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    
    // Si es un archivo estático que existe, servirlo
    if (is_file($file)) {
        return false;
    }
}

// Para cualquier otra petición, usar index.php
require_once __DIR__ . '/index.php';
