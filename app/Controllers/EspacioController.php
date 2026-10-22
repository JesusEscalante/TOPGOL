<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cancha;
use App\Models\Espacio;
use App\Models\Notificacion;
use App\Services\TelegramNotifier;

class EspacioController extends Controller {
    public function solicitar(): void {
        $this->requireAuth();
        $old = $_SESSION['old_espacio'] ?? [];
        unset($_SESSION['old_espacio']);
        $this->view('espacios/solicitar', [
            'titulo'  => 'Solicitar Cumpleaños/Reunión - TOP GOL',
            'canchas' => (new Cancha())->obtenerDisponibles(),
            'old'     => is_array($old) ? $old : [],
        ]);
    }

    /**
     * Vuelve al formulario guardando lo que el usuario ya escribió (el mensaje ya está en el flash).
     */
    private function volverSolicitud(): void {
        $_SESSION['old_espacio'] = [
            'fecha'             => (string)($_POST['fecha'] ?? ''),
            'hora'              => (string)($_POST['hora'] ?? ''),
            'duracion'          => (string)($_POST['duracion'] ?? ''),
            'tipo_celebracion'  => (string)($_POST['tipo_celebracion'] ?? ''),
            'contacto_nombre'   => (string)($_POST['contacto_nombre'] ?? ''),
            'contacto_telefono' => (string)($_POST['contacto_telefono'] ?? ''),
            'observaciones'     => (string)($_POST['observaciones'] ?? ''),
            'canchas_ids'       => array_map('intval', (array)($_POST['canchas_ids'] ?? [])),
        ];
        $this->redirect('/cumpleanos/solicitar');
    }

