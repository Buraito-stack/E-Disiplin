# E-Disiplin - Sistem Poin Pelanggaran Siswa

Sistem manajemen disiplin siswa berbasis web untuk mencatat pelanggaran, mengelola surat peringatan berjenjang (SP1/SP2/SP3/LE), dan menghasilkan dokumen surat secara otomatis.

## Fitur

- **Autentikasi & Otorisasi** - Session-based auth dengan CSRF protection, rate limiting, dan role-based access control
- **Role Management** - Admin, Guru, BK, Guru Mapel, Orang Tua, Siswa
- **Data Siswa** - CRUD data siswa lengkap dengan info orang tua
- **Pencatatan Pelanggaran** - CRUD pelanggaran dengan jenis, poin, dan keterangan
- **Surat Pelanggaran Berjenjang** - SP1, SP2, SP3, LE dengan auto-generate nomor surat
- **Surat Pernyataan Siswa** - Surat pernyataan dengan tanda tangan guru BK, wali kelas, dan wakasek
- **Surat Pindah** - Surat keterangan pindah sekolah
- **Daftar Pelanggaran** - Laporan pelanggaran dengan filter kelas dan rentang tanggal
- **Dashboard** - Statistik, grafik tren 7 hari, dan ringkasan pelanggaran terbaru
- **Dashboard Orang Tua** - Pantau pelanggaran dan surat anak (ownership-verified)
- **Dashboard Siswa** - Lihat pelanggaran dan surat pribadi
- **User Management** - CRUD user oleh admin
- **Reset Password** - Ganti password dengan verifikasi password lama
- **Print** - Cetak semua jenis surat dalam format print-friendly

## Struktur Project

```
E-Disiplin/
├── app/
│   ├── config/
│   │   ├── Database.php         # Koneksi database MySQLi
│   │   └── bootstrap.php        # Inisialisasi app, session, security headers
│   ├── controllers/
│   │   └── AuthController.php   # Login/logout logic
│   ├── helpers/
│   │   └── SecurityHelper.php   # CSRF, rate limiting, sanitasi input, logging
│   ├── middleware/
│   │   ├── AccessControl.php    # Verifikasi kepemilikan data & role access
│   │   ├── AuthMiddleware.php   # Inisialisasi session
│   │   └── RoleMiddleware.php   # Enforcement role requirement
│   ├── models/
│   │   └── User.php             # Model user (find, verify, create)
│   ├── templates/
│   │   └── SuratTemplate.php    # Template surat (SP, Pernyataan, Pindah, Daftar)
│   └── views/
│       ├── layouts/
│       │   ├── header.php       # Header & navigasi
│       │   └── footer.php       # Footer
│       └── login.php            # Halaman login
├── database/
│   └── seed_dummy.php           # Seeder data dummy untuk testing
├── public/                      # Web-accessible root
│   ├── index.php                # Login page / front controller
│   ├── dashboard.php            # Dashboard admin/staff (statistik & chart)
│   ├── pelanggaran.php          # CRUD pelanggaran (search, pagination)
│   ├── data_siswa.php           # CRUD data siswa
│   ├── user_management.php      # CRUD user (admin only)
│   ├── surat_pelanggaran.php    # Kelola surat pelanggaran (SP1-SP3, LE)
│   ├── surat_dokumen.php        # Hub surat pernyataan & surat pindah
│   ├── surat_print.php          # Print surat pelanggaran/pernyataan/pindah
│   ├── surat_pernyataan_print.php  # Print surat pernyataan siswa
│   ├── surat_pindah_print.php   # Print surat pindah by ID
│   ├── daftar_pelanggaran_print.php # Print laporan pelanggaran (filter)
│   ├── cek_surat.php            # Cek surat by NIS (ortu/siswa)
│   ├── siswa_dashboard.php      # Dashboard siswa
│   ├── ortu_dashboard.php       # Dashboard orang tua
│   ├── reset_password.php       # Ganti password
│   ├── endpoint/
│   │   └── auth/
│   │       ├── login.php        # JSON login endpoint (CSRF, rate-limited)
│   │       └── logout.php       # JSON logout endpoint
│   ├── css/                     # Stylesheets
│   ├── js/                      # JavaScript
│   └── images/                  # Gambar (kop_surat.png, dll)
├── .htaccess                    # Redirect ke public/
└── README.md
```

## Database Schema

