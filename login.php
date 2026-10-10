<?php
/**
 * Halaman Login Sistem
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/User.php';

// Jika sudah login, langsung ke dashboard
if (is_logged_in()) {
    header('Location: ' . base_url('index.php?page=dashboard'));
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Sesi keamanan tidak valid (CSRF token expired). Silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($username) || empty($password)) {
            $error = 'Silakan isi username dan password Anda.';
        } else {
            $userModel = new User();
            $user = $userModel->authenticate($username, $password);

            if ($user) {
                login_user($user, $remember);
                set_flash('success', "Selamat datang kembali, {$user['nama_lengkap']}!");
                header('Location: ' . base_url('index.php?page=dashboard'));
                exit;
            } else {
                $error = 'Username atau password salah, atau akun Anda dinonaktifkan.';
            }
        }
    }
}

$schoolName = get_setting('nama_sekolah', 'SMK Negeri 2 Sumbawa Besar');
$appName = get_setting('nama_perpustakaan', 'PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= asset_url('images/logo_smkn2.png') ?>">
    <link rel="stylesheet" href="<?= asset_url('vendor/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <style>
        body {
            background: linear-gradient(135deg, #0b1120 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 440px;
            padding: 36px 32px;
            color: #f8fafc;
        }

        .login-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            filter: drop-shadow(0 4px 10px rgba(37, 99, 235, 0.5));
            margin-bottom: 12px;
        }

        .form-control-custom {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-control-custom:focus {
            background: rgba(30, 41, 59, 0.95);
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.2);
            color: #ffffff;
        }

        .input-group-text-custom {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-left: none;
            color: #94a3b8;
            cursor: pointer;
            border-radius: 0 10px 10px 0;
        }

        .btn-login {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
            font-size: 15px;
            letter-spacing: 0.5px;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.6);
        }

        .demo-box {
            background: rgba(30, 41, 59, 0.5);
            border: 1px dashed rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 12px;
            color: #cbd5e1;
            margin-top: 24px;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <img src="<?= asset_url('images/logo_smkn2.png') ?>" alt="Logo SMKN 2 Sumbawa Besar" class="login-logo">
            <h4 class="fw-bold mb-1" style="font-size: 18px; letter-spacing: 0.5px;">
                <?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></h4>
            <div class="text-info fw-semibold small">
                <?= htmlspecialchars(str_replace($schoolName, '', $appName), ENT_QUOTES, 'UTF-8') ?: 'PERPUSTAKAAN DIGITAL' ?>
            </div>
            <p class="text-muted small mt-1">Masuk untuk mengakses layanan perpustakaan</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 small border-0 mb-3"
                style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; border-radius: 8px;">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('login.php') ?>" autocomplete="off">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label small text-muted">Username</label>
                <div class="input-group">
                    <input type="text" class="form-control form-control-custom" id="username" name="username"
                        value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" placeholder="Masukkan username"
                        required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small text-muted">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control form-control-custom border-end-0" id="password"
                        name="password" placeholder="Masukkan password" required>
                    <span class="input-group-text input-group-text-custom" id="togglePasswordBtn">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember"
                        style="cursor: pointer;">
                    <label class="form-check-label small text-muted" for="remember" style="cursor: pointer;">
                        Ingat Saya
                    </label>
                </div>
                <a href="<?= base_url('index.php?page=katalog') ?>" class="small text-info text-decoration-none">
                    <i class="bi bi-journals me-1"></i>Katalog Publik
                </a>
            </div>

            <button type="submit" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Sistem
            </button>
        </form>

        <!-- Kredensial Default Info Box -->
        <div class="demo-box">
            <div class="fw-bold text-white mb-1"><i class="bi bi-info-circle text-info me-1"></i> Akun Pengujian Sistem:
            </div>
            <div class="d-flex justify-content-between">
                <span>Admin: <code>admin</code></span>
                <span>Pass: <code>password123</code></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Petugas: <code>petugas1</code></span>
                <span>Pass: <code>password123</code></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Kepala: <code>kepala</code></span>
                <span>Pass: <code>password123</code></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Siswa: <code>siswa1</code></span>
                <span>Pass: <code>password123</code></span>
            </div>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.className = 'bi bi-eye-slash';
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.className = 'bi bi-eye';
                }
            });
        }
    </script>

</body>

</html>