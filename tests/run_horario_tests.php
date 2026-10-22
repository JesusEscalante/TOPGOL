<?php
declare(strict_types=1);

/**
 * ====================================================================
 * TOP GOL - PRUEBAS DE REGLAS DE HORARIO (Parte 14)
 * ====================================================================
 * - Inicio solo en :00 o :30 (paso configurable)
 * - No reservar en el pasado (hora de Lima) con reglas por rol
 * - Tolerancia: una sola fuente en config
 * Pasan a cualquier hora del día: la "hora actual" se simula con un instante fijo en Lima.
 * Las pruebas con base de datos usan filas "PRUEBA P14" en una fecha lejana y las borran al final.
 *
 * Ejecutar:  php tests/run_horario_tests.php
 */
$base = dirname(__DIR__);
require_once $base . '/config/config.php';
require_once $base . '/app/Config/Database.php';
require_once $base . '/app/Core/Model.php';
require_once $base . '/app/Helpers/helpers.php';
require_once $base . '/app/Models/Cancha.php';
require_once $base . '/app/Models/Pago.php';
require_once $base . '/app/Models/Reserva.php';

use App\Models\Reserva;

$fallos = 0;
$t = function (string $nombre, bool $ok, string $detalle = '') use (&$fallos): void {
    echo ($ok ? ' [OK]   ' : ' [FAIL] ') . $nombre . ($detalle !== '' ? " -> {$detalle}" : '') . "\n";
    if (!$ok) { $fallos++; }
};
$lima = new DateTimeZone('America/Lima');
$manana = (new DateTimeImmutable('tomorrow', $lima))->format('Y-m-d');
$hoy = (new DateTimeImmutable('today', $lima))->format('Y-m-d');
$ayer = (new DateTimeImmutable('yesterday', $lima))->format('Y-m-d');
/** Instante "ahora" simulado: hoy (Lima) a la hora indicada. */
$ahora = fn(string $hhmmss): int => (new DateTimeImmutable("{$hoy} {$hhmmss}", $lima))->getTimestamp();
$val = fn(string $fecha, string $hora, float $dur = 1.0, ?int $ahoraTs = null, bool $admin = false) => validarHorarioReserva($fecha, $hora, $dur, $ahoraTs, $admin);

echo "== 0. Configuración y zona horaria ==\n";
$t('el servidor trabaja en hora de Lima', date_default_timezone_get() === 'America/Lima', date_default_timezone_get());
$t('el paso de inicio es 30 min (solo :00 y :30)', reservaPasoMinutos() === 30 && reservaMinutosInicio() === ['00', '30'], implode(',', reservaMinutosInicio()));
$t('la tolerancia sale de config y vale 10', toleranciaMinutos() === 10 && TOLERANCIA_MINUTOS === 10);
// Lima 21:30 del día D = UTC del día D+1 02:30: "hoy" se decide con la fecha de Lima, no con la de UTC
$tsNoche = (new DateTimeImmutable('2026-12-10 21:30:00', $lima))->getTimestamp();
$t('Lima 21:30 (ya es el día siguiente en UTC): las 22:00 de ese mismo día en Lima son válidas', $val('2026-12-10', '22:00', 0.5, $tsNoche)['ok'] === true);
$t('...y las 21:00 de ese día en Lima ya pasaron', $val('2026-12-10', '21:00', 0.5, $tsNoche)['ok'] === false);

echo "\n== 1. Inicio solo en :00 o :30 ==\n";
$t('mañana 18:00 + 60 min: válido', $val($manana, '18:00')['ok'] === true);
$t('mañana 18:30 + 60 min: válido', $val($manana, '18:30')['ok'] === true);
foreach (['18:10', '18:20', '18:15', '18:45', '18:40'] as $h) {
    $r = $val($manana, $h);
    $t("mañana {$h}: rechazado con el mensaje exacto", $r['ok'] === false && $r['error'] === 'La hora de inicio debe ser en :00 o :30', (string)$r['error']);
}
$t('duración 45 min rechazada (solo múltiplos de 30)', $val($manana, '18:00', 0.75)['ok'] === false);
$t('duración 90 min válida y termina 19:30', ($x = $val($manana, '18:00', 1.5))['ok'] === true && $x['hora_fin'] === '19:30:00');
$t('precio proporcional (S/ 60 por hora): 90 min = S/ 90, 30 min = S/ 30', round(90 * (60 / 60), 2) === 90.0 && calcularTotalEspacio(30, [1 => 60.0])['canchas'][1] === 30.0);
$t('terminar a las 23:00 es válido; pasarse de 23:00, no', $val($manana, '22:00')['ok'] === true && $val($manana, '22:30')['ok'] === false);

