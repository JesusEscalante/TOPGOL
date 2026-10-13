-- ====================================================================
-- MIGRACIÓN 011: Módulo de Pagos, Saldos y Caja del Administrador
-- ====================================================================
-- 1. Ampliar ENUM metodo_pago en reservas para incluir 'niubiz'
-- 2. Crear tabla pagos para historial de adelantos, saldos y pagos completos
-- 3. Migrar adelantos existentes de forma incremental y segura
-- ====================================================================

USE `topgol_db`;

-- 1. Ampliar metodo_pago en reservas para admitir 'niubiz'
ALTER TABLE `reservas`
    MODIFY COLUMN `metodo_pago` ENUM('yape', 'transferencia_bcp', 'efectivo', 'niubiz') DEFAULT NULL;

-- 2. Crear tabla de historial de pagos
CREATE TABLE IF NOT EXISTS `pagos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reserva_id` INT NOT NULL,
    `tipo` ENUM('adelanto', 'saldo', 'completo') NOT NULL DEFAULT 'adelanto',
    `monto` DECIMAL(10,2) NOT NULL,
    `metodo_pago` ENUM('yape', 'efectivo', 'niubiz') NOT NULL,
    `estado` ENUM('pendiente', 'en_revision', 'verificado', 'rechazado') NOT NULL DEFAULT 'verificado',
    `registrado_por` INT NULL,
    `notas` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pagos_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pagos_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
    INDEX `idx_pagos_reserva` (`reserva_id`),
    INDEX `idx_pagos_fecha` (`created_at`),
    INDEX `idx_pagos_metodo` (`metodo_pago`),
    INDEX `idx_pagos_estado` (`estado`),
    INDEX `idx_pagos_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Migración retrocompatible de reservas existentes que tengan adelanto
INSERT INTO `pagos` (`reserva_id`, `tipo`, `monto`, `metodo_pago`, `estado`, `registrado_por`, `notas`, `created_at`)
SELECT
    r.id AS reserva_id,
    'adelanto' AS tipo,
    r.adelanto_monto AS monto,
    CASE
        WHEN r.metodo_pago = 'efectivo' THEN 'efectivo'
        WHEN r.metodo_pago = 'niubiz' THEN 'niubiz'
        ELSE 'yape'
    END AS metodo_pago,
    r.pago_estado AS estado,
    r.pago_verificado_por AS registrado_por,
    CONCAT('Adelanto inicial registrado (', r.origen, ')') AS notas,
    COALESCE(r.comprobante_subido_at, r.pago_verificado_at, r.created_at) AS created_at
FROM `reservas` r
WHERE r.adelanto_monto > 0
  AND r.pago_estado IN ('en_revision', 'verificado')
  AND NOT EXISTS (
      SELECT 1 FROM `pagos` p WHERE p.reserva_id = r.id AND p.tipo = 'adelanto'
  );
