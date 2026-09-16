-- ====================================================================
-- MIGRACIÓN: Tabla de productos (inventario del bar/kiosco)
-- ====================================================================
-- Refrescos, bebidas alcohólicas, snacks, hidratantes y otros.
-- ====================================================================

USE `topgol_db`;

CREATE TABLE IF NOT EXISTS `productos` (
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