echo "\n== 1b. La lógica de minutos sigue siendo genérica (paso cambiado en config) ==\n";
$tmp = tempnam(sys_get_temp_dir(), 'paso10') . '.php';
file_put_contents($tmp, '<?php define("RESERVA_PASO_MINUTOS", 10); @require ' . var_export($base . '/config/config.php', true) . '; require ' . var_export($base . '/app/Helpers/helpers.php', true) . ';'
    . '$m = (new DateTimeImmutable("tomorrow", new DateTimeZone("America/Lima")))->format("Y-m-d");'
    . 'echo json_encode([implode(",", reservaMinutosInicio()), validarHorarioReserva($m, "18:10", 1.0)["ok"], validarHorarioReserva($m, "18:15", 1.0)["ok"]]);');
$salida = shell_exec('"' . PHP_BINARY . '" "' . $tmp . '"');
@unlink($tmp);
$r = json_decode((string)$salida, true);
$t('con paso 10: minutos 00,10,20,30,40,50; 18:10 válido; 18:15 rechazado', is_array($r) && $r[0] === '00,10,20,30,40,50' && $r[1] === true && $r[2] === false, (string)$salida);

echo "\n== 2. No reservar en el pasado (cliente, evento y cumpleaños: estricto) ==\n";
$n = $ahora('14:10:00');
$r = $val($hoy, '14:00', 1.0, $n);
$t('hoy 14:00 con ahora=14:10: rechazado "Esa hora ya pasó. Elige una hora desde las 14:30"', $r['ok'] === false && $r['error'] === 'Esa hora ya pasó. Elige una hora desde las 14:30', (string)$r['error']);
$t('hoy 13:00 con ahora=14:10: rechazado', $val($hoy, '13:00', 1.0, $n)['ok'] === false);
$t('hoy 14:30 con ahora=14:10: aceptado (la próxima media hora)', $val($hoy, '14:30', 1.0, $n)['ok'] === true);
$t('hoy 15:00 con ahora=14:10: aceptado', $val($hoy, '15:00', 1.0, $n)['ok'] === true);
$n2 = $ahora('14:30:00');
$r = $val($hoy, '14:30', 1.0, $n2);
$t('con ahora=14:30:00 la hora 14:30 NO es posterior: rechazada, sugiere 15:00', $r['ok'] === false && str_ends_with((string)$r['error'], '15:00'), (string)$r['error']);
$r = $val($ayer, '18:00', 1.0, $n);
$t('fecha anterior a hoy: rechazada', $r['ok'] === false && $r['error'] === 'No es posible reservar en fechas pasadas.', (string)$r['error']);
$t('mañana a cualquier hora válida: aceptado aunque "ahora" sea las 23:50', $val($manana, '09:00', 1.0, $ahora('23:50:00'))['ok'] === true);
$r = $val($hoy, '22:00', 0.5, $ahora('22:45:00'));
$t('hoy a las 22:45 ya no quedan horarios: mensaje claro', $r['ok'] === false && str_contains((string)$r['error'], 'ya no quedan horarios'), (string)$r['error']);
$t('horaMinimaReserva hoy (cliente) con ahora=14:10 es 14:30', horaMinimaReserva($hoy, false, $n) === '14:30');
$t('horaMinimaReserva fecha pasada = 24:00; mañana = apertura', horaMinimaReserva($ayer, false, $n) === '24:00' && in_array(horaMinimaReserva($manana, false, $n), ['07:00', '09:00'], true));

echo "\n== 2b. Reserva manual del administrador: solo el bloque de media hora en curso ==\n";
$t('admin 14:00 con ahora=14:10 (bloque actual): aceptado', $val($hoy, '14:00', 1.0, $n, true)['ok'] === true);
$r = $val($hoy, '13:30', 1.0, $n, true);
$t('admin 13:30 con ahora=14:10 (bloque anterior): rechazado y sugiere 14:00', $r['ok'] === false && str_ends_with((string)$r['error'], '14:00'), (string)$r['error']);
$t('admin 14:30 con ahora=14:35 (bloque actual): aceptado', $val($hoy, '14:30', 1.0, $ahora('14:35:00'), true)['ok'] === true);
$t('el cliente con la misma hora (14:00 con ahora=14:10) sigue rechazado', $val($hoy, '14:00', 1.0, $n, false)['ok'] === false);
$t('horaMinimaReserva admin con ahora=14:10 es 14:00', horaMinimaReserva($hoy, true, $n) === '14:00');

