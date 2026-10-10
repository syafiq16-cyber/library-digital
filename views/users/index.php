<?php
/**
 * Manajemen User & Akun Sistem
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

$pageTitle = 'Manajemen User';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/User.php';

require_role('Administrator');

$userModel = new User();
$currentUser = current_user();

// Handle form POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid. Silakan coba kembali.');
        header('Location: ' . base_url('index.php?page=users'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    // TAMBAH USER BARU
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);

        if (empty($username) || empty($password) || empty($namaLengkap) || !$roleId) {
            set_flash('danger', 'Semua field wajib diisi (Username, Password, Nama, Role).');
        } elseif (strlen($password) < 6) {
            set_flash('danger', 'Password minimal 6 karakter.');
        } elseif ($userModel->getByUsername($username)) {
            set_flash('danger', "Username '$username' sudah digunakan.");
        } else {
            $userModel->create([
                'username'    => $username,
                'password'    => $password,
                'nama_lengkap'=> $namaLengkap,
                'email'       => $email,
                'role_id'     => $roleId,
                'status_aktif'=> 1
            ]);
            log_activity($currentUser['id'], 'User', 'Tambah', "Menambahkan user baru: $username");
            set_flash('success', "User '$username' berhasil ditambahkan.");
        }
        header('Location: ' . base_url('index.php?page=users'));
        exit;
    }

    // EDIT USER
    if ($action === 'edit') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $statusAktif = (int)($_POST['status_aktif'] ?? 1);
        $password = trim($_POST['password'] ?? '');

        if (!$uid || !$namaLengkap || !$roleId) {
            set_flash('danger', 'Data tidak lengkap.');
        } else {
            $updateData = [
                'nama_lengkap'=> $namaLengkap,
                'email'       => $email,
                'role_id'     => $roleId,
                'status_aktif'=> $statusAktif
            ];
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    set_flash('danger', 'Password baru minimal 6 karakter.');
                    header('Location: ' . base_url('index.php?page=users'));
                    exit;
                }
                $updateData['password'] = $password;
            }
            $userModel->update($uid, $updateData);
            log_activity($currentUser['id'], 'User', 'Update', "Memperbarui user ID: $uid");
            set_flash('success', 'Data user berhasil diperbarui.');
        }
        header('Location: ' . base_url('index.php?page=users'));
        exit;
    }

    // HAPUS USER
    if ($action === 'delete') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid === $currentUser['id']) {
            set_flash('danger', 'Anda tidak dapat menghapus akun yang sedang aktif digunakan.');
        } elseif ($uid > 0) {
            $u = $userModel->getById($uid);
            $userModel->delete($uid);
            log_activity($currentUser['id'], 'User', 'Hapus', "Menghapus user: " . ($u['username'] ?? $uid));
            set_flash('success', 'User berhasil dihapus dari sistem.');
        }
        header('Location: ' . base_url('index.php?page=users'));
        exit;
    }
}

$users = $userModel->getAll();
$roles = $userModel->getRoles();

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
            <h1 class="page-title"><i class="bi bi-person-badge-fill text-primary me-2"></i>Manajemen User & Akun Sistem</h1>
            <p class="text-muted mb-0 small">Kelola pengguna sistem — tambah, ubah role, reset password, nonaktifkan akun</p>
        </div>
        <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah User Baru
        </button>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <?php
        $roleStats = array_count_values(array_column($users, 'nama_role'));
        $totalActive = count(array_filter($users, fn($u) => $u['status_aktif']));
        $statsCards = [
            ['label' => 'Total User', 'val' => count($users), 'icon' => 'bi-people-fill', 'color' => 'primary'],
            ['label' => 'User Aktif', 'val' => $totalActive, 'icon' => 'bi-person-check-fill', 'color' => 'success'],
            ['label' => 'User Nonaktif', 'val' => count($users) - $totalActive, 'icon' => 'bi-person-x-fill', 'color' => 'warning'],
            ['label' => 'Role / Jabatan', 'val' => count($roles), 'icon' => 'bi-shield-fill', 'color' => 'info'],
        ];
        foreach ($statsCards as $sc): ?>
            <div class="col-6 col-md-3">
                <div class="card card-custom text-center p-3">
                    <i class="<?= $sc['icon'] ?> text-<?= $sc['color'] ?> fs-3 mb-1"></i>
                    <div class="fw-bold fs-5"><?= $sc['val'] ?></div>
                    <div class="text-muted small"><?= $sc['label'] ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- User Table -->
    <div class="card card-custom">
        <div class="card-header d-flex align-items-center justify-content-between py-3">
            <strong><i class="bi bi-list-ul me-2"></i>Daftar Pengguna Sistem</strong>
            <input type="text" id="userSearch" class="form-control form-control-sm w-auto" placeholder="&#x1F50D; Cari username / nama..." style="min-width:200px">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 align-middle" id="userTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $u): ?>
                            <tr data-search="<?= strtolower($u['username'] . ' ' . $u['nama_lengkap']) ?>">
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.8rem;font-weight:700;flex-shrink:0;">
                                            <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold small"><?= htmlspecialchars($u['username']) ?></div>
                                            <?php if ($u['id'] === $currentUser['id']): ?>
                                                <span class="badge bg-primary" style="font-size:0.6rem">Anda</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="small"><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                                <td>
                                    <?php
                                    $roleBadge = ['Administrator' => 'danger', 'Petugas' => 'primary', 'Kepala Perpustakaan' => 'info', 'Anggota' => 'secondary'];
                                    $bc = $roleBadge[$u['nama_role']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $bc ?> small"><?= htmlspecialchars($u['nama_role']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $u['status_aktif'] ? 'success' : 'secondary' ?> small">
                                        <?= $u['status_aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button class="btn btn-warning btn-sm px-2 py-1" title="Edit"
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)">
                                            <i class="bi bi-pencil-square" style="font-size:0.8rem"></i>
                                        </button>
                                        <?php if ($u['id'] !== $currentUser['id']): ?>
                                            <form method="POST" action="<?= base_url('index.php?page=users') ?>"
                                                onsubmit="return confirm('Yakin hapus user <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>? Tindakan ini tidak dapat dibatalkan.')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm px-2 py-1" title="Hapus">
                                                    <i class="bi bi-trash3-fill" style="font-size:0.8rem"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data user.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="modalTambahUser" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Tambah User Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= base_url('index.php?page=users') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" placeholder="Minimal 4 karakter" required minlength="4" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama lengkap pengguna" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="email@smkn2sumbawa.sch.id">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Role / Jabatan <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <option value="">-- Pilih Role --</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nama_role']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-check-fill me-1"></i> Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="modalEditUser" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= base_url('index.php?page=users') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Username</label>
                            <input type="text" id="editUsername" class="form-control bg-light" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Password Baru <span class="text-muted small">(kosongkan jika tidak diubah)</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Isi untuk reset password" minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" id="editNamaLengkap" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Role / Jabatan <span class="text-danger">*</span></label>
                            <select name="role_id" id="editRoleId" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nama_role']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Status Akun</label>
                            <select name="status_aktif" id="editStatusAktif" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
<script>
function openEditModal(u) {
    document.getElementById('editUserId').value = u.id;
    document.getElementById('editUsername').value = u.username;
    document.getElementById('editNamaLengkap').value = u.nama_lengkap;
    document.getElementById('editEmail').value = u.email || '';
    document.getElementById('editRoleId').value = u.role_id;
    document.getElementById('editStatusAktif').value = u.status_aktif;
    var modal = new bootstrap.Modal(document.getElementById('modalEditUser'));
    modal.show();
}

// Live search in table
document.getElementById('userSearch').addEventListener('input', function () {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('#userTable tbody tr').forEach(function(row) {
        var text = row.dataset.search || '';
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
});
</script>

    </div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
