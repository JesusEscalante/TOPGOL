<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cancha;
use App\Models\Reserva;
use App\Models\Usuario;

/**
 * ====================================================================
 * TOP GOL - Controlador Principal (Home)
 * ====================================================================
 * Gestiona la página de inicio, presentación del complejo y resumen
 * para administradores.
 */
class HomeController extends Controller {

    /**
     * Muestra la página de bienvenida y catálogo de canchas
     */
    public function index(): void {
        $modeloCancha = new Cancha();
        $canchas = $modeloCancha->obtenerDisponibles();

        // Slots realmente ocupados hoy por cancha (para pintar los horarios tomados)
        $modeloReserva = new Reserva();
        $hoy = date('Y-m-d');
        $slotsOcupados = [];
        foreach ($canchas as $c) {
            $slotsOcupados[(int)$c['id']] = $modeloReserva->obtenerSlotsOcupados((int)$c['id'], $hoy);
        }

        $estadisticas = null;
        if (isAdmin()) {
            $modeloUsuario = new Usuario();
            $estadisticas = $modeloReserva->obtenerEstadisticas();
            $estadisticas['total_canchas'] = $modeloCancha->contarCanchas();
            $estadisticas['total_usuarios'] = $modeloUsuario->contarUsuarios();
        }

        $this->view('home/index', [
            'titulo'        => 'Alquiler de Canchas Sintéticas en TOP GOL',
            'canchas'       => $canchas,
            'estadisticas'  => $estadisticas,
            'slotsOcupados' => $slotsOcupados,
            'fechaSlots'    => $hoy,
        ]);
    }
}