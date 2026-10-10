<?php
/**
 * Modul Autentikasi, Manajemen Sesi & Kontrol Hak Akses (RBAC)
 * Serta Proteksi CSRF
 */

require_once __DIR__ . '/config.php';

/**
 * Generate CSRF Token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render input field tersembunyi untuk form CSRF
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verifikasi CSRF Token
 */
function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Periksa apakah user telah login
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Dapatkan data user yang sedang login
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return $_SESSION['user'] ?? null;
}

/**
 * Dapatkan Role ID user aktif
 */
function current_role_id(): int {
    return (int)($_SESSION['user']['role_id'] ?? 0);
}

/**
 * Dapatkan Nama Role user aktif
 */
function current_role_name(): string {
    return $_SESSION['user']['nama_role'] ?? '';
}

/**
 * Cek apakah user memiliki peran tertentu
 * $roles dapat berupa string tunggal atau array: ['Administrator', 'Petugas']
 */
function has_role($roles): bool {
    if (!is_logged_in()) {
        return false;
    }
    $userRole = current_role_name();
    if (is_array($roles)) {
        return in_array($userRole, $roles, true);
    }
    return strcasecmp($userRole, (string)$roles) === 0;
}

/**
 * Wajibkan login sebelum mengakses halaman
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Silakan login terlebih dahulu untuk mengakses sistem.');
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/**
 * Wajibkan peran tertentu untuk mengakses halaman
 */
function require_role($roles): void {
    require_login();
    if (!has_role($roles)) {
        set_flash('danger', 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman tersebut.');
        header('Location: ' . base_url('index.php?page=dashboard'));
        exit;
    }
}

/**
 * Daftarkan sesi pengguna setelah login sukses
 */
function login_user(array $user, bool $remember = false): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = [
        'id'           => $user['id'],
        'username'     => $user['username'],
        'nama_lengkap' => $user['nama_lengkap'],
        'email'        => $user['email'] ?? '',
        'role_id'      => $user['role_id'],
        'nama_role'    => $user['nama_role'] ?? ''
    ];

    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $db = get_db();
        $stmt = $db->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
        $stmt->execute([':token' => $token, ':id' => $user['id']]);
        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
    }

    log_activity($user['id'], 'Autentikasi', 'Login', "Pengguna {$user['username']} ({$user['nama_role']}) berhasil login");
}

/**
 * Keluar sistem dan hancurkan sesi
 */
function logout_user(): void {
    if (isset($_SESSION['user_id'])) {
        log_activity($_SESSION['user_id'], 'Autentikasi', 'Logout', 'Pengguna keluar dari sistem');
    }

    if (isset($_COOKIE['remember_token'])) {
        $db = get_db();
        $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = :token");
        $stmt->execute([':token' => $_COOKIE['remember_token']]);
        setcookie('remember_token', '', time() - 3600, '/');
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
