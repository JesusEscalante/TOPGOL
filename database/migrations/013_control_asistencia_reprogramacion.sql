-- ====================================================================
-- MIGRACIÓN 013: Módulo de Puntualidad, Asistencia y Reprogramaciones
-- ====================================================================
-- Agrega campos de control operativo en la tabla reservas:
-- 1. Control de asistencia y puntualidad (tolerancia 10 min, no asistencia)
-- 2. Trazabilidad de reprogramaciones (anticipación mín. 1 hora, auditoría)
-- 3. Registro de contacto previo y confirmación de llegada (WhatsApp/llamada)
-- ====================================================================

USE `topgol_db`;

ALTER TABLE `reservas`
    -- Control de asistencia y puntualidad
    ADD COLUMN `asistencia_estado` ENUM('sin_registrar', 'presente', 'tardanza', 'retraso_confirmado', 'no_asistio') NOT NULL DEFAULT 'sin_registrar' AFTER `estado`,
    ADD COLUMN `asistencia_hora` TIME NULL DEFAULT NULL AFTER `asistencia_estado`,
    ADD COLUMN `asistencia_notas` VARCHAR(255) NULL DEFAULT NULL AFTER `asistencia_hora`,
    ADD COLUMN `asistencia_registrada_por` INT NULL DEFAULT NULL AFTER `asistencia_notas`,
    ADD COLUMN `asistencia_updated_at` TIMESTAMP NULL DEFAULT NULL AFTER `asistencia_registrada_por`,

    -- Trazabilidad de reprogramaciones
    ADD COLUMN `reprogramado_de_cancha_id` INT NULL DEFAULT NULL AFTER `asistencia_updated_at`,
    ADD COLUMN `reprogramado_de_fecha` DATE NULL DEFAULT NULL AFTER `reprogramado_de_cancha_id`,
    ADD COLUMN `reprogramado_de_hora` TIME NULL DEFAULT NULL AFTER `reprogramado_de_fecha`,
    ADD COLUMN `reprogramado_por` INT NULL DEFAULT NULL AFTER `reprogramado_de_hora`,
    ADD COLUMN `reprogramado_at` TIMESTAMP NULL DEFAULT NULL AFTER `reprogramado_por`,
    ADD COLUMN `motivo_reprogramacion` VARCHAR(255) NULL DEFAULT NULL AFTER `reprogramado_at`,

    -- Contacto previo y recordatorio (~30 min antes)
    ADD COLUMN `contacto_realizado_at` TIMESTAMP NULL DEFAULT NULL AFTER `motivo_reprogramacion`,
    ADD COLUMN `contacto_resultado` VARCHAR(100) NULL DEFAULT NULL AFTER `contacto_realizado_at`,

    -- Claves foráneas de auditoría
    ADD CONSTRAINT `fk_reserva_asistencia_admin` FOREIGN KEY (`asistencia_registrada_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_reserva_reprogramado_por` FOREIGN KEY (`reprogramado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,

    -- Índices para consultas operativas rápidas
    ADD INDEX `idx_reserva_asistencia` (`asistencia_estado`),
    ADD INDEX `idx_reserva_contacto` (`contacto_realizado_at`);
