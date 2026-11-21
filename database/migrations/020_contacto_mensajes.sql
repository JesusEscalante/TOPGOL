-- ====================================================================
-- TOP GOL - MIGRACIÓN 020: BUZÓN DE MENSAJES DE CONTACTO (RF02 / RF16)
-- ====================================================================
-- Almacena en base de datos todos los mensajes recibidos desde el
-- formulario público de contacto para evitar pérdida de información.
-- ====================================================================

CREATE TABLE IF NOT EXISTS `contacto_mensajes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100) NOT NULL,
  `email`       VARCHAR(100) NOT NULL,
  `telefono`    VARCHAR(25) DEFAULT NULL,
  `asunto`      VARCHAR(100) NOT NULL DEFAULT 'Consulta general',
  `mensaje`     TEXT NOT NULL,
  `ip_address`  VARCHAR(45) DEFAULT NULL,
  `atendido`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contacto_atendido` (`atendido`),
  KEY `idx_contacto_fecha`    (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
