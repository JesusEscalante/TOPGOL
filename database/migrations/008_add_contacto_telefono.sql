-- ====================================================================
-- MIGRACIÓN: número de contacto por reserva (celular / WhatsApp)
-- ====================================================================

USE `topgol_db`;

ALTER TABLE `reservas`
    ADD COLUMN `contacto_telefono` VARCHAR(20) DEFAULT NULL AFTER `cliente_nombre`;
