<?php
/**
 * Middleware de Autenticación JWT
 * Verifica y valida los tokens JWT
 */

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use App\Core\Response;

class AuthMiddleware {
    
    /**
     * Verificar token JWT
     */
    public static function verify() {
        $config = require __DIR__ . '/../config/jwt.php';
        
        // Obtener el token del header Authorization
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            Response::unauthorized('Token no proporcionado');
        }
        
        $authHeader = $headers['Authorization'];
        
        // El formato debe ser: Bearer <token>
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Response::unauthorized('Formato de token inválido');
        }
        
        $token = $matches[1];
        
        try {
            // Decodificar y verificar el token
            $decoded = JWT::decode($token, new Key($config['secret_key'], $config['algorithm']));
            
            // Retornar los datos del usuario
            return $decoded;
            
        } catch (ExpiredException $e) {
            Response::unauthorized('Token expirado');
        } catch (\Exception $e) {
            Response::unauthorized('Token inválido: ' . $e->getMessage());
        }
    }
    
    /**
     * Generar nuevo token JWT
     */
    public static function generate($userId, $email, $rol) {
        $config = require __DIR__ . '/../config/jwt.php';
        
        $issuedAt = time();
        $expirationTime = $issuedAt + $config['expiration_time'];
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'iss' => $config['issuer'],
            'aud' => $config['audience'],
            'data' => [
                'userId' => $userId,
                'email' => $email,
                'rol' => $rol
            ]
        ];
        
        return JWT::encode($payload, $config['secret_key'], $config['algorithm']);
    }
}
