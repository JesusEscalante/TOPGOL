<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * ====================================================================
 * TOP GOL - Controlador de Contacto
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

        // Notificación interna para admins (opcional)
        try {
            $m = new \App\Models\Notificacion();
            $m->notificarAdmins('contacto', "Nuevo mensaje de {$nombre}", mb_strimwidth($asunto . ' - ' . $mensaje, 0, 120, '…'), '/contacto');
        } catch (\Throwable $e) {}

        // Telegram si está habilitado
        try {
            $texto = "CONTACTO TOP GOL\nDe: {$nombre} ({$email}" . ($telefono ? ", {$telefono}" : "") . ")\nAsunto: {$asunto}\n\n{$mensaje}";
            (new \App\Services\TelegramNotifier())->send($texto);
        } catch (\Throwable $e) {}

        sessionFlash('success', '¡Gracias por contactarnos, ' . htmlspecialchars($nombre) . '! Te responderemos muy pronto por correo o WhatsApp.', 'success');
        $this->redirect('/contacto');
    }
}
