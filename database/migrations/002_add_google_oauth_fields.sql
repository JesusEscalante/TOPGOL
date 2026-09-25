-- ====================================================================
-- MIGRACIÓN: Agregar campos para Google OAuth
-- ====================================================================
-- Ejecutar en bases de datos existentes para habilitar login con Google
-- ====================================================================

USE `topgol_db`;

-- Agregar columnas google_id y avatar a la tabla usuarios
ALTER TABLE `usuarios` 
    ADD COLUMN `google_id` VARCHAR(255) DEFAULT NULL AFTER `rol`,
    ADD COLUMN `avatar` VARCHAR(500) DEFAULT NULL AFTER `google_id`;

-- Agregar índice único para google_id
ALTER TABLE `usuarios` 
    ADD UNIQUE KEY `idx_usuario_google_id` (`google_id`);

-- Hacer password nullable (para usuarios que solo usan Google)
ALTER TABLE `usuarios` 
    MODIFY COLUMN `password` VARCHAR(255) DEFAULT NULL;