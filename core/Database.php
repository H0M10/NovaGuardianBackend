<?php
/**
 * Clase Database
 * Maneja la conexión a la base de datos usando PDO
 */

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Constructor privado (Singleton)
     */
    private function __construct() {
        // Cargar configuración de entorno
        $env = require __DIR__ . '/../config/env.php';
        
        if (!is_array($env)) {
            throw new \Exception("Configuración inválida");
        }
        
        if (!isset($env['database'])) {
            throw new \Exception("Configuración de base de datos no encontrada");
        }
        
        $dbConfig = $env['database'];
        
        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $options
            );
            
        } catch (PDOException $e) {
            error_log("ERROR Database: " . $e->getMessage());
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Error de conexión a la base de datos',
                'error' => $e->getMessage()
            ]));
        }
    }
    
    /**
     * Obtener instancia única (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtener conexión PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prevenir clonación
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialización
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