| Tabel | Fungsi | Kolom Utama |
|-------|--------|-------------|
| **users** | Autentikasi user | id, username, password, email, name, role, is_active |
| **siswa** | Data siswa | id_siswa, nama, nis, kelas, alamat, nama_orang_tua, kontak_orang_tua, level_sp |
| **jenis_pelanggaran** | Jenis pelanggaran | id_jenis, nama_jenis, poin |
| **pelanggaran** | Catatan pelanggaran | id_pelanggaran, id_siswa, id_jenis, tanggal, keterangan |
| **surat_orang_tua** | Surat pelanggaran (SP) | id_surat_orang_tua, id_pelanggaran, level_sp, nomor_surat, status_kirim |
| **surat_perjanjian** | Surat pernyataan siswa | id_perjanjian, id_siswa, isi_perjanjian, nomor_surat, nama_guru_bk |
| **surat_pindah** | Surat pindah sekolah | id_surat_pindah, id_siswa, alasan_pindah, sekolah_tujuan |

## Role & Hak Akses

| Role | Akses |
|------|-------|
| **admin** | Semua fitur (user, siswa, pelanggaran, surat) |
| **guru** | Pelanggaran, surat, data siswa |
| **bk** | Pelanggaran, surat, data siswa |
| **guru_mapel** | Pelanggaran, surat, cek surat |
| **orangtua** | Dashboard orang tua (data anak saja) |
| **siswa** | Dashboard siswa (data sendiri saja) |

## Sistem Surat Pelanggaran Berjenjang

| Level | Nama | Deskripsi |
|-------|------|-----------|
| SP1 | Surat Pelanggaran Pertama | Pelanggaran Ringan - Notifikasi & pembinaan |
| SP2 | Surat Pelanggaran Kedua | Pelanggaran Sedang - Peringatan lebih tegas |
| SP3 | Surat Pelanggaran Ketiga | Pelanggaran Berat - Peringatan akhir |
| LE | Level Ekstensif | Tindakan Final - Rapat keputusan |

Flow: Pelanggaran Dicatat → Guru/BK Buat Surat (pilih level) → Auto-generate Nomor Surat → Update Level SP Siswa → Cetak Surat → Orang Tua Terima & Tanda Tangan

## Test Accounts

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Administrator |
| guru1 | admin123 | Guru/BK |
| guru2 | admin123 | Guru/BK |
| orangtua1 | admin123 | Orang Tua |
| siswa1 | admin123 | Siswa |

> Ubah password di production!

## Setup & Run

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Laragon / XAMPP / WAMP

### Steps

1. **Database Setup**
   ```bash
   mysql -u root < e-disiplin.sql
   mysql -u root < create_users_table.sql
   ```

2. **Konfigurasi**
   - Edit `app/config/Database.php` atau buat file `.env` di root:
     ```
     DB_HOST=localhost
     DB_NAME=e_disiplin
     DB_USER=root
     DB_PASS=
     APP_ENV=development
     APP_DEBUG=true
     ```

3. **Seed Data Dummy** (opsional)
   ```bash
   php database/seed_dummy.php
   ```

4. **Jalankan**
   - Akses via browser: `http://localhost/E-Disiplin`

> **Note:** Document root sebaiknya mengarah ke `public/`. Jika tidak bisa diubah, `.htaccess` akan forward request ke `public/` secara otomatis.

## Keamanan

- CSRF Protection pada semua form POST
- Prepared Statements (SQL injection prevention)
- Password hashing dengan bcrypt
- Rate limiting pada login (5 percobaan / 15 menit)
- Security headers (X-Frame-Options, CSP, X-XSS-Protection, dll)
- Input sanitization (htmlspecialchars, trim)
- Session security (HTTP-only cookies, SameSite strict)
- Access control & data ownership verification
- Security event logging

## Tech Stack

- **Backend**: Pure PHP (no framework), clean OOP
- **Database**: MySQL dengan MySQLi
- **Frontend**: Tailwind CSS (CDN), Vanilla JS
- **Auth**: Session-based

## Surat Templates & Autofill

### Jenis Surat
1. **Surat Pelanggaran** (SP1/SP2/SP3/LE) - via `surat_print.php`
2. **Surat Pernyataan Siswa** - via `surat_pernyataan_print.php` atau `surat_print.php?type=pernyataan`
3. **Surat Pindah** - via `surat_pindah_print.php?id=X`
4. **Daftar Pelanggaran** - via `daftar_pelanggaran_print.php` (filter kelas & tanggal)

### Autofill Parameters (Surat Pernyataan)
Query string keys: `nama`, `nis`, `kelas`, `program`, `masalah`, `nama_orang_tua`, `pekerjaan_orang_tua`, `alamat_orang_tua`, `kontak_orang_tua`, `nama_guru_bk`, `nama_guru_wali`, `nomor_surat`, `tanggal_cetak`

Contoh:
```
/surat_print.php?type=pernyataan&nama=Budi&nis=12345&kelas=11A&program=Software+Engineering&masalah=Telat+sekolah
```

---

**Version**: 1.1.0
**Last Updated**: March 31, 2026
