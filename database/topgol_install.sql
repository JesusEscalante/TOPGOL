-- ====================================================================
-- TOP GOL V1 - SCRIPT DE INSTALACION COMPLETO (INSTALACION NUEVA)
-- ====================================================================
-- Autor        : TOP GOL Dev Team
-- Generado por : Auditoria Tecnica Antigravity - 2026-10-15
-- Version      : V1 (esquema final post-migracion 015)
-- ====================================================================
-- INSTRUCCIONES DE USO:
--   1. Crear o seleccionar la base de datos de destino (ej. topgol_db o la definida en .env):
--      CREATE DATABASE IF NOT EXISTS `topgol_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--      USE `topgol_db`;
--   2. Importar este archivo (topgol_install.sql) mediante phpMyAdmin o terminal:
--      mysql -u root -p topgol_db < database/topgol_install.sql
--   3. NO ejecutar schema.sql sobre una base existente con datos.
--   4. Para actualizar una base existente, aplicar solo las migraciones pendientes.
-- ====================================================================


-- 1. USUARIOS
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100) NOT NULL,
  `email`       VARCHAR(100) NOT NULL,
  `password`    VARCHAR(255) DEFAULT NULL,
  `telefono`    VARCHAR(20) DEFAULT NULL,
  `rol`         ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
  `google_id`   VARCHAR(255) DEFAULT NULL,
  `avatar`      VARCHAR(500) DEFAULT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_usuario_email`     (`email`),
  UNIQUE KEY `idx_usuario_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CANCHAS
CREATE TABLE IF NOT EXISTS `canchas` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `precio_hora` DECIMAL(10,2) NOT NULL,
  `capacidad`   INT NOT NULL,
  `tipo`        ENUM('futbol_5','futbol_7','futbol_11') NOT NULL DEFAULT 'futbol_5',
  `iluminacion` TINYINT(1) NOT NULL DEFAULT 1,
  `techada`     TINYINT(1) NOT NULL DEFAULT 0,
  `estado`      ENUM('disponible','mantenimiento') NOT NULL DEFAULT 'disponible',
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. RESERVAS (esquema final con todas las columnas de migraciones 002-015)
CREATE TABLE IF NOT EXISTS `reservas` (
  `id`                        INT NOT NULL AUTO_INCREMENT,
  `usuario_id`                INT NOT NULL,
  `cancha_id`                 INT NOT NULL,
  `fecha`                     DATE NOT NULL,
  `hora_inicio`               TIME NOT NULL,
  `hora_fin`                  TIME NOT NULL,
  `duracion_horas`            DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `total_pago`                DECIMAL(10,2) NOT NULL,
  `estado`                    ENUM('pendiente','confirmada','cancelada','finalizada') NOT NULL DEFAULT 'pendiente',
  `asistencia_estado`         ENUM('sin_registrar','presente','tardanza','retraso_confirmado','no_asistio') NOT NULL DEFAULT 'sin_registrar',
  `asistencia_hora`           TIME NULL DEFAULT NULL,
  `asistencia_notas`          VARCHAR(255) NULL DEFAULT NULL,
  `asistencia_registrada_por` INT NULL DEFAULT NULL,
  `asistencia_updated_at`     TIMESTAMP NULL DEFAULT NULL,
  `reprogramado_de_cancha_id` INT NULL DEFAULT NULL,
  `reprogramado_de_fecha`     DATE NULL DEFAULT NULL,
  `reprogramado_de_hora`      TIME NULL DEFAULT NULL,
  `reprogramado_por`          INT NULL DEFAULT NULL,
  `reprogramado_at`           TIMESTAMP NULL DEFAULT NULL,
  `motivo_reprogramacion`     VARCHAR(255) NULL DEFAULT NULL,
  `contacto_realizado_at`     TIMESTAMP NULL DEFAULT NULL,
  `contacto_resultado`        VARCHAR(100) NULL DEFAULT NULL,
  `es_demo`                   TINYINT(1) NOT NULL DEFAULT 0,
  `observaciones`             TEXT DEFAULT NULL,
  `metodo_pago`               ENUM('yape','transferencia_bcp','efectivo','niubiz') DEFAULT NULL,
  `origen`                    ENUM('web','presencial','llamada','whatsapp') NOT NULL DEFAULT 'web',
  `adelanto_monto`            DECIMAL(10,2) NOT NULL DEFAULT 20.00,
  `cliente_nombre`            VARCHAR(120) DEFAULT NULL,
  `contacto_telefono`         VARCHAR(20) DEFAULT NULL,
  `evento_grupo`              VARCHAR(32) DEFAULT NULL,
  `pago_estado`               ENUM('pendiente','en_revision','verificado','rechazado') NOT NULL DEFAULT 'pendiente',
  `comprobante_ruta`          VARCHAR(500) DEFAULT NULL,
  `comprobante_nombre`        VARCHAR(255) DEFAULT NULL,
  `comprobante_tipo`          VARCHAR(100) DEFAULT NULL,
  `comprobante_size`          INT DEFAULT NULL,
  `comprobante_subido_at`     TIMESTAMP NULL DEFAULT NULL,
  `pago_verificado_at`        TIMESTAMP NULL DEFAULT NULL,
  `pago_verificado_por`       INT DEFAULT NULL,
  `created_at`                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reserva_usuario`       (`usuario_id`),
  KEY `idx_reserva_cancha`        (`cancha_id`),
  KEY `idx_reserva_horario`       (`cancha_id`,`fecha`,`hora_inicio`,`hora_fin`),
  KEY `idx_reserva_pago_estado`   (`pago_estado`),
  KEY `idx_reserva_evento_grupo`  (`evento_grupo`),
  KEY `idx_reserva_asistencia`    (`asistencia_estado`),
  KEY `idx_reserva_contacto`      (`contacto_realizado_at`),
  KEY `idx_reservas_es_demo`      (`es_demo`),
  CONSTRAINT `fk_reserva_usuario`           FOREIGN KEY (`usuario_id`)                REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reserva_cancha`            FOREIGN KEY (`cancha_id`)                 REFERENCES `canchas`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reserva_verificado_por`    FOREIGN KEY (`pago_verificado_por`)        REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reserva_asistencia_admin`  FOREIGN KEY (`asistencia_registrada_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reserva_reprogramado_por`  FOREIGN KEY (`reprogramado_por`)          REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CATEGORIAS
CREATE TABLE IF NOT EXISTS `categorias` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`      VARCHAR(60) NOT NULL,
  `slug`        VARCHAR(60) NOT NULL UNIQUE,
  `descripcion` VARCHAR(255) NULL,
  `icono`       VARCHAR(30) NOT NULL DEFAULT 'bi-box',
  `activo`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `categorias` (`id`,`nombre`,`slug`,`descripcion`,`icono`,`activo`) VALUES
