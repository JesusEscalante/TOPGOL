<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Usuario;
use App\Services\GoogleOAuth;

/**
 * ====================================================================
 * TOP GOL - Controlador de Autenticación
 * ====================================================================
 * Gestiona el inicio de sesión, registro de nuevos clientes, cierre
 * de sesión y seguridad contra robo o fijación de sesiones.
 * Incluye autenticación con Google OAuth 2.0.
 */
class AuthController extends Controller {

    /**
     * Muestra el formulario de inicio de sesión
     */
    public function showLogin(): void {
        $this->requireGuest();
        $this->view('auth/login', [
            'titulo' => 'Iniciar Sesión - ' . APP_NAME
        ]);
    }

    /**
     * Procesa la solicitud de autenticación
     */
    public function login(): void {
        $this->requireGuest();
        $this->validateCsrf();

        $post = $this->sanitizePost();
        $email = strtolower(trim($post['email'] ?? ''));
        $password = (string)($post['password'] ?? '');

        if (empty($email) || empty($password)) {
            sessionFlash('error', 'Por favor ingresa tu correo y contraseña.', 'danger');
            $this->redirect('/login');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sessionFlash('error', 'El formato del correo electrónico no es válido.', 'danger');
            $this->redirect('/login');
        }

        $modeloUsuario = new Usuario();
        $usuario = $modeloUsuario->obtenerPorEmail($email);

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            sessionFlash('error', 'Credenciales inválidas. Verifica tu correo y contraseña.', 'danger');
            $this->redirect('/login');
        }

        // Seguridad: Regenerar ID de sesión para prevenir Session Fixation
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = (int)$usuario['id'];
        $_SESSION['usuario_nombre'] = (string)$usuario['nombre'];
        $_SESSION['usuario_email'] = (string)$usuario['email'];
        $_SESSION['usuario_rol'] = (string)$usuario['rol'];
        $_SESSION['usuario_telefono'] = (string)($usuario['telefono'] ?? '');

        sessionFlash('success', "¡Bienvenido a TOP GOL, {$usuario['nombre']}!", 'success');

