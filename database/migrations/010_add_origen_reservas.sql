-- ====================================================================
-- MIGRACIÓN 010: Agregar columna origen a la tabla reservas
-- Permite distinguir reservas: web, presencial, llamada, whatsapp
-- ====================================================================

USE `topgol_db`;

ALTER TABLE `reservas`
    ADD COLUMN `origen` ENUM('web', 'presencial', 'llamada', 'whatsapp') NOT NULL DEFAULT 'web' AFTER `metodo_pago`;
