<?php
/**
 * Konfigurasi Database PDO
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 * Kompatibel dengan XAMPP Windows, Linux, dan macOS
 */

defined('DB_HOST') or define('DB_HOST', '127.0.0.1');
defined('DB_PORT') or define('DB_PORT', '3306');
defined('DB_NAME') or define('DB_NAME', 'perpustakaan_db');
defined('DB_USER') or define('DB_USER', 'root');
defined('DB_PASS') or define('DB_PASS', '');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Coba koneksi via socket jika tersedia di macOS/Linux, atau TCP standard untuk XAMPP
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            if (file_exists('/tmp/mysql.sock')) {
                $dsn_socket = "mysql:unix_socket=/tmp/mysql.sock;dbname=" . DB_NAME . ";charset=utf8mb4";
                try {
                    self::$instance = new PDO($dsn_socket, DB_USER, DB_PASS, $options);
                    return self::$instance;
                } catch (PDOException $e) {
                    // Fallback ke standard TCP
                }
            }

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Jangan menampilkan kredensial sensitif ke publik
                error_log("Database Connection Error: " . $e->getMessage());
                die('<div style="font-family:sans-serif;max-width:600px;margin:50px auto;padding:25px;border:1px solid #f5c6cb;background:#f8d7da;color:#721c24;border-radius:8px;">' .
                    '<h3 style="margin-top:0;">Gagal Terhubung ke Database MySQL</h3>' .
                    '<p>Pastikan layanan <strong>MySQL di XAMPP</strong> sudah diaktifkan dan database <strong>perpustakaan_db</strong> sudah di-import melalui phpMyAdmin.</p>' .
                    '<p style="font-size:12px;color:#856404;background:#fff3cd;padding:8px;border-radius:4px;">Detail konfigurasi: Host: ' . DB_HOST . ' | Database: ' . DB_NAME . ' | User: ' . DB_USER . '</p>' .
                    '</div>');
            }
        }
        return self::$instance;
    }
}

function get_db(): PDO {
    return Database::getConnection();
}
