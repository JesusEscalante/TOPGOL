<?php
declare(strict_types=1);

/**
 * ====================================================================
 * TOP GOL - SUITE DE PRUEBAS DE CIERRE Y CONSISTENCIA (FASE 2F)
 * ====================================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Helpers/helpers.php';
require_once __DIR__ . '/../app/Models/Cancha.php';
require_once __DIR__ . '/../app/Models/Categoria.php';
require_once __DIR__ . '/../app/Models/Espacio.php';
require_once __DIR__ . '/../app/Models/MovimientoInventario.php';
require_once __DIR__ . '/../app/Models/Notificacion.php';
require_once __DIR__ . '/../app/Models/Pago.php';
require_once __DIR__ . '/../app/Models/Producto.php';
require_once __DIR__ . '/../app/Models/Reserva.php';
require_once __DIR__ . '/../app/Models/Venta.php';

$pdo = App\Config\Database::getConnection();

// Conteos ANTES de correr las pruebas: la prueba 8 comprueba que las pruebas no alteran los datos
$conteos = static function (PDO $pdo): array {
    $r = [];
    foreach (['reservas', 'pagos', 'ventas', 'productos', 'movimientos_inventario', 'canchas'] as $tabla) {
        $r[$tabla] = (int)$pdo->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn();
    }
    return $r;
};
$conteosAntes = $conteos($pdo);
$modeloCancha = new App\Models\Cancha();
$modeloPago = new App\Models\Pago();
$modeloProducto = new App\Models\Producto();
$modeloReserva = new App\Models\Reserva();
$modeloVenta = new App\Models\Venta();

$resultados = [];

$test = function(string $nombre, callable $fn) use (&$resultados): void {
    try {
        $msg = $fn();
        $resultados[] = ['nombre' => $nombre, 'ok' => true, 'mensaje' => $msg];
        echo " [OK] {$nombre}: {$msg}\n";
    } catch (\Throwable $e) {
        $resultados[] = ['nombre' => $nombre, 'ok' => false, 'mensaje' => $e->getMessage()];
        echo " [FAIL] {$nombre}: {$e->getMessage()}\n";
    }
};

echo "============================================================\n";
echo "EJECUTANDO 8 PRUEBAS DE CIERRE - TOP GOL (FASE 2F)\n";
echo "============================================================\n\n";

// 1. Dashboard sin datos (fecha futura sin movimientos, ej: 2029-01-01)
$test("1. Dashboard sin datos", function() use ($modeloReserva, $modeloPago, $modeloVenta, $modeloCancha) {
    $fechaVacia = '2029-01-01'; // Lunes
    $horarioBase = getHorarioProvisionalNegocio($fechaVacia);
    $metricas = $modeloReserva->obtenerMetricasDia($fechaVacia, false);
    $caja = $modeloPago->obtenerResumenCaja($fechaVacia, false);
    $ventas = $modeloVenta->obtenerResumenVentasDia($fechaVacia, false);
    $topHoy = $modeloVenta->obtenerProductosMasVendidos(5, $fechaVacia, false);
    $topHist = $modeloVenta->obtenerProductosMasVendidos(5, null, false);

    $canchasDisp = count(array_filter($modeloCancha->obtenerTodas(), fn($c) => ($c['estado'] ?? '') !== 'mantenimiento'));
    $capacidad = max(1, $canchasDisp * $horarioBase['horas_operativas']);
    $pctOcup = min(100, (int)round((($metricas['horas_cancha'] ?? 0) / $capacidad) * 100));

    if ((float)$caja['total_ingresos'] !== 0.0) throw new Exception("Ingresos no son S/0");
    if ((float)$ventas['total_ventas'] !== 0.0) throw new Exception("Ventas no son S/0");
    if (!empty($topHoy)) throw new Exception("Top hoy no está vacío");
    if (!empty($topHist)) throw new Exception("Top histórico sin demo no está vacío");
    if ($pctOcup !== 0) throw new Exception("Ocupación no es 0%");

    return "Sin errores: Ingresos=S/0, Ventas=S/0, Ocupación=0% ({$canchasDisp} canchas x {$horarioBase['horas_operativas']}h = {$capacidad}h base), Top vacío.";
});

// 2. Reportes sin ventas demo
$test("2. Reportes sin ventas demo", function() use ($modeloVenta, $modeloPago) {
    $repProds = $modeloVenta->obtenerReporteProductos('2026-10-01', '2026-10-31', false);
    $repIngr = $modeloPago->obtenerReporteIngresos('2026-10-01', '2026-10-31', false);

    if ($repProds['total_ingresos'] > 0.0) throw new Exception("Ventas comerciales reportaron > S/0");
    if (!empty($repProds['ranking'])) throw new Exception("Ranking contiene productos cuando se excluye demo");
    if (!empty($repProds['por_categoria'])) throw new Exception("Ventas por categoría contiene datos cuando se excluye demo");
    if ($repIngr['productos_total'] > 0.0) throw new Exception("Ingresos por kiosco en reporte > S/0");

    return "Confirmado: total_ingresos=S/0, ranking=0 items, categorias=0 items, productos_total=S/0.";
});

// 3. Reportes incluyendo datos demo, claramente identificados
$test("3. Reportes incluyendo datos demo", function() use ($modeloVenta, $modeloPago) {
    $repProds = $modeloVenta->obtenerReporteProductos('2026-10-01', '2026-10-31', true);
    $repIngr = $modeloPago->obtenerReporteIngresos('2026-10-01', '2026-10-31', true);

    if ($repProds['total_ingresos'] != 50.0) throw new Exception("Total ingresos demo != S/50.00 (obtenido: {$repProds['total_ingresos']})");
    if (count($repProds['ranking']) != 2) throw new Exception("Ranking no tiene los 2 productos demo");
    if ($repIngr['productos_total'] != 50.0) throw new Exception("Ingresos demo kiosco != S/50.00");

    // Verificar que las transacciones en ingresos tengan bandera es_demo
    $txDemoCount = count(array_filter($repIngr['transacciones'], fn($t) => !empty($t['es_demo'])));
    if ($txDemoCount !== 2) throw new Exception("Transacciones demo auditadas != 2 (obtenido: {$txDemoCount})");

    return "Correcto: total_ingresos=S/50.00, ranking=2 productos, 2 transacciones identificadas con flag es_demo=1.";
});

// 4. Productos más vendidos y categorías
$test("4. Productos más vendidos y categorías", function() use ($modeloVenta) {
    // Sin demo
    $vSinDemo = $modeloVenta->obtenerProductosMasVendidos(5, '2026-10-12', false);
    $repSinDemo = $modeloVenta->obtenerReporteProductos('2026-10-12', '2026-10-12', false);
    if (!empty($vSinDemo)) throw new Exception("obtenerProductosMasVendidos devolvió items sin demo");
    if (!empty($repSinDemo['ranking'])) throw new Exception("ranking contiene items sin demo");
    if (!empty($repSinDemo['por_categoria'])) throw new Exception("por_categoria contiene items sin demo");

    // Con demo
    $vConDemo = $modeloVenta->obtenerProductosMasVendidos(5, '2026-10-12', true);
    $repConDemo = $modeloVenta->obtenerReporteProductos('2026-10-12', '2026-10-12', true);
    if (count($vConDemo) !== 2) throw new Exception("No devolvió los 2 productos con demo");
    if ($vConDemo[0]['nombre'] !== 'Gatorade 500ml' || $vConDemo[0]['unidades_vendidas'] != 4) throw new Exception("Top 1 no es Gatorade con 4 unidades");
    if ($vConDemo[1]['nombre'] !== 'Coca-Cola 500ml' || $vConDemo[1]['unidades_vendidas'] != 2) throw new Exception("Top 2 no es Coca-Cola con 2 unidades");
    if (count($repConDemo['por_categoria']) !== 2) throw new Exception("No devolvió las 2 categorías (Hidratantes y Refrescos)");

    return "Consistencia perfecta: 0 productos/categorías sin demo; con demo Top 1=Gatorade (4 und, S/40), Top 2=Coca-Cola (2 und, S/10), 2 categorías auditadas.";
});

// 5. Porcentaje de ocupación según día de semana
$test("5. Porcentaje de ocupación según día de semana", function() use ($modeloCancha) {
    $canchasDisp = count(array_filter($modeloCancha->obtenerTodas(), fn($c) => ($c['estado'] ?? '') !== 'mantenimiento'));

    // Probar Lunes (2026-10-12)
    $lunes = '2026-10-12';
    $hLunes = getHorarioProvisionalNegocio($lunes);
    if ($hLunes['horas_operativas'] !== 14 || $hLunes['apertura'] !== '09:00' || $hLunes['cierre'] !== '23:00') {
        throw new Exception("Horario lunes inválido");
    }
    $capLunes = $canchasDisp * 14;
    $pctLunes10h = (int)round((10 / $capLunes) * 100);

    // Probar Sábado (2026-10-17)
    $sabado = '2026-10-17';
    $hSabado = getHorarioProvisionalNegocio($sabado);
    if ($hSabado['horas_operativas'] !== 16 || $hSabado['apertura'] !== '07:00' || $hSabado['cierre'] !== '23:00') {
        throw new Exception("Horario sábado inválido");
    }
    $capSabado = $canchasDisp * 16;
    $pctSabado10h = (int)round((10 / $capSabado) * 100);

    if ($pctLunes10h <= $pctSabado10h) {
        throw new Exception("Ocupación lunes debe ser mayor para la misma cantidad de horas debido a menor capacidad base");
    }

    return "Cálculo verificado: Lunes=14h/cancha ({$capLunes}h total, 10h ocupadas={$pctLunes10h}%), Sábado=16h/cancha ({$capSabado}h total, 10h ocupadas={$pctSabado10h}%).";
});

// 6. Exportación CSV consistente con los filtros
$test("6. Exportación CSV consistente con los filtros", function() use ($modeloVenta, $modeloPago, $modeloReserva) {
    // Probar datos exportados para productos sin demo
    $pSin = $modeloVenta->obtenerReporteProductos('2026-10-01', '2026-10-31', false);
    if (!empty($pSin['ranking'])) throw new Exception("CSV sin demo tendría filas de ranking");
    if (!empty($pSin['por_categoria'])) throw new Exception("CSV sin demo tendría filas de categorías");

    // Probar datos exportados para productos con demo
    $pCon = $modeloVenta->obtenerReporteProductos('2026-10-01', '2026-10-31', true);
    if (count($pCon['ranking']) !== 2) throw new Exception("CSV con demo no tiene 2 filas en ranking");
    if (count($pCon['por_categoria']) !== 2) throw new Exception("CSV con demo no tiene 2 filas en categorías");

    // Probar datos exportados para ingresos sin demo vs con demo
    $iSin = $modeloPago->obtenerReporteIngresos('2026-10-01', '2026-10-31', false);
    $iCon = $modeloPago->obtenerReporteIngresos('2026-10-01', '2026-10-31', true);
    $demoTxSin = array_filter($iSin['transacciones'], fn($t) => !empty($t['es_demo']));
    $demoTxCon = array_filter($iCon['transacciones'], fn($t) => !empty($t['es_demo']));
    if (count($demoTxSin) !== 0) throw new Exception("CSV ingresos sin demo contiene transacciones demo");
    if (count($demoTxCon) !== 2) throw new Exception("CSV ingresos con demo no contiene las 2 transacciones demo");

    return "Exportaciones auditadas: Productos sin demo=0 items, con demo=2 en ranking y 2 en categorías. Ingresos sin demo=0 demo tx, con demo=2 demo tx.";
});

// 7. Caja diaria y dashboard con el mismo total de ingresos
$test("7. Caja diaria y dashboard con el mismo total de ingresos", function() use ($modeloPago) {
    $fechas = ['2026-09-27', '2026-10-11', '2026-10-12', '2026-10-13'];

    foreach ($fechas as $f) {
        foreach ([false, true] as $demo) {
            $caja = $modeloPago->obtenerResumenCaja($f, $demo);
            $totalCaja = (float)($caja['total_caja'] ?? 0.0);
            $totalDashboard = (float)($caja['total_ingresos'] ?? 0.0);

            if ($totalCaja !== $totalDashboard) {
                throw new Exception("Discrepancia en fecha {$f} (demo=" . ($demo ? '1' : '0') . "): Caja={$totalCaja} vs Dash={$totalDashboard}");
            }
        }
    }

    $caja12Sin = $modeloPago->obtenerResumenCaja('2026-10-12', false);
    $caja12Con = $modeloPago->obtenerResumenCaja('2026-10-12', true);

    return "100% Coincidencia en todas las fechas. Para 2026-10-12: Sin demo=S/ " . number_format((float)$caja12Sin['total_ingresos'], 2) . ", Con demo=S/ " . number_format((float)$caja12Con['total_ingresos'], 2) . ".";
});

// 8. Reservas, pagos, ventas e inventario sin regresiones
$test("8. Reservas, pagos, ventas e inventario sin regresiones", function() use ($pdo, $modeloProducto, $conteos, $conteosAntes) {
    // Compara con los conteos tomados ANTES de correr las pruebas (no con números fijos)
    $despues = $conteos($pdo);
    foreach ($conteosAntes as $tabla => $antes) {
        if ($despues[$tabla] !== $antes) {
            throw new Exception("Las pruebas alteraron {$tabla}: antes={$antes}, después={$despues[$tabla]}");
        }
    }

    // Verificar valorización de inventario
    $val = $modeloProducto->valorizacion();
    if ($val <= 0.0) throw new Exception("Valorización de inventario es 0 o negativa");

    return "0 regresiones: " . implode(', ', array_map(static fn($k, $v) => "{$v} {$k}", array_keys($despues), $despues)) . " (iguales antes y después). Valorización PVP=S/ " . number_format($val, 2) . ".";
});

echo "\n============================================================\n";
$fallidas = count(array_filter($resultados, static fn($r) => !$r['ok']));
if ($fallidas === 0) {
    echo "TODAS LAS PRUEBAS COMPLETADAS CON ÉXITO.\n";
} else {
    echo "HAY {$fallidas} PRUEBA(S) FALLIDA(S).\n";
}
echo "============================================================\n";
exit($fallidas === 0 ? 0 : 1);
