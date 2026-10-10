-- ==========================================================
-- APLIKASI PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR
-- Skrip Database MySQL / MariaDB Lengkap
-- Database: perpustakaan_db
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `perpustakaan_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `perpustakaan_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Tabel Roles
-- --------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_role` VARCHAR(50) NOT NULL UNIQUE,
  `deskripsi` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `nama_role`, `deskripsi`) VALUES
(1, 'Administrator', 'Akses penuh seluruh sistem, konfigurasi, backup, dan manajemen user'),
(2, 'Petugas', 'Mengelola sirkulasi peminjaman, pengembalian, buku, dan anggota'),
(3, 'Kepala Perpustakaan', 'Melihat dashboard, analitik eksekutif, dan laporan lengkap'),
(4, 'Anggota', 'Melihat katalog, riwayat peminjaman, dan status buku');

-- --------------------------------------------------------
-- 2. Tabel Users
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NULL,
  `role_id` INT NOT NULL,
  `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `remember_token` VARCHAR(100) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_role_id` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password default: password123
INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `role_id`, `status_aktif`) VALUES
(1, 'admin', '$2y$12$GnkpL6hfcEorGIisSOO0Iuqn/PyE7h/.k1gRbuw.76Hq05p0Wmi.K', 'Administrator Utama', 'admin@smkn2sumbawa.sch.id', 1, 1),
(2, 'petugas1', '$2y$12$GnkpL6hfcEorGIisSOO0Iuqn/PyE7h/.k1gRbuw.76Hq05p0Wmi.K', 'Ahmad Fauzi, S.Pd', 'fauzi@smkn2sumbawa.sch.id', 2, 1),
(3, 'petugas2', '$2y$12$GnkpL6hfcEorGIisSOO0Iuqn/PyE7h/.k1gRbuw.76Hq05p0Wmi.K', 'Dewi Lestari, S.I.Pust', 'dewi@smkn2sumbawa.sch.id', 2, 1),
(4, 'kepala', '$2y$12$GnkpL6hfcEorGIisSOO0Iuqn/PyE7h/.k1gRbuw.76Hq05p0Wmi.K', 'Drs. H. M. Nasir, M.Pd', 'kepala.perpus@smkn2sumbawa.sch.id', 3, 1),
(5, 'siswa1', '$2y$12$GnkpL6hfcEorGIisSOO0Iuqn/PyE7h/.k1gRbuw.76Hq05p0Wmi.K', 'Budi Santoso', 'budi@siswa.smkn2sumbawa.sch.id', 4, 1);

-- --------------------------------------------------------
-- 3. Tabel Jurusan (Kompetensi Keahlian SMKN 2 Sumbawa Besar)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `jurusan`;
CREATE TABLE `jurusan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_jurusan` VARCHAR(20) NOT NULL UNIQUE,
  `nama_jurusan` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `jurusan` (`id`, `kode_jurusan`, `nama_jurusan`) VALUES
(1, 'TSM', 'Teknik Sepeda Motor'),
(2, 'TOI', 'Teknik Otomasi Industri'),
(3, 'TEI', 'Teknik Elektronika Industri'),
(4, 'RPL', 'Rekayasa Perangkat Lunak'),
(5, 'TITL', 'Teknik Instalasi Tenaga Listrik');

-- --------------------------------------------------------
-- 4. Tabel Kelas
-- --------------------------------------------------------
DROP TABLE IF EXISTS `kelas`;
CREATE TABLE `kelas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tingkat` ENUM('X', 'XI', 'XII') NOT NULL,
  `jurusan_id` INT NOT NULL,
  `rombel` VARCHAR(10) NOT NULL DEFAULT '1',
  `nama_kelas` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_jurusan_id` (`jurusan_id`),
  CONSTRAINT `fk_kelas_jurusan` FOREIGN KEY (`jurusan_id`) REFERENCES `jurusan` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kelas` (`id`, `tingkat`, `jurusan_id`, `rombel`, `nama_kelas`) VALUES
(1, 'X', 1, '1', 'X TSM 1'),
(2, 'X', 1, '2', 'X TSM 2'),
(3, 'X', 2, '1', 'X TOI 1'),
(4, 'X', 3, '1', 'X TEI 1'),
(5, 'X', 4, '1', 'X RPL 1'),
(6, 'XI', 1, '1', 'XI TSM 1'),
(7, 'XI', 2, '1', 'XI TOI 1'),
(8, 'XI', 4, '1', 'XI RPL 1'),
(9, 'XII', 1, '1', 'XII TSM 1'),
(10, 'XII', 4, '1', 'XII RPL 1');

-- --------------------------------------------------------
-- 5. Tabel Kategori Buku
-- --------------------------------------------------------
DROP TABLE IF EXISTS `kategori_buku`;
CREATE TABLE `kategori_buku` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_kategori` VARCHAR(20) NOT NULL UNIQUE,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `deskripsi` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kategori_buku` (`id`, `kode_kategori`, `nama_kategori`, `deskripsi`) VALUES
(1, 'KAT-OTM', 'Kejuruan Otomotif & Sepeda Motor', 'Koleksi teknik mesin, kelistrikan bodi, dan servis sepeda motor'),
(2, 'KAT-ELK', 'Teknik Elektronika & Otomasi', 'Koleksi PLC, mikrokontroler, pneumatik, dan robotika industri'),
(3, 'KAT-KOM', 'Komputer, IT & Pemrograman', 'Buku rekayasa perangkat lunak, database, web, dan jaringan'),
(4, 'KAT-PEL', 'Pelajaran Umum & Wajib', 'Buku teks kurikulum merdeka (Bahasa, PPKn, Sejarah, PAI)'),
(5, 'KAT-MTH', 'Sains & Matematika Terapan', 'Koleksi matematika teknik, fisika terapan, dan kimia dasar'),
(6, 'KAT-FIK', 'Fiksi & Sastra', 'Koleksi antologi cerpen, puisi, dan drama sastra Indonesia'),
(7, 'KAT-NVL', 'Novel & Inspirasi', 'Novel pembangun karakter, literasi fiksi, dan biografi inspiratif'),
(8, 'KAT-REF', 'Referensi & Kamus', 'Ensiklopedia umum, kamus istilah teknik, dan atlas'),
(9, 'KAT-AGM', 'Agama & Budi Pekerti', 'Buku keagamaan, akhlak mulia, dan studi Islam moderat'),
(10, 'KAT-MJL', 'Majalah & Jurnal Vokasi', 'Publikasi berkala industri vokasi, teknologi, dan buletin sekolah');

-- --------------------------------------------------------
-- 6. Tabel Pengarang
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pengarang`;
CREATE TABLE `pengarang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_pengarang` VARCHAR(100) NOT NULL,
  `biografi` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pengarang` (`id`, `nama_pengarang`, `biografi`) VALUES
(1, 'Ir. Zainal Arifin, M.T.', 'Praktisi dan dosen teknik otomotif nasional'),
(2, 'Bambang Supriyanto, S.T.', 'Pakar otomasi industri dan instrumentasi'),
(3, 'Budi Raharjo', 'Penulis buku pemrograman web dan teknologi informasi'),
(4, 'Prof. Dr. Ir. Hari Purnomo', 'Guru besar teknik industri dan ergonomi'),
(5, 'Andrea Hirata', 'Novelis Indonesia, penulis tetralogi Laskar Pelangi'),
(6, 'Pramoedya Ananta Toer', 'Sastrawan legendaris Indonesia'),
(7, 'Drs. Supardi, M.Sc.', 'Penyusun buku teks matematika sekolah kejuruan'),
(8, 'Tim Kemdikbudristek', 'Penyusun kurikulum dan modul ajar vokasi nasional'),
(9, 'Ahmad Rifa\'i, S.Kom.', 'Instruktur rekayasa perangkat lunak SMK'),
(10, 'H. M. Quraish Shihab', 'Cendekiawan muslim dan penulis tafsir Al-Mishbah');

-- --------------------------------------------------------
-- 7. Tabel Penerbit
-- --------------------------------------------------------
DROP TABLE IF EXISTS `penerbit`;
CREATE TABLE `penerbit` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_penerbit` VARCHAR(100) NOT NULL,
  `alamat` VARCHAR(255) NULL,
  `kontak` VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `penerbit` (`id`, `nama_penerbit`, `alamat`, `kontak`) VALUES
