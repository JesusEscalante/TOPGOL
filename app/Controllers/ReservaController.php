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
        $ocupacion = [];
        if ($canchaSeleccionada) {
            $ocupacion = (new Reserva())->ocupacionCancha((int)$canchaSeleccionada['id'], date('Y-m-d'), date('Y-m-d', strtotime('+120 days')));
        }
        $this->view('reservas/formulario', [
            'titulo'             => 'Reservar Cancha - ' . APP_NAME,
            'canchas'            => $canchas,
            'canchaSeleccionada' => $canchaSeleccionada,
            'ocupacion'          => $ocupacion
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
    /**
     * Elimina un comprobante ya subido cuando la reserva no se pudo guardar (no deja archivos huérfanos).
     *
     * @param array<string, mixed>|null $comprobante Con la clave 'ruta' (relativa a public/)
     */
    private function descartarComprobante(?array $comprobante): void {
        $ruta = (string)($comprobante['ruta'] ?? '');
        if ($ruta === '' || !str_starts_with($ruta, 'uploads/comprobantes/') || str_contains($ruta, '..')) {
            return;
        }
        $abs = PUBLIC_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ruta);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    /**
     * Mensaje para el usuario cuando falla el guardado atómico de reservas.
     *
     * @param array<string, mixed> $res Resultado de Reserva::crearAtomico()
     */
    private function mensajeFalloReserva(array $res): string {
        return match ($res['codigo'] ?? 'error') {
            'ocupado' => 'Ese horario acaba de ser reservado, elige otro.',
            'cancha_no_disponible' => 'La cancha seleccionada no está disponible para alquiler.',
            default => 'Ocurrió un error inesperado al registrar la reserva. Intenta de nuevo.',
        };
    }

    /**
     * Vuelve al formulario de reserva (paso 2) conservando lo que el usuario ya escribió.
     * El mensaje de error ya quedó en el flash.
     *
     * @param array<string, mixed> $post
     */
    private function volverConDatos(int $canchaId, array $post): void {
        $durHoras = (float)($post['duracion_horas'] ?? $post['duracion'] ?? 1);
        $datos = [
            'cancha_id'      => $canchaId,
            'fecha'          => trim((string)($post['fecha'] ?? '')),
            'horario'        => trim((string)($post['hora_inicio'] ?? '')),
            'duracion'       => (int)round($durHoras * 60),
            'celular'        => trim((string)($post['contacto_telefono'] ?? '')),
            'observaciones'  => trim((string)($post['observaciones'] ?? '')),
            'nombre_cliente' => trim((string)($post['cliente_nombre'] ?? '')),
            'origen'         => trim((string)($post['origen'] ?? '')),
        ];
        $this->redirect('/reserva/crear/' . $canchaId . '?' . http_build_query(array_filter($datos, static fn($v) => $v !== '' && $v !== 0)));
    }

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
        $metodosValidos = $esAdmin ? ['yape', 'efectivo', 'niubiz'] : ['yape'];
        if (!in_array($metodoPago, $metodosValidos, true)) {
            sessionFlash('error', 'Método de pago no válido.', 'danger');
            $this->volverConDatos($canchaId, $post);
            return;
        }

        $esPresencialAdmin = $esAdmin && in_array($metodoPago, ['efectivo', 'niubiz'], true);
        $adelantoMonto = 20.00;
        $tipoCobroAdmin = trim($post['tipo_cobro'] ?? 'completo');

        // 0. Origen de la reserva (web, presencial, llamada, whatsapp)
        $origen = 'web';
        if ($esAdmin) {
            $origenInput = trim($post['origen'] ?? 'presencial');
            $origen = in_array($origenInput, ['presencial', 'llamada', 'whatsapp', 'web'], true) ? $origenInput : 'presencial';
        }

        // Validación de número de contacto (celular / WhatsApp) - obligatorio para ambas vistas
        if ($contactoTelefono === '') {
            sessionFlash('error', 'Ingresa un número de contacto (celular / WhatsApp) para la reserva.', 'danger');
            $this->volverConDatos($canchaId, $post);
            return;
        }
        if (!preg_match('/^[\d\s\+\-]{7,20}$/', $contactoTelefono)) {
            sessionFlash('error', 'El número de contacto no es válido. Usa 7 a 20 dígitos (puede incluir +, espacios o guiones).', 'danger');
            $this->volverConDatos($canchaId, $post);
            return;
        }
        // Normalizar con +51 si viene solo el número (diseño con etiqueta +51)
        $contactoTelefono = preg_replace('/\s+/', '', $contactoTelefono);
        if (!str_starts_with($contactoTelefono, '+')) {
            $contactoTelefono = '+51 ' . ltrim($contactoTelefono, '0');
        } else {
            $contactoTelefono = preg_replace('/^\+51\s*/', '+51 ', $contactoTelefono);
        }

        // Si es admin, vincular a cliente existente o auto-crear cliente real (nunca guardar al admin como cliente)
        if ($esAdmin) {
            if ($clienteNombre === '') {
                $clienteNombre = trim($post['cliente_nombre'] ?? '');
            }
            if ($clienteNombre === '') {
                sessionFlash('error', 'Como administrador debes ingresar el nombre del cliente.', 'danger');
                $this->volverConDatos($canchaId, $post);
                return;
            }

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
            // Para clientes presenciales, llamadas o WhatsApp sin cuenta web:
            // No creamos cuentas ficticias con contraseñas o correos generados.
            // La reserva mantiene usuario_id del operador/administrador responsable,
            // y almacena el nombre y teléfono reales del cliente en cliente_nombre y contacto_telefono.
        }

        // 1. Validaciones básicas de campos
        if ($canchaId <= 0 || empty($fecha) || empty($horaInicio) || $duracionHoras <= 0) {
            sessionFlash('error', 'Por favor completa todos los datos obligatorios para la reserva.', 'danger');
            $this->volverConDatos($canchaId, $post);
        }

        // Zona horaria provisional
        date_default_timezone_set('America/Lima');

        // 2. Validación de fecha (no permitir fechas anteriores al día de hoy)
        $hoy = date('Y-m-d');
        if ($fecha < $hoy) {
            sessionFlash('error', 'No es posible realizar reservas en fechas pasadas.', 'danger');
            $this->volverConDatos($canchaId, $post);
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
            $this->volverConDatos($canchaId, $post);
        }

        // 4b. Horario: paso de inicio, duración, horario del negocio y no pasado (servidor)
        $validacion = validarHorarioReserva($fecha, $horaInicio, $duracionHoras, null, $esAdmin);
        if (!$validacion['ok']) {
            sessionFlash('error', (string)$validacion['error'], 'danger');
            $this->volverConDatos($canchaId, $post);
        }
        $horaInicioNormalizada = $validacion['hora_inicio'];
        $horaFinNormalizada = $validacion['hora_fin'];

        // 5. Control estricto de disponibilidad (Solapamiento de horarios)
        $estaDisponible = $modeloCancha->estaDisponible($canchaId, $fecha, $horaInicioNormalizada, $horaFinNormalizada);
        if (!$estaDisponible) {
            sessionFlash('error', "La cancha '{$cancha['nombre']}' ya cuenta con una reserva activa entre las {$horaInicioNormalizada} y las {$horaFinNormalizada} en la fecha {$fecha}. Por favor elige otro horario o cancha.", 'warning');
            $this->volverConDatos($canchaId, $post);
        }

        // 6. Cálculo automático del monto total a pagar
        $precioHora = (float)$cancha['precio_hora'];
        $totalPago = round($validacion['minutos'] * ($precioHora / 60), 2); // minutos x precio por minuto

        // Validar duración en bloques de 30 min
        $duracionesValidas = [0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 6, 8];
        if (!in_array($duracionHoras, $duracionesValidas, true)) {
            // permitir cualquier múltiplo de 0.5 entre 0.5 y 8
            if ($duracionHoras < 0.5 || $duracionHoras > 8 || fmod($duracionHoras * 2, 1) !== 0.0) {
                sessionFlash('error', 'Duración no válida. Usa bloques de 30 min (0.5, 1, 1.5, 2...).', 'danger');
                $this->volverConDatos($canchaId, $post);
            }
        }

        // Si es admin quien reserva, se confirma directamente; si es cliente con comprobante por revisar, queda 'pendiente'
        $estadoInicial = isAdmin() ? 'confirmada' : 'pendiente';

        // Para admin presencial: pago completo o adelanto
        if ($esAdmin) {
            if ($tipoCobroAdmin === 'adelanto') {
                $adelantoInput = isset($post['adelanto_monto']) && is_numeric($post['adelanto_monto']) ? round((float)$post['adelanto_monto'], 2) : 20.00;
                $adelantoMonto = min($totalPago, max(0.0, $adelantoInput));
            } else {
                $adelantoMonto = $totalPago;
            }
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
                    $this->volverConDatos($canchaId, $post);
                }
                $maxSize = 5 * 1024 * 1024;
                if (($archivo['size'] ?? 0) <= 0 || ($archivo['size'] ?? 0) > $maxSize) {
                    sessionFlash('error', 'El comprobante supera el tamaño máximo de 5MB.', 'danger');
                    $this->volverConDatos($canchaId, $post);
                }
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($archivo['tmp_name']) ?: '';
                $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
                if (!isset($permitidos[$mime])) {
                    sessionFlash('error', 'Formato de comprobante no válido. Usa JPG, PNG o PDF.', 'danger');
                    $this->volverConDatos($canchaId, $post);
                }
                $dirDestino = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'comprobantes';
                if (!is_dir($dirDestino)) { mkdir($dirDestino, 0755, true); }
                $nombreSeguro = 'reserva_' . $usuarioId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $permitidos[$mime];
                $rutaAbsoluta = $dirDestino . DIRECTORY_SEPARATOR . $nombreSeguro;
                if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
                    sessionFlash('error', 'No se pudo guardar el comprobante. Intenta nuevamente.', 'danger');
                    $this->volverConDatos($canchaId, $post);
                }
                $comprobante = ['ruta' => 'uploads/comprobantes/' . $nombreSeguro, 'nombre' => (string)($archivo['name'] ?? $nombreSeguro), 'tipo' => $mime, 'size' => (int)$archivo['size']];
                // admin con comprobante queda verificado igualmente
                $pagoEstado = 'verificado';
            }
        } else {
        $archivo = $_FILES['comprobante'] ?? null;
        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            sessionFlash('error', 'Falta tu comprobante de pago. Súbelo para completar tu reserva.', 'danger');
            $this->volverConDatos($canchaId, $post);
        }
        if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            sessionFlash('error', 'Error al subir el comprobante. Intenta nuevamente.', 'danger');
            $this->volverConDatos($canchaId, $post);
        }

        $maxSize = 5 * 1024 * 1024;
        if (($archivo['size'] ?? 0) <= 0 || ($archivo['size'] ?? 0) > $maxSize) {
            sessionFlash('error', 'El comprobante supera el tamaño máximo de 5MB.', 'danger');
            $this->volverConDatos($canchaId, $post);
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
            $this->volverConDatos($canchaId, $post);
        }

        $dirDestino = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'comprobantes';
        if (!is_dir($dirDestino)) {
            mkdir($dirDestino, 0755, true);
        }

        $nombreSeguro = 'reserva_' . $usuarioId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $permitidos[$mime];
        $rutaAbsoluta = $dirDestino . DIRECTORY_SEPARATOR . $nombreSeguro;
        if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
            sessionFlash('error', 'No se pudo guardar el comprobante. Intenta nuevamente.', 'danger');
            $this->volverConDatos($canchaId, $post);
        }

        $comprobante = [
            'ruta'   => 'uploads/comprobantes/' . $nombreSeguro,
            'nombre' => (string)($archivo['name'] ?? $nombreSeguro),
            'tipo'   => $mime,
            'size'   => (int)$archivo['size'],
        ];
        $pagoEstado = 'en_revision';
        } // fin admin/cliente

        // 7. Guardar reserva de forma ATÓMICA (transacción + bloqueo de la cancha + nueva comprobación)
        $modeloReserva = new Reserva();
        $guardado = $modeloReserva->crearAtomico([[
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
            'origen'             => $origen,
            'adelanto_monto'     => $adelantoMonto,
            'cliente_nombre'     => $esAdmin ? $clienteNombre : null,
            'contacto_telefono'  => $contactoTelefono,
            'pago_estado'        => $pagoEstado,
            'comprobante_ruta'   => $comprobante['ruta'] ?? null,
            'comprobante_nombre' => $comprobante['nombre'] ?? null,
            'comprobante_tipo'   => $comprobante['tipo'] ?? null,
            'comprobante_size'   => $comprobante['size'] ?? null,
            'comprobante_subido_at' => !empty($comprobante['ruta'] ?? null) ? date('Y-m-d H:i:s') : null,
        ]]);
        if (!$guardado['ok']) {
            $this->descartarComprobante($comprobante ?? null); // si se subió un comprobante, se elimina
            sessionFlash('error', $this->mensajeFalloReserva($guardado), $guardado['codigo'] === 'ocupado' ? 'warning' : 'danger');
            $this->volverConDatos($canchaId, $post);
        }
        $idReserva = (int)$guardado['ids'][0];

        if ($idReserva > 0) {
            // Notificar por Telegram (no bloquea el flujo si falla)
            $nombreTelegram = $esAdmin && $clienteNombre !== '' ? $clienteNombre : ($_SESSION['usuario_nombre'] ?? '');
            $metodoLblTelegram = match ($metodoPago) {
                'efectivo' => 'Efectivo (presencial, pago completo)',
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
            $this->volverConDatos($canchaId, $post);
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
        $filtroAsistencia = trim($_GET['asistencia'] ?? '');
        $busqueda = trim($_GET['q'] ?? '');
        // Filtros rápidos: hoy | confirmadas | pendientes | todas ("Por verificar" vive en Caja)
        $vista = trim($_GET['vista'] ?? 'todas');
        if (!in_array($vista, ['hoy', 'confirmadas', 'pendientes', 'todas'], true)) {
            $vista = 'todas';
        }
        $hoyStr = date('Y-m-d');

        $estadosValidos = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];
        $pagosValidos = ['pendiente', 'en_revision', 'verificado', 'rechazado'];
        $asistenciasValidas = ['sin_registrar', 'presente', 'tardanza', 'retraso_confirmado', 'no_asistio'];
        if ($filtroEstado !== '' && !in_array($filtroEstado, $estadosValidos, true)) {
            $filtroEstado = '';
        }
        if ($filtroPago !== '' && !in_array($filtroPago, $pagosValidos, true)) {
            $filtroPago = '';
        }
        if ($filtroAsistencia !== '' && !in_array($filtroAsistencia, $asistenciasValidas, true)) {
            $filtroAsistencia = '';
        }

        $modeloReserva = new Reserva();
        $todas = $modeloReserva->obtenerTodas();

        // Contadores de los botones (un evento de varias canchas cuenta una vez)
        $conteos = ['hoy' => 0, 'verificar' => 0, 'confirmadas' => 0, 'pendientes' => 0, 'todas' => 0];
        $vistos = [];
        foreach ($todas as $r) {
            $k = !empty($r['evento_grupo']) ? 'evt_' . $r['evento_grupo'] : 'res_' . $r['id'];
            if (isset($vistos[$k])) {
                continue;
            }
            $vistos[$k] = true;
            $conteos['todas']++;
            if ($r['estado'] === 'cancelada') {
                continue;
            }
            if ($r['fecha'] === $hoyStr) { $conteos['hoy']++; }
            if ($r['estado'] === 'confirmada') { $conteos['confirmadas']++; }
            if ($r['estado'] === 'pendiente') { $conteos['pendientes']++; }
            if (pagoEsPorVerificar($r) && empty($r['es_demo'])) { $conteos['verificar']++; } // igual que la lista de Caja (sin demo)
        }

        $reservas = array_values(array_filter($todas, function ($r) use ($filtroEstado, $filtroPago, $filtroAsistencia, $busqueda, $vista, $hoyStr) {
            if ($vista === 'hoy' && !($r['fecha'] === $hoyStr && $r['estado'] !== 'cancelada')) {
                return false;
            }
            if ($vista === 'confirmadas' && $r['estado'] !== 'confirmada') {
                return false;
            }
            if ($vista === 'pendientes' && $r['estado'] !== 'pendiente') {
                return false;
            }
            if ($filtroEstado !== '' && $r['estado'] !== $filtroEstado) {
                return false;
            }
            if ($filtroPago !== '' && ($r['pago_estado'] ?? 'pendiente') !== $filtroPago) {
                return false;
            }
            if ($filtroAsistencia !== '' && ($r['asistencia_estado'] ?? 'sin_registrar') !== $filtroAsistencia) {
                return false;
            }
            if ($busqueda !== '') {
                $q = mb_strtolower($busqueda);
                $texto = mb_strtolower('#' . $r['id'] . ' ' . ($r['usuario_nombre'] ?? '') . ' ' . ($r['usuario_email'] ?? '') . ' ' . ($r['cancha_nombre'] ?? '') . ' ' . ($r['contacto_telefono'] ?? ''));
                if (!str_contains($texto, $q)) {
                    return false;
                }
            }
            return true;
        }));

        $this->view('reservas/admin', [
            'titulo'           => 'Reservas - Panel Administrador',
            'adminMenu'        => 'reservas',
            'vista'            => $vista,
            'conteos'          => $conteos,
            'reservas'         => $reservas,
            'estadisticas'     => $modeloReserva->obtenerEstadisticas(),
            'filtroEstado'     => $filtroEstado,
            'filtroPago'       => $filtroPago,
            'filtroAsistencia' => $filtroAsistencia,
            'busqueda'         => $busqueda,
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

        $grupo = $_POST['evento_grupo'] ?? null;
        if (!empty($grupo)) {
            $ok = $modeloReserva->actualizarEstadoPagoGrupo($grupo, $nuevoEstado, (int)$_SESSION['usuario_id']);
            if ($nuevoEstado === 'verificado') {
                $modeloReserva->actualizarEstadoGrupo($grupo, 'confirmada');
            } elseif ($nuevoEstado === 'rechazado') {
                $modeloReserva->actualizarEstadoGrupo($grupo, 'cancelada');
            }
        } else {
            $ok = $modeloReserva->actualizarEstadoPago($idReserva, $nuevoEstado, (int)$_SESSION['usuario_id']);
            if ($nuevoEstado === 'verificado') {
                $modeloReserva->actualizarEstado($idReserva, 'confirmada');
            } elseif ($nuevoEstado === 'rechazado') {
                $modeloReserva->actualizarEstado($idReserva, 'cancelada');
            }
        }
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
     * La pantalla de edición se retiró: todo se hace desde el detalle de la reserva (Solo Admin).
     * La ruta se conserva y redirige al detalle con un aviso.
     */
    public function adminEdit(string|int $id): void {
        $this->requireAdmin();
        $idReserva = (int)$id;
        sessionFlash('error', 'La edición se hace desde el detalle de la reserva: asistencia, reprogramar y cancelar están ahí.', 'info');
        $this->redirect($idReserva > 0 ? '/mis-reservas/' . $idReserva : '/admin/reservas');
    }

    /**
     * Procesa la actualización de una reserva (Solo Admin)
     * Valida disponibilidad y previene cruces de horario.
     */
    public function adminUpdate(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();
        $idReserva = (int)$id;

        $modeloReserva = new Reserva();
        $reserva = $modeloReserva->obtenerPorId($idReserva);
        if (!$reserva) {
            sessionFlash('error', 'La reserva no existe.', 'warning');
            $this->redirect('/admin/reservas');
            return;
        }

        $post = $this->sanitizePost();
        $canchaId = (int)($post['cancha_id'] ?? $reserva['cancha_id']);
        $fecha = trim($post['fecha'] ?? $reserva['fecha']);
        $horaInicio = trim($post['hora_inicio'] ?? $reserva['hora_inicio']);
        $duracionHoras = (float)($post['duracion_horas'] ?? $reserva['duracion_horas']);
        // Campos que ESTA ruta nunca cambia (se ignoran aunque lleguen en el POST): estado, origen, nombre y teléfono
        // del cliente y monto del adelanto. El estado cambia con verificar/rechazar (Caja), cancelar o reprogramar;
        // los datos del cliente y los montos no se corrigen desde aquí.
        $estado = (string)$reserva['estado'];
        $origen = (string)($reserva['origen'] ?? 'web');
        $clienteNombre = (string)($reserva['cliente_nombre'] ?? '');
        $contactoTelefono = (string)($reserva['contacto_telefono'] ?? '');
        $observaciones = trim($post['observaciones'] ?? (string)($reserva['observaciones'] ?? ''));
        $adelantoMonto = (float)$reserva['adelanto_monto'];

        if ($canchaId <= 0 || empty($fecha) || empty($horaInicio) || $duracionHoras <= 0) {
            sessionFlash('error', 'Completa todos los campos obligatorios para guardar la reserva.', 'danger');
            $this->redirect("/mis-reservas/{$idReserva}");
            return;
        }

        $timestampInicio = strtotime("{$fecha} {$horaInicio}");
        if ($timestampInicio === false) {
            sessionFlash('error', 'Formato de hora de inicio inválido.', 'danger');
            $this->redirect("/mis-reservas/{$idReserva}");
            return;
        }

        $horaInicioNormalizada = date('H:i:s', $timestampInicio);
        $timestampFin = $timestampInicio + (int)round($duracionHoras * 3600);
        $horaFinNormalizada = date('H:i:s', $timestampFin);

        // Si se cambia la fecha o la hora, el NUEVO horario debe ser válido (no pasado, :00/:30, horario de atención).
        // Si no se cambia, la reserva (aunque sea antigua o de minutos distintos) se puede seguir editando.
        $cambioHorario = ($fecha !== (string)$reserva['fecha'] || substr($horaInicioNormalizada, 0, 5) !== substr((string)$reserva['hora_inicio'], 0, 5));
        if ($cambioHorario) {
            $valHorario = validarHorarioReserva($fecha, $horaInicioNormalizada, $duracionHoras);
            if (!$valHorario['ok']) {
                sessionFlash('error', (string)$valHorario['error'], 'danger');
                $this->redirect("/mis-reservas/{$idReserva}");
                return;
            }
        }

        $modeloCancha = new Cancha();
        $cancha = $modeloCancha->obtenerPorId($canchaId);
        if (!$cancha) {
            sessionFlash('error', 'La cancha indicada no existe.', 'danger');
            $this->redirect("/mis-reservas/{$idReserva}");
            return;
        }

        if ($cancha['estado'] === 'mantenimiento' && in_array($estado, ['pendiente', 'confirmada'], true)) {
            sessionFlash('error', "La cancha '{$cancha['nombre']}' se encuentra en mantenimiento y no admite reservas activas.", 'danger');
            $this->redirect("/mis-reservas/{$idReserva}");
            return;
        }

        // Control de disponibilidad (excluyendo la misma reserva que se edita)
        if (in_array($estado, ['pendiente', 'confirmada'], true)) {
            $disponible = $modeloCancha->estaDisponible($canchaId, $fecha, $horaInicioNormalizada, $horaFinNormalizada, $idReserva);
            if (!$disponible) {
                sessionFlash('error', "No se puede guardar: La cancha '{$cancha['nombre']}' ya cuenta con una reserva activa entre las {$horaInicioNormalizada} y las {$horaFinNormalizada} en la fecha {$fecha}.", 'danger');
                $this->redirect("/mis-reservas/{$idReserva}");
                return;
            }
        }

        $precioHora = (float)$cancha['precio_hora'];
        // El total solo se recalcula si cambia la cancha o la duración (guardar una nota no toca el monto)
        $mismoPlan = ($canchaId === (int)$reserva['cancha_id'] && abs($duracionHoras - (float)$reserva['duracion_horas']) < 0.001);
        $totalPago = $mismoPlan ? (float)$reserva['total_pago'] : $duracionHoras * $precioHora;

        $resAt = $modeloReserva->actualizarReservaAtomica($idReserva, [
            'cancha_id'         => $canchaId,
            'fecha'             => $fecha,
            'hora_inicio'       => $horaInicioNormalizada,
            'hora_fin'          => $horaFinNormalizada,
            'duracion_horas'    => $duracionHoras,
            'total_pago'        => $totalPago,
            'estado'            => $estado,
            'observaciones'     => $observaciones,
            'origen'            => $origen,
            'cliente_nombre'    => $clienteNombre,
            'contacto_telefono' => $contactoTelefono,
            'adelanto_monto'    => $adelantoMonto,
        ], in_array($estado, ['pendiente', 'confirmada'], true));
        $ok = $resAt['ok'];
        if (!$ok && $resAt['codigo'] === 'ocupado') {
            sessionFlash('error', 'Ese horario acaba de ser reservado, elige otro.', 'warning');
            $this->redirect("/mis-reservas/{$idReserva}");
            return;
        }

        if ($ok) {
            sessionFlash('success', "Reserva #{$idReserva} actualizada correctamente.", 'success');
            $this->redirect("/mis-reservas/{$idReserva}");
        } else {
            sessionFlash('error', 'No se pudo guardar la modificación de la reserva.', 'danger');
            $this->redirect("/mis-reservas/{$idReserva}");
        }
    }

    /**
     * Calendario administrativo con vista diaria (las 6 canchas) y semanal.
     */
    public function calendario(): void {
        $this->requireAdmin();

        $vista = trim($_GET['vista'] ?? 'dia');
        if (!in_array($vista, ['dia', 'semana'], true)) {
            $vista = 'dia';
        }

        $ref = trim($_GET['fecha'] ?? $_GET['semana'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ref)) {
            $ref = date('Y-m-d');
        }

        $filtroCancha = (int)($_GET['cancha'] ?? 0);

        $modeloCancha = new Cancha();
        $canchas = $modeloCancha->obtenerTodas();

        $modeloReserva = new Reserva();
        $todas = $modeloReserva->obtenerTodas();

        $horas = [];
        for ($h = 7; $h <= 23; $h++) {
            foreach (['00', '30'] as $mm) {
                $horas[] = sprintf('%02d:%s', $h, $mm);
            }
        }

        $hoy = date('Y-m-d');
        $horaActual = date('H:i:s');

        if ($vista === 'dia') {
            $fechaDia = $ref;
            $reservasDia = array_values(array_filter($todas, static fn($r) => $r['fecha'] === $fechaDia && $r['estado'] !== 'cancelada'));

            $grillaDia = [];
            foreach ($canchas as $c) {
                $cid = (int)$c['id'];
                foreach ($horas as $slot) {
                    $ini = $slot . ':00';
                    $fin = date('H:i:s', strtotime($slot . ' +30 minutes'));
                    if ($fin === '00:00:00') $fin = '24:00:00';

                    $celda = [];
                    foreach ($reservasDia as $r) {
                        if ((int)$r['cancha_id'] !== $cid) continue;
                        $rFin = $r['hora_fin'] === '00:00:00' ? '24:00:00' : $r['hora_fin'];
                        if ($r['hora_inicio'] < $fin && $rFin > $ini) {
                            $celda[] = $r;
                        }
                    }
                    $grillaDia[$cid][$slot] = $celda;
                }
            }

            $resumenCanchas = [];
            foreach ($canchas as $c) {
                $cid = (int)$c['id'];
                $enMantenimiento = ($c['estado'] === 'mantenimiento');
                $reservasCancha = array_values(array_filter($reservasDia, static fn($r) => (int)$r['cancha_id'] === $cid));

                $reservaEnCurso = null;
                $proximaReserva = null;
                if ($fechaDia === $hoy) {
                    foreach ($reservasCancha as $r) {
                        $rFin = $r['hora_fin'] === '00:00:00' ? '24:00:00' : $r['hora_fin'];
                        if ($r['hora_inicio'] <= $horaActual && $rFin > $horaActual) {
                            $reservaEnCurso = $r;
                            break;
                        } elseif ($r['hora_inicio'] > $horaActual && ($proximaReserva === null || $r['hora_inicio'] < $proximaReserva['hora_inicio'])) {
                            $proximaReserva = $r;
                        }
                    }
                }

                $resumenCanchas[$cid] = [
                    'cancha'            => $c,
                    'en_mantenimiento'  => $enMantenimiento,
                    'total_reservas'    => count($reservasCancha),
                    'reserva_en_curso'  => $reservaEnCurso,
                    'proxima_reserva'   => $proximaReserva,
                ];
            }

            $modeloEspacio = new \App\Models\Espacio();
            $solicitudesEspacio = $modeloEspacio->obtenerActivasEntre($fechaDia, $fechaDia);

            $this->view('reservas/calendario', [
                'titulo'         => 'Calendario de Canchas - Panel Administrador',
                'solicitudesEspacio' => $solicitudesEspacio,
                'canchasPorSolicitud' => $modeloEspacio->obtenerCanchasPorSolicitudes(array_column($solicitudesEspacio, 'id')),
                'adminMenu'      => 'calendario',
                'vista'          => 'dia',
                'fechaDia'       => $fechaDia,
                'horas'          => $horas,
                'canchas'        => $canchas,
                'grillaDia'      => $grillaDia,
                'resumenCanchas' => $resumenCanchas,
                'hoy'            => $hoy,
                'horaActual'     => $horaActual,
            ], false);

        } else {
            // Vista Semanal
            $lunes = date('Y-m-d', strtotime($ref . ' -' . (date('N', strtotime($ref)) - 1) . ' days'));
            $dias = [];
            for ($i = 0; $i < 7; $i++) {
                $dias[] = date('Y-m-d', strtotime($lunes . " +{$i} days"));
            }

            $semana = array_values(array_filter($todas, static function ($r) use ($dias, $filtroCancha) {
                if (!in_array($r['fecha'], $dias, true)) return false;
                if ($r['estado'] === 'cancelada') return false;
                if ($filtroCancha > 0 && (int)$r['cancha_id'] !== $filtroCancha) return false;
                return true;
            }));

            $grilla = [];
            foreach ($dias as $d) {
                foreach ($horas as $slot) {
                    $ini = $slot . ':00';
                    $fin = date('H:i:s', strtotime($slot . ' +30 minutes'));
                    if ($fin === '00:00:00') $fin = '24:00:00';
                    $celda = [];
                    foreach ($semana as $r) {
                        if ($r['fecha'] !== $d) continue;
                        $rFin = $r['hora_fin'] === '00:00:00' ? '24:00:00' : $r['hora_fin'];
                        if ($r['hora_inicio'] < $fin && $rFin > $ini) {
                            $celda[] = $r;
                        }
                    }
                    usort($celda, static fn($a, $b) => strcmp($a['hora_inicio'], $b['hora_inicio']));
                    $grilla[$d][$slot] = $celda;
                }
            }

            $modeloEspacio = new \App\Models\Espacio();
            $solicitudesEspacio = $modeloEspacio->obtenerActivasEntre($dias[0], $dias[6]);

            $this->view('reservas/calendario', [
                'titulo'       => 'Calendario de reservas - Panel Administrador',
                'solicitudesEspacio' => $solicitudesEspacio,
                'canchasPorSolicitud' => $modeloEspacio->obtenerCanchasPorSolicitudes(array_column($solicitudesEspacio, 'id')),
                'adminMenu'    => 'calendario',
                'vista'        => 'semana',
                'dias'         => $dias,
                'lunes'        => $lunes,
                'semanaRef'    => $ref,
                'horas'        => $horas,
                'grilla'       => $grilla,
                'canchas'      => $canchas,
                'filtroCancha' => $filtroCancha,
                'hoy'          => $hoy,
                'horaActual'   => $horaActual,
            ], false);
        }
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
        $metodosValidos = $esAdmin ? ['yape', 'efectivo', 'niubiz'] : ['yape'];
        if (!in_array($metodoPago, $metodosValidos, true)) {
            sessionFlash('error', 'Método de pago no válido.', 'danger');
            $this->redirect('/evento/crear');
            return;
        }

        if (!$esAdmin && empty($post['acepta_reglas'])) {
            sessionFlash('error', 'Debes aceptar las reglas para reservar un evento.', 'danger');
            $this->redirect('/evento');
            return;
        }

        $esPresencialAdmin = $esAdmin && in_array($metodoPago, ['efectivo', 'niubiz'], true);
        $tipoCobroAdmin = trim($post['tipo_cobro'] ?? 'completo');
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
        if (count($canchasIds) > 6) {
            sessionFlash('error', 'Máximo 6 canchas por evento.', 'danger');
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
        $esPagoCompletoAdmin = $esPresencialAdmin && ($tipoCobroAdmin === 'completo');
        $totalGeneral = 0;
        $items = [];
        $tsIniBase = strtotime("{$fecha} {$hora}");
        if ($tsIniBase === false) {
            sessionFlash('error', 'Hora del evento inválida.', 'danger');
            $this->redirect('/evento');
            return;
        }

        $validacion = validarHorarioReserva($fecha, $hora, $dur, null, $esAdmin);
        if (!$validacion['ok']) {
            sessionFlash('error', (string)$validacion['error'], 'danger');
            $_SESSION['old_evento'] = [
                'fecha' => $fecha, 'hora' => $hora, 'duracion' => (string)$dur, 'canchas_ids' => $canchasIds,
                'contacto_telefono' => (string)($post['contacto_telefono'] ?? ''), 'observaciones' => $observaciones, 'cliente_nombre' => (string)($post['cliente_nombre'] ?? ''),
            ];
            $this->redirect('/evento');
            return;
        }
        $tsFin = $tsIniBase + (int)round($dur * 3600);

        $horaIni = date('H:i:s', $tsIniBase);
        $horaFin = date('H:i:s', $tsFin);
        foreach ($canchasIds as $cid) {
            $cancha = $modeloCancha->obtenerPorId($cid);
            if (!$cancha || $cancha['estado'] !== 'disponible') {
                sessionFlash('error', "La cancha #{$cid} no está disponible.", 'danger');
                $this->redirect('/evento');
                return;
            }
            if (!$modeloCancha->estaDisponible($cid, $fecha, $horaIni, $horaFin)) {
                sessionFlash('error', "La {$cancha['nombre']} ya está ocupada el {$fecha} de {$horaIni} a {$horaFin}.", 'warning');
                $this->redirect('/evento');
                return;
            }
            $precio = (float)$cancha['precio_hora'];
            $total = round($validacion['minutos'] * ($precio / 60), 2);
            $adelanto = $esPagoCompletoAdmin ? $total : 20.00;
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

        $adelantoTotal = $esPagoCompletoAdmin ? $totalGeneral : (count($items) * 20.00);

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
        $lista = [];
        foreach ($items as $it) {
            $lista[] = [
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
            ];
        }
        // Todo o nada: todas las canchas se reservan en una sola transacción (con bloqueo ordenado)
        $guardado = $modeloReserva->crearAtomico($lista);
        if (!$guardado['ok']) {
            $this->descartarComprobante($comprobante); // si se subió un comprobante, se elimina
            $cn = ($guardado['cancha_id'] ?? 0) > 0 ? $modeloCancha->obtenerPorId((int)$guardado['cancha_id']) : null;
            $msg = $this->mensajeFalloReserva($guardado);
            if ($guardado['codigo'] === 'ocupado' && $cn) { $msg .= ' (' . $cn['nombre'] . ' ya está ocupada; no se reservó ninguna cancha del evento.)'; }
            sessionFlash('error', $msg, $guardado['codigo'] === 'ocupado' ? 'warning' : 'danger');
            $_SESSION['old_evento'] = [
                'fecha' => $fecha, 'hora' => $hora, 'duracion' => (string)$dur, 'canchas_ids' => $canchasIds,
                'contacto_telefono' => (string)($post['contacto_telefono'] ?? ''), 'observaciones' => $observaciones, 'cliente_nombre' => (string)($post['cliente_nombre'] ?? ''),
            ];
            $this->redirect('/evento');
        }
        $creados = $guardado['ids'];

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
            'metodo_pago' => $metodoPago === 'efectivo' ? 'Efectivo (presencial, pago completo)' : 'Yape',
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

        // Administrador: aplicar la regla de cierre (asistió + saldo en cero + ya terminó => finalizada)
        if (isAdmin() && $modeloReserva->sincronizarFinalizacion($idReserva)) {
            $reserva = $modeloReserva->obtenerPorId($idReserva) ?? $reserva;
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
        // Solo por POST con token CSRF: abrir el enlace antiguo (GET) no cancela nada
        $this->soloPost(isAdmin() ? '/reservas' : '/mis-reservas', 'Para cancelar una reserva usa el botón "Cancelar" y confirma: abrir el enlace ya no ejecuta la acción.');
        $this->validateCsrf();
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

        // El cliente solo cancela reservas de hoy en adelante (la misma regla que ya aplicaba la pantalla)
        if (!isAdmin() && (string)$reserva['fecha'] < date('Y-m-d')) {
            sessionFlash('error', 'No se puede cancelar una reserva de una fecha pasada.', 'warning');
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

    protected function isAjax(): bool {
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
        $grupo = $_POST['evento_grupo'] ?? null;
        if (!empty($grupo)) {
            $okEstado = $modeloReserva->actualizarEstadoGrupo($grupo, $nuevoEstado);
        } else {
            $okEstado = $modeloReserva->actualizarEstado($idReserva, $nuevoEstado);
        }
        if (!$okEstado && $modeloReserva->ultimoCodigo === 'ocupado') {
            if ($this->isAjax()) { $this->json(['ok'=>false,'error'=>'Ese horario ya fue tomado por otra reserva.'], 409); }
            sessionFlash('error', 'No se puede reactivar: ese horario ya fue tomado por otra reserva.', 'warning');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/reservas');
        }
        // Al cancelar desde el detalle se guarda el motivo (en la nota de la reserva)
        $motivoCancelacion = trim((string)($_POST['motivo'] ?? ''));
        if ($nuevoEstado === 'cancelada' && $motivoCancelacion !== '') {
            $modeloReserva->guardarMotivoCancelacion($idReserva, !empty($grupo) ? (string)$grupo : null, $motivoCancelacion, (int)($_SESSION['usuario_id'] ?? 0));
        }

        if ($this->isAjax()) {
            $this->json(['ok'=>true,'id'=>$idReserva,'estado'=>$nuevoEstado,'msg'=>"Estado actualizado a '{$nuevoEstado}'"]);
        }
        sessionFlash('success', "El estado de la reserva #{$idReserva} fue actualizado a '{$nuevoEstado}'.", 'success');
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/reservas');
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

    /**
     * Registra el estado de asistencia y puntualidad de una reserva (Solo Admin)
     */
    public function registrarAsistencia(string|int $id): void {
        $this->requireAdmin();

        if (!validateCsrf()) {
            if ($this->isAjax()) {
                $this->json(['ok' => false, 'error' => 'Token CSRF inválido o expirado.'], 403);
            }
            sessionFlash('error', 'Token de seguridad inválido. Recarga la página e intenta nuevamente.', 'danger');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/reservas');
        }

        $reservaId = (int)$id;
        $asistenciaEstado = trim((string)($_POST['asistencia_estado'] ?? 'sin_registrar'));
        $asistenciaHora = !empty($_POST['asistencia_hora']) ? trim((string)$_POST['asistencia_hora']) : null;
        $asistenciaNotas = !empty($_POST['asistencia_notas']) ? trim((string)$_POST['asistencia_notas']) : null;
        $liberarCancha = !empty($_POST['liberar_cancha']) && in_array($_POST['liberar_cancha'], ['1', 'true', 'on'], true);

        $admin = currentUser();
        $adminId = $admin ? (int)$admin['id'] : null;

        $modeloReserva = new Reserva();
        $res = $modeloReserva->registrarAsistencia(
            $reservaId,
            $asistenciaEstado,
            $asistenciaHora,
            $asistenciaNotas,
            $adminId,
            $liberarCancha
        );

        if (!$res['ok']) {
            if ($this->isAjax()) {
                $this->json($res, 400);
            }
            sessionFlash('error', $res['error'] ?? 'Error al registrar la asistencia.', 'danger');
        } else {
            // Regla de cierre: asistió + saldo en cero + ya pasó la hora de fin => finalizada
            $modeloReserva->sincronizarFinalizacion($reservaId);
            if ($this->isAjax()) {
                $this->json($res);
            }
            sessionFlash('mensaje', $res['mensaje'] ?? 'Asistencia actualizada correctamente.', 'success');
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/reservas');
    }

    /**
     * Procesa la reprogramación de una reserva cumpliendo la política de anticipación (Solo Admin)
     */
    public function reprogramar(string|int $id): void {
        $this->requireAdmin();

        if (!validateCsrf()) {
            if ($this->isAjax()) {
                $this->json(['ok' => false, 'error' => 'Token CSRF inválido o expirado.'], 403);
            }
            sessionFlash('error', 'Token de seguridad inválido.', 'danger');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/reservas');
        }

        $reservaId = (int)$id;
        $nuevaCanchaId = (int)($_POST['cancha_id'] ?? 0);
        $nuevaFecha = trim((string)($_POST['fecha'] ?? ''));
        $nuevaHoraInicio = trim((string)($_POST['hora_inicio'] ?? ''));
        $duracionHoras = (float)($_POST['duracion_horas'] ?? 1.0);
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        $forzarFueraDePlazo = !empty($_POST['forzar_fuera_de_plazo']) && in_array($_POST['forzar_fuera_de_plazo'], ['1', 'true', 'on'], true);

        if ($nuevaCanchaId <= 0 || empty($nuevaFecha) || empty($nuevaHoraInicio) || $duracionHoras <= 0) {
            $err = 'Datos incompletos para reprogramar la reserva.';
            if ($this->isAjax()) {
                $this->json(['ok' => false, 'error' => $err], 400);
            }
            sessionFlash('error', $err, 'danger');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/mis-reservas/' . $reservaId);
        }

        $admin = currentUser();
        $adminId = $admin ? (int)$admin['id'] : 1;

        $modeloReserva = new Reserva();
        $res = $modeloReserva->reprogramar(
            $reservaId,
            $nuevaCanchaId,
            $nuevaFecha,
            $nuevaHoraInicio,
            $duracionHoras,
            $motivo,
            $adminId,
            $forzarFueraDePlazo
        );

        if (!$res['ok']) {
            if ($this->isAjax()) {
                $this->json($res, 400);
            }
            sessionFlash('error', $res['error'] ?? 'Error al reprogramar la reserva.', 'danger');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/mis-reservas/' . $reservaId);
        }

        if ($this->isAjax()) {
            $this->json($res);
        }
        sessionFlash('mensaje', 'Reserva reprogramada exitosamente. Verifique los nuevos detalles.', 'success');
        $this->redirect('/mis-reservas/' . $reservaId);
    }

    /**
     * Registra el resultado del contacto previo al cliente (~30 min o seguimiento)
     */
    public function registrarContacto(string|int $id): void {
        $this->requireAdmin();

        if (!validateCsrf()) {
            if ($this->isAjax()) {
                $this->json(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
            }
            sessionFlash('error', 'Token de seguridad inválido.', 'danger');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/reservas');
        }

        $reservaId = (int)$id;
        $resultado = trim((string)($_POST['contacto_resultado'] ?? 'contacto_realizado'));

        $admin = currentUser();
        $adminId = $admin ? (int)$admin['id'] : null;

        $modeloReserva = new Reserva();
        $res = $modeloReserva->registrarContacto($reservaId, $resultado, $adminId);

        if ($this->isAjax()) {
            $this->json($res);
        }
        sessionFlash('mensaje', 'Contacto registrado en el historial de la reserva.', 'success');
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/reservas');
    }
}
