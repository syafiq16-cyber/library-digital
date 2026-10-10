<?php
/**
 * Logout Handler
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';

logout_user();
set_flash('info', 'Anda telah berhasil keluar dari sistem.');
header('Location: ' . base_url('login.php'));
exit;