    public function storeSolicitud(): void {
        $this->requireAuth();
        $this->validateCsrf();

        $fecha = trim((string)($_POST['fecha'] ?? ''));
        $hora = trim((string)($_POST['hora'] ?? ''));
        $duracion = (float)($_POST['duracion'] ?? 1);
        $nombre = trim((string)($_POST['contacto_nombre'] ?? ''));
        $telefono = trim((string)($_POST['contacto_telefono'] ?? ''));
        $tipo_celebracion = trim((string)($_POST['tipo_celebracion'] ?? 'No especificado'));
        $observaciones = trim((string)($_POST['observaciones'] ?? ''));

        $observaciones_completas = "[Tipo: $tipo_celebracion]";
        if (!empty($observaciones)) {
            $observaciones_completas .= " - " . $observaciones;
        }

        if ($fecha === '' || $hora === '' || $nombre === '' || $telefono === '') {
            sessionFlash('error', 'Faltan campos obligatorios.', 'danger');
            $this->volverSolicitud();
            return;
        }
        if (!preg_match('/^[\d\s\+\-]{7,20}$/', $telefono)) {
            sessionFlash('error', 'El número de contacto no es válido. Usa 7 a 20 dígitos (puede incluir +, espacios o guiones).', 'danger');
            $this->volverSolicitud();
            return;
        }
        $telefono = preg_replace('/\s+/', '', $telefono);
        if (!str_starts_with($telefono, '+')) {
            $telefono = '+51 ' . ltrim($telefono, '0');
        } else {
            $telefono = preg_replace('/^\+51\s*/', '+51 ', $telefono);
        }

        // Horario (paso de inicio, duración, horario del negocio, no pasado) validado en el servidor
        $validacion = validarHorarioReserva($fecha, $hora, $duracion);
        if (!$validacion['ok']) {
            sessionFlash('error', (string)$validacion['error'], 'danger');
            $this->volverSolicitud();
            return;
        }
        $horaIni = $validacion['hora_inicio'];
        $horaFin = $validacion['hora_fin'];
        $minutos = $validacion['minutos'];

        // El espacio no puede cruzarse con otra solicitud confirmada
        $modelo = new Espacio();
        if (!$modelo->estaDisponible(1, $fecha, $horaIni, $duracion)) {
            sessionFlash('error', "El espacio ya está ocupado el {$fecha} entre las " . substr($horaIni, 0, 5) . ' y las ' . substr($horaFin, 0, 5) . '. Elige otro horario.', 'warning');
            $this->volverSolicitud();
            return;
        }

        // Canchas opcionales (0, 1 o varias): mismo intervalo, validadas en el servidor
        $canchasIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['canchas_ids'] ?? [])), static fn($v) => $v > 0)));
        if (count($canchasIds) > 6) {
            sessionFlash('error', 'Máximo 6 canchas.', 'danger');
            $this->volverSolicitud();
            return;
        }
        $modeloCancha = new Cancha();
        $preciosCancha = [];
        $nombresCancha = [];
        foreach ($canchasIds as $cid) {
            $cancha = $modeloCancha->obtenerPorId($cid);
            if (!$cancha || $cancha['estado'] !== 'disponible') {
                sessionFlash('error', "La cancha #{$cid} no está disponible.", 'danger');
                $this->volverSolicitud();
                return;
            }
            if (!$modeloCancha->estaDisponible($cid, $fecha, $horaIni, $horaFin)) {
                sessionFlash('error', "La {$cancha['nombre']} ya está ocupada el {$fecha} entre las " . substr($horaIni, 0, 5) . ' y las ' . substr($horaFin, 0, 5) . '.', 'warning');
                $this->volverSolicitud();
                return;
            }
            $preciosCancha[$cid] = (float)$cancha['precio_hora'];
            $nombresCancha[$cid] = (string)$cancha['nombre'];
        }

        // Total estimado (siempre calculado en el servidor)
        $calculo = calcularTotalEspacio($minutos, $preciosCancha);
        $canchasGuardar = [];
        foreach ($preciosCancha as $cid => $precio) {
            $canchasGuardar[] = ['cancha_id' => $cid, 'precio_hora' => $precio, 'subtotal' => $calculo['canchas'][$cid]];
        }

        $guardado = $modelo->guardarSolicitudAtomica([
            'espacio_id' => 1,
            'usuario_id' => (int)$_SESSION['usuario_id'],
            'fecha' => $fecha,
            'hora_inicio' => $horaIni,
            'hora_fin' => $horaFin,
            'duracion_horas' => $minutos / 60,
            'contacto_nombre' => $nombre,
            'contacto_telefono' => $telefono,
            'observaciones' => $observaciones_completas,
            'estado' => 'pendiente',
            'total_estimado' => $calculo['total'],
            'canchas' => $canchasGuardar,
        ]);
        if (!$guardado['ok']) {
            $msg = match ($guardado['codigo']) {
                'ocupado' => 'Ese horario acaba de ser reservado, elige otro.',
                'cancha_no_disponible' => 'Una de las canchas elegidas ya no está disponible.',
                default => 'Ocurrió un error inesperado al enviar la solicitud. Intenta de nuevo.',
            };
            sessionFlash('error', $msg, $guardado['codigo'] === 'ocupado' ? 'warning' : 'danger');
            $this->volverSolicitud();
            return;
        }
        $id = $guardado['id'];

        $listaCanchas = implode(', ', $nombresCancha);
        (new TelegramNotifier())->avisarSolicitudEspacio([
            'id'          => $id,
            'cliente'     => $nombre,
            'telefono'    => $telefono,
            'fecha'       => $fecha,
            'hora_inicio' => substr($horaIni, 0, 5),
            'hora_fin'    => substr($horaFin, 0, 5),
            'canchas'     => $listaCanchas,
            'total'       => formatPrice($calculo['total']),
        ]);
        (new Notificacion())->notificarAdmins('espacio', "Nueva solicitud de cumpleaños/reunión #{$id}", "{$nombre} · {$fecha} " . substr($horaIni, 0, 5) . ' - ' . substr($horaFin, 0, 5), url('/admin/espacios'));

        sessionFlash('success', 'Tu solicitud de espacio ha sido enviada. Total estimado (provisional): ' . formatPrice($calculo['total']) . '. Nos pondremos en contacto contigo para confirmar disponibilidad, precio final y adelanto.', 'success');
        $this->redirect('/mis-solicitudes');
    }

    public function misSolicitudes(): void {
        $this->requireAuth();
        $modelo = new Espacio();
        $solicitudes = $modelo->obtenerPorUsuario((int)$_SESSION['usuario_id']);

        $this->view('espacios/mis_solicitudes', [
            'titulo' => 'Mis Solicitudes de Espacios - TOP GOL',
            'solicitudes' => $solicitudes,
            'canchasPorSolicitud' => $modelo->obtenerCanchasPorSolicitudes(array_column($solicitudes, 'id'))
        ]);
    }

    public function adminIndex(): void {
        $this->requireAdmin();
        $modelo = new Espacio();
        $solicitudes = $modelo->obtenerTodas();

        $this->view('espacios/admin', [
            'titulo' => 'Solicitudes de Espacios - Administrador',
            'adminMenu' => 'espacios',
            'solicitudes' => $solicitudes,
            'canchasPorSolicitud' => $modelo->obtenerCanchasPorSolicitudes(array_column($solicitudes, 'id'))
        ], false);
    }

    public function updateStatus(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $estado = $_POST['estado'] ?? 'pendiente';
        $estadosPermitidos = ['pendiente', 'cotizado', 'confirmado', 'rechazado', 'cancelado'];
        if (!in_array($estado, $estadosPermitidos, true)) {
            sessionFlash('error', 'Estado no válido.', 'danger');
            $this->redirect('/admin/espacios');
            return;
        }

        $precioRaw = trim((string)($_POST['precio_cotizado'] ?? ''));
        $precio = null;
        if ($precioRaw !== '') {
            if (!is_numeric($precioRaw) || (float)$precioRaw < 0) {
                sessionFlash('error', 'El precio cotizado debe ser un número válido mayor o igual a 0.', 'danger');
                $this->redirect('/admin/espacios');
                return;
            }
            $precio = round((float)$precioRaw, 2);
        }

        $modelo = new Espacio();
        $solicitud = $modelo->obtenerPorId((int)$id);
        if (!$solicitud) {
            sessionFlash('error', 'La solicitud indicada no existe.', 'warning');
            $this->redirect('/admin/espacios');
            return;
        }

        // Al confirmar, el espacio y las canchas se vuelven a comprobar de forma atómica (con bloqueo)
        $res = $modelo->cambiarEstadoAtomico((int)$id, $estado, $precio);
        if (!$res['ok']) {
            if ($res['codigo'] === 'ocupado') {
                if (($res['cancha_id'] ?? 0) > 0) {
                    $cn = (new Cancha())->obtenerPorId((int)$res['cancha_id']);
                    sessionFlash('error', 'No se puede confirmar: la ' . ($cn['nombre'] ?? 'cancha') . ' ya no está libre en ese horario.', 'danger');
                } else {
                    sessionFlash('error', 'No se puede confirmar: Ya existe otra solicitud confirmada que se cruza en ese horario.', 'danger');
                }
            } else {
                sessionFlash('error', 'No se pudo actualizar la solicitud. Intenta de nuevo.', 'danger');
            }
            $this->redirect('/admin/espacios');
            return;
        }

        sessionFlash('success', "Solicitud #{$id} actualizada correctamente.", 'success');
        $this->redirect('/admin/espacios');
    }
}
