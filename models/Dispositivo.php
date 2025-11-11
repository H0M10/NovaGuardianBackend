<?php
/**
 * Modelo: Dispositivo
 * Gestiona la lógica de negocio de dispositivos/pulseras
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class Dispositivo {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todos los dispositivos
     */
    public function getAll() {
        $stmt = $this->db->query("
            SELECT d.*, u.nombre_completo AS usuario_nombre
            FROM dispositivos d
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            ORDER BY d.id
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar dispositivo por ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.nombre_completo AS usuario_nombre, u.email AS usuario_email
            FROM dispositivos d
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            WHERE d.id = ?
        ");
        
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Crear nuevo dispositivo
     */
    public function create($data) {
        // Verificar si el ID ya existe
        if ($this->findById($data['id'])) {
            return false; // ID duplicado
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO dispositivos (id, usuario_id, estado, bateria) 
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['id'],
            $data['usuario_id'] ?? null,
            $data['estado'] ?? 'activo',
            $data['bateria'] ?? 100
        ]);
    }
    
    /**
     * Actualizar dispositivo
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE dispositivos SET 
                usuario_id = ?,
                estado = ?,
                bateria = ?,
                ultima_conexion = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['usuario_id'] ?? null,
            $data['estado'] ?? 'activo',
            $data['bateria'] ?? 100,
            $data['ultima_conexion'] ?? null,
            $id
        ]);
    }
    
    /**
     * Reasignar dispositivo a otro usuario
     */
    public function reassign($dispositivoId, $usuarioId) {
        $stmt = $this->db->prepare("
            UPDATE dispositivos SET usuario_id = ? WHERE id = ?
        ");
        
        return $stmt->execute([$usuarioId, $dispositivoId]);
    }
    
    /**
     * Dar de baja dispositivo
     */
    public function deactivate($id) {
        $stmt = $this->db->prepare("
            UPDATE dispositivos SET estado = 'inactivo', usuario_id = NULL WHERE id = ?
        ");
        
        return $stmt->execute([$id]);
    }
    
    /**
     * Eliminar dispositivo
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM dispositivos WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Contar dispositivos activos
     */
    public function countActive() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM dispositivos WHERE estado = 'activo'");
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Obtener dispositivos con batería baja
     */
    public function getLowBattery($threshold = 20) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.nombre_completo AS usuario_nombre
            FROM dispositivos d
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            WHERE d.bateria <= ? AND d.estado = 'activo'
            ORDER BY d.bateria ASC
        ");
        
        $stmt->execute([$threshold]);
        return $stmt->fetchAll();
    }
    
    /**
     * Filtrar dispositivos por estado
     */
    public function filterByStatus($estado) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.nombre_completo AS usuario_nombre
            FROM dispositivos d
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            WHERE d.estado = ?
            ORDER BY d.id
        ");
        
        $stmt->execute([$estado]);
        return $stmt->fetchAll();
    }
}
