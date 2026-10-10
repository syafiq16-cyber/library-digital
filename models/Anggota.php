<?php
/**
 * Model Anggota (Siswa, Guru, Tendik, Umum)
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/qrcode_helper.php';

class Anggota {
    private PDO $db;

    public function __construct() {
        $this->db = get_db();
    }

    /**
     * Generate Nomor Anggota Otomatis: AGT-00001
     */
    public function generateNomorAnggota(): string {
        $stmt = $this->db->query("SELECT MAX(id) AS max_id FROM anggota");
        $row = $stmt->fetch();
        $nextId = ((int)($row['max_id'] ?? 0)) + 1;
        return 'AGT-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
    }

    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "
            SELECT a.*, k.nama_kelas, j.kode_jurusan, j.nama_jurusan 
            FROM anggota a 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (a.nama LIKE :search OR a.nomor_anggota LIKE :search OR a.identitas_no LIKE :search OR a.nomor_hp LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['jenis_anggota'])) {
            $sql .= " AND a.jenis_anggota = :jenis";
            $params[':jenis'] = $filters['jenis_anggota'];
        }

        if (!empty($filters['kelas_id'])) {
            $sql .= " AND a.kelas_id = :kelas_id";
            $params[':kelas_id'] = (int)$filters['kelas_id'];
        }

        if (!empty($filters['jurusan_id'])) {
            $sql .= " AND a.jurusan_id = :jurusan_id";
            $params[':jurusan_id'] = (int)$filters['jurusan_id'];
        }

        if (isset($filters['status_aktif']) && $filters['status_aktif'] !== '') {
            $sql .= " AND a.status_aktif = :status";
            $params[':status'] = (int)$filters['status_aktif'];
        }

        $sql .= " ORDER BY a.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(array $filters = []): int {
        $sql = "
            SELECT COUNT(*) AS total 
            FROM anggota a 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (a.nama LIKE :search OR a.nomor_anggota LIKE :search OR a.identitas_no LIKE :search OR a.nomor_hp LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['jenis_anggota'])) {
            $sql .= " AND a.jenis_anggota = :jenis";
            $params[':jenis'] = $filters['jenis_anggota'];
        }

        if (!empty($filters['kelas_id'])) {
            $sql .= " AND a.kelas_id = :kelas_id";
            $params[':kelas_id'] = (int)$filters['kelas_id'];
        }

        if (!empty($filters['jurusan_id'])) {
            $sql .= " AND a.jurusan_id = :jurusan_id";
            $params[':jurusan_id'] = (int)$filters['jurusan_id'];
        }

        if (isset($filters['status_aktif']) && $filters['status_aktif'] !== '') {
            $sql .= " AND a.status_aktif = :status";
            $params[':status'] = (int)$filters['status_aktif'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, k.nama_kelas, j.kode_jurusan, j.nama_jurusan 
            FROM anggota a 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            WHERE a.id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function getByNomorAnggota(string $nomor): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, k.nama_kelas, j.kode_jurusan, j.nama_jurusan 
            FROM anggota a 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            WHERE a.nomor_anggota = :no 
            LIMIT 1
        ");
        $stmt->execute([':no' => trim($nomor)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function getByIdentitas(string $identitas): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, k.nama_kelas, j.kode_jurusan, j.nama_jurusan 
            FROM anggota a 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            WHERE a.identitas_no = :idno 
            LIMIT 1
        ");
        $stmt->execute([':idno' => trim($identitas)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function getByQrToken(string $token): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, k.nama_kelas, j.kode_jurusan, j.nama_jurusan 
            FROM anggota a 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            WHERE a.qr_token = :token 
            LIMIT 1
        ");
        $stmt->execute([':token' => trim($token)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function create(array $data): int {
        $nomorAnggota = !empty($data['nomor_anggota']) ? trim($data['nomor_anggota']) : $this->generateNomorAnggota();
        $qrToken = make_member_qr_token($nomorAnggota);

        $defaultFoto = ($data['jenis_kelamin'] ?? 'L') === 'P' ? 'default_avatar_f.png' : 'default_avatar_m.png';

        $stmt = $this->db->prepare("
            INSERT INTO anggota (
                nomor_anggota, identitas_no, nama, jenis_anggota, jenis_kelamin,
                kelas_id, jurusan_id, nomor_hp, alamat, foto, qr_token, status_aktif, tanggal_registrasi
            ) VALUES (
                :nomor_anggota, :identitas_no, :nama, :jenis_anggota, :jenis_kelamin,
                :kelas_id, :jurusan_id, :nomor_hp, :alamat, :foto, :qr_token, :status_aktif, :tanggal_registrasi
            )
        ");

        $stmt->execute([
            ':nomor_anggota'      => $nomorAnggota,
            ':identitas_no'       => trim($data['identitas_no']),
            ':nama'               => trim($data['nama']),
            ':jenis_anggota'      => $data['jenis_anggota'] ?? 'Siswa',
            ':jenis_kelamin'      => $data['jenis_kelamin'] ?? 'L',
            ':kelas_id'           => !empty($data['kelas_id']) ? (int)$data['kelas_id'] : null,
            ':jurusan_id'         => !empty($data['jurusan_id']) ? (int)$data['jurusan_id'] : null,
            ':nomor_hp'           => $data['nomor_hp'] ?? null,
            ':alamat'             => $data['alamat'] ?? null,
            ':foto'               => $data['foto'] ?? $defaultFoto,
            ':qr_token'           => $qrToken,
            ':status_aktif'       => $data['status_aktif'] ?? 1,
            ':tanggal_registrasi' => $data['tanggal_registrasi'] ?? date('Y-m-d')
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [
            'identitas_no = :identitas_no',
            'nama = :nama',
            'jenis_anggota = :jenis_anggota',
            'jenis_kelamin = :jenis_kelamin',
            'kelas_id = :kelas_id',
            'jurusan_id = :jurusan_id',
            'nomor_hp = :nomor_hp',
            'alamat = :alamat',
            'status_aktif = :status_aktif'
        ];

        $params = [
            ':id'            => $id,
            ':identitas_no'  => trim($data['identitas_no']),
            ':nama'          => trim($data['nama']),
            ':jenis_anggota' => $data['jenis_anggota'] ?? 'Siswa',
            ':jenis_kelamin' => $data['jenis_kelamin'] ?? 'L',
            ':kelas_id'      => !empty($data['kelas_id']) ? (int)$data['kelas_id'] : null,
            ':jurusan_id'    => !empty($data['jurusan_id']) ? (int)$data['jurusan_id'] : null,
            ':nomor_hp'      => $data['nomor_hp'] ?? null,
            ':alamat'        => $data['alamat'] ?? null,
            ':status_aktif'  => $data['status_aktif'] ?? 1
        ];

        if (!empty($data['foto'])) {
            $fields[] = 'foto = :foto';
            $params[':foto'] = $data['foto'];
        }

        $sql = "UPDATE anggota SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM anggota WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getActiveLoans(int $anggotaId): array {
        $stmt = $this->db->prepare("
            SELECT p.*, pd.buku_id, pd.eksemplar_id, b.judul, b.kode_buku, e.kode_eksemplar 
            FROM peminjaman p 
            JOIN peminjaman_detail pd ON p.id = pd.peminjaman_id 
            JOIN buku b ON pd.buku_id = b.id 
            LEFT JOIN buku_eksemplar e ON pd.eksemplar_id = e.id 
            WHERE p.anggota_id = :aid AND pd.status = 'Dipinjam'
            ORDER BY p.tanggal_pinjam ASC
        ");
        $stmt->execute([':aid' => $anggotaId]);
        return $stmt->fetchAll();
    }

    public function getMostActiveMembers(int $limit = 5): array {
        $stmt = $this->db->prepare("
            SELECT a.id, a.nomor_anggota, a.nama, a.foto, a.jenis_anggota, 
                   k.nama_kelas, j.kode_jurusan, 
                   COUNT(p.id) AS total_pinjam 
            FROM anggota a 
            JOIN peminjaman p ON a.id = p.anggota_id 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            GROUP BY a.id 
            ORDER BY total_pinjam DESC, a.nama ASC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
