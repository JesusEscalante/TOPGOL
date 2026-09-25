<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cancha;
use App\Models\Reserva;

/**
 * ====================================================================
 * TOP GOL - Controlador de Canchas
 * ====================================================================
 * Gestiona el catálogo de canchas, creación, edición, detalle y
 * eliminación física (CRUD completo para administradores).
 */
class CanchaController extends Controller {

    /**
     * Muestra la lista de canchas deportivas con filtros opcionales
     */
    public function index(): void {
        $modeloCancha = new Cancha();

        // Obtener filtros del formulario de búsqueda (GET)
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $horario = $_GET['horario'] ?? '';
        $tipo = $_GET['tipo'] ?? '';

        // Validar fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        // Obtener canchas filtradas (admin ve también las en mantenimiento)
        $esAdmin = isAdmin();
        if ($esAdmin) {
            $canchas = $modeloCancha->obtenerTodas();
            if ($tipo !== '') {
                $canchas = array_values(array_filter($canchas, static fn($c) => $c['tipo'] === $tipo));
            }
            // Admin: no filtrar por disponibilidad horaria, debe ver las en mantenimiento para reactivarlas
        } else {
            $canchas = $modeloCancha->obtenerConFiltros($fecha, $horario, $tipo);
        }

        // Slots realmente ocupados en la fecha del filtro (para pintar los horarios tomados)
        $modeloReserva = new Reserva();
        $slotsOcupados = [];
        foreach ($canchas as $c) {
            $slotsOcupados[(int)$c['id']] = $modeloReserva->obtenerSlotsOcupados((int)$c['id'], $fecha);
        }

        $this->view('canchas/index', [
            'titulo'        => 'Nuestras Canchas - ' . APP_NAME,
            'canchas'       => $canchas,
            'slotsOcupados' => $slotsOcupados,
            'filtros'       => [
                'fecha'    => $fecha,
                'horario'  => $horario,
                'tipo'     => $tipo,
            ]
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva cancha (Solo Admin)
     */
    public function create(): void {
        $this->requireAdmin();
        $this->view('canchas/create', [
            'titulo' => 'Registrar Nueva Cancha - ' . APP_NAME
        ]);
    }

    /**
     * Procesa y guarda la nueva cancha en la base de datos (Solo Admin)
     */
    public function store(): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $post = $this->sanitizePost();
        $nombre = trim($post['nombre'] ?? '');
        $descripcion = trim($post['descripcion'] ?? '');
        $precioHora = (float)($post['precio_hora'] ?? 0);
        $capacidad = (int)($post['capacidad'] ?? 0);
        $tipo = (string)($post['tipo'] ?? 'futbol_5');
        $iluminacion = isset($post['iluminacion']) ? 1 : 0;
        $techada = isset($post['techada']) ? 1 : 0;
        $estado = (string)($post['estado'] ?? 'disponible');

        // Validaciones en servidor
        if (empty($nombre) || $precioHora <= 0 || $capacidad <= 0) {
            sessionFlash('error', 'El nombre, precio por hora mayor a 0 y capacidad son requeridos.', 'danger');
            $this->redirect('/cancha/crear');
        }

        $tiposValidos = ['futbol_5', 'futbol_7', 'futbol_11'];
        if (!in_array($tipo, $tiposValidos, true)) {
            $tipo = 'futbol_5';
        }

        $modeloCancha = new Cancha();
        $nuevoId = $modeloCancha->crear([
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'precio_hora' => $precioHora,
            'capacidad'   => $capacidad,
            'tipo'        => $tipo,
            'iluminacion' => $iluminacion,
            'techada'     => $techada,
            'estado'      => $estado
        ]);

        if ($nuevoId > 0) {
            sessionFlash('success', "Cancha '{$nombre}' registrada exitosamente con ID #{$nuevoId}.", 'success');
            $this->redirect('/canchas');
        } else {
            sessionFlash('error', 'No se pudo registrar la cancha. Revisa los datos ingresados.', 'danger');
            $this->redirect('/cancha/crear');
        }
    }

    /**
     * Muestra el detalle específico de una cancha
     */
    public function show(string|int $id): void {
        $idCancha = (int)$id;
        $modeloCancha = new Cancha();
        $cancha = $modeloCancha->obtenerPorId($idCancha);

        if (!$cancha) {
            sessionFlash('error', 'La cancha solicitada no existe.', 'warning');
            $this->redirect('/canchas');
        }

        $this->view('canchas/show', [
            'titulo' => "{$cancha['nombre']} - " . APP_NAME,
            'cancha' => $cancha
        ]);
    }

    /**
     * Muestra el formulario para editar una cancha (Solo Admin)
     */
    public function edit(string|int $id): void {
        $this->requireAdmin();
        $idCancha = (int)$id;
        $modeloCancha = new Cancha();
        $cancha = $modeloCancha->obtenerPorId($idCancha);

        if (!$cancha) {
            sessionFlash('error', 'La cancha que intentas editar no existe.', 'warning');
            $this->redirect('/canchas');
        }

        $this->view('canchas/edit', [
            'titulo' => "Editar Cancha: {$cancha['nombre']} - " . APP_NAME,
            'cancha' => $cancha
        ]);
    }

    /**
     * Actualiza la cancha en la base de datos (Solo Admin)
     */
    public function update(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idCancha = (int)$id;
        $post = $this->sanitizePost();

        $nombre = trim($post['nombre'] ?? '');
        $descripcion = trim($post['descripcion'] ?? '');
        $precioHora = (float)($post['precio_hora'] ?? 0);
        $capacidad = (int)($post['capacidad'] ?? 0);
        $tipo = (string)($post['tipo'] ?? 'futbol_5');
        $iluminacion = isset($post['iluminacion']) ? 1 : 0;
        $techada = isset($post['techada']) ? 1 : 0;
        $estado = (string)($post['estado'] ?? 'disponible');

        if (empty($nombre) || $precioHora <= 0 || $capacidad <= 0) {
            sessionFlash('error', 'Verifica que el nombre, precio y capacidad sean válidos.', 'danger');
            $this->redirect("/cancha/editar/{$idCancha}");
        }

        $modeloCancha = new Cancha();
        $actualizado = $modeloCancha->actualizar($idCancha, [
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'precio_hora' => $precioHora,
            'capacidad'   => $capacidad,
            'tipo'        => $tipo,
            'iluminacion' => $iluminacion,
            'techada'     => $techada,
            'estado'      => $estado
        ]);

        if ($actualizado) {
            sessionFlash('success', "Cancha '{$nombre}' actualizada exitosamente.", 'success');
        } else {
            sessionFlash('info', 'No se detectaron cambios en los datos de la cancha.', 'info');
        }

        $this->redirect('/canchas');
    }

    /**
     * Cambia el estado de una cancha entre disponible y mantenimiento (Solo Admin)
     */
    public function toggleEstado(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();
        $idCancha = (int)$id;
        $modeloCancha = new Cancha();
        $cancha = $modeloCancha->obtenerPorId($idCancha);
        if (!$cancha) {
            sessionFlash('error', 'La cancha no fue encontrada.', 'warning');
            $this->redirect('/canchas');
        }
        $nuevo = $cancha['estado'] === 'disponible' ? 'mantenimiento' : 'disponible';
        $modeloCancha->cambiarEstado($idCancha, $nuevo);
        $msg = $nuevo === 'disponible' ? "Cancha '{$cancha['nombre']}' reactivada como disponible." : "Cancha '{$cancha['nombre']}' puesta en mantenimiento.";
        sessionFlash('success', $msg, 'success');
        $this->redirect('/canchas');
    }

    /**
     * Elimina una cancha de la base de datos (Solo Admin)
     */
    public function delete(string|int $id): void {
        $this->requireAdmin();
        $idCancha = (int)$id;
        $modeloCancha = new Cancha();

        $cancha = $modeloCancha->obtenerPorId($idCancha);
        if (!$cancha) {
            sessionFlash('error', 'La cancha a eliminar no fue encontrada.', 'warning');
            $this->redirect('/canchas');
        }

        $modeloCancha->eliminar($idCancha);
        sessionFlash('success', "La cancha '{$cancha['nombre']}' ha sido eliminada del sistema.", 'success');
        $this->redirect('/canchas');
    }
}