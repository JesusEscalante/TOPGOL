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

    $tolMin = toleranciaMinutos();
    $msg = match ($tipo) {
        'tardanza' => "Hola {$cliente}, le saludamos de TOP GOL. Le recordamos que su reserva #{$id} en la {$cancha} inició a las {$hIni}. Han transcurrido más de {$tolMin} min de tolerancia. Por favor confírmenos si se encuentra en camino. Le recordamos que el horario culmina puntualmente a las {$hFin}.",
        'reprogramacion' => "Hola {$cliente}, le saludamos de TOP GOL. Le confirmamos la reprogramación de su reserva #{$id}: {$cancha}, fecha {$fecha} de {$hIni} a {$hFin}. ¡Le esperamos puntualmente!",
        'consulta' => "Hola {$cliente}, le saludamos de TOP GOL respecto a su reserva #{$id} ({$cancha}, {$fecha} {$hIni}-{$hFin}). ¿Nos confirma si todo está conforme?",
        default => "Hola {$cliente}, le saludamos de TOP GOL. Le recordamos su reserva #{$id} para hoy en {$cancha} de {$hIni} a {$hFin}. Recordamos que contamos con {$tolMin} min de tolerancia; pasado ese tiempo el horario sigue corriendo y no se extiende: culmina a las {$hFin}. ¿Nos confirma su asistencia?"
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
            if ($minutosDesdeInicio <= toleranciaMinutos()) {
                $codigo = 'en_tolerancia';
                $label = "En tolerancia ({$minutosDesdeInicio}/" . toleranciaMinutos() . " min)";
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
        'en_tolerancia'           => ($ahoraTs >= $startTs && $ahoraTs <= $endTs && $minutosDesdeInicio <= toleranciaMinutos()),
        'tardanza_detectada'      => ($ahoraTs >= $startTs && $minutosDesdeInicio > toleranciaMinutos() && in_array($asistenciaEstado, ['sin_registrar', 'tardanza'], true)),
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

/**
 * Paso de la hora de inicio (minutos). Si la constante es invalida usa 10.
 */
function reservaPasoMinutos(): int {
    $paso = defined('RESERVA_PASO_MINUTOS') ? (int)RESERVA_PASO_MINUTOS : 10;
    return ($paso >= 1 && $paso <= 60 && 60 % $paso === 0) ? $paso : 10;
}

/**
 * Minutos (con cero a la izquierda) que se ofrecen como hora de inicio segun RESERVA_PASO_MINUTOS.
 *
 * @return array<int, string>
 */
function reservaMinutosInicio(): array {
    $paso = reservaPasoMinutos();
    $minutos = [];
    for ($m = 0; $m < 60; $m += $paso) {
        $minutos[] = sprintf('%02d', $m);
    }
    return $minutos;
}

/**
 * Valida en servidor el horario de una reserva: formato, paso de la hora de inicio,
 * duracion en bloques de 30 min, horario del negocio (fin <= 23:00) y que no sea en el pasado.
 *
 * @param string   $fecha         YYYY-MM-DD
 * @param string   $horaInicio    HH:MM o HH:MM:SS
 * @param float    $duracionHoras Duracion en horas (0.5, 1, 1.5...)
 * @param int|null $ahoraTs       Marca de tiempo "ahora" (solo para pruebas)
 * @return array{ok: bool, error: string|null, hora_inicio: string, hora_fin: string, minutos: int}
 */
function validarHorarioReserva(string $fecha, string $horaInicio, float $duracionHoras, ?int $ahoraTs = null, bool $permitirBloqueActual = false): array {
    $fallo = static fn(string $msg): array => ['ok' => false, 'error' => $msg, 'hora_inicio' => '', 'hora_fin' => '', 'minutos' => 0];
    $ahoraTs = $ahoraTs ?? time();

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', trim($horaInicio), $mt)) {
        return $fallo('El formato de fecha u hora de inicio es inválido.');
    }
    $h = (int)$mt[1];
    $m = (int)$mt[2];
    if ($h > 23 || $m > 59) {
        return $fallo('El formato de hora de inicio es inválido.');
    }
    $tz = new \DateTimeZone('America/Lima');
    $inicio = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', sprintf('%s %02d:%02d', $fecha, $h, $m), $tz);
    if ($inicio === false || $inicio->format('Y-m-d') !== $fecha) {
        return $fallo('La fecha indicada no es válida.');
    }

    $paso = reservaPasoMinutos();
    if ($m % $paso !== 0) {
        if ($paso === 30) {
            return $fallo('La hora de inicio debe ser en :00 o :30');
        }
        return $fallo("La hora de inicio debe ser en pasos de {$paso} minutos (por ejemplo 18:00, " . sprintf('18:%02d', $paso % 60) . ').');
    }

    $bloque = defined('RESERVA_DURACION_BLOQUE_MIN') ? (int)RESERVA_DURACION_BLOQUE_MIN : 30;
    $minutos = (int)round($duracionHoras * 60);
    if ($minutos < $bloque || $minutos > 480 || $minutos % $bloque !== 0 || abs($duracionHoras * 60 - $minutos) > 0.01) {
        return $fallo("Duración no válida. Usa múltiplos de {$bloque} minutos (máximo 8 horas).");
    }

    $negocio = getHorarioProvisionalNegocio($fecha);
    $aperturaMin = ((int)substr($negocio['apertura'], 0, 2)) * 60 + (int)substr($negocio['apertura'], 3, 2);
    $cierreMin = ((int)substr($negocio['cierre'], 0, 2)) * 60 + (int)substr($negocio['cierre'], 3, 2);
    $inicioMin = $h * 60 + $m;

    // Nunca en el pasado (hora de Lima). El servidor es quien manda.
    $hoyLima = (new \DateTimeImmutable('@' . $ahoraTs))->setTimezone($tz)->format('Y-m-d');
    if ($fecha < $hoyLima) {
        return $fallo('No es posible reservar en fechas pasadas.');
    }
    if ($fecha === $hoyLima) {
        $minAhora = reservaMinutoMinimoAhora($permitirBloqueActual, $ahoraTs);
        if ($inicioMin < $minAhora) {
            $sugerida = max($minAhora, $aperturaMin);
            if ($sugerida + $bloque > $cierreMin) {
                return $fallo('Por hoy ya no quedan horarios disponibles. Elige otra fecha.');
            }
            return $fallo('Esa hora ya pasó. Elige una hora desde las ' . sprintf('%02d:%02d', intdiv($sugerida, 60), $sugerida % 60));
        }
    }

    if ($inicioMin < $aperturaMin) {
        return $fallo("El horario de atención para esta fecha inicia a las {$negocio['apertura']}.");
    }
    if ($inicioMin + $minutos > $cierreMin) {
        return $fallo("La reserva no puede terminar después de las {$negocio['cierre']}.");
    }

    $fin = $inicio->modify("+{$minutos} minutes");
    return [
        'ok'          => true,
        'error'       => null,
        'hora_inicio' => $inicio->format('H:i:s'),
        'hora_fin'    => $fin->format('H:i:s'),
        'minutos'     => $minutos,
    ];
}

/**
 * Minuto del día (hora de Lima) a partir del cual se puede EMPEZAR una reserva hoy, sin mirar el horario de atención.
 *  - Cliente / evento / cumpleaños: la hora de inicio debe ser posterior a ahora (siguiente múltiplo del paso).
 *  - Administrador (reserva manual): se permite el bloque de media hora en curso, nunca uno anterior.
 */
function reservaMinutoMinimoAhora(bool $permitirBloqueActual = false, ?int $ahoraTs = null): int {
    $ahora = (new \DateTimeImmutable('@' . ($ahoraTs ?? time())))->setTimezone(new \DateTimeZone('America/Lima'));
    $minAhora = ((int)$ahora->format('G')) * 60 + (int)$ahora->format('i');
    if ($permitirBloqueActual) {
        return intdiv($minAhora, 30) * 30;
    }
    $paso = reservaPasoMinutos();
    return (intdiv($minAhora, $paso) + 1) * $paso;
}

/**
 * Hora más temprana (HH:MM) en que se puede empezar una reserva en esa fecha: hoy tiene en cuenta
 * la hora actual de Lima; fechas futuras, la apertura; fechas pasadas o sin horarios: '24:00'.
 */
function horaMinimaReserva(string $fecha, bool $permitirBloqueActual = false, ?int $ahoraTs = null): string {
    $ahoraTs = $ahoraTs ?? time();
    $hoy = (new \DateTimeImmutable('@' . $ahoraTs))->setTimezone(new \DateTimeZone('America/Lima'))->format('Y-m-d');
    if ($fecha < $hoy) {
        return '24:00';
    }
    $negocio = getHorarioProvisionalNegocio($fecha);
    $aperturaMin = ((int)substr($negocio['apertura'], 0, 2)) * 60 + (int)substr($negocio['apertura'], 3, 2);
    $cierreMin = ((int)substr($negocio['cierre'], 0, 2)) * 60 + (int)substr($negocio['cierre'], 3, 2);
    $min = $fecha > $hoy ? $aperturaMin : max($aperturaMin, reservaMinutoMinimoAhora($permitirBloqueActual, $ahoraTs));
    return ($min + 30 > $cierreMin) ? '24:00' : sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
}

/**
 * Minutos de tolerancia de llegada (config/config.php: TOLERANCIA_MINUTOS). Única fuente del valor.
 */
function toleranciaMinutos(): int {
    return defined('TOLERANCIA_MINUTOS') ? max(0, (int)TOLERANCIA_MINUTOS) : 10;
}

/**
 * Aviso de tolerancia para el cliente (reservas de cancha y eventos de fútbol; NO para cumpleaños y reuniones).
 */
function textoToleranciaReserva(): string {
    $n = toleranciaMinutos();
    return "Tienes {$n} minutos de tolerancia desde la hora de tu reserva. Pasado ese tiempo tu horario sigue corriendo y no se extiende: termina a la hora acordada. El encargado te atenderá en el local.";
}

/**
 * Aviso al cliente mientras su pago está por verificar (plazo estimado, editable en config: VERIFICACION_PAGO_PLAZO).
 */
function textoVerificacionPago(): string {
    $plazo = defined('VERIFICACION_PAGO_PLAZO') ? trim((string)VERIFICACION_PAGO_PLAZO) : '10 a 15 minutos';
    return "El encargado revisará tu comprobante en un plazo estimado de {$plazo}. Cuando lo confirme, tu reserva pasará a Confirmada; revisa el estado en Mis reservas.";
}

/**
 * Formato único de duración para mostrar al usuario (no cambia lo que se guarda):
 * 30 → "30 min", 60 → "1 h", 90 → "1 h 30 min", 120 → "2 h", 150 → "2 h 30 min".
 */
function formatoDuracion(int $minutos): string {
    $minutos = max(0, $minutos);
    if ($minutos < 60) {
        return $minutos . ' min';
    }
    $h = intdiv($minutos, 60);
    $m = $minutos % 60;
    return $m === 0 ? "{$h} h" : "{$h} h {$m} min";
}

/**
 * Igual que formatoDuracion pero recibe horas decimales (como se guarda en reservas.duracion_horas: 1.5 → "1 h 30 min").
 */
function formatoDuracionHoras(float $horas): string {
    return formatoDuracion((int)round($horas * 60));
}

/**
 * Valor por hora del espacio de cumpleaños/reuniones (configurable, provisional).
 */
function espacioPrecioHora(): float {
    return defined('ESPACIO_PRECIO_HORA') ? max(0.0, (float)ESPACIO_PRECIO_HORA) : 60.0;
}

/**
 * Calcula el total estimado de una solicitud de espacio: espacio + canchas opcionales.
 * Todo en base a minutos (precio por hora / 60 x minutos).
 *
 * @param int                $minutos       Duración total en minutos
 * @param array<int, float>  $preciosCancha Mapa cancha_id => precio por hora
 * @return array{espacio: float, canchas: array<int, float>, total: float}
 */
function calcularTotalEspacio(int $minutos, array $preciosCancha = []): array {
    $espacio = round($minutos * (espacioPrecioHora() / 60), 2);
    $canchas = [];
    $total = $espacio;
    foreach ($preciosCancha as $cid => $precio) {
        $sub = round($minutos * ((float)$precio / 60), 2);
        $canchas[(int)$cid] = $sub;
        $total += $sub;
    }
    return ['espacio' => $espacio, 'canchas' => $canchas, 'total' => round($total, 2)];
}

/**
 * Textos de /eventos y reglas de eventos (config/eventos_textos.php, un solo lugar para editarlos).
 *
 * @return array<string, mixed>
 */
function eventosTextos(): array {
    static $textos = null;
    if ($textos === null) {
        $archivo = ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'eventos_textos.php';
        $textos = is_file($archivo) ? (array)(require $archivo) : [];
    }
    return $textos;
}

/**
 * URL de una imagen de public/assets/img/eventos/. Devuelve null si el archivo no existe
 * (la vista muestra entonces un cuadro neutro).
 */
function eventoImagenUrl(string $archivo): ?string {
    $archivo = basename($archivo);
    $ruta = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'eventos' . DIRECTORY_SEPARATOR . $archivo;
    return ($archivo !== '' && is_file($ruta)) ? url('/assets/img/eventos/' . rawurlencode($archivo)) : null;
}

/**
 * Datos de Yape desde config/yape.php (número, titular y QR). Todo vacío = pendiente de configurar.
 *
 * @return array{numero: string, titular: string, qr_url: string}
 */
function yapeConfig(): array {
    static $cfg = null;
    if ($cfg === null) {
        $archivo = ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'yape.php';
        $datos = is_file($archivo) ? (array)(require $archivo) : [];
        $qr = trim((string)($datos['qr'] ?? ''));
        $qrUrl = '';
        if ($qr !== '' && is_file(PUBLIC_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($qr, '/')))) {
            $qrUrl = url('/' . ltrim($qr, '/'));
        }
        $cfg = [
            'numero'  => trim((string)($datos['numero'] ?? '')),
            'titular' => trim((string)($datos['titular'] ?? '')),
            'qr_url'  => $qrUrl,
        ];
    }
    return $cfg;
}

