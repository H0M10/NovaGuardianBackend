-- ============================================================================
-- NOVAGUARDIAN - Base de Datos Completa
-- Sistema IoT de Monitoreo para Adultos Mayores
-- Adaptado para Railway
-- ============================================================================

-- NO eliminar la base de datos railway, solo limpiarla si existe
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS dispositivos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS administradores;
DROP VIEW IF EXISTS vista_usuarios_dispositivos;
DROP VIEW IF EXISTS vista_eventos_completos;

USE railway;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

SET time_zone = "+00:00";

-- ============================================================================
-- TABLA: administradores
-- ============================================================================
CREATE TABLE administradores (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL COMMENT 'Hash con password_hash()',
  rol ENUM('admin', 'superadmin') DEFAULT 'admin',
  activo TINYINT(1) DEFAULT 1,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ultima_sesion TIMESTAMP NULL,
  
  INDEX idx_email (email),
  INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: usuarios
-- ============================================================================
CREATE TABLE usuarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre_completo VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE,
  telefono VARCHAR(20),
  fecha_nacimiento DATE,
  
  contacto_emergencia_1_nombre VARCHAR(100),
  contacto_emergencia_1_telefono VARCHAR(20),
  contacto_emergencia_1_relacion VARCHAR(50),
  
  contacto_emergencia_2_nombre VARCHAR(100),
  contacto_emergencia_2_telefono VARCHAR(20),
  contacto_emergencia_2_relacion VARCHAR(50),
  
  condiciones_medicas TEXT,
  
  estado ENUM('activo', 'inactivo') DEFAULT 'activo',
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_nombre (nombre_completo),
  INDEX idx_email (email),
  INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: dispositivos
-- ============================================================================
CREATE TABLE dispositivos (
  id VARCHAR(20) PRIMARY KEY,
  usuario_id INT NULL,
  
  estado ENUM('activo', 'inactivo', 'mantenimiento') DEFAULT 'activo',
  bateria INT DEFAULT 100,
  
  ultima_conexion TIMESTAMP NULL,
  ip_ultima_conexion VARCHAR(45) NULL,
  
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  
  INDEX idx_usuario (usuario_id),
  INDEX idx_estado (estado),
  INDEX idx_bateria (bateria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: eventos
-- ============================================================================
CREATE TABLE eventos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  dispositivo_id VARCHAR(20),
  
  tipo ENUM('SOS', 'Caída', 'Pulso anormal', 'Batería baja', 'Botón SOS', 'Inactividad', 'Otro') NOT NULL,
  descripcion TEXT,
  
  estado ENUM('Pendiente', 'Atendido', 'Resuelto', 'En Atención', 'Falsa Alarma') DEFAULT 'Pendiente',
  
  latitud DECIMAL(10, 8) NULL,
  longitud DECIMAL(11, 8) NULL,
  
  fecha_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_atencion TIMESTAMP NULL,
  atendido_por INT NULL,
  
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (dispositivo_id) REFERENCES dispositivos(id) ON DELETE SET NULL,
  FOREIGN KEY (atendido_por) REFERENCES administradores(id) ON DELETE SET NULL,
  
  INDEX idx_usuario (usuario_id),
  INDEX idx_tipo (tipo),
  INDEX idx_estado (estado),
  INDEX idx_fecha (fecha_evento),
  INDEX idx_eventos_usuario_fecha (usuario_id, fecha_evento DESC),
  INDEX idx_eventos_tipo_estado (tipo, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- VISTAS
-- ============================================================================

CREATE VIEW vista_usuarios_dispositivos AS
SELECT 
  u.id,
  u.nombre_completo,
  u.email,
  u.telefono,
  u.fecha_nacimiento,
  u.contacto_emergencia_1_nombre,
  u.contacto_emergencia_1_telefono,
  u.contacto_emergencia_1_relacion,
  u.contacto_emergencia_2_nombre,
  u.contacto_emergencia_2_telefono,
  u.contacto_emergencia_2_relacion,
  u.condiciones_medicas,
  u.estado,
  u.fecha_registro,
  d.id AS dispositivo_id,
  d.estado AS dispositivo_estado,
  d.bateria AS dispositivo_bateria,
  d.ultima_conexion AS dispositivo_ultima_conexion,
  (SELECT MAX(fecha_evento) FROM eventos WHERE usuario_id = u.id) AS ultimo_evento
FROM usuarios u
LEFT JOIN dispositivos d ON d.usuario_id = u.id;

CREATE VIEW vista_eventos_completos AS
SELECT 
  e.id,
  e.tipo,
  e.descripcion,
  e.estado,
  e.latitud,
  e.longitud,
  e.fecha_evento,
  e.fecha_atencion,
  u.id AS usuario_id,
  u.nombre_completo AS usuario,
  u.telefono AS usuario_telefono,
  u.contacto_emergencia_1_nombre,
  u.contacto_emergencia_1_telefono,
  d.id AS dispositivo,
  d.bateria AS dispositivo_bateria,
  a.nombre AS atendido_por
FROM eventos e
INNER JOIN usuarios u ON e.usuario_id = u.id
LEFT JOIN dispositivos d ON e.dispositivo_id = d.id
LEFT JOIN administradores a ON e.atendido_por = a.id
ORDER BY e.fecha_evento DESC;

-- ============================================================================
-- PROCEDIMIENTOS ALMACENADOS
-- ============================================================================

DELIMITER //

CREATE PROCEDURE obtener_estadisticas_dashboard()
BEGIN
  SELECT 
    (SELECT COUNT(*) FROM usuarios WHERE estado = 'activo') AS total_usuarios,
    (SELECT COUNT(*) FROM dispositivos WHERE estado = 'activo') AS dispositivos_activos,
    (SELECT COUNT(*) FROM eventos WHERE estado = 'Pendiente') AS alertas_pendientes,
    (SELECT COUNT(*) FROM eventos WHERE DATE(fecha_evento) = CURDATE()) AS eventos_hoy;
END //

DELIMITER ;

-- ============================================================================
-- DATOS DE PRUEBA
-- ============================================================================

-- Administradores (Password: Admin123!)
INSERT INTO administradores (nombre, email, password, rol) VALUES 
('Administrador Principal', 'admin@novaguardian.com', '$2y$10$73blX8ZuscqyD1qPAtxHB.RwjNZxIfGjgkWg/PQMTLofq69320gL2', 'superadmin'),
('Juan Pérez', 'juan.perez@novaguardian.com', '$2y$10$73blX8ZuscqyD1qPAtxHB.RwjNZxIfGjgkWg/PQMTLofq69320gL2', 'admin');

-- Usuarios (Adultos Mayores)
INSERT INTO usuarios (nombre_completo, email, telefono, fecha_nacimiento, 
  contacto_emergencia_1_nombre, contacto_emergencia_1_telefono, contacto_emergencia_1_relacion,
  contacto_emergencia_2_nombre, contacto_emergencia_2_telefono, contacto_emergencia_2_relacion,
  condiciones_medicas) VALUES 
('María López García', 'maria.lopez@email.com', '442-123-4567', '1945-03-15',
  'Ana López', '442-111-2222', 'Hija',
  'Carlos López', '442-333-4444', 'Hijo',
  'Hipertensión arterial, Diabetes tipo 2'),
('José Ramírez Sánchez', 'jose.ramirez@email.com', '442-234-5678', '1950-07-22',
  'Patricia Ramírez', '442-555-6666', 'Hija',
  'Roberto Ramírez', '442-777-8888', 'Hijo',
  'Artritis reumatoide'),
('Elena Martínez Cruz', 'elena.martinez@email.com', '442-345-6789', '1948-11-30',
  'Miguel Martínez', '442-999-0000', 'Esposo',
  'Laura Martínez', '442-111-3333', 'Hija',
  'Osteoporosis'),
('Antonio González Ruiz', 'antonio.gonzalez@email.com', '442-456-7890', '1952-02-10',
  'Carmen González', '442-222-4444', 'Esposa',
  'David González', '442-555-7777', 'Hijo',
  NULL),
('Rosa Hernández Flores', 'rosa.hernandez@email.com', '442-567-8901', '1947-09-05',
  'Jorge Hernández', '442-666-8888', 'Hijo',
  'Marta Hernández', '442-999-1111', 'Hija',
  'Hipotiroidismo, Hipertensión');

-- Dispositivos
INSERT INTO dispositivos (id, usuario_id, estado, bateria, ultima_conexion) VALUES 
('NG-001', 1, 'activo', 85, NOW() - INTERVAL 5 MINUTE),
('NG-002', 2, 'activo', 45, NOW() - INTERVAL 15 MINUTE),
('NG-003', 3, 'activo', 15, NOW() - INTERVAL 1 HOUR),
('NG-004', 4, 'activo', 92, NOW() - INTERVAL 2 MINUTE),
('NG-005', 5, 'activo', 68, NOW() - INTERVAL 30 MINUTE),
('NG-006', NULL, 'inactivo', 100, NULL),
('NG-007', NULL, 'mantenimiento', 0, NOW() - INTERVAL 2 DAY);

-- Eventos
INSERT INTO eventos (usuario_id, dispositivo_id, tipo, descripcion, estado, latitud, longitud, fecha_evento) VALUES 
(1, 'NG-001', 'SOS', 'Botón de emergencia presionado', 'Pendiente', 20.5888, -100.3899, NOW() - INTERVAL 30 MINUTE),
(2, 'NG-002', 'Caída', 'Caída detectada por acelerómetro', 'Atendido', 20.5910, -100.3920, NOW() - INTERVAL 2 HOUR),
(3, 'NG-003', 'Batería baja', 'Nivel de batería crítico (15%)', 'Pendiente', 20.5875, -100.3885, NOW() - INTERVAL 1 HOUR),
(1, 'NG-001', 'Pulso anormal', 'Frecuencia cardíaca elevada (120 bpm)', 'Resuelto', 20.5888, -100.3899, NOW() - INTERVAL 1 DAY),
(4, 'NG-004', 'SOS', 'Botón de emergencia presionado', 'Resuelto', 20.5900, -100.3910, NOW() - INTERVAL 1 DAY),
(2, 'NG-002', 'Caída', 'Caída detectada', 'Resuelto', 20.5910, -100.3920, NOW() - INTERVAL 3 DAY),
(5, 'NG-005', 'Pulso anormal', 'Frecuencia cardíaca baja (45 bpm)', 'Resuelto', 20.5865, -100.3870, NOW() - INTERVAL 3 DAY),
(3, 'NG-003', 'SOS', 'Botón de emergencia presionado', 'Resuelto', 20.5875, -100.3885, NOW() - INTERVAL 5 DAY),
(1, 'NG-001', 'Caída', 'Caída detectada', 'Resuelto', 20.5888, -100.3899, NOW() - INTERVAL 5 DAY),
(4, 'NG-004', 'Batería baja', 'Nivel de batería bajo (20%)', 'Resuelto', 20.5900, -100.3910, NOW() - INTERVAL 7 DAY),
(5, 'NG-005', 'Pulso anormal', 'Frecuencia cardíaca elevada (115 bpm)', 'Resuelto', 20.5865, -100.3870, NOW() - INTERVAL 7 DAY),
(2, 'NG-002', 'SOS', 'Botón de emergencia presionado', 'Resuelto', 20.5910, -100.3920, NOW() - INTERVAL 10 DAY),
(3, 'NG-003', 'Caída', 'Caída detectada', 'Resuelto', 20.5875, -100.3885, NOW() - INTERVAL 10 DAY);

-- ============================================================================
-- VERIFICACIÓN
-- ============================================================================
SELECT 'Base de datos creada exitosamente' AS Estado;
SELECT 'Administradores:' AS Info, COUNT(*) AS Total FROM administradores
UNION ALL SELECT 'Usuarios:', COUNT(*) FROM usuarios
UNION ALL SELECT 'Dispositivos:', COUNT(*) FROM dispositivos
UNION ALL SELECT 'Eventos:', COUNT(*) FROM eventos;

-- ============================================================================
-- CREDENCIALES
-- ============================================================================
-- Email: admin@novaguardian.com
-- Password: Admin123!
-- ============================================================================
