<?php
/**
 * Model Peminjaman Buku
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/database.php';

class Peminjaman {
    private PDO $db;

    public function __construct() {
        $this->db = get_db();
    }

    /**
     * Generate Nomor Transaksi Otomatis: PJM-YYYYMMDD-XXXX
     */
    public function generateNomorTransaksi(): string {
        $prefix = 'PJM-' . date('Ymd') . '-';
        $stmt = $this->db->prepare("
            SELECT nomor_transaksi 
            FROM peminjaman 
            WHERE nomor_transaksi LIKE :pfx 
            ORDER BY nomor_transaksi DESC 
            LIMIT 1
        ");
        $stmt->execute([':pfx' => $prefix . '%']);
        $lastNo = $stmt->fetchColumn();

        if ($lastNo) {
            $lastSeq = (int)substr($lastNo, -4);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
    }

    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array {
        $this->updateStatusOverdue();

        $sql = "
            SELECT p.*, a.nama AS nama_anggota, a.nomor_anggota, a.identitas_no, 
                   k.nama_kelas, j.kode_jurusan, u.nama_lengkap AS nama_petugas 
            FROM peminjaman p 
            JOIN anggota a ON p.anggota_id = a.id 
            JOIN users u ON p.petugas_id = u.id 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.nomor_transaksi LIKE :search OR a.nama LIKE :search OR a.nomor_anggota LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['anggota_id'])) {
            $sql .= " AND p.anggota_id = :aid";
            $params[':aid'] = (int)$filters['anggota_id'];
        }

        if (!empty($filters['tgl_awal']) && !empty($filters['tgl_akhir'])) {
            $sql .= " AND p.tanggal_pinjam BETWEEN :tgl_awal AND :tgl_akhir";
            $params[':tgl_awal'] = $filters['tgl_awal'];
            $params[':tgl_akhir'] = $filters['tgl_akhir'];
        }

        $sql .= " ORDER BY p.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(array $filters = []): int {
        $sql = "
            SELECT COUNT(*) AS total 
            FROM peminjaman p 
            JOIN anggota a ON p.anggota_id = a.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.nomor_transaksi LIKE :search OR a.nama LIKE :search OR a.nomor_anggota LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['anggota_id'])) {
            $sql .= " AND p.anggota_id = :aid";
            $params[':aid'] = (int)$filters['anggota_id'];
        }

        if (!empty($filters['tgl_awal']) && !empty($filters['tgl_akhir'])) {
            $sql .= " AND p.tanggal_pinjam BETWEEN :tgl_awal AND :tgl_akhir";
            $params[':tgl_awal'] = $filters['tgl_awal'];
            $params[':tgl_akhir'] = $filters['tgl_akhir'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*, a.nama AS nama_anggota, a.nomor_anggota, a.identitas_no, a.jenis_anggota,
                   a.nomor_hp, a.alamat, a.foto, a.qr_token AS qr_anggota,
                   k.nama_kelas, j.kode_jurusan, j.nama_jurusan,
                   u.nama_lengkap AS nama_petugas 
            FROM peminjaman p 
            JOIN anggota a ON p.anggota_id = a.id 
            JOIN users u ON p.petugas_id = u.id 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            WHERE p.id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getByNomor(string $nomorTransaksi): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*, a.nama AS nama_anggota, a.nomor_anggota, a.identitas_no, a.jenis_anggota,
                   a.nomor_hp, a.alamat, a.foto, a.qr_token AS qr_anggota,
                   k.nama_kelas, j.kode_jurusan, j.nama_jurusan,
                   u.nama_lengkap AS nama_petugas 
            FROM peminjaman p 
            JOIN anggota a ON p.anggota_id = a.id 
            JOIN users u ON p.petugas_id = u.id 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            WHERE p.nomor_transaksi = :no 
            LIMIT 1
        ");
        $stmt->execute([':no' => trim($nomorTransaksi)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getDetails(int $peminjamanId): array {
        $stmt = $this->db->prepare("
            SELECT pd.*, b.judul, b.kode_buku, b.isbn, b.cover, 
                   cat.nama_kategori, r.kode_rak, r.nama_rak,
                   e.kode_eksemplar, e.qr_token AS qr_eksemplar, e.kondisi AS kondisi_awal
            FROM peminjaman_detail pd 
            JOIN buku b ON pd.buku_id = b.id 
            LEFT JOIN kategori_buku cat ON b.kategori_id = cat.id 
            LEFT JOIN rak r ON b.rak_id = r.id 
            LEFT JOIN buku_eksemplar e ON pd.eksemplar_id = e.id 
            WHERE pd.peminjaman_id = :pid
            ORDER BY pd.id ASC
        ");
        $stmt->execute([':pid' => $peminjamanId]);
        return $stmt->fetchAll();
    }

    /**
     * Memproses Peminjaman Multi-Buku
     * $bukuItems: array of ['buku_id' => int, 'eksemplar_id' => ?int]
     */
    public function create(array $data, array $bukuItems): int {
        if (empty($bukuItems)) {
            throw new Exception("Minimal harus memilih satu buku untuk dipinjam.");
        }

        $this->db->beginTransaction();
        try {
            $nomorTransaksi = !empty($data['nomor_transaksi']) ? $data['nomor_transaksi'] : $this->generateNomorTransaksi();

            $stmt = $this->db->prepare("
                INSERT INTO peminjaman (
                    nomor_transaksi, anggota_id, petugas_id, tanggal_pinjam, tanggal_jatuh_tempo,
                    total_buku, status, catatan
                ) VALUES (
                    :nomor_transaksi, :anggota_id, :petugas_id, :tanggal_pinjam, :tanggal_jatuh_tempo,
                    :total_buku, 'Dipinjam', :catatan
                )
            ");

            $stmt->execute([
                ':nomor_transaksi'     => $nomorTransaksi,
                ':anggota_id'          => (int)$data['anggota_id'],
                ':petugas_id'          => (int)$data['petugas_id'],
                ':tanggal_pinjam'      => $data['tanggal_pinjam'] ?? date('Y-m-d'),
                ':tanggal_jatuh_tempo' => $data['tanggal_jatuh_tempo'],
                ':total_buku'          => count($bukuItems),
                ':catatan'             => $data['catatan'] ?? null
            ]);

            $peminjamanId = (int)$this->db->lastInsertId();

            $stmtDetail = $this->db->prepare("
                INSERT INTO peminjaman_detail (peminjaman_id, buku_id, eksemplar_id, status) 
                VALUES (:pid, :bid, :eid, 'Dipinjam')
            ");

            $stmtUpdBuku = $this->db->prepare("
                UPDATE buku 
                SET stok_tersedia = GREATEST(0, stok_tersedia - 1),
                    status = CASE WHEN (stok_tersedia - 1) <= 0 THEN 'Habis Dipinjam' ELSE 'Tersedia' END 
                WHERE id = :bid
            ");

            $stmtUpdEx = $this->db->prepare("
                UPDATE buku_eksemplar 
                SET status = 'Dipinjam' 
                WHERE id = :eid
            ");

            foreach ($bukuItems as $item) {
                $bukuId = (int)$item['buku_id'];
                $eksemplarId = !empty($item['eksemplar_id']) ? (int)$item['eksemplar_id'] : null;

                // Jika eksemplar belum dipilih langsung, pilih eksemplar pertama yang tersedia
                if (!$eksemplarId) {
                    $stmtFindEx = $this->db->prepare("
                        SELECT id FROM buku_eksemplar 
                        WHERE buku_id = :bid AND status = 'Tersedia' 
                        LIMIT 1
                    ");
                    $stmtFindEx->execute([':bid' => $bukuId]);
                    $eksemplarId = $stmtFindEx->fetchColumn() ?: null;
                }

                $stmtDetail->execute([
                    ':pid' => $peminjamanId,
                    ':bid' => $bukuId,
                    ':eid' => $eksemplarId
                ]);

                // Kurangi stok buku
                $stmtUpdBuku->execute([':bid' => $bukuId]);

                // Tandai eksemplar dipinjam
                if ($eksemplarId) {
                    $stmtUpdEx->execute([':eid' => $eksemplarId]);
                }
            }

            $this->db->commit();
            return $peminjamanId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Failed saving peminjaman: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Memperbarui status peminjaman menjadi 'Terlambat' jika melewati tanggal jatuh tempo
     */
    public function updateStatusOverdue(): void {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("
            UPDATE peminjaman 
            SET status = 'Terlambat' 
            WHERE status = 'Dipinjam' AND tanggal_jatuh_tempo < :today
        ");
        $stmt->execute([':today' => $today]);
    }
}