/**
 * Texto legible del método de pago (sin guiones bajos).
 */
function metodoPagoTexto(string $metodo): string {
    return match ($metodo) {
        'yape' => 'Yape',
        'efectivo' => 'Efectivo',
        'niubiz' => 'Niubiz',
        'transferencia_bcp' => 'Transferencia (histórico)', // método antiguo: ya no se acepta en reservas nuevas
        '' => '',
        default => ucfirst(str_replace('_', ' ', $metodo)),
    };
}

/**
 * Etiqueta de pago calculada por MONTOS (no cambia lo que se guarda en la base).
 * Una sola función para Reservas, Detalle, Pagos y Calendario. Para eventos de varias
 * canchas (evento_grupo) hay que pasar el total y lo pagado de TODO el grupo.
 *
 * @param float  $total      Total de la reserva (o del grupo)
 * @param float  $pagado     Suma de pagos verificados (tabla pagos)
 * @param string $pagoEstado reservas.pago_estado (pendiente | en_revision | verificado | rechazado)
 * @return array{codigo: string, texto: string, cls: string, falta: float}
 */
function etiquetaPagoReserva(float $total, float $pagado, string $pagoEstado): array {
    $total = round($total, 2);
    $pagado = round($pagado, 2);
    $falta = max(0.0, round($total - $pagado, 2));

    if ($pagoEstado === 'rechazado') {
        return ['codigo' => 'rechazado', 'texto' => 'Rechazado', 'cls' => 'bad', 'falta' => $falta];
    }
    if ($pagado > 0.0 && $falta <= 0.0) {
        return ['codigo' => 'pagado', 'texto' => 'Pagado completo', 'cls' => 'ok', 'falta' => 0.0];
    }
    if ($pagoEstado === 'en_revision') {
        return ['codigo' => 'por_verificar', 'texto' => 'Por verificar', 'cls' => 'warn', 'falta' => $falta];
    }
    if ($pagado > 0.0) {
        return ['codigo' => 'adelanto', 'texto' => 'Adelanto confirmado · falta S/ ' . number_format($falta, 2), 'cls' => 'info', 'falta' => $falta];
    }
    return ['codigo' => 'sin_pago', 'texto' => 'Sin pago', 'cls' => 'fin', 'falta' => $falta];
}