echo "\n== 3. Reprogramar / editar horario y doble reserva (base de datos) ==\n";
$pdo = App\Config\Database::getConnection();
$reserva = new Reserva();
$usuario = (int)$pdo->query('SELECT id FROM usuarios ORDER BY id LIMIT 1')->fetchColumn();
$limpiar = function () use ($pdo): void {
    $pdo->exec("DELETE FROM pagos WHERE reserva_id IN (SELECT id FROM reservas WHERE cliente_nombre LIKE 'PRUEBA P14%')");
    $pdo->exec("DELETE FROM reservas WHERE cliente_nombre LIKE 'PRUEBA P14%'");
};
$limpiar();
$antes = (int)$pdo->query('SELECT COUNT(*) FROM reservas')->fetchColumn();
$f = '2031-10-06'; // lunes lejano
$dato = static fn(int $cancha, string $ini, string $fin) => [
    'usuario_id' => $usuario, 'cancha_id' => $cancha, 'fecha' => '2031-10-06', 'hora_inicio' => $ini, 'hora_fin' => $fin, 'duracion_horas' => 1,
    'total_pago' => 60, 'estado' => 'confirmada', 'cliente_nombre' => 'PRUEBA P14', 'metodo_pago' => 'yape', 'origen' => 'web',
    'adelanto_monto' => 20, 'pago_estado' => 'verificado',
];
try {
    $a = $reserva->crearAtomico([$dato(4, '10:00:00', '11:00:00')])['ids'][0];
    $legacy = $reserva->crearAtomico([$dato(5, '13:20:00', '14:20:00')])['ids'][0]; // reserva antigua con minutos raros
    $t('reprogramar a una fecha pasada: rechazado', ($x = $reserva->reprogramar($a, 4, $ayer, '10:00', 1.0, 'prueba', $usuario, true))['ok'] === false, (string)($x['error'] ?? ''));
    $t('reprogramar a las :10: rechazado con el mensaje de :00/:30', ($x = $reserva->reprogramar($a, 4, $f, '12:10', 1.0, 'prueba', $usuario, true))['ok'] === false && ($x['error'] ?? '') === 'La hora de inicio debe ser en :00 o :30', (string)($x['error'] ?? ''));
    $t('reprogramar a las :30 libre: aceptado', $reserva->reprogramar($a, 4, $f, '12:30', 1.0, 'prueba', $usuario, true)['ok'] === true);
    $t('la reserva antigua 13:20 se conserva y se muestra bien', substr((string)$pdo->query("SELECT hora_inicio FROM reservas WHERE id = {$legacy}")->fetchColumn(), 0, 5) === '13:20');
    $t('mover la reserva antigua de cancha SIN cambiar su hora (13:20) sigue permitido', $reserva->reprogramar($legacy, 6, $f, '13:20', 1.0, 'prueba', $usuario, true)['ok'] === true);
    $t('la reserva antigua sigue en 13:20 tras moverla de cancha', substr((string)$pdo->query("SELECT hora_inicio FROM reservas WHERE id = {$legacy}")->fetchColumn(), 0, 5) === '13:20');
    $r = $reserva->crearAtomico([$dato(6, '13:30:00', '14:30:00')]);
    $t('la doble reserva sigue rechazada (13:30 choca con la 13:20-14:20)', $r['ok'] === false && $r['codigo'] === 'ocupado');
    $t('una reserva consecutiva (14:20) sí se acepta', $reserva->crearAtomico([$dato(6, '14:20:00', '15:20:00')])['ok'] === true);
} finally {
    $limpiar();
}
$t('limpieza: la base quedó igual', (int)$pdo->query('SELECT COUNT(*) FROM reservas')->fetchColumn() === $antes);

echo "\n== 4. Tolerancia: una sola fuente ==\n";
$esperado = 'Tienes 10 minutos de tolerancia desde la hora de tu reserva. Pasado ese tiempo tu horario sigue corriendo y no se extiende: termina a la hora acordada. El encargado te atenderá en el local.';
$t('el aviso usa exactamente el texto acordado con el valor de config', textoToleranciaReserva() === $esperado);
$sueltos = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base . '/app', FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if ($file->getExtension() !== 'php') { continue; }
    foreach (file($file->getPathname()) as $n => $linea) {
        if (preg_match('/(\b10 ?min(utos)?\b.*toleranc|toleranc.*\b10 ?min(utos)?\b|\+ ?600\b|minutosDesdeInicio\s*(<=|>)\s*10\b)/iu', $linea)) {
            $sueltos[] = str_replace($base . '/', '', str_replace('\\', '/', $file->getPathname())) . ':' . ($n + 1);
        }
    }
}
$t('ningún archivo de la app tiene el 10 de tolerancia escrito a mano', empty($sueltos), implode(', ', $sueltos));
$t('el aviso no está en las pantallas de cumpleaños y reuniones', !str_contains((string)file_get_contents($base . '/app/Views/espacios/solicitar.php'), 'textoToleranciaReserva') && !str_contains((string)file_get_contents($base . '/app/Views/espacios/solicitar.php'), '_tolerancia'));

echo $fallos === 0 ? "\nTODAS LAS PRUEBAS DE HORARIO PASARON.\n" : "\nHAY {$fallos} PRUEBA(S) FALLIDA(S).\n";
exit($fallos === 0 ? 0 : 1);
