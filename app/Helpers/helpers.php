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

/**
 * Genera el enlace de WhatsApp (wa.me) con texto preformateado y política de puntualidad
 *
 * @param array<string, mixed> $reserva
 * @param string $tipo 'recordatorio' | 'tardanza' | 'reprogramacion' | 'consulta'
 * @return string URL válida para WhatsApp
 */
function generarEnlaceWhatsApp(array $reserva, string $tipo = 'recordatorio'): string {
    $rawTel = (string)($reserva['contacto_telefono'] ?? $reserva['usuario_telefono'] ?? '');
    // Extraer únicamente dígitos
    $digitos = preg_replace('/\D+/', '', $rawTel);
    if (empty($digitos)) {
        return '#';
    }
    // Si tiene 9 dígitos (formato estándar Perú 9xxxxxxxx), anteponer código 51
    if (strlen($digitos) === 9) {
        $digitos = '51' . $digitos;
    }

    $cliente = !empty($reserva['cliente_nombre']) ? trim((string)$reserva['cliente_nombre']) : (!empty($reserva['usuario_nombre']) ? trim((string)$reserva['usuario_nombre']) : 'estimado/a cliente');
    $cancha = (string)($reserva['cancha_nombre'] ?? 'Cancha sintética');
    $fecha = (string)($reserva['fecha'] ?? date('Y-m-d'));
    $hIni = substr((string)($reserva['hora_inicio'] ?? '00:00'), 0, 5);
    $hFin = substr((string)($reserva['hora_fin'] ?? '00:00'), 0, 5);
    $id = (int)($reserva['id'] ?? 0);

    $msg = match ($tipo) {
        'tardanza' => "Hola {$cliente}, le saludamos de TOP GOL. Le recordamos que su reserva #{$id} en la {$cancha} inició a las {$hIni}. Han transcurrido más de 10 min de tolerancia. Por favor confírmenos si se encuentra en camino. Le recordamos que el horario culmina puntualmente a las {$hFin}.",
        'reprogramacion' => "Hola {$cliente}, le saludamos de TOP GOL. Le confirmamos la reprogramación de su reserva #{$id}: {$cancha}, fecha {$fecha} de {$hIni} a {$hFin}. ¡Le esperamos puntualmente!",
        'consulta' => "Hola {$cliente}, le saludamos de TOP GOL respecto a su reserva #{$id} ({$cancha}, {$fecha} {$hIni}-{$hFin}). ¿Nos confirma si todo está conforme?",
        default => "Hola {$cliente}, le saludamos de TOP GOL. Le recordamos su reserva #{$id} para hoy en {$cancha} de {$hIni} a {$hFin}. Recordamos que contamos con 10 min de tolerancia y el horario culmina estrictamente a las {$hFin}. ¿Nos confirma su asistencia?"
    };

    return 'https://wa.me/' . $digitos . '?text=' . urlencode($msg);
}

/**
 * Calcula el estado operativo de puntualidad y asistencia en tiempo real
 *
 * @param array<string, mixed> $r
 * @return array<string, mixed>
 */