/**
 * Criterios ÚNICOS de las listas de Caja (los usan las pestañas, las tarjetas y los contadores).
 * Reciben una reserva o un grupo de evento ya con 'estado', 'pago_estado', 'total_pagado' y 'saldo_pendiente'.
 *
 * @param array<string, mixed> $g
 */
function pagoEsPorVerificar(array $g): bool {
    return (string)($g['pago_estado'] ?? '') === 'en_revision' && (string)($g['estado'] ?? '') !== 'cancelada';
}

/** Saldo por cobrar: reserva ya confirmada (adelanto verificado) y con saldo mayor que 0. @param array<string, mixed> $g */
function pagoTieneSaldoPorCobrar(array $g): bool {
    return (string)($g['estado'] ?? '') === 'confirmada'
        && (string)($g['pago_estado'] ?? '') === 'verificado'
        && (float)($g['saldo_pendiente'] ?? 0) > 0.0;
}

/** Pagada al 100%: no cancelada, sin saldo y con algo cobrado. @param array<string, mixed> $g */
function pagoEstaPagadaCompleta(array $g): bool {
    return (string)($g['estado'] ?? '') !== 'cancelada'
        && (float)($g['saldo_pendiente'] ?? 0) <= 0.0
        && (float)($g['total_pagado'] ?? 0) > 0.0;
}

