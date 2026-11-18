<?php
declare(strict_types=1);

namespace App\Services;

/**
 * ====================================================================
 * TOP GOL - Servicio de Envío de Correos Electrónicos (Mailer)
 * ====================================================================
 * Soporta envío mediante SMTP nativo (con STARTTLS / SSL / AUTH LOGIN),
 * función mail() de PHP y registro en archivo local para desarrollo
 * (storage/logs/emails.log).
 */
class Mailer
{
    private string $driver;
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromAddress;
    private string $fromName;

    public function __construct()
    {
        $this->driver      = defined('MAIL_DRIVER') ? (string)MAIL_DRIVER : 'smtp';
        $this->host        = defined('MAIL_HOST') ? (string)MAIL_HOST : 'smtp.gmail.com';
        $this->port        = defined('MAIL_PORT') ? (int)MAIL_PORT : 587;
        $this->username    = defined('MAIL_USERNAME') ? (string)MAIL_USERNAME : '';
        $this->password    = defined('MAIL_PASSWORD') ? (string)MAIL_PASSWORD : '';
        $this->encryption  = defined('MAIL_ENCRYPTION') ? (string)MAIL_ENCRYPTION : 'tls';
        $this->fromAddress = defined('MAIL_FROM_ADDRESS') && MAIL_FROM_ADDRESS !== '' ? (string)MAIL_FROM_ADDRESS : 'no-reply@topgol.com';
        $this->fromName    = defined('MAIL_FROM_NAME') && MAIL_FROM_NAME !== '' ? (string)MAIL_FROM_NAME : (defined('APP_NAME') ? (string)APP_NAME : 'TOP GOL');
    }

    /**
     * Indica si existe una configuración SMTP activa con credenciales
     * o un servidor local SMTP sin autenticación (ej. Mailpit/Mailhog).
     */
    public function isSmtpConfigured(): bool
    {
        if ($this->driver !== 'smtp' || $this->host === '') {
            return false;
        }
        // Si es un servidor local de pruebas (ej. puerto 1025 o 25 en localhost), no requiere usuario
        if (in_array($this->host, ['localhost', '127.0.0.1'], true) && in_array($this->port, [25, 1025], true)) {
            return true;
        }
        return $this->username !== '' && $this->password !== '';
    }

    /**
     * Envía el correo de recuperación de contraseña con plantilla HTML de TOP GOL.
     *
     * @param string $toEmail Correo del destinatario
     * @param string $toName Nombre del destinatario
     * @param string $resetUrl Enlace único de recuperación
     * @param int $expiraMinutos Tiempo de validez en minutos
     * @return bool True si se envió por SMTP/mail() o se registró en modo desarrollo
     */
    public function enviarRecuperacionPassword(string $toEmail, string $toName, string $resetUrl, int $expiraMinutos = 30): bool
    {
        $appName = defined('APP_NAME') ? (string)APP_NAME : 'TOP GOL';
        $subject = "Recuperación de contraseña - {$appName}";
        $nombreSeguro = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $urlSegura = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:'Segoe UI',Arial,sans-serif;color:#1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:30px 15px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color:#0f291e;padding:28px 24px;text-align:center;">
                            <h1 style="margin:0;font-size:26px;color:#ffffff;letter-spacing:0.5px;">
                                TOP <span style="color:#f5c842;">GOL</span>
                            </h1>
                            <p style="margin:6px 0 0;color:#cbd5e1;font-size:13px;">Complejo Deportivo y Eventos</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px;">
                            <h2 style="margin:0 0 16px;font-size:20px;color:#0f291e;">Hola, {$nombreSeguro}</h2>
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;">
                                Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en <strong>{$appName}</strong>.
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#334155;">
                                Haz clic en el siguiente botón para crear una nueva contraseña. Este enlace es válido por <strong>{$expiraMinutos} minutos</strong>:
                            </p>
                            <div style="text-align:center;margin:28px 0;">
                                <a href="{$urlSegura}" style="background-color:#1a7a3a;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-weight:bold;font-size:15px;display:inline-block;border:2px solid #f5c842;">
                                    Restablecer mi contraseña
                                </a>
                            </div>
                            <p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#64748b;">
                                Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:
                            </p>
                            <p style="margin:0 0 24px;font-size:12px;word-break:break-all;background-color:#f8fafc;padding:10px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#0f291e;">
                                <a href="{$urlSegura}" style="color:#1a7a3a;">{$urlSegura}</a>
                            </p>
                            <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
                                Si tú no solicitaste este cambio, puedes ignorar este correo con total tranquilidad; tu contraseña actual seguirá siendo segura.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8fafc;padding:16px 24px;text-align:center;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0;">
                            &copy; {$appName} - Sistema de Gestión de Canchas Sintéticas
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $textBody = "Hola {$toName},\n\n"
            . "Hemos recibido una solicitud para restablecer tu contraseña en {$appName}.\n"
            . "Usa el siguiente enlace para crear una nueva contraseña (válido por {$expiraMinutos} minutos):\n\n"
            . "{$resetUrl}\n\n"
            . "Si no solicitaste este cambio, ignora este mensaje.\n";

        return $this->send($toEmail, $toName, $subject, $htmlBody, $textBody);
    }