(1,'Refrescos','refrescos','Gaseosas y bebidas refrescantes','bi-cup-straw',1),
(2,'Bebidas alcoholicas','bebidas_alcoholicas','Cervezas y afines para mayores de edad','bi-cup-hot',1),
(3,'Snacks','snacks','Papas fritas, chizitos y piqueos','bi-basket',1),
(4,'Hidratantes','hidratantes','Bebidas isotónicas y deportivas','bi-droplet',1),
(5,'Otros','otros','Balones, accesorios y otros artículos','bi-box',1);

-- 5. PRODUCTOS
CREATE TABLE IF NOT EXISTS `productos` (
  `id`           INT NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(100) NOT NULL,
  `descripcion`  VARCHAR(255) DEFAULT NULL,
  `categoria`    ENUM('refrescos','bebidas_alcoholicas','snacks','hidratantes','otros') NOT NULL DEFAULT 'otros',
  `categoria_id` INT UNSIGNED NULL,
  `precio`       DECIMAL(10,2) NOT NULL,
  `stock`        INT NOT NULL DEFAULT 0,
  `stock_minimo` INT NOT NULL DEFAULT 5,
  `unidad`       VARCHAR(20) NOT NULL DEFAULT 'unidad',
  `estado`       ENUM('disponible','agotado','descontinuado') NOT NULL DEFAULT 'disponible',
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_producto_categoria` (`categoria`),
  KEY `idx_producto_estado`    (`estado`),
  CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. NOTIFICACIONES
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT NOT NULL,
  `tipo`        VARCHAR(30) NOT NULL,
  `titulo`      VARCHAR(150) NOT NULL,
  `mensaje`     VARCHAR(255) NOT NULL,
  `link`        VARCHAR(255) DEFAULT NULL,
  `leida`       TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_usuario` (`usuario_id`),
  KEY `idx_notif_leida`   (`leida`),
  CONSTRAINT `fk_notif_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. PAGOS
CREATE TABLE IF NOT EXISTS `pagos` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `reserva_id`     INT NOT NULL,
  `tipo`           ENUM('adelanto','saldo','completo') NOT NULL DEFAULT 'adelanto',
  `monto`          DECIMAL(10,2) NOT NULL,
  `metodo_pago`    ENUM('yape','efectivo','niubiz') NOT NULL,
  `estado`         ENUM('pendiente','en_revision','verificado','rechazado') NOT NULL DEFAULT 'verificado',
  `es_demo`        TINYINT(1) NOT NULL DEFAULT 0,
  `registrado_por` INT NULL,
  `notas`          VARCHAR(255) NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pagos_reserva`  FOREIGN KEY (`reserva_id`)     REFERENCES `reservas` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_pagos_usuario`  FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  INDEX `idx_pagos_reserva` (`reserva_id`),
  INDEX `idx_pagos_fecha`   (`created_at`),
  INDEX `idx_pagos_metodo`  (`metodo_pago`),
  INDEX `idx_pagos_estado`  (`estado`),
  INDEX `idx_pagos_tipo`    (`tipo`),
  INDEX `idx_pagos_es_demo` (`es_demo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. MOVIMIENTOS INVENTARIO (Kardex)
CREATE TABLE IF NOT EXISTS `movimientos_inventario` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `producto_id`    INT NOT NULL,
  `tipo`           ENUM('ingreso','salida','venta','ajuste','saldo_apertura') NOT NULL,
  `cantidad`       INT NOT NULL,
  `stock_anterior` INT NOT NULL,
  `stock_nuevo`    INT NOT NULL,
  `registrado_por` INT NULL,
  `motivo`         VARCHAR(255) NOT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_mov_producto` (`producto_id`),
  KEY `idx_mov_fecha`    (`created_at`),
  KEY `idx_mov_tipo`     (`tipo`),
  CONSTRAINT `fk_mov_producto` FOREIGN KEY (`producto_id`)    REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_admin`    FOREIGN KEY (`registrado_por`) REFERENCES `usuarios`  (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. VENTAS POS
CREATE TABLE IF NOT EXISTS `ventas` (
  `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo_operacion`  VARCHAR(30) NOT NULL UNIQUE,
  `idempotency_token` VARCHAR(64) NULL UNIQUE,
  `cliente_nombre`    VARCHAR(100) NULL DEFAULT 'Cliente Mostrador',
  `total`             DECIMAL(10,2) NOT NULL,
  `metodo_pago`       ENUM('efectivo','niubiz') NOT NULL,
  `estado`            ENUM('completada','anulada') NOT NULL DEFAULT 'completada',
  `es_demo`           TINYINT(1) NOT NULL DEFAULT 0,
  `registrado_por`    INT NOT NULL,
  `notas`             VARCHAR(255) NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_ventas_fecha`   (`created_at`),
  KEY `idx_ventas_metodo`  (`metodo_pago`),
  KEY `idx_ventas_estado`  (`estado`),
  KEY `idx_ventas_es_demo` (`es_demo`),
  CONSTRAINT `fk_ventas_admin` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. VENTA_DETALLES
CREATE TABLE IF NOT EXISTS `venta_detalles` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `venta_id`        INT UNSIGNED NOT NULL,
  `producto_id`     INT NOT NULL,
  `cantidad`        INT NOT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  `subtotal`        DECIMAL(10,2) NOT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_vdetalles_venta`    (`venta_id`),
  KEY `idx_vdetalles_producto` (`producto_id`),
  CONSTRAINT `fk_vdetalles_venta`    FOREIGN KEY (`venta_id`)    REFERENCES `ventas`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_vdetalles_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. ESPACIOS EVENTOS Y CUMPLEANOS
CREATE TABLE IF NOT EXISTS `espacios_eventos` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `estado`      ENUM('disponible','mantenimiento') NOT NULL DEFAULT 'disponible',
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `espacios_eventos` (`id`,`nombre`,`descripcion`) VALUES
(1,'Salon de Cumpleanos / Reuniones','Espacio exclusivo con pantalla y comodidades para tu evento.');

-- 12. SOLICITUDES DE ESPACIOS
CREATE TABLE IF NOT EXISTS `solicitudes_espacios` (
  `id`                INT NOT NULL AUTO_INCREMENT,
  `espacio_id`        INT NOT NULL,
  `usuario_id`        INT NOT NULL,
  `fecha`             DATE NOT NULL,
  `hora_inicio`       TIME NOT NULL,
  `duracion_horas`    DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `contacto_nombre`   VARCHAR(120) NOT NULL,
  `contacto_telefono` VARCHAR(20) NOT NULL,
  `observaciones`     TEXT DEFAULT NULL,
  `estado`            ENUM('pendiente','cotizado','confirmado','rechazado','cancelado') NOT NULL DEFAULT 'pendiente',
  `precio_cotizado`   DECIMAL(10,2) DEFAULT NULL,
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_solicitudes_espacio` FOREIGN KEY (`espacio_id`) REFERENCES `espacios_eventos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_solicitudes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`         (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SEEDS: DATOS DE DEMOSTRACION
-- Contrasena Admin  : admin123
-- Contrasena Cliente: cliente123
-- ====================================================================
INSERT IGNORE INTO `usuarios` (`id`,`nombre`,`email`,`password`,`telefono`,`rol`) VALUES
(1,'Carlos Administrador','admin@topgol.com','$2y$10$OiuK1BALSi0zSYuQ/rNCquw4zWVSNy5GyRUebaFTabHhBAr9NbXry','+51 987654321','admin'),
(2,'Juan Perez Cliente','cliente@topgol.com','$2y$10$M1LfZcYe4VwpawZqFrdIO.4D63N82OC4B1drN4kQG4cJlvqVfj3ri','+51 912345678','cliente');

INSERT IGNORE INTO `canchas` (`id`,`nombre`,`descripcion`,`precio_hora`,`capacidad`,`tipo`,`iluminacion`,`techada`,`estado`) VALUES
(1,'Cancha 1','Cancha de futbol 5',60.00,10,'futbol_5',1,0,'disponible'),
(2,'Cancha 2','Cancha de futbol 5',60.00,10,'futbol_5',1,0,'disponible'),
(3,'Cancha 3','Cancha de futbol 5',60.00,10,'futbol_5',1,0,'disponible'),
(4,'Cancha 4','Cancha de futbol 5',60.00,10,'futbol_5',1,0,'disponible'),
(5,'Cancha 5','Cancha de futbol 5',60.00,10,'futbol_5',1,0,'disponible'),
(6,'Cancha 6','Cancha de futbol 5',60.00,10,'futbol_5',1,0,'disponible');

INSERT IGNORE INTO `productos` (`id`,`nombre`,`descripcion`,`categoria`,`categoria_id`,`precio`,`stock`,`stock_minimo`,`unidad`,`estado`) VALUES
(1,'Gatorade 500ml','Bebida hidratante','hidratantes',4,10.00,48,10,'botella','disponible'),
(2,'Agua San Luis 625ml','Agua mineral sin gas','refrescos',1,3.00,60,12,'botella','disponible'),
(3,'Coca-Cola 500ml','Gaseosa personal','refrescos',1,5.00,36,10,'botella','disponible'),
(4,'Cerveza Cristal 650ml','Cerveza rubia retornable','bebidas_alcoholicas',2,9.00,24,6,'botella','disponible'),
(5,'Pilsen Callao 650ml','Cerveza rubia retornable','bebidas_alcoholicas',2,9.00,4,6,'botella','disponible'),
(6,'Papas Lays 150g','Snack de papas fritas','snacks',3,7.50,20,5,'bolsa','disponible'),
(7,'Chizitos 100g','Snack de maiz con queso','snacks',3,4.00,0,5,'bolsa','agotado'),
(8,'Balon Top Gol N5','Balon oficial de la casa','otros',5,40.00,8,2,'unidad','disponible');
-- ====================================================================
-- FIN TOPGOL_INSTALL.SQL
-- ====================================================================