(1, 'Penerbit Andi Yogyakarta', 'Jl. Beo 38-40, Sleman, Yogyakarta', '0274-561881'),
(2, 'Penerbit Erlangga', 'Jl. H. Baping Raya No. 100, Jakarta Timur', '021-8717006'),
(3, 'Informatika Bandung', 'Jl. Buah Batu No. 121, Bandung', '022-7310842'),
(4, 'Gramedia Pustaka Utama', 'Gedung Kompas Gramedia, Jakarta Pusat', '021-53650110'),
(5, 'Kemdikbudristek RI', 'Komp. Kemdikbud Gedung A, Senayan, Jakarta', '021-5711144'),
(6, 'Yudhistira Ghalia Indonesia', 'Jl. Raya Rancamaya Km 1 No. 67, Bogor', '0251-8240623');

-- --------------------------------------------------------
-- 8. Tabel Rak
-- --------------------------------------------------------
DROP TABLE IF EXISTS `rak`;
CREATE TABLE `rak` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_rak` VARCHAR(20) NOT NULL UNIQUE,
  `nama_rak` VARCHAR(50) NOT NULL,
  `lokasi` VARCHAR(100) NOT NULL,
  `kapasitas` INT NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rak` (`id`, `kode_rak`, `nama_rak`, `lokasi`, `kapasitas`) VALUES
(1, 'RAK-A1', 'Rak Otomotif & Sepeda Motor', 'Lantai 1 Sayap Kiri', 150),
(2, 'RAK-B1', 'Rak Elektronika & Otomasi', 'Lantai 1 Sayap Tengah', 150),
(3, 'RAK-C1', 'Rak Komputer & Informatika', 'Lantai 1 Sayap Kanan', 200),
(4, 'RAK-D1', 'Rak Pelajaran Umum & Sains', 'Lantai 2 Sayap Kiri', 250),
(5, 'RAK-E1', 'Rak Sastra, Novel & Fiksi', 'Lantai 2 Sayap Kanan', 200),
(6, 'RAK-REF', 'Rak Referensi & Ensiklopedia', 'Ruang Baca Khusus Lantai 2', 100);

