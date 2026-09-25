-- ====================================================================
-- MIGRACIÓN: Pago y comprobante en reservas (Yape / Transferencia BCP)
-- ====================================================================
-- Ejecutar en bases de datos existentes para habilitar la interfaz
-- de "Pago y comprobante" (método de pago + subida de comprobante).
-- ====================================================================

USE `topgol_db`;

-- 1. Método de pago y monto del adelanto
ALTER TABLE `reservas`
    ADD COLUMN `metodo_pago` ENUM('yape', 'transferencia_bcp') DEFAULT NULL AFTER `observaciones`,
    ADD COLUMN `adelanto_monto` DECIMAL(10,2) NOT NULL DEFAULT 20.00 AFTER `metodo_pago`;

-- 2. Estado de verificación del pago
ALTER TABLE `reservas`
    ADD COLUMN `pago_estado` ENUM('pendiente', 'en_revision', 'verificado', 'rechazado') NOT NULL DEFAULT 'pendiente' AFTER `adelanto_monto`;

-- 3. Comprobante de pago subido por el cliente
ALTER TABLE `reservas`
    ADD COLUMN `comprobante_ruta` VARCHAR(500) DEFAULT NULL AFTER `pago_estado`,
    ADD COLUMN `comprobante_nombre` VARCHAR(255) DEFAULT NULL AFTER `comprobante_ruta`,
    ADD COLUMN `comprobante_tipo` VARCHAR(100) DEFAULT NULL AFTER `comprobante_nombre`,
    ADD COLUMN `comprobante_size` INT DEFAULT NULL AFTER `comprobante_tipo`,
    ADD COLUMN `comprobante_subido_at` TIMESTAMP NULL DEFAULT NULL AFTER `comprobante_size`;

-- 4. Auditoría de verificación (admin que valida el pago)
ALTER TABLE `reservas`
    ADD COLUMN `pago_verificado_at` TIMESTAMP NULL DEFAULT NULL AFTER `comprobante_subido_at`,
    ADD COLUMN `pago_verificado_por` INT DEFAULT NULL AFTER `pago_verificado_at`;

-- 5. Índice y clave foránea de auditoría
ALTER TABLE `reservas`
    ADD KEY `idx_reserva_pago_estado` (`pago_estado`);

ALTER TABLE `reservas`
    ADD CONSTRAINT `fk_reserva_verificado_por` FOREIGN KEY (`pago_verificado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