/**
 * Línea de seguimiento de una reserva con datos REALES guardados.
 * Cada paso: estado = completo | actual | pendiente | problema.
 *
 * @param array<string, mixed> $r       Fila de la reserva (obtenerPorId)
 * @param float                $pagado  Suma de pagos verificados
 * @param int|null             $ahoraTs Marca de tiempo "ahora" (solo para pruebas)
 * @return array<int, array{clave: string, titulo: string, estado: string, detalle: string}>
 */
function seguimientoReserva(array $r, float $pagado, ?int $ahoraTs = null): array {
    $ahoraTs = $ahoraTs ?? time();
    $tz = new \DateTimeZone('America/Lima');
    $estado = (string)($r['estado'] ?? 'pendiente');
    $pagoEstado = (string)($r['pago_estado'] ?? 'pendiente');
    $asist = (string)($r['asistencia_estado'] ?? 'sin_registrar');
    $total = round((float)($r['total_pago'] ?? 0), 2);
    $falta = max(0.0, round($total - $pagado, 2));
    $cancelada = ($estado === 'cancelada');

    $ini = new \DateTime((string)$r['fecha'] . ' ' . (string)$r['hora_inicio'], $tz);
    $fin = new \DateTime((string)$r['fecha'] . ' ' . (string)$r['hora_fin'], $tz);
    if ($fin <= $ini) { $fin->modify('+1 day'); }
    $ya = $ahoraTs >= $ini->getTimestamp();
    $termino = $ahoraTs >= $fin->getTimestamp();
    $asistio = in_array($asist, ['presente', 'tardanza'], true);
    $money = static fn(float $v): string => 'S/ ' . number_format($v, 2);

    $creada = !empty($r['created_at']) ? date('d/m/Y H:i', strtotime((string)$r['created_at'])) : '';
    $pasos = [];

    $pasos[] = ['clave' => 'registrada', 'titulo' => 'Reserva registrada', 'estado' => 'completo', 'detalle' => $creada];

    if ($pagoEstado === 'rechazado') {
        $pasos[] = ['clave' => 'adelanto', 'titulo' => 'Adelanto rechazado', 'estado' => 'problema', 'detalle' => 'El comprobante fue rechazado'];
    } elseif ($pagado > 0.0) {
        $pasos[] = ['clave' => 'adelanto', 'titulo' => 'Adelanto verificado', 'estado' => 'completo', 'detalle' => $money($pagado) . ' verificados'];
    } elseif ($pagoEstado === 'en_revision') {
        $pasos[] = ['clave' => 'adelanto', 'titulo' => 'Adelanto por verificar', 'estado' => 'pendiente', 'detalle' => 'Comprobante recibido'];
    } else {
        $pasos[] = ['clave' => 'adelanto', 'titulo' => 'Adelanto verificado', 'estado' => 'pendiente', 'detalle' => 'Aún sin pago'];
    }

    if ($cancelada) {
        $pasos[] = ['clave' => 'confirmada', 'titulo' => 'Reserva cancelada', 'estado' => 'problema', 'detalle' => ''];
    } elseif (in_array($estado, ['confirmada', 'finalizada'], true)) {
        $pasos[] = ['clave' => 'confirmada', 'titulo' => 'Reserva confirmada', 'estado' => 'completo', 'detalle' => ''];
    } else {
        $pasos[] = ['clave' => 'confirmada', 'titulo' => 'Reserva confirmada', 'estado' => 'pendiente', 'detalle' => 'Aún pendiente de confirmar'];
    }

    if ($cancelada) {
        $pasos[] = ['clave' => 'jugar', 'titulo' => 'Por jugar', 'estado' => 'pendiente', 'detalle' => ''];
    } elseif ($asist === 'no_asistio') {
        $pasos[] = ['clave' => 'jugar', 'titulo' => 'No asistió', 'estado' => 'problema', 'detalle' => ''];
    } elseif ($asistio && $termino) {
        $pasos[] = ['clave' => 'jugar', 'titulo' => 'Jugó', 'estado' => 'completo', 'detalle' => $asist === 'tardanza' ? 'Llegó tarde' : 'Llegó a tiempo'];
    } elseif ($asistio) {
        $pasos[] = ['clave' => 'jugar', 'titulo' => 'Jugando', 'estado' => 'pendiente', 'detalle' => 'Hasta las ' . $fin->format('H:i')];
    } elseif ($termino) {
        $pasos[] = ['clave' => 'jugar', 'titulo' => 'Por jugar', 'estado' => 'pendiente', 'detalle' => 'Ya pasó la hora: falta registrar la asistencia'];
    } else {
        $pasos[] = ['clave' => 'jugar', 'titulo' => 'Por jugar', 'estado' => 'pendiente', 'detalle' => $ya ? 'En curso' : $ini->format('d/m H:i')];
    }

    if ($pagado > 0.0 && $falta <= 0.0) {
        $pasos[] = ['clave' => 'saldo', 'titulo' => 'Saldo cobrado', 'estado' => 'completo', 'detalle' => 'Pagado completo'];
    } else {
        $pasos[] = ['clave' => 'saldo', 'titulo' => 'Saldo cobrado', 'estado' => 'pendiente', 'detalle' => $cancelada ? '' : 'Falta ' . $money($falta)];
    }

    if ($estado === 'finalizada') {
        $pasos[] = ['clave' => 'finalizada', 'titulo' => 'Finalizada', 'estado' => 'completo', 'detalle' => ''];
    } else {
        $pasos[] = ['clave' => 'finalizada', 'titulo' => 'Finalizada', 'estado' => 'pendiente', 'detalle' => ''];
    }

    // El primer paso pendiente (si no hay un problema antes) pasa a ser el actual
    foreach ($pasos as $i => $paso) {
        if ($paso['estado'] === 'problema') { break; }
        if ($paso['estado'] === 'pendiente') { $pasos[$i]['estado'] = 'actual'; break; }
    }
    return $pasos;
}

