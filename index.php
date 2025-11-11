<?php
/**
 * API Router - Punto de entrada de la API REST
 * NovaGuardian Backend
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/middleware/CorsMiddleware.php';
use App\Middleware\CorsMiddleware;

// Aplicar CORS globalmente
CorsMiddleware::apply();

// Obtener la ruta solicitada
$request_uri = $_SERVER['REQUEST_URI'];

// DEBUG: Ver qué está recibiendo el servidor
error_log("DEBUG: REQUEST_URI=" . $request_uri);
error_log("DEBUG: SCRIPT_NAME=" . $_SERVER['SCRIPT_NAME']);

// Parsear la URL para quitar query string
$path = parse_url($request_uri, PHP_URL_PATH);
error_log("DEBUG: path after parse_url=" . $path);

// Remover index.php si está presente
$path = preg_replace('#^/index\.php#', '', $path);

// Limpiar las barras
$path = trim($path, '/');

error_log("DEBUG: path after trim=" . $path);

// Separar la ruta en segmentos
$segments = $path ? explode('/', $path) : [];

error_log("DEBUG: segments=" . json_encode($segments));

// Si el primer segmento es 'api', lo saltamos
if (isset($segments[0]) && $segments[0] === 'api') {
    array_shift($segments);
}

// Obtener el recurso principal (usuarios, dispositivos, eventos, etc.)
$resource = $segments[0] ?? '';

// DEBUG
error_log("DEBUG: final resource=$resource");

// Obtener el ID si existe
$id = $segments[1] ?? null;

// Health check para Railway
if (empty($path) || $path === 'api') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'NovaGuardian API is running',
        'version' => '1.0.0',
        'status' => 'healthy'
    ]);
    exit;
}

// Ruteo básico
switch ($resource) {
    case 'auth':
    case 'login':
        error_log("DEBUG: Routing to AuthController, resource=$resource");
        require_once __DIR__ . '/controllers/AuthController.php';
        break;
        
    case 'usuarios':
        require_once __DIR__ . '/controllers/UsuarioController.php';
        break;
        
    case 'dispositivos':
        require_once __DIR__ . '/controllers/DispositivoController.php';
        break;
        
    case 'eventos':
        require_once __DIR__ . '/controllers/EventoController.php';
        break;
        
    case 'dashboard':
        require_once __DIR__ . '/controllers/DashboardController.php';
        break;
        
    case 'export':
        require_once __DIR__ . '/controllers/ExportController.php';
        break;
        
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint no encontrado',
            'path' => $path
        ]);
        break;
}
