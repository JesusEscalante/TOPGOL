<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Espacio;

class EspacioController extends Controller {
    public function solicitar(): void {
        $this->requireAuth();
        $this->view('espacios/solicitar', [
            'titulo' => 'Solicitar Cumpleaños/Reunión - TOP GOL'
        ]);
    }

    public function storeSolicitud(): void {
        $this->requireAuth();
        $this->validateCsrf();

        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $duracion = (float)($_POST['duracion'] ?? 1);
        $nombre = $_POST['contacto_nombre'] ?? '';
        $telefono = $_POST['contacto_telefono'] ?? '';
        $tipo_celebracion = $_POST['tipo_celebracion'] ?? 'No especificado';
        $observaciones = trim($_POST['observaciones'] ?? '');

        $observaciones_completas = "[Tipo: $tipo_celebracion]";
        if (!empty($observaciones)) {
            $observaciones_completas .= " - " . $observaciones;
        }

        if (!$fecha || !$hora || !$nombre || !$telefono) {
            sessionFlash('error', 'Faltan campos obligatorios.', 'danger');
            $this->redirect('/cumpleanos/solicitar');
            return;
        }

        $hoy = date('Y-m-d');
        if ($fecha < $hoy) {
            sessionFlash('error', 'No es posible solicitar espacios en fechas pasadas.', 'danger');
            $this->redirect('/cumpleanos/solicitar');
            return;
        }

        $minutosInicio = (int)date('i', strtotime("{$fecha} {$hora}"));
        if ($minutosInicio !== 0 && $minutosInicio !== 30) {
            sessionFlash('error', 'La hora de inicio debe ser en bloques de 30 minutos (:00 o :30).', 'danger');
            $this->redirect('/cumpleanos/solicitar');
            return;
        }

        if ($duracion < 0.5 || fmod($duracion * 2, 1) !== 0.0) {
            sessionFlash('error', 'La duración debe ser en múltiplos de 30 minutos.', 'danger');
            $this->redirect('/cumpleanos/solicitar');
            return;
        }

        $modelo = new Espacio();
        $modelo->guardarSolicitud([
            'espacio_id' => 1,
            'usuario_id' => (int)$_SESSION['usuario_id'],
            'fecha' => $fecha,
            'hora_inicio' => $hora,
            'duracion_horas' => $duracion,
            'contacto_nombre' => $nombre,
            'contacto_telefono' => $telefono,
            'observaciones' => $observaciones_completas,
            'estado' => 'pendiente'
        ]);

        sessionFlash('success', 'Tu solicitud de espacio ha sido enviada. Nos pondremos en contacto contigo para confirmar disponibilidad y precios.', 'success');
        $this->redirect('/mis-solicitudes');
    }

    public function misSolicitudes(): void {
        $this->requireAuth();
        $modelo = new Espacio();
        $solicitudes = $modelo->obtenerPorUsuario((int)$_SESSION['usuario_id']);

        $this->view('espacios/mis_solicitudes', [
            'titulo' => 'Mis Solicitudes de Espacios - TOP GOL',
            'solicitudes' => $solicitudes
        ]);
    }

    public function adminIndex(): void {
        $this->requireAdmin();
        $modelo = new Espacio();
        $solicitudes = $modelo->obtenerTodas();

        $this->view('espacios/admin', [
            'titulo' => 'Solicitudes de Espacios - Administrador',
            'adminMenu' => 'espacios',
            'solicitudes' => $solicitudes
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

        if ($estado === 'confirmado') {
            $disponible = $modelo->estaDisponible((int)$solicitud['espacio_id'], $solicitud['fecha'], $solicitud['hora_inicio'], (float)$solicitud['duracion_horas'], (int)$id);
            if (!$disponible) {
                sessionFlash('error', 'No se puede confirmar: Ya existe otra solicitud confirmada que se cruza en ese horario.', 'danger');
                $this->redirect('/admin/espacios');
                return;
            }
        }

        $modelo->actualizarEstadoYPrecio((int)$id, $estado, $precio);

        sessionFlash('success', "Solicitud #{$id} actualizada correctamente.", 'success');
        $this->redirect('/admin/espacios');
    }
}