-- --------------------------------------------------------
-- 9. Tabel Anggota
-- Menggunakan QR Code format: LIBRARY-MEMBER:{nomor_anggota}
-- --------------------------------------------------------
DROP TABLE IF EXISTS `anggota`;
CREATE TABLE `anggota` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `nomor_anggota` VARCHAR(30) NOT NULL UNIQUE,
  `identitas_no` VARCHAR(30) NOT NULL UNIQUE, -- NISN atau NIP
  `nama` VARCHAR(100) NOT NULL,
  `jenis_anggota` ENUM('Siswa', 'Guru', 'Tenaga Kependidikan', 'Umum') NOT NULL DEFAULT 'Siswa',
  `jenis_kelamin` ENUM('L', 'P') NOT NULL,
  `kelas_id` INT NULL,
  `jurusan_id` INT NULL,
  `nomor_hp` VARCHAR(20) NULL,
  `alamat` TEXT NULL,
  `foto` VARCHAR(255) NULL,
  `qr_token` VARCHAR(100) NOT NULL UNIQUE,
  `qr_image` VARCHAR(255) NULL,
  `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `tanggal_registrasi` DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_anggota_kelas` (`kelas_id`),
  INDEX `idx_anggota_jurusan` (`jurusan_id`),
  INDEX `idx_anggota_qr` (`qr_token`),
  CONSTRAINT `fk_anggota_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_anggota_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_anggota_jurusan` FOREIGN KEY (`jurusan_id`) REFERENCES `jurusan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `anggota` (`id`, `user_id`, `nomor_anggota`, `identitas_no`, `nama`, `jenis_anggota`, `jenis_kelamin`, `kelas_id`, `jurusan_id`, `nomor_hp`, `alamat`, `foto`, `qr_token`, `status_aktif`, `tanggal_registrasi`) VALUES
(1, 5, 'AGT-00001', '0061234501', 'Budi Santoso', 'Siswa', 'L', 1, 1, '081234567801', 'Jl. Garuda No. 12, Sumbawa Besar', 'default_avatar_m.png', 'LIBRARY-MEMBER:AGT-00001', 1, '2026-07-15'),
(2, NULL, 'AGT-00002', '0061234502', 'Siti Rahmawati', 'Siswa', 'P', 5, 4, '081234567802', 'Jl. Hasanuddin No. 45, Sumbawa Besar', 'default_avatar_f.png', 'LIBRARY-MEMBER:AGT-00002', 1, '2026-07-15'),
(3, NULL, 'AGT-00003', '0061234503', 'Ahmad Dani Pratama', 'Siswa', 'L', 3, 2, '081234567803', 'Kel. Brang Biji, Sumbawa Besar', 'default_avatar_m.png', 'LIBRARY-MEMBER:AGT-00003', 1, '2026-07-16'),
(4, NULL, 'AGT-00004', '0061234504', 'Rina Anggraini', 'Siswa', 'P', 4, 3, '081234567804', 'Kel. Lempeh, Sumbawa Besar', 'default_avatar_f.png', 'LIBRARY-MEMBER:AGT-00004', 1, '2026-07-16'),
(5, NULL, 'AGT-00005', '0061234505', 'Dimas Arya Saputra', 'Siswa', 'L', 6, 1, '081234567805', 'Jl. Cendrawasih No. 8, Sumbawa', 'default_avatar_m.png', 'LIBRARY-MEMBER:AGT-00005', 1, '2026-07-17'),
(6, NULL, 'AGT-00006', '0061234506', 'Nabila Putri Azzahra', 'Siswa', 'P', 8, 4, '081234567806', 'Kel. Bugis, Sumbawa Besar', 'default_avatar_f.png', 'LIBRARY-MEMBER:AGT-00006', 1, '2026-07-17'),
(7, NULL, 'AGT-00007', '0061234507', 'Muhammad Fikri', 'Siswa', 'L', 2, 1, '081234567807', 'Kec. Labuhan Badas, Sumbawa', 'default_avatar_m.png', 'LIBRARY-MEMBER:AGT-00007', 1, '2026-07-18'),
(8, NULL, 'AGT-00008', '0061234508', 'Nurul Hikmah', 'Siswa', 'P', 5, 4, '081234567808', 'Kel. Seketeng, Sumbawa Besar', 'default_avatar_f.png', 'LIBRARY-MEMBER:AGT-00008', 1, '2026-07-18'),
(9, NULL, 'AGT-00009', '0061234509', 'Rian Hidayatullah', 'Siswa', 'L', 7, 2, '081234567809', 'Desa Karang Dima, Labuhan Badas', 'default_avatar_m.png', 'LIBRARY-MEMBER:AGT-00009', 1, '2026-07-19'),
(10, NULL, 'AGT-00010', '0061234510', 'Fitri Handayani', 'Siswa', 'P', 4, 3, '081234567810', 'Jl. Diponegoro No. 19, Sumbawa', 'default_avatar_f.png', 'LIBRARY-MEMBER:AGT-00010', 1, '2026-07-19'),
(11, NULL, 'AGT-00011', '0061234511', 'Bayu Setiawan', 'Siswa', 'L', 9, 1, '081234567811', 'Kel. Samapuin, Sumbawa Besar', 'default_avatar_m.png', 'LIBRARY-MEMBER:AGT-00011', 1, '2026-07-20'),
(12, NULL, 'AGT-00012', '0061234512', 'Tri Wahyuni', 'Siswa', 'P', 10, 4, '081234567812', 'Kel. Uma Sima, Sumbawa Besar', 'default_avatar_f.png', 'LIBRARY-MEMBER:AGT-00012', 1, '2026-07-20'),
(13, NULL, 'AGT-00013', '0061234513', 'Ilham Kurniawan', 'Siswa', 'L', 1, 1, '081234567813', 'Jl. Mawar No. 3, Sumbawa', 'default_avatar_m.png', 'LIBRARY-MEMBER:AGT-00013', 1, '2026-07-21'),
(14, NULL, 'AGT-00014', '0061234514', 'Eka Novitasari', 'Siswa', 'P', 3, 2, '081234567814', 'Kel. Pekat, Sumbawa Besar', 'default_avatar_f.png', 'LIBRARY-MEMBER:AGT-00014', 1, '2026-07-21'),
(15, NULL, 'AGT-00015', '0061234515', 'Yoga Aditya', 'Siswa', 'L', 8, 4, '081234567815', 'Kel. Brang Bara, Sumbawa Besar', 'default_avatar_m.png', 'LIBRARY-MEMBER:AGT-00015', 1, '2026-07-22'),
(16, NULL, 'AGT-00016', '198501102010011005', 'Dedi Kurniawan, S.Pd', 'Guru', 'L', NULL, 1, '081234567816', 'Jl. Pramuka No. 7, Sumbawa', 'default_avatar_m.png', 'LIBRARY-MEMBER:AGT-00016', 1, '2026-07-10'),
(17, NULL, 'AGT-00017', '198705202012022003', 'Sri Mulyani, S.Kom', 'Guru', 'P', NULL, 4, '081234567817', 'Jl. Mangga No. 22, Sumbawa', 'default_avatar_f.png', 'LIBRARY-MEMBER:AGT-00017', 1, '2026-07-10'),
(18, NULL, 'AGT-00018', '199003152015031002', 'Irwan Saputra, S.T.', 'Guru', 'L', NULL, 2, '081234567818', 'Kel. Lempeh, Sumbawa Besar', 'default_avatar_m.png', 'LIBRARY-MEMBER:AGT-00018', 1, '2026-07-11'),
(19, NULL, 'AGT-00019', '199208122019032008', 'Neneng Maryani, A.Md', 'Tenaga Kependidikan', 'P', NULL, NULL, '081234567819', 'Jl. Kamboja No. 14, Sumbawa', 'default_avatar_f.png', 'LIBRARY-MEMBER:AGT-00019', 1, '2026-07-12'),
(20, NULL, 'AGT-00020', '5204012304950001', 'Syamsuddin Hamzah', 'Umum', 'L', NULL, NULL, '081234567820', 'Kel. Brang Biji, Sumbawa Besar', 'default_avatar_m.png', 'LIBRARY-MEMBER:AGT-00020', 1, '2026-07-25');

-- --------------------------------------------------------
-- 10. Tabel Buku
-- Menggunakan QR Code format: LIBRARY-BOOK:{kode_buku}
-- --------------------------------------------------------
DROP TABLE IF EXISTS `buku`;
CREATE TABLE `buku` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_buku` VARCHAR(30) NOT NULL UNIQUE,
  `isbn` VARCHAR(30) NULL,
  `judul` VARCHAR(255) NOT NULL,
  `pengarang_id` INT NOT NULL,
  `penerbit_id` INT NOT NULL,
  `kategori_id` INT NOT NULL,
  `subkategori` VARCHAR(100) NULL,
  `rak_id` INT NOT NULL,
  `tahun_terbit` YEAR NOT NULL,
  `edisi` VARCHAR(30) NULL DEFAULT 'Cetakan 1',
  `bahasa` VARCHAR(50) NOT NULL DEFAULT 'Indonesia',
  `jumlah_halaman` INT NOT NULL DEFAULT 150,
  `sumber_buku` ENUM('BOS', 'BOSDA', 'Hibah/Sumbangan', 'Pembelian Mandiri') NOT NULL DEFAULT 'BOS',
  `harga` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `stok_total` INT NOT NULL DEFAULT 5,
  `stok_tersedia` INT NOT NULL DEFAULT 5,
  `cover` VARCHAR(255) NULL,
  `deskripsi` TEXT NULL,
  `qr_token` VARCHAR(100) NOT NULL UNIQUE,
  `qr_image` VARCHAR(255) NULL,
  `status` ENUM('Tersedia', 'Habis Dipinjam', 'Nonaktif') NOT NULL DEFAULT 'Tersedia',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_buku_kategori` (`kategori_id`),
  INDEX `idx_buku_pengarang` (`pengarang_id`),
  INDEX `idx_buku_penerbit` (`penerbit_id`),
  INDEX `idx_buku_rak` (`rak_id`),
  INDEX `idx_buku_qr` (`qr_token`),
  CONSTRAINT `fk_buku_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_buku` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_buku_pengarang` FOREIGN KEY (`pengarang_id`) REFERENCES `pengarang` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_buku_penerbit` FOREIGN KEY (`penerbit_id`) REFERENCES `penerbit` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_buku_rak` FOREIGN KEY (`rak_id`) REFERENCES `rak` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `buku` (`id`, `kode_buku`, `isbn`, `judul`, `pengarang_id`, `penerbit_id`, `kategori_id`, `subkategori`, `rak_id`, `tahun_terbit`, `edisi`, `bahasa`, `jumlah_halaman`, `sumber_buku`, `harga`, `stok_total`, `stok_tersedia`, `cover`, `deskripsi`, `qr_token`, `status`) VALUES
