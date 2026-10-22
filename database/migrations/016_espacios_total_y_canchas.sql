-- ====================================================================
-- TOP GOL - MIGRACIÓN 016: TOTAL ESTIMADO Y CANCHAS OPCIONALES EN SOLICITUDES DE ESPACIO
-- ====================================================================
-- Solo AGREGA (no borra ni cambia columnas existentes):
-- 1. `hora_fin` y `total_estimado` en `solicitudes_espacios`.
-- 2. Tabla `solicitudes_espacios_canchas`: canchas opcionales (0, 1 o varias) por solicitud.
-- 3. Rellena `hora_fin` de las solicitudes antiguas a partir de hora_inicio + duracion_horas.
-- Ejecutar una sola vez.
-- ====================================================================

ALTER TABLE `solicitudes_espacios`
    ADD COLUMN `hora_fin` TIME DEFAULT NULL AFTER `hora_inicio`,
    ADD COLUMN `total_estimado` DECIMAL(10,2) DEFAULT NULL AFTER `precio_cotizado`;

UPDATE `solicitudes_espacios`
   SET `hora_fin` = ADDTIME(`hora_inicio`, SEC_TO_TIME(ROUND(`duracion_horas` * 3600)))
 WHERE `hora_fin` IS NULL;

CREATE TABLE IF NOT EXISTS `solicitudes_espacios_canchas` (
  `id`           INT NOT NULL AUTO_INCREMENT,
  `solicitud_id` INT NOT NULL,
  `cancha_id`    INT NOT NULL,
  `precio_hora`  DECIMAL(10,2) NOT NULL,
  `subtotal`     DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_solicitud_cancha` (`solicitud_id`, `cancha_id`),
  KEY `idx_sec_cancha` (`cancha_id`),
  CONSTRAINT `fk_sec_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_espacios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sec_cancha` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
