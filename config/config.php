<?php
declare(strict_types=1);

/**
 * ====================================================================
 * TOP GOL - Archivo Central de Configuracion
 * ====================================================================
 * Carga de variables de entorno (.env) y definicion de constantes globales.
 */

// Definicion de rutas fisicas base
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('VIEWS_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'Views');
define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');

/**
 * Carga nativa de variables del archivo .env
 */
$envFile = ROOT_PATH . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, "\"'");
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Configuracion general del sistema
define('APP_NAME', $_ENV['APP_NAME'] ?? 'TOP GOL');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');

// Deteccion y definicion de URL_BASE
// Se detecta dinamicamente el host y puerto actuales para que la aplicacion
// funcione sin importar si Apache corre en el puerto 80, 8080 u otro.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? '';
if (!empty($host)) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim($protocol . $host . str_replace('\\', '/', $scriptDir), '/');
    $base = preg_replace('/\/public$/', '', $base);
    define('URL_BASE', $base);
} elseif (!empty($_ENV['APP_URL'])) {
    define('URL_BASE', rtrim($_ENV['APP_URL'], '/'));
} else {
    define('URL_BASE', $protocol . 'localhost');
}

// Controlador y metodo por defecto
define('DEFAULT_CONTROLLER', 'Home');
define('DEFAULT_METHOD', 'index');

// Reservas: paso (en minutos) de la hora de inicio. 30 => solo :00 y :30 (18:00, 18:30...).
// Si algun dia se cambia (10, 15, 20...), todo el sistema se adapta. Debe dividir 60 de forma exacta.
// La duracion siempre va en bloques de 30 min.
define('RESERVA_PASO_MINUTOS', 30);
define('RESERVA_DURACION_BLOQUE_MIN', 30);

// Tolerancia de llegada (minutos desde la hora de la reserva). Es la UNICA fuente del valor:
// la usan el aviso al cliente, el control de asistencia y los mensajes de WhatsApp.
define('TOLERANCIA_MINUTOS', 10);

// Plazo ESTIMADO en que el encargado revisa un comprobante de pago (texto que ve el cliente).
// Es una politica propuesta por el equipo: no es automatica ni garantizada, y NO es la tolerancia de la hora de juego.
define('VERIFICACION_PAGO_PLAZO', '10 a 15 minutos');

// Espacio de cumpleanos / reuniones: valor por hora (PROVISIONAL, por confirmar con el encargado).
// Cada cancha opcional se suma con el precio_hora de la propia cancha (S/ 60 por hora).
define('ESPACIO_PRECIO_HORA', 60.00);
define('ESPACIO_PRECIO_PROVISIONAL', true);

// Configuracion de Base de Datos
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'topgol_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Configuracion de Zona Horaria
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Lima');

// Configuracion de Google OAuth
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
define('GOOGLE_REDIRECT_URI', $_ENV['GOOGLE_REDIRECT_URI'] ?? URL_BASE . '/auth/google/callback');

// Configuracion de notificaciones Telegram (CallMeBot)
define('TELEGRAM_ENABLED', filter_var($_ENV['TELEGRAM_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('TELEGRAM_USER', $_ENV['TELEGRAM_USER'] ?? '');
define('TELEGRAM_DEBUG', filter_var($_ENV['TELEGRAM_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));