<?php
/**
 * Riwayat Pinjaman Saya (Portal Siswa / Anggota)
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/qrcode_helper.php';
require_once __DIR__ . '/../../models/Anggota.php';
require_once __DIR__ . '/../../models/Peminjaman.php';

require_login();

$pageTitle = 'Riwayat Pinjaman Saya';
$user = current_user();

// Cari data anggota yang terhubung dengan user login
$db = get_db();
$stmt = $db->prepare("
    SELECT a.*, k.nama_kelas, j.kode_jurusan, j.nama_jurusan 
    FROM anggota a 
    LEFT JOIN kelas k ON a.kelas_id = k.id 
    LEFT JOIN jurusan j ON a.jurusan_id = j.id 
    WHERE a.user_id = :uid OR a.nama = :nama 
    LIMIT 1
");
$stmt->execute([':uid' => $user['id'], ':nama' => $user['nama_lengkap']]);
$member = $stmt->fetch();

$peminjamanModel = new Peminjaman();
$myLoans = [];
if ($member) {
    $myLoans = $peminjamanModel->getAll(['anggota_id' => $member['id']], 50, 0);
}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-person-lines-fill text-primary me-2"></i>Riwayat Pinjaman Saya</h1>
                <div class="page-subtitle">Pantau status peminjaman buku, jatuh tempo, dan kartu anggota digital Anda</div>
            </div>
            <div>
                <a href="<?= base_url('index.php?page=katalog') ?>" class="btn btn-primary-custom">
                    <i class="bi bi-journals me-1"></i>Jelajahi Katalog Buku
                </a>
            </div>
        </div>

        <?php if (!$member): ?>
            <div class="alert alert-info py-3 px-4 rounded-3 border-0 shadow-sm">
                <h5 class="fw-bold"><i class="bi bi-info-circle me-2"></i>Akun Belum Ditautkan ke Data Anggota</h5>
                <p class="small mb-0">Akun Anda belum terhubung dengan nomor anggota perpustakaan. Silakan hubungi petugas perpustakaan untuk mengaktifkan kartu anggota Anda.</p>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-4">
                <!-- Kartu Anggota Digital Saya -->
                <div class="col-lg-4">
                    <div class="card-custom text-center mb-0">
                        <div class="card-custom-header bg-primary-subtle border-primary-subtle">
                            <h2 class="card-custom-title text-primary"><i class="bi bi-credit-card-2-front"></i><span>Kartu Digital Anggota</span></h2>
                        </div>
                        <div class="card-custom-body p-4">
                            <img src="<?= base_url('uploads/anggota/' . ($member['foto'] ?: 'default_avatar_m.png')) ?>" alt="Foto" class="rounded-circle border shadow-sm mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($member['nama'], ENT_QUOTES, 'UTF-8') ?></h5>
                            <span class="badge bg-primary mt-1 mb-2"><?= htmlspecialchars($member['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="small text-muted mb-3"><?= htmlspecialchars(($member['nama_kelas'] ?? '-') . ' &bull; ' . ($member['kode_jurusan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>

                            <hr>

                            <!-- QR Code Anggota -->
                            <div class="p-2 bg-light rounded border d-inline-block">
                                <div class="small fw-bold text-muted mb-1"><i class="bi bi-qr-code me-1"></i>Tunjukkan ke Petugas Saat Transaksi:</div>
                                <?= render_qr_code_html($member['qr_token'], 130) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Riwayat Pinjaman -->
                <div class="col-lg-8">
                    <div class="card-custom mb-0">
                        <div class="card-custom-header">
                            <h2 class="card-custom-title"><i class="bi bi-clock-history text-primary"></i><span>Daftar Transaksi Peminjaman</span></h2>
                            <span class="badge bg-light text-muted border"><?= count($myLoans) ?> Transaksi</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table-custom">
                                <thead>
                                    <tr>
                                        <th>No. Transaksi</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Total Buku</th>
                                        <th>Status</th>
                                        <th class="text-end">Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($myLoans)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-book fs-2 d-block mb-1 opacity-25"></i>
                                            Anda belum pernah melakukan peminjaman buku.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($myLoans as $row): ?>
                                        <?php $isOverdue = ($row['status'] === 'Terlambat' || ($row['status'] === 'Dipinjam' && strtotime($row['tanggal_jatuh_tempo']) < time())); ?>
                                        <tr>
                                            <td><span class="fw-bold font-monospace text-primary"><?= htmlspecialchars($row['nomor_transaksi'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                            <td class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                                <?= date('d/m/Y', strtotime($row['tanggal_jatuh_tempo'])) ?>
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge bg-danger ms-1">Terlambat</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= $row['total_buku'] ?> Buku</span></td>
                                            <td>
                                                <?php if ($row['status'] === 'Dipinjam'): ?>
                                                    <span class="badge-status badge-status-warning"><i class="bi bi-hourglass-split"></i> Sedang Dipinjam</span>
                                                <?php elseif ($row['status'] === 'Selesai'): ?>
                                                    <span class="badge-status badge-status-success"><i class="bi bi-check2"></i> Selesai Dikembalikan</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-status-danger"><i class="bi bi-exclamation-circle"></i> Terlambat</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= base_url('index.php?page=peminjaman_detail&id=' . $row['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                    Lihat Buku
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
