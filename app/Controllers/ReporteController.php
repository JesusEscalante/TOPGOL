<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cancha;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Venta;

/**
 * ====================================================================
 * TOP GOL - Controlador de Reportes y Consolidación de Datos (Fase 2F)
 * ====================================================================
 * Proporciona reportes analíticos para la toma de decisiones:
 *  1. Reporte Operativo de Reservas (canchas, canales, horas pico, eventos)
 *  2. Reporte Financiero de Ingresos (cobros auditados, métodos, saldos)
 *  3. Reporte Comercial de Productos e Inventario (ranking, categorías, PVP)
 *  4. Exportación en CSV compatible con Microsoft Excel (UTF-8 con BOM)
 */
class ReporteController extends Controller {

    /**
     * Pantalla principal con pestañas de reportes, filtros de fechas y gráficos
     */
    public function index(): void {
        $this->requireAdmin();

        $modeloReserva = new Reserva();
        $modeloPago = new Pago();
        $modeloVenta = new Venta();
        $modeloProducto = new Producto();
        $modeloCancha = new Cancha();

        // Pestaña activa ('reservas', 'ingresos', 'productos')
        $tab = trim((string)($_GET['tab'] ?? 'reservas'));
        if (!in_array($tab, ['reservas', 'ingresos', 'productos'], true)) {
            $tab = 'reservas';
        }

        // Periodo y fechas
        $hoy = date('Y-m-d');
        $periodo = trim((string)($_GET['periodo'] ?? 'mes'));
        if (!in_array($periodo, ['hoy', 'semana', 'mes', 'personalizado'], true)) {
            $periodo = 'mes';
        }

        $desde = '';
        $hasta = '';

        switch ($periodo) {
            case 'hoy':
                $desde = $hoy;
                $hasta = $hoy;
                break;
            case 'semana':
                $tsHoy = strtotime($hoy);
                $lunes = date('Y-m-d', strtotime($hoy . ' -' . (date('N', $tsHoy) - 1) . ' days'));
                $desde = $lunes;
                $hasta = date('Y-m-d', strtotime($lunes . ' +6 days'));
                break;
            case 'mes':
                $desde = date('Y-m-01');
                $hasta = date('Y-m-t');
                break;
            case 'personalizado':
                $desde = trim((string)($_GET['desde'] ?? date('Y-m-01')));
                $hasta = trim((string)($_GET['hasta'] ?? $hoy));
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
                    $desde = date('Y-m-01');
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
                    $hasta = $hoy;
                }
                if ($desde > $hasta) {
                    $temp = $desde;
                    $desde = $hasta;
                    $hasta = $temp;
                }
                break;
        }

        $incluirDemo = ((int)($_GET['demo'] ?? 0) === 1);

        // Filtros adicionales para reservas
        $filtrosReservas = [];
        if (!empty($_GET['cancha_id'])) {
            $filtrosReservas['cancha_id'] = (int)$_GET['cancha_id'];
        }
        if (!empty($_GET['origen'])) {
            $filtrosReservas['origen'] = trim((string)$_GET['origen']);
        }
        if (!empty($_GET['estado'])) {
            $filtrosReservas['estado'] = trim((string)$_GET['estado']);
        }

        // Obtener datos de los modelos según la pestaña consultada
        $datosReservas = $modeloReserva->obtenerReporteReservas($desde, $hasta, $filtrosReservas, $incluirDemo);
        $datosIngresos = $modeloPago->obtenerReporteIngresos($desde, $hasta, $incluirDemo);
        $datosProductos = $modeloVenta->obtenerReporteProductos($desde, $hasta, $incluirDemo);

        $canchas = $modeloCancha->obtenerTodas();

