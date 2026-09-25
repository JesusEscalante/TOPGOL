<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cancha;
use App\Models\Notificacion;
use App\Models\Reserva;
use App\Services\TelegramNotifier;

/**
 * ====================================================================
 * TOP GOL - Controlador de Reservas
 * ====================================================================
 * Gestiona el proceso completo de reservas: creación, cálculo de tarifas,
 * validación de solapamiento horario, listado por usuario y administración.
 */
class ReservaController extends Controller {

    /**
     * Muestra el panel general de todas las reservas (Solo Admin)
     */
    public function index(): void {
        $this->requireAdmin();
        $modeloReserva = new Reserva();
        $reservas = $modeloReserva->obtenerTodas();
        $estadisticas = $modeloReserva->obtenerEstadisticas();

        $this->view('reservas/index', [
            'titulo'       => 'Gestión de Reservas - ' . APP_NAME,
            'reservas'     => $reservas,
            'estadisticas' => $estadisticas
        ]);
    }

    /**
     * Formulario para crear una nueva reserva
     *
     * @param string|int|null $id_cancha ID opcional de la cancha preseleccionada
     */
    public function formulario(string|int|null $id_cancha = null): void {
        $this->requireAuth();
        if (isAdmin()) {
            // Admin hace todo en una sola página
            $this->create($id_cancha);
            return;
        }
        $modeloCancha = new Cancha();
        $canchas = $modeloCancha->obtenerDisponibles();
        $canchaSeleccionada = null;
        if ($id_cancha !== null) {
            $canchaSeleccionada = $modeloCancha->obtenerPorId((int)$id_cancha);
        }
        $this->view('reservas/formulario', [
            'titulo'             => 'Reservar Cancha - ' . APP_NAME,
            'canchas'            => $canchas,
            'canchaSeleccionada' => $canchaSeleccionada
        ]);
    }

    public function create(string|int|null $id_cancha = null): void {
        $this->requireAuth();

        $modeloCancha = new Cancha();
        $canchas = $modeloCancha->obtenerDisponibles();

        $canchaSeleccionada = null;
        if ($id_cancha !== null) {
            $canchaSeleccionada = $modeloCancha->obtenerPorId((int)$id_cancha);
        }
        // Si viene de formulario (cliente) con cancha_id por GET, usar esa
        if ($canchaSeleccionada === null && isset($_GET['cancha_id'])) {
            $canchaSeleccionada = $modeloCancha->obtenerPorId((int)$_GET['cancha_id']);
        }

        $clientes = [];
        if (isAdmin()) {
            $modeloUsuario = new \App\Models\Usuario();
            $todos = $modeloUsuario->obtenerTodos();
            $clientes = array_values(array_filter($todos, static fn($u) => ($u['rol'] ?? 'cliente') === 'cliente'));
        }

        $this->view('reservas/create', [
            'titulo'             => 'Reservar Cancha - ' . APP_NAME,
            'canchas'            => $canchas,
            'canchaSeleccionada' => $canchaSeleccionada,
            'clientes'           => $clientes
        ]);
    }

