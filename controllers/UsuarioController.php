<?php
/**
 * Controlador de Usuarios
 * CRUD completo de usuarios (adultos mayores)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';

use App\Core\Response;
use App\Models\Usuario;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;

// Aplicar CORS
CorsMiddleware::apply();

// Verificar autenticación (excepto para OPTIONS)
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    AuthMiddleware::verify();
}

$method = $_SERVER['REQUEST_METHOD'];
$usuarioModel = new Usuario();

// Obtener ID de la URL si existe
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$id = isset($segments[3]) && is_numeric($segments[3]) ? (int)$segments[3] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            // Obtener un usuario específico
            $usuario = $usuarioModel->findById($id);
            
            if (!$usuario) {
                Response::notFound('Usuario no encontrado');
            }
            
            Response::success($usuario);
            
        } else {
            // Obtener todos los usuarios o buscar por nombre
            $search = $_GET['search'] ?? null;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            if ($search) {
                $usuarios = $usuarioModel->searchByName($search);
            } else {
                $usuarios = $usuarioModel->getAll($limit, $offset);
            }
            
            $total = $usuarioModel->count();
            
            Response::success([
                'usuarios' => $usuarios,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
        }
        break;
        
    case 'POST':
        // Crear nuevo usuario
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validaciones
        $errors = [];
        
        if (empty($input['nombre_completo'])) {
            $errors['nombre_completo'] = 'El nombre completo es requerido';
        }
        
        if (empty($input['contacto_emergencia_1_nombre']) || empty($input['contacto_emergencia_1_telefono'])) {
            $errors['contacto_emergencia_1'] = 'Al menos un contacto de emergencia es requerido';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
        }
        
        try {
            $usuarioId = $usuarioModel->create($input);
            
            if ($usuarioId) {
                $usuario = $usuarioModel->findById($usuarioId);
                Response::success($usuario, 'Usuario creado exitosamente', 201);
            } else {
                Response::error('Error al crear usuario');
            }
        } catch (\Exception $e) {
            Response::error('Error al crear usuario: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
        // Actualizar usuario
        if (!$id) {
            Response::error('ID de usuario requerido', 400);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validaciones
        $errors = [];
        
        if (empty($input['nombre_completo'])) {
            $errors['nombre_completo'] = 'El nombre completo es requerido';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
        }
        
        try {
            $updated = $usuarioModel->update($id, $input);
            
            if ($updated) {
                $usuario = $usuarioModel->findById($id);
                Response::success($usuario, 'Usuario actualizado exitosamente');
            } else {
                Response::error('Error al actualizar usuario o usuario no encontrado');
            }
        } catch (\Exception $e) {
            Response::error('Error al actualizar usuario: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'DELETE':
        // Eliminar usuario
        if (!$id) {
            Response::error('ID de usuario requerido', 400);
        }
        
        try {
            $deleted = $usuarioModel->delete($id);
            
            if ($deleted) {
                Response::success(null, 'Usuario eliminado exitosamente');
            } else {
                Response::error('Error al eliminar usuario o usuario no encontrado');
            }
        } catch (\Exception $e) {
            Response::error('Error al eliminar usuario: ' . $e->getMessage(), 500);
        }
        break;
        
    default:
        Response::error('Método no permitido', 405);
}
