<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cancha;
use App\Models\Pago;
use App\Models\Reserva;

/**
 * ====================================================================
 * TOP GOL - Controlador de Pagos y Caja del Administrador
 * ====================================================================
 * Gestiona el control de adelantos, saldos pendientes, cobros presenciales
 * (Efectivo y Niubiz POS), verificación manual de comprobantes Yape y
 * arqueo/consulta básica de la caja diaria.
 */
class PagoController extends Controller {

    /**
     * Panel principal de Pagos y Control de Saldos (Solo Admin)
     */
    public function index(): void {
        $this->requireAdmin();

        $tab = trim($_GET['tab'] ?? 'todos');
        if (!in_array($tab, ['todos', 'revision', 'pendientes', 'pagadas', 'historial'], true)) {
            $tab = 'todos';
        }

        $busqueda = trim($_GET['q'] ?? '');
        $filtroMetodo = trim($_GET['metodo'] ?? '');
        if (!in_array($filtroMetodo, ['yape', 'efectivo', 'niubiz'], true)) {
            $filtroMetodo = '';
        }

        $incluirDemo = ((int)($_GET['demo'] ?? 0) === 1);

        $modeloReserva = new Reserva();
        $modeloPago = new Pago();

        $todasReservas = $modeloReserva->obtenerTodas($incluirDemo);
        $estadisticas = $modeloReserva->obtenerEstadisticas($incluirDemo);

        // Agrupar reservas para eventos y calcular saldos en tiempo real
        $grupos = [];
        foreach ($todasReservas as $r) {
            $k = !empty($r['evento_grupo']) ? 'evt_' . $r['evento_grupo'] : 'res_' . $r['id'];
            if (!isset($grupos[$k])) {
                $grupos[$k] = [
                    'id'                => (int)$r['id'],
                    'is_grupo'          => !empty($r['evento_grupo']),
                    'evento_grupo'      => $r['evento_grupo'],
                    'usuario_id'        => (int)$r['usuario_id'],
                    'cliente_nombre'    => !empty($r['cliente_nombre']) ? $r['cliente_nombre'] : $r['usuario_nombre'],
                    'cliente_email'     => $r['usuario_email'],
                    'contacto_telefono' => !empty($r['contacto_telefono']) ? $r['contacto_telefono'] : ($r['usuario_telefono'] ?? ''),
                    'origen'            => $r['origen'] ?? 'web',
                    'fecha'             => $r['fecha'],
                    'hora_inicio'       => $r['hora_inicio'],
                    'hora_fin'          => $r['hora_fin'],
                    'estado'            => $r['estado'],
                    'pago_estado'       => $r['pago_estado'],
                    'metodo_pago'       => $r['metodo_pago'],
                    'comprobante_ruta'  => $r['comprobante_ruta'],
                    'total_pago'        => 0.0,
                    'adelanto_monto'    => 0.0,
                    'total_pagado'      => 0.0,
                    'saldo_pendiente'   => 0.0,
                    'canchas'           => [],
                    'reservas_ids'      => [],
                ];
            }
            $grupos[$k]['total_pago'] += (float)$r['total_pago'];
            $grupos[$k]['adelanto_monto'] += (float)$r['adelanto_monto'];
            $grupos[$k]['total_pagado'] += (float)($r['total_pagado'] ?? 0.0);
            $grupos[$k]['saldo_pendiente'] += (float)($r['saldo_pendiente'] ?? 0.0);
            $grupos[$k]['canchas'][] = ['id' => $r['cancha_id'], 'nombre' => $r['cancha_nombre']];
            $grupos[$k]['reservas_ids'][] = (int)$r['id'];
        }

        // Redondear saldos
        foreach ($grupos as $k => $g) {
            $grupos[$k]['total_pago'] = round($g['total_pago'], 2);
            $grupos[$k]['total_pagado'] = round($g['total_pagado'], 2);
            $grupos[$k]['saldo_pendiente'] = max(0.0, round($g['total_pago'] - $g['total_pagado'], 2));
        }

        // Filtrar según pestaña seleccionada
        $items = array_values(array_filter($grupos, function ($item) use ($tab, $busqueda, $filtroMetodo) {
            // Filtro de búsqueda
            if ($busqueda !== '') {
                $q = mb_strtolower($busqueda);
                $texto = mb_strtolower('#' . $item['id'] . ' ' . $item['cliente_nombre'] . ' ' . $item['cliente_email'] . ' ' . implode(' ', array_column($item['canchas'], 'nombre')));
                if (!str_contains($texto, $q)) {
                    return false;
                }
            }

            // Filtro de método
            if ($filtroMetodo !== '' && ($item['metodo_pago'] ?? '') !== $filtroMetodo) {
                return false;
            }

            // Filtro por pestaña
            return match ($tab) {
                'revision'   => ($item['pago_estado'] === 'en_revision'),
                'pendientes' => ($item['estado'] !== 'cancelada' && $item['saldo_pendiente'] > 0.0),
                'pagadas'    => ($item['estado'] !== 'cancelada' && $item['saldo_pendiente'] <= 0.0 && $item['total_pagado'] > 0.0),
                default      => true,
            };
        }));

        // Historial de transacciones si la pestaña activa es historial
        $historialTransacciones = [];
        if ($tab === 'historial') {
            $historialTransacciones = $modeloPago->obtenerHistorial([
                'metodo' => $filtroMetodo,
                'q'      => $busqueda,
                'demo'   => $incluirDemo,
            ], 60);
        }

        // Resumen de caja de hoy
        $hoy = date('Y-m-d');
        $cajaHoy = $modeloPago->obtenerResumenCaja($hoy, $incluirDemo);

        // Contadores para pestañas
        $conteoRevision = count(array_filter($grupos, static fn($g) => $g['pago_estado'] === 'en_revision'));
        $conteoPendientes = count(array_filter($grupos, static fn($g) => $g['estado'] !== 'cancelada' && $g['saldo_pendiente'] > 0.0));
        $conteoPagadas = count(array_filter($grupos, static fn($g) => $g['estado'] !== 'cancelada' && $g['saldo_pendiente'] <= 0.0 && $g['total_pagado'] > 0.0));

        $this->view('pagos/index', [
            'titulo'                 => 'Control de Pagos y Caja - ' . APP_NAME,
            'adminMenu'              => 'pagos',
            'tab'                    => $tab,
            'busqueda'               => $busqueda,
            'filtroMetodo'           => $filtroMetodo,
            'items'                  => $items,
            'historialTransacciones' => $historialTransacciones,
            'estadisticas'           => $estadisticas,
            'cajaHoy'                => $cajaHoy,
            'conteoRevision'         => $conteoRevision,
            'conteoPendientes'       => $conteoPendientes,
            'conteoPagadas'          => $conteoPagadas,
            'incluirDemo'            => $incluirDemo,
        ], false);
    }