    /**
     * Valida y almacena una nueva reserva en el sistema
     */
    public function store(): void {
        $this->requireAuth();
        $this->validateCsrf();

        $post = $this->sanitizePost();
        $usuarioId = (int)$_SESSION['usuario_id'];
        $canchaId = (int)($post['cancha_id'] ?? 0);
        $fecha = trim($post['fecha'] ?? '');
        $horaInicio = trim($post['hora_inicio'] ?? '');
        $horaInicio = trim($post['hora'] ?? $horaInicio); // compatibilidad evento (hora)
        $duracionHoras = (float)($post['duracion_horas'] ?? $post['duracion'] ?? 1);
        $observaciones = trim($post['observaciones'] ?? '');
        $esAdmin = isAdmin();
        $clienteNombre = trim($post['cliente_nombre'] ?? '');
        $clienteId = (int)($post['cliente_id'] ?? 0);
        $contactoTelefono = trim($post['contacto_telefono'] ?? '');
        $metodoPago = trim($post['metodo_pago'] ?? ($esAdmin ? 'efectivo' : 'yape'));
        $metodosValidos = $esAdmin ? ['yape', 'transferencia_bcp', 'efectivo'] : ['yape', 'transferencia_bcp'];
        if (!in_array($metodoPago, $metodosValidos, true)) {
            $metodoPago = $esAdmin ? 'efectivo' : 'yape';
        }
        $esEfectivoPresencial = $esAdmin && $metodoPago === 'efectivo';
        $adelantoMonto = 20.00;

        // Si es admin, permite asignar la reserva a un cliente existente o usar nombre libre
        if ($esAdmin) {
            if ($clienteId > 0) {
                $modeloUsuarioTmp = new \App\Models\Usuario();
                $cliente = $modeloUsuarioTmp->obtenerPorId($clienteId);
                if ($cliente && ($cliente['rol'] ?? 'cliente') === 'cliente') {
                    $usuarioId = (int)$cliente['id'];
                    if ($clienteNombre === '') {
                        $clienteNombre = (string)$cliente['nombre'];
                    }
                }
            }
            if ($clienteNombre === '') {
                $clienteNombre = trim($post['cliente_nombre'] ?? '');
            }
            if ($clienteNombre === '') {
                sessionFlash('error', 'Como administrador debes ingresar el nombre del cliente.', 'danger');
                $this->redirect("/reserva/crear/{$canchaId}");
            }
        }

        // Validación de número de contacto (celular / WhatsApp) - obligatorio para ambas vistas
        if ($contactoTelefono === '') {
            sessionFlash('error', 'Ingresa un número de contacto (celular / WhatsApp) para la reserva.', 'danger');
            $this->redirect("/reserva/crear/{$canchaId}");
        }
        if (!preg_match('/^[\d\s\+\-]{7,20}$/', $contactoTelefono)) {
            sessionFlash('error', 'El número de contacto no es válido. Usa 7 a 20 dígitos (puede incluir +, espacios o guiones).', 'danger');
            $this->redirect("/reserva/crear/{$canchaId}");
        }
        // Normalizar con +51 si viene solo el número (diseño con etiqueta +51)
        $contactoTelefono = preg_replace('/\s+/', '', $contactoTelefono);
        if (!str_starts_with($contactoTelefono, '+')) {
            $contactoTelefono = '+51 ' . ltrim($contactoTelefono, '0');
        } else {
            $contactoTelefono = preg_replace('/^\+51\s*/', '+51 ', $contactoTelefono);
        }

        // 1. Validaciones básicas de campos
        if ($canchaId <= 0 || empty($fecha) || empty($horaInicio) || $duracionHoras <= 0) {
            sessionFlash('error', 'Por favor completa todos los datos obligatorios para la reserva.', 'danger');
            $this->redirect("/reserva/crear/{$canchaId}");
        }

        // 2. Validación de fecha (no permitir fechas anteriores al día de hoy)
        $hoy = date('Y-m-d');
        if ($fecha < $hoy) {
            sessionFlash('error', 'No es posible realizar reservas en fechas pasadas.', 'danger');
            $this->redirect("/reserva/crear/{$canchaId}");
        }

        // 3. Verificar existencia y estado de la cancha
        $modeloCancha = new Cancha();
        $cancha = $modeloCancha->obtenerPorId($canchaId);
        if (!$cancha || $cancha['estado'] !== 'disponible') {
            sessionFlash('error', 'La cancha seleccionada no está disponible para alquiler.', 'danger');
            $this->redirect('/canchas');
        }

        // 4. Calcular hora de fin según la duración en horas
        $timestampInicio = strtotime("{$fecha} {$horaInicio}");
        if ($timestampInicio === false) {
            sessionFlash('error', 'El formato de hora de inicio es inválido.', 'danger');
            $this->redirect("/reserva/crear/{$canchaId}");
        }

        $horaInicioNormalizada = date('H:i:s', $timestampInicio);
        $timestampFin = $timestampInicio + (int)round($duracionHoras * 3600);
        $horaFinNormalizada = date('H:i:s', $timestampFin);

        // 5. Control estricto de disponibilidad (Solapamiento de horarios)
        $estaDisponible = $modeloCancha->estaDisponible($canchaId, $fecha, $horaInicioNormalizada, $horaFinNormalizada);
        if (!$estaDisponible) {
            sessionFlash('error', "La cancha '{$cancha['nombre']}' ya cuenta con una reserva activa entre las {$horaInicioNormalizada} y las {$horaFinNormalizada} en la fecha {$fecha}. Por favor elige otro horario o cancha.", 'warning');
            $this->redirect("/reserva/crear/{$canchaId}");
        }

        // 6. Cálculo automático del monto total a pagar
        $precioHora = (float)$cancha['precio_hora'];
        $totalPago = $duracionHoras * $precioHora;

        // Validar duración en bloques de 30 min
        $duracionesValidas = [0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 6, 8];
        if (!in_array($duracionHoras, $duracionesValidas, true)) {
            // permitir cualquier múltiplo de 0.5 entre 0.5 y 8
            if ($duracionHoras < 0.5 || $duracionHoras > 8 || fmod($duracionHoras * 2, 1) !== 0.0) {
                sessionFlash('error', 'Duración no válida. Usa bloques de 30 min (0.5, 1, 1.5, 2...).', 'danger');
                $this->redirect("/reserva/crear/{$canchaId}");
            }
        }

        // Si es admin quien reserva, se confirma directamente; si es cliente, queda 'pendiente' o 'confirmada'
        $estadoInicial = isAdmin() ? 'confirmada' : 'confirmada';

        // Para admin en efectivo: pago completo presencial, sin comprobante
        if ($esEfectivoPresencial) {
            $adelantoMonto = $duracionHoras * (float)$cancha['precio_hora'];
        }

        // 6b. Comprobante: obligatorio para clientes, opcional para admin (cualquier método)
        if ($esAdmin) {
            $comprobante = null;
            $pagoEstado = 'verificado';
            $archivo = $_FILES['comprobante'] ?? null;
            $tieneArchivo = $archivo !== null && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if ($tieneArchivo) {
                if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    sessionFlash('error', 'Error al subir el comprobante. Intenta nuevamente.', 'danger');
                    $this->redirect("/reserva/crear/{$canchaId}");
                }
                $maxSize = 5 * 1024 * 1024;
                if (($archivo['size'] ?? 0) <= 0 || ($archivo['size'] ?? 0) > $maxSize) {
                    sessionFlash('error', 'El comprobante supera el tamaño máximo de 5MB.', 'danger');
                    $this->redirect("/reserva/crear/{$canchaId}");
                }
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($archivo['tmp_name']) ?: '';
                $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
                if (!isset($permitidos[$mime])) {
                    sessionFlash('error', 'Formato de comprobante no válido. Usa JPG, PNG o PDF.', 'danger');
                    $this->redirect("/reserva/crear/{$canchaId}");
                }
                $dirDestino = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'comprobantes';
                if (!is_dir($dirDestino)) { mkdir($dirDestino, 0755, true); }
                $nombreSeguro = 'reserva_' . $usuarioId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $permitidos[$mime];
                $rutaAbsoluta = $dirDestino . DIRECTORY_SEPARATOR . $nombreSeguro;
                if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
                    sessionFlash('error', 'No se pudo guardar el comprobante. Intenta nuevamente.', 'danger');
                    $this->redirect("/reserva/crear/{$canchaId}");
                }
                $comprobante = ['ruta' => 'uploads/comprobantes/' . $nombreSeguro, 'nombre' => (string)($archivo['name'] ?? $nombreSeguro), 'tipo' => $mime, 'size' => (int)$archivo['size']];
                // admin con comprobante queda verificado igualmente
                $pagoEstado = 'verificado';
            }
        } else {
        $archivo = $_FILES['comprobante'] ?? null;
        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            sessionFlash('error', 'Falta tu comprobante de pago. Súbelo para completar tu reserva.', 'danger');
            $this->redirect("/reserva/crear/{$canchaId}");
        }
        if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            sessionFlash('error', 'Error al subir el comprobante. Intenta nuevamente.', 'danger');
            $this->redirect("/reserva/crear/{$canchaId}");
        }

        $maxSize = 5 * 1024 * 1024;
        if (($archivo['size'] ?? 0) <= 0 || ($archivo['size'] ?? 0) > $maxSize) {
            sessionFlash('error', 'El comprobante supera el tamaño máximo de 5MB.', 'danger');
            $this->redirect("/reserva/crear/{$canchaId}");
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($archivo['tmp_name']) ?: '';
        $permitidos = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'application/pdf' => 'pdf',
        ];
        if (!isset($permitidos[$mime])) {
            sessionFlash('error', 'Formato de comprobante no válido. Usa JPG, PNG o PDF.', 'danger');
            $this->redirect("/reserva/crear/{$canchaId}");
        }

        $dirDestino = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'comprobantes';
        if (!is_dir($dirDestino)) {
            mkdir($dirDestino, 0755, true);
        }

        $nombreSeguro = 'reserva_' . $usuarioId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $permitidos[$mime];
        $rutaAbsoluta = $dirDestino . DIRECTORY_SEPARATOR . $nombreSeguro;
        if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
            sessionFlash('error', 'No se pudo guardar el comprobante. Intenta nuevamente.', 'danger');
            $this->redirect("/reserva/crear/{$canchaId}");
        }

        $comprobante = [
            'ruta'   => 'uploads/comprobantes/' . $nombreSeguro,
            'nombre' => (string)($archivo['name'] ?? $nombreSeguro),
            'tipo'   => $mime,
            'size'   => (int)$archivo['size'],
        ];
        $pagoEstado = 'en_revision';
        } // fin admin/cliente

        // 7. Guardar reserva
        $modeloReserva = new Reserva();
        $idReserva = $modeloReserva->crear([
            'usuario_id'         => $usuarioId,
            'cancha_id'          => $canchaId,
            'fecha'              => $fecha,
            'hora_inicio'        => $horaInicioNormalizada,
            'hora_fin'           => $horaFinNormalizada,
            'duracion_horas'     => $duracionHoras,
            'total_pago'         => $totalPago,
            'estado'             => $estadoInicial,
            'observaciones'      => $observaciones,
            'metodo_pago'        => $metodoPago,
            'adelanto_monto'     => $adelantoMonto,
            'cliente_nombre'     => $esAdmin ? $clienteNombre : null,
            'contacto_telefono'  => $contactoTelefono,
            'pago_estado'        => $pagoEstado,
            'comprobante_ruta'   => $comprobante['ruta'] ?? null,
            'comprobante_nombre' => $comprobante['nombre'] ?? null,
            'comprobante_tipo'   => $comprobante['tipo'] ?? null,
            'comprobante_size'   => $comprobante['size'] ?? null,
            'comprobante_subido_at' => !empty($comprobante['ruta'] ?? null) ? date('Y-m-d H:i:s') : null,
        ]);

        if ($idReserva > 0) {
            // Notificar por Telegram (no bloquea el flujo si falla)
            $nombreTelegram = $esAdmin && $clienteNombre !== '' ? $clienteNombre : ($_SESSION['usuario_nombre'] ?? '');
            $metodoLblTelegram = match ($metodoPago) {
                'efectivo' => 'Efectivo (presencial, pago completo)',
                'transferencia_bcp' => 'Transferencia BCP',
                default => 'Yape',
            };
            $comprobanteUrlTelegram = !empty($comprobante['ruta'] ?? null) ? URL_BASE . '/' . ltrim($comprobante['ruta'], '/') : '';
            (new TelegramNotifier())->avisarReserva([
                'id'              => $idReserva,
                'cancha'          => $cancha['nombre'],
                'cliente'         => $nombreTelegram,
                'fecha'           => $fecha,
                'hora_inicio'     => $horaInicioNormalizada,
                'hora_fin'        => $horaFinNormalizada,
                'total'           => formatPrice($totalPago),
                'metodo_pago'     => $metodoLblTelegram,
                'comprobante_url' => $comprobanteUrlTelegram,
            ]);
            // Notificación interna tiempo real para admins (al detalle)
            (new Notificacion())->notificarAdmins('reserva', "Nueva reserva #{$idReserva}", "{$nombreTelegram} reservó {$cancha['nombre']} el {$fecha} {$horaInicioNormalizada}", url('/mis-reservas/' . $idReserva));

            if ($comprobante !== null) {
                sessionFlash('success', "¡Reserva #{$idReserva} registrada! Recibimos tu comprobante ({$metodoPago}) y está en revisión. Total: " . formatPrice($totalPago), 'success');
            } else {
                sessionFlash('success', "¡Reserva #{$idReserva} confirmada con éxito para la {$cancha['nombre']}! Total: " . formatPrice($totalPago), 'success');
            }
            if (isAdmin()) {
                $this->redirect('/admin/reservas');
            } else {
                $this->redirect('/mis-reservas/' . $idReserva);
            }
        } else {
            sessionFlash('error', 'Ocurrió un error inesperado al registrar la reserva.', 'danger');
            $this->redirect("/reserva/crear/{$canchaId}");
        }
    }

    /**
     * Panel administrativo de reservas (layout del panel admin).
     * Soporta filtros por estado, estado de pago y búsqueda.
     */
    public function adminPanel(): void {
        $this->requireAdmin();

        $filtroEstado = trim($_GET['estado'] ?? '');
        $filtroPago = trim($_GET['pago'] ?? '');
        $busqueda = trim($_GET['q'] ?? '');

        $estadosValidos = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];
        $pagosValidos = ['pendiente', 'en_revision', 'verificado', 'rechazado'];
        if ($filtroEstado !== '' && !in_array($filtroEstado, $estadosValidos, true)) {
            $filtroEstado = '';
        }
        if ($filtroPago !== '' && !in_array($filtroPago, $pagosValidos, true)) {
            $filtroPago = '';
        }

        $modeloReserva = new Reserva();
        $todas = $modeloReserva->obtenerTodas();

        $reservas = array_values(array_filter($todas, function ($r) use ($filtroEstado, $filtroPago, $busqueda) {
            if ($filtroEstado !== '' && $r['estado'] !== $filtroEstado) {
                return false;
            }
            if ($filtroPago !== '' && ($r['pago_estado'] ?? 'pendiente') !== $filtroPago) {
                return false;
            }
            if ($busqueda !== '') {
                $q = mb_strtolower($busqueda);
                $texto = mb_strtolower('#' . $r['id'] . ' ' . ($r['usuario_nombre'] ?? '') . ' ' . ($r['usuario_email'] ?? '') . ' ' . ($r['cancha_nombre'] ?? ''));
                if (!str_contains($texto, $q)) {
                    return false;
                }
            }
            return true;
        }));

        $this->view('reservas/admin', [
            'titulo'        => 'Reservas - Panel Administrador',
            'adminMenu'     => ($filtroPago === 'en_revision' && $filtroEstado === '' && $busqueda === '') ? 'pagos' : 'reservas',
            'reservas'      => $reservas,
            'estadisticas'  => $modeloReserva->obtenerEstadisticas(),
            'filtroEstado'  => $filtroEstado,
            'filtroPago'    => $filtroPago,
            'busqueda'      => $busqueda,
        ], false);
    }

    /**
     * Actualiza el estado de verificación del pago de una reserva (Solo Admin)
     *
     * @param string|int $id ID de la reserva
     */
    public function updatePayment(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idReserva = (int)$id;
        $nuevoEstado = trim($_POST['pago_estado'] ?? '');

        $estadosValidos = ['pendiente', 'en_revision', 'verificado', 'rechazado'];
        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            if ($this->isAjax()) { $this->json(['ok'=>false,'error'=>'Estado de pago no válido'], 400); }
            sessionFlash('error', 'Estado de pago no válido.', 'danger');
            $this->redirect('/admin/reservas');
        }

        $modeloReserva = new Reserva();
        $reserva = $modeloReserva->obtenerPorId($idReserva);
        if (!$reserva) {
            if ($this->isAjax()) { $this->json(['ok'=>false,'error'=>'Reserva no encontrada'], 404); }
            sessionFlash('error', 'La reserva no fue encontrada.', 'warning');
            $this->redirect('/admin/reservas');
        }

        $ok = $modeloReserva->actualizarEstadoPago($idReserva, $nuevoEstado, (int)$_SESSION['usuario_id']);
        if ($this->isAjax()) {
            if ($ok) {
                $this->json(['ok'=>true,'id'=>$idReserva,'pago_estado'=>$nuevoEstado]);
            } else {
                $this->json(['ok'=>false,'error'=>'No se pudo actualizar'], 500);
            }
        }
        if ($ok) {
            $msg = $nuevoEstado === 'verificado'
                ? "Pago de la reserva #{$idReserva} verificado correctamente."
                : "El pago de la reserva #{$idReserva} fue marcado como '{$nuevoEstado}'.";
            sessionFlash('success', $msg, 'success');
        } else {
            sessionFlash('error', 'No se pudo actualizar el estado del pago.', 'danger');
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/reservas');
    }

    /**
     * Calendario semanal completo de reservas (Solo Admin).
     * Vista de ocupación por día y hora con filtro por cancha.
     */
    public function calendario(): void {
        $this->requireAdmin();

        // Semana a mostrar (cualquier fecha dentro de ella; por defecto hoy)
        $ref = trim($_GET['semana'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ref)) {
            $ref = date('Y-m-d');
        }

        // Lunes de la semana (N: 1=lunes ... 7=domingo)
        $lunes = date('Y-m-d', strtotime($ref . ' -' . (date('N', strtotime($ref)) - 1) . ' days'));
        $dias = [];
        for ($i = 0; $i < 7; $i++) {
            $dias[] = date('Y-m-d', strtotime($lunes . " +{$i} days"));
        }

        // Filtro por cancha
        $filtroCancha = (int)($_GET['cancha'] ?? 0);

        $modeloCancha = new Cancha();
        $canchas = $modeloCancha->obtenerTodas();

        // Reservas de la semana (excluye canceladas)
        $modeloReserva = new Reserva();
        $todas = $modeloReserva->obtenerTodas();
        $semana = array_values(array_filter($todas, static function ($r) use ($dias, $filtroCancha) {
            if (!in_array($r['fecha'], $dias, true)) {
                return false;
            }
            if ($r['estado'] === 'cancelada') {
                return false;
            }
            if ($filtroCancha > 0 && (int)$r['cancha_id'] !== $filtroCancha) {
                return false;
            }
            return true;
        }));

        // Agrupar por [fecha][slot hora] las reservas que se solapan con cada bloque 30 min (07:00-00:00)
        $horas = [];
        for ($h = 7; $h <= 23; $h++) {
            foreach (['00','30'] as $mm) {
                $horas[] = sprintf('%02d:%s', $h, $mm);
            }
        }
        $grilla = [];
        foreach ($dias as $d) {
            foreach ($horas as $slot) {
                $ini = $slot . ':00';
                $fin = date('H:i:s', strtotime($slot . ' +30 minutes'));
                if ($fin === '00:00:00') {
                    $fin = '24:00:00';
                }
                $celda = [];
                foreach ($semana as $r) {
                    if ($r['fecha'] !== $d) {
                        continue;
                    }
                    $rFin = $r['hora_fin'] === '00:00:00' ? '24:00:00' : $r['hora_fin'];
                    if ($r['hora_inicio'] < $fin && $rFin > $ini) {
                        $celda[] = $r;
                    }
                }
                usort($celda, static fn($a, $b) => strcmp($a['hora_inicio'], $b['hora_inicio']));
                $grilla[$d][$slot] = $celda;
            }
        }

        $this->view('reservas/calendario', [
            'titulo'       => 'Calendario de reservas - Panel Administrador',
            'adminMenu'    => 'reservas',
            'dias'         => $dias,
            'lunes'        => $lunes,
            'semanaRef'    => $ref,
            'horas'        => $horas,
            'grilla'       => $grilla,
            'canchas'      => $canchas,
            'filtroCancha' => $filtroCancha,
            'hoy'          => date('Y-m-d'),
        ], false);
    }

    /**
     * Página informativa de eventos deportivos (pública)
     */
    public function eventosInfo(): void {
        $this->view('eventos/index', [
            'titulo' => 'Eventos Deportivos - ' . APP_NAME
        ]);
    }

    /**
     * Vista para reservar un evento (múltiples canchas el mismo día)
     * Adelanto S/ 20 por cancha.
     */
    public function eventoCreate(): void {
        $this->requireAuth();

        $modeloCancha = new Cancha();
        $canchas = $modeloCancha->obtenerDisponibles();

        $this->view('reservas/evento', [
            'titulo'  => 'Reservar Evento - ' . APP_NAME,
            'canchas' => $canchas
        ]);
    }

    /**
     * Guarda un evento con múltiples reservas el mismo día
     */
    public function eventoStore(): void {
        $this->requireAuth();
        $this->validateCsrf();

        $post = $this->sanitizePost();
        $usuarioId = (int)$_SESSION['usuario_id'];
        $fecha = trim($post['fecha'] ?? '');
        $observaciones = trim($post['observaciones'] ?? '');
        $esAdmin = isAdmin();
        $metodoPago = trim($post['metodo_pago'] ?? ($esAdmin ? 'efectivo' : 'yape'));
        $metodosValidos = $esAdmin ? ['yape', 'transferencia_bcp', 'efectivo'] : ['yape', 'transferencia_bcp'];
        if (!in_array($metodoPago, $metodosValidos, true)) {
            $metodoPago = $esAdmin ? 'efectivo' : 'yape';
        }
        $esEfectivoPresencial = $esAdmin && $metodoPago === 'efectivo';
        $clienteNombre = '';
        $clienteId = 0;
        $contactoTelefono = trim($post['contacto_telefono'] ?? '');
        if ($esAdmin) {
            $clienteNombre = trim($post['cliente_nombre'] ?? '');
            $clienteId = (int)($post['cliente_id'] ?? 0);
            if ($clienteId > 0) {
                $mu = new \App\Models\Usuario();
                $cl = $mu->obtenerPorId($clienteId);
                if ($cl && ($cl['rol'] ?? 'cliente') === 'cliente') {
                    $usuarioId = (int)$cl['id'];
                    if ($clienteNombre === '') {
                        $clienteNombre = (string)$cl['nombre'];
                    }
                    if ($contactoTelefono === '' && !empty($cl['telefono'])) {
                        $contactoTelefono = (string)$cl['telefono'];
                    }
                }
            }
            if ($clienteNombre === '') {
                sessionFlash('error', 'Como administrador debes ingresar el nombre del cliente.', 'danger');
                $this->redirect('/evento');
            }
        }

        if ($contactoTelefono === '') {
            sessionFlash('error', 'Ingresa un número de contacto (celular / WhatsApp) para el evento.', 'danger');
            $this->redirect('/evento');
        }
        if (!preg_match('/^[\d\s\+\-]{7,20}$/', $contactoTelefono)) {
            sessionFlash('error', 'El número de contacto no es válido. Usa 7 a 20 dígitos (puede incluir +, espacios o guiones).', 'danger');
            $this->redirect('/evento');
        }
        $contactoTelefono = preg_replace('/\s+/', '', $contactoTelefono);
        if (!str_starts_with($contactoTelefono, '+')) {
            $contactoTelefono = '+51 ' . ltrim($contactoTelefono, '0');
        } else {
            $contactoTelefono = preg_replace('/^\+51\s*/', '+51 ', $contactoTelefono);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $fecha < date('Y-m-d')) {
            sessionFlash('error', 'Selecciona una fecha válida a partir de hoy.', 'danger');
            $this->redirect('/evento');
        }

        $canchasIds = $post['canchas_ids'] ?? [];
        if (!is_array($canchasIds)) {
            $canchasIds = [$canchasIds];
        }
        $canchasIds = array_values(array_unique(array_map('intval', $canchasIds)));
        $canchasIds = array_values(array_filter($canchasIds, static fn($v) => $v > 0));

        if (empty($canchasIds)) {
            sessionFlash('error', 'Selecciona al menos una cancha para tu evento.', 'danger');
            $this->redirect('/evento');
        }
        if (count($canchasIds) > 5) {
            sessionFlash('error', 'Máximo 5 canchas por evento.', 'danger');
            $this->redirect('/evento');
        }

        // Horario único para todas las canchas del evento
        $hora = trim($post['hora'] ?? '');
        $dur = (float)($post['duracion'] ?? 1);
        if ($hora === '' || $dur < 0.5) {
            sessionFlash('error', 'Selecciona el horario y duración del evento.', 'danger');
            $this->redirect('/evento');
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $hora)) {
            sessionFlash('error', 'Hora del evento inválida.', 'danger');
            $this->redirect('/evento');
        }
        if ($dur < 0.5 || $dur > 8 || fmod($dur * 2, 1) !== 0.0) {
            sessionFlash('error', 'Duración no válida. Usa bloques de 30 min (0.5, 1, 1.5...).', 'danger');
            $this->redirect('/evento');
        }

        $modeloCancha = new Cancha();
        $adelantoUnit = $esEfectivoPresencial ? 0 : 20;
        // Validar disponibilidad y calcular totales (mismo horario para todas)
        $totalGeneral = 0;
        $items = [];
        $tsIniBase = strtotime("{$fecha} {$hora}");
        if ($tsIniBase === false) {
            sessionFlash('error', 'Hora del evento inválida.', 'danger');
            $this->redirect('/evento');
        }
        $horaIni = date('H:i:s', $tsIniBase);
        $horaFin = date('H:i:s', $tsIniBase + (int)round($dur * 3600));
        foreach ($canchasIds as $cid) {
            $cancha = $modeloCancha->obtenerPorId($cid);
            if (!$cancha || $cancha['estado'] !== 'disponible') {
                sessionFlash('error', "La cancha #{$cid} no está disponible.", 'danger');
                $this->redirect('/evento');
            }
            if (!$modeloCancha->estaDisponible($cid, $fecha, $horaIni, $horaFin)) {
                sessionFlash('error', "La {$cancha['nombre']} ya está ocupada el {$fecha} de {$horaIni} a {$horaFin}.", 'warning');
                $this->redirect('/evento');
            }
            $precio = (float)$cancha['precio_hora'];
            $total = $precio * $dur;
            $adelanto = $esEfectivoPresencial ? $total : $adelantoUnit;
            $totalGeneral += $total;
            $items[] = [
                'cancha' => $cancha,
                'hora_ini' => $horaIni,
                'hora_fin' => $horaFin,
                'dur' => $dur,
                'total' => $total,
                'adelanto' => $adelanto
            ];
        }

        $adelantoTotal = $esEfectivoPresencial ? $totalGeneral : (count($items) * $adelantoUnit);

        // Comprobante: opcional para admin, obligatorio para cliente
        $comprobante = null;
        $pagoEstado = $esAdmin ? 'verificado' : 'en_revision';
        if (!$esAdmin || ($esAdmin && isset($_FILES['comprobante']) && ($_FILES['comprobante']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
            $archivo = $_FILES['comprobante'] ?? null;
            $tieneArchivo = $archivo !== null && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if (!$esAdmin && !$tieneArchivo) {
                sessionFlash('error', 'Falta tu comprobante de pago. Súbelo para completar tu evento.', 'danger');
                $this->redirect('/evento');
            }
            if ($tieneArchivo) {
                if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    sessionFlash('error', 'Error al subir el comprobante.', 'danger');
                    $this->redirect('/evento');
                }
                $maxSize = 5 * 1024 * 1024;
                if (($archivo['size'] ?? 0) <= 0 || ($archivo['size'] ?? 0) > $maxSize) {
                    sessionFlash('error', 'El comprobante supera 5MB.', 'danger');
                    $this->redirect('/evento');
                }
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($archivo['tmp_name']) ?: '';
                $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
                if (!isset($permitidos[$mime])) {
                    sessionFlash('error', 'Formato no válido. Usa JPG, PNG o PDF.', 'danger');
                    $this->redirect('/evento');
                }
                $dir = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'comprobantes';
                if (!is_dir($dir)) { mkdir($dir, 0755, true); }
                $nombreSeguro = 'evento_' . $usuarioId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $permitidos[$mime];
                $rutaAbs = $dir . DIRECTORY_SEPARATOR . $nombreSeguro;
                if (!move_uploaded_file($archivo['tmp_name'], $rutaAbs)) {
                    sessionFlash('error', 'No se pudo guardar el comprobante.', 'danger');
                    $this->redirect('/evento');
                }
                $comprobante = ['ruta' => 'uploads/comprobantes/' . $nombreSeguro, 'nombre' => (string)($archivo['name'] ?? $nombreSeguro), 'tipo' => $mime, 'size' => (int)$archivo['size']];
                $pagoEstado = $esAdmin ? 'verificado' : 'en_revision';
            } else {
                // admin sin archivo
                $comprobante = null;
                $pagoEstado = 'verificado';
            }
        } else {
            $pagoEstado = $esAdmin ? 'verificado' : 'pendiente';
        }

        $grupo = bin2hex(random_bytes(8));
        $modeloReserva = new Reserva();
        $creados = [];
        foreach ($items as $it) {
            $id = $modeloReserva->crear([
                'usuario_id' => $usuarioId,
                'cancha_id' => (int)$it['cancha']['id'],
                'fecha' => $fecha,
                'hora_inicio' => $it['hora_ini'],
                'hora_fin' => $it['hora_fin'],
                'duracion_horas' => $it['dur'],
                'total_pago' => $it['total'],
                'estado' => 'confirmada',
                'observaciones' => $observaciones !== '' ? $observaciones . ' | Evento ' . $grupo : 'Evento ' . $grupo,
                'metodo_pago' => $metodoPago,
                'adelanto_monto' => $it['adelanto'],
                'cliente_nombre' => $esAdmin ? $clienteNombre : null,
                'contacto_telefono' => $contactoTelefono,
                'evento_grupo' => $grupo,
                'pago_estado' => $pagoEstado,
                'comprobante_ruta' => $comprobante['ruta'] ?? null,
                'comprobante_nombre' => $comprobante['nombre'] ?? null,
                'comprobante_tipo' => $comprobante['tipo'] ?? null,
                'comprobante_size' => $comprobante['size'] ?? null,
                'comprobante_subido_at' => !empty($comprobante['ruta'] ?? null) ? date('Y-m-d H:i:s') : null,
            ]);
            if ($id > 0) { $creados[] = $id; }
        }

        if (empty($creados)) {
            sessionFlash('error', 'No se pudo registrar el evento.', 'danger');
            $this->redirect('/evento');
        }

        $listaCanchas = implode(', ', array_map(static fn($it) => $it['cancha']['nombre'] . ' ' . substr($it['hora_ini'], 0, 5) . '-' . substr($it['hora_fin'], 0, 5), $items));
        (new TelegramNotifier())->avisarReserva([
            'id' => (int)$creados[0],
            'cancha' => 'EVENTO (' . count($creados) . ' canchas): ' . $listaCanchas,
            'cliente' => $esAdmin && $clienteNombre !== '' ? $clienteNombre : ($_SESSION['usuario_nombre'] ?? ''),
            'fecha' => $fecha,
            'hora_inicio' => $items[0]['hora_ini'],
            'hora_fin' => $items[count($items)-1]['hora_fin'],
            'total' => formatPrice($totalGeneral) . ' (adelanto ' . formatPrice($adelantoTotal) . ' por ' . count($creados) . ' canchas)',
            'metodo_pago' => $metodoPago === 'efectivo' ? 'Efectivo (presencial, pago completo)' : ($metodoPago === 'transferencia_bcp' ? 'Transferencia BCP' : 'Yape'),
            'comprobante_url' => !empty($comprobante['ruta'] ?? null) ? URL_BASE . '/' . ltrim($comprobante['ruta'], '/') : '',
        ]);
        (new Notificacion())->notificarAdmins('evento', "Nuevo evento (" . count($creados) . " canchas)", $fecha . ' ' . $items[0]['hora_ini'] . ' · ' . $listaCanchas, url('/mis-reservas/' . (int)$creados[0]));

        sessionFlash('success', "¡Evento registrado con " . count($creados) . " canchas el {$fecha}! Total: " . formatPrice($totalGeneral) . " · Adelanto: " . formatPrice($adelantoTotal) . " (S/ 20 x cancha).", 'success');
        if ($esAdmin) {
            $this->redirect('/admin/reservas');
        } else {
            $this->redirect('/mis-reservas');
        }
    }

    /**
     * Muestra el historial y reservas activas del cliente conectado
     */
    public function misReservas(): void {
        $this->requireAuth();
        $usuarioId = (int)$_SESSION['usuario_id'];

        $modeloReserva = new Reserva();
        $reservas = $modeloReserva->obtenerPorUsuario($usuarioId);

        $this->view('reservas/mis-reservas', [
            'titulo'   => 'Mis Reservas - ' . APP_NAME,
            'reservas' => $reservas
        ]);
    }

    /**
     * Muestra el detalle y estado de una reserva específica (página de confirmación)
     *
     * @param string|int $id ID de la reserva
     */
    public function detalle(string|int $id): void {
        $this->requireAuth();
        $idReserva = (int)$id;
        $usuarioId = (int)$_SESSION['usuario_id'];

        $modeloReserva = new Reserva();
        $reserva = $modeloReserva->obtenerPorId($idReserva);

        if (!$reserva) {
            sessionFlash('error', 'La reserva solicitada no existe.', 'warning');
            $this->redirect('/mis-reservas');
        }

        // Solo el dueño o un administrador pueden ver el detalle
        if (!isAdmin() && (int)$reserva['usuario_id'] !== $usuarioId) {
            sessionFlash('error', 'No tienes autorización para ver esta reserva.', 'danger');
            $this->redirect('/mis-reservas');
        }

        $modeloCancha = new Cancha();
        $cancha = $modeloCancha->obtenerPorId((int)$reserva['cancha_id']);

        // Otras reservas del mismo usuario (excluye la actual)
        $todas = $modeloReserva->obtenerPorUsuario((int)$reserva['usuario_id']);
        $otras = array_values(array_filter($todas, fn($r) => (int)$r['id'] !== $idReserva));
        $otras = array_slice($otras, 0, 4);

        $this->view('reservas/detalle', [
            'titulo'   => "Reserva #{$idReserva} - " . APP_NAME,
            'reserva'  => $reserva,
            'cancha'   => $cancha,
            'otras'    => $otras
        ]);
    }

    /**
     * Permite cancelar una reserva
     */
    public function cancel(string|int $id): void {
        $this->requireAuth();
        $idReserva = (int)$id;
        $usuarioId = (int)$_SESSION['usuario_id'];

        $modeloReserva = new Reserva();
        $reserva = $modeloReserva->obtenerPorId($idReserva);

        if (!$reserva) {
            sessionFlash('error', 'La reserva no fue encontrada.', 'warning');
            $this->redirect('/mis-reservas');
        }

        // Si no es admin y no es el dueño de la reserva
        if (!isAdmin() && (int)$reserva['usuario_id'] !== $usuarioId) {
            sessionFlash('error', 'No tienes autorización para cancelar esta reserva.', 'danger');
            $this->redirect('/mis-reservas');
        }

        $cancelado = $modeloReserva->cancelar($idReserva, $usuarioId, isAdmin());

        if ($cancelado) {
            sessionFlash('success', "La reserva #{$idReserva} ha sido cancelada.", 'info');
        } else {
            sessionFlash('error', 'No se pudo cancelar la reserva. Es posible que ya haya finalizado o esté cancelada.', 'warning');
        }

        if (isAdmin()) {
            $this->redirect('/reservas');
        } else {
            $this->redirect('/mis-reservas');
        }
    }

    private function isAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains((string)$_SERVER['HTTP_ACCEPT'], 'application/json'));
    }

    /**
     * Permite a un administrador actualizar el estado de una reserva
     */
    public function updateStatus(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idReserva = (int)$id;
        $nuevoEstado = trim($_POST['estado'] ?? '');

        $estadosValidos = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];
        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            if ($this->isAjax()) { $this->json(['ok'=>false,'error'=>'Estado no válido'], 400); }
            sessionFlash('error', 'Estado de reserva no válido.', 'danger');
            $this->redirect('/admin/reservas');
        }

        $modeloReserva = new Reserva();
        $modeloReserva->actualizarEstado($idReserva, $nuevoEstado);

        if ($this->isAjax()) {
            $this->json(['ok'=>true,'id'=>$idReserva,'estado'=>$nuevoEstado,'msg'=>"Estado actualizado a '{$nuevoEstado}'"]);
        }
        sessionFlash('success', "El estado de la reserva #{$idReserva} fue actualizado a '{$nuevoEstado}'.", 'success');
        $this->redirect('/admin/reservas');
    }

    /**
     * Endpoint API para comprobar disponibilidad en tiempo real vía JavaScript
     */
    public function checkAvailability(): void {
        $canchaId = (int)($_GET['cancha_id'] ?? 0);
        $fecha = trim($_GET['fecha'] ?? '');
        $horaInicio = trim($_GET['hora_inicio'] ?? '');
        $duracion = (float)($_GET['duracion'] ?? 1);

        if ($canchaId <= 0 || empty($fecha) || empty($horaInicio)) {
            $this->json(['error' => 'Parámetros insuficientes'], 400);
        }

        $timestampInicio = strtotime("{$fecha} {$horaInicio}");
        if ($timestampInicio === false) {
            $this->json(['error' => 'Hora inválida'], 400);
        }

        $horaInicioNormalizada = date('H:i:s', $timestampInicio);
        $horaFinNormalizada = date('H:i:s', $timestampInicio + (int)round($duracion * 3600));

        $modeloCancha = new Cancha();
        $cancha = $modeloCancha->obtenerPorId($canchaId);

        if (!$cancha) {
            $this->json(['error' => 'Cancha no encontrada'], 404);
        }

        $disponible = $modeloCancha->estaDisponible($canchaId, $fecha, $horaInicioNormalizada, $horaFinNormalizada);
        $precioHora = (float)$cancha['precio_hora'];
        $total = $duracion * $precioHora;

        $this->json([
            'disponible'    => $disponible,
            'cancha'        => $cancha['nombre'],
            'hora_inicio'   => $horaInicioNormalizada,
            'hora_fin'      => $horaFinNormalizada,
            'duracion'      => $duracion,
            'precio_hora'   => $precioHora,
            'total'         => $total,
            'total_formato' => formatPrice($total)
        ]);
    }
}