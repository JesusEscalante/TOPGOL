<?php
declare(strict_types=1);

namespace App\Core;

/**
 * ====================================================================
 * TOP GOL - Enrutador (Router)
 * ====================================================================
 * Gestiona el registro y despacho de rutas HTTP (GET y POST),
 * soporta parámetros dinámicos {param} y verificación de middlewares.
 */
class Router {
    /**
     * @var array<string, array<int, array{ruta: string, regex: string, accion: string, params: array<int, string>, middlewares: array<int, string>}>>
     */
    private array $rutas = [
        'GET'  => [],
        'POST' => []
    ];

    /**
     * Registra una ruta GET
     *
     * @param string $ruta Patrón de la ruta (ej: '/cancha/editar/{id}')
     * @param string $accion Controlador y método en formato 'Controlador@metodo'
     * @param array<int, string> $middlewares Lista de filtros ('auth', 'admin', 'guest')
     */
    public function get(string $ruta, string $accion, array $middlewares = []): void {
        $this->registrar('GET', $ruta, $accion, $middlewares);
    }

    /**
     * Registra una ruta POST
     *
     * @param string $ruta Patrón de la ruta (ej: '/cancha/guardar')
     * @param string $accion Controlador y método en formato 'Controlador@metodo'
     * @param array<int, string> $middlewares Lista de filtros ('auth', 'admin', 'guest')
     */
    public function post(string $ruta, string $accion, array $middlewares = []): void {
        $this->registrar('POST', $ruta, $accion, $middlewares);
    }

    /**
     * Procesa y registra internamente la ruta convirtiendo los parámetros {nombre} en expresiones regulares
     */
    private function registrar(string $metodo, string $ruta, string $accion, array $middlewares): void {
        $rutaNormalizada = '/' . trim($ruta, '/');
        if ($rutaNormalizada === '//') {
            $rutaNormalizada = '/';
        }

        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $rutaNormalizada, $matches);
        $nombresParametros = $matches[1] ?? [];