(1, 'BK-00001', '978-623-01-0101-1', 'Teknologi Mesin Sepeda Motor Kontemporer', 1, 1, 1, 'Mesin Otomotif', 1, 2024, 'Edisi Revisi', 'Indonesia', 280, 'BOS', 85000.00, 8, 6, 'default_book.png', 'Panduan komprehensif sistem transmisi, bahan bakar injeksi, dan tune up motor modern.', 'LIBRARY-BOOK:BK-00001', 'Tersedia'),
(2, 'BK-00002', '978-623-01-0102-8', 'Pemeliharaan Kelistrikan Sepeda Motor Kelas XI', 1, 2, 1, 'Kelistrikan Otomotif', 1, 2023, 'Cetakan 2', 'Indonesia', 240, 'BOS', 78000.00, 6, 5, 'default_book.png', 'Membahas sistem pengapian CDI, sensor ECU, sistem starter elektrik, dan diagram kelistrikan.', 'LIBRARY-BOOK:BK-00002', 'Tersedia'),
(3, 'BK-00003', '978-602-02-0303-3', 'Otomasi Industri Berbasis PLC dan SCADA', 2, 1, 2, 'Kontrol Industri', 2, 2023, 'Edisi 1', 'Indonesia', 320, 'BOS', 110000.00, 5, 4, 'default_book.png', 'Implementasi otomasi pabrik menggunakan PLC OMRON, Siemens, dan antarmuka HMI SCADA.', 'LIBRARY-BOOK:BK-00003', 'Tersedia'),
(4, 'BK-00004', '978-602-02-0304-0', 'Pneumatik & Elektro-Pneumatik Terapan', 2, 2, 2, 'Pneumatik Industri', 2, 2022, 'Cetakan 3', 'Indonesia', 210, 'BOS', 72000.00, 7, 7, 'default_book.png', 'Dasar rangkaian pneumatik, katup kontrol directional, silinder kerja ganda, dan troubleshooting.', 'LIBRARY-BOOK:BK-00004', 'Tersedia'),
(5, 'BK-00005', '978-602-6232-15-1', 'Pemrograman Web Dinamis dengan PHP dan MySQL', 3, 3, 3, 'Web Development', 3, 2024, 'Edisi 4', 'Indonesia', 412, 'BOS', 125000.00, 10, 8, 'default_book.png', 'Panduan praktis membangun aplikasi sistem informasi berbasis web, PDO, dan keamanan aplikasi.', 'LIBRARY-BOOK:BK-00005', 'Tersedia'),
(6, 'BK-00006', '978-602-6232-20-5', 'Rekayasa Perangkat Lunak Berorientasi Objek dengan Java', 9, 3, 3, 'Software Engineering', 3, 2023, 'Edisi 2', 'Indonesia', 360, 'BOS', 98000.00, 6, 6, 'default_book.png', 'Konsep OOP, inheritance, polimorfisme, design pattern, dan perancangan diagram UML.', 'LIBRARY-BOOK:BK-00006', 'Tersedia'),
(7, 'BK-00007', '978-623-04-1100-2', 'Matematika Terapan untuk Sekolah Menengah Kejuruan', 7, 2, 5, 'Matematika Teknik', 4, 2023, 'Cetakan 1', 'Indonesia', 310, 'BOS', 75000.00, 12, 10, 'default_book.png', 'Trigonometri, aljabar linear, matriks, kalkulus dasar, dan aplikasi perhitungan teknik.', 'LIBRARY-BOOK:BK-00007', 'Tersedia'),
(8, 'BK-00008', '978-623-04-1101-9', 'Fisika Terapan Bidang Keahlian Teknologi & Rekayasa', 4, 2, 5, 'Fisika Teknik', 4, 2022, 'Edisi 3', 'Indonesia', 260, 'BOS', 70000.00, 8, 8, 'default_book.png', 'Mekanika fluida, termodinamika terapan, getaran, gelombang, dan listrik arus searah/bolak-balik.', 'LIBRARY-BOOK:BK-00008', 'Tersedia'),
(9, 'BK-00009', '978-979-1227-01-8', 'Laskar Pelangi', 5, 4, 7, 'Sastra Inspiratif', 5, 2021, 'Cetakan 45', 'Indonesia', 529, 'Hibah/Sumbangan', 89000.00, 5, 3, 'default_book.png', 'Kisah perjuangan sepuluh anak di Belitung dalam menempuh pendidikan di tengah keterbatasan.', 'LIBRARY-BOOK:BK-00009', 'Tersedia'),
(10, 'BK-00010', '978-979-9731-23-4', 'Bumi Manusia', 6, 4, 6, 'Roman Sejarah', 5, 2020, 'Cetakan 32', 'Indonesia', 535, 'Hibah/Sumbangan', 115000.00, 4, 4, 'default_book.png', 'Mahakarya roman sejarah tetralogi buru yang menggambarkan kebangkitan nasionalisme.', 'LIBRARY-BOOK:BK-00010', 'Tersedia'),
(11, 'BK-00011', '978-623-11-0021-0', 'Sistem Sasis & Pemindah Tenaga Sepeda Motor', 1, 1, 1, 'Sasis Otomotif', 1, 2023, 'Edisi 1', 'Indonesia', 230, 'BOS', 79000.00, 5, 5, 'default_book.png', 'Perawatan rem hidrolik ABS, suspensi teleskopik, rangka motor, dan balancing roda.', 'LIBRARY-BOOK:BK-00011', 'Tersedia'),
(12, 'BK-00012', '978-623-11-0022-7', 'Dasar-Dasar Teknik Elektronika Industri Kelas X', 2, 5, 2, 'Elektronika Dasar', 2, 2023, 'Kurikulum Merdeka', 'Indonesia', 270, 'BOS', 65000.00, 15, 14, 'default_book.png', 'Komponen pasif dan aktif, hukum Ohm/Kirchhoff, alat ukur osiloskop, dan soldering.', 'LIBRARY-BOOK:BK-00012', 'Tersedia'),
(13, 'BK-00013', '978-602-6232-45-8', 'Mastering Framework JavaScript Modern (React & Vue)', 3, 3, 3, 'Frontend Web', 3, 2024, 'Cetakan 1', 'Indonesia', 340, 'BOS', 135000.00, 6, 5, 'default_book.png', 'Arsitektur komponen, state management, REST API consumption, dan single page application.', 'LIBRARY-BOOK:BK-00013', 'Tersedia'),
(14, 'BK-00014', '978-602-6232-46-5', 'Basis Data Relasional dan Implementasi SQL Server', 9, 3, 3, 'Database Management', 3, 2022, 'Edisi 2', 'Indonesia', 290, 'BOS', 92000.00, 7, 7, 'default_book.png', 'Normalisasi data 1NF-3NF, DDL, DML, stored procedure, triggers, dan optimasi query indeks.', 'LIBRARY-BOOK:BK-00014', 'Tersedia'),
(15, 'BK-00015', '978-602-298-801-4', 'Pendidikan Pancasila dan Kewarganegaraan Kelas X', 8, 5, 4, 'PPKn Vokasi', 4, 2023, 'Kurikulum Merdeka', 'Indonesia', 190, 'BOS', 55000.00, 20, 20, 'default_book.png', 'Wawasan kebangsaan, integritas kepribadian, konstitusi, dan hak asasi manusia.', 'LIBRARY-BOOK:BK-00015', 'Tersedia'),
(16, 'BK-00016', '978-602-298-802-1', 'Bahasa Indonesia Berbasis Vokasi Kelas XI', 8, 5, 4, 'Bahasa & Literasi', 4, 2023, 'Kurikulum Merdeka', 'Indonesia', 220, 'BOS', 58000.00, 18, 18, 'default_book.png', 'Penulisan laporan ilmiah, proposal usaha teknik, surat resmi, dan komunikasi profesional.', 'LIBRARY-BOOK:BK-00016', 'Tersedia'),
(17, 'BK-00017', '978-979-075-300-1', 'Ensiklopedia Teknologi Otomotif Dunia', 1, 6, 8, 'Ensiklopedia Teknik', 6, 2022, 'Edisi Lux', 'Indonesia', 450, 'BOSDA', 250000.00, 3, 3, 'default_book.png', 'Sejarah penemuan mobil, motor listrik, teknologi autonomous driving, dan keselamatan.', 'LIBRARY-BOOK:BK-00017', 'Tersedia'),
(18, 'BK-00018', '978-979-075-301-8', 'Kamus Istilah Teknik Mesin dan Informatika', 3, 6, 8, 'Kamus Vokasi', 6, 2021, 'Edisi 3', 'Indonesia', 380, 'BOSDA', 95000.00, 4, 4, 'default_book.png', 'Glosarium istilah teknik mesin, elektronik, bahasa coding, dan singkatan standar internasional.', 'LIBRARY-BOOK:BK-00018', 'Tersedia'),
(19, 'BK-00019', '978-602-441-112-6', 'Islam Moderat dan Akhlak Remaja Berbudi Luhur', 10, 4, 9, 'Pendidikan Karakter', 4, 2023, 'Cetakan 2', 'Indonesia', 215, 'Hibah/Sumbangan', 65000.00, 8, 8, 'default_book.png', 'Panduan moralitas, toleransi, etika bermedia sosial, dan penguatan karakter generasi muda.', 'LIBRARY-BOOK:BK-00019', 'Tersedia'),
(20, 'BK-00020', '978-602-03-2415-9', 'Sang Pemimpi (Dwilogi Laskar Pelangi)', 5, 4, 7, 'Novel Sastra', 5, 2022, 'Cetakan 38', 'Indonesia', 292, 'Hibah/Sumbangan', 79000.00, 5, 4, 'default_book.png', 'Perjalanan remaja dalam merajut mimpi hingga ke Sorbonne Paris dengan semangat juang tinggi.', 'LIBRARY-BOOK:BK-00020', 'Tersedia'),
(21, 'BK-00021', '978-623-01-0991-8', 'Kelistrikan Bodi dan Sistem EFI Sepeda Motor', 1, 1, 1, 'Injeksi Motor', 1, 2024, 'Cetakan 1', 'Indonesia', 250, 'BOS', 82000.00, 6, 6, 'default_book.png', 'Sistem sensor O2, throttle body, sensor CKP, dan diagnosis error kode MIL scanner OBD.', 'LIBRARY-BOOK:BK-00021', 'Tersedia'),
(22, 'BK-00022', '978-602-02-0992-5', 'Mikrokontroler Arduino dan Internet of Things (IoT)', 2, 1, 2, 'IoT & Embedded', 2, 2023, 'Edisi 2', 'Indonesia', 280, 'BOS', 96000.00, 8, 7, 'default_book.png', 'Interfacing sensor DHT22, ESP8266/ESP32, protokol MQTT, dan visualisasi dashboard IoT.', 'LIBRARY-BOOK:BK-00022', 'Tersedia'),
(23, 'BK-00023', '978-602-6232-88-2', 'Keamanan Sistem Informasi dan Ethical Hacking Dasar', 3, 3, 3, 'Cyber Security', 3, 2024, 'Cetakan 1', 'Indonesia', 310, 'BOS', 115000.00, 6, 6, 'default_book.png', 'Konsep CIA triad, uji penetrasi dasar, pengamanan web OWASP Top 10, dan kriptografi.', 'LIBRARY-BOOK:BK-00023', 'Tersedia'),
(24, 'BK-00024', '978-623-04-1234-5', 'Kimia Terapan untuk Vokasi Rekayasa & Manufaktur', 4, 2, 5, 'Kimia Teknik', 4, 2022, 'Cetakan 1', 'Indonesia', 210, 'BOS', 68000.00, 7, 7, 'default_book.png', 'Reaksi elektrokimia korosi, pencegahan karat, pelapisan logam, dan karakteristik oli pelumas.', 'LIBRARY-BOOK:BK-00024', 'Tersedia'),
(25, 'BK-00025', '978-602-298-990-2', 'Pendidikan Agama Islam dan Budi Pekerti Kelas XI', 8, 5, 4, 'PAI Vokasi', 4, 2023, 'Kurikulum Merdeka', 'Indonesia', 230, 'BOS', 59000.00, 15, 15, 'default_book.png', 'Fiqih muamalah kontemporer, etika profesi bekerja keras, dan sejarah peradaban Islam.', 'LIBRARY-BOOK:BK-00025', 'Tersedia'),
(26, 'BK-00026', '978-602-298-991-9', 'Sejarah Perjuangan Bangsa dan Wawasan Kepulauan NTB', 8, 5, 4, 'Sejarah Lokal', 4, 2023, 'Cetakan 1', 'Indonesia', 180, 'BOSDA', 52000.00, 10, 10, 'default_book.png', 'Khasanah sejarah Kesultanan Sumbawa, jejak maritim nusantara, dan nilai kepahlawanan lokal.', 'LIBRARY-BOOK:BK-00026', 'Tersedia'),
(27, 'BK-00027', '978-979-1227-89-6', 'Negeri 5 Menara', 5, 4, 7, 'Novel Inspiratif', 5, 2021, 'Cetakan 24', 'Indonesia', 423, 'Hibah/Sumbangan', 85000.00, 6, 6, 'default_book.png', 'Kisah persahabatan santri dengan tekad baja semboyan Man Jadda Wajada menuju pentas dunia.', 'LIBRARY-BOOK:BK-00027', 'Tersedia'),
(28, 'BK-00028', '978-623-01-0888-1', 'Perancangan Panel Kontrol Motor Listrik Industri', 2, 1, 2, 'Motor Listrik', 2, 2023, 'Edisi 1', 'Indonesia', 245, 'BOS', 88000.00, 5, 5, 'default_book.png', 'Rangkaian kendali Direct Online (DOL), Star-Delta, pengasutan inverter VFD, dan proteksi beban.', 'LIBRARY-BOOK:BK-00028', 'Tersedia'),
(29, 'BK-00029', '978-602-6232-90-5', 'Pengembangan Aplikasi Mobile Android dengan Kotlin', 9, 3, 3, 'Mobile Programming', 3, 2024, 'Cetakan 1', 'Indonesia', 350, 'BOS', 130000.00, 5, 5, 'default_book.png', 'Android Studio, Jetpack Compose, integrasi Room database, coroutine, dan arsitektur MVVM.', 'LIBRARY-BOOK:BK-00029', 'Tersedia'),
(30, 'BK-00030', '978-623-04-1999-0', 'Manajemen Bengkel dan Keselamatan Kerja (K3) Vokasi', 1, 2, 1, 'K3 & Manajemen Bengkel', 1, 2023, 'Cetakan 2', 'Indonesia', 195, 'BOS', 64000.00, 8, 8, 'default_book.png', 'Standar keselamatan bengkel 5R/5S, APD kerja mekanik, prosedur penanganan limbah B3 bengkel.', 'LIBRARY-BOOK:BK-00030', 'Tersedia');

