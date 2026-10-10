<?php
/**
 * Backup & Restore Database
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

$pageTitle = 'Backup & Restore Database';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

require_role('Administrator');

// Handle aksi dari controller (controller dipanggil di sini agar redirect kembali ke halaman ini)
require_once __DIR__ . '/../../controllers/BackupController.php';

// Dapatkan daftar file backup yang sudah ada
$backupDir = UPLOAD_BACKUP_PATH;
$backupFiles = [];
if (is_dir($backupDir)) {
    $files = glob($backupDir . '/*.sql');
    if ($files) {
        rsort($files); // terbaru duluan
        foreach ($files as $file) {
            $backupFiles[] = [
                'name'     => basename($file),
                'size'     => filesize($file),
                'modified' => filemtime($file)
            ];
        }
    }
}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">

    <!-- Flash Messages -->
    <?php if ($flash = get_flash()): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?> me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="page-title"><i class="bi bi-database-fill-gear text-primary me-2"></i>Backup & Restore Database</h1>
            <p class="text-muted mb-0 small">Lindungi data perpustakaan Anda — buat cadangan otomatis atau pulihkan dari file SQL</p>
        </div>
    </div>

    <div class="row g-4">

        <!-- Kolom Kiri: Buat Backup & Restore -->
        <div class="col-lg-5">

            <!-- Card: Buat Backup -->
            <div class="card card-custom mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <strong><i class="bi bi-cloud-download-fill me-2"></i>Buat Backup Database</strong>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Klik tombol di bawah untuk membuat cadangan seluruh database perpustakaan ke dalam file <code>.sql</code>. 
                        File backup akan tersimpan di server dan dapat diunduh langsung.
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= base_url('index.php?page=backup&action=download') ?>" class="btn btn-primary px-4">
                            <i class="bi bi-download me-2"></i> Download Backup Sekarang
                        </a>
                        <a href="<?= base_url('index.php?page=backup&action=create') ?>"
                           class="btn btn-outline-primary px-4"
                           onclick="return confirm('Buat file backup dan simpan di server?')">
                            <i class="bi bi-floppy2-fill me-2"></i> Simpan ke Server
                        </a>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 small py-2">
                        <i class="bi bi-info-circle me-1"></i>
                        File backup berisi seluruh struktur tabel (DDL) dan data (DML) dari database <strong>perpustakaan_db</strong>.
                    </div>
                </div>
            </div>

            <!-- Card: Restore Database -->
            <div class="card card-custom border-warning">
                <div class="card-header bg-warning text-dark py-3">
                    <strong><i class="bi bi-cloud-upload-fill me-2"></i>Restore Database dari File SQL</strong>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger small py-2 mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Peringatan:</strong> Proses restore akan <strong>menghapus dan menimpa seluruh data</strong> database saat ini. Pastikan Anda sudah backup data terbaru.
                    </div>
                    <form method="POST" action="<?= base_url('index.php?page=backup&action=restore') ?>" enctype="multipart/form-data"
                          onsubmit="return confirm('PERINGATAN KERAS: Seluruh data database akan diganti dengan file SQL ini. Lanjutkan?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="restore">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Pilih File SQL Backup <span class="text-danger">*</span></label>
                            <input type="file" name="sql_file" accept=".sql" class="form-control" required>
                            <div class="form-text">Hanya file berekstensi <code>.sql</code> yang diizinkan.</div>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-arrow-clockwise me-2"></i> Pulihkan Database Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Daftar File Backup -->
        <div class="col-lg-7">
            <div class="card card-custom h-100">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <strong><i class="bi bi-folder2-open me-2"></i>File Backup Tersimpan di Server</strong>
                    <span class="badge bg-secondary"><?= count($backupFiles) ?> file</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($backupFiles)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-folder-x fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Belum ada file backup yang tersimpan.</p>
                            <p class="text-muted small">Klik "Simpan ke Server" untuk membuat backup pertama.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0 align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Nama File</th>
                                        <th>Ukuran</th>
                                        <th>Tanggal Dibuat</th>
                                        <th class="text-center">Unduh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backupFiles as $i => $bf): ?>
                                        <tr>
                                            <td class="text-muted small"><?= $i + 1 ?></td>
                                            <td>
                                                <i class="bi bi-file-earmark-text text-success me-1"></i>
                                                <span class="small fw-semibold"><?= htmlspecialchars($bf['name']) ?></span>
                                            </td>
                                            <td class="small text-muted">
                                                <?= round($bf['size'] / 1024, 1) ?> KB
                                            </td>
                                            <td class="small text-muted">
                                                <?= date('d M Y, H:i', $bf['modified']) ?> WITA
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url('index.php?page=backup&action=download_existing&filename=' . urlencode($bf['name'])) ?>"
                                                   class="btn btn-success btn-sm px-2">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer small text-muted py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    File tersimpan di: <code><?= htmlspecialchars($backupDir) ?></code>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan Backup -->
    <div class="card card-custom mt-4">
        <div class="card-header py-3">
            <strong><i class="bi bi-book-fill text-info me-2"></i>Panduan Backup & Restore</strong>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-bold"><i class="bi bi-1-circle-fill text-primary me-2"></i>Cara Membuat Backup</h6>
                    <ol class="small text-muted ps-3 mb-0">
                        <li>Klik <strong>"Download Backup Sekarang"</strong> untuk mengunduh langsung.</li>
                        <li>Atau klik <strong>"Simpan ke Server"</strong> untuk menyimpan di folder uploads/backups/.</li>
                        <li>File backup dinamai otomatis dengan format <code>backup_perpus_YYYYMMDD_HHMMSS.sql</code>.</li>
                        <li>Simpan file backup di tempat yang aman (flashdisk, Google Drive, dll).</li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold"><i class="bi bi-2-circle-fill text-warning me-2"></i>Cara Restore Database</h6>
                    <ol class="small text-muted ps-3 mb-0">
                        <li><strong>WAJIB</strong>: Buat backup data terkini sebelum restore.</li>
                        <li>Klik <strong>"Browse"</strong> dan pilih file <code>.sql</code> backup Anda.</li>
                        <li>Klik <strong>"Pulihkan Database Sekarang"</strong> dan konfirmasi peringatan.</li>
                        <li>Sistem akan menimpa seluruh database dengan data dari file SQL.</li>
                        <li>Setelah restore berhasil, login kembali dengan akun yang ada di file backup.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
<script>
// Handle download existing backup file
document.querySelectorAll('[href*="download_existing"]').forEach(function(link) {
    link.addEventListener('click', function(e) {
        var filename = new URL(this.href).searchParams.get('filename');
        if (!filename) return;
        e.preventDefault();
        // Trigger download via hidden form
        var form = document.createElement('form');
        form.method = 'GET';
        form.action = this.href;
        document.body.appendChild(form);
        form.submit();
    });
});
</script>

    </div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