        $this->view('reportes/index', [
            'titulo'          => 'Reportes y Consolidación - ' . APP_NAME,
            'adminMenu'       => 'reportes',
            'tab'             => $tab,
            'periodo'         => $periodo,
            'desde'           => $desde,
            'hasta'           => $hasta,
            'hoy'             => $hoy,
            'incluirDemo'     => $incluirDemo,
            'filtrosReservas' => $filtrosReservas,
            'canchas'         => $canchas,
            'reporteReservas' => $datosReservas,
            'reporteIngresos' => $datosIngresos,
            'reporteProductos'=> $datosProductos,
        ], false);
    }

    /**
     * Exporta el reporte solicitado a CSV compatible con Microsoft Excel (BOM UTF-8 y delimitador ;)
     */
    public function exportar(): void {
        $this->requireAdmin();

        $tipo = trim((string)($_GET['tipo'] ?? 'reservas'));
        if (!in_array($tipo, ['reservas', 'ingresos', 'productos'], true)) {
            $tipo = 'reservas';
        }

        $desde = trim((string)($_GET['desde'] ?? date('Y-m-01')));
        $hasta = trim((string)($_GET['hasta'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $desde = date('Y-m-01');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $hasta = date('Y-m-d');
        }
        if ($desde > $hasta) {
            $temp = $desde;
            $desde = $hasta;
            $hasta = $temp;
        }

        $incluirDemo = ((int)($_GET['demo'] ?? 0) === 1);

        $nombreArchivo = "topgol_reporte_{$tipo}_{$desde}_a_{$hasta}.csv";

        // Configuración de encabezados HTTP para descarga forzada de CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        // Escribir UTF-8 BOM para que Microsoft Excel en español abra los acentos sin problemas
        fwrite($out, "\xEF\xBB\xBF");

        $delimitador = ';';

        switch ($tipo) {
            case 'reservas':
                $modeloReserva = new Reserva();
                $filtros = [];
                if (!empty($_GET['cancha_id'])) {
                    $filtros['cancha_id'] = (int)$_GET['cancha_id'];
                }
                if (!empty($_GET['origen'])) {
                    $filtros['origen'] = trim((string)$_GET['origen']);
                }
                if (!empty($_GET['estado'])) {
                    $filtros['estado'] = trim((string)$_GET['estado']);
                }

                $rep = $modeloReserva->obtenerReporteReservas($desde, $hasta, $filtros, $incluirDemo);

                // Metadatos
                fputcsv($out, ['TOP GOL - REPORTE DE RESERVAS DE CANCHAS'], $delimitador);
                fputcsv($out, ['Periodo:', "{$desde} al {$hasta}"], $delimitador);
                fputcsv($out, ['Filtro Datos:', $incluirDemo ? 'Incluye transacciones de demostración' : 'Solo operaciones comerciales reales'], $delimitador);
                fputcsv($out, ['Total Reservas:', $rep['total_reservas']], $delimitador);
                fputcsv($out, ['Solicitudes Únicas (Eventos Agrupados):', $rep['solicitudes_unicas']], $delimitador);
                fputcsv($out, ['Horas-Cancha Contratadas:', number_format((float)$rep['horas_cancha'], 2)], $delimitador);
                fputcsv($out, ['Monto Total Contratado (S/):', number_format((float)$rep['total_contratado'], 2)], $delimitador);
                fputcsv($out, ['Adelantos Cobrados (S/):', number_format((float)$rep['adelantos_verificados'], 2)], $delimitador);
                fputcsv($out, ['Saldo Pendiente (S/):', number_format((float)$rep['saldo_pendiente_total'], 2)], $delimitador);
                fputcsv($out, [], $delimitador);

                // Encabezados de tabla detallada
                fputcsv($out, [
                    'ID Reserva',
                    'Fecha',
                    'Hora Inicio',
                    'Hora Fin',
                    'Horas-Cancha',
                    'Cancha',
                    'Tipo Cancha',
                    'Cliente',
                    'Teléfono',
                    'Email',
                    'Canal Origen',
                    'Grupo / Evento',
                    'Estado Reserva',
                    'Estado Pago',
                    'Total Contratado (S/)',
                    'Adelanto Pagado (S/)',
                    'Saldo Pendiente (S/)',
                    'Es Demo'
                ], $delimitador);

                foreach ($rep['lista'] as $r) {
                    fputcsv($out, [
                        $r['id'],
                        $r['fecha'],
                        $r['hora_inicio'],
                        $r['hora_fin'],
                        number_format((float)$r['duracion_horas'], 2),
                        $r['cancha_nombre'],
                        $r['cancha_tipo'],
                        $r['cliente_nombre'],
                        $r['cliente_telefono'] ?? '-',
                        $r['cliente_email'],
                        ucfirst((string)$r['origen']),
                        $r['evento_grupo'] ? "Evento ({$r['evento_grupo']})" : 'Individual',
                        ucfirst((string)$r['estado']),
                        ucfirst((string)$r['pago_estado']),
                        number_format((float)$r['total_pago'], 2),
                        number_format((float)$r['pagado_verificado'], 2),
                        number_format((float)$r['saldo_pendiente'], 2),
                        !empty($r['es_demo']) ? 'DEMO' : 'REAL'
                    ], $delimitador);
                }
                break;

            case 'ingresos':
                $modeloPago = new Pago();
                $rep = $modeloPago->obtenerReporteIngresos($desde, $hasta, $incluirDemo);

                // Metadatos
                fputcsv($out, ['TOP GOL - REPORTE DE INGRESOS EFECTIVAMENTE COBRADOS'], $delimitador);
                fputcsv($out, ['Periodo:', "{$desde} al {$hasta}"], $delimitador);
                fputcsv($out, ['Filtro Datos:', $incluirDemo ? 'Incluye transacciones de demostración' : 'Solo operaciones comerciales reales'], $delimitador);
                fputcsv($out, ['Total Cobrado (S/):', number_format((float)$rep['total_ingresos'], 2)], $delimitador);
                fputcsv($out, ['Efectivo (S/):', number_format((float)$rep['total_efectivo'], 2)], $delimitador);
                fputcsv($out, ['Niubiz POS (S/):', number_format((float)$rep['total_niubiz'], 2)], $delimitador);
                fputcsv($out, ['Yape Adelantos (S/):', number_format((float)$rep['total_yape'], 2)], $delimitador);
                fputcsv($out, ['Alquiler Canchas (S/):', number_format((float)$rep['canchas_total'], 2)], $delimitador);
                fputcsv($out, ['Venta Productos (S/):', number_format((float)$rep['productos_total'], 2)], $delimitador);
                fputcsv($out, ['Saldos Pendientes por Cobrar (S/):', number_format((float)$rep['saldos_pendientes'], 2)], $delimitador);
                fputcsv($out, [], $delimitador);

                // Encabezados
                fputcsv($out, [
                    'ID',
                    'Fecha y Hora',
                    'Rubro',
                    'Tipo Cobro',
                    'Concepto / Referencia',
                    'Cliente',
                    'Método de Pago',
                    'Monto Cobrado (S/)',
                    'Registrado Por',
                    'Es Demo'
                ], $delimitador);

                foreach ($rep['transacciones'] as $tx) {
                    $metodoLbl = match ($tx['metodo_pago']) {
                        'efectivo' => 'Efectivo',
                        'niubiz'   => 'Niubiz POS',
                        'yape'     => 'Yape',
                        default    => ucfirst((string)$tx['metodo_pago'])
                    };
                    $tipoLbl = match ($tx['tipo']) {
                        'adelanto'      => 'Adelanto Reserva',
                        'saldo'         => 'Saldo de Cancha',
                        'completo'      => 'Pago Completo Reserva',
                        'venta_directa' => 'Venta Productos Mostrador',
                        default         => ucfirst((string)$tx['tipo'])
                    };

                    fputcsv($out, [
                        $tx['id'],
                        $tx['created_at'],
                        ucfirst((string)$tx['rubro']),
                        $tipoLbl,
                        $tx['concepto'],
                        $tx['cliente_nombre'] ?? 'Cliente Mostrador',
                        $metodoLbl,
                        number_format((float)$tx['monto'], 2),
                        $tx['admin_nombre'] ?? 'Sistema Web',
                        !empty($tx['es_demo']) ? 'DEMO' : 'REAL'
                    ], $delimitador);
                }
                break;

            case 'productos':
                $modeloVenta = new Venta();
                $rep = $modeloVenta->obtenerReporteProductos($desde, $hasta, $incluirDemo);

                // Metadatos
                fputcsv($out, ['TOP GOL - REPORTE DE VENTAS DE PRODUCTOS E INVENTARIO'], $delimitador);
                fputcsv($out, ['Periodo:', "{$desde} al {$hasta}"], $delimitador);
                fputcsv($out, ['Filtro Datos:', $incluirDemo ? 'Incluye transacciones de demostración' : 'Solo operaciones comerciales reales'], $delimitador);
                fputcsv($out, ['Unidades Vendidas:', $rep['total_unidades']], $delimitador);
                fputcsv($out, ['Ingresos por Ventas (S/):', number_format((float)$rep['total_ingresos'], 2)], $delimitador);
                fputcsv($out, ['Ticket Promedio (S/):', number_format((float)$rep['ticket_promedio'], 2)], $delimitador);
                fputcsv($out, ['Valor de Existencias al PVP (S/):', number_format((float)$rep['valor_inventario_pvp'], 2)], $delimitador);
                fputcsv($out, ['Nota Financiera:', 'Valorizado a Precio de Venta al Público (PVP). No representa costo de adquisición ni margen bruto.'], $delimitador);
                fputcsv($out, ['Nota Inventario:', 'El stock reportado en la columna \'Stock Actual Vigente (Hoy)\' corresponde a las existencias físicas actuales en catálogo, no al saldo histórico a la fecha consultada. Consulte el Kardex para trazabilidad de existencias pasadas.'], $delimitador);
                fputcsv($out, [], $delimitador);

                // Ranking de productos
                fputcsv($out, ['1. RANKING DE PRODUCTOS MÁS VENDIDOS'], $delimitador);
                fputcsv($out, [
                    'ID Producto',
                    'Nombre Producto',
                    'Categoría',
                    'Unidad',
                    'Precio Venta PVP (S/)',
                    'Unidades Vendidas',
                    'Total Ingresos (S/)',
                    'Stock Actual Vigente (Hoy)',
                    'Stock Mínimo',
                    'Estado Stock',
                    'Tipo Registro'
                ], $delimitador);

                foreach ($rep['ranking'] as $prod) {
                    $stk = (int)$prod['stock_actual'];
                    $min = (int)$prod['stock_minimo'];
                    $stkEstado = $stk === 0 ? 'AGOTADO' : ($stk <= $min ? 'STOCK BAJO' : 'NORMAL');

                    fputcsv($out, [
                        $prod['id'],
                        $prod['nombre'],
                        $prod['categoria_nombre'],
                        $prod['unidad'],
                        number_format((float)$prod['precio_pvp'], 2),
                        $prod['unidades_vendidas'],
                        number_format((float)$prod['total_ingresos'], 2),
                        $stk,
                        $min,
                        $stkEstado,
                        $incluirDemo ? 'INCLUYE DEMO' : 'COMERCIAL REAL'
                    ], $delimitador);
                }

                // Ventas por Categoría
                fputcsv($out, [], $delimitador);
                fputcsv($out, ['2. VENTAS CONSOLIDADAS POR CATEGORÍA'], $delimitador);
                fputcsv($out, [
                    'Categoría',
                    'Unidades Vendidas',
                    'Total Ingresos (S/)',
                    'Tipo Registro'
                ], $delimitador);

                foreach ($rep['por_categoria'] as $cat) {
                    fputcsv($out, [
                        $cat['categoria_nombre'],
                        $cat['unidades_vendidas'],
                        number_format((float)$cat['total_ingresos'], 2),
                        $incluirDemo ? 'INCLUYE DEMO' : 'COMERCIAL REAL'
                    ], $delimitador);
                }
                break;
        }

        fclose($out);
        exit;
    }
}
