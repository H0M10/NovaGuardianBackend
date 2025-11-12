<?php
/**
 * Modelo: Evento
 * Gestiona la lógica de negocio de eventos/alertas
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class Evento {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todos los eventos con información completa
     */
    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM vista_eventos_completos";
        
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit, $offset]);
        } else {
            $stmt = $this->db->query($sql);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar evento por ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT e.*, 
                   u.nombre_completo AS usuario_nombre,
                   u.telefono AS usuario_telefono,
                   u.contacto_emergencia_1_nombre,
                   u.contacto_emergencia_1_telefono,
                   d.bateria AS dispositivo_bateria
            FROM eventos e
            INNER JOIN usuarios u ON e.usuario_id = u.id
            LEFT JOIN dispositivos d ON e.dispositivo_id = d.id
            WHERE e.id = ?
        ");
        
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener eventos recientes (últimas N alertas)
     */
    public function getRecent($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT * FROM vista_eventos_completos 
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        $result = $stmt->fetchAll();
        
        // Debug temporal
        error_log("DEBUG Evento: Columnas = " . json_encode(array_keys($result[0] ?? [])));
        error_log("DEBUG Evento: Primer registro = " . json_encode($result[0] ?? []));
        
        return $result;
    }
    
    /**
     * Obtener eventos pendientes
     */
    public function getPending() {
        $stmt = $this->db->query("
            SELECT * FROM vista_eventos_completos 
            WHERE estado = 'Pendiente'
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Crear nuevo evento
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO eventos (
                usuario_id, dispositivo_id, tipo, descripcion, 
                estado, latitud, longitud
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['usuario_id'],
            $data['dispositivo_id'] ?? null,
            $data['tipo'],
            $data['descripcion'] ?? null,
            $data['estado'] ?? 'Pendiente',
            $data['latitud'] ?? null,
            $data['longitud'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar estado del evento
     */
    public function updateStatus($id, $estado, $adminId = null) {
        $stmt = $this->db->prepare("
            UPDATE eventos SET 
                estado = ?,
                fecha_atencion = NOW(),
                atendido_por = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([$estado, $adminId, $id]);
    }
    
    /**
     * Eliminar evento
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM eventos WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Filtrar eventos por tipo
     */
    public function filterByType($tipo) {
        $stmt = $this->db->prepare("
            SELECT * FROM vista_eventos_completos 
            WHERE tipo = ?
        ");
        
        $stmt->execute([$tipo]);
        return $stmt->fetchAll();
    }
    
    /**
     * Filtrar eventos por usuario
     */
    public function filterByUser($usuarioId) {
        $stmt = $this->db->prepare("
            SELECT e.*, 
                   u.nombre_completo AS usuario,
                   d.id AS dispositivo
            FROM eventos e
            INNER JOIN usuarios u ON e.usuario_id = u.id
            LEFT JOIN dispositivos d ON e.dispositivo_id = d.id
            WHERE e.usuario_id = ?
            ORDER BY e.fecha_evento DESC
        ");
        
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Filtrar eventos por rango de fechas
     */
    public function filterByDateRange($fechaInicio, $fechaFin, $usuarioId = null) {
        if ($usuarioId) {
            $stmt = $this->db->prepare("
                SELECT e.*, 
                       u.nombre_completo AS usuario,
                       d.id AS dispositivo
                FROM eventos e
                INNER JOIN usuarios u ON e.usuario_id = u.id
                LEFT JOIN dispositivos d ON e.dispositivo_id = d.id
                WHERE e.usuario_id = ? 
                  AND DATE(e.fecha_evento) BETWEEN ? AND ?
                ORDER BY e.fecha_evento DESC
            ");
            
            $stmt->execute([$usuarioId, $fechaInicio, $fechaFin]);
        } else {
            $stmt = $this->db->prepare("
                SELECT * FROM vista_eventos_completos 
                WHERE DATE(fecha_evento) BETWEEN ? AND ?
            ");
            
            $stmt->execute([$fechaInicio, $fechaFin]);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Contar eventos de hoy
     */
    public function countToday() {
        $stmt = $this->db->query("
            SELECT COUNT(*) as total 
            FROM eventos 
            WHERE DATE(fecha_evento) = CURDATE()
        ");
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Contar eventos pendientes
     */
    public function countPending() {
        $stmt = $this->db->query("
            SELECT COUNT(*) as total 
            FROM eventos 
            WHERE estado = 'Pendiente'
        ");
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Obtener eventos por tipo (últimos N días)
     */
    public function getEventsByType($days = 7) {
        $stmt = $this->db->prepare("
            SELECT tipo, COUNT(*) as cantidad
            FROM eventos
            WHERE fecha_evento >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY tipo
        ");
        
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener eventos por día (últimos N días)
     */
    public function getEventsByDay($days = 30) {
        $stmt = $this->db->prepare("
            SELECT DATE(fecha_evento) as fecha, COUNT(*) as cantidad
            FROM eventos
            WHERE fecha_evento >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(fecha_evento)
            ORDER BY fecha
        ");
        
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}
