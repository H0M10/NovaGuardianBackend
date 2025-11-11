<?php
/**
 * Controlador de Exportación
 * Genera archivos CSV de eventos
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';

use App\Core\Response;
use App\Models\Evento;
use App\Models\Usuario;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;

// Aplicar CORS
CorsMiddleware::apply();

// Verificar autenticación
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    AuthMiddleware::verify();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' || $method === 'POST') {
    
    // Obtener parámetros
    $usuarioId = $_GET['usuario_id'] ?? $_POST['usuario_id'] ?? null;
    $fechaInicio = $_GET['fecha_inicio'] ?? $_POST['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? $_POST['fecha_fin'] ?? null;
    
    // Validaciones
    if (!$usuarioId) {
        Response::error('El ID del usuario es requerido', 400);
    }
    
    if (!$fechaInicio || !$fechaFin) {
        Response::error('Las fechas de inicio y fin son requeridas', 400);
    }
    
    // Validar que fecha inicio no sea mayor que fecha fin
    if (strtotime($fechaInicio) > strtotime($fechaFin)) {
        Response::error('La fecha de inicio no puede ser mayor que la fecha fin', 400);
    }
    
    try {
        $eventoModel = new Evento();
        $usuarioModel = new Usuario();
        
        // Obtener usuario
        $usuario = $usuarioModel->findById($usuarioId);
        
        if (!$usuario) {
            Response::notFound('Usuario no encontrado');
        }
        
        // Obtener eventos
        $eventos = $eventoModel->filterByDateRange($fechaInicio, $fechaFin, $usuarioId);
        
        if (empty($eventos)) {
            Response::error('No se encontraron eventos en el rango de fechas especificado', 404);
        }
        
        // Generar nombre del archivo
        $nombreArchivo = 'eventos_' . str_replace(' ', '_', $usuario['nombre_completo']) . '_' . date('Y-m-d') . '.csv';
        
        // Headers para descarga de CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Abrir output stream
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8 (para que Excel lo abra correctamente)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Escribir encabezados
        fputcsv($output, [
            'ID Evento',
            'Usuario',
            'Dispositivo',
            'Tipo',
            'Descripción',
            'Estado',
            'Fecha y Hora',
            'Latitud',
            'Longitud'
        ]);
        
        // Escribir datos
        foreach ($eventos as $evento) {
            fputcsv($output, [
                $evento['id'],
                $evento['usuario'] ?? $usuario['nombre_completo'],
                $evento['dispositivo'] ?? 'N/A',
                $evento['tipo'],
                $evento['descripcion'] ?? '',
                $evento['estado'],
                $evento['fecha_evento'],
                $evento['latitud'] ?? '',
                $evento['longitud'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
        
    } catch (\Exception $e) {
        Response::error('Error al generar CSV: ' . $e->getMessage(), 500);
    }
    
} else {
    Response::error('Método no permitido', 405);
}
