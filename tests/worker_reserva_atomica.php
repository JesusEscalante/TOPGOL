<?php
declare(strict_types=1);

/**
 * Proceso de apoyo para run_reserva_atomica_tests.php: intenta reservar una cancha en un intervalo
 * en el instante exacto indicado (así dos procesos compiten de verdad por el mismo horario).
 *
 * Uso: php worker_reserva_atomica.php <cancha_id> <fecha> <hora_ini> <hora_fin> <inicio_microtime> <etiqueta>
 * Imprime una línea JSON con el resultado.
 */
$base = dirname(__DIR__);
require_once $base . '/config/config.php';
require_once $base . '/app/Config/Database.php';
require_once $base . '/app/Core/Model.php';
require_once $base . '/app/Helpers/helpers.php';
require_once $base . '/app/Models/Cancha.php';
require_once $base . '/app/Models/Pago.php';
require_once $base . '/app/Models/Reserva.php';

[$script, $canchaId, $fecha, $ini, $fin, $inicio, $etiqueta] = $argv + [null, 1, '', '', '', '0', 'worker'];

$reserva = new App\Models\Reserva();
$usuario = (int)App\Config\Database::getConnection()->query('SELECT id FROM usuarios ORDER BY id LIMIT 1')->fetchColumn();

// Espera activa hasta el instante de salida común
while (microtime(true) < (float)$inicio) {
    usleep(200);
}

$res = $reserva->crearAtomico([[
    'usuario_id' => $usuario, 'cancha_id' => (int)$canchaId, 'fecha' => $fecha,
    'hora_inicio' => $ini, 'hora_fin' => $fin, 'duracion_horas' => 1, 'total_pago' => 60, 'estado' => 'pendiente',
    'cliente_nombre' => 'PRUEBA P12 ' . $etiqueta, 'metodo_pago' => 'yape', 'origen' => 'web',
    'adelanto_monto' => 20, 'pago_estado' => 'en_revision', 'contacto_telefono' => '+51 900000000',
]]);
echo json_encode(['etiqueta' => $etiqueta, 'ok' => $res['ok'], 'codigo' => $res['codigo'], 'ids' => $res['ids']]) . "\n";
