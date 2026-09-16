<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Producto;

/**
 * ====================================================================
 * TOP GOL - Controlador de Productos (Inventario)
 * ====================================================================
 * CRUD de productos del bar/kiosco solo para administradores.
 */
class ProductoController extends Controller {

    /**
     * Lista el inventario con filtros por categoría, stock y búsqueda
     */
    public function index(): void {
        $this->requireAdmin();

        $categoria = trim($_GET['categoria'] ?? '');
        $busqueda = trim($_GET['q'] ?? '');
        $filtro = trim($_GET['filtro'] ?? '');
        $soloStockBajo = ($filtro === 'stock_bajo');

        $modelo = new Producto();
        $productos = $modelo->obtenerTodos($categoria, $busqueda, $soloStockBajo);

        $this->view('productos/index', [
            'titulo'        => 'Productos - Panel Administrador',
            'adminMenu'     => $soloStockBajo ? 'inventario' : 'productos',
            'productos'     => $productos,
            'categorias'    => Producto::CATEGORIAS,
            'filtroCat'     => $categoria,
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
        $this->view('productos/form', [
            'titulo'     => 'Nuevo Producto - Panel Administrador',
            'adminMenu'  => 'productos',
            'categorias' => Producto::CATEGORIAS,
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

        $modelo = new Producto();
        $id = $modelo->crear($datos);

        if ($id > 0) {
            sessionFlash('success', "Producto '{$datos['nombre']}' registrado correctamente.", 'success');
            $this->redirect('/admin/productos');
        }
        sessionFlash('error', 'No se pudo registrar el producto.', 'danger');
        $this->redirect('/admin/productos/crear');
    }

    /**
     * Formulario de edición
     */
    public function edit(string|int $id): void {
        $this->requireAdmin();

        $modelo = new Producto();
        $producto = $modelo->obtenerPorId((int)$id);
        if (!$producto) {
            sessionFlash('error', 'Producto no encontrado.', 'warning');
            $this->redirect('/admin/productos');
        }

        $this->view('productos/form', [
            'titulo'     => "Editar: {$producto['nombre']} - Panel Administrador",
            'adminMenu'  => 'productos',
            'categorias' => Producto::CATEGORIAS,
            'producto'   => $producto,
        ], false);
    }

    /**
     * Actualiza un producto
     */
    public function update(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idProducto = (int)$id;
        $datos = $this->datosValidados();
        if ($datos === null) {
            $this->redirect("/admin/productos/editar/{$idProducto}");
        }

        $modelo = new Producto();
        if ($modelo->actualizar($idProducto, $datos)) {
            sessionFlash('success', "Producto '{$datos['nombre']}' actualizado.", 'success');
        } else {
            sessionFlash('info', 'Sin cambios en el producto.', 'info');
        }
        $this->redirect('/admin/productos');
    }

    /**
     * Elimina un producto
     */
    public function delete(string|int $id): void {
        $this->requireAdmin();

        $modelo = new Producto();
        $producto = $modelo->obtenerPorId((int)$id);
        if (!$producto) {
            sessionFlash('error', 'Producto no encontrado.', 'warning');
            $this->redirect('/admin/productos');
        }

        $modelo->eliminar((int)$id);
        sessionFlash('success', "Producto '{$producto['nombre']}' eliminado.", 'success');
        $this->redirect('/admin/productos');
    }

    /**
     * Valida y normaliza los datos del formulario
     *
     * @return array<string, mixed>|null Null si hay errores (ya redirige con flash)
     */
    private function datosValidados(): ?array {
        $post = $this->sanitizePost();
        $nombre = trim($post['nombre'] ?? '');
        $categoria = trim($post['categoria'] ?? 'otros');
        $precio = (float)($post['precio'] ?? 0);
        $stock = (int)($post['stock'] ?? 0);
        $stockMin = (int)($post['stock_minimo'] ?? 5);
        $estado = trim($post['estado'] ?? 'disponible');

        if ($nombre === '') {
            sessionFlash('error', 'El nombre del producto es obligatorio.', 'danger');
            return null;
        }
        if (!isset(Producto::CATEGORIAS[$categoria])) {
            $categoria = 'otros';
        }
        if ($precio <= 0) {
            sessionFlash('error', 'El precio debe ser mayor a 0.', 'danger');
            return null;
        }
        if ($stock < 0 || $stockMin < 0) {
            sessionFlash('error', 'El stock no puede ser negativo.', 'danger');
            return null;
        }
        if (!in_array($estado, Producto::ESTADOS, true)) {
            $estado = 'disponible';
        }

        return [
            'nombre'       => $nombre,
            'descripcion'  => trim($post['descripcion'] ?? ''),
            'categoria'    => $categoria,
            'precio'       => $precio,
            'stock'        => $stock,
            'stock_minimo' => $stockMin,
            'unidad'       => trim($post['unidad'] ?? 'unidad') ?: 'unidad',
            'estado'       => $estado,
        ];
    }
}
