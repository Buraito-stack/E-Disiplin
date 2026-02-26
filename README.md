# E-Disiplin - Sistem Poin Pelanggaran Siswa

## 📋 Fitur

- ✅ Autentikasi user dengan session
- ✅ Role-based access (Admin, Guru, Orang Tua, Siswa)
- ✅ Dashboard sederhana
- 🔄 Menu Master, Transaksi, Laporan (coming soon)

## 🗂️ Struktur Project

```
E-Disiplin/
├── app/
│   ├── config/          # Configuration & helpers
│   ├── controllers/     # Business logic
│   ├── models/          # Database models
│   ├── middleware/      # Authorization/authentication
│   └── views/           # Blade-like templates & layouts
├── api/                # JSON endpoints (auth, etc.)
├── public/             # Web‑accessible root
│   ├── index.php        # Front controller (login page)
│   ├── *.php            # Other entrypoint scripts (dashboard, CRUD pages)
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── images/          # Static images like kop_surat.png
├── .htaccess           # Redirect all traffic into public/
└── README.md           # Project documentation
```

## 🔐 Test Accounts

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Administrator |
| guru1 | admin123 | Guru/BK |
| guru2 | admin123 | Guru/BK |
| orangtua1 | admin123 | Orang Tua |
| siswa1 | admin123 | Siswa |

Ubah password di production

## 🚀 Setup & Run

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Laragon / XAMPP / WAMP

### Steps

1. **Database Setup**
   ```bash
   # Create database & tables
   mysql -u root < e-disiplin.sql
   
   # Create users table
   mysql -u root < create_users_table.sql
   ```

2. **Configure**
   - Edit `app/config/Database.php` jika ada perubahan DB credentials

3. **Run**
   - Akses via browser: `http://localhost/E-Disiplin`

## 📝 Code Standards

- **Pure PHP** - No framework, clean OOP structure
- **Prepared Statements** - Security against SQL injection
- **Session-based Auth** - Simple & secure
- **Tailwind CSS** - No frameworks, just utility classes
- **Vanilla JS** - No jQuery, clean modern JavaScript

## 🔧 Development Notes

> **Note:** The web server **document root** should point to the `public/` directory.  If you cannot change the host configuration you can leave the root the same; `.htaccess` will transparently forward requests into `public/`.



### Adding New Features

1. **New Database Model**
   ```php
   class ModelName {
       private $conn;
       private $table = 'table_name';
       
       public function __construct($db) {
           $this->conn = $db;
       }
   }
   ```

2. **New Controller**
   ```php
   class ControllerName {
       private $model;
       
       public function __construct($model) {
           $this->model = $model;
       }
   }
   ```

3. **New API Endpoint**
   - Create file in `api/` folder
   - Use JSON responses
   - Include proper error handling

## 📊 Database Schema

### Core Tables
- **users** - Authentication & user data
- **siswa** - Student information
- **jenis_pelanggaran** - Violation types
- **pelanggaran** - Violation records
- **surat_perjanjian** - Agreement letters
- **surat_pindah** - Transfer letters
- **surat_orang_tua** - Parent notification letters

## 🎯 Next Steps

- [ ] Master Data Management
- [ ] Violation Entry & Management
- [ ] Letter Generation
- [ ] Reporting Module
- [ ] Point Deduction System
- [ ] User Management

## ✉️ Letter Templates & Autofill

There are two distinct output documents handled by the system:

1. **Surat Pelanggaran** – used by the school when issuing an SP1/SP2/SP3/LE warning. Data comes from the `surat_orang_tua` table and is printed via `surat_print.php` (default). The `?type=sp` query parameter is implicit.
2. **Surat Pernyataan Siswa** – a separate form that can be generated for student statements. This is available via `surat_pernyataan_print.php` or by adding `type=pernyataan` to `surat_print.php`. Fields are supplied via GET parameters for autofill.

### Autofill variables

When generating a **Surat Pernyataan** the following query string keys may be provided (all optional but recommended):

- `nama`, `nis`, `kelas`, `program` (keahlian)
- `masalah` (penjelasan detail)
- `nama_orang_tua`, `pekerjaan_orang_tua`, `alamat_orang_tua`, `kontak_orang_tua`
- `nama_guru_bk`, `nama_guru_wali` (ditambah blok tanda tangan)
- `nomor_surat`, `tanggal_cetak`

> _Tip_: The prompt for an AI autofill engine should explicitly request these values and make clear whether a pernyataan or pelanggaran letter is required so the correct template is used.

Example URL:
```
/surat_print.php?type=pernyataan&nama=Budi&nis=12345&kelas=11A&program=Software+Engineering&masalah=Telat+sekolah&nama_orang_tua=Ibu+Ani&pekerjaan_orang_tua=Pegawai+Negeri&kontak_orang_tua=08123456789
```

This ensures program keahlian, pekerjaan dan nomor telepon dimasukkan, serta bagian tanda tangan mencakup Guru BK, Wali Kelas, dan Wakasek sesuai format sekolah.

## 📧 Support

For issues or questions, contact the development team.

---

**Version**: 1.0.0  
**Last Updated**: January 12, 2026
