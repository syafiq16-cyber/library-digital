<?php
/**
 * Master Data Anggota
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Anggota.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas']);

$pageTitle = 'Data Anggota Perpustakaan';
$anggotaModel = new Anggota();
$masterModel = new Master();

$kelasList = $masterModel->getKelas();
$jurusanList = $masterModel->getJurusan();

$pageNo = max(1, (int)($_GET['p'] ?? 1));
$limit = 15;
$offset = ($pageNo - 1) * $limit;

$filters = [
    'search'        => trim($_GET['search'] ?? ''),
    'jenis_anggota' => trim($_GET['jenis_anggota'] ?? ''),
    'kelas_id'      => trim($_GET['kelas_id'] ?? ''),
    'jurusan_id'    => trim($_GET['jurusan_id'] ?? '')
];

$members = $anggotaModel->getAll($filters, $limit, $offset);
$totalRows = $anggotaModel->countAll($filters);
$totalPages = ceil($totalRows / $limit);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-people-fill text-primary me-2"></i>Data Anggota Perpustakaan</h1>
                <div class="page-subtitle">Kelola anggota (Siswa, Guru, Tendik, Umum) beserta kartu QR Code terintegrasi</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('index.php?page=cetak_kartu_massal') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-card-heading me-1"></i>Cetak Kartu Anggota
                </a>
                <a href="<?= base_url('index.php?page=import') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i>Import Siswa
                </a>
                <a href="<?= base_url('index.php?page=anggota_create') ?>" class="btn btn-primary-custom">
                    <i class="bi bi-person-plus-fill"></i> Tambah Anggota
                </a>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="card-custom mb-4">
            <div class="card-custom-body py-3">
                <form method="GET" action="<?= base_url('index.php') ?>" class="row g-2 align-items-center">
                    <input type="hidden" name="page" value="anggota">

                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Cari nama, no. anggota, NISN..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <select name="jenis_anggota" class="form-select form-select-sm">
                            <option value="">Semua Jenis</option>
                            <option value="Siswa" <?= ($filters['jenis_anggota'] === 'Siswa') ? 'selected' : '' ?>>Siswa</option>
                            <option value="Guru" <?= ($filters['jenis_anggota'] === 'Guru') ? 'selected' : '' ?>>Guru</option>
                            <option value="Tenaga Kependidikan" <?= ($filters['jenis_anggota'] === 'Tenaga Kependidikan') ? 'selected' : '' ?>>Tendik</option>
                            <option value="Umum" <?= ($filters['jenis_anggota'] === 'Umum') ? 'selected' : '' ?>>Umum</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="kelas_id" class="form-select form-select-sm">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($kelasList as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= ($filters['kelas_id'] == $k['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select name="jurusan_id" class="form-select form-select-sm">
                            <option value="">Semua Keahlian</option>
                            <?php foreach ($jurusanList as $j): ?>
                            <option value="<?= $j['id'] ?>" <?= ($filters['jurusan_id'] == $j['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($j['kode_jurusan'] . ' - ' . $j['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <a href="<?= base_url('index.php?page=anggota') ?>" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Anggota -->
        <div class="card-custom">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Foto</th>
                            <th>No. Anggota</th>
                            <th>NIS / NIP</th>
                            <th>Nama Lengkap</th>
                            <th>Jenis / JK</th>
                            <th>Kelas & Jurusan</th>
                            <th>QR Token Anggota</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-2 d-block mb-2 opacity-50"></i>
                                Tidak ada data anggota ditemukan.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($members as $idx => $m): ?>
                            <?php
                                $photoUrl = !empty($m['foto']) && file_exists(UPLOAD_ANGGOTA_PATH . '/' . $m['foto'])
                                    ? base_url('uploads/anggota/' . $m['foto'])
                                    : asset_url('images/' . ($m['jenis_kelamin'] === 'P' ? 'default_avatar_f.png' : 'default_avatar_m.png'));
                            ?>
                            <tr>
                                <td><?= $offset + $idx + 1 ?></td>
                                <td>
                                    <img src="<?= $photoUrl ?>" alt="Foto" class="rounded-circle border shadow-sm" style="width: 36px; height: 36px; object-fit: cover;">
                                </td>
                                <td><span class="fw-bold font-monospace text-primary"><?= htmlspecialchars($m['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><span class="text-muted font-monospace small"><?= htmlspecialchars($m['identitas_no'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td>
                                    <div class="fw-semibold text-main"><?= htmlspecialchars($m['nama'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($m['nomor_hp'] ?: '-', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($m['jenis_anggota'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-muted ms-1">(<?= $m['jenis_kelamin'] ?>)</span>
                                </td>
                                <td>
                                    <?php if ($m['nama_kelas']): ?>
                                        <div class="fw-semibold small"><?= htmlspecialchars($m['nama_kelas'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($m['kode_jurusan'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="font-monospace small text-primary" style="font-size: 11px;">
                                        <?= htmlspecialchars($m['qr_token'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($m['status_aktif']): ?>
                                        <span class="badge-status badge-status-success"><i class="bi bi-check-circle"></i> Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-status-danger"><i class="bi bi-x-circle"></i> Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('index.php?page=cetak_kartu_massal&anggota_id=' . $m['id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Cetak Kartu">
                                            <i class="bi bi-credit-card"></i>
                                        </a>
                                        <a href="<?= base_url('index.php?page=peminjaman_create&anggota_id=' . $m['id']) ?>" class="btn btn-outline-success" title="Pinjam Buku">
                                            <i class="bi bi-plus-lg"></i>
                                        </a>
                                        <a href="<?= base_url('index.php?page=anggota_edit&id=' . $m['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= base_url('controllers/AnggotaController.php?action=delete&id=' . $m['id']) ?>" class="btn btn-outline-danger" title="Hapus" onclick="return confirm('Hapus data anggota <?= htmlspecialchars($m['nama'], ENT_QUOTES, 'UTF-8') ?>?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-custom-header border-top d-flex justify-content-between align-items-center py-2">
                <small class="text-muted">Menampilkan <?= count($members) ?> dari total <?= $totalRows ?> anggota</small>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $pageNo) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= base_url('index.php?page=anggota&p=' . $i . '&' . http_build_query($filters)) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
