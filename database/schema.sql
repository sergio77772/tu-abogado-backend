-- Base de datos para "Tu Abogado en Línea"

-- Tabla de usuarios (clientes, abogados y admins)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    contraseña VARCHAR(255) NOT NULL,
    rol ENUM('cliente', 'abogado', 'admin') NOT NULL DEFAULT 'cliente',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT(1) DEFAULT 1,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de planes
CREATE TABLE IF NOT EXISTS planes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tipo ENUM('paquete', 'suscripcion') NOT NULL,
    cantidad_consultas INT NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    duracion_dias INT DEFAULT NULL, -- Para suscripciones
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de compras
CREATE TABLE IF NOT EXISTS compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    plan_id INT NOT NULL,
    estado ENUM('pendiente', 'pagada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    fecha_compra DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_pago DATETIME DEFAULT NULL,
    monto DECIMAL(10, 2) NOT NULL,
    transaccion_id VARCHAR(255) DEFAULT NULL, -- ID de transacción de Mercado Pago/PayPal/Stripe
    metodo_pago VARCHAR(50) DEFAULT NULL,
    consultas_totales INT NOT NULL, -- Consultas incluidas en este plan
    consultas_usadas INT DEFAULT 0,
    consultas_disponibles INT NOT NULL, -- Calculado: consultas_totales - consultas_usadas
    fecha_expiracion DATETIME DEFAULT NULL, -- Para suscripciones
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES planes(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_compra (fecha_compra)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de consultas
CREATE TABLE IF NOT EXISTS consultas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    abogado_id INT DEFAULT NULL, -- Asignado cuando el abogado responde
    compra_id INT NOT NULL, -- Relación con la compra que permite esta consulta
    estado ENUM('abierta', 'respondida', 'cerrada') NOT NULL DEFAULT 'abierta',
    asunto VARCHAR(255) NOT NULL,
    mensaje_inicial TEXT NOT NULL,
    respuesta TEXT DEFAULT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta DATETIME DEFAULT NULL,
    fecha_cierre DATETIME DEFAULT NULL,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (abogado_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_abogado (abogado_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_creacion (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar algunos planes de ejemplo
INSERT INTO planes (nombre, descripcion, tipo, cantidad_consultas, precio, duracion_dias) VALUES
('Paquete Básico', '3 consultas legales anuales', 'paquete', 3, 50000.00, 365),
('Paquete Intermedio', '6 consultas legales anuales', 'paquete', 6, 90000.00, 365),
('Suscripción Mensual', '2 consultas mensuales', 'suscripcion', 2, 20000.00, 30),
('Suscripción Anual', '24 consultas anuales (2 por mes)', 'suscripcion', 24, 180000.00, 365);

-- Insertar un usuario admin por defecto (contraseña: admin123)
-- NOTA: Cambiar esta contraseña en producción
INSERT INTO usuarios (nombre, email, contraseña, rol) VALUES
('Administrador', 'admin@tuabogado.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

