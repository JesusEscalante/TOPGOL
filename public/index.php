<?php
declare(strict_types=1);

/**
 * ====================================================================
 * TOP GOL - Punto de Entrada Principal (Front Controller)
 * ====================================================================
 * Recibe todas las peticiones HTTP, inicializa el entorno de sesiones,
 * carga la configuración, registra el autoloader y ejecuta el enrutador.
 */

// 1. Configuración de seguridad para sesiones antes de iniciarla
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Cargar configuración y constantes
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// 3. Cargar funciones auxiliares (Helpers)
require_once APP_PATH . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'helpers.php';

// 4. Autoloader PSR-4 para clases con namespace App\
spl_autoload_register(function (string $clase): void {
    $prefijo = 'App\\';
    $longitudPrefijo = strlen($prefijo);

    if (strncmp($prefijo, $clase, $longitudPrefijo) !== 0) {
        return;
    }

    $claseRelativa = substr($clase, $longitudPrefijo);
    $rutaArchivo = APP_PATH . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $claseRelativa) . '.php';

    if (file_exists($rutaArchivo)) {
        require_once $rutaArchivo;
    }
});

// 5. Inicializar Enrutador
$router = new App\Core\Router();

// --------------------------------------------------------------------
// DEFINICIÓN DE RUTAS DEL SISTEMA TOP GOL
// --------------------------------------------------------------------

// Página principal y Home
$router->get('/', 'HomeController@index');

// Catálogo y CRUD de Canchas
$router->get('/canchas', 'CanchaController@index');
$router->get('/cancha/crear', 'CanchaController@create', ['admin']);
$router->post('/cancha/guardar', 'CanchaController@store', ['admin']);
$router->get('/cancha/ver/{id}', 'CanchaController@show');
$router->get('/cancha/editar/{id}', 'CanchaController@edit', ['admin']);
$router->post('/cancha/actualizar/{id}', 'CanchaController@update', ['admin']);
$router->get('/cancha/eliminar/{id}', 'CanchaController@delete', ['admin']);
$router->post('/cancha/eliminar/{id}', 'CanchaController@delete', ['admin']);

// Sistema y CRUD de Reservas
$router->get('/reservas', 'ReservaController@index', ['admin']);
$router->get('/reserva/crear', 'ReservaController@create', ['auth']);
$router->get('/reserva/crear/{id_cancha}', 'ReservaController@create', ['auth']);
$router->post('/reserva/guardar', 'ReservaController@store', ['auth']);
$router->get('/reserva/cancelar/{id}', 'ReservaController@cancel', ['auth']);
$router->post('/reserva/estado/{id}', 'ReservaController@updateStatus', ['admin']);
$router->get('/mis-reservas', 'ReservaController@misReservas', ['auth']);
$router->get('/mis-reservas/{id}', 'ReservaController@detalle', ['auth']);

// Panel Administrador - Reservas
$router->get('/admin/reservas', 'ReservaController@adminPanel', ['admin']);
$router->get('/admin/calendario', 'ReservaController@calendario', ['admin']);
$router->post('/reserva/pago/{id}', 'ReservaController@updatePayment', ['admin']);

// Panel Administrador - Productos (Inventario)
$router->get('/admin/productos', 'ProductoController@index', ['admin']);
$router->get('/admin/productos/crear', 'ProductoController@create', ['admin']);
$router->post('/admin/productos/guardar', 'ProductoController@store', ['admin']);
$router->get('/admin/productos/editar/{id}', 'ProductoController@edit', ['admin']);
$router->post('/admin/productos/actualizar/{id}', 'ProductoController@update', ['admin']);
$router->get('/admin/productos/eliminar/{id}', 'ProductoController@delete', ['admin']);

// API de Disponibilidad (AJAX)
$router->get('/api/cancha/disponibilidad', 'ReservaController@checkAvailability');

// Panel Administrativo y Usuarios
$router->get('/admin/dashboard', 'UsuarioController@dashboard', ['admin']);
$router->get('/admin/usuarios', 'UsuarioController@index', ['admin']);

// Perfil de usuario (admin y clientes)
$router->get('/perfil', 'UsuarioController@perfil', ['auth']);
$router->post('/perfil/actualizar', 'UsuarioController@updatePerfil', ['auth']);
$router->post('/perfil/password', 'UsuarioController@updatePassword', ['auth']);

// Autenticación de Usuarios
$router->get('/login', 'AuthController@showLogin', ['guest']);
$router->post('/login', 'AuthController@login', ['guest']);
$router->get('/register', 'AuthController@showRegister', ['guest']);
$router->post('/register', 'AuthController@register', ['guest']);
$router->get('/logout', 'AuthController@logout', ['auth']);

// Google OAuth
$router->get('/auth/google', 'AuthController@googleLogin', ['guest']);
$router->get('/auth/google/callback', 'AuthController@googleCallback', ['guest']);

// 6. Despachar la petición entrante
$router->dispatch();