-- --------------------------------------------------------
-- 11. Tabel Buku Eksemplar
-- Menggunakan QR Code format: LIBRARY-BOOK:{kode_eksemplar}
-- --------------------------------------------------------
DROP TABLE IF EXISTS `buku_eksemplar`;
CREATE TABLE `buku_eksemplar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `buku_id` INT NOT NULL,
  `kode_eksemplar` VARCHAR(40) NOT NULL UNIQUE,
  `nomor_barkod` VARCHAR(50) NOT NULL UNIQUE,
  `qr_token` VARCHAR(100) NOT NULL UNIQUE,
  `qr_image` VARCHAR(255) NULL,
  `kondisi` ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') NOT NULL DEFAULT 'Baik',
  `status` ENUM('Tersedia', 'Dipinjam', 'Perbaikan', 'Hilang') NOT NULL DEFAULT 'Tersedia',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_eksemplar_buku` (`buku_id`),
  INDEX `idx_eksemplar_qr` (`qr_token`),
  CONSTRAINT `fk_eksemplar_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eksemplar buku representatif untuk pelacakan transaksi fisik
INSERT INTO `buku_eksemplar` (`buku_id`, `kode_eksemplar`, `nomor_barkod`, `qr_token`, `kondisi`, `status`) VALUES
(1, 'BK-00001-01', 'BAR-BK-00001-01', 'LIBRARY-BOOK:BK-00001-01', 'Baik', 'Dipinjam'),
(1, 'BK-00001-02', 'BAR-BK-00001-02', 'LIBRARY-BOOK:BK-00001-02', 'Baik', 'Dipinjam'),
(1, 'BK-00001-03', 'BAR-BK-00001-03', 'LIBRARY-BOOK:BK-00001-03', 'Baik', 'Tersedia'),
(1, 'BK-00001-04', 'BAR-BK-00001-04', 'LIBRARY-BOOK:BK-00001-04', 'Baik', 'Tersedia'),
(2, 'BK-00002-01', 'BAR-BK-00002-01', 'LIBRARY-BOOK:BK-00002-01', 'Baik', 'Dipinjam'),
(2, 'BK-00002-02', 'BAR-BK-00002-02', 'LIBRARY-BOOK:BK-00002-02', 'Baik', 'Tersedia'),
(3, 'BK-00003-01', 'BAR-BK-00003-01', 'LIBRARY-BOOK:BK-00003-01', 'Baik', 'Dipinjam'),
(3, 'BK-00003-02', 'BAR-BK-00003-02', 'LIBRARY-BOOK:BK-00003-02', 'Baik', 'Tersedia'),
(5, 'BK-00005-01', 'BAR-BK-00005-01', 'LIBRARY-BOOK:BK-00005-01', 'Baik', 'Dipinjam'),
(5, 'BK-00005-02', 'BAR-BK-00005-02', 'LIBRARY-BOOK:BK-00005-02', 'Baik', 'Dipinjam'),
(5, 'BK-00005-03', 'BAR-BK-00005-03', 'LIBRARY-BOOK:BK-00005-03', 'Baik', 'Tersedia'),
(7, 'BK-00007-01', 'BAR-BK-00007-01', 'LIBRARY-BOOK:BK-00007-01', 'Baik', 'Dipinjam'),
(7, 'BK-00007-02', 'BAR-BK-00007-02', 'LIBRARY-BOOK:BK-00007-02', 'Baik', 'Dipinjam'),
(7, 'BK-00007-03', 'BAR-BK-00007-03', 'LIBRARY-BOOK:BK-00007-03', 'Baik', 'Tersedia'),
(9, 'BK-00009-01', 'BAR-BK-00009-01', 'LIBRARY-BOOK:BK-00009-01', 'Baik', 'Dipinjam'),
(9, 'BK-00009-02', 'BAR-BK-00009-02', 'LIBRARY-BOOK:BK-00009-02', 'Baik', 'Dipinjam'),
(9, 'BK-00009-03', 'BAR-BK-00009-03', 'LIBRARY-BOOK:BK-00009-03', 'Baik', 'Tersedia'),
(12, 'BK-00012-01', 'BAR-BK-00012-01', 'LIBRARY-BOOK:BK-00012-01', 'Baik', 'Dipinjam'),
(12, 'BK-00012-02', 'BAR-BK-00012-02', 'LIBRARY-BOOK:BK-00012-02', 'Baik', 'Tersedia'),
(13, 'BK-00013-01', 'BAR-BK-00013-01', 'LIBRARY-BOOK:BK-00013-01', 'Baik', 'Dipinjam'),
(13, 'BK-00013-02', 'BAR-BK-00013-02', 'LIBRARY-BOOK:BK-00013-02', 'Baik', 'Tersedia'),
(20, 'BK-00020-01', 'BAR-BK-00020-01', 'LIBRARY-BOOK:BK-00020-01', 'Baik', 'Dipinjam'),
(20, 'BK-00020-02', 'BAR-BK-00020-02', 'LIBRARY-BOOK:BK-00020-02', 'Baik', 'Tersedia'),
(22, 'BK-00022-01', 'BAR-BK-00022-01', 'LIBRARY-BOOK:BK-00022-01', 'Baik', 'Dipinjam'),
(22, 'BK-00022-02', 'BAR-BK-00022-02', 'LIBRARY-BOOK:BK-00022-02', 'Baik', 'Tersedia');

-- --------------------------------------------------------
-- 12. Tabel Peminjaman
-- Format Nomor Transaksi: PJM-YYYYMMDD-XXXX
-- --------------------------------------------------------
DROP TABLE IF EXISTS `peminjaman`;
CREATE TABLE `peminjaman` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_transaksi` VARCHAR(40) NOT NULL UNIQUE,
  `anggota_id` INT NOT NULL,
  `petugas_id` INT NOT NULL,
  `tanggal_pinjam` DATE NOT NULL,
  `tanggal_jatuh_tempo` DATE NOT NULL,
  `total_buku` INT NOT NULL DEFAULT 1,
  `status` ENUM('Dipinjam', 'Selesai', 'Terlambat') NOT NULL DEFAULT 'Dipinjam',
  `catatan` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_pinjam_anggota` (`anggota_id`),
  INDEX `idx_pinjam_petugas` (`petugas_id`),
  INDEX `idx_pinjam_status` (`status`),
  CONSTRAINT `fk_pinjam_anggota` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pinjam_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `peminjaman` (`id`, `nomor_transaksi`, `anggota_id`, `petugas_id`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `total_buku`, `status`, `catatan`) VALUES
(1, 'PJM-20260910-0001', 1, 2, '2026-09-10', '2026-09-17', 2, 'Terlambat', 'Peminjaman buku kejuruan TSM'),
(2, 'PJM-20260912-0002', 2, 2, '2026-09-12', '2026-09-19', 2, 'Dipinjam', 'Peminjaman modul RPL dan novel'),
(3, 'PJM-20260914-0003', 3, 3, '2026-09-14', '2026-09-21', 1, 'Dipinjam', 'Peminjaman kontrol PLC TOI'),
(4, 'PJM-20260901-0004', 5, 2, '2026-09-01', '2026-09-08', 2, 'Selesai', 'Dikembalikan tepat waktu'),
(5, 'PJM-20260902-0005', 6, 3, '2026-09-02', '2026-09-09', 1, 'Selesai', 'Pengembalian terlambat 2 hari denda lunas');

-- --------------------------------------------------------
-- 13. Tabel Peminjaman Detail
-- --------------------------------------------------------
DROP TABLE IF EXISTS `peminjaman_detail`;
CREATE TABLE `peminjaman_detail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `peminjaman_id` INT NOT NULL,
  `buku_id` INT NOT NULL,
  `eksemplar_id` INT NULL,
  `tanggal_kembali_aktual` DATE NULL,
  `status` ENUM('Dipinjam', 'Dikembalikan') NOT NULL DEFAULT 'Dipinjam',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pdetail_pinjam` (`peminjaman_id`),
  INDEX `idx_pdetail_buku` (`buku_id`),
  INDEX `idx_pdetail_eksemplar` (`eksemplar_id`),
  CONSTRAINT `fk_pdetail_pinjam` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pdetail_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pdetail_eksemplar` FOREIGN KEY (`eksemplar_id`) REFERENCES `buku_eksemplar` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `peminjaman_detail` (`id`, `peminjaman_id`, `buku_id`, `eksemplar_id`, `tanggal_kembali_aktual`, `status`) VALUES