    /**
     * Construye el envoltorio HTML corporativo de TOP GOL para cualquier notificación al cliente.
     */
    private function construirPlantillaCorreo(
        string $subject,
        string $toName,
        string $badgeText,
        string $badgeBg,
        string $contentHtml,
        string $ctaText = '',
        string $ctaUrl = ''
    ): string {
        $appName = defined('APP_NAME') ? (string)APP_NAME : 'TOP GOL';
        $nombreSeguro = htmlspecialchars($toName !== '' ? $toName : 'Cliente', ENT_QUOTES, 'UTF-8');
        $badgeSeguro = htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8');

        $ctaHtml = '';
        if ($ctaText !== '' && $ctaUrl !== '') {
            $urlSegura = htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8');
            $txtSeguro = htmlspecialchars($ctaText, ENT_QUOTES, 'UTF-8');
            $ctaHtml = <<<HTML
<div style="text-align:center;margin:26px 0 10px;">
    <a href="{$urlSegura}" style="background-color:#1a7a3a;color:#ffffff;text-decoration:none;padding:13px 26px;border-radius:8px;font-weight:bold;font-size:14px;display:inline-block;border:2px solid #f5c842;">
        {$txtSeguro}
    </a>
</div>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:'Segoe UI',Arial,sans-serif;color:#1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:30px 15px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color:#0f291e;padding:26px 24px;text-align:center;">
                            <h1 style="margin:0;font-size:26px;color:#ffffff;letter-spacing:0.5px;">
                                TOP <span style="color:#f5c842;">GOL</span>
                            </h1>
                            <p style="margin:6px 0 0;color:#cbd5e1;font-size:13px;">Complejo Deportivo y Eventos</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 28px;">
                            <div style="margin-bottom:14px;">
                                <span style="background-color:{$badgeBg};color:#ffffff;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:bold;letter-spacing:0.4px;display:inline-block;">
                                    {$badgeSeguro}
                                </span>
                            </div>
                            <h2 style="margin:0 0 14px;font-size:20px;color:#0f291e;">Hola, {$nombreSeguro}</h2>
                            {$contentHtml}
                            {$ctaHtml}
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8fafc;padding:16px 24px;text-align:center;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0;">
                            &copy; {$appName} - Notificación automática al cliente
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Genera la tabla HTML con los detalles de una reserva para incrustar en los correos.
     *
     * @param array<string, mixed> $r
     */
    private function construirTablaReservaHtml(array $r): string
    {
        $id      = (int)($r['id'] ?? 0);
        $cancha  = htmlspecialchars((string)($r['cancha_nombre'] ?? 'Cancha'), ENT_QUOTES, 'UTF-8');
        $fecha   = htmlspecialchars((string)($r['fecha'] ?? ''), ENT_QUOTES, 'UTF-8');
        $horario = htmlspecialchars((string)($r['hora_inicio'] ?? '') . ' - ' . (string)($r['hora_fin'] ?? ''), ENT_QUOTES, 'UTF-8');
        $total   = 'S/ ' . number_format((float)($r['total_pago'] ?? 0), 2);
        $adelanto = 'S/ ' . number_format((float)($r['adelanto_monto'] ?? 0), 2);
        $saldo   = 'S/ ' . number_format((float)($r['saldo_pendiente'] ?? max(0, (float)($r['total_pago'] ?? 0) - (float)($r['total_pagado'] ?? 0))), 2);

        return <<<HTML
<table width="100%" cellpadding="8" cellspacing="0" style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin:18px 0;font-size:14px;">
    <tr>
        <td style="color:#64748b;border-bottom:1px solid #e2e8f0;width:42%;"><strong>Reserva N°:</strong></td>
        <td style="color:#0f172a;border-bottom:1px solid #e2e8f0;"><strong>#{$id}</strong></td>
    </tr>
    <tr>
        <td style="color:#64748b;border-bottom:1px solid #e2e8f0;"><strong>Cancha(s):</strong></td>
        <td style="color:#0f172a;border-bottom:1px solid #e2e8f0;">{$cancha}</td>
    </tr>
    <tr>
        <td style="color:#64748b;border-bottom:1px solid #e2e8f0;"><strong>Fecha:</strong></td>
        <td style="color:#0f172a;border-bottom:1px solid #e2e8f0;">{$fecha}</td>
    </tr>
    <tr>
        <td style="color:#64748b;border-bottom:1px solid #e2e8f0;"><strong>Horario:</strong></td>
        <td style="color:#0f172a;border-bottom:1px solid #e2e8f0;">{$horario}</td>
    </tr>
    <tr>
        <td style="color:#64748b;border-bottom:1px solid #e2e8f0;"><strong>Total del alquiler:</strong></td>
        <td style="color:#0f172a;border-bottom:1px solid #e2e8f0;">{$total}</td>
    </tr>
    <tr>
        <td style="color:#64748b;border-bottom:1px solid #e2e8f0;"><strong>Adelanto registrado:</strong></td>
        <td style="color:#0f172a;border-bottom:1px solid #e2e8f0;">{$adelanto}</td>
    </tr>
    <tr>
        <td style="color:#64748b;"><strong>Saldo pendiente:</strong></td>
        <td style="color:#1a7a3a;font-weight:bold;">{$saldo}</td>
    </tr>
</table>
HTML;
    }

    /**
     * RF26: Envía notificación al cliente cuando registra una reserva o evento grupal.
     *
     * @param array<string, mixed> $r Resumen de la reserva (obtenerResumenNotificacion)
     */
    public function notificarReservaRecibida(array $r): bool
    {
        $email = trim((string)($r['cliente_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $id = (int)($r['id'] ?? 0);
        $nombre = (string)($r['cliente_nombre'] ?? 'Cliente');
        $estado = (string)($r['estado'] ?? 'pendiente');
        $esConfirmada = ($estado === 'confirmada');
        $plazo = defined('VERIFICACION_PAGO_PLAZO') ? (string)VERIFICACION_PAGO_PLAZO : '15 minutos';

        $subject = $esConfirmada
            ? "Reserva #{$id} Confirmada - TOP GOL"
            : "Reserva #{$id} Recibida (Comprobante en revisión) - TOP GOL";

        $badgeText = $esConfirmada ? 'RESERVA CONFIRMADA' : 'PAGO EN REVISIÓN';
        $badgeBg   = $esConfirmada ? '#16a34a' : '#d97706';

        $intro = $esConfirmada
            ? "<p style=\"margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;\">Tu reserva ha sido registrada y <strong>confirmada</strong> exitosamente en nuestro sistema.</p>"
            : "<p style=\"margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;\">Hemos recibido tu solicitud de reserva junto con tu comprobante de adelanto. Nuestro encargado verificará el pago en un plazo aproximado de <strong>{$plazo}</strong> y te enviaremos un correo de confirmación.</p>";

        $tabla = $this->construirTablaReservaHtml($r);
        $detalleUrl = (defined('URL_BASE') ? rtrim((string)URL_BASE, '/') : '') . '/mis-reservas/' . $id;

        $html = $this->construirPlantillaCorreo(
            $subject,
            $nombre,
            $badgeText,
            $badgeBg,
            $intro . $tabla,
            'Ver detalle de mi reserva',
            $detalleUrl
        );

        $texto = "Hola {$nombre},\n"
            . ($esConfirmada ? "Tu reserva #{$id} ha sido confirmada.\n" : "Tu reserva #{$id} fue recibida y su comprobante está en revisión.\n")
            . "Cancha(s): " . ($r['cancha_nombre'] ?? '') . "\n"
            . "Fecha: " . ($r['fecha'] ?? '') . " (" . ($r['hora_inicio'] ?? '') . " - " . ($r['hora_fin'] ?? '') . ")\n"
            . "Total: S/ " . number_format((float)($r['total_pago'] ?? 0), 2) . "\n"
            . "Consulta el estado aquí: {$detalleUrl}\n";

        return $this->send($email, $nombre, $subject, $html, $texto);
    }

    /**
     * RF26: Envía notificación al cliente cuando su comprobante de pago es verificado y su reserva queda confirmada.
     *
     * @param array<string, mixed> $r Resumen de la reserva
     */
    public function notificarPagoVerificado(array $r): bool
    {
        $email = trim((string)($r['cliente_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $id = (int)($r['id'] ?? 0);
        $nombre = (string)($r['cliente_nombre'] ?? 'Cliente');
        $tolerancia = defined('TOLERANCIA_MINUTOS') ? (int)TOLERANCIA_MINUTOS : 10;

        $subject = "¡Comprobante Aprobado! Reserva #{$id} Confirmada - TOP GOL";
        $intro = "<p style=\"margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;\">"
            . "¡Buenas noticias! Hemos verificado tu comprobante de pago y tu <strong>Reserva #{$id}</strong> se encuentra oficialmente <strong>CONFIRMADA</strong>."
            . "</p>";
        $tabla = $this->construirTablaReservaHtml($r);
        $nota = "<p style=\"margin:14px 0 0;font-size:13px;line-height:1.5;color:#475569;background-color:#f0fdf4;padding:12px;border-radius:8px;border-left:4px solid #16a34a;\">"
            . "<strong>Recordatorio:</strong> Te recomendamos llegar con anticipación. La tolerancia de ingreso es de <strong>{$tolerancia} minutos</strong> desde la hora de inicio programada."
            . "</p>";

        $detalleUrl = (defined('URL_BASE') ? rtrim((string)URL_BASE, '/') : '') . '/mis-reservas/' . $id;

        $html = $this->construirPlantillaCorreo(
            $subject,
            $nombre,
            'PAGO VERIFICADO · RESERVA CONFIRMADA',
            '#16a34a',
            $intro . $tabla . $nota,
            'Ver mi reserva confirmada',
            $detalleUrl
        );

        $texto = "Hola {$nombre},\n"
            . "¡Tu comprobante fue verificado y tu Reserva #{$id} está CONFIRMADA!\n"
            . "Cancha(s): " . ($r['cancha_nombre'] ?? '') . "\n"
            . "Fecha: " . ($r['fecha'] ?? '') . " (" . ($r['hora_inicio'] ?? '') . " - " . ($r['hora_fin'] ?? '') . ")\n"
            . "Saldo pendiente en cancha: S/ " . number_format((float)($r['saldo_pendiente'] ?? 0), 2) . "\n"
            . "Ver reserva: {$detalleUrl}\n";

        return $this->send($email, $nombre, $subject, $html, $texto);
    }

    /**
     * RF26: Envía notificación al cliente cuando su comprobante de pago es rechazado y la reserva se cancela.
     *
     * @param array<string, mixed> $r Resumen de la reserva
     * @param string $motivo Motivo del rechazo ingresado por el encargado
     */
    public function notificarPagoRechazado(array $r, string $motivo = ''): bool
    {
        $email = trim((string)($r['cliente_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $id = (int)($r['id'] ?? 0);
        $nombre = (string)($r['cliente_nombre'] ?? 'Cliente');
        $motivoSeguro = htmlspecialchars($motivo !== '' ? $motivo : 'El comprobante adjunto no pudo ser validado.', ENT_QUOTES, 'UTF-8');

        $subject = "Aviso sobre tu Reserva #{$id} (Comprobante Rechazado) - TOP GOL";
        $intro = "<p style=\"margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;\">"
            . "Lamentamos informarte que el comprobante de pago enviado para la <strong>Reserva #{$id}</strong> fue rechazado durante la verificación, por lo que la reserva ha quedado <strong>cancelada</strong> y el horario fue liberado."
            . "</p>";
        $bloqueMotivo = "<div style=\"margin:14px 0;padding:12px 14px;background-color:#fef2f2;border-left:4px solid #dc2626;border-radius:8px;font-size:14px;color:#991b1b;\">"
            . "<strong>Motivo registrado:</strong> {$motivoSeguro}"
            . "</div>";
        $tabla = $this->construirTablaReservaHtml($r);
        $canchasUrl = (defined('URL_BASE') ? rtrim((string)URL_BASE, '/') : '') . '/canchas';

        $html = $this->construirPlantillaCorreo(
            $subject,
            $nombre,
            'COMPROBANTE RECHAZADO · RESERVA CANCELADA',
            '#dc2626',
            $intro . $bloqueMotivo . $tabla,
            'Realizar una nueva reserva',
            $canchasUrl
        );

        $texto = "Hola {$nombre},\n"
            . "El comprobante de tu Reserva #{$id} fue rechazado y la reserva quedó cancelada.\n"
            . "Motivo: " . ($motivo !== '' ? $motivo : 'No especificado') . "\n"
            . "Puedes volver a reservar en: {$canchasUrl}\n";

        return $this->send($email, $nombre, $subject, $html, $texto);
    }

    /**
     * RF26: Envía notificación al cliente cuando su reserva cambia de estado (confirmada, cancelada o finalizada).
     *
     * @param array<string, mixed> $r Resumen de la reserva
     * @param string $nuevoEstado
     * @param string $motivo Motivo opcional (ej. al cancelar)
     */
    public function notificarEstadoReserva(array $r, string $nuevoEstado, string $motivo = ''): bool
    {
        $email = trim((string)($r['cliente_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $id = (int)($r['id'] ?? 0);
        $nombre = (string)($r['cliente_nombre'] ?? 'Cliente');
        $estadoUpper = strtoupper($nuevoEstado);

        [$badgeBg, $mensajeIntro] = match ($nuevoEstado) {
            'confirmada' => [
                '#16a34a',
                "Tu <strong>Reserva #{$id}</strong> ha sido <strong>confirmada</strong>. ¡Te esperamos en la cancha!",
            ],
            'cancelada' => [
                '#dc2626',
                "Tu <strong>Reserva #{$id}</strong> ha sido <strong>cancelada</strong>.",
            ],
            'finalizada' => [
                '#0f766e',
                "Tu <strong>Reserva #{$id}</strong> ha sido marcada como <strong>finalizada</strong>. ¡Gracias por jugar en TOP GOL!",
            ],
            default => [
                '#475569',
                "El estado de tu <strong>Reserva #{$id}</strong> se ha actualizado a <strong>{$estadoUpper}</strong>.",
            ],
        };

        $motivoHtml = '';
        if ($motivo !== '') {
            $motivoSeguro = htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8');
            $motivoHtml = "<div style=\"margin:14px 0;padding:12px 14px;background-color:#f8fafc;border-left:4px solid {$badgeBg};border-radius:8px;font-size:14px;color:#334155;\">"
                . "<strong>Detalle / Motivo:</strong> {$motivoSeguro}"
                . "</div>";
        }

        $subject = "Actualización de Reserva #{$id} ({$estadoUpper}) - TOP GOL";
        $tabla = $this->construirTablaReservaHtml($r);
        $detalleUrl = (defined('URL_BASE') ? rtrim((string)URL_BASE, '/') : '') . '/mis-reservas/' . $id;

        $html = $this->construirPlantillaCorreo(
            $subject,
            $nombre,
            "ESTADO: {$estadoUpper}",
            $badgeBg,
            "<p style=\"margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;\">{$mensajeIntro}</p>" . $motivoHtml . $tabla,
            'Ver detalle en TOP GOL',
            $detalleUrl
        );

        $texto = "Hola {$nombre},\n"
            . "El estado de tu Reserva #{$id} ahora es: {$estadoUpper}.\n"
            . ($motivo !== '' ? "Motivo: {$motivo}\n" : '')
            . "Cancha(s): " . ($r['cancha_nombre'] ?? '') . " | Fecha: " . ($r['fecha'] ?? '') . " (" . ($r['hora_inicio'] ?? '') . " - " . ($r['hora_fin'] ?? '') . ")\n"
            . "Ver reserva: {$detalleUrl}\n";

        return $this->send($email, $nombre, $subject, $html, $texto);
    }

    /**
     * RF26: Envía notificación al cliente cuando su reserva es reprogramada a una nueva fecha, hora o cancha.
     *
     * @param array<string, mixed> $r Resumen actualizado de la reserva
     * @param array<string, mixed> $anterior Datos previos (cancha_nombre, fecha, hora_inicio, hora_fin)
     * @param string $motivo Motivo de la reprogramación
     */
    public function notificarReprogramacion(array $r, array $anterior, string $motivo = ''): bool
    {
        $email = trim((string)($r['cliente_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $id = (int)($r['id'] ?? 0);
        $nombre = (string)($r['cliente_nombre'] ?? 'Cliente');

        $antCancha = htmlspecialchars((string)($anterior['cancha_nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
        $antFecha  = htmlspecialchars((string)($anterior['fecha'] ?? ''), ENT_QUOTES, 'UTF-8');
        $antHora   = htmlspecialchars(substr((string)($anterior['hora_inicio'] ?? ''), 0, 5) . ' - ' . substr((string)($anterior['hora_fin'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8');

        $motivoHtml = '';
        if ($motivo !== '') {
            $motivoSeguro = htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8');
            $motivoHtml = "<p style=\"margin:10px 0;font-size:14px;color:#334155;\"><strong>Motivo de reprogramación:</strong> {$motivoSeguro}</p>";
        }

        $bloqueAnterior = <<<HTML
<div style="margin:14px 0;padding:12px 14px;background-color:#fffbeb;border-left:4px solid #f59e0b;border-radius:8px;font-size:13px;color:#92400e;">
    <strong>Horario anterior:</strong> {$antCancha} · {$antFecha} ({$antHora})
</div>
HTML;

        $subject = "Reserva #{$id} Reprogramada - TOP GOL";
        $intro = "<p style=\"margin:0 0 12px;font-size:15px;line-height:1.6;color:#334155;\">"
            . "Te informamos que tu <strong>Reserva #{$id}</strong> ha sido <strong>reprogramada</strong> con los siguientes nuevos datos:"
            . "</p>";
        $tabla = $this->construirTablaReservaHtml($r);
        $detalleUrl = (defined('URL_BASE') ? rtrim((string)URL_BASE, '/') : '') . '/mis-reservas/' . $id;

        $html = $this->construirPlantillaCorreo(
            $subject,
            $nombre,
            'RESERVA REPROGRAMADA',
            '#2563eb',
            $intro . $bloqueAnterior . $motivoHtml . $tabla,
            'Ver nuevo horario de reserva',
            $detalleUrl
        );

        $texto = "Hola {$nombre},\n"
            . "Tu Reserva #{$id} ha sido reprogramada.\n"
            . "Anterior: {$antCancha} el {$antFecha} ({$antHora})\n"
            . "Nuevo: " . ($r['cancha_nombre'] ?? '') . " el " . ($r['fecha'] ?? '') . " (" . ($r['hora_inicio'] ?? '') . " - " . ($r['hora_fin'] ?? '') . ")\n"
            . ($motivo !== '' ? "Motivo: {$motivo}\n" : '')
            . "Ver detalle: {$detalleUrl}\n";

        return $this->send($email, $nombre, $subject, $html, $texto);
    }

    /**
     * RF26: Envía constancia por correo al cliente cuando se registra el cobro del saldo pendiente.
     *
     * @param array<string, mixed> $r Resumen actualizado de la reserva
     * @param float $montoCobrado Monto abonado en esta operación
     * @param string $metodoPago Método utilizado (efectivo, yape, niubiz)
     */
    public function notificarCobroSaldo(array $r, float $montoCobrado, string $metodoPago): bool
    {
        $email = trim((string)($r['cliente_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $id = (int)($r['id'] ?? 0);
        $nombre = (string)($r['cliente_nombre'] ?? 'Cliente');
        $metodoLbl = match ($metodoPago) {
            'niubiz' => 'Niubiz POS',
            'yape'   => 'Yape',
            default  => 'Efectivo',
        };
        $montoFmt = 'S/ ' . number_format($montoCobrado, 2);

        $subject = "Constancia de Pago - Reserva #{$id} - TOP GOL";
        $intro = "<p style=\"margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;\">"
            . "Se ha registrado satisfactoriamente tu pago de <strong>{$montoFmt}</strong> mediante <strong>{$metodoLbl}</strong> correspondiente a la <strong>Reserva #{$id}</strong>."
            . "</p>";
        $tabla = $this->construirTablaReservaHtml($r);
        $detalleUrl = (defined('URL_BASE') ? rtrim((string)URL_BASE, '/') : '') . '/mis-reservas/' . $id;

        $html = $this->construirPlantillaCorreo(
            $subject,
            $nombre,
            'PAGO DE SALDO REGISTRADO',
            '#16a34a',
            $intro . $tabla,
            'Ver estado de mi reserva',
            $detalleUrl
        );

        $texto = "Hola {$nombre},\n"
            . "Se registró tu pago de {$montoFmt} ({$metodoLbl}) para la Reserva #{$id}.\n"
            . "Saldo pendiente actual: S/ " . number_format((float)($r['saldo_pendiente'] ?? 0), 2) . "\n"
            . "Detalle: {$detalleUrl}\n";

        return $this->send($email, $nombre, $subject, $html, $texto);
    }

    /**
     * RF26: Envía notificación al cliente sobre su solicitud de espacio para cumpleaños/reuniones
     * (al crearse o al cambiar a cotizado, confirmado, rechazado o cancelado).
     *
     * @param array<string, mixed> $s Datos de la solicitud
     */
    public function notificarSolicitudEspacio(array $s): bool
    {
        $email = trim((string)($s['usuario_email'] ?? $s['cliente_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $id = (int)($s['id'] ?? 0);
        $nombre = (string)($s['contacto_nombre'] ?? $s['cliente_nombre'] ?? 'Cliente');
        $estado = (string)($s['estado'] ?? 'pendiente');
        $espacio = htmlspecialchars((string)($s['espacio_nombre'] ?? 'Salón de Cumpleaños / Reuniones'), ENT_QUOTES, 'UTF-8');
        $fecha = htmlspecialchars((string)($s['fecha'] ?? ''), ENT_QUOTES, 'UTF-8');
        $horaIni = substr((string)($s['hora_inicio'] ?? ''), 0, 5);
        $horaFin = !empty($s['hora_fin'])
            ? substr((string)$s['hora_fin'], 0, 5)
            : date('H:i', strtotime("1970-01-01 {$horaIni}:00") + (int)round(((float)($s['duracion_horas'] ?? 1)) * 3600));
        $horario = htmlspecialchars("{$horaIni} - {$horaFin}", ENT_QUOTES, 'UTF-8');

        $precioFinal = isset($s['precio_cotizado']) && $s['precio_cotizado'] !== null && $s['precio_cotizado'] !== ''
            ? 'S/ ' . number_format((float)$s['precio_cotizado'], 2) . ' (Cotización final)'
            : 'S/ ' . number_format((float)($s['total_estimado'] ?? 0), 2) . ' (Estimado provisional)';

        [$badgeText, $badgeBg, $intro] = match ($estado) {
            'cotizado' => [
                'SOLICITUD COTIZADA',
                '#2563eb',
                "Hemos cotizado tu solicitud <strong>#{$id}</strong> para el espacio de eventos. Revisa el monto cotizado a continuación y comunícate con nosotros para confirmar tu reserva.",
            ],
            'confirmado' => [
                'EVENTO CONFIRMADO',
                '#16a34a',
                "¡Excelente noticia! Tu solicitud de espacio <strong>#{$id}</strong> ha sido <strong>CONFIRMADA</strong>. El salón y las canchas solicitadas ya están separados para tu evento.",
            ],
            'rechazado' => [
                'SOLICITUD RECHAZADA',
                '#dc2626',
                "Lamentamos informarte que tu solicitud de espacio <strong>#{$id}</strong> no pudo ser aprobada para el horario seleccionado.",
            ],
            'cancelado' => [
                'SOLICITUD CANCELADA',
                '#64748b',
                "Tu solicitud de espacio <strong>#{$id}</strong> ha sido marcada como <strong>cancelada</strong>.",
            ],
            default => [
                'SOLICITUD RECIBIDA',
                '#d97706',
                "Hemos recibido tu solicitud de espacio <strong>#{$id}</strong>. Nuestro equipo revisará la disponibilidad y te enviará la cotización final a la brevedad.",
            ],
        };

        $tabla = <<<HTML
<table width="100%" cellpadding="8" cellspacing="0" style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin:18px 0;font-size:14px;">
    <tr>
        <td style="color:#64748b;border-bottom:1px solid #e2e8f0;width:42%;"><strong>Solicitud N°:</strong></td>
        <td style="color:#0f172a;border-bottom:1px solid #e2e8f0;"><strong>#{$id}</strong></td>
    </tr>
    <tr>
        <td style="color:#64748b;border-bottom:1px solid #e2e8f0;"><strong>Espacio:</strong></td>
        <td style="color:#0f172a;border-bottom:1px solid #e2e8f0;">{$espacio}</td>
    </tr>
    <tr>
        <td style="color:#64748b;border-bottom:1px solid #e2e8f0;"><strong>Fecha:</strong></td>
        <td style="color:#0f172a;border-bottom:1px solid #e2e8f0;">{$fecha}</td>
    </tr>
    <tr>
        <td style="color:#64748b;border-bottom:1px solid #e2e8f0;"><strong>Horario:</strong></td>
        <td style="color:#0f172a;border-bottom:1px solid #e2e8f0;">{$horario}</td>
    </tr>
    <tr>
        <td style="color:#64748b;"><strong>Importe:</strong></td>
        <td style="color:#1a7a3a;font-weight:bold;">{$precioFinal}</td>
    </tr>
</table>
HTML;

        $subject = "Solicitud de Espacio #{$id} (" . strtoupper($estado) . ") - TOP GOL";
        $misSolicitudesUrl = (defined('URL_BASE') ? rtrim((string)URL_BASE, '/') : '') . '/mis-solicitudes';

        $html = $this->construirPlantillaCorreo(
            $subject,
            $nombre,
            $badgeText,
            $badgeBg,
            "<p style=\"margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;\">{$intro}</p>" . $tabla,
            'Ver mis solicitudes',
            $misSolicitudesUrl
        );

        $texto = "Hola {$nombre},\n"
            . "Actualización de tu Solicitud de Espacio #{$id}: " . strtoupper($estado) . "\n"
            . "Espacio: {$espacio} | Fecha: {$fecha} ({$horaIni} - {$horaFin})\n"
            . "Importe: {$precioFinal}\n"
            . "Ver mis solicitudes: {$misSolicitudesUrl}\n";

        return $this->send($email, $nombre, $subject, $html, $texto);
    }

    /**
     * Envía un correo electrónico en formato HTML/Texto y registra en el log si aplica.
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $sent = false;

        if ($this->driver === 'smtp' && $this->isSmtpConfigured()) {
            $sent = $this->sendViaSmtp($toEmail, $toName, $subject, $htmlBody, $textBody);
        } elseif ($this->driver === 'mail') {
            $sent = $this->sendViaPhpMail($toEmail, $subject, $htmlBody);
        }

        // Registrar siempre en el log de desarrollo o como respaldo si no hay servidor SMTP activo
        $isDev = defined('APP_ENV') && APP_ENV === 'development';
        if ($isDev || $this->driver === 'log' || !$sent) {
            $this->logEmail($toEmail, $subject, $textBody !== '' ? $textBody : strip_tags($htmlBody), $sent);
            if ($isDev || $this->driver === 'log') {
                return true;
            }
        }

        return $sent;
    }

    /**
     * Envío mediante función nativa mail() de PHP
     */
    private function sendViaPhpMail(string $toEmail, string $subject, string $htmlBody): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->encodeHeader($this->fromName) . " <{$this->fromAddress}>",
            'Reply-To: ' . $this->fromAddress,
            'X-Mailer: PHP/' . phpversion(),
        ];

        $encodedSubject = $this->encodeHeader($subject);
        return @mail($toEmail, $encodedSubject, $htmlBody, implode("\r\n", $headers));
    }

    /**
     * Envío mediante socket SMTP directo (soporta TLS/STARTTLS y SSL)
     */
    private function sendViaSmtp(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): bool
    {
        try {
            $remoteHost = ($this->encryption === 'ssl') ? "ssl://{$this->host}" : $this->host;
            $errno = 0;
            $errstr = '';

            $socket = @stream_socket_client(
                "{$remoteHost}:{$this->port}",
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT
            );

            if (!is_resource($socket)) {
                error_log("[TOP GOL][Mailer] No se pudo conectar a {$remoteHost}:{$this->port} ({$errno}: {$errstr})");
                return false;
            }

            stream_set_timeout($socket, 10);

            if (!$this->expectSmtpCode($socket, [220])) {
                fclose($socket);
                return false;
            }

            $localHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $this->sendSmtpCommand($socket, "EHLO {$localHost}");
            if (!$this->expectSmtpCode($socket, [250])) {
                fclose($socket);
                return false;
            }

            if ($this->encryption === 'tls') {
                $this->sendSmtpCommand($socket, 'STARTTLS');
                if (!$this->expectSmtpCode($socket, [220])) {
                    fclose($socket);
                    return false;
                }
                $cryptoOk = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$cryptoOk) {
                    error_log('[TOP GOL][Mailer] Falló la negociación STARTTLS.');
                    fclose($socket);
                    return false;
                }
                $this->sendSmtpCommand($socket, "EHLO {$localHost}");
                if (!$this->expectSmtpCode($socket, [250])) {
                    fclose($socket);
                    return false;
                }
            }

            if ($this->username !== '' && $this->password !== '') {
                $this->sendSmtpCommand($socket, 'AUTH LOGIN');
                if (!$this->expectSmtpCode($socket, [334])) {
                    fclose($socket);
                    return false;
                }
                $this->sendSmtpCommand($socket, base64_encode($this->username));
                if (!$this->expectSmtpCode($socket, [334])) {
                    fclose($socket);
                    return false;
                }
                $this->sendSmtpCommand($socket, base64_encode($this->password));
                if (!$this->expectSmtpCode($socket, [235])) {
                    error_log('[TOP GOL][Mailer] Credenciales SMTP rechazadas.');
                    fclose($socket);
                    return false;
                }
            }

            $senderEmail = filter_var($this->username, FILTER_VALIDATE_EMAIL) ? $this->username : $this->fromAddress;

            $this->sendSmtpCommand($socket, "MAIL FROM:<{$senderEmail}>");
            if (!$this->expectSmtpCode($socket, [250])) {
                fclose($socket);
                return false;
            }

            $this->sendSmtpCommand($socket, "RCPT TO:<{$toEmail}>");
            if (!$this->expectSmtpCode($socket, [250, 251])) {
                fclose($socket);
                return false;
            }

            $this->sendSmtpCommand($socket, 'DATA');
            if (!$this->expectSmtpCode($socket, [354])) {
                fclose($socket);
                return false;
            }

            $boundary = 'bnd_' . bin2hex(random_bytes(8));
            $headers = [
                'Date: ' . date('r'),
                'From: ' . $this->encodeHeader($this->fromName) . " <{$senderEmail}>",
                'To: ' . ($toName !== '' ? $this->encodeHeader($toName) . " <{$toEmail}>" : $toEmail),
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            ];

            $plain = $textBody !== '' ? $textBody : strip_tags($htmlBody);

            $message = implode("\r\n", $headers) . "\r\n\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($plain)) . "\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($htmlBody)) . "\r\n"
                . "--{$boundary}--\r\n.";

            $this->sendSmtpCommand($socket, $message);
            $ok = $this->expectSmtpCode($socket, [250]);

            $this->sendSmtpCommand($socket, 'QUIT');
            fclose($socket);

            return $ok;
        } catch (\Throwable $e) {
            error_log('[TOP GOL][Mailer] Excepción SMTP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía una línea de comando al socket SMTP
     *
     * @param resource $socket
     */
    private function sendSmtpCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    /**
     * Lee la respuesta del servidor SMTP y verifica el código numérico esperado
     *
     * @param resource $socket
     * @param array<int, int> $expectedCodes
     */
    private function expectSmtpCode($socket, array $expectedCodes): bool
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            error_log("[TOP GOL][Mailer] Respuesta SMTP inesperada ({$code}): " . trim($response));
            return false;
        }
        return true;
    }

    /**
     * Codifica cabeceras UTF-8 según RFC 2047
     */
    private function encodeHeader(string $text): string
    {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }

    /**
     * Registra el correo en storage/logs/emails.log para trazabilidad y pruebas locales
     */
    private function logEmail(string $toEmail, string $subject, string $body, bool $dispatched): void
    {
        $logDir = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'emails.log';
        $estado = $dispatched ? 'ENVIADO_SMTP' : 'GUARDADO_LOCAL_DEV';
        $entry = sprintf(
            "[%s] [%s] Para: %s | Asunto: %s\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $estado,
            $toEmail,
            $subject,
            trim($body),
            str_repeat('-', 70)
        );
        @file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
