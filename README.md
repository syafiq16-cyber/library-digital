# 📚 PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR

> Sistem Informasi Perpustakaan Sekolah Modern — Berbasis PHP 8.x + MySQL + Bootstrap 5
> Dijalankan sepenuhnya **offline** di server lokal XAMPP / Apache

---

## 🖥️ Persyaratan Sistem

| Komponen | Versi Minimum |
|----------|---------------|
| PHP | 8.0+ (Recommended: 8.2+) |
| MySQL / MariaDB | 5.7+ / 10.4+ |
| Apache | 2.4+ |
| XAMPP | 8.2+ |
| Browser | Chrome, Firefox, Edge (modern) |
| RAM | 512 MB (min) |
| Storage | 500 MB (min) |

**Ekstensi PHP yang diperlukan:** `pdo_mysql`, `gd`, `mbstring`, `fileinfo`

---

## ⚙️ Instalasi di XAMPP (Windows)

### Langkah 1: Letakkan Folder Aplikasi

```
Salin folder "library-digital" ke:
C:\xampp\htdocs\perpustakaan
```

Pastikan struktur folder menjadi:
```
C:\xampp\htdocs\perpustakaan\
├── index.php
├── login.php
├── config/
├── models/
├── controllers/
├── views/
├── assets/
├── uploads/
└── database/
```

### Langkah 2: Aktifkan XAMPP

1. Buka **XAMPP Control Panel**
2. Klik **Start** pada **Apache**
3. Klik **Start** pada **MySQL**

### Langkah 3: Import Database

1. Buka browser → akses **http://localhost/phpmyadmin**
2. Klik **New** di panel kiri → buat database bernama: `perpustakaan_db`
3. Klik tab **Import** → **Choose File**
4. Pilih file: `C:\xampp\htdocs\perpustakaan\database\perpustakaan.sql`
5. Klik **Go** → Tunggu sampai proses selesai

### Langkah 4: Konfigurasi Database (jika perlu)

Buka file `config/database.php` dan sesuaikan jika diperlukan:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'perpustakaan_db');
define('DB_USER', 'root');       // Username MySQL XAMPP
define('DB_PASS', '');           // Password MySQL XAMPP (default kosong)
```

### Langkah 5: Akses Aplikasi

Buka browser → **http://localhost/perpustakaan**

---

## ⚙️ Instalasi di macOS (Homebrew/Terminal)

```bash
# Clone atau letakkan project di folder htdocs Homebrew
cp -r library-digital /opt/homebrew/var/www/perpustakaan

# Jalankan MySQL
brew services start mysql

# Import database
mysql -u root < /path/to/library-digital/database/perpustakaan.sql

# Jalankan PHP Built-in Server (alternatif Apache)
cd /path/to/library-digital
php -S 127.0.0.1:8000

# Akses: http://127.0.0.1:8000
```

---

## 🔐 Akun Default

| Username | Password | Role | Keterangan |
|----------|----------|------|------------|
| `admin` | `password123` | Administrator | Akses penuh seluruh sistem |
| `kepala` | `password123` | Kepala Perpustakaan | Laporan & monitoring |
| `petugas1` | `password123` | Petugas | Transaksi peminjaman/pengembalian |
| `petugas2` | `password123` | Petugas | Transaksi peminjaman/pengembalian |
| `siswa1` | `password123` | Anggota | Portal siswa & katalog |

> ⚠️ **Segera ganti password default** setelah instalasi melalui menu Pengaturan!

---

## 🏗️ Struktur Direktori

```
perpustakaan/
├── index.php                  # Front-controller & router utama
├── login.php                  # Halaman login
├── logout.php                 # Logout & hapus sesi
│
├── config/
│   ├── config.php             # Konfigurasi utama & helper functions
│   ├── database.php           # Koneksi PDO MySQL
│   ├── auth.php               # RBAC, CSRF, session management
│   └── qrcode_helper.php      # Generate & validasi QR Code
│
├── models/
│   ├── User.php               # Model pengguna & autentikasi
│   ├── Anggota.php            # Model anggota perpustakaan
│   ├── Buku.php               # Model buku & eksemplar fisik
│   ├── Master.php             # Kategori, pengarang, penerbit, rak, kelas
│   ├── Peminjaman.php         # Transaksi peminjaman (multi-buku)
│   ├── Pengembalian.php       # Transaksi pengembalian & denda
│   ├── Pengunjung.php         # Buku tamu pengunjung perpustakaan
│   ├── Jurnal.php             # Jurnal harian petugas
│   ├── Laporan.php            # 12 jenis laporan & KPI dashboard
│   └── LogAktivitas.php       # Audit trail & scan log QR
│
├── controllers/
│   ├── BukuController.php     # CRUD buku & upload cover
│   ├── AnggotaController.php  # CRUD anggota & upload foto
│   ├── TransaksiController.php# Peminjaman, pengembalian, perpanjangan
│   ├── SettingController.php  # Pengaturan sistem & upload logo
│   └── BackupController.php   # Backup & restore database .sql
│
├── api/
│   ├── scan_handler.php       # API validasi QR Code (member & buku)
│   ├── search_global.php      # API pencarian global (topbar)
│   ├── search_buku.php        # API pencarian buku + status stok
│   └── search_anggota.php     # API pencarian anggota + kuota pinjam
│
├── views/
│   ├── layouts/               # Header, navbar, sidebar, footer
│   ├── dashboard/             # Dashboard KPI + chart
│   ├── scanner/               # Kamera QR scanner
│   ├── buku/                  # CRUD buku
│   ├── anggota/               # CRUD anggota + cetak kartu
│   ├── peminjaman/            # Daftar, buat, detail, cetak
│   ├── pengembalian/          # Proses kembali + denda
│   ├── master/                # Kategori, pengarang, penerbit, rak, dll
│   ├── katalog/               # Katalog buku publik
│   ├── label/                 # Cetak label punggung + QR buku
│   ├── import/                # Import massal CSV buku & anggota
│   ├── laporan/               # 12 laporan + cetak + export CSV
│   ├── users/                 # Manajemen user sistem
│   ├── log/                   # Audit trail aktivitas
│   ├── backup/                # Backup & restore database
│   ├── setting/               # Pengaturan sistem
│   ├── pengunjung/            # Buku tamu pengunjung
│   └── jurnal/                # Jurnal harian perpustakaan
│
├── assets/
│   ├── css/
│   │   ├── style.css          # Desain utama (light/dark mode)
│   │   └── print.css          # Stylesheet cetak kartu & laporan
│   ├── js/
│   │   ├── app.js             # Tema toggle, sidebar, QR generator
│   │   └── chart-custom.js    # Inisialisasi Chart.js
│   ├── images/                # Logo, avatar, cover default
│   └── vendor/                # Bootstrap, Chart.js, QRCode.js (OFFLINE)
│
├── uploads/
│   ├── covers/                # Cover buku upload
│   ├── anggota/               # Foto anggota upload
│   └── backups/               # File backup .sql
│
└── database/
    └── perpustakaan.sql       # Script SQL lengkap (DDL + DML dummy data)
