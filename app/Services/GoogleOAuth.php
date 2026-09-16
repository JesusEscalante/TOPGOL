<?php
declare(strict_types=1);

namespace App\Services;

/**
 * ====================================================================
 * TOP GOL - Servicio de Autenticación Google OAuth 2.0
 * ====================================================================
 * Maneja el flujo de autorización con Google para login social.
 */
class GoogleOAuth
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private const SCOPES = ['openid', 'email', 'profile'];
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';

    public function __construct()
    {
        $this->clientId = GOOGLE_CLIENT_ID ?? '';
        $this->clientSecret = GOOGLE_CLIENT_SECRET ?? '';
        $this->redirectUri = GOOGLE_REDIRECT_URI ?? '';
    }

    /**
     * Verifica si Google OAuth está configurado correctamente
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->redirectUri);
    }

    /**
     * Genera la URL de autorización de Google
     *
     * @param string $state Token CSRF para validar el callback
     * @return string URL de autorización
     */
    public function getAuthUrl(string $state): string
    {
        $params = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => implode(' ', self::SCOPES),
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Intercambia el código de autorización por un token de acceso
     *
     * @param string $code Código de autorización recibido en el callback
     * @return array{access_token: string, expires_in: int, token_type: string, scope: string, refresh_token?: string}|false
     */
    public function getAccessToken(string $code): array|false
    {
        $data = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
        ];

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return false;
        }

        $tokenData = json_decode($response, true);
        return is_array($tokenData) && isset($tokenData['access_token']) ? $tokenData : false;
    }

    /**
     * Obtiene la información del usuario desde Google
     *
     * @param string $accessToken Token de acceso de Google
     * @return array{id: string, email: string, verified_email: bool, name: string, given_name: string, family_name: string, picture: string, locale: string}|false
     */
    public function getUserInfo(string $accessToken): array|false
    {
        $ch = curl_init(self::USERINFO_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return false;
        }

        $userData = json_decode($response, true);
        return is_array($userData) && isset($userData['id']) ? $userData : false;
    }

    /**
     * Ejecuta el flujo completo de OAuth y retorna los datos del usuario
     *
     * @param string $code Código de autorización
     * @param string $state State para validar CSRF
     * @return array{google_id: string, email: string, nombre: string, avatar: string}|false
     */
    public function authenticate(string $code, string $state): array|false
    {
        // Validar state contra CSRF
        if (!hash_equals($_SESSION['oauth_state'] ?? '', $state)) {
            return false;
        }

        $tokenData = $this->getAccessToken($code);
        if (!$tokenData) {
            return false;
        }

        $userInfo = $this->getUserInfo($tokenData['access_token']);
        if (!$userInfo) {
            return false;
        }

        return [
            'google_id' => $userInfo['id'],
            'email'     => strtolower($userInfo['email']),
            'nombre'    => $userInfo['name'],
            'avatar'    => $userInfo['picture'] ?? '',
        ];
    }

    /**
     * Genera y almacena un state CSRF para el flujo OAuth
     */
    public function generateState(): string
    {
        $state = bin2hex(random_bytes(32));
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['oauth_state'] = $state;
        return $state;
    }
}