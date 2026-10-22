<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;

/**
 * ====================================================================
 * TOP GOL - Controlador de Productos e Inventario
 * ====================================================================
 * CRUD de productos, categorías dinámicas, control de stock y kardex.
 */
class ProductoController extends Controller {

    /**
     * Lista el catálogo de productos con filtros por categoría, stock y búsqueda
     */
    public function index(): void {
        $this->requireAdmin();

        $categoriaId = trim($_GET['categoria'] ?? '');
        $busqueda = trim($_GET['q'] ?? '');
        $filtro = trim($_GET['filtro'] ?? '');
        $soloStockBajo = ($filtro === 'stock_bajo');

        $modelo = new Producto();
        $modeloCat = new Categoria();

        $productos = $modelo->obtenerTodos($categoriaId, $busqueda, $soloStockBajo);
        $categorias = $modeloCat->obtenerTodas();

        $this->view('productos/index', [
            'titulo'        => 'Productos - Panel Administrador',
            'adminMenu'     => $soloStockBajo ? 'inventario' : 'productos',
            'productos'     => $productos,
            'categorias'    => $categorias,
            'filtroCat'     => $categoriaId,
            'busqueda'      => $busqueda,
            'filtro'        => $filtro,
            'stockBajo'     => $modelo->contarStockBajo(),
            'valorizacion'  => $modelo->valorizacion(),
        ], false);
    }

    /**
     * Formulario de nuevo producto
     */
    public function create(): void {
        $this->requireAdmin();

        $modeloCat = new Categoria();
        $categorias = $modeloCat->obtenerTodas(true);

        $this->view('productos/form', [
            'titulo'     => 'Nuevo Producto - Panel Administrador',
            'adminMenu'  => 'productos',
            'categorias' => $categorias,
            'producto'   => null,
        ], false);
    }

    /**
     * Guarda un nuevo producto
     */
    public function store(): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $datos = $this->datosValidados();
        if ($datos === null) {
            $this->redirect('/admin/productos/crear');
        }

        $admin = currentUser();
        $adminId = $admin ? (int)$admin['id'] : null;

        $modelo = new Producto();
        $id = $modelo->crear($datos, $adminId);

        if ($id > 0) {
            sessionFlash('success', "Producto '{$datos['nombre']}' registrado correctamente en el catálogo.", 'success');
            $this->redirect('/admin/productos');
        }