```

---

## 📱 Sistem Dua QR Code

Aplikasi menggunakan **dua jenis QR Code yang berbeda** dengan validasi silang ketat:

### A. QR Code Anggota
- **Format:** `LIBRARY-MEMBER:{nomor_anggota}`
- **Contoh:** `LIBRARY-MEMBER:AGT-00001`
- **Fungsi:** Identifikasi anggota saat peminjaman, pengembalian, dan melihat profil
- **Generate:** Otomatis saat anggota didaftarkan, bisa di-regenerate & dicetak sebagai kartu ID

### B. QR Code Buku
- **Format:** `LIBRARY-BOOK:{kode_buku}` atau `LIBRARY-BOOK:{kode_eksemplar}`
- **Contoh:** `LIBRARY-BOOK:BK-00001` atau `LIBRARY-BOOK:BK-00001-01`
- **Fungsi:** Identifikasi buku/eksemplar saat peminjaman, pengembalian, dan cetak label
- **Generate:** Otomatis saat buku didaftarkan (per judul & per eksemplar fisik)

### Validasi Silang
Jika petugas scan QR **buku** saat sistem menunggu QR **anggota** → sistem memperingatkan.
Jika petugas scan QR **anggota** saat sistem menunggu QR **buku** → sistem memperingatkan.

---

## 🔄 Alur Peminjaman Buku

```
1. Petugas buka menu Peminjaman → Buat Peminjaman Baru
2. SCAN QR Code ANGGOTA (atau ketik nomor anggota manual)
   → Sistem menampilkan data & kuota pinjam anggota
3. SCAN QR Code BUKU (bisa scan beberapa buku sekaligus)
   → Sistem menampilkan detail buku & cek stok
4. Klik "Proses Peminjaman"
   → Stok buku otomatis berkurang
   → QR Code eksemplar ditandai "Dipinjam"
   → Bukti peminjaman bisa dicetak
```

## 🔄 Alur Pengembalian Buku

```
1. Petugas buka menu Pengembalian → Proses Pengembalian
2. Cari transaksi peminjaman (nomor transaksi / nama anggota)
3. Centang buku yang dikembalikan & isi kondisi fisik
   → Sistem otomatis hitung denda jika terlambat
   → Tambahkan denda kerusakan/kehilangan jika ada
4. Klik "Proses Pengembalian"
   → Stok buku otomatis bertambah (kecuali buku hilang)
   → Denda tersimpan di database
   → Bukti pengembalian bisa dicetak
