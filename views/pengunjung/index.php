<?php
/**
 * Buku Tamu / Data Kunjungan Perpustakaan
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Pengunjung.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);

$pageTitle = 'Buku Tamu Pengunjung';
$pengunjungModel = new Pengunjung();
$masterModel = new Master();

$kelasList = $masterModel->getKelas();
$jurusanList = $masterModel->getJurusan();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=pengunjung'));
        exit;
    }

    $nama = trim($_POST['nama'] ?? '');
    $keperluan = trim($_POST['keperluan'] ?? '');

    if (!empty($nama) && !empty($keperluan)) {
        $data = [
            'identitas_no'     => trim($_POST['identitas_no'] ?? ''),
            'nama'             => $nama,
            'jenis_pengunjung' => $_POST['jenis_pengunjung'] ?? 'Siswa',
            'kelas_id'         => !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : null,
            'jurusan_id'       => !empty($_POST['jurusan_id']) ? (int)$_POST['jurusan_id'] : null,
            'keperluan'        => $keperluan,
            'waktu_masuk'      => date('H:i:s'),
            'tanggal'          => date('Y-m-d'),
            'keterangan'       => trim($_POST['keterangan'] ?? '')
        ];

        $pengunjungModel->create($data);
        log_activity($user['id'], 'Pengunjung', 'Tambah', "Mencatat kehadiran pengunjung: $nama ($keperluan)");
        set_flash('success', "Kunjungan '$nama' berhasil dicatat ke buku tamu.");
    }
    header('Location: ' . base_url('index.php?page=pengunjung'));
    exit;
}

$filters = [
    'tanggal' => trim($_GET['tanggal'] ?? date('Y-m-d'))
];
$visitors = $pengunjungModel->getAll($filters, 50, 0);
$totalToday = $pengunjungModel->countToday();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-person-check-fill text-primary me-2"></i>Buku Tamu / Pengunjung</h1>
                <div class="page-subtitle">Presensi kehadiran siswa, guru, dan tamu di ruang perpustakaan SMKN 2 Sumbawa Besar</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalPresensiTamu">
                    <i class="bi bi-person-plus"></i> Isi Buku Tamu
                </button>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Widget Statistik Ringkas -->
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-primary">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="kpi-value"><?= $totalToday ?></div>
                        <div class="kpi-label">Pengunjung Hari Ini (<?= date('d M Y') ?>)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <!-- Filter Tanggal -->
                <div class="card-custom h-100 mb-0 d-flex justify-content-center">
                    <div class="card-custom-body py-3">
                        <form method="GET" action="<?= base_url('index.php') ?>" class="row g-2 align-items-center">
                            <input type="hidden" name="page" value="pengunjung">
                            <div class="col-auto">
                                <label class="small fw-semibold text-muted">Pilih Tanggal Kunjungan:</label>
                            </div>
                            <div class="col-auto">
                                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['tanggal'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Tampilkan</button>
                                <a href="<?= base_url('index.php?page=pengunjung') ?>" class="btn btn-sm btn-outline-secondary ms-1">Hari Ini</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Pengunjung -->
        <div class="card-custom">
            <div class="card-custom-header">
                <h2 class="card-custom-title">
                    <i class="bi bi-clock-history text-success"></i>
                    <span>Daftar Kunjungan Tanggal <?= format_date($filters['tanggal']) ?> (<?= count($visitors) ?> Orang)</span>
                </h2>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No.</th>
                            <th>Waktu Masuk</th>
                            <th>Nama Pengunjung</th>
                            <th>Kategori</th>
                            <th>Kelas & Jurusan</th>
                            <th>Keperluan</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visitors)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada pengunjung tercatat pada tanggal ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($visitors as $idx => $v): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= substr($v['waktu_masuk'], 0, 5) ?> WITA</span></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($v['nama'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($v['identitas_no'] ?: '-', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($v['jenis_pengunjung'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= htmlspecialchars(($v['nama_kelas'] ?? '-') . ' (' . ($v['kode_jurusan'] ?? '-') . ')', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="fw-semibold text-main"><?= htmlspecialchars($v['keperluan'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($v['keterangan'] ?: '-', ENT_QUOTES, 'UTF-8') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

<!-- Modal Presensi Tamu -->
<div class="modal fade" id="modalPresensiTamu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=pengunjung') ?>">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-check text-primary me-2"></i>Catat Kunjungan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jenis Pengunjung: <span class="text-danger">*</span></label>
                        <select name="jenis_pengunjung" id="presensiJenis" class="form-select" required>
                            <option value="Siswa">Siswa SMKN 2</option>
                            <option value="Guru">Guru / Tenaga Pendidik</option>
                            <option value="Tenaga Kependidikan">Tenaga Kependidikan</option>
                            <option value="Umum">Tamu Umum / Instansi Luar</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">NISN / NIP (Jika ada):</label>
                        <input type="text" name="identitas_no" class="form-control" placeholder="Contoh: 0061234501">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap: <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" placeholder="Masukkan nama pengunjung..." required>
                    </div>
                    <div class="row g-2 mb-3" id="presensiBoxSiswa">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Kelas:</label>
                            <select name="kelas_id" class="form-select">
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($kelasList as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jurusan:</label>
                            <select name="jurusan_id" class="form-select">
                                <option value="">-- Pilih Jurusan --</option>
                                <?php foreach ($jurusanList as $j): ?>
                                <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['kode_jurusan'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tujuan / Keperluan: <span class="text-danger">*</span></label>
                        <input type="text" name="keperluan" class="form-control" placeholder="Contoh: Membaca buku, tugas kelompok, meminjam buku..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Keterangan Tambahan:</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Keterangan opsional...">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-check2-circle me-1"></i>Catat Kehadiran</button>
                </div>
            </form>
        </div>
    </div>
    </div>
<script>
    const selJenis = document.getElementById('presensiJenis');
    const boxSiswa = document.getElementById('presensiBoxSiswa');
    selJenis.addEventListener('change', () => {
        boxSiswa.style.display = (selJenis.value === 'Siswa') ? 'flex' : 'none';
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
