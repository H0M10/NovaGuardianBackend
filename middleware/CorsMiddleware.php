<?php
/**
 * CORS Middleware
 * Maneja las políticas de Cross-Origin Resource Sharing
 */

namespace App\Middleware;

class CorsMiddleware {
    
    /**
     * Aplicar headers CORS
     */
    public static function apply() {
        $config = require __DIR__ . '/../config/cors.php';
        
        // Obtener origen de la petición
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        // Verificar si el origen está permitido
        if (in_array($origin, $config['allowed_origins']) || in_array('*', $config['allowed_origins'])) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        // Headers permitidos
        header("Access-Control-Allow-Methods: " . implode(', ', $config['allowed_methods']));
        header("Access-Control-Allow-Headers: " . implode(', ', $config['allowed_headers']));
        header("Access-Control-Expose-Headers: " . implode(', ', $config['exposed_headers']));
        
        // Credenciales
        if ($config['supports_credentials']) {
            header("Access-Control-Allow-Credentials: true");
        }
        
        // Max age
        header("Access-Control-Max-Age: {$config['max_age']}");
        
        // Responder a preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
