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
│   ├── config/          # Database configuration
│   ├── controllers/     # Business logic
│   ├── models/          # Database models
│   └── middleware/      # Authentication middleware
├── api/
│   └── auth/            # Authentication endpoints
├── public/
│   ├── css/             # Stylesheets
│   └── js/              # JavaScript files
├── views/
│   ├── layouts/         # Header & footer
│   └── login.php        # Login page
├── index.php            # Entry point (Login)
└── dashboard.php        # Dashboard
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

## 📧 Support

For issues or questions, contact the development team.

---

**Version**: 1.0.0  
**Last Updated**: January 12, 2026
