<?php
/**
 * Modelo: Usuario
 * Gestiona la lógica de negocio de usuarios (adultos mayores)
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todos los usuarios con información del dispositivo y último evento
     */
    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM vista_usuarios_dispositivos ORDER BY nombre_completo";
        
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
     * Buscar usuario por ID con toda su información
     */
    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT u.*, 
                   d.id AS dispositivo_id,
                   d.estado AS dispositivo_estado,
                   d.bateria AS dispositivo_bateria,
                   d.ultima_conexion AS dispositivo_ultima_conexion
            FROM usuarios u
            LEFT JOIN dispositivos d ON d.usuario_id = u.id
            WHERE u.id = ?
        ");
        
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Buscar usuarios por nombre (para búsqueda)
     */
    public function searchByName($nombre) {
        $stmt = $this->db->prepare("
            SELECT * FROM vista_usuarios_dispositivos 
            WHERE nombre_completo LIKE ? 
            ORDER BY nombre_completo
        ");
        
        $stmt->execute(["%$nombre%"]);
        return $stmt->fetchAll();
    }
    
    /**
     * Crear nuevo usuario
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (
                nombre_completo, email, telefono, fecha_nacimiento,
                contacto_emergencia_1_nombre, contacto_emergencia_1_telefono, contacto_emergencia_1_relacion,
                contacto_emergencia_2_nombre, contacto_emergencia_2_telefono, contacto_emergencia_2_relacion,
                condiciones_medicas, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['nombre_completo'],
            $data['email'] ?? null,
            $data['telefono'] ?? null,
            $data['fecha_nacimiento'] ?? null,
            $data['contacto_emergencia_1_nombre'] ?? null,
            $data['contacto_emergencia_1_telefono'] ?? null,
            $data['contacto_emergencia_1_relacion'] ?? null,
            $data['contacto_emergencia_2_nombre'] ?? null,
            $data['contacto_emergencia_2_telefono'] ?? null,
            $data['contacto_emergencia_2_relacion'] ?? null,
            $data['condiciones_medicas'] ?? null,
            $data['estado'] ?? 'activo'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar usuario
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE usuarios SET 
                nombre_completo = ?,
                email = ?,
                telefono = ?,
                fecha_nacimiento = ?,
                contacto_emergencia_1_nombre = ?,
                contacto_emergencia_1_telefono = ?,
                contacto_emergencia_1_relacion = ?,
                contacto_emergencia_2_nombre = ?,
                contacto_emergencia_2_telefono = ?,
                contacto_emergencia_2_relacion = ?,
                condiciones_medicas = ?,
                estado = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['nombre_completo'],
            $data['email'] ?? null,
            $data['telefono'] ?? null,
            $data['fecha_nacimiento'] ?? null,
            $data['contacto_emergencia_1_nombre'] ?? null,
            $data['contacto_emergencia_1_telefono'] ?? null,
            $data['contacto_emergencia_1_relacion'] ?? null,
            $data['contacto_emergencia_2_nombre'] ?? null,
            $data['contacto_emergencia_2_telefono'] ?? null,
            $data['contacto_emergencia_2_relacion'] ?? null,
            $data['condiciones_medicas'] ?? null,
            $data['estado'] ?? 'activo',
            $id
        ]);
    }
    
    /**
     * Eliminar usuario
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Contar total de usuarios
     */
    public function count() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'");
        $result = $stmt->fetch();
        return $result['total'];
    }
}
