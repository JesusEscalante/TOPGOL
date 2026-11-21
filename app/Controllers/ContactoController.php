<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ContactoMensaje;
use App\Models\Notificacion;
use App\Services\TelegramNotifier;

/**
 * ====================================================================
 * TOP GOL - Controlador de Contacto (RF02 / RF16)
 * ====================================================================
 */
class ContactoController extends Controller {

    public function index(): void {
        $this->view('contacto/index', [
            'titulo' => 'Contacto - ' . APP_NAME
        ]);
    }

    public function enviar(): void {
        $this->validateCsrf();

        // Protección anti-spam (honeypot oculto)
        if (!empty($_POST['website'])) {
            sessionFlash('success', '¡Gracias por contactarnos! Te responderemos muy pronto.', 'success');
            $this->redirect('/contacto');
        }

        // Rate-limit por sesión (mínimo 20 segundos entre envíos seguidos)
        $ahora = time();
        if (isset($_SESSION['ultimo_contacto_ts']) && ($ahora - (int)$_SESSION['ultimo_contacto_ts']) < 20) {
            sessionFlash('error', 'Por favor espera unos segundos antes de enviar otro mensaje.', 'warning');
            $this->redirect('/contacto');
        }

        $post = $this->sanitizePost();
        $nombre = trim($post['nombre'] ?? '');
        $email = trim($post['email'] ?? '');
        $telefono = trim($post['telefono'] ?? '');
        $asunto = trim($post['asunto'] ?? 'Consulta general');
        $mensaje = trim($post['mensaje'] ?? '');

        if ($nombre === '' || $email === '' || $mensaje === '') {
            sessionFlash('error', 'Completa nombre, correo y mensaje.', 'danger');
            $this->redirect('/contacto');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sessionFlash('error', 'Correo no válido.', 'danger');
            $this->redirect('/contacto');
        }
        if ($telefono !== '') {
            $telefono = preg_replace('/\s+/', '', $telefono);
            if (!str_starts_with($telefono, '+')) {
                $telefono = '+51 ' . ltrim($telefono, '0');
            }
        }

        // Persistir mensaje completo en base de datos (RF02)
        $idMensaje = 0;
        try {
            $modeloContacto = new ContactoMensaje();
            $idMensaje = $modeloContacto->crear([
                'nombre'     => $nombre,
                'email'      => $email,
                'telefono'   => $telefono,
                'asunto'     => $asunto,
                'mensaje'    => $mensaje,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ]);
            $_SESSION['ultimo_contacto_ts'] = $ahora;
        } catch (\Throwable $e) {
            error_log('[TOP GOL][Contacto] Error al guardar mensaje: ' . $e->getMessage());
        }

        // Notificación interna para admins apuntando al buzón administrativo
        try {
            $m = new Notificacion();
            $m->notificarAdmins(
                'contacto',
                "Nuevo mensaje de {$nombre}" . ($idMensaje > 0 ? " (#{$idMensaje})" : ''),
                mb_strimwidth($asunto . ' - ' . $mensaje, 0, 120, '…'),
                url('/admin/contactos')
            );
        } catch (\Throwable $e) {}

        // Telegram si está habilitado
        try {
            $texto = "CONTACTO TOP GOL" . ($idMensaje > 0 ? " #{$idMensaje}" : '') . "\n"
                . "De: {$nombre} ({$email}" . ($telefono ? ", {$telefono}" : "") . ")\n"
                . "Asunto: {$asunto}\n\n{$mensaje}";
            (new TelegramNotifier())->send($texto);
        } catch (\Throwable $e) {}

        sessionFlash('success', '¡Gracias por contactarnos, ' . htmlspecialchars($nombre) . '! Hemos registrado tu mensaje y te responderemos muy pronto por correo o WhatsApp.', 'success');
        $this->redirect('/contacto');
    }

    /**
     * Bandeja administrativa de mensajes de contacto recibidos (Solo Admin)
     */
    public function adminIndex(): void {
        $this->requireAdmin();
        $modelo = new ContactoMensaje();
        $mensajes = $modelo->obtenerTodos(200);

        $this->view('contacto/admin', [
            'titulo'    => 'Mensajes de Contacto - Panel Administrador',
            'adminMenu' => 'dashboard',
            'mensajes'  => $mensajes,
        ], false);
    }

    /**
     * Marca un mensaje de contacto como atendido (Solo Admin)
     */
    public function atender(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idMsg = (int)$id;
        $modelo = new ContactoMensaje();
        if ($modelo->marcarAtendido($idMsg, true)) {
            auditar('contacto', 'atender_mensaje', "Marcó como atendido el mensaje de contacto #{$idMsg}", 'contacto_mensaje', $idMsg);
            sessionFlash('success', "Mensaje #{$idMsg} marcado como atendido.", 'success');
        }
        $this->redirect('/admin/contactos');
    }
}