(1, 1, 1, 1, NULL, 'Dipinjam'),
(2, 1, 2, 5, NULL, 'Dipinjam'),
(3, 2, 5, 9, NULL, 'Dipinjam'),
(4, 2, 9, 15, NULL, 'Dipinjam'),
(5, 3, 3, 7, NULL, 'Dipinjam'),
(6, 4, 1, NULL, '2026-09-08', 'Dikembalikan'),
(7, 4, 7, NULL, '2026-09-08', 'Dikembalikan'),
(8, 5, 5, NULL, '2026-09-11', 'Dikembalikan');

-- --------------------------------------------------------
-- 14. Tabel Pengembalian
-- Format Nomor: KMB-YYYYMMDD-XXXX
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pengembalian`;
CREATE TABLE `pengembalian` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_pengembalian` VARCHAR(40) NOT NULL UNIQUE,
  `peminjaman_id` INT NOT NULL,
  `petugas_id` INT NOT NULL,
  `tanggal_kembali` DATE NOT NULL,
  `terlambat_hari` INT NOT NULL DEFAULT 0,
  `total_denda` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `denda_dibayar` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status_denda` ENUM('Lunas', 'Belum Lunas', 'Tidak Ada') NOT NULL DEFAULT 'Tidak Ada',
  `catatan` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_kembali_pinjam` (`peminjaman_id`),
  INDEX `idx_kembali_petugas` (`petugas_id`),
  CONSTRAINT `fk_kembali_pinjam` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_kembali_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pengembalian` (`id`, `nomor_pengembalian`, `peminjaman_id`, `petugas_id`, `tanggal_kembali`, `terlambat_hari`, `total_denda`, `denda_dibayar`, `status_denda`, `catatan`) VALUES
(1, 'KMB-20260908-0001', 4, 2, '2026-09-08', 0, 0.00, 0.00, 'Tidak Ada', 'Buku kembali dalam kondisi prima'),
(2, 'KMB-20260911-0002', 5, 3, '2026-09-11', 2, 2000.00, 2000.00, 'Lunas', 'Terlambat 2 hari @ Rp1.000');

-- --------------------------------------------------------
-- 15. Tabel Pengembalian Detail
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pengembalian_detail`;
CREATE TABLE `pengembalian_detail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pengembalian_id` INT NOT NULL,
  `peminjaman_detail_id` INT NOT NULL,
  `buku_id` INT NOT NULL,
  `eksemplar_id` INT NULL,
  `kondisi_buku` ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') NOT NULL DEFAULT 'Baik',
  `denda_terlambat` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `denda_kondisi` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `subtotal_denda` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `catatan` VARCHAR(255) NULL,
  INDEX `idx_kdetail_kembali` (`pengembalian_id`),
  INDEX `idx_kdetail_buku` (`buku_id`),
  CONSTRAINT `fk_kdetail_kembali` FOREIGN KEY (`pengembalian_id`) REFERENCES `pengembalian` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_kdetail_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pengembalian_detail` (`id`, `pengembalian_id`, `peminjaman_detail_id`, `buku_id`, `kondisi_buku`, `denda_terlambat`, `denda_kondisi`, `subtotal_denda`, `catatan`) VALUES
(1, 1, 6, 1, 'Baik', 0.00, 0.00, 0.00, 'Kondisi baik'),
(2, 1, 7, 7, 'Baik', 0.00, 0.00, 0.00, 'Kondisi baik'),
(3, 2, 8, 5, 'Baik', 2000.00, 0.00, 2000.00, 'Kondisi fisik mulus');

-- --------------------------------------------------------
-- 16. Tabel Perpanjangan Peminjaman
-- --------------------------------------------------------
DROP TABLE IF EXISTS `perpanjangan`;
CREATE TABLE `perpanjangan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `peminjaman_id` INT NOT NULL,
  `petugas_id` INT NOT NULL,
  `tanggal_perpanjangan` DATE NOT NULL,
  `jatuh_tempo_lama` DATE NOT NULL,
  `jatuh_tempo_baru` DATE NOT NULL,
  `perpanjangan_ke` INT NOT NULL DEFAULT 1,
  `catatan` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_panjang_pinjam` (`peminjaman_id`),
  CONSTRAINT `fk_panjang_pinjam` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_panjang_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `perpanjangan` (`id`, `peminjaman_id`, `petugas_id`, `tanggal_perpanjangan`, `jatuh_tempo_lama`, `jatuh_tempo_baru`, `perpanjangan_ke`, `catatan`) VALUES
(1, 3, 2, '2026-09-17', '2026-09-21', '2026-09-28', 1, 'Perpanjangan 7 hari untuk keperluan tugas akhir praktikum');

-- --------------------------------------------------------
-- 17. Tabel Denda
-- --------------------------------------------------------
DROP TABLE IF EXISTS `denda`;
CREATE TABLE `denda` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pengembalian_id` INT NULL,
  `anggota_id` INT NOT NULL,
  `jenis_denda` ENUM('Keterlambatan', 'Kerusakan', 'Kehilangan') NOT NULL DEFAULT 'Keterlambatan',
  `jumlah_denda` DECIMAL(12,2) NOT NULL,
  `status_bayar` ENUM('Lunas', 'Belum Lunas') NOT NULL DEFAULT 'Belum Lunas',
  `tanggal_bayar` DATE NULL,
  `petugas_id` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_denda_anggota` (`anggota_id`),
  CONSTRAINT `fk_denda_anggota` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_denda_kembali` FOREIGN KEY (`pengembalian_id`) REFERENCES `pengembalian` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_denda_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `denda` (`id`, `pengembalian_id`, `anggota_id`, `jenis_denda`, `jumlah_denda`, `status_bayar`, `tanggal_bayar`, `petugas_id`) VALUES