        sessionFlash('error', 'No se pudo registrar el producto.', 'danger');
        $this->redirect('/admin/productos/crear');
    }

    /**
     * Formulario de edición de producto
     */
    public function edit(string|int $id): void {
        $this->requireAdmin();

        $modelo = new Producto();
        $producto = $modelo->obtenerPorId((int)$id);
        if (!$producto) {
            sessionFlash('error', 'Producto no encontrado.', 'warning');
            $this->redirect('/admin/productos');
        }

        $modeloCat = new Categoria();
        $categorias = $modeloCat->obtenerTodas();

        // Obtener últimos movimientos de inventario de este producto
        $modeloMov = new MovimientoInventario();
        $movimientos = $modeloMov->obtenerPorProducto((int)$id, 10);

        $this->view('productos/form', [
            'titulo'      => "Editar: {$producto['nombre']} - Panel Administrador",
            'adminMenu'   => 'productos',
            'categorias'  => $categorias,
            'producto'    => $producto,
            'movimientos' => $movimientos,
        ], false);
    }

    /**
     * Actualiza un producto existente
     */
    public function update(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idProducto = (int)$id;
        $datos = $this->datosValidados();
        if ($datos === null) {
            $this->redirect("/admin/productos/editar/{$idProducto}");
        }

        $admin = currentUser();
        $adminId = $admin ? (int)$admin['id'] : null;

        $modelo = new Producto();
        if ($modelo->actualizar($idProducto, $datos, $adminId)) {
            sessionFlash('success', "Producto '{$datos['nombre']}' actualizado.", 'success');
        } else {
            sessionFlash('info', 'Sin cambios en el producto.', 'info');
        }
        $this->redirect('/admin/productos');
    }

    /**
     * Registra un ingreso o salida manual de stock (Kardex seguro)
     */
    public function ajustarStock(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idProducto = (int)$id;
        $post = $this->sanitizePost();
        $tipo = trim((string)($post['tipo'] ?? 'ingreso'));
        $cantidad = (int)($post['cantidad'] ?? 0);
        $motivo = trim((string)($post['motivo'] ?? ''));

        $admin = currentUser();
        $adminId = $admin ? (int)$admin['id'] : null;

        $modelo = new Producto();
        $res = $modelo->ajustarStock($idProducto, $tipo, $cantidad, $adminId, $motivo);

        if ($this->isAjax()) {
            $this->json($res, $res['ok'] ? 200 : 400);
        }

        if ($res['ok']) {
            sessionFlash('success', $res['mensaje'] ?? 'Stock ajustado con éxito.', 'success');
        } else {
            sessionFlash('error', $res['error'] ?? 'No se pudo ajustar el stock.', 'danger');
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? "/admin/productos/editar/{$idProducto}");
    }

    /**
     * Intenta eliminar un producto (protegido contra productos con ventas o movimientos)
     */
    public function delete(string|int $id): void {
        $this->requireAdmin();
        $this->soloPost('/admin/productos', 'Para eliminar un producto usa el botón de la lista: abrir el enlace ya no ejecuta la acción.');
        $this->validateCsrf();

        $idProducto = (int)$id;
        $modelo = new Producto();
        $res = $modelo->eliminarSeguro($idProducto);

        if ($res['ok']) {
            sessionFlash('success', $res['mensaje'], 'success');
        } else {
            sessionFlash('error', $res['error'], 'warning');
        }

        $this->redirect('/admin/productos');
    }

    /**
     * Descontinúa un producto para que no aparezca en venta pero conserve su histórico
     */
    public function descontinuar(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idProducto = (int)$id;
        $modelo = new Producto();
        $ok = $modelo->descontinuar($idProducto);

        if ($ok) {
            sessionFlash('success', 'Producto marcado como descontinuado. No estará disponible en el mostrador.', 'info');
        } else {
            sessionFlash('error', 'No se pudo actualizar el estado del producto.', 'danger');
        }

        $this->redirect('/admin/productos');
    }

    /**
     * Vista general de inventario y kardex de movimientos
     */
    public function inventario(): void {
        $this->requireAdmin();

        $productoId = (int)($_GET['producto_id'] ?? 0);
        $tipo = trim($_GET['tipo'] ?? '');
        $fecha = trim($_GET['fecha'] ?? '');

        $modeloMov = new MovimientoInventario();
        $modeloProd = new Producto();

        $movimientos = $modeloMov->obtenerHistorial([
            'producto_id' => $productoId,
            'tipo'        => $tipo,
            'fecha'       => $fecha,
        ], 100);

        $productos = $modeloProd->obtenerTodos();
        $stockBajo = $modeloProd->obtenerStockCritico(50);

        $this->view('productos/inventario', [
            'titulo'      => 'Control de Inventario y Kardex - Panel Administrador',
            'adminMenu'   => 'inventario',
            'movimientos' => $movimientos,
            'productos'   => $productos,
            'stockBajo'   => $stockBajo,
            'filtroProd'  => $productoId,
            'filtroTipo'  => $tipo,
            'filtroFecha' => $fecha,
        ], false);
    }

    /**
     * Valida y normaliza los datos del formulario de producto
     *
     * @return array<string, mixed>|null Null si hay errores
     */
    private function datosValidados(): ?array {
        $post = $this->sanitizePost();
        $nombre = trim((string)($post['nombre'] ?? ''));
        $categoriaId = (int)($post['categoria_id'] ?? 5);
        $precio = (float)($post['precio'] ?? 0.0);
        $stock = (int)($post['stock'] ?? 0);
        $stockMin = (int)($post['stock_minimo'] ?? 5);
        $estado = trim((string)($post['estado'] ?? 'disponible'));

        if ($nombre === '') {
            sessionFlash('error', 'El nombre del producto es obligatorio.', 'danger');
            return null;
        }
        if ($precio <= 0) {
            sessionFlash('error', 'El precio debe ser mayor a S/ 0.00.', 'danger');
            return null;
        }
        if ($stock < 0 || $stockMin < 0) {
            sessionFlash('error', 'Las cantidades de stock no pueden ser negativas.', 'danger');
            return null;
        }
        if (!in_array($estado, Producto::ESTADOS, true)) {
            $estado = 'disponible';
        }

        return [
            'nombre'       => $nombre,
            'descripcion'  => trim((string)($post['descripcion'] ?? '')),
            'categoria_id' => $categoriaId,
            'precio'       => round($precio, 2),
            'stock'        => $stock,
            'stock_minimo' => $stockMin,
            'unidad'       => trim((string)($post['unidad'] ?? 'unidad')) ?: 'unidad',
            'estado'       => $estado,
        ];
    }
}