```

---

## 📊 Fitur Laporan (12 Jenis)

| No | Jenis Laporan | Filter Tersedia |
|----|---------------|-----------------|
| 1 | Data Buku | Kategori, Rak, Tahun |
| 2 | Data Anggota | Jenis, Kelas, Jurusan |
| 3 | Transaksi Peminjaman | Tanggal, Status |
| 4 | Transaksi Pengembalian | Tanggal, Status Denda |
| 5 | Keterlambatan | — |
| 6 | Denda | Tanggal, Status Bayar |
| 7 | Buku Rusak | — |
| 8 | Buku Hilang | — |
| 9 | Pengunjung | Tanggal, Jenis |
| 10 | Buku Terpopuler | — |
| 11 | Anggota Paling Aktif | — |
| 12 | Ringkasan Statistik | — |

Semua laporan bisa **dicetak** (dengan kop surat resmi) dan **diekspor ke CSV/Excel**.

---

## 💾 Backup & Restore Database

### Membuat Backup
1. Login sebagai **Administrator**
2. Menu → **Backup & Restore**
3. Klik **"Download Backup Sekarang"** → file `.sql` otomatis terunduh
4. Atau klik **"Simpan ke Server"** → tersimpan di `uploads/backups/`

### Restore Database
> ⚠️ **PERINGATAN:** Restore akan **menghapus seluruh data** saat ini!

1. Buat backup data terbaru terlebih dahulu
2. Menu → **Backup & Restore** → bagian **Restore Database**
3. Pilih file `.sql` → klik **"Pulihkan Database Sekarang"**
4. Konfirmasi → tunggu proses selesai
5. Login ulang menggunakan akun yang ada di file backup

---

## 🎨 Fitur Utama

- ✅ **Dashboard Real-time** — KPI, grafik 12 bulan, buku terpopuler, alert keterlambatan
- ✅ **Scanner QR Code** — Kamera depan/belakang, validasi silang, fallback manual
- ✅ **Manajemen Buku** — CRUD lengkap, upload cover, eksemplar fisik otomatis
- ✅ **Manajemen Anggota** — CRUD, foto, cetak kartu ID (85.6×54mm)
- ✅ **Peminjaman Multi-Buku** — Hingga N buku/transaksi (konfigurasi setting)
- ✅ **Pengembalian & Denda** — Hitung otomatis, assessment kondisi fisik
- ✅ **Perpanjangan Pinjam** — Extend jatuh tempo dari detail transaksi
- ✅ **Cetak Label Buku** — Label punggung 55×38mm dengan QR & call number
- ✅ **Import Massal CSV** — Import buku & anggota dengan preview & duplicate detection
- ✅ **12 Laporan Komprehensif** — Print resmi + Export CSV/Excel
- ✅ **Dark / Light Mode** — Tema persisten via localStorage
- ✅ **Buku Tamu Pengunjung** — Logbook digital harian
- ✅ **Jurnal Harian Petugas** — Catatan kegiatan perpustakaan
- ✅ **RBAC 4 Role** — Administrator, Kepala Perpustakaan, Petugas, Anggota
- ✅ **Backup & Restore** — Database .sql dump langsung dari browser
- ✅ **100% Offline** — Semua library (Bootstrap, Chart.js, QRCode.js) bundled lokal

---

## 🔧 Troubleshooting

### Error: "Could not connect to database"
- Pastikan MySQL/MariaDB sudah aktif di XAMPP
- Periksa konfigurasi di `config/database.php` (host, port, user, password, nama database)
- Pastikan database `perpustakaan_db` sudah dibuat dan SQL sudah di-import

### Error: "Session not started"
- Pastikan PHP `session.save_path` bisa ditulis
- Cek `php.ini` untuk `session.save_handler = files`

### Gambar tidak muncul / Folder upload error
- Pastikan folder `uploads/covers/`, `uploads/anggota/`, `uploads/backups/` bisa ditulis (writable)
- Di Linux/macOS: `chmod -R 775 uploads/`
- Di Windows: Klik kanan folder → Properties → Security → izinkan Write

### QR Scanner tidak bisa akses kamera
- Fitur kamera memerlukan **HTTPS** atau **localhost**
- Saat diakses via IP LAN (misal: `192.168.x.x`), browser memblokir kamera karena non-HTTPS
- Solusi: Gunakan `http://localhost/...` dari komputer server, atau setup SSL

### Halaman 404 / Redirect loop
- Pastikan URL diakses sesuai nama folder di htdocs (misal: `http://localhost/perpustakaan/`)
- Cek `BASE_URL` yang terdeteksi otomatis di `config/config.php`

---

## 📋 Konfigurasi yang Dapat Diubah

Semua konfigurasi di bawah dapat diubah melalui menu **Pengaturan Sistem** (tanpa edit file):

- Nama sekolah & perpustakaan
- NPSN, alamat, email, telepon
- Nama kepala sekolah & kepala perpustakaan (untuk kop surat)
- Logo sekolah
- Lama pinjam standar (hari)
- Maksimal buku per peminjaman
- Tarif denda keterlambatan (Rp/hari)
- Tarif denda kerusakan ringan & berat
- Tarif penggantian buku hilang
- Hari & jam operasional perpustakaan

---

## 📞 Informasi Pengembang

**Sistem Informasi Perpustakaan Digital**
SMK Negeri 2 Sumbawa Besar
Jl. Lingkar Luar No. 02, Sumbawa Besar, NTB

*Dibangun dengan PHP 8.x + MySQL + Bootstrap 5 + Chart.js + HTML5-QRCode*
*Mendukung operasional offline penuh — tidak memerlukan koneksi internet*