(1, 2, 6, 'Keterlambatan', 2000.00, 'Lunas', '2026-09-11', 3);

-- --------------------------------------------------------
-- 18. Tabel Pengunjung (Buku Tamu Harian)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pengunjung`;
CREATE TABLE `pengunjung` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `identitas_no` VARCHAR(30) NULL,
  `nama` VARCHAR(100) NOT NULL,
  `jenis_pengunjung` ENUM('Siswa', 'Guru', 'Tenaga Kependidikan', 'Umum') NOT NULL DEFAULT 'Siswa',
  `kelas_id` INT NULL,
  `jurusan_id` INT NULL,
  `keperluan` VARCHAR(255) NOT NULL,
  `waktu_masuk` TIME NOT NULL,
  `waktu_keluar` TIME NULL,
  `tanggal` DATE NOT NULL,
  `keterangan` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pengunjung_tgl` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pengunjung` (`identitas_no`, `nama`, `jenis_pengunjung`, `kelas_id`, `jurusan_id`, `keperluan`, `waktu_masuk`, `waktu_keluar`, `tanggal`, `keterangan`) VALUES
('0061234501', 'Budi Santoso', 'Siswa', 1, 1, 'Membaca buku kelistrikan motor', '08:15:00', '09:00:00', '2026-09-17', 'Kunjungan jam istirahat'),
('0061234502', 'Siti Rahmawati', 'Siswa', 5, 4, 'Mengerjakan tugas web design', '09:30:00', '10:45:00', '2026-09-17', 'Menggunakan meja baca digital'),
('198501102010011005', 'Dedi Kurniawan, S.Pd', 'Guru', NULL, 1, 'Mencari referensi kurikulum merdeka', '10:00:00', '11:15:00', '2026-09-17', 'Guru produktif TSM'),
('0061234504', 'Rina Anggraini', 'Siswa', 4, 3, 'Meminjam buku fisika terapan', '11:00:00', '11:30:00', '2026-09-17', 'Keperluan tugas lab'),
('0061234503', 'Ahmad Dani Pratama', 'Siswa', 3, 2, 'Membaca majalah vokasi otomasi', '08:30:00', '09:15:00', '2026-09-18', 'Kunjungan pagi'),
('0061234506', 'Nabila Putri Azzahra', 'Siswa', 8, 4, 'Riset materi basis data', '09:00:00', '10:00:00', '2026-09-18', 'Literasi mandiri');

-- --------------------------------------------------------
-- 19. Tabel Jurnal Kegiatan Perpustakaan
-- --------------------------------------------------------
DROP TABLE IF EXISTS `jurnal`;
CREATE TABLE `jurnal` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal` DATE NOT NULL,
  `petugas_id` INT NOT NULL,
  `kegiatan` VARCHAR(255) NOT NULL,
  `jumlah_pengunjung` INT NOT NULL DEFAULT 0,
  `jumlah_peminjaman` INT NOT NULL DEFAULT 0,
  `jumlah_pengembalian` INT NOT NULL DEFAULT 0,
  `catatan` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_jurnal_tanggal` (`tanggal`),
  CONSTRAINT `fk_jurnal_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `jurnal` (`tanggal`, `petugas_id`, `kegiatan`, `jumlah_pengunjung`, `jumlah_peminjaman`, `jumlah_pengembalian`, `catatan`) VALUES
