<?php
/**
 * Controlador de Dashboard
 * Provee estadísticas y datos para el panel principal
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Dispositivo.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';

use App\Core\Response;
use App\Models\Usuario;
use App\Models\Dispositivo;
use App\Models\Evento;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;

// Aplicar CORS
CorsMiddleware::apply();

// Verificar autenticación
AuthMiddleware::verify();

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $usuarioModel = new Usuario();
    $dispositivoModel = new Dispositivo();
    $eventoModel = new Evento();
    
    // Estadísticas generales
    $stats = [
        'total_usuarios' => $usuarioModel->count(),
        'dispositivos_activos' => $dispositivoModel->countActive(),
        'alertas_pendientes' => $eventoModel->countPending(),
        'eventos_hoy' => $eventoModel->countToday()
    ];
    
    // Eventos recientes
    $eventosRecientes = $eventoModel->getRecent(10);
    
    // Datos para gráficas
    $eventosPorTipo = $eventoModel->getEventsByType(7);
    $eventosPorDia = $eventoModel->getEventsByDay(30);
    
    // Estado de dispositivos
    $todosDispositivos = $dispositivoModel->getAll();
    $estadoDispositivos = [
        'activo' => 0,
        'inactivo' => 0,
        'mantenimiento' => 0
    ];
    
    foreach ($todosDispositivos as $disp) {
        $estadoDispositivos[$disp['estado']]++;
    }
    
    // Dispositivos con batería baja
    $dispositivosBateriaBaja = $dispositivoModel->getLowBattery(20);
    
    Response::success([
        'estadisticas' => $stats,
        'eventos_recientes' => $eventosRecientes,
        'graficas' => [
            'eventos_por_tipo' => $eventosPorTipo,
            'eventos_por_dia' => $eventosPorDia,
            'estado_dispositivos' => $estadoDispositivos
        ],
        'dispositivos_bateria_baja' => $dispositivosBateriaBaja
    ]);
    
} else {
    Response::error('Método no permitido', 405);
}
