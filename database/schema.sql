-- ====================================================================
-- BASE DE DATOS: TOP GOL - Alquiler de Canchas de Fútbol
-- ====================================================================
-- Script de inicialización y datos de demostración
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `topgol_db` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `topgol_db`;

-- --------------------------------------------------------------------
-- 1. TABLA: usuarios
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `reservas`;
DROP TABLE IF EXISTS `canchas`;
DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `rol` ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
  `google_id` VARCHAR(255) DEFAULT NULL,
  `avatar` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_usuario_email` (`email`),
  UNIQUE KEY `idx_usuario_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 2. TABLA: canchas
-- --------------------------------------------------------------------
CREATE TABLE `canchas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `precio_hora` DECIMAL(10,2) NOT NULL,
  `capacidad` INT NOT NULL,
  `tipo` ENUM('futbol_5', 'futbol_7', 'futbol_11') NOT NULL DEFAULT 'futbol_5',
  `iluminacion` TINYINT(1) NOT NULL DEFAULT 1,
  `techada` TINYINT(1) NOT NULL DEFAULT 0,
  `estado` ENUM('disponible', 'mantenimiento') NOT NULL DEFAULT 'disponible',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 3. TABLA: reservas
-- --------------------------------------------------------------------
CREATE TABLE `reservas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `cancha_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  `duracion_horas` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `total_pago` DECIMAL(10,2) NOT NULL,
  `estado` ENUM('pendiente', 'confirmada', 'cancelada', 'finalizada') NOT NULL DEFAULT 'pendiente',
  `observaciones` TEXT DEFAULT NULL,
  `metodo_pago` ENUM('yape', 'transferencia_bcp', 'efectivo') DEFAULT NULL,
  `adelanto_monto` DECIMAL(10,2) NOT NULL DEFAULT 20.00,
  `cliente_nombre` VARCHAR(120) DEFAULT NULL,
  `contacto_telefono` VARCHAR(20) DEFAULT NULL,
  `evento_grupo` VARCHAR(32) DEFAULT NULL,
  `pago_estado` ENUM('pendiente', 'en_revision', 'verificado', 'rechazado') NOT NULL DEFAULT 'pendiente',
  `comprobante_ruta` VARCHAR(500) DEFAULT NULL,
  `comprobante_nombre` VARCHAR(255) DEFAULT NULL,
  `comprobante_tipo` VARCHAR(100) DEFAULT NULL,
  `comprobante_size` INT DEFAULT NULL,
  `comprobante_subido_at` TIMESTAMP NULL DEFAULT NULL,
  `pago_verificado_at` TIMESTAMP NULL DEFAULT NULL,
  `pago_verificado_por` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reserva_usuario` (`usuario_id`),
  KEY `idx_reserva_cancha` (`cancha_id`),
  KEY `idx_reserva_horario` (`cancha_id`, `fecha`, `hora_inicio`, `hora_fin`),
  KEY `idx_reserva_pago_estado` (`pago_estado`),
  CONSTRAINT `fk_reserva_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reserva_cancha` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reserva_verificado_por` FOREIGN KEY (`pago_verificado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 4. TABLA: productos (inventario: refrescos, bebidas, snacks)
