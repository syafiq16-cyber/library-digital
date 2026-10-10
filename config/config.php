<?php
/**
 * Konfigurasi Utama & Global Helpers
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

// Aktifkan session jika belum
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Set Zona Waktu WITA (Sumbawa Besar, NTB)
date_default_timezone_set('Asia/Makassar');

// Sertakan file koneksi database
require_once __DIR__ . '/database.php';

// Deteksi Otomatis Base URL
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Cari root direktori proyek relatif terhadap document root
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $appDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $subDir = str_replace($docRoot, '', $appDir);
    $subDir = trim($subDir, '/');

    $baseUrl = $protocol . $host . ($subDir ? '/' . $subDir : '');
    define('BASE_URL', rtrim($baseUrl, '/'));
}

// Direktori Unggahan
define('UPLOAD_PATH', realpath(__DIR__ . '/../uploads'));
define('UPLOAD_COVER_PATH', UPLOAD_PATH . '/covers');
define('UPLOAD_ANGGOTA_PATH', UPLOAD_PATH . '/anggota');
define('UPLOAD_BACKUP_PATH', UPLOAD_PATH . '/backups');

/**
 * Mendapatkan URL lengkap aplikasi
 */
function base_url(string $path = ''): string {
    $path = ltrim($path, '/');
    return BASE_URL . ($path ? '/' . $path : '');
}

/**
 * Mendapatkan URL aset statis
 */
function asset_url(string $path = ''): string {
    return base_url('assets/' . ltrim($path, '/'));
}

/**
 * Sanitasi string input untuk mencegah XSS
 */
function sanitize(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format mata uang Rupiah
 */
function format_rupiah($nominal): string {
    return 'Rp' . number_format((float)$nominal, 0, ',', '.');
}

/**
 * Format tanggal Indonesia
 */
function format_date(?string $datetime, bool $with_time = false): string {
    if (!$datetime || $datetime === '0000-00-00' || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    $timestamp = strtotime($datetime);
    if (!$timestamp) return '-';

    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $hari = date('d', $timestamp);
    $bulan = $bulanIndo[(int)date('m', $timestamp)];
    $tahun = date('Y', $timestamp);

    $formatted = "$hari $bulan $tahun";
    if ($with_time) {
        $formatted .= ' ' . date('H:i', $timestamp) . ' WITA';
    }
    return $formatted;
}

/**
 * Ambil nilai pengaturan sistem dari tabel setting
 */
function get_setting(string $key, string $default = ''): string {
    static $settingsCache = [];
    if (isset($settingsCache[$key])) {
        return $settingsCache[$key];
    }

    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT nilai FROM setting WHERE kunci = :kunci LIMIT 1");
        $stmt->execute([':kunci' => $key]);
        $row = $stmt->fetch();
        $val = $row ? $row['nilai'] : $default;
        $settingsCache[$key] = $val;
        return $val;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Perbarui pengaturan sistem
 */
function update_setting(string $key, string $value): bool {
    try {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO setting (kunci, nilai) VALUES (:kunci, :nilai) ON DUPLICATE KEY UPDATE nilai = :nilai_upd");
        return $stmt->execute([
            ':kunci' => $key,
            ':nilai' => $value,
            ':nilai_upd' => $value
        ]);
    } catch (Exception $e) {
        error_log("Error updating setting $key: " . $e->getMessage());
        return false;
    }
}

/**
 * Pasang pesan kilat (flash notification)
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Ambil dan bersihkan pesan kilat
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Mencatat audit trail ke tabel log_aktivitas
 */
function log_activity(?int $userId, string $modul, string $aksi, string $keterangan): bool {
    try {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO log_aktivitas (user_id, modul, aksi, keterangan, ip_address, user_agent) VALUES (:uid, :modul, :aksi, :ket, :ip, :ua)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Unknown', 0, 250);
        return $stmt->execute([
            ':uid'    => $userId,
            ':modul'  => $modul,
            ':aksi'   => $aksi,
            ':ket'    => $keterangan,
            ':ip'     => $ip,
            ':ua'     => $ua
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Ambil IP Pengguna
 */
function get_client_ip(): string {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}