        if ($usuario['rol'] === 'admin') {
            $this->redirect('/admin/dashboard');
        } else {
            $this->redirect('/mis-reservas');
        }
    }

    /**
     * Muestra el formulario de registro para nuevos clientes
     */
    public function showRegister(): void {
        $this->requireGuest();
        $this->view('auth/register', [
            'titulo' => 'Crear Cuenta - ' . APP_NAME
        ]);
    }

    /**
     * Registra un nuevo usuario en la base de datos
     */
    public function register(): void {
        $this->requireGuest();
        $this->validateCsrf();

        $post = $this->sanitizePost();
        $nombre = trim($post['nombre'] ?? '');
        $email = strtolower(trim($post['email'] ?? ''));
        $telefono = trim($post['telefono'] ?? '');
        $password = (string)($post['password'] ?? '');
        $passwordConfirm = (string)($post['password_confirm'] ?? '');

        // Validaciones del lado del servidor
        if (empty($nombre) || empty($email) || empty($password)) {
            sessionFlash('error', 'Todos los campos obligatorios deben ser completados.', 'danger');
            $this->redirect('/register');
        }

        if (strlen($nombre) < 3) {
            sessionFlash('error', 'El nombre completo debe tener al menos 3 caracteres.', 'danger');
            $this->redirect('/register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sessionFlash('error', 'El correo electrónico ingresado no es válido.', 'danger');
            $this->redirect('/register');
        }

        if (strlen($password) < 6) {
            sessionFlash('error', 'La contraseña debe tener un mínimo de 6 caracteres.', 'danger');
            $this->redirect('/register');
        }

        if ($password !== $passwordConfirm) {
            sessionFlash('error', 'Las contraseñas ingresadas no coinciden.', 'danger');
            $this->redirect('/register');
        }

        $modeloUsuario = new Usuario();

        // Validar si el correo ya está registrado
        if ($modeloUsuario->obtenerPorEmail($email) !== null) {
            sessionFlash('error', 'Ya existe una cuenta registrada con ese correo electrónico.', 'warning');
            $this->redirect('/register');
        }

        $nuevoId = $modeloUsuario->crear([
            'nombre'   => $nombre,
            'email'    => $email,
            'password' => $password,
            'telefono' => $telefono,
            'rol'      => 'cliente'
        ]);

        if ($nuevoId > 0) {
            // Iniciar sesión automáticamente
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $nuevoId;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_email'] = $email;
            $_SESSION['usuario_rol'] = 'cliente';
            $_SESSION['usuario_telefono'] = $telefono;

            sessionFlash('success', '¡Tu cuenta ha sido creada exitosamente! Explora nuestras canchas y haz tu primera reserva.', 'success');
            $this->redirect('/canchas');
        } else {
            sessionFlash('error', 'Ocurrió un error al procesar el registro. Intenta nuevamente.', 'danger');
            $this->redirect('/register');
        }
    }

    /**
     * Cierra la sesión activa del usuario
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vaciar array de sesión
        $_SESSION = [];

        // Eliminar la cookie de sesión si existe
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();

        // Iniciar una nueva sesión limpia para mostrar el mensaje flash
        session_start();
        sessionFlash('success', 'Has cerrado tu sesión de forma segura. ¡Vuelve pronto!', 'info');
        $this->redirect('/login');
    }

    /**
     * Inicia el flujo de autenticación con Google
     */
    public function googleLogin(): void {
        $this->requireGuest();

        if (!defined('GOOGLE_CLIENT_ID') || empty(GOOGLE_CLIENT_ID)) {
            sessionFlash('error', 'La autenticación con Google no está configurada. Contacte al administrador.', 'danger');
            $this->redirect('/login');
        }

        $googleOAuth = new GoogleOAuth();
        if (!$googleOAuth->isConfigured()) {
            sessionFlash('error', 'La autenticación con Google no está configurada correctamente.', 'danger');
            $this->redirect('/login');
        }

        $state = $googleOAuth->generateState();
        $authUrl = $googleOAuth->getAuthUrl($state);
        $this->redirect($authUrl);
    }

    /**
     * Callback de Google OAuth - procesa la respuesta de Google
     */
    public function googleCallback(): void {
        $this->requireGuest();

        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $error = $_GET['error'] ?? '';

        if ($error) {
            sessionFlash('error', 'Error en la autenticación con Google: ' . htmlspecialchars($error), 'danger');
            $this->redirect('/login');
        }

        if (empty($code) || empty($state)) {
            sessionFlash('error', 'Respuesta inválida de Google. Intente nuevamente.', 'danger');
            $this->redirect('/login');
        }

        $googleOAuth = new GoogleOAuth();
        $userData = $googleOAuth->authenticate($code, $state);

        if (!$userData) {
            sessionFlash('error', 'No se pudo obtener la información de su cuenta de Google. Intente nuevamente.', 'danger');
            $this->redirect('/login');
        }

        $modeloUsuario = new Usuario();
        $usuario = $modeloUsuario->obtenerPorEmail($userData['email']);

        if ($usuario) {
            // Usuario existe - verificar si ya tiene Google vinculado o vincularlo
            if (empty($usuario['google_id'])) {
                // Vincular cuenta existente con Google
                $modeloUsuario->vincularGoogle($usuario['id'], $userData['google_id'], $userData['avatar']);
                $usuario['google_id'] = $userData['google_id'];
                $usuario['avatar'] = $userData['avatar'];
            } elseif ($usuario['google_id'] !== $userData['google_id']) {
                // El email ya está asociado a otra cuenta de Google
                sessionFlash('error', 'Este correo ya está vinculado a otra cuenta de Google.', 'danger');
                $this->redirect('/login');
            }
        } else {
            // Crear nuevo usuario con Google
            $nuevoId = $modeloUsuario->crearConGoogle([
                'nombre'    => $userData['nombre'],
                'email'     => $userData['email'],
                'google_id' => $userData['google_id'],
                'avatar'    => $userData['avatar'],
            ]);

            if ($nuevoId <= 0) {
                sessionFlash('error', 'Error al crear su cuenta con Google. Intente nuevamente.', 'danger');
                $this->redirect('/login');
            }

            $usuario = $modeloUsuario->obtenerPorId($nuevoId);
        }

        // Iniciar sesión
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int)$usuario['id'];
        $_SESSION['usuario_nombre'] = (string)$usuario['nombre'];
        $_SESSION['usuario_email'] = (string)$usuario['email'];
        $_SESSION['usuario_rol'] = (string)$usuario['rol'];
        $_SESSION['usuario_telefono'] = (string)($usuario['telefono'] ?? '');
        $_SESSION['usuario_avatar'] = (string)($usuario['avatar'] ?? '');

        sessionFlash('success', "¡Bienvenido a TOP GOL, {$usuario['nombre']}!", 'success');

        if ($usuario['rol'] === 'admin') {
            $this->redirect('/reservas');
        } else {
            $this->redirect('/mis-reservas');
        }
    }
}