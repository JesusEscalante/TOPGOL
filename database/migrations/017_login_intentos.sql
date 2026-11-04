-- ====================================================================
-- TOP GOL - MIGRACIÓN 017: LÍMITE DE INTENTOS DE LOGIN
-- ====================================================================
-- Frena la fuerza bruta: registra fallos por correo y bloquea la cuenta
-- temporalmente al superar LOGIN_MAX_INTENTOS (config/config.php).
-- Ejecutar una sola vez.
-- ====================================================================

CREATE TABLE IF NOT EXISTS `login_intentos` (
  `email`            VARCHAR(100) NOT NULL,
  `intentos`         INT NOT NULL DEFAULT 0,
  `ultimo_intento`   TIMESTAMP NULL DEFAULT NULL,
  `bloqueado_hasta`  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
