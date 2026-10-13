<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cancha;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Models\Venta;

/**
 * ====================================================================
 * TOP GOL - Controlador de Usuarios y Administración
 * ====================================================================
 * Gestiona el panel de control administrativo (Dashboard con estadísticas)
 * y la administración de clientes del sistema.
 */
class UsuarioController extends Controller {

    /**
     * Muestra el Dashboard administrativo con estadísticas completas
     * (vista standalone con su propio layout de panel, sin header/footer del sitio).
     */
    public function dashboard(): void {
        $this->requireAdmin();

        $modeloReserva = new Reserva();
        $modeloCancha = new Cancha();
        $modeloUsuario = new Usuario();
        $modeloPago = new Pago();
        $modeloVenta = new Venta();
        $modeloProducto = new Producto();

        $hoyReal = date('Y-m-d');
        $hoy = trim((string)($_GET['fecha'] ?? $hoyReal));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hoy)) {
            $hoy = $hoyReal;
        }

        $tsHoy = strtotime($hoy);
        $lunes = date('Y-m-d', strtotime($hoy . ' -' . (date('N', $tsHoy) - 1) . ' days'));
        $domingo = date('Y-m-d', strtotime($lunes . ' +6 days'));
        $ayer = date('Y-m-d', strtotime($hoy . ' -1 day'));

        $incluirDemo = ((int)($_GET['demo'] ?? 0) === 1);

        // 1. Métricas de reservas para la fecha seleccionada
        $metricasHoy = $modeloReserva->obtenerMetricasDia($hoy, $incluirDemo);
        $metricasAyer = $modeloReserva->obtenerMetricasDia($ayer, $incluirDemo);

        // 2. Ingresos efectivamente cobrados (caja auditada)
        $cajaHoy = $modeloPago->obtenerResumenCaja($hoy, $incluirDemo);
        $cajaAyer = $modeloPago->obtenerResumenCaja($ayer, $incluirDemo);
        $ingresosHoy = (float)($cajaHoy['total_ingresos'] ?? 0.0);
        $ingresosAyer = (float)($cajaAyer['total_ingresos'] ?? 0.0);

        // 3. Ventas de productos
        $ventasHoy = $modeloVenta->obtenerResumenVentasDia($hoy, $incluirDemo);
        $ventasAyer = $modeloVenta->obtenerResumenVentasDia($ayer, $incluirDemo);
        $ventasProductosHoy = (float)($ventasHoy['total_ventas'] ?? 0.0);
        $ventasProductosAyer = (float)($ventasAyer['total_ventas'] ?? 0.0);

        $pct = static function (float $h, float $a): int {
            if ($a > 0) {
                return (int)round(($h - $a) / $a * 100);
            }
            return $h > 0 ? 100 : 0;
        };

        // 4. Productos más vendidos para el widget (hoy o histórico si hoy aún no hay ventas)
        $productosTopHoy = $modeloVenta->obtenerProductosMasVendidos(5, $hoy, $incluirDemo);
        $productosTopHistorico = empty($productosTopHoy) ? $modeloVenta->obtenerProductosMasVendidos(5, null, $incluirDemo) : [];

        // 5. Alertas de inventario
        $stockBajoConteo = $modeloProducto->contarStockBajo();
        $productosStockCritico = $modeloProducto->obtenerStockCritico(5);
        $valorInventarioPvp = round($modeloProducto->valorizacion(), 2);

        // 6. Ocupación de canchas en la fecha consultada según horarios provisionales
        $horarioBase = getHorarioProvisionalNegocio($hoy);
        $canchas = $modeloCancha->obtenerTodas();
        $ocupCanchas = array_values($canchas);
        $horas = [];
        for ($h = 7; $h <= 23; $h++) {
            foreach (['00', '30'] as $mm) {
                $horas[] = sprintf('%02d:%s', $h, $mm);
            }
        }

        $ocupacion = [];
        $totalCanchasDisponibles = 0;
        foreach ($ocupCanchas as $c) {
            $fila = [];
            $esMant = (($c['estado'] ?? 'disponible') === 'mantenimiento');
            if ($esMant) {
                foreach ($horas as $slot) {
                    $fila[$slot] = 'mant';
                }
            } else {
                $totalCanchasDisponibles++;
                $bloques = $modeloReserva->obtenerHorariosOcupados((int)$c['id'], $hoy, $incluirDemo);
                foreach ($horas as $slot) {
                    $ini = $slot . ':00';
                    $fin = date('H:i:s', strtotime($slot . ' +30 minutes'));
                    if ($fin === '00:00:00') {
                        $fin = '24:00:00';
                    }
                    $ocupada = false;
                    foreach ($bloques as $b) {
                        $bFin = $b['hora_fin'] === '00:00:00' ? '24:00:00' : $b['hora_fin'];
                        if ($b['hora_inicio'] < $fin && $bFin > $ini) {
                            $ocupada = true;
                            break;
                        }
                    }
                    $fila[$slot] = $ocupada ? 'ocup' : 'libre';
                }
            }
            $ocupacion[(int)$c['id']] = $fila;
        }

        // Capacidad base según horarios provisionales del negocio:
        // Lunes a viernes: 14 horas operativas por cancha (09:00 a 23:00)
        // Sábados y domingos: 16 horas operativas por cancha (07:00 a 23:00)
        // Se permiten reservas extraordinarias registradas por el encargado
        $horasOperativasCancha = $horarioBase['horas_operativas'];
        $capacidadHorasDia = max(1, $totalCanchasDisponibles * $horasOperativasCancha);
        $horasCanchaOcupadas = (float)($metricasHoy['horas_cancha'] ?? 0.0);
        $pctOcupacion = min(100, (int)round(($horasCanchaOcupadas / $capacidadHorasDia) * 100));

        // 7. Reservas recientes (últimas 6 registradas, respetando filtro de demostración)
        $todas = $modeloReserva->obtenerTodas($incluirDemo);
        $recientes = array_slice($todas, 0, 6);

        // Estadísticas acumuladas globales para contadores auxiliares
        $estadisticas = $modeloReserva->obtenerEstadisticas($incluirDemo);
        $estadisticas['total_canchas'] = $modeloCancha->contarCanchas();
        $estadisticas['total_usuarios'] = $modeloUsuario->contarUsuarios();

        $this->view('usuarios/dashboard', [
            'titulo'                 => 'Dashboard Administrativo - ' . APP_NAME,
            'adminMenu'              => 'dashboard',
            'estadisticas'           => $estadisticas,
            'canchas'                => $canchas,
            'hoy'                    => $hoy,
            'hoyReal'                => $hoyReal,
            'lunes'                  => $lunes,
            'domingo'                => $domingo,
            'incluirDemo'            => $incluirDemo,
            'horarioBase'            => $horarioBase,
            'horasOperativasCancha'  => $horasOperativasCancha,
            'capacidadHorasDia'      => $capacidadHorasDia,
            // 9 Indicadores solicitados
            'reservasHoy'            => (int)$metricasHoy['total_reservas'],
            'reservasConfirmadas'    => (int)$metricasHoy['confirmadas'],
            'reservasPendientes'     => (int)$metricasHoy['pendientes'],
            'horasCanchaHoy'         => (float)$metricasHoy['horas_cancha'],
            'pctReservas'            => $pct((float)$metricasHoy['total_reservas'], (float)$metricasAyer['total_reservas']),
            'pagosVerificar'         => (int)$metricasHoy['comprobantes_revision'],
            'pagosVerificarDia'      => (int)$metricasHoy['comprobantes_dia'],
            'ingresosHoy'            => $ingresosHoy,
            'pctIngresos'            => $pct($ingresosHoy, $ingresosAyer),
            'ventasProductos'        => $ventasProductosHoy,
            'pctVentasProductos'     => $pct($ventasProductosHoy, $ventasProductosAyer),
            'saldoPendiente'         => (float)$metricasHoy['saldo_pendiente'],
            'stockBajoConteo'        => $stockBajoConteo,
            'productosStockCritico'  => $productosStockCritico,
            'valorInventarioPvp'     => $valorInventarioPvp,
            'pctOcupacion'           => $pctOcupacion,
            // Detalle y widgets
            'cajaHoy'                => $cajaHoy,
            'ventasHoy'              => $ventasHoy,
            'productosTopHoy'        => $productosTopHoy,
            'productosTopHistorico'  => $productosTopHistorico,
            'recientes'              => $recientes,
            'ocupCanchas'            => $ocupCanchas,
            'horas'                  => $horas,
            'ocupacion'              => $ocupacion,
        ], false);
    }

    /**
     * Muestra el perfil del usuario conectado para editar sus datos
     */
    public function perfil(): void {
        $this->requireAuth();

        $modeloUsuario = new Usuario();
        $usuario = $modeloUsuario->obtenerPorEmail((string)($_SESSION['usuario_email'] ?? ''));

        if (!$usuario) {
            sessionFlash('error', 'No se encontró tu cuenta.', 'warning');
            $this->redirect('/');
        }

        // Los administradores ven el perfil dentro del panel admin
        if (isAdmin()) {
            $this->view('usuarios/perfil_admin', [
                'titulo'    => 'Mi Perfil - Panel Administrador',
                'adminMenu' => '',
                'usuario'   => $usuario
            ], false);
            return;
        }

        $this->view('usuarios/perfil', [
            'titulo'  => 'Mi Perfil - ' . APP_NAME,
            'usuario' => $usuario
        ]);
    }

    /**
     * Actualiza nombre y teléfono del usuario conectado
     */
    public function updatePerfil(): void {
        $this->requireAuth();
        $this->validateCsrf();

        $post = $this->sanitizePost();
        $nombre = trim($post['nombre'] ?? '');
        $telefono = trim($post['telefono'] ?? '');
        // Normalizar teléfono con etiqueta +51 (el input trae solo los 9 dígitos)
        if ($telefono !== '') {
            $telefono = preg_replace('/\s+/', '', $telefono);
            if (!str_starts_with($telefono, '+')) {
                $telefono = '+51 ' . ltrim($telefono, '0');
            } else {
                $telefono = preg_replace('/^\+51\s*/', '+51 ', $telefono);
            }
        }
        $usuarioId = (int)$_SESSION['usuario_id'];

        if (strlen($nombre) < 3) {
            sessionFlash('error', 'El nombre debe tener al menos 3 caracteres.', 'danger');
            $this->redirect('/perfil');
        }

        $modeloUsuario = new Usuario();
        $ok = $modeloUsuario->actualizar($usuarioId, [
            'nombre'   => $nombre,
            'telefono' => $telefono,
            'rol'      => (string)($_SESSION['usuario_rol'] ?? 'cliente'),
        ]);

        if ($ok) {
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_telefono'] = $telefono;
            sessionFlash('success', 'Tus datos se actualizaron correctamente.', 'success');
        } else {
            sessionFlash('error', 'No se pudo actualizar tu perfil. Intenta nuevamente.', 'danger');
        }
        $this->redirect('/perfil');
    }

    /**
     * Cambia la contraseña del usuario conectado
     */
    public function updatePassword(): void {
        $this->requireAuth();
        $this->validateCsrf();

        $post = $this->sanitizePost();
        $actual = (string)($post['password_actual'] ?? '');
        $nueva = (string)($post['password_nueva'] ?? '');
        $confirm = (string)($post['password_confirm'] ?? '');
        $usuarioId = (int)$_SESSION['usuario_id'];

        if (strlen($nueva) < 6) {
            sessionFlash('error', 'La nueva contraseña debe tener mínimo 6 caracteres.', 'danger');
            $this->redirect('/perfil');
        }
        if ($nueva !== $confirm) {
            sessionFlash('error', 'La confirmación no coincide con la nueva contraseña.', 'danger');
            $this->redirect('/perfil');
        }

        $modeloUsuario = new Usuario();
        $usuario = $modeloUsuario->obtenerPorEmail((string)($_SESSION['usuario_email'] ?? ''));

        // Si la cuenta tiene contraseña (no solo Google), exigir la actual
        if (!empty($usuario['password']) && !password_verify($actual, $usuario['password'])) {
            sessionFlash('error', 'Tu contraseña actual no es correcta.', 'danger');
            $this->redirect('/perfil');
        }

        if ($modeloUsuario->cambiarPassword($usuarioId, $nueva)) {
            sessionFlash('success', 'Contraseña actualizada correctamente.', 'success');
        } else {
            sessionFlash('error', 'No se pudo cambiar la contraseña. Intenta nuevamente.', 'danger');
        }
        $this->redirect('/perfil');
    }

    /**
     * Muestra el listado de usuarios para gestión del administrador
     */
    public function index(): void {
        $this->requireAdmin();

        $modeloUsuario = new Usuario();
        $usuarios = $modeloUsuario->obtenerTodos();

        $this->view('usuarios/index', [
            'titulo'   => 'Gestión de Usuarios - ' . APP_NAME,
            'usuarios' => $usuarios
        ]);
    }
}