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
