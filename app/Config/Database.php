<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

/**
 * ====================================================================
 * TOP GOL - Conexión de Base de Datos (PDO Singleton)
 * ====================================================================
 * Gestiona la conexión persistente y segura a la base de datos MySQL.
 */
class Database {
    private static ?PDO $instancia = null;

    /**
     * Constructor privado para prevenir instanciación directa (Singleton)
     */
    private function __construct() {}

    /**
     * Prevenir clonación de la instancia
     */
    private function __clone() {}

    /**
     * Obtiene la conexión activa a PDO o la crea si no existe
     *
     * @return PDO Instancia compartida de PDO
     * @throws PDOException Si la conexión falla
     */
    public static function getConnection(): PDO {
        if (self::$instancia === null) {
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $port = defined('DB_PORT') ? DB_PORT : '3306';
            $dbName = defined('DB_NAME') ? DB_NAME : 'topgol_db';
            $user = defined('DB_USER') ? DB_USER : 'root';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}'"
            ];

            try {
                self::$instancia = new PDO($dsn, $user, $pass, $opciones);
            } catch (PDOException $e) {
                // Si estamos en entorno de desarrollo, mostrar mensaje explicativo
                $mensaje = "Error de conexión a la base de datos: " . $e->getMessage();
                if (defined('APP_ENV') && APP_ENV === 'development') {
                    die("<div style='font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;border:1px solid #f87171;border-radius:8px;max-width:700px;margin:50px auto;'>
                        <h3 style='margin-top:0;'>⚠️ Error de Conexión en TOP GOL</h3>
                        <p>{$mensaje}</p>
                        <p><strong>Verifica lo siguiente:</strong></p>
                        <ul>
                            <li>Que el servicio MySQL en XAMPP esté iniciado.</li>
                            <li>Que la base de datos <code>topgol_db</code> haya sido importada usando el archivo <code>database/schema.sql</code>.</li>
                            <li>Que las credenciales en <code>.env</code> sean correctas.</li>
                        </ul>
                    </div>");
                }
                die("Error interno de conexión al servidor de datos. Intente más tarde.");
            }
        }

        return self::$instancia;
    }
}