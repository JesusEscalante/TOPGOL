<?php
declare(strict_types=1);

/**
 * ====================================================================
 * TOP GOL - PRUEBAS DE RESERVA ATÓMICA (evitar doble reserva)
 * ====================================================================
 * Usa la base de datos local con filas de prueba ("PRUEBA P12") en una fecha lejana y las borra al final.
 * Ejecutar:  php tests/run_reserva_atomica_tests.php
 */
$base = dirname(__DIR__);
require_once $base . '/config/config.php';
require_once $base . '/app/Config/Database.php';
require_once $base . '/app/Core/Model.php';
require_once $base . '/app/Helpers/helpers.php';
require_once $base . '/app/Models/Cancha.php';
require_once $base . '/app/Models/Pago.php';
require_once $base . '/app/Models/Reserva.php';
require_once $base . '/app/Models/Espacio.php';

use App\Models\Espacio;
use App\Models\Reserva;

$pdo = App\Config\Database::getConnection();
$reserva = new Reserva();
$espacio = new Espacio();
$fallos = 0;
$t = function (string $nombre, bool $ok, string $detalle = '') use (&$fallos): void {
    echo ($ok ? ' [OK]   ' : ' [FAIL] ') . $nombre . ($detalle !== '' ? " -> {$detalle}" : '') . "\n";
    if (!$ok) { $fallos++; }
};
$usuario = (int)$pdo->query('SELECT id FROM usuarios ORDER BY id LIMIT 1')->fetchColumn();
$dato = static fn(int $cancha, string $fecha, string $ini, string $fin, string $estado = 'pendiente', ?string $grupo = null): array => [
    'usuario_id' => $usuario, 'cancha_id' => $cancha, 'fecha' => $fecha, 'hora_inicio' => $ini, 'hora_fin' => $fin,
    'duracion_horas' => 1, 'total_pago' => 60, 'estado' => $estado, 'cliente_nombre' => 'PRUEBA P12', 'metodo_pago' => 'yape',
    'origen' => 'web', 'adelanto_monto' => 20, 'pago_estado' => 'en_revision', 'evento_grupo' => $grupo,
];
$contar = static fn(string $where): int => (int)$GLOBALS['pdo']->query("SELECT COUNT(*) FROM reservas WHERE {$where}")->fetchColumn();
$limpiar = function () use ($pdo): void {
    $pdo->exec("DELETE FROM pagos WHERE reserva_id IN (SELECT id FROM reservas WHERE cliente_nombre LIKE 'PRUEBA P12%')");
    $pdo->exec("DELETE FROM reservas WHERE cliente_nombre LIKE 'PRUEBA P12%'");
    $pdo->exec("DELETE FROM solicitudes_espacios WHERE contacto_nombre LIKE 'PRUEBA P12%'");
};
$limpiar();
$antes = [(int)$pdo->query('SELECT COUNT(*) FROM reservas')->fetchColumn(), (int)$pdo->query('SELECT COUNT(*) FROM pagos')->fetchColumn(), (int)$pdo->query('SELECT COUNT(*) FROM solicitudes_espacios')->fetchColumn()];

echo "== 1. Dos procesos a la vez por la misma cancha e intervalo ==\n";
$php = PHP_BINARY;
$worker = __DIR__ . DIRECTORY_SEPARATOR . 'worker_reserva_atomica.php';
for ($ronda = 1; $ronda <= 5; $ronda++) {
    $fecha = date('Y-m-d', strtotime("2031-07-0{$ronda}"));
    $salida = microtime(true) + 1.5; // todos arrancan en el mismo instante
    $procs = [];
    foreach (['A', 'B', 'C'] as $etq) {
        $cmd = [$php, $worker, '6', $fecha, '18:00:00', '19:00:00', sprintf('%.4f', $salida), $etq];
        $procs[$etq] = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $procs[$etq] = [$procs[$etq], $pipes];
    }
    $resultados = [];
    foreach ($procs as $etq => [$p, $pipes]) {
        $resultados[$etq] = json_decode(trim((string)stream_get_contents($pipes[1])), true);
        fclose($pipes[1]); fclose($pipes[2]); proc_close($p);
    }
    $oks = count(array_filter($resultados, static fn($r) => is_array($r) && ($r['ok'] ?? false) === true));
    $ocupados = count(array_filter($resultados, static fn($r) => is_array($r) && ($r['codigo'] ?? '') === 'ocupado'));
    $enBd = $contar("cancha_id = 6 AND fecha = '{$fecha}' AND hora_inicio = '18:00:00' AND cliente_nombre LIKE 'PRUEBA P12%'");
    $t("ronda {$ronda}: 3 procesos simultáneos, solo 1 se guarda", $oks === 1 && $ocupados === 2 && $enBd === 1, "ok={$oks} ocupado={$ocupados} filas={$enBd}");
}

