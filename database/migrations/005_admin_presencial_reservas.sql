-- ====================================================================
-- MIGRACIÓN: reservas presenciales por administrador (efectivo + cliente)
-- ====================================================================
-- Agrega método de pago en efectivo y campo para nombre del cliente
-- cuando el administrador registra la reserva de forma presencial
-- (pago completo, sin comprobante).
-- ====================================================================

USE `topgol_db`;

ALTER TABLE `reservas`
    MODIFY COLUMN `metodo_pago` ENUM('yape', 'transferencia_bcp', 'efectivo') DEFAULT NULL;

ALTER TABLE `reservas`
    ADD COLUMN `cliente_nombre` VARCHAR(120) DEFAULT NULL AFTER `adelanto_monto`;