('2026-09-15', 2, 'Pelayanan sirkulasi rutin dan penataan rak buku otomotif', 38, 12, 8, 'Pembersihan rak buku dan pengecekan stiker QR code.'),
('2026-09-16', 3, 'Sosialisasi literasi digital kelas X RPL dan pelayanan peminjaman', 52, 18, 11, 'Kunjungan kelompok kelas dipandu oleh guru pembimbing.'),
('2026-09-17', 2, 'Inventarisasi berkala buku paket bantuan BOS & pelayanan peminjaman', 45, 14, 9, 'Semua alur sirkulasi berjalan tertib.');

-- --------------------------------------------------------
-- 20. Tabel Setting (Pengaturan Dinamis Sistem)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `setting`;
CREATE TABLE `setting` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kunci` VARCHAR(50) NOT NULL UNIQUE,
  `nilai` TEXT NOT NULL,
  `keterangan` VARCHAR(255) NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `setting` (`kunci`, `nilai`, `keterangan`) VALUES
('nama_sekolah', 'SMK Negeri 2 Sumbawa Besar', 'Nama resmi satuan pendidikan'),
('nama_perpustakaan', 'PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR', 'Nama unit perpustakaan sekolah'),
('npsn', '50203525', 'Nomor Pokok Sekolah Nasional'),
('alamat', 'Jl. Lingkar Luar No. 02, Sumbawa Besar, Nusa Tenggara Barat (NTB)', 'Alamat lengkap instansi'),
('email', 'perpustakaan@smkn2sumbawa.sch.id', 'Alamat email korespondensi'),
('nomor_telepon', '(0371) 21456 / 081234567890', 'Nomor telepon resmi'),
('kepala_sekolah', 'H. Khaeruddin, S.Pd., M.Pd.', 'Nama Kepala Sekolah beserta gelar'),
('nip_kepala_sekolah', '196808171994121003', 'NIP Kepala Sekolah'),
('kepala_perpustakaan', 'Drs. H. M. Nasir, M.Pd.', 'Nama Kepala Perpustakaan beserta gelar'),
('nip_kepala_perpustakaan', '197204151998031004', 'NIP Kepala Perpustakaan'),
('logo', 'logo_smkn2.png', 'File logo sekolah'),
('lama_pinjam_hari', '7', 'Masa peminjaman standar (hari)'),
('maksimal_buku_pinjam', '3', 'Batas maksimal judul/eksemplar dipinjam per anggota'),
('denda_per_hari', '1000', 'Tarif denda keterlambatan per hari per buku (Rupiah)'),
('denda_rusak_ringan', '15000', 'Tarif denda kondisi buku rusak ringan (Rupiah)'),
('denda_rusak_berat', '50000', 'Tarif denda kondisi buku rusak berat (Rupiah)'),
('denda_hilang', '100000', 'Tarif penggantian buku hilang (Rupiah)'),
('format_nomor_pinjam', 'PJM-{YYYY}{MM}{DD}-{XXXX}', 'Pola penomoran transaksi peminjaman'),
('format_nomor_kembali', 'KMB-{YYYY}{MM}{DD}-{XXXX}', 'Pola penomoran transaksi pengembalian'),
('hari_operasional', 'Senin - Jumat: 07.30 - 15.30 WITA', 'Jadwal operasional layanan perpustakaan');

-- --------------------------------------------------------
-- 21. Tabel Log Aktivitas (Audit Trail)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `log_aktivitas`;
CREATE TABLE `log_aktivitas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `modul` VARCHAR(50) NOT NULL,
  `aksi` VARCHAR(50) NOT NULL,
  `keterangan` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
  `user_agent` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_log_user` (`user_id`),
  INDEX `idx_log_waktu` (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `log_aktivitas` (`user_id`, `modul`, `aksi`, `keterangan`, `ip_address`) VALUES
(1, 'Autentikasi', 'Login', 'Administrator login ke dashboard sistem', '127.0.0.1'),
(1, 'Database', 'Inisialisasi', 'Database perpustakaan_db berhasil diinstalasi dengan data default', '127.0.0.1'),
(2, 'Peminjaman', 'Tambah', 'Petugas memproses transaksi peminjaman PJM-20260910-0001 anggota Budi Santoso', '127.0.0.1');

-- --------------------------------------------------------
-- 22. Tabel Scan Log (Log Pemindaian QR Code)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `scan_log`;
CREATE TABLE `scan_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `qr_type` ENUM('MEMBER', 'BOOK', 'UNKNOWN') NOT NULL,
  `qr_token` VARCHAR(100) NOT NULL,
  `scanned_by` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `result_info` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_scan_type` (`qr_type`),
  INDEX `idx_scan_waktu` (`created_at`),
  CONSTRAINT `fk_scan_user` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `scan_log` (`qr_type`, `qr_token`, `scanned_by`, `action`, `result_info`) VALUES
('MEMBER', 'LIBRARY-MEMBER:AGT-00001', 2, 'Identifikasi Scanner', 'Ditemukan: Budi Santoso (X TSM 1)'),
('BOOK', 'LIBRARY-BOOK:BK-00001', 2, 'Pengecekan Buku', 'Ditemukan: Teknologi Mesin Sepeda Motor');

SET FOREIGN_KEY_CHECKS = 1;