echo "\n== 2. Choques parciales, totales y reservas consecutivas ==\n";
$f = '2031-08-01';
$r0 = $reserva->crearAtomico([$dato(5, $f, '18:00:00', '19:00:00')]);
$t('reserva base 18:00-19:00 guardada', $r0['ok'] === true);
foreach ([['18:30:00', '19:30:00', 'choque parcial (empieza dentro)'], ['17:30:00', '18:30:00', 'choque parcial (termina dentro)'],
          ['18:00:00', '19:00:00', 'choque total (mismo intervalo)'], ['18:30:00', '19:00:00', 'choque total (queda dentro)'],
          ['17:00:00', '20:00:00', 'choque total (la envuelve)']] as [$i, $fi, $nom]) {
    $r = $reserva->crearAtomico([$dato(5, $f, $i, $fi)]);
    $t($nom, $r['ok'] === false && $r['codigo'] === 'ocupado');
}
$r = $reserva->crearAtomico([$dato(5, $f, '19:00:00', '20:00:00')]);
$t('consecutiva 19:00-20:00 (empieza cuando termina la otra) SÍ es válida', $r['ok'] === true);
$r = $reserva->crearAtomico([$dato(5, $f, '17:00:00', '18:00:00')]);
$t('consecutiva 17:00-18:00 (termina cuando empieza la otra) SÍ es válida', $r['ok'] === true);
$r = $reserva->crearAtomico([$dato(4, $f, '18:00:00', '19:00:00')]);
$t('otra cancha, mismo horario: válida', $r['ok'] === true);
$reserva->actualizarEstado($r0['ids'][0], 'cancelada');
$r = $reserva->crearAtomico([$dato(5, $f, '18:00:00', '19:00:00')]);
$t('una reserva cancelada libera la cancha', $r['ok'] === true);
$idNueva = $r['ids'][0];
$t('reactivar la cancelada ahora choca: no se reactiva', $reserva->actualizarEstado($r0['ids'][0], 'confirmada') === false && $reserva->ultimoCodigo === 'ocupado');
$reserva->actualizarEstado($idNueva, 'cancelada');
$t('liberado el horario, la cancelada sí se reactiva', $reserva->actualizarEstado($r0['ids'][0], 'confirmada') === true);

echo "\n== 3. Evento de varias canchas: todo o nada ==\n";
$f = '2031-08-02';
$bloq = $reserva->crearAtomico([$dato(2, $f, '20:00:00', '21:00:00')]);
$antesEvt = $contar("fecha = '{$f}' AND cliente_nombre LIKE 'PRUEBA P12%'");
$r = $reserva->crearAtomico([$dato(1, $f, '20:00:00', '21:00:00', 'confirmada', 'g1'), $dato(2, $f, '20:00:00', '21:00:00', 'confirmada', 'g1'), $dato(3, $f, '20:00:00', '21:00:00', 'confirmada', 'g1')]);
$despEvt = $contar("fecha = '{$f}' AND cliente_nombre LIKE 'PRUEBA P12%'");
$t('3 canchas con una ocupada: falla y NO se guarda ninguna', $r['ok'] === false && $r['codigo'] === 'ocupado' && $r['cancha_id'] === 2 && $despEvt === $antesEvt, "antes={$antesEvt} despues={$despEvt}");
$t('tampoco quedó ningún pago huérfano', (int)$pdo->query("SELECT COUNT(*) FROM pagos p JOIN reservas r ON r.id = p.reserva_id WHERE r.evento_grupo = 'g1'")->fetchColumn() === 0);
$r = $reserva->crearAtomico([$dato(1, $f, '20:00:00', '21:00:00', 'confirmada', 'g2'), $dato(3, $f, '20:00:00', '21:00:00', 'confirmada', 'g2'), $dato(4, $f, '20:00:00', '21:00:00', 'confirmada', 'g2')]);
$t('3 canchas libres: se guardan las 3 con su pago', $r['ok'] === true && count($r['ids']) === 3 && $contar("evento_grupo = 'g2'") === 3 && (int)$pdo->query("SELECT COUNT(*) FROM pagos p JOIN reservas r ON r.id = p.reserva_id WHERE r.evento_grupo = 'g2'")->fetchColumn() === 3);

echo "\n== 4. Mover una reserva (reprogramar / editar horario) ==\n";
$f = '2031-08-03';
$a = $reserva->crearAtomico([$dato(6, $f, '10:00:00', '11:00:00')])['ids'][0];
$b = $reserva->crearAtomico([$dato(6, $f, '12:00:00', '13:00:00')])['ids'][0];
$mover = fn(int $id, int $cancha, string $i, string $fi) => $reserva->actualizarReservaAtomica($id, ['cancha_id' => $cancha, 'fecha' => $f, 'hora_inicio' => $i, 'hora_fin' => $fi, 'duracion_horas' => 1, 'total_pago' => 60, 'estado' => 'pendiente', 'observaciones' => '', 'origen' => 'web', 'cliente_nombre' => 'PRUEBA P12', 'contacto_telefono' => '', 'adelanto_monto' => 20]);
$m = $mover($a, 6, '12:30:00', '13:30:00');
$t('mover encima de otra reserva: rechazado y no cambia', $m['ok'] === false && $m['codigo'] === 'ocupado' && $pdo->query("SELECT hora_inicio FROM reservas WHERE id = {$a}")->fetchColumn() === '10:00:00');
$t('mover sobre su propio horario (se excluye a sí misma): válido', $mover($a, 6, '10:00:00', '11:00:00')['ok'] === true);
$t('mover a un horario consecutivo (11:00-12:00): válido', $mover($a, 6, '11:00:00', '12:00:00')['ok'] === true);
$t('mover a otra cancha libre: válido', $mover($a, 5, '12:00:00', '13:00:00')['ok'] === true);

