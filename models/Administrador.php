<?php
/**
 * Modelo: Administrador
 * Gestiona la lógica de negocio de administradores
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class Administrador {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Buscar administrador por email
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("
            SELECT id, nombre, email, password, rol, activo 
            FROM administradores 
            WHERE email = ? AND activo = 1
        ");
        
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    /**
     * Buscar administrador por ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT id, nombre, email, rol, activo, fecha_registro, ultima_sesion 
            FROM administradores 
            WHERE id = ?
        ");
        
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Verificar contraseña
     */
    public function verifyPassword($plainPassword, $hashedPassword) {
        return password_verify($plainPassword, $hashedPassword);
    }
    
    /**
     * Actualizar última sesión
     */
    public function updateLastSession($id) {
        $stmt = $this->db->prepare("
            UPDATE administradores 
            SET ultima_sesion = NOW() 
            WHERE id = ?
        ");
        
        return $stmt->execute([$id]);
    }
    
    /**
     * Crear nuevo administrador
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO administradores (nombre, email, password, rol) 
            VALUES (?, ?, ?, ?)
        ");
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt->execute([
            $data['nombre'],
            $data['email'],
            $hashedPassword,
            $data['rol'] ?? 'admin'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Obtener todos los administradores
     */
    public function getAll() {
        $stmt = $this->db->query("
            SELECT id, nombre, email, rol, activo, fecha_registro, ultima_sesion 
            FROM administradores 
            ORDER BY nombre
        ");
        
        return $stmt->fetchAll();
    }
}