        $regex = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $rutaNormalizada);
        $regexFinal = '#^' . $regex . '$#';

        $this->rutas[$metodo][] = [
            'ruta'        => $rutaNormalizada,
            'regex'       => $regexFinal,
            'accion'      => $accion,
            'params'      => $nombresParametros,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Despacha la solicitud actual hacia el controlador correspondiente
     */
    public function dispatch(): void {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($metodo === 'POST' && isset($_POST['_method'])) {
            $metodo = strtoupper((string)$_POST['_method']);
        }

        $uri = $this->obtenerRutaActual();

        if (!isset($this->rutas[$metodo])) {
            $this->mostrar404("Método HTTP no soportado: {$metodo}");
            return;
        }

        foreach ($this->rutas[$metodo] as $rutaInfo) {
            if (preg_match($rutaInfo['regex'], $uri, $matches)) {
                array_shift($matches); // Eliminar la coincidencia completa

                // Ejecutar middlewares asignados a la ruta
                $this->ejecutarMiddlewares($rutaInfo['middlewares']);

                // Despachar el controlador
                $this->ejecutarAccion($rutaInfo['accion'], $matches);
                return;
            }
        }

        // Si ninguna ruta coincidió
        $this->mostrar404("La página solicitada ({$uri}) no existe en este servidor.");
    }

    /**
     * Extrae y normaliza la ruta actual desde $_GET['url'] o $_SERVER['REQUEST_URI']
     * garantizando compatibilidad absoluta con subcarpetas en XAMPP o VirtualHosts.
     */
    private function obtenerRutaActual(): string {
        // 1. Si viene por parámetro url desde .htaccess (y no es index.php ni vacío)
        if (!empty($_GET['url']) && $_GET['url'] !== 'index.php') {
            $uri = '/' . trim((string)$_GET['url'], '/');
            return $uri === '//' ? '/' : $uri;
        }

        // 2. Extraer de REQUEST_URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remover Query String (?foo=bar)
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        // Remover la ruta base del proyecto según URL_BASE (ej: /topgol)
        if (defined('URL_BASE')) {
            $basePath = parse_url(URL_BASE, PHP_URL_PATH) ?? '';
            if (!empty($basePath) && $basePath !== '/' && str_starts_with($requestUri, $basePath)) {
                $requestUri = substr($requestUri, strlen($basePath));
            }
        }

        // Remover la carpeta física del script si aún estuviese presente
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', $scriptDir);
        if ($scriptDir !== '/' && $scriptDir !== '.' && $scriptDir !== '' && str_starts_with($requestUri, $scriptDir)) {
            $requestUri = substr($requestUri, strlen($scriptDir));
        }

        // Remover prefijos como /public o /index.php si quedaron en la ruta
        $requestUri = preg_replace('#^/public(/|$)#', '$1', $requestUri);
        $requestUri = preg_replace('#^/index\.php(/|$)#', '$1', $requestUri);

        $uri = '/' . trim($requestUri, '/');
        return $uri === '//' ? '/' : $uri;
    }

    /**
     * Evalúa los middlewares requeridos para la ruta
     *
     * @param array<int, string> $middlewares
     */
    private function ejecutarMiddlewares(array $middlewares): void {
        foreach ($middlewares as $mw) {
            if ($mw === 'auth') {
                if (!isLoggedIn()) {
                    sessionFlash('error', 'Debes iniciar sesión para acceder a esta función.', 'warning');
                    redirect('/login');
                }
            } elseif ($mw === 'admin') {
                if (!isLoggedIn()) {
                    sessionFlash('error', 'Debes iniciar sesión con una cuenta de Administrador.', 'warning');
                    redirect('/login');
                }
                if (!isAdmin()) {
                    sessionFlash('error', 'Acceso denegado: solo personal administrativo.', 'danger');
                    redirect('/');
                }
            } elseif ($mw === 'guest') {
                if (isLoggedIn()) {
                    redirect('/');
                }
            }
        }
    }

    /**
     * Instancia el controlador y llama al método pasándole los argumentos capturados
     *
     * @param string $accion Formato 'NombreControlador@metodo'
     * @param array<int, string> $params Valores de los parámetros en la URL
     */
    private function ejecutarAccion(string $accion, array $params): void {
        [$controladorNombre, $metodo] = explode('@', $accion);

        $claseCompleta = "App\\Controllers\\{$controladorNombre}";

        if (!class_exists($claseCompleta)) {
            die("<div style='font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;margin:30px;border-radius:6px;'>
                <h3>Error en el Enrutador</h3>
                <p>No se encontró la clase del controlador: <code>{$claseCompleta}</code></p>
            </div>");
        }

        $controlador = new $claseCompleta();

        if (!method_exists($controlador, $metodo)) {
            die("<div style='font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;margin:30px;border-radius:6px;'>
                <h3>Error en el Enrutador</h3>
                <p>El método <code>{$metodo}()</code> no existe en el controlador <code>{$claseCompleta}</code></p>
            </div>");
        }

        call_user_func_array([$controlador, $metodo], $params);
    }

    /**
     * Muestra una página 404 amigable y elegante
     */
    private function mostrar404(string $detalle = ''): void {
        http_response_code(404);
        $titulo = "Página No Encontrada (404) - " . APP_NAME;
        $urlInicio = URL_BASE;
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$titulo}</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
            <style>
                body { background-color: #fdfdfd; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
                .card-404 { background-color: #1e293b; border: 1px solid #334155; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); padding: 40px; text-align: center; max-width: 500px; width: 90%; }
                .badge-topgol { background-color: #1a7a3a; color: #f5c842; font-weight: bold; padding: 6px 14px; border-radius: 20px; display: inline-block; margin-bottom: 20px; }
                .btn-home { background-color: #1a7a3a; color: #ffffff; border: 2px solid #f5c842; font-weight: 600; padding: 10px 24px; border-radius: 8px; text-decoration: none; display: inline-block; transition: all 0.2s; }
                .btn-home:hover { background-color: #145e2c; color: #f5c842; }
            </style>
        </head>
        <body>
            <div class="card-404">
                <div class="badge-topgol"><i class="bi bi-dribbble"></i> TOP GOL</div>
                <h1 class="display-3 fw-bold text-warning mb-2">404</h1>
                <h3 class="h4 mb-3">¡Fuera de Juego! Balón fuera</h3>
                <p class="text-secondary mb-4">La página que buscas no existe o ha sido movida.</p>
                <a href="{$urlInicio}" class="btn-home"><i class="bi bi-house-door-fill me-1"></i> Volver al Inicio</a>
            </div>
        </body>
        </html>
        HTML;
        exit;
    }
}