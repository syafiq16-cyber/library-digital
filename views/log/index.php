<?php
/**
 * Log Aktivitas Sistem - Audit Trail
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

$pageTitle = 'Log Aktivitas';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/LogAktivitas.php';

require_role('Administrator');

$logModel = new LogAktivitas();

$perPage = 100;
$page    = max(1, (int)($_GET['p'] ?? 1));
$offset  = ($page - 1) * $perPage;

$logs = $logModel->getAll($perPage, $offset);
$recentScans = $logModel->getRecentScans(20);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">

    <!-- Page Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="page-title"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Log Aktivitas Sistem</h1>
            <p class="text-muted mb-0 small">Audit trail seluruh operasi pengguna di sistem perpustakaan</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('index.php?page=log') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Kolom Utama: Activity Log -->
        <div class="col-lg-8">
            <div class="card card-custom">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <strong><i class="bi bi-clock-history me-2"></i>Riwayat Aktivitas Pengguna</strong>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="logSearch" class="form-control form-control-sm" 
                               placeholder="&#x1F50D; Cari aktivitas..." style="min-width:180px">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-bordered mb-0 align-middle" id="logTable">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Pengguna</th>
                                    <th>Modul</th>
                                    <th>Aksi</th>
                                    <th>Keterangan</th>
                                    <th>IP Address</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            Belum ada log aktivitas.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $i => $log):
                                        $aksi = strtolower($log['aksi'] ?? '');
                                        $aksiColor = match($aksi) {
                                            'tambah', 'create', 'import', 'backup' => 'success',
                                            'edit', 'update', 'restore', 'perpanjang' => 'warning',
                                            'hapus', 'delete', 'logout' => 'danger',
                                            'login', 'view', 'cetak' => 'info',
                                            default => 'secondary'
                                        };
                                    ?>
                                        <tr data-search="<?= strtolower(($log['username'] ?? '') . ' ' . ($log['modul'] ?? '') . ' ' . ($log['aksi'] ?? '') . ' ' . ($log['keterangan'] ?? '')) ?>">
                                            <td class="text-muted small"><?= $offset + $i + 1 ?></td>
                                            <td>
                                                <div class="fw-semibold small"><?= htmlspecialchars($log['nama_lengkap'] ?? $log['username'] ?? 'Sistem') ?></div>
                                                <div class="text-muted" style="font-size:0.7rem"><?= htmlspecialchars($log['nama_role'] ?? '-') ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border small"><?= htmlspecialchars($log['modul'] ?? '-') ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $aksiColor ?> small"><?= htmlspecialchars($log['aksi'] ?? '-') ?></span>
                                            </td>
                                            <td class="small text-muted" style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" 
                                                title="<?= htmlspecialchars($log['keterangan'] ?? '') ?>">
                                                <?= htmlspecialchars($log['keterangan'] ?? '-') ?>
                                            </td>
                                            <td class="small text-muted"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                                            <td class="small text-muted" style="white-space:nowrap;">
                                                <?= date('d/m/Y H:i', strtotime($log['created_at'] ?? 'now')) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (count($logs) === $perPage): ?>
                    <div class="card-footer text-center py-2">
                        <a href="<?= base_url('index.php?page=log&p=' . ($page + 1)) ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-chevron-down me-1"></i> Muat Lebih Banyak
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom Kanan: Scan Log QR Code -->
        <div class="col-lg-4">
            <div class="card card-custom h-100">
                <div class="card-header py-3">
                    <strong><i class="bi bi-qr-code-scan me-2 text-primary"></i>Log Scan QR Code Terbaru</strong>
                </div>
                <div class="card-body p-0" style="max-height:520px;overflow-y:auto;">
                    <?php if (empty($recentScans)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-qr-code fs-2 d-block mb-2"></i>
                            Belum ada aktivitas scan QR.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recentScans as $scan):
                                $scanType = strpos($scan['token_scanned'] ?? '', 'MEMBER') !== false ? 'Anggota' : 'Buku';
                                $scanIcon = $scanType === 'Anggota' ? 'bi-person-fill' : 'bi-book-fill';
                                $scanColor = $scanType === 'Anggota' ? 'primary' : 'info';
                            ?>
                                <li class="list-group-item py-2 px-3 d-flex align-items-start gap-2" style="border-left:3px solid var(--bs-<?= $scanColor ?>);">
                                    <div class="mt-1">
                                        <i class="<?= $scanIcon ?> text-<?= $scanColor ?>"></i>
                                    </div>
                                    <div style="overflow:hidden;">
                                        <div class="fw-semibold small"><?= htmlspecialchars($scan['target_identifier'] ?? '-') ?></div>
                                        <div style="font-size:0.7rem;color:#888;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($scan['token_scanned'] ?? '') ?>">
                                            QR: <?= htmlspecialchars($scan['token_scanned'] ?? '-') ?>
                                        </div>
                                        <div style="font-size:0.68rem;color:#aaa;">
                                            <?= date('d/m H:i', strtotime($scan['created_at'] ?? 'now')) ?>
                                            <?php if (!empty($scan['nama_petugas'])): ?> &bull; <?= htmlspecialchars($scan['nama_petugas']) ?><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="ms-auto">
                                        <span class="badge bg-<?= $scanColor ?> small"><?= $scanType ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<script>
document.getElementById('logSearch').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('#logTable tbody tr').forEach(function(row) {
        var text = row.dataset.search || '';
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
});
</script>

    </div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
