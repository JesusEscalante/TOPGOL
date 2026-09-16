<?php
declare(strict_types=1);

namespace App\Core;

/**
 * ====================================================================
 * TOP GOL - Controlador Base
 * ====================================================================
 * Clase base de la que heredan todos los controladores de la aplicación.
 * Proporciona métodos para renderizado de vistas, respuestas JSON,
 * validación de sesiones y protección CSRF.
 */
abstract class Controller {

    /**
     * Renderiza una vista PHP combinándola con el layout principal (header y footer)
     *
     * @param string $vista Ruta de la vista relativa a app/Views (ej: 'canchas/index')
     * @param array<string, mixed> $datos Variables a pasar a la vista
     * @param bool $conLayout Determina si se incluye el header y footer común
     */
    protected function view(string $vista, array $datos = [], bool $conLayout = true): void {
        // Extraer variables para hacerlas accesibles directamente en la vista
        extract($datos);

        $archivoVista = VIEWS_PATH . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $vista) . '.php';

        if (!file_exists($archivoVista)) {
            die("<div style='font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;margin:30px;border-radius:6px;'>
                <h3>Error: Vista no encontrada</h3>
                <p>No se pudo localizar el archivo de la vista: <code>{$archivoVista}</code></p>
            </div>");
        }

        if ($conLayout) {
            $header = VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
            $footer = VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';

            if (file_exists($header)) {
                require_once $header;
            }
            require $archivoVista;
            if (file_exists($footer)) {
                require_once $footer;
            }
        } else {
            require $archivoVista;
        }
    }

    /**
     * Emite una respuesta en formato JSON y finaliza la ejecución
     *
     * @param mixed $datos Contenido a serializar
     * @param int $codigo Código de respuesta HTTP
     * @return never
     */
    protected function json(mixed $datos, int $codigo = 200): void {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Redirige al usuario y finaliza la ejecución
     *
     * @param string $ruta
     * @return never
     */
    protected function redirect(string $ruta): void {
        redirect($ruta);
    }

    /**
     * Exige que el usuario esté autenticado para continuar
     */
    protected function requireAuth(): void {
        if (!isLoggedIn()) {
            sessionFlash('error', 'Debes iniciar sesión para realizar esta acción.', 'warning');
            $this->redirect('/login');
        }
    }

    /**
     * Exige que el usuario tenga rol de Administrador
     */
    protected function requireAdmin(): void {
        $this->requireAuth();
        if (!isAdmin()) {
            sessionFlash('error', 'Acceso restringido. Solo administradores pueden ingresar a este módulo.', 'danger');
            $this->redirect('/');
        }
    }

    /**
     * Exige que el usuario NO haya iniciado sesión (para páginas de login/registro)
     */
    protected function requireGuest(): void {
        if (isLoggedIn()) {
            $this->redirect('/');
        }
    }

    /**
     * Valida el token CSRF recibido en peticiones POST
     * Si no coincide, detiene la solicitud y redirige con error
     */
    protected function validateCsrf(): void {
        if (!validateCsrf()) {
            sessionFlash('error', 'La solicitud ha expirado por inactividad o token CSRF inválido. Intenta de nuevo.', 'danger');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }

    /**
     * Obtiene los datos de $_POST sanitizados
     *
     * @return array<string, string>
     */
    protected function sanitizePost(): array {
        $sanitizado = [];
        foreach ($_POST as $key => $value) {
            if (is_string($value)) {
                $sanitizado[$key] = trim($value);
            } else {
                $sanitizado[$key] = $value;
            }
        }
        return $sanitizado;
    }
}