<?php
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "Conectando a la base de datos...\n";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "✓ Conexión exitosa\n\n";

    // Eliminar vistas si existen
    echo "Eliminando vistas anteriores...\n";
    $pdo->exec("DROP VIEW IF EXISTS vista_eventos_completos");
    $pdo->exec("DROP VIEW IF EXISTS vista_usuarios_dispositivos");
    echo "✓ Vistas anteriores eliminadas\n\n";

    // Crear vista_usuarios_dispositivos
    echo "Creando vista_usuarios_dispositivos...\n";
    $sql1 = "CREATE VIEW vista_usuarios_dispositivos AS
    SELECT 
      u.id,
      u.nombre_completo,
      u.email,
      u.telefono,
      u.estado AS estado_usuario,
      d.id AS dispositivo_id,
      d.estado AS estado_dispositivo,
      d.bateria,
      d.ultima_conexion,
      (SELECT fecha_evento FROM eventos WHERE usuario_id = u.id ORDER BY fecha_evento DESC LIMIT 1) AS ultimo_evento
    FROM usuarios u
    LEFT JOIN dispositivos d ON d.usuario_id = u.id";
    
    $pdo->exec($sql1);
    echo "✓ vista_usuarios_dispositivos creada\n\n";

    // Crear vista_eventos_completos
    echo "Creando vista_eventos_completos...\n";
    $sql2 = "CREATE VIEW vista_eventos_completos AS
    SELECT 
      e.id,
      e.tipo,
      e.descripcion,
      e.estado,
      e.fecha_evento,
      u.nombre_completo AS usuario_nombre,
      u.telefono AS usuario_telefono,
      u.contacto_emergencia_1_nombre,
      u.contacto_emergencia_1_telefono,
      d.id AS dispositivo,
      d.bateria
    FROM eventos e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    LEFT JOIN dispositivos d ON e.dispositivo_id = d.id
    ORDER BY e.fecha_evento DESC";
    
    $pdo->exec($sql2);
    echo "✓ vista_eventos_completos creada\n\n";

    // Verificar que funcionan las vistas
    echo "Verificando vista_eventos_completos...\n";
    $stmt = $pdo->query("SELECT id, tipo, usuario_nombre, fecha_evento FROM vista_eventos_completos LIMIT 3");
    $eventos = $stmt->fetchAll();
    
    echo "✓ Primeros 3 eventos encontrados:\n";
    foreach ($eventos as $evento) {
        echo "  - ID: {$evento['id']} | Tipo: {$evento['tipo']} | Usuario: {$evento['usuario_nombre']} | Fecha: {$evento['fecha_evento']}\n";
    }
    
    echo "\n🎉 VISTAS CREADAS EXITOSAMENTE\n";
    echo "\nAhora elimina este archivo (create_views.php) por seguridad.\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    http_response_code(500);
}