/**
 * Categoría visual de una reserva (mismos colores en calendario, leyenda y lista de reservas).
 *   neg = cancelada / no asistió · fin = finalizada · pagada = pago completo
 *   falta = confirmada con adelanto verificado y saldo · verificar = por verificar o sin pago
 *
 * @param array<string, mixed> $r      Fila de la reserva (total_pago, pago_estado, estado, asistencia_estado)
 * @param float                $pagado Suma de pagos verificados
 * @return array{clave: string, texto: string}
 */
function categoriaVisualReserva(array $r, float $pagado): array {
    $estado = (string)($r['estado'] ?? 'pendiente');
    if ($estado === 'cancelada') {
        return ['clave' => 'neg', 'texto' => ((string)($r['asistencia_estado'] ?? '') === 'no_asistio') ? 'No asistió' : 'Cancelada'];
    }
    if ((string)($r['asistencia_estado'] ?? '') === 'no_asistio') {
        return ['clave' => 'neg', 'texto' => 'No asistió'];
    }
    if ($estado === 'finalizada') {
        return ['clave' => 'fin', 'texto' => 'Finalizada'];
    }
    $e = etiquetaPagoReserva((float)($r['total_pago'] ?? 0), $pagado, (string)($r['pago_estado'] ?? 'pendiente'));
    return match ($e['codigo']) {
        'pagado'   => ['clave' => 'pagada', 'texto' => 'Pagada'],
        'adelanto' => ['clave' => 'falta', 'texto' => 'Falta pagar'],
        'rechazado' => ['clave' => 'neg', 'texto' => 'Pago rechazado'],
        'sin_pago' => ['clave' => 'verificar', 'texto' => 'Sin pago'],
        default    => ['clave' => 'verificar', 'texto' => 'Por verificar'],
    };
}

/**
 * Datos de contacto del negocio desde config/contacto.php (vacío = no se muestra).
 *
 * @return array{whatsapp: string}
 */
function contactoConfig(): array {
    static $cfg = null;
    if ($cfg === null) {
        $archivo = ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'contacto.php';
        $datos = is_file($archivo) ? (array)(require $archivo) : [];
        $wa = preg_replace('/\D+/', '', (string)($datos['whatsapp'] ?? ''));
        if (strlen($wa) === 9) { $wa = '51' . $wa; }
        $cfg = ['whatsapp' => strlen($wa) >= 11 ? $wa : ''];
    }
    return $cfg;
}

/**
 * Enlace de WhatsApp del NEGOCIO con un texto prellenado, o null si el número no está configurado.
 */
function whatsappNegocioUrl(string $texto): ?string {
    $wa = contactoConfig()['whatsapp'];
    return $wa === '' ? null : 'https://wa.me/' . $wa . '?text=' . urlencode($texto);
}
