-- ====================================================================
-- MIGRACIÓN: agrupar reservas de un mismo evento (múltiples canchas)
-- ====================================================================

USE `topgol_db`;

ALTER TABLE `reservas`
    ADD COLUMN `evento_grupo` VARCHAR(32) DEFAULT NULL AFTER `cliente_nombre`,
    ADD KEY `idx_reserva_evento_grupo` (`evento_grupo`);
