<?php
/**
 * Configuración de JWT
 * NovaGuardian - Sistema IoT
 */

return [
    // Clave secreta para firmar tokens (CAMBIAR EN PRODUCCIÓN)
    'secret_key' => 'NovaGuardian_2025_UTQ_Secret_Key_Change_In_Production',
    
    // Algoritmo de encriptación
    'algorithm' => 'HS256',
    
    // Tiempo de expiración del token (en segundos)
    // 86400 = 24 horas
    'expiration_time' => 86400,
    
    // Tiempo de refresh (7 días)
    'refresh_time' => 604800,
    
    // Emisor del token
    'issuer' => 'novaguardian.com',
    
    // Audiencia del token
    'audience' => 'novaguardian-web-panel'
];
