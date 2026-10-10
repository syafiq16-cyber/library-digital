<?php
/**
 * Model Buku & Eksemplar
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/qrcode_helper.php';

class Buku {
    private PDO $db;

    public function __construct() {
        $this->db = get_db();
    }

    /**
     * Generate Kode Buku Otomatis: BK-00001
     */
    public function generateKodeBuku(): string {
        $stmt = $this->db->query("SELECT MAX(id) AS max_id FROM buku");
        $row = $stmt->fetch();
        $nextId = ((int)($row['max_id'] ?? 0)) + 1;
        return 'BK-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Dapatkan daftar buku dengan filter, pencarian, dan pagination
     */
    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "
            SELECT b.*, k.nama_kategori, p.nama_pengarang, pub.nama_penerbit, r.kode_rak, r.nama_rak 
            FROM buku b 
            LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
            LEFT JOIN pengarang p ON b.pengarang_id = p.id 
            LEFT JOIN penerbit pub ON b.penerbit_id = pub.id 
            LEFT JOIN rak r ON b.rak_id = r.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (b.judul LIKE :search OR b.kode_buku LIKE :search OR b.isbn LIKE :search OR p.nama_pengarang LIKE :search OR pub.nama_penerbit LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['kategori_id'])) {
            $sql .= " AND b.kategori_id = :kategori_id";
            $params[':kategori_id'] = (int)$filters['kategori_id'];
        }

        if (!empty($filters['rak_id'])) {
            $sql .= " AND b.rak_id = :rak_id";
            $params[':rak_id'] = (int)$filters['rak_id'];
        }

        if (!empty($filters['tahun'])) {
            $sql .= " AND b.tahun_terbit = :tahun";
            $params[':tahun'] = (int)$filters['tahun'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND b.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY b.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Hitung total buku untuk pagination
     */
    public function countAll(array $filters = []): int {
        $sql = "
            SELECT COUNT(*) AS total 
            FROM buku b 
            LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
            LEFT JOIN pengarang p ON b.pengarang_id = p.id 
            LEFT JOIN penerbit pub ON b.penerbit_id = pub.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (b.judul LIKE :search OR b.kode_buku LIKE :search OR b.isbn LIKE :search OR p.nama_pengarang LIKE :search OR pub.nama_penerbit LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['kategori_id'])) {
            $sql .= " AND b.kategori_id = :kategori_id";
            $params[':kategori_id'] = (int)$filters['kategori_id'];
        }

        if (!empty($filters['rak_id'])) {
            $sql .= " AND b.rak_id = :rak_id";
            $params[':rak_id'] = (int)$filters['rak_id'];
        }

        if (!empty($filters['tahun'])) {
            $sql .= " AND b.tahun_terbit = :tahun";
            $params[':tahun'] = (int)$filters['tahun'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND b.status = :status";
            $params[':status'] = $filters['status'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT b.*, k.nama_kategori, p.nama_pengarang, pub.nama_penerbit, r.kode_rak, r.nama_rak, r.lokasi 
            FROM buku b 
            LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
            LEFT JOIN pengarang p ON b.pengarang_id = p.id 
            LEFT JOIN penerbit pub ON b.penerbit_id = pub.id 
            LEFT JOIN rak r ON b.rak_id = r.id 
            WHERE b.id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $book = $stmt->fetch();
        return $book ?: null;
    }

    public function getByKode(string $kodeBuku): ?array {
        $stmt = $this->db->prepare("
            SELECT b.*, k.nama_kategori, p.nama_pengarang, pub.nama_penerbit, r.kode_rak, r.nama_rak 
            FROM buku b 
            LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
            LEFT JOIN pengarang p ON b.pengarang_id = p.id 
            LEFT JOIN penerbit pub ON b.penerbit_id = pub.id 
            LEFT JOIN rak r ON b.rak_id = r.id 
            WHERE b.kode_buku = :kode 
            LIMIT 1
        ");
        $stmt->execute([':kode' => trim($kodeBuku)]);
        $book = $stmt->fetch();
        return $book ?: null;
    }

    public function getByQrToken(string $token): ?array {
        $stmt = $this->db->prepare("
            SELECT b.*, k.nama_kategori, p.nama_pengarang, pub.nama_penerbit, r.kode_rak, r.nama_rak 
            FROM buku b 
            LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
            LEFT JOIN pengarang p ON b.pengarang_id = p.id 
            LEFT JOIN penerbit pub ON b.penerbit_id = pub.id 
            LEFT JOIN rak r ON b.rak_id = r.id 
            WHERE b.qr_token = :token 
            LIMIT 1
        ");
        $stmt->execute([':token' => trim($token)]);
        $book = $stmt->fetch();
        return $book ?: null;
    }

    public function getEksemplarByBukuId(int $bukuId): array {
        $stmt = $this->db->prepare("
            SELECT e.*, b.judul, b.kode_buku 
            FROM buku_eksemplar e 
            JOIN buku b ON e.buku_id = b.id 
            WHERE e.buku_id = :buku_id 
            ORDER BY e.kode_eksemplar ASC
        ");
        $stmt->execute([':buku_id' => $bukuId]);
        return $stmt->fetchAll();
    }

    public function findEksemplar(string $identifierOrQr): ?array {
        $identifier = trim($identifierOrQr);
        $stmt = $this->db->prepare("
            SELECT e.*, b.judul, b.kode_buku, b.isbn, b.stok_tersedia, b.cover, 
                   k.nama_kategori, p.nama_pengarang, r.kode_rak, r.nama_rak 
            FROM buku_eksemplar e 
            JOIN buku b ON e.buku_id = b.id 
            LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
            LEFT JOIN pengarang p ON b.pengarang_id = p.id 
            LEFT JOIN rak r ON b.rak_id = r.id 
            WHERE e.qr_token = :val OR e.kode_eksemplar = :val OR e.nomor_barkod = :val 
            LIMIT 1
        ");
        $stmt->execute([':val' => $identifier]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int {
        $this->db->beginTransaction();
        try {
            $kodeBuku = !empty($data['kode_buku']) ? trim($data['kode_buku']) : $this->generateKodeBuku();
            $qrToken = make_book_qr_token($kodeBuku);

            $stokTotal = (int)($data['stok_total'] ?? 1);
            $stokTersedia = (int)($data['stok_tersedia'] ?? $stokTotal);

            $stmt = $this->db->prepare("
                INSERT INTO buku (
                    kode_buku, isbn, judul, pengarang_id, penerbit_id, kategori_id, subkategori,
                    rak_id, tahun_terbit, edisi, bahasa, jumlah_halaman, sumber_buku, harga,
                    stok_total, stok_tersedia, cover, deskripsi, qr_token, status
                ) VALUES (
                    :kode_buku, :isbn, :judul, :pengarang_id, :penerbit_id, :kategori_id, :subkategori,
                    :rak_id, :tahun_terbit, :edisi, :bahasa, :jumlah_halaman, :sumber_buku, :harga,
                    :stok_total, :stok_tersedia, :cover, :deskripsi, :qr_token, :status
                )
            ");

            $stmt->execute([
                ':kode_buku'      => $kodeBuku,
                ':isbn'           => $data['isbn'] ?? null,
                ':judul'          => trim($data['judul']),
                ':pengarang_id'   => (int)$data['pengarang_id'],
                ':penerbit_id'    => (int)$data['penerbit_id'],
                ':kategori_id'    => (int)$data['kategori_id'],
                ':subkategori'    => $data['subkategori'] ?? null,
                ':rak_id'         => (int)$data['rak_id'],
                ':tahun_terbit'   => (int)($data['tahun_terbit'] ?? date('Y')),
                ':edisi'          => $data['edisi'] ?? 'Cetakan 1',
                ':bahasa'         => $data['bahasa'] ?? 'Indonesia',
                ':jumlah_halaman' => (int)($data['jumlah_halaman'] ?? 100),
                ':sumber_buku'    => $data['sumber_buku'] ?? 'BOS',
                ':harga'          => (float)($data['harga'] ?? 0),
                ':stok_total'     => $stokTotal,
                ':stok_tersedia'  => $stokTersedia,
                ':cover'          => $data['cover'] ?? 'default_book.png',
                ':deskripsi'      => $data['deskripsi'] ?? null,
                ':qr_token'       => $qrToken,
                ':status'         => $stokTersedia > 0 ? 'Tersedia' : 'Habis Dipinjam'
            ]);

            $bukuId = (int)$this->db->lastInsertId();

            // Buat eksemplar fisik otomatis
            $stmtEx = $this->db->prepare("
                INSERT INTO buku_eksemplar (buku_id, kode_eksemplar, nomor_barkod, qr_token, kondisi, status) 
                VALUES (:buku_id, :kode_eksemplar, :nomor_barkod, :qr_token, 'Baik', 'Tersedia')
            ");

            for ($i = 1; $i <= $stokTotal; $i++) {
                $idx = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                $kodeEx = "{$kodeBuku}-{$idx}";
                $stmtEx->execute([
                    ':buku_id'        => $bukuId,
                    ':kode_eksemplar' => $kodeEx,
                    ':nomor_barkod'   => "BAR-{$kodeEx}",
                    ':qr_token'       => make_book_qr_token($kodeEx)
                ]);
            }

            $this->db->commit();
            return $bukuId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Failed creating book: " . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $data): bool {
        $fields = [
            'isbn = :isbn',
            'judul = :judul',
            'pengarang_id = :pengarang_id',
            'penerbit_id = :penerbit_id',
            'kategori_id = :kategori_id',
            'subkategori = :subkategori',
            'rak_id = :rak_id',
            'tahun_terbit = :tahun_terbit',
            'edisi = :edisi',
            'bahasa = :bahasa',
            'jumlah_halaman = :jumlah_halaman',
            'sumber_buku = :sumber_buku',
            'harga = :harga',
            'deskripsi = :deskripsi',
            'status = :status'
        ];

        $params = [
            ':id'             => $id,
            ':isbn'           => $data['isbn'] ?? null,
            ':judul'          => trim($data['judul']),
            ':pengarang_id'   => (int)$data['pengarang_id'],
            ':penerbit_id'    => (int)$data['penerbit_id'],
            ':kategori_id'    => (int)$data['kategori_id'],
            ':subkategori'    => $data['subkategori'] ?? null,
            ':rak_id'         => (int)$data['rak_id'],
            ':tahun_terbit'   => (int)($data['tahun_terbit'] ?? date('Y')),
            ':edisi'          => $data['edisi'] ?? 'Cetakan 1',
            ':bahasa'         => $data['bahasa'] ?? 'Indonesia',
            ':jumlah_halaman' => (int)($data['jumlah_halaman'] ?? 100),
            ':sumber_buku'    => $data['sumber_buku'] ?? 'BOS',
            ':harga'          => (float)($data['harga'] ?? 0),
            ':deskripsi'      => $data['deskripsi'] ?? null,
            ':status'         => $data['status'] ?? 'Tersedia'
        ];

        if (!empty($data['cover'])) {
            $fields[] = 'cover = :cover';
            $params[':cover'] = $data['cover'];
        }

        $sql = "UPDATE buku SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM buku WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function updateStokTersedia(int $bukuId, int $delta): bool {
        $stmt = $this->db->prepare("
            UPDATE buku 
            SET stok_tersedia = GREATEST(0, stok_tersedia + :delta),
                status = CASE WHEN (stok_tersedia + :delta) > 0 THEN 'Tersedia' ELSE 'Habis Dipinjam' END 
            WHERE id = :id
        ");
        return $stmt->execute([':delta' => $delta, ':id' => $bukuId]);
    }

    public function getBukuTerpopuler(int $limit = 5): array {
        $stmt = $this->db->prepare("
            SELECT b.id, b.kode_buku, b.judul, b.cover, k.nama_kategori, p.nama_pengarang, 
                   COUNT(pd.id) AS total_dipinjam 
            FROM buku b 
            LEFT JOIN peminjaman_detail pd ON b.id = pd.buku_id 
            LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
            LEFT JOIN pengarang p ON b.pengarang_id = p.id 
            GROUP BY b.id 
            ORDER BY total_dipinjam DESC, b.judul ASC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
