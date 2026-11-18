-- ====================================================================
-- TOP GOL - MIGRACIÓN 019: AUDITORÍA Y BITÁCORA ADMINISTRATIVA (RF32)
-- ====================================================================
-- Registra de forma inmutable las operaciones sensibles ejecutadas por
-- los administradores (usuario, módulo, acción, entidad, detalle, IP,
-- navegador y fecha/hora).
-- Ejecutar una sola vez.
-- ====================================================================

CREATE TABLE IF NOT EXISTS `auditoria_logs` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`     INT NULL,
  `usuario_nombre` VARCHAR(100) NOT NULL,
  `modulo`         VARCHAR(50) NOT NULL,
  `accion`         VARCHAR(50) NOT NULL,
  `entidad`        VARCHAR(50) DEFAULT NULL,
  `entidad_id`     VARCHAR(64) DEFAULT NULL,
  `descripcion`    VARCHAR(255) NOT NULL,
  `datos_json`     TEXT DEFAULT NULL,
  `ip_address`     VARCHAR(45) DEFAULT NULL,
  `user_agent`     VARCHAR(255) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_usuario` (`usuario_id`),
  KEY `idx_audit_modulo`  (`modulo`),
  KEY `idx_audit_accion`  (`accion`),
  KEY `idx_audit_fecha`   (`created_at`),
  CONSTRAINT `fk_audit_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
