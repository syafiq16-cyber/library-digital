<?php
/**
 * Model Master Data (Kategori, Pengarang, Penerbit, Rak, Kelas, Jurusan)
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/database.php';

class Master {
    private PDO $db;

    public function __construct() {
        $this->db = get_db();
    }

    // ==========================================
    // JURUSAN
    // ==========================================
    public function getJurusan(): array {
        return $this->db->query("SELECT * FROM jurusan ORDER BY id ASC")->fetchAll();
    }

    public function createJurusan(string $kode, string $nama): int {
        $stmt = $this->db->prepare("INSERT INTO jurusan (kode_jurusan, nama_jurusan) VALUES (:kode, :nama)");
        $stmt->execute([':kode' => strtoupper(trim($kode)), ':nama' => trim($nama)]);
        return (int)$this->db->lastInsertId();
    }

    public function updateJurusan(int $id, string $kode, string $nama): bool {
        $stmt = $this->db->prepare("UPDATE jurusan SET kode_jurusan = :kode, nama_jurusan = :nama WHERE id = :id");
        return $stmt->execute([':kode' => strtoupper(trim($kode)), ':nama' => trim($nama), ':id' => $id]);
    }

    public function deleteJurusan(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM jurusan WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ==========================================
    // KELAS
    // ==========================================
    public function getKelas(): array {
        $stmt = $this->db->query("
            SELECT k.*, j.nama_jurusan, j.kode_jurusan 
            FROM kelas k 
            JOIN jurusan j ON k.jurusan_id = j.id 
            ORDER BY k.tingkat ASC, j.kode_jurusan ASC, k.rombel ASC
        ");
        return $stmt->fetchAll();
    }

    public function createKelas(string $tingkat, int $jurusanId, string $rombel, string $namaKelas): int {
        $stmt = $this->db->prepare("
            INSERT INTO kelas (tingkat, jurusan_id, rombel, nama_kelas) 
            VALUES (:tingkat, :jurusan_id, :rombel, :nama_kelas)
        ");
        $stmt->execute([
            ':tingkat'    => $tingkat,
            ':jurusan_id' => $jurusanId,
            ':rombel'     => $rombel,
            ':nama_kelas' => $namaKelas
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateKelas(int $id, string $tingkat, int $jurusanId, string $rombel, string $namaKelas): bool {
        $stmt = $this->db->prepare("
            UPDATE kelas 
            SET tingkat = :tingkat, jurusan_id = :jurusan_id, rombel = :rombel, nama_kelas = :nama_kelas 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id'         => $id,
            ':tingkat'    => $tingkat,
            ':jurusan_id' => $jurusanId,
            ':rombel'     => $rombel,
            ':nama_kelas' => $namaKelas
        ]);
    }

    public function deleteKelas(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM kelas WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ==========================================
    // KATEGORI BUKU
    // ==========================================
    public function getKategori(): array {
        return $this->db->query("
            SELECT k.*, COUNT(b.id) AS total_buku 
            FROM kategori_buku k 
            LEFT JOIN buku b ON k.id = b.kategori_id 
            GROUP BY k.id 
            ORDER BY k.nama_kategori ASC
        ")->fetchAll();
    }

    public function createKategori(string $kode, string $nama, ?string $deskripsi): int {
        $stmt = $this->db->prepare("INSERT INTO kategori_buku (kode_kategori, nama_kategori, deskripsi) VALUES (:kode, :nama, :deskripsi)");
        $stmt->execute([':kode' => trim($kode), ':nama' => trim($nama), ':deskripsi' => $deskripsi]);
        return (int)$this->db->lastInsertId();
    }

    public function updateKategori(int $id, string $kode, string $nama, ?string $deskripsi): bool {
        $stmt = $this->db->prepare("UPDATE kategori_buku SET kode_kategori = :kode, nama_kategori = :nama, deskripsi = :deskripsi WHERE id = :id");
        return $stmt->execute([':id' => $id, ':kode' => trim($kode), ':nama' => trim($nama), ':deskripsi' => $deskripsi]);
    }

    public function deleteKategori(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM kategori_buku WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ==========================================
    // PENGARANG
    // ==========================================
    public function getPengarang(): array {
        return $this->db->query("
            SELECT p.*, COUNT(b.id) AS total_buku 
            FROM pengarang p 
            LEFT JOIN buku b ON p.id = b.pengarang_id 
            GROUP BY p.id 
            ORDER BY p.nama_pengarang ASC
        ")->fetchAll();
    }

    public function createPengarang(string $nama, ?string $biografi): int {
        $stmt = $this->db->prepare("INSERT INTO pengarang (nama_pengarang, biografi) VALUES (:nama, :bio)");
        $stmt->execute([':nama' => trim($nama), ':bio' => $biografi]);
        return (int)$this->db->lastInsertId();
    }

    public function updatePengarang(int $id, string $nama, ?string $biografi): bool {
        $stmt = $this->db->prepare("UPDATE pengarang SET nama_pengarang = :nama, biografi = :bio WHERE id = :id");
        return $stmt->execute([':id' => $id, ':nama' => trim($nama), ':bio' => $biografi]);
    }

    public function deletePengarang(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM pengarang WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ==========================================
    // PENERBIT
    // ==========================================
    public function getPenerbit(): array {
        return $this->db->query("
            SELECT p.*, COUNT(b.id) AS total_buku 
            FROM penerbit p 
            LEFT JOIN buku b ON p.id = b.penerbit_id 
            GROUP BY p.id 
            ORDER BY p.nama_penerbit ASC
        ")->fetchAll();
    }

    public function createPenerbit(string $nama, ?string $alamat, ?string $kontak): int {
        $stmt = $this->db->prepare("INSERT INTO penerbit (nama_penerbit, alamat, kontak) VALUES (:nama, :alamat, :kontak)");
        $stmt->execute([':nama' => trim($nama), ':alamat' => $alamat, ':kontak' => $kontak]);
        return (int)$this->db->lastInsertId();
    }

    public function updatePenerbit(int $id, string $nama, ?string $alamat, ?string $kontak): bool {
        $stmt = $this->db->prepare("UPDATE penerbit SET nama_penerbit = :nama, alamat = :alamat, kontak = :kontak WHERE id = :id");
        return $stmt->execute([':id' => $id, ':nama' => trim($nama), ':alamat' => $alamat, ':kontak' => $kontak]);
    }

    public function deletePenerbit(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM penerbit WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ==========================================
    // RAK
    // ==========================================
    public function getRak(): array {
        return $this->db->query("
            SELECT r.*, COUNT(b.id) AS total_buku 
            FROM rak r 
            LEFT JOIN buku b ON r.id = b.rak_id 
            GROUP BY r.id 
            ORDER BY r.kode_rak ASC
        ")->fetchAll();
    }

    public function createRak(string $kode, string $nama, string $lokasi, int $kapasitas): int {
        $stmt = $this->db->prepare("INSERT INTO rak (kode_rak, nama_rak, lokasi, kapasitas) VALUES (:kode, :nama, :lokasi, :kapasitas)");
        $stmt->execute([':kode' => trim($kode), ':nama' => trim($nama), ':lokasi' => trim($lokasi), ':kapasitas' => $kapasitas]);
        return (int)$this->db->lastInsertId();
    }

    public function updateRak(int $id, string $kode, string $nama, string $lokasi, int $kapasitas): bool {
        $stmt = $this->db->prepare("UPDATE rak SET kode_rak = :kode, nama_rak = :nama, lokasi = :lokasi, kapasitas = :kapasitas WHERE id = :id");
        return $stmt->execute([':id' => $id, ':kode' => trim($kode), ':nama' => trim($nama), ':lokasi' => trim($lokasi), ':kapasitas' => $kapasitas]);
    }

    public function deleteRak(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM rak WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
