<?php
// ====================================================================
// TOP GOL - Servidor WebSocket para notificaciones tiempo real
// ====================================================================
// Uso: php websocket_server.php
// Requiere: extension php_sockets habilitada (php.ini -> extension=sockets)
// Escucha en ws://0.0.0.0:8080 y reenvía mensajes a todos los admins conectados.
// Si no puedes habilitar sockets, el panel ya funciona con SSE (EventSource)
// que es el fallback automático y da el mismo efecto tiempo real.
// ====================================================================

if (!extension_loaded('sockets')) {
    echo "Extensión sockets no habilitada. Habilita extension=sockets en php.ini y reinicia Apache.\n";
    echo "Mientras tanto el panel usa SSE (tiempo real sin WebSocket) y funciona igual.\n";
    exit(1);
}

$host = '0.0.0.0';
$port = 8080;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
if (!socket_bind($socket, $host, $port)) {
    echo "No se pudo hacer bind en $host:$port\n";
    exit(1);
}
socket_listen($socket);
socket_set_nonblock($socket);

$clients = [$socket];
echo "WebSocket TOP GOL escuchando en ws://$host:$port\n";
echo "Presiona Ctrl+C para detener.\n";

function wsHandshake($headers) {
    if (!preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $headers, $m)) return false;
    $key = trim($m[1]);
    $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    return "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: $accept\r\n\r\n";
}

function wsEncode($payload) {
    $len = strlen($payload);
    $header = chr(0x81);
    if ($len <= 125) {
        $header .= chr($len);
    } elseif ($len <= 65535) {
        $header .= chr(126) . pack('n', $len);
    } else {
        $header .= chr(127) . pack('J', $len);
    }
    return $header . $payload;
}

function wsDecode($data) {
    if (strlen($data) < 2) return null;
    $payloadLen = ord($data[1]) & 127;
    $maskStart = 2;
    if ($payloadLen === 126) $maskStart = 4;
    elseif ($payloadLen === 127) $maskStart = 10;
    $masks = substr($data, $maskStart, 4);
    $payload = substr($data, $maskStart + 4);
    $decoded = '';
    for ($i = 0; $i < strlen($payload); $i++) {
        $decoded .= $payload[$i] ^ $masks[$i % 4];
    }
    return $decoded;
}

while (true) {
    $read = $clients;
    $write = $except = null;
    if (socket_select($read, $write, $except, 0, 200000) < 1) continue;

    foreach ($read as $sock) {
        if ($sock === $socket) {
            $new = socket_accept($socket);
            if ($new !== false) {
                socket_set_nonblock($new);
                $clients[] = $new;
            }
        } else {
            $data = @socket_read($sock, 2048, PHP_BINARY_READ);
            if ($data === false || $data === '') {
                // desconectado
                $idx = array_search($sock, $clients, true);
                if ($idx !== false) unset($clients[$idx]);
                @socket_close($sock);
                continue;
            }
            // Handshake?
            if (strpos($data, 'Sec-WebSocket-Key') !== false) {
                $resp = wsHandshake($data);
                socket_write($sock, $resp, strlen($resp));
            } else {
                $msg = wsDecode($data);
                if ($msg !== null && $msg !== '') {
                    // Broadcast a todos
                    $frame = wsEncode($msg);
                    foreach ($clients as $c) {
                        if ($c !== $socket && $c !== $sock) {
                            @socket_write($c, $frame, strlen($frame));
                        }
                    }
                    // echo también al emisor
                    @socket_write($sock, wsEncode('{"ok":true}'), 10);
                }
            }
        }
    }
}
