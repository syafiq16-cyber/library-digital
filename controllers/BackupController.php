<?php
/**
 * Controller Backup & Restore Database
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

require_role('Administrator');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user = current_user();

// 1. PROSES BACKUP DATABASE (.sql)
if ($action === 'download' || $action === 'create') {
    try {
        $db = get_db();
        $tables = [];
        $stmt = $db->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sqlDump = "-- ==========================================================\n";
        $sqlDump .= "-- BACKUP DATABASE PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR\n";
        $sqlDump .= "-- Waktu Pembuatan: " . date('Y-m-d H:i:s') . " WITA\n";
        $sqlDump .= "-- Dibuat Oleh: " . $user['nama_lengkap'] . " (" . $user['username'] . ")\n";
        $sqlDump .= "-- ==========================================================\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // DDL
            $createStmt = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sqlDump .= $createStmt[1] . ";\n\n";

            // DML
            $rowsStmt = $db->query("SELECT * FROM `$table`");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $cols = array_keys($rows[0]);
                $colNames = implode('`, `', $cols);

                foreach ($rows as $r) {
                    $vals = [];
                    foreach ($cols as $col) {
                        if ($r[$col] === null) {
                            $vals[] = "NULL";
                        } else {
                            $vals[] = $db->quote($r[$col]);
                        }
                    }
                    $sqlDump .= "INSERT INTO `$table` (`$colNames`) VALUES (" . implode(', ', $vals) . ");\n";
                }
                $sqlDump .= "\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        $filename = 'backup_perpus_' . date('Ymd_His') . '.sql';
        $savePath = UPLOAD_BACKUP_PATH . '/' . $filename;
        file_put_contents($savePath, $sqlDump);

        log_activity($user['id'], 'Database', 'Backup', "Membuat cadangan database: $filename");

        if ($action === 'download') {
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . $filename . "\"");
            echo $sqlDump;
            exit;
        }

        set_flash('success', "File backup database '$filename' berhasil dibuat dan disimpan di server.");
        header('Location: ' . base_url('index.php?page=backup'));
        exit;
    } catch (Exception $e) {
        set_flash('danger', 'Gagal membuat backup database: ' . $e->getMessage());
        header('Location: ' . base_url('index.php?page=backup'));
        exit;
    }
}

// 2. PROSES RESTORE DATABASE
if ($action === 'restore' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=backup'));
        exit;
    }

    if (empty($_FILES['sql_file']['name']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
        set_flash('danger', 'Silakan pilih file SQL yang valid untuk di-restore.');
        header('Location: ' . base_url('index.php?page=backup'));
        exit;
    }

    $ext = strtolower(pathinfo($_FILES['sql_file']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'sql') {
        set_flash('danger', 'Hanya file berekstensi .sql yang dapat di-restore.');
        header('Location: ' . base_url('index.php?page=backup'));
        exit;
    }

    try {
        $sqlContent = file_get_contents($_FILES['sql_file']['tmp_name']);
        $db = get_db();
        $db->exec($sqlContent);

        log_activity($user['id'], 'Database', 'Restore', "Memulihkan database dari file: " . $_FILES['sql_file']['name']);
        set_flash('success', 'Database perpustakaan berhasil dipulihkan secara menyeluruh.');
        header('Location: ' . base_url('index.php?page=backup'));
        exit;
    } catch (Exception $e) {
        set_flash('danger', 'Gagal memulihkan database: ' . $e->getMessage());
        header('Location: ' . base_url('index.php?page=backup'));
        exit;
    }
}

// 3. DOWNLOAD FILE BACKUP YANG SUDAH ADA DI SERVER
if ($action === 'download_existing') {
    $filename = basename($_GET['filename'] ?? '');
    if (empty($filename) || !preg_match('/^backup_perpus_[\w]+\.sql$/', $filename)) {
        set_flash('danger', 'Nama file backup tidak valid.');
        header('Location: ' . base_url('index.php?page=backup'));
        exit;
    }

    $filePath = UPLOAD_BACKUP_PATH . '/' . $filename;
    if (!file_exists($filePath)) {
        set_flash('danger', 'File backup tidak ditemukan: ' . $filename);
        header('Location: ' . base_url('index.php?page=backup'));
        exit;
    }

    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: Binary');
    header('Content-disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}