    /**
     * Consulta de Caja Diaria básica (Solo Admin)
     */
    public function caja(): void {
        $this->requireAdmin();

        $fecha = trim($_GET['fecha'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        $incluirDemo = ((int)($_GET['demo'] ?? 0) === 1);

        $modeloPago = new Pago();
        $resumen = $modeloPago->obtenerResumenCaja($fecha, $incluirDemo);
        $movimientos = $modeloPago->obtenerMovimientosCaja($fecha, $incluirDemo);
        $ventas = $modeloPago->obtenerVentasCaja($fecha, $incluirDemo);

        $this->view('pagos/caja', [
            'titulo'      => 'Caja Diaria - ' . APP_NAME,
            'adminMenu'   => 'caja',
            'fecha'       => $fecha,
            'incluirDemo' => $incluirDemo,
            'resumen'     => $resumen,
            'movimientos' => $movimientos,
            'ventas'      => $ventas,
        ], false);
    }

    /**
     * Procesa el cobro de saldo para una reserva o grupo de evento (Solo Admin)
     */
    public function registrarCobro(): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $post = $this->sanitizePost();
        $idReserva = (int)($post['reserva_id'] ?? 0);
        $grupo = trim((string)($post['evento_grupo'] ?? ''));
        $monto = (float)($post['monto'] ?? 0.0);
        $metodo = trim((string)($post['metodo_pago'] ?? 'efectivo'));
        $notas = trim((string)($post['notas'] ?? ''));
        $adminId = (int)$_SESSION['usuario_id'];

        $modeloPago = new Pago();

        if (!empty($grupo)) {
            $resultado = $modeloPago->registrarCobroSaldoGrupo($grupo, $monto, $metodo, $adminId, $notas);
        } else {
            if ($idReserva <= 0) {
                if ($this->isAjax()) { $this->json(['ok' => false, 'error' => 'Identificador de reserva no válido.'], 400); }
                sessionFlash('error', 'Identificador de reserva no válido.', 'danger');
                $this->redirect('/admin/pagos');
                return;
            }
            $resultado = $modeloPago->registrarCobroSaldo($idReserva, $monto, $metodo, $adminId, $notas);
        }

        if ($this->isAjax()) {
            if ($resultado['ok']) {
                $this->json($resultado, 200);
            } else {
                $this->json($resultado, 400);
            }
        }

        if ($resultado['ok']) {
            $metodoLbl = $metodo === 'niubiz' ? 'Niubiz POS' : 'Efectivo';
            $msg = !empty($grupo)
                ? "Cobro de saldo de S/ " . number_format($monto, 2) . " ({$metodoLbl}) registrado para el evento grupal."
                : "Cobro de saldo de S/ " . number_format($monto, 2) . " ({$metodoLbl}) registrado con éxito para la reserva #{$idReserva}.";
            sessionFlash('success', $msg, 'success');
        } else {
            sessionFlash('error', $resultado['error'] ?? 'No se pudo procesar el cobro.', 'danger');
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/pagos');
    }

    /**
     * Verifica el comprobante de adelanto Yape (Solo Admin)
     *
     * @param string|int $id
     */
    public function verificar(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idReserva = (int)$id;
        $adminId = (int)$_SESSION['usuario_id'];
        $grupo = trim((string)($_POST['evento_grupo'] ?? ''));

        $modeloReserva = new Reserva();

        if (!empty($grupo)) {
            $ok = $modeloReserva->actualizarEstadoPagoGrupo($grupo, 'verificado', $adminId);
            if ($ok) {
                $modeloReserva->actualizarEstadoGrupo($grupo, 'confirmada');
            }
        } else {
            $ok = $modeloReserva->actualizarEstadoPago($idReserva, 'verificado', $adminId);
            if ($ok) {
                $modeloReserva->actualizarEstado($idReserva, 'confirmada');
            }
        }

        if ($this->isAjax()) {
            if ($ok) {
                $this->json(['ok' => true, 'mensaje' => 'Comprobante verificado con éxito']);
            } else {
                $this->json(['ok' => false, 'error' => 'No se pudo verificar el comprobante'], 500);
            }
        }

        if ($ok) {
            sessionFlash('success', "Comprobante verificado y reserva confirmada exitosamente.", 'success');
        } else {
            sessionFlash('error', "No se pudo verificar el comprobante.", 'danger');
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/pagos');
    }

    /**
     * Rechaza el comprobante y cancela la reserva para liberar el horario (Solo Admin)
     *
     * @param string|int $id
     */
    public function rechazar(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idReserva = (int)$id;
        $adminId = (int)$_SESSION['usuario_id'];
        $grupo = trim((string)($_POST['evento_grupo'] ?? ''));

        $modeloReserva = new Reserva();

        if (!empty($grupo)) {
            $ok = $modeloReserva->actualizarEstadoPagoGrupo($grupo, 'rechazado', $adminId);
            if ($ok) {
                $modeloReserva->actualizarEstadoGrupo($grupo, 'cancelada');
            }
        } else {
            $ok = $modeloReserva->actualizarEstadoPago($idReserva, 'rechazado', $adminId);
            if ($ok) {
                $modeloReserva->actualizarEstado($idReserva, 'cancelada');
            }
        }

        if ($this->isAjax()) {
            if ($ok) {
                $this->json(['ok' => true, 'mensaje' => 'Comprobante rechazado y horario liberado']);
            } else {
                $this->json(['ok' => false, 'error' => 'No se pudo rechazar el comprobante'], 500);
            }
        }

        if ($ok) {
            sessionFlash('success', "Comprobante rechazado. La reserva fue cancelada y el horario ha quedado libre.", 'warning');
        } else {
            sessionFlash('error', "No se pudo rechazar el comprobante.", 'danger');
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/pagos');
    }
}
