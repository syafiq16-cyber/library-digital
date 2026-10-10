<?php
/**
 * Layout Header
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

$pageTitle = $pageTitle ?? 'Dashboard';
$appName = get_setting('nama_perpustakaan', 'PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR');
$schoolName = get_setting('nama_sekolah', 'SMK Negeri 2 Sumbawa Besar');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Informasi Perpustakaan Digital SMKN 2 Sumbawa Besar berbasis web modern, responsif, dan terpadu.">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= asset_url('images/logo_smkn2.png') ?>">

    <!-- Bootstrap 5 Offline -->
    <link rel="stylesheet" href="<?= asset_url('vendor/bootstrap.min.css') ?>">
    
    <!-- Bootstrap Icons Offline -->
    <link rel="stylesheet" href="<?= asset_url('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    
    <!-- Custom Modern Style -->
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    
    <!-- Print Stylesheet -->
    <link rel="stylesheet" href="<?= asset_url('css/print.css') ?>" media="print">

    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
        // Muat preferensi tema sebelum DOM selesai agar tidak flicker
        (function() {
            const savedTheme = localStorage.getItem('perpus_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>
<div class="app-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>