echo "\n== 5. Reprogramar (modelo) respeta choques ==\n";
$c = $reserva->crearAtomico([$dato(3, '2031-08-04', '10:00:00', '11:00:00')])['ids'][0];
$d = $reserva->crearAtomico([$dato(3, '2031-08-04', '11:00:00', '12:00:00')])['ids'][0];
$rp = $reserva->reprogramar($c, 3, '2031-08-04', '11:30', 1.0, 'prueba', $usuario, true);
$t('reprogramar encima de otra: rechazado', $rp['ok'] === false);
$rp = $reserva->reprogramar($c, 3, '2031-08-04', '12:00', 1.0, 'prueba', $usuario, true);
$t('reprogramar a un horario libre consecutivo: válido', $rp['ok'] === true, json_encode($rp));

echo "\n== 6. Solicitud de cumpleaños con canchas agregadas ==\n";
$f = '2031-08-05';
$reserva->crearAtomico([$dato(2, $f, '15:00:00', '16:00:00')]);
$sol = fn(array $canchas, string $ini, string $fin) => $espacio->guardarSolicitudAtomica([
    'espacio_id' => 1, 'usuario_id' => $usuario, 'fecha' => $f, 'hora_inicio' => $ini, 'hora_fin' => $fin, 'duracion_horas' => 1,
    'contacto_nombre' => 'PRUEBA P12', 'contacto_telefono' => '+51 900000000', 'observaciones' => 'x', 'estado' => 'pendiente', 'total_estimado' => 120,
    'canchas' => array_map(static fn($c) => ['cancha_id' => $c, 'precio_hora' => 60, 'subtotal' => 60], $canchas)]);
$antesSol = (int)$pdo->query("SELECT COUNT(*) FROM solicitudes_espacios WHERE contacto_nombre LIKE 'PRUEBA P12%'")->fetchColumn();
$r = $sol([1, 2], '15:00:00', '16:00:00');
$t('cumpleaños con una cancha ocupada: rechazado y no se guarda nada', $r['ok'] === false && $r['codigo'] === 'ocupado' && (int)$pdo->query("SELECT COUNT(*) FROM solicitudes_espacios WHERE contacto_nombre LIKE 'PRUEBA P12%'")->fetchColumn() === $antesSol);
$r = $sol([1, 3], '15:00:00', '16:00:00');
$t('cumpleaños con canchas libres: se guarda con sus canchas', $r['ok'] === true && (int)$pdo->query("SELECT COUNT(*) FROM solicitudes_espacios_canchas WHERE solicitud_id = {$r['id']}")->fetchColumn() === 2);
$r1 = $r['id'];
$c1 = $espacio->cambiarEstadoAtomico($r1, 'confirmado', null);
$t('confirmar la solicitud: válido', $c1['ok'] === true);
$r = $sol([], '15:30:00', '16:30:00');
$t('otro cumpleaños que cruza el espacio confirmado: rechazado', $r['ok'] === false && $r['codigo'] === 'ocupado');
$r = $reserva->crearAtomico([$dato(3, $f, '15:30:00', '16:30:00')]);
$t('una reserva normal en una cancha del cumpleaños confirmado: rechazada', $r['ok'] === false && $r['codigo'] === 'ocupado');
$t('reservar la cancha justo cuando termina el cumpleaños (16:00): válido', $reserva->crearAtomico([$dato(3, $f, '16:00:00', '17:00:00')])['ok'] === true);

$limpiar();
$despues = [(int)$pdo->query('SELECT COUNT(*) FROM reservas')->fetchColumn(), (int)$pdo->query('SELECT COUNT(*) FROM pagos')->fetchColumn(), (int)$pdo->query('SELECT COUNT(*) FROM solicitudes_espacios')->fetchColumn()];
$t('limpieza: la base quedó igual que al empezar', $antes === $despues, json_encode($antes) . ' vs ' . json_encode($despues));

echo $fallos === 0 ? "\nTODAS LAS PRUEBAS DE RESERVA ATÓMICA PASARON.\n" : "\nHAY {$fallos} PRUEBA(S) FALLIDA(S).\n";
exit($fallos === 0 ? 0 : 1);
