-- ====================================================================
-- TOP GOL - MIGRACIÓN 018: RECUPERACIÓN DE CONTRASEÑA POR CORREO (RF25)
-- ====================================================================
-- Almacena los tokens temporales (hasheados con SHA-256) para el
-- restablecimiento seguro de contraseñas olvidadas.
-- Ejecutar una sola vez.
-- ====================================================================

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `email`       VARCHAR(100) NOT NULL,
  `token_hash`  VARCHAR(64) NOT NULL,
  `expires_at`  DATETIME NOT NULL,
  `used_at`     DATETIME DEFAULT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pw_reset_email` (`email`),
  KEY `idx_pw_reset_token` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
