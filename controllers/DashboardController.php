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
    try {
        error_log("DEBUG Dashboard: Iniciando carga de dashboard");
        
        $usuarioModel = new Usuario();
        error_log("DEBUG Dashboard: Usuario model creado");
        
        $dispositivoModel = new Dispositivo();
        error_log("DEBUG Dashboard: Dispositivo model creado");
        
        $eventoModel = new Evento();
        error_log("DEBUG Dashboard: Evento model creado");
        
        // Estadísticas generales
        $stats = [
            'total_usuarios' => $usuarioModel->count(),
            'dispositivos_activos' => $dispositivoModel->countActive(),
            'alertas_pendientes' => $eventoModel->countPending(),
            'eventos_hoy' => $eventoModel->countToday()
        ];
        error_log("DEBUG Dashboard: Stats obtenidas");
        
        // Eventos recientes
        $eventosRecientes = $eventoModel->getRecent(10);
        error_log("DEBUG Dashboard: Eventos recientes obtenidos");
        
        // Datos para gráficas
        $eventosPorTipo = $eventoModel->getEventsByType(7);
        $eventosPorDia = $eventoModel->getEventsByDay(30);
        error_log("DEBUG Dashboard: Datos de gráficas obtenidos");
        
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
        error_log("DEBUG Dashboard: Estado de dispositivos calculado");
        
        // Dispositivos con batería baja
        $dispositivosBateriaBaja = $dispositivoModel->getLowBattery(20);
        error_log("DEBUG Dashboard: Dispositivos batería baja obtenidos");
        
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
        
    } catch (\Exception $e) {
        error_log("ERROR Dashboard: " . $e->getMessage());
        error_log("ERROR Dashboard Stack: " . $e->getTraceAsString());
        Response::error('Error al cargar dashboard: ' . $e->getMessage(), 500);
    }
    
} else {
    Response::error('Método no permitido', 405);
}
