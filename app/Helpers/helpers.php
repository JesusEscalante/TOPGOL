<?php
declare(strict_types=1);

/**
 * ====================================================================
 * TOP GOL - Funciones Auxiliares (Helpers)
 * ====================================================================
 * Funciones globales de apoyo para redireccion, seguridad, sesiones y vistas.
 */

/**
 * Redirecciona al usuario a una ruta interna o externa
 *
 * @param string $ruta Ruta relativa o URL absoluta
 * @return never
 */
function redirect(string $ruta): void {
    if (str_starts_with($ruta, 'http://') || str_starts_with($ruta, 'https://')) {
        header("Location: {$ruta}");
    } else {
        $rutaLimpia = ltrim($ruta, '/');
        header("Location: " . URL_BASE . "/{$rutaLimpia}");
    }
    exit;
}

/**
 * Dump and Die: Imprime información formateada y detiene la ejecución
 *
 * @param mixed ...$datos Variables a inspeccionar
 * @return never
 */
function dd(mixed ...$datos): void {
    echo '<style>
        body { background-color: #111827; color: #f9fafb; font-family: monospace; padding: 20px; }
        .dd-box { background-color: #1f2937; border-left: 4px solid #f5c842; padding: 15px; border-radius: 6px; margin-bottom: 15px; overflow-x: auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.5); }
    </style>';
    echo '<h3 style="color: #f5c842;">TOP GOL - Debug Inspector (dd)</h3>';
    foreach ($datos as $item) {
        echo '<div class="dd-box"><pre>';
        var_dump($item);
        echo '</pre></div>';
    }
    exit;
}

/**
 * Almacena un mensaje flash en la sesión para ser mostrado una sola vez
 *
 * @param string $clave Identificador del mensaje (e.g., 'mensaje', 'error')
 * @param string $mensaje Contenido del mensaje
 * @param string $tipo Tipo de alerta Bootstrap ('success', 'danger', 'warning', 'info')
 */
function sessionFlash(string $clave, string $mensaje, string $tipo = 'success'): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][$clave] = [
        'mensaje' => $mensaje,
        'tipo'    => $tipo
    ];
}

/**
 * Obtiene y elimina un mensaje flash de la sesión
 *
 * @param string $clave
 * @return array{mensaje: string, tipo: string}|null
 */
function getFlash(string $clave): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'][$clave])) {
        $mensaje = $_SESSION['flash'][$clave];
        unset($_SESSION['flash'][$clave]);
        return $mensaje;
    }
    return null;
}

/**
 * Verifica si existe un mensaje flash específico
 */
function hasFlash(string $clave): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['flash'][$clave]);
}

/**
 * Renderiza en HTML todas las alertas flash acumuladas con estilo Bootstrap 5
 *
 * @return string HTML de las alertas
 */
function renderFlashMessages(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $html = '';
    foreach ($_SESSION['flash'] as $key => $flash) {
        $tipo = htmlspecialchars($flash['tipo']);
        $mensaje = htmlspecialchars($flash['mensaje']);
        $icono = match($tipo) {
            'success' => 'bi-check-circle-fill',
            'danger', 'error' => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default => 'bi-info-circle-fill'
        };
        $alertTipo = ($tipo === 'error') ? 'danger' : $tipo;

        $html .= "
        <div class=\"alert alert-{$alertTipo} alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm\" role=\"alert\">
            <i class=\"bi {$icono} me-2 fs-5\"></i>
            <div class=\"flex-grow-1\">{$mensaje}</div>
            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
        </div>";
    }
    unset($_SESSION['flash']);
    return $html;
}

/**
 * Verifica si el usuario actual ha iniciado sesión
 */
function isLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['usuario_id']);
}

/**
 * Verifica si el usuario actual tiene rol de Administrador
 */
function isAdmin(): bool {
    if (!isLoggedIn()) {
        return false;
    }
    return ($_SESSION['usuario_rol'] ?? '') === 'admin';
}

/**
 * Retorna los datos del usuario autenticado en la sesión
 *
 * @return array<string, mixed>|null
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'       => $_SESSION['usuario_id'],
        'nombre'   => $_SESSION['usuario_nombre'] ?? '',
        'email'    => $_SESSION['usuario_email'] ?? '',
        'rol'      => $_SESSION['usuario_rol'] ?? 'cliente',
        'telefono' => $_SESSION['usuario_telefono'] ?? '',
        'avatar'   => $_SESSION['usuario_avatar'] ?? ''
    ];
}

/**
 * Genera u obtiene el token CSRF para la sesión actual
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Genera un input hidden con el token CSRF para formularios
 */
function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valida si el token CSRF recibido en POST coincide con la sesión
 */
function validateCsrf(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $tokenRecibido = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($tokenRecibido) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$tokenRecibido);
}

/**
 * Sanitiza texto eliminando caracteres peligrosos para prevención XSS
 */
function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Formatea un número como precio en moneda (Soles / Moneda Local)
 */
function formatPrice(float|int|string $precio): string {
    $numero = is_numeric($precio) ? (float)$precio : 0.0;
    return 'S/ ' . number_format($numero, 2, '.', ',');
}

/**
 * Genera una URL absoluta dentro del proyecto
 */
function url(string $ruta = ''): string {
    $rutaLimpia = ltrim($ruta, '/');
    if ($rutaLimpia === '') {
        return URL_BASE;
    }
    return URL_BASE . '/' . $rutaLimpia;
}

/**
 * Genera la URL pública para recursos estáticos (CSS, JS, imágenes)
 */
function asset(string $ruta): string {
    $rutaLimpia = ltrim($ruta, '/');
    return URL_BASE . '/assets/' . $rutaLimpia;
}