-- --------------------------------------------------------------------
CREATE TABLE `productos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `categoria` ENUM('refrescos', 'bebidas_alcoholicas', 'snacks', 'hidratantes', 'otros') NOT NULL DEFAULT 'otros',
  `precio` DECIMAL(10,2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `stock_minimo` INT NOT NULL DEFAULT 5,
  `unidad` VARCHAR(20) NOT NULL DEFAULT 'unidad',
  `estado` ENUM('disponible', 'agotado', 'descontinuado') NOT NULL DEFAULT 'disponible',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_producto_categoria` (`categoria`),
  KEY `idx_producto_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 5. TABLA: notificaciones (campanita tiempo real)
-- --------------------------------------------------------------------
CREATE TABLE `notificaciones` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `tipo` VARCHAR(30) NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `mensaje` VARCHAR(255) NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `leida` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_usuario` (`usuario_id`),
  KEY `idx_notif_leida` (`leida`),
  CONSTRAINT `fk_notif_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- DATOS DE DEMOSTRACIÓN (SEEDS)
-- ====================================================================

-- Inserción de Usuarios de Prueba
-- Contraseña Admin: admin123
-- Contraseña Cliente: cliente123
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `telefono`, `rol`, `created_at`, `updated_at`) VALUES
(1, 'Carlos Administrador', 'admin@topgol.com', '$2y$10$OiuK1BALSi0zSYuQ/rNCquw4zWVSNy5GyRUebaFTabHhBAr9NbXry', '+51 987654321', 'admin', NOW(), NOW()),
(2, 'Juan Pérez (Cliente)', 'cliente@topgol.com', '$2y$10$M1LfZcYe4VwpawZqFrdIO.4D63N82OC4B1drN4kQG4cJlvqVfj3ri', '+51 912345678', 'cliente', NOW(), NOW());

-- Inserción de 5 Canchas de Prueba
INSERT INTO `canchas` (`id`, `nombre`, `descripcion`, `precio_hora`, `capacidad`, `tipo`, `iluminacion`, `techada`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'Cancha 1 - La Bombonera', 'Cancha de fútbol 5 con techo cobertor térmico, césped sintético monofilamento de 50mm y arcos reglamentarios.', 60.00, 10, 'futbol_5', 1, 1, 'disponible', NOW(), NOW()),
(2, 'Cancha 2 - El Monumental', 'Cancha de fútbol 7 al aire libre con césped de alta densidad, excelente drenaje e iluminación LED nocturna de 8 focos.', 90.00, 14, 'futbol_7', 1, 0, 'disponible', NOW(), NOW()),
(3, 'Cancha 3 - Camp Nou', 'Campo reglamentario para fútbol 11 profesional, ideal para torneos interempresariales, campeonatos y eventos corporativos.', 160.00, 22, 'futbol_11', 1, 0, 'disponible', NOW(), NOW()),
(4, 'Cancha 4 - Santiago Bernabéu', 'Cancha de fútbol 7 totalmente techada y climatizada, graderías cómodas para espectadores y vestuarios VIP adyacentes.', 110.00, 14, 'futbol_7', 1, 1, 'disponible', NOW(), NOW()),
(5, 'Cancha 5 - Maracaná', 'Cancha de fútbol 5 rápida al aire libre con iluminación panorámica y redes de protección perimétricas de máxima seguridad.', 55.00, 10, 'futbol_5', 1, 0, 'disponible', NOW(), NOW());

-- Inserción de 5 Reservas de Prueba
INSERT INTO `reservas` (`id`, `usuario_id`, `cancha_id`, `fecha`, `hora_inicio`, `hora_fin`, `duracion_horas`, `total_pago`, `estado`, `observaciones`, `metodo_pago`, `adelanto_monto`, `cliente_nombre`, `contacto_telefono`, `pago_estado`, `comprobante_ruta`, `comprobante_nombre`, `comprobante_tipo`, `comprobante_size`, `comprobante_subido_at`, `created_at`, `updated_at`) VALUES
(1, 2, 1, CURDATE(), '18:00:00', '19:00:00', 1, 60.00, 'confirmada', 'Partido con amigos de la oficina, solicitar 10 chalecos.', 'yape', 20.00, 'Juan Pérez (Cliente)', '+51 912345678', 'verificado', 'uploads/comprobantes/demo-4587.jpg', 'yape-4587.jpg', 'image/jpeg', 182400, NOW(), NOW(), NOW()),
(2, 2, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '20:00:00', '22:00:00', 2, 180.00, 'pendiente', 'Semifinal del torneo de fin de semana.', 'yape', 20.00, 'Juan Pérez (Cliente)', '+51 912345678', 'en_revision', 'uploads/comprobantes/demo-4586.jpg', 'yape-4586.jpg', 'image/jpeg', 195300, NOW(), NOW(), NOW()),
(3, 2, 4, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '19:00:00', '20:00:00', 1, 110.00, 'confirmada', 'Llevar balón número 5 oficial.', 'transferencia_bcp', 20.00, 'Juan Pérez (Cliente)', '+51 912345678', 'verificado', 'uploads/comprobantes/demo-4585.pdf', 'bcp-4585.pdf', 'application/pdf', 210800, NOW(), NOW(), NOW()),
(4, 2, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '16:00:00', '18:00:00', 2, 320.00, 'pendiente', 'Partido amistoso de fútbol 11.', NULL, 20.00, NULL, NULL, 'pendiente', NULL, NULL, NULL, NULL, NULL, NOW(), NOW()),
(5, 2, 5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '17:00:00', '18:00:00', 1, 55.00, 'finalizada', 'Partido jugado y cancelado en caja satisfactoriamente.', 'yape', 20.00, 'Juan Pérez (Cliente)', '+51 912345678', 'verificado', 'uploads/comprobantes/demo-4584.jpg', 'yape-4584.jpg', 'image/jpeg', 176900, NOW(), NOW(), NOW());

-- Inserción de Productos de Prueba (inventario)
INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `categoria`, `precio`, `stock`, `stock_minimo`, `unidad`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'Gatorade 500ml', 'Bebida hidratante sabor variado', 'hidratantes', 10.00, 48, 10, 'botella', 'disponible', NOW(), NOW()),
(2, 'Agua San Luis 625ml', 'Agua mineral sin gas', 'refrescos', 3.00, 60, 12, 'botella', 'disponible', NOW(), NOW()),
(3, 'Coca-Cola 500ml', 'Gaseosa personal', 'refrescos', 5.00, 36, 10, 'botella', 'disponible', NOW(), NOW()),
(4, 'Cerveza Cristal 650ml', 'Cerveza rubia retornable', 'bebidas_alcoholicas', 9.00, 24, 6, 'botella', 'disponible', NOW(), NOW()),
(5, 'Pilsen Callao 650ml', 'Cerveza rubia retornable', 'bebidas_alcoholicas', 9.00, 4, 6, 'botella', 'disponible', NOW(), NOW()),
(6, 'Papas Lays 150g', 'Snack de papas fritas', 'snacks', 7.50, 20, 5, 'bolsa', 'disponible', NOW(), NOW()),
(7, 'Chizitos 100g', 'Snack de maíz con queso', 'snacks', 4.00, 0, 5, 'bolsa', 'agotado', NOW(), NOW()),
(8, 'Balón Top Gol N°5', 'Balón oficial de la casa', 'otros', 40.00, 8, 2, 'unidad', 'disponible', NOW(), NOW());