function calcularEstadoPuntualidad(array $r): array {
    $tz = new DateTimeZone('America/Lima');
    $ahora = new DateTime('now', $tz);
    $ahoraTs = $ahora->getTimestamp();

    $fecha = (string)($r['fecha'] ?? date('Y-m-d'));
    $hIniStr = (string)($r['hora_inicio'] ?? '00:00:00');
    $hFinStr = (string)($r['hora_fin'] ?? '00:00:00');

    $startDt = DateTime::createFromFormat('Y-m-d H:i:s', "{$fecha} {$hIniStr}", $tz)
        ?: DateTime::createFromFormat('Y-m-d H:i', "{$fecha} {$hIniStr}", $tz)
        ?: new DateTime("{$fecha} {$hIniStr}", $tz);
    $startTs = $startDt->getTimestamp();

    $endDt = DateTime::createFromFormat('Y-m-d H:i:s', "{$fecha} {$hFinStr}", $tz)
        ?: DateTime::createFromFormat('Y-m-d H:i', "{$fecha} {$hFinStr}", $tz)
        ?: new DateTime("{$fecha} {$hFinStr}", $tz);
    if ($hFinStr === '00:00:00' || $endDt <= $startDt) {
        $endDt->modify('+1 day');
    }
    $endTs = $endDt->getTimestamp();

    $asistenciaEstado = (string)($r['asistencia_estado'] ?? 'sin_registrar');
    $estadoReserva = (string)($r['estado'] ?? 'pendiente');

    $minutosParaInicio = (int)ceil(($startTs - $ahoraTs) / 60);
    $minutosDesdeInicio = (int)floor(($ahoraTs - $startTs) / 60);

    // Regla de reprogramación: al menos 1 hora (3600 seg) antes del inicio y estado pendiente o confirmada
    $puedeReprogramarRegla = ($startTs - $ahoraTs >= 3600) && in_array($estadoReserva, ['pendiente', 'confirmada'], true);

    // Detección de proximidad: ventana de 30 minutos antes del inicio
    $esProxima = ($minutosParaInicio > 0 && $minutosParaInicio <= 30 && in_array($estadoReserva, ['pendiente', 'confirmada'], true));

    // Determinar código operativo y badges
    if ($estadoReserva === 'cancelada') {
        $codigo = $asistenciaEstado === 'no_asistio' ? 'no_asistio' : 'cancelada';
        $label = $asistenciaEstado === 'no_asistio' ? 'No asistió (Cancha liberada)' : 'Cancelada';
        $badge = 'danger';
        $color = '#ef4444';
    } elseif ($asistenciaEstado === 'presente') {
        $codigo = 'presente';
        $label = 'Presente / En cancha';
        $badge = 'success';
        $color = '#16a34a';
    } elseif ($asistenciaEstado === 'tardanza') {
        $codigo = 'tardanza';
        $label = 'Llegó con tardanza';
        $badge = 'warning';
        $color = '#d97706';
    } elseif ($asistenciaEstado === 'retraso_confirmado') {
        $codigo = 'retraso_confirmado';
        $label = 'Retraso avisado (En camino)';
        $badge = 'info';
        $color = '#0284c7';
    } elseif ($asistenciaEstado === 'no_asistio') {
        $codigo = 'no_asistio';
        $label = 'No asistió';
        $badge = 'danger';
        $color = '#dc2626';
    } else {
        // sin_registrar
        if ($ahoraTs < $startTs) {
            if ($esProxima) {
                $codigo = 'proxima';
                $label = "Próxima ({$minutosParaInicio} min)";
                $badge = 'primary';
                $color = '#2563eb';
            } else {
                $codigo = 'programada';
                $label = 'Programada';
                $badge = 'secondary';
                $color = '#64748b';
            }
        } elseif ($ahoraTs >= $startTs && $ahoraTs <= $endTs) {
            if ($minutosDesdeInicio <= 10) {
                $codigo = 'en_tolerancia';
                $label = "En tolerancia ({$minutosDesdeInicio}/10 min)";
                $badge = 'warning';
                $color = '#f59e0b';
            } else {
                $codigo = 'tardanza_pendiente';
                $label = "Tardanza (+{$minutosDesdeInicio} min sin llegar)";
                $badge = 'danger';
                $color = '#dc2626';
            }
        } else {
            $codigo = 'finalizada_sin_asistencia';
            $label = 'Culminó sin registro';
            $badge = 'dark';
            $color = '#334155';
        }
    }

    return [
        'codigo'                  => $codigo,
        'label'                   => $label,
        'badge'                   => $badge,
        'color'                   => $color,
        'asistencia_estado'       => $asistenciaEstado,
        'minutos_para_inicio'     => $minutosParaInicio,
        'minutos_desde_inicio'    => $minutosDesdeInicio,
        'es_proxima'              => $esProxima,
        'en_tolerancia'           => ($ahoraTs >= $startTs && $ahoraTs <= $endTs && $minutosDesdeInicio <= 10),
        'tardanza_detectada'      => ($ahoraTs >= $startTs && $minutosDesdeInicio > 10 && in_array($asistenciaEstado, ['sin_registrar', 'tardanza'], true)),
        'puede_reprogramar_regla' => $puedeReprogramarRegla,
        'hora_fin_estricta'       => substr($hFinStr, 0, 5),
        'aviso_horario_estricto'  => "El horario corre desde las " . substr($hIniStr, 0, 5) . " y termina puntualmente a las " . substr($hFinStr, 0, 5) . ". No se extiende por tardanza."
    ];
}

/**
 * Retorna la configuración de horario provisional del negocio para una fecha dada.
 * Nota: Los horarios son de carácter provisional y referencial para el cálculo operativo,
 * sujetos a confirmación por la empresa. Los administradores pueden registrar horarios extraordinarios.
 *
 * @param string $fecha YYYY-MM-DD
 * @return array{apertura: string, cierre: string, horas_operativas: int, es_fin_de_semana: bool, descripcion: string}
 */
function getHorarioProvisionalNegocio(string $fecha): array {
    $diaSemana = (int)date('N', strtotime($fecha)); // 1=Lunes, 7=Domingo
    $esFinDeSemana = ($diaSemana >= 6);
    $apertura = $esFinDeSemana ? '07:00' : '09:00';
    $cierre = '23:00';
    $horasOperativas = $esFinDeSemana ? 16 : 14;

    return [
        'apertura'         => $apertura,
        'cierre'           => $cierre,
        'horas_operativas' => $horasOperativas,
        'es_fin_de_semana' => $esFinDeSemana,
        'descripcion'      => $esFinDeSemana ? 'Sáb-Dom: 07:00 a 23:00 (16h)' : 'Lun-Vie: 09:00 a 23:00 (14h)',
    ];
}