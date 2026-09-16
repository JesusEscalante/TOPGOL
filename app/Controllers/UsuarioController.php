<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cancha;
use App\Models\Reserva;
use App\Models\Usuario;

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

        $estadisticas = $modeloReserva->obtenerEstadisticas();
        $estadisticas['total_canchas'] = $modeloCancha->contarCanchas();
        $estadisticas['total_usuarios'] = $modeloUsuario->contarUsuarios();

        $hoy = date('Y-m-d');
        $ayer = date('Y-m-d', strtotime('-1 day'));
        $todas = $modeloReserva->obtenerTodas();

        // Reservas de hoy / ayer (excluye canceladas)
        $reservasHoy = array_values(array_filter($todas, static fn($r) => $r['fecha'] === $hoy && $r['estado'] !== 'cancelada'));
        $reservasAyer = array_values(array_filter($todas, static fn($r) => $r['fecha'] === $ayer && $r['estado'] !== 'cancelada'));

        // Ingresos del día (reservas confirmadas/finalizadas con fecha de hoy)
        $sumaIngresos = static function (array $lista): float {
            $s = 0.0;
            foreach ($lista as $r) {
                if (in_array($r['estado'], ['confirmada', 'finalizada'], true)) {
                    $s += (float)$r['total_pago'];
                }
            }
            return $s;
        };
        $ingresosHoy = $sumaIngresos(array_filter($todas, static fn($r) => $r['fecha'] === $hoy));
        $ingresosAyer = $sumaIngresos(array_filter($todas, static fn($r) => $r['fecha'] === $ayer));

        $pct = static function (float $h, float $a): int {
            if ($a > 0) {
                return (int)round(($h - $a) / $a * 100);
            }
            return $h > 0 ? 100 : 0;
        };

        // Ventas de productos (módulo demo: sin tabla propia aún)
        $ventasProductos = 340;

        $recientes = array_slice($todas, 0, 5);

        // Ocupación de canchas hoy (todas las canchas, bloques de 07:00 a 00:00)
        $canchas = $modeloCancha->obtenerTodas();
        $ocupCanchas = array_values($canchas);
        $horas = [];
        for ($h = 7; $h <= 23; $h++) {
            $horas[] = sprintf('%02d:00', $h);
        }
        $ocupacion = [];
        foreach ($ocupCanchas as $c) {
            $fila = [];
            if (($c['estado'] ?? 'disponible') !== 'disponible') {
                foreach ($horas as $slot) {
                    $fila[$slot] = 'mant';
                }
            } else {
                $bloques = $modeloReserva->obtenerHorariosOcupados((int)$c['id'], $hoy);
                foreach ($horas as $slot) {
                    $ini = $slot . ':00';
                    $fin = date('H:i:s', strtotime($slot . ' +1 hour'));
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

        $this->view('usuarios/dashboard', [
            'titulo'          => 'Dashboard Administrativo - ' . APP_NAME,
            'adminMenu'       => 'dashboard',
            'estadisticas'    => $estadisticas,
            'ultimasReservas' => array_slice($todas, 0, 8),
            'canchas'         => $canchas,
            'hoy'             => $hoy,
            'reservasHoy'     => count($reservasHoy),
            'pctReservas'     => $pct(count($reservasHoy), count($reservasAyer)),
            'ingresosHoy'     => $ingresosHoy,
            'pctIngresos'     => $pct($ingresosHoy, $ingresosAyer),
            'pagosVerificar'  => (int)($estadisticas['pagos_en_revision'] ?? 0),
            'ventasProductos' => $ventasProductos,
            'recientes'       => $recientes,
            'ocupCanchas'     => $ocupCanchas,
            'horas'           => $horas,
            'ocupacion'       => $ocupacion,
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