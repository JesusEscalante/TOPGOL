-- ====================================================================
-- MIGRACIÓN: duración por bloques de 30 min (0.5h) y precio S/30 media hora
-- ====================================================================

USE `topgol_db`;

ALTER TABLE `reservas`
    MODIFY COLUMN `duracion_horas` DECIMAL(4,2) NOT NULL DEFAULT 1.00;
