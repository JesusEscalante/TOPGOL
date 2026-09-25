<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notificacion;

class NotificacionController extends Controller {

    public function listar(): void {
        $this->requireAdmin();
        $m = new Notificacion();
        $uid = (int)$_SESSION['usuario_id'];
        $this->json([
            'count' => $m->contarNoLeidas($uid),
            'items' => $m->listar($uid, 10)
        ]);
    }

    public function leer(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();
        $m = new Notificacion();
        $m->marcarLeida((int)$id, (int)$_SESSION['usuario_id']);
        $this->json(['ok' => true]);
    }

    public function leerTodas(): void {
        $this->requireAdmin();
        $this->validateCsrf();
        $m = new Notificacion();
        $m->marcarTodasLeidas((int)$_SESSION['usuario_id']);
        $this->json(['ok' => true]);
    }

    /** Stream SSE para push en tiempo real (alternativa ligera a WebSockets) */
    public function stream(): void {
        $this->requireAdmin();
        // SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        set_time_limit(0);
        ignore_user_abort(false);

        $m = new Notificacion();
        $uid = (int)$_SESSION['usuario_id'];
        $lastId = (int)($_GET['last'] ?? 0);

        // Enviar estado inicial
        $count = $m->contarNoLeidas($uid);
        echo "event: count\n";
        echo "data: " . json_encode(['count' => $count]) . "\n\n";
        @ob_flush(); @flush();

        $start = time();
        // Mantener conexión hasta 25s (evita timeout de proxy), el cliente reconecta
        while ((time() - $start) < 25) {
            if (connection_aborted()) break;
            // Buscar nuevas notificaciones desde lastId
            $nuevas = $m->select("SELECT * FROM notificaciones WHERE usuario_id = ? AND id > ? ORDER BY id ASC", [$uid, $lastId]);
            foreach ($nuevas as $n) {
                $lastId = (int)$n['id'];
                echo "event: notificacion\n";
                echo "data: " . json_encode($n, JSON_UNESCAPED_UNICODE) . "\n\n";
            }
            if (!empty($nuevas)) {
                $count = $m->contarNoLeidas($uid);
                echo "event: count\n";
                echo "data: " . json_encode(['count' => $count]) . "\n\n";
            } else {
                echo ": ping\n\n";
            }
            @ob_flush(); @flush();
            sleep(2);
        }
    }
}
