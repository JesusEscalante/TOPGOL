<?php
declare(strict_types=1);

namespace App\Services;

/**
 * ====================================================================
 * TOP GOL - Notificaciones Telegram vía CallMeBot
 * ====================================================================
 * Envía mensajes de texto a Telegram usando la API gratuita:
 * https://api.callmebot.com/text.php?user=[usuario]&text=[texto]&preview=1
 *
 * NOTA: para que el enlace del comprobante sea clicable y con vista
 * previa, APP_URL debe ser una URL pública https (no localhost),
 * ya que Telegram debe poder alcanzar el enlace desde internet.
 *
 * Requisito: el usuario debe iniciar chat con @CallMeBot_txtbot en
 * Telegram y autorizarlo antes de recibir mensajes.
 */
class TelegramNotifier
{
    private const API_URL = 'https://api.callmebot.com/text.php';

    private bool $enabled;
    private string $user;
    private bool $debug;

    public function __construct()
    {
        $this->enabled = defined('TELEGRAM_ENABLED') ? (bool)TELEGRAM_ENABLED : false;
        $this->user = defined('TELEGRAM_USER') ? trim((string)TELEGRAM_USER) : '';
        $this->debug = defined('TELEGRAM_DEBUG') ? (bool)TELEGRAM_DEBUG : false;
    }

    /**
     * Verifica si el servicio está configurado y habilitado
     */
    public function isConfigured(): bool
    {
        return $this->enabled && $this->user !== '';
    }

    /**
     * Envía un mensaje de texto a Telegram.
     * Nunca lanza excepciones: los errores solo se registran en el log.
     *
     * @param string $text Mensaje en texto plano (se codifica automáticamente)
     * @return bool True si la API aceptó el mensaje
     */
    public function send(string $text): bool
    {
        if (!$this->isConfigured() || trim($text) === '') {
            return false;
        }

        $url = self::API_URL . '?' . http_build_query([
            'user'    => $this->user,
            'text'    => $text,
            'preview' => '1',   // fuerza la previsualización
        ]);

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $response = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($this->debug) {
                error_log('[TOP GOL][Telegram] HTTP ' . $httpCode . ' respuesta: ' . substr((string)$response, 0, 300));
            }
            if ($response === false || $httpCode !== 200) {
                error_log("[TOP GOL][Telegram] Error HTTP {$httpCode}: {$error}");
                return false;
            }
            if (is_string($response) && stripos($response, 'ERROR') !== false) {
                error_log('[TOP GOL][Telegram] API respondió: ' . substr($response, 0, 200));
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            error_log('[TOP GOL][Telegram] Excepción: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Arma y envía el aviso de una nueva reserva.
     *
     * @param array<string, mixed> $datos Datos de la reserva creada
     * @return bool
     */
    public function avisarReserva(array $datos): bool
    {
        $id      = (int)($datos['id'] ?? 0);
        $cancha  = (string)($datos['cancha'] ?? '');
        $cliente = (string)($datos['cliente'] ?? '');
        $fecha   = (string)($datos['fecha'] ?? '');
        $hora    = (string)($datos['hora_inicio'] ?? '');
        $fin     = (string)($datos['hora_fin'] ?? '');
        $total   = (string)($datos['total'] ?? '');
        $metodo  = (string)($datos['metodo_pago'] ?? '');
        $comp    = (string)($datos['comprobante_url'] ?? '');

        $texto = "NUEVA RESERVA TOP GOL #{$id}\n"
            . "Cancha: {$cancha}\n"
            . "Cliente: {$cliente}\n"
            . "Fecha: {$fecha}\n"
            . "Horario: {$hora} - {$fin}\n"
            . "Total: {$total} (adelanto S/ 20 por {$metodo})";
        if ($comp !== '') {
            // URL en su propia línea para que Telegram la detecte como enlace
            $texto .= "\n\n{$comp}";
        }

        return $this->send($texto);
    }
}
