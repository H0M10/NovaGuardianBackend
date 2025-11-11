<?php
/**
 * Controlador de Dispositivos
 * CRUD completo de dispositivos/pulseras
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../models/Dispositivo.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';

use App\Core\Response;
use App\Models\Dispositivo;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;

// Aplicar CORS
CorsMiddleware::apply();

// Verificar autenticación
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    AuthMiddleware::verify();
}

$method = $_SERVER['REQUEST_METHOD'];
$dispositivoModel = new Dispositivo();

// Obtener ID de la URL si existe
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$id = isset($segments[3]) ? $segments[3] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            // Obtener un dispositivo específico
            $dispositivo = $dispositivoModel->findById($id);
            
            if (!$dispositivo) {
                Response::notFound('Dispositivo no encontrado');
            }
            
            Response::success($dispositivo);
            
        } else {
            // Obtener todos los dispositivos o filtrar
            $estado = $_GET['estado'] ?? null;
            $bateriaBaja = isset($_GET['bateria_baja']);
            
            if ($bateriaBaja) {
                $dispositivos = $dispositivoModel->getLowBattery(20);
            } elseif ($estado) {
                $dispositivos = $dispositivoModel->filterByStatus($estado);
            } else {
                $dispositivos = $dispositivoModel->getAll();
            }
            
            Response::success([
                'dispositivos' => $dispositivos,
                'total' => count($dispositivos)
            ]);
        }
        break;
        
    case 'POST':
        // Crear nuevo dispositivo
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validaciones
        $errors = [];
        
        if (empty($input['id'])) {
            $errors['id'] = 'El ID del dispositivo es requerido';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
        }
        
        try {
            $created = $dispositivoModel->create($input);
            
            if ($created) {
                $dispositivo = $dispositivoModel->findById($input['id']);
                Response::success($dispositivo, 'Dispositivo creado exitosamente', 201);
            } else {
                Response::error('Error al crear dispositivo. El ID puede estar duplicado.');
            }
        } catch (\Exception $e) {
            Response::error('Error al crear dispositivo: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
        // Actualizar dispositivo
        if (!$id) {
            Response::error('ID de dispositivo requerido', 400);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Verificar si es una reasignación
        if (isset($input['action']) && $input['action'] === 'reassign') {
            $usuarioId = $input['usuario_id'] ?? null;
            
            try {
                $reassigned = $dispositivoModel->reassign($id, $usuarioId);
                
                if ($reassigned) {
                    $dispositivo = $dispositivoModel->findById($id);
                    Response::success($dispositivo, 'Dispositivo reasignado exitosamente');
                } else {
                    Response::error('Error al reasignar dispositivo');
                }
            } catch (\Exception $e) {
                Response::error('Error al reasignar: ' . $e->getMessage(), 500);
            }
        } 
        // Verificar si es una baja
        elseif (isset($input['action']) && $input['action'] === 'deactivate') {
            try {
                $deactivated = $dispositivoModel->deactivate($id);
                
                if ($deactivated) {
                    Response::success(null, 'Dispositivo dado de baja exitosamente');
                } else {
                    Response::error('Error al dar de baja el dispositivo');
                }
            } catch (\Exception $e) {
                Response::error('Error: ' . $e->getMessage(), 500);
            }
        }
        // Actualización normal
        else {
            try {
                $updated = $dispositivoModel->update($id, $input);
                
                if ($updated) {
                    $dispositivo = $dispositivoModel->findById($id);
                    Response::success($dispositivo, 'Dispositivo actualizado exitosamente');
                } else {
                    Response::error('Error al actualizar dispositivo');
                }
            } catch (\Exception $e) {
                Response::error('Error al actualizar: ' . $e->getMessage(), 500);
            }
        }
        break;
        
    case 'DELETE':
        // Eliminar dispositivo
        if (!$id) {
            Response::error('ID de dispositivo requerido', 400);
        }
        
        try {
            $deleted = $dispositivoModel->delete($id);
            
            if ($deleted) {
                Response::success(null, 'Dispositivo eliminado exitosamente');
            } else {
                Response::error('Error al eliminar dispositivo');
            }
        } catch (\Exception $e) {
            Response::error('Error al eliminar: ' . $e->getMessage(), 500);
        }
        break;
        
    default:
        Response::error('Método no permitido', 405);
}
