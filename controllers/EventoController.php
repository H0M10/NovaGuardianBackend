<?php
/**
 * Controlador de Eventos/Alertas
 * Gestión completa de eventos con filtros
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';

use App\Core\Response;
use App\Models\Evento;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;

// Aplicar CORS
CorsMiddleware::apply();

// Verificar autenticación
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    $user = AuthMiddleware::verify();
}

$method = $_SERVER['REQUEST_METHOD'];
$eventoModel = new Evento();

// Obtener ID de la URL si existe
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$id = isset($segments[3]) && is_numeric($segments[3]) ? (int)$segments[3] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            // Obtener un evento específico
            $evento = $eventoModel->findById($id);
            
            if (!$evento) {
                Response::notFound('Evento no encontrado');
            }
            
            Response::success($evento);
            
        } else {
            // Obtener eventos con filtros
            $tipo = $_GET['tipo'] ?? null;
            $usuarioId = $_GET['usuario_id'] ?? null;
            $estado = $_GET['estado'] ?? null;
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin = $_GET['fecha_fin'] ?? null;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $recientes = isset($_GET['recientes']);
            $pendientes = isset($_GET['pendientes']);
            
            // Eventos pendientes
            if ($pendientes) {
                $eventos = $eventoModel->getPending();
            }
            // Eventos recientes
            elseif ($recientes) {
                $limitRecientes = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $eventos = $eventoModel->getRecent($limitRecientes);
            }
            // Filtrar por rango de fechas
            elseif ($fechaInicio && $fechaFin) {
                $eventos = $eventoModel->filterByDateRange($fechaInicio, $fechaFin, $usuarioId);
            }
            // Filtrar por tipo
            elseif ($tipo) {
                $eventos = $eventoModel->filterByType($tipo);
            }
            // Filtrar por usuario
            elseif ($usuarioId) {
                $eventos = $eventoModel->filterByUser($usuarioId);
            }
            // Obtener todos
            else {
                $eventos = $eventoModel->getAll($limit, $offset);
            }
            
            Response::success([
                'eventos' => $eventos,
                'total' => count($eventos)
            ]);
        }
        break;
        
    case 'POST':
        // Crear nuevo evento (simulación de alerta)
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validaciones
        $errors = [];
        
        if (empty($input['usuario_id'])) {
            $errors['usuario_id'] = 'El usuario es requerido';
        }
        
        if (empty($input['tipo'])) {
            $errors['tipo'] = 'El tipo de evento es requerido';
        }
        
        $tiposPermitidos = ['SOS', 'Caída', 'Pulso anormal', 'Batería baja'];
        if (!empty($input['tipo']) && !in_array($input['tipo'], $tiposPermitidos)) {
            $errors['tipo'] = 'Tipo de evento inválido';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
        }
        
        try {
            $eventoId = $eventoModel->create($input);
            
            if ($eventoId) {
                $evento = $eventoModel->findById($eventoId);
                Response::success($evento, 'Evento creado exitosamente', 201);
            } else {
                Response::error('Error al crear evento');
            }
        } catch (\Exception $e) {
            Response::error('Error al crear evento: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
        // Actualizar estado del evento
        if (!$id) {
            Response::error('ID de evento requerido', 400);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['estado'])) {
            Response::validationError(['estado' => 'El estado es requerido']);
        }
        
        $estadosPermitidos = ['Pendiente', 'Atendido', 'Resuelto'];
        if (!in_array($input['estado'], $estadosPermitidos)) {
            Response::validationError(['estado' => 'Estado inválido']);
        }
        
        try {
            $adminId = $user->data->userId ?? null;
            $updated = $eventoModel->updateStatus($id, $input['estado'], $adminId);
            
            if ($updated) {
                $evento = $eventoModel->findById($id);
                Response::success($evento, 'Estado actualizado exitosamente');
            } else {
                Response::error('Error al actualizar estado');
            }
        } catch (\Exception $e) {
            Response::error('Error al actualizar: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'DELETE':
        // Eliminar evento
        if (!$id) {
            Response::error('ID de evento requerido', 400);
        }
        
        try {
            $deleted = $eventoModel->delete($id);
            
            if ($deleted) {
                Response::success(null, 'Evento eliminado exitosamente');
            } else {
                Response::error('Error al eliminar evento');
            }
        } catch (\Exception $e) {
            Response::error('Error al eliminar: ' . $e->getMessage(), 500);
        }
        break;
        
    default:
        Response::error('Método no permitido', 405);
}
