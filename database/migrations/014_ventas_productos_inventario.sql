-- ====================================================================
-- TOP GOL - MIGRACIÓN 014: VENTAS, PRODUCTOS E INVENTARIO
-- ====================================================================
-- Agrega:
-- 1. Tabla `categorias` para gestión dinámica de categorías de productos.
-- 2. Columna `categoria_id` en `productos` con migración de valores existentes y FK RESTRICT.
-- 3. Tabla `movimientos_inventario` (Kardex auditable con stock anterior y nuevo).
-- 4. Tabla `ventas` con código de operación, token único de idempotencia y desglose de método de pago.
-- 5. Tabla `venta_detalles` con precio unitario congelado y protección referencial RESTRICT.
-- ====================================================================

-- 1. Crear tabla `categorias`
CREATE TABLE IF NOT EXISTS `categorias` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(60) NOT NULL,
    `slug` VARCHAR(60) NOT NULL UNIQUE,
    `descripcion` VARCHAR(255) NULL,
    `icono` VARCHAR(30) NOT NULL DEFAULT 'bi-box',
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sembrar las categorías base del establecimiento
INSERT IGNORE INTO `categorias` (`id`, `nombre`, `slug`, `descripcion`, `icono`, `activo`) VALUES
(1, 'Refrescos', 'refrescos', 'Gaseosas y bebidas refrescantes', 'bi-cup-straw', 1),
(2, 'Bebidas alcohólicas', 'bebidas_alcoholicas', 'Cervezas y afines para mayores de edad', 'bi-cup-hot', 1),
(3, 'Snacks', 'snacks', 'Papas fritas, chizitos y piqueos', 'bi-basket', 1),
(4, 'Hidratantes', 'hidratantes', 'Bebidas isotónicas y deportivas', 'bi-droplet', 1),
(5, 'Otros', 'otros', 'Balones, accesorios y otros artículos', 'bi-box', 1);

-- 2. Alterar tabla `productos` para relacionar `categoria_id`
ALTER TABLE `productos`
    ADD COLUMN `categoria_id` INT UNSIGNED NULL AFTER `categoria`;

-- Migrar la relación de categorías para los productos existentes
UPDATE `productos` SET `categoria_id` = 1 WHERE `categoria` = 'refrescos' AND `categoria_id` IS NULL;
UPDATE `productos` SET `categoria_id` = 2 WHERE `categoria` = 'bebidas_alcoholicas' AND `categoria_id` IS NULL;
UPDATE `productos` SET `categoria_id` = 3 WHERE `categoria` = 'snacks' AND `categoria_id` IS NULL;
UPDATE `productos` SET `categoria_id` = 4 WHERE `categoria` = 'hidratantes' AND `categoria_id` IS NULL;
UPDATE `productos` SET `categoria_id` = 5 WHERE (`categoria` = 'otros' OR `categoria` IS NULL) AND `categoria_id` IS NULL;

-- Asignar categoría 'otros' a cualquier producto residual sin categoria_id
UPDATE `productos` SET `categoria_id` = 5 WHERE `categoria_id` IS NULL;

-- Agregar clave foránea para categoria_id
ALTER TABLE `productos`
    ADD CONSTRAINT `fk_productos_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE;

-- 3. Crear tabla `movimientos_inventario`
CREATE TABLE IF NOT EXISTS `movimientos_inventario` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT(11) NOT NULL,
    `tipo` ENUM('ingreso', 'salida', 'venta', 'ajuste', 'saldo_apertura') NOT NULL,
    `cantidad` INT NOT NULL,
    `stock_anterior` INT NOT NULL,
    `stock_nuevo` INT NOT NULL,
    `registrado_por` INT NULL,
    `motivo` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_mov_producto` (`producto_id`),
    KEY `idx_mov_fecha` (`created_at`),
    KEY `idx_mov_tipo` (`tipo`),
    CONSTRAINT `fk_mov_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_mov_admin` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Crear tabla `ventas`
CREATE TABLE IF NOT EXISTS `ventas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo_operacion` VARCHAR(30) NOT NULL UNIQUE,
    `idempotency_token` VARCHAR(64) NULL UNIQUE,
    `cliente_nombre` VARCHAR(100) NULL DEFAULT 'Cliente Mostrador',
    `total` DECIMAL(10,2) NOT NULL,
    `metodo_pago` ENUM('efectivo', 'niubiz') NOT NULL,
    `estado` ENUM('completada', 'anulada') NOT NULL DEFAULT 'completada',
    `registrado_por` INT NOT NULL,
    `notas` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_ventas_fecha` (`created_at`),
    KEY `idx_ventas_metodo` (`metodo_pago`),
    KEY `idx_ventas_estado` (`estado`),
    CONSTRAINT `fk_ventas_admin` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Crear tabla `venta_detalles`
CREATE TABLE IF NOT EXISTS `venta_detalles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `venta_id` INT UNSIGNED NOT NULL,
    `producto_id` INT(11) NOT NULL,
    `cantidad` INT NOT NULL,
    `precio_unitario` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_vdetalles_venta` (`venta_id`),
    KEY `idx_vdetalles_producto` (`producto_id`),
    CONSTRAINT `fk_vdetalles_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_vdetalles_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
