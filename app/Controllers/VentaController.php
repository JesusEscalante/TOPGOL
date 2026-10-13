<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Venta;

/**
 * ====================================================================
 * TOP GOL - Controlador de Ventas (Bar y Kiosco)
 * ====================================================================
 * Gestiona el terminal de venta rápida en mostrador (POS),
 * transacciones comerciales, selección de métodos de pago e historial.
 */
class VentaController extends Controller {

    /**
     * Historial de ventas realizadas
     */
    public function index(): void {
        $this->requireAdmin();

        $fecha = trim((string)($_GET['fecha'] ?? ''));
        $metodo = trim((string)($_GET['metodo'] ?? ''));
        $busqueda = trim((string)($_GET['q'] ?? ''));

        $modeloVenta = new Venta();
        $ventas = $modeloVenta->obtenerHistorial([
            'fecha'       => $fecha,
            'metodo_pago' => $metodo,
            'q'           => $busqueda,
        ], 50);

        // Resumen del día de hoy
        $resumenHoy = $modeloVenta->obtenerResumenVentasDia(date('Y-m-d'));

        $this->view('ventas/index', [
            'titulo'       => 'Historial de Ventas - Panel Administrador',
            'adminMenu'    => 'ventas',
            'ventas'       => $ventas,
            'resumenHoy'   => $resumenHoy,
            'filtroFecha'  => $fecha,
            'filtroMetodo' => $metodo,
            'busqueda'     => $busqueda,
        ], false);
    }

    /**
     * Interfaz de Venta Rápida en Mostrador (POS)
     */
    public function pos(): void {
        $this->requireAdmin();

        $modeloProd = new Producto();
        $modeloCat = new Categoria();

        // Obtener productos disponibles con stock > 0 para la venta rápida
        $productos = $modeloProd->obtenerTodos(0, '', false, true);
        $categorias = $modeloCat->obtenerTodas(true);

        // Generar un token único de idempotencia para esta sesión de venta
        $idempotencyToken = bin2hex(random_bytes(16));

        $this->view('ventas/pos', [
            'titulo'           => 'Venta Rápida (Bar y Kiosco) - Top Gol',
            'adminMenu'        => 'ventas',
            'productos'        => $productos,
            'categorias'       => $categorias,
            'idempotencyToken' => $idempotencyToken,
        ], false);
    }

    /**
     * Procesa y registra la venta comercial
     */
    public function store(): void {
        $this->requireAdmin();

        if (!validateCsrf()) {
            if ($this->isAjax()) {
                $this->json(['ok' => false, 'error' => 'Token de seguridad inválido o expirado.'], 403);
            }
            sessionFlash('error', 'Token CSRF inválido. Recarga la página e intenta de nuevo.', 'danger');
            $this->redirect('/admin/ventas/pos');
        }

        $post = $this->sanitizePost();
        $admin = currentUser();
        $adminId = $admin ? (int)$admin['id'] : 1;

        $clienteNombre = trim((string)($post['cliente_nombre'] ?? 'Cliente Mostrador'));
        $metodoPago = trim((string)($post['metodo_pago'] ?? 'efectivo'));
        $notas = trim((string)($post['notas'] ?? ''));
        $token = trim((string)($post['idempotency_token'] ?? ''));

        // Los items pueden llegar por JSON o por POST array
        $items = [];
        if (!empty($post['items_json'])) {
            $decoded = json_decode((string)$post['items_json'], true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        } elseif (!empty($post['items']) && is_array($post['items'])) {
            $items = $post['items'];
        }

        $modeloVenta = new Venta();
        $resultado = $modeloVenta->crearVenta([
            'cliente_nombre' => $clienteNombre,
            'metodo_pago'    => $metodoPago,
            'notas'          => $notas,
        ], $items, $adminId, $token);

        if ($this->isAjax()) {
            $this->json($resultado, $resultado['ok'] ? 200 : 400);
        }

        if ($resultado['ok']) {
            sessionFlash('success', $resultado['mensaje'] ?? 'Venta registrada con éxito.', 'success');
            $this->redirect("/admin/ventas/detalle/{$resultado['venta_id']}");
        } else {
            sessionFlash('error', $resultado['error'] ?? 'No se pudo procesar la venta.', 'danger');
            $this->redirect('/admin/ventas/pos');
        }
    }

    /**
     * Muestra el detalle o comprobante de venta interna
     */
    public function detalle(string|int $id): void {
        $this->requireAdmin();

        $idVenta = (int)$id;
        $modeloVenta = new Venta();
        $venta = $modeloVenta->obtenerPorId($idVenta);

        if (!$venta) {
            sessionFlash('error', 'Comprobante de venta no encontrado.', 'warning');
            $this->redirect('/admin/ventas');
        }

        $this->view('ventas/detalle', [
            'titulo'    => "Detalle de Venta {$venta['codigo_operacion']} - Top Gol",
            'adminMenu' => 'ventas',
            'venta'     => $venta,
        ], false);
    }
}
