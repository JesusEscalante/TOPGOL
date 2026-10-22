<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Categoria;

/**
 * ====================================================================
 * TOP GOL - Controlador de Categorías
 * ====================================================================
 * Permite administrar las categorías dinámicas de productos del bar/kiosco.
 */
class CategoriaController extends Controller {

    /**
     * Listado de categorías del catálogo
     */
    public function index(): void {
        $this->requireAdmin();

        $modelo = new Categoria();
        $categorias = $modelo->obtenerTodas(false);

        $this->view('categorias/index', [
            'titulo'     => 'Categorías de Productos - Panel Administrador',
            'adminMenu'  => 'productos',
            'categorias' => $categorias,
        ], false);
    }

    /**
     * Guarda una nueva categoría dinámica
     */
    public function store(): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $post = $this->sanitizePost();
        $nombre = trim((string)($post['nombre'] ?? ''));
        $slug = trim((string)($post['slug'] ?? ''));
        $icono = trim((string)($post['icono'] ?? 'bi-box')) ?: 'bi-box';
        $descripcion = trim((string)($post['descripcion'] ?? ''));
        $activo = !empty($post['activo']) ? 1 : 0;

        if ($nombre === '') {
            sessionFlash('error', 'El nombre de la categoría es obligatorio.', 'danger');
            $this->redirect('/admin/categorias');
        }

        $modelo = new Categoria();
        $id = $modelo->crear([
            'nombre'      => $nombre,
            'slug'        => $slug,
            'descripcion' => $descripcion,
            'icono'       => $icono,
            'activo'      => $activo,
        ]);

        if ($id > 0) {
            sessionFlash('success', "Categoría '{$nombre}' creada exitosamente.", 'success');
        } else {
            sessionFlash('error', 'No se pudo crear la categoría.', 'danger');
        }

        $this->redirect('/admin/categorias');
    }

    /**
     * Actualiza una categoría existente
     */
    public function update(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idCat = (int)$id;
        $post = $this->sanitizePost();
        $nombre = trim((string)($post['nombre'] ?? ''));
        $slug = trim((string)($post['slug'] ?? ''));
        $icono = trim((string)($post['icono'] ?? 'bi-box')) ?: 'bi-box';
        $descripcion = trim((string)($post['descripcion'] ?? ''));
        $activo = !empty($post['activo']) ? 1 : 0;

        if ($nombre === '') {
            sessionFlash('error', 'El nombre de la categoría es obligatorio.', 'danger');
            $this->redirect('/admin/categorias');
        }

        $modelo = new Categoria();
        $ok = $modelo->actualizar($idCat, [
            'nombre'      => $nombre,
            'slug'        => $slug,
            'descripcion' => $descripcion,
            'icono'       => $icono,
            'activo'      => $activo,
        ]);

        if ($ok) {
            sessionFlash('success', "Categoría '{$nombre}' actualizada.", 'success');
        } else {
            sessionFlash('info', 'Sin cambios en la categoría.', 'info');
        }

        $this->redirect('/admin/categorias');
    }

    /**
     * Alterna el estado activo de una categoría
     */
    public function toggle(string|int $id): void {
        $this->requireAdmin();
        $this->validateCsrf();

        $idCat = (int)$id;
        $modelo = new Categoria();
        $modelo->toggleActivo($idCat);

        sessionFlash('success', 'Estado de categoría actualizado.', 'success');
        $this->redirect('/admin/categorias');
    }

    /**
     * Elimina una categoría (protegida si tiene productos asociados)
     */
    public function delete(string|int $id): void {
        $this->requireAdmin();
        $this->soloPost('/admin/categorias', 'Para eliminar una categoría usa el botón de la lista: abrir el enlace ya no ejecuta la acción.');
        $this->validateCsrf();

        $idCat = (int)$id;
        $modelo = new Categoria();
        $res = $modelo->eliminar($idCat);

        if ($res['ok']) {
            sessionFlash('success', $res['mensaje'], 'success');
        } else {
            sessionFlash('error', $res['error'], 'warning');
        }

        $this->redirect('/admin/categorias');
    }
}
