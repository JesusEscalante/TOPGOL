CREATE TABLE IF NOT EXISTS `espacios_eventos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `estado` ENUM('disponible', 'mantenimiento') NOT NULL DEFAULT 'disponible',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `espacios_eventos` (`nombre`, `descripcion`) VALUES ('Salón de Cumpleaños / Reuniones', 'Espacio exclusivo con pantalla y comodidades para tu evento.');

CREATE TABLE IF NOT EXISTS `solicitudes_espacios` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `espacio_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `duracion_horas` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `contacto_nombre` VARCHAR(120) NOT NULL,
  `contacto_telefono` VARCHAR(20) NOT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `estado` ENUM('pendiente', 'cotizado', 'confirmado', 'rechazado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  `precio_cotizado` DECIMAL(10,2) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`espacio_id`) REFERENCES `espacios_eventos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
