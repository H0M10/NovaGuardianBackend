<?php
/**
 * Controlador de Autenticación
 * Maneja login, logout y verificación de tokens
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../models/Administrador.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';

use App\Core\Response;
use App\Models\Administrador;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;

// Aplicar CORS
CorsMiddleware::apply();

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Manejar rutas
if ($method === 'POST') {
    // Login
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['email']) || !isset($input['password'])) {
            Response::validationError([
                'email' => 'El email es requerido',
                'password' => 'La contraseña es requerida'
            ]);
        }
        
        $adminModel = new Administrador();
        $admin = $adminModel->findByEmail($input['email']);
        
        if (!$admin) {
            Response::error('Credenciales incorrectas', 401);
        }
        
        if (!$adminModel->verifyPassword($input['password'], $admin['password'])) {
            Response::error('Credenciales incorrectas', 401);
        }
        
        // Generar token JWT
        $token = AuthMiddleware::generate(
            $admin['id'],
            $admin['email'],
            $admin['rol']
        );
        
        // Actualizar última sesión
        $adminModel->updateLastSession($admin['id']);
        
        Response::success([
            'token' => $token,
            'admin' => [
                'id' => $admin['id'],
                'nombre' => $admin['nombre'],
                'email' => $admin['email'],
                'rol' => $admin['rol']
            ]
        ], 'Login exitoso');
        
    } catch (Exception $e) {
        error_log("ERROR AuthController: " . $e->getMessage());
        error_log("ERROR AuthController Stack: " . $e->getTraceAsString());
        Response::error('Error interno: ' . $e->getMessage(), 500);
    }
    
} elseif ($method === 'GET') {
    // Verificar token (obtener info del usuario actual)
    $user = AuthMiddleware::verify();
    
    $adminModel = new Administrador();
    $admin = $adminModel->findById($user->data->userId);
    
    if (!$admin) {
        Response::notFound('Usuario no encontrado');
    }
    
    Response::success([
        'id' => $admin['id'],
        'nombre' => $admin['nombre'],
        'email' => $admin['email'],
        'rol' => $admin['rol']
    ]);
    
} else {
    Response::error('Método no permitido', 405);
}
