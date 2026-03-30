# 📋 SISTEM SURAT PELANGGARAN BERJENJANG (SP1, SP2, SP3, LE)

> **Catatan Struktur:** semua halaman PHP pindah ke `public/` dan `views/` sekarang berada di `app/views/`; gunakan `.htaccess` untuk mengarahkan ke public root.


## 🎯 Update yang Dilakukan

### 1. **Database Migration** 
File: `database_migration_SP.sql`

Perubahan tabel yang diperlukan:
```sql
-- Tambah kolom ke tabel siswa
ALTER TABLE siswa ADD COLUMN level_sp INT DEFAULT 0;
ALTER TABLE siswa ADD COLUMN tanggal_sp_terakhir DATETIME DEFAULT NULL;

-- Tambah kolom ke tabel surat_orang_tua
ALTER TABLE surat_orang_tua ADD COLUMN level_sp VARCHAR(10) DEFAULT 'SP1';
ALTER TABLE surat_orang_tua ADD COLUMN nomor_surat VARCHAR(50) DEFAULT NULL;
ALTER TABLE surat_orang_tua ADD COLUMN tanda_tangan_orang_tua DATETIME DEFAULT NULL;
ALTER TABLE surat_orang_tua ADD COLUMN tanda_tangan_guru DATETIME DEFAULT NULL;

-- Buat tabel kategori pelanggaran
CREATE TABLE pelanggaran_kategori (
    id_kategori INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT
);

-- Buat tabel history SP
CREATE TABLE history_sp (
    id_history INT PRIMARY KEY AUTO_INCREMENT,
    id_siswa INT NOT NULL,
    level_sp_sebelum VARCHAR(10),
    level_sp_sesudah VARCHAR(10) NOT NULL,
    id_surat_orang_tua INT,
    tanggal_update DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### 2. **File Template Baru**
File: `app/templates/SuratTemplate.php`

Template surat dengan 4 level:
- **SP1** - Pelanggaran Ringan (Surat Pelanggaran Pertama)
- **SP2** - Pelanggaran Sedang (Surat Pelanggaran Kedua)
- **SP3** - Pelanggaran Berat (Surat Pelanggaran Ketiga)
- **LE** - Level Ekstensif (Tindakan Final)

Setiap level memiliki isi surat yang berbeda dan disesuaikan dengan tingkat keseriusan.

### 3. **Update Halaman Surat Pelanggaran**
File: `surat_pelanggaran.php`

Fitur baru:
- ✅ Dropdown untuk memilih level SP (SP1, SP2, SP3, LE)
- ✅ Auto-generate nomor surat: `TGL/LEVEL/BULAN/TAHUN`
- ✅ Update status siswa ke `level_sp` setiap membuat surat
- ✅ Tabel menampilkan kolom Level SP dengan color-coding:
  - SP1: Biru (Ringan)
  - SP2: Kuning (Sedang)
  - SP3: Orange (Berat)
  - LE: Merah (Ekstensif)

### 4. **Update Halaman Print Surat**
File: `surat_print.php`

- Menggunakan template dari `SuratTemplate.php`
- Surat otomatis disesuaikan berdasarkan `level_sp`
- Tampilan profesional dengan header sekolah
- Footer untuk tanda tangan (Wali Kelas, Kepala Sekolah, Orang Tua)

### 5. **Update Dashboard Orang Tua**
File: `ortu_dashboard.php`

- ✅ Tampilkan kolom Level SP di tabel surat
- ✅ Color-coding untuk setiap level SP
- ✅ Isi section data surat dengan level yang sesuai

### 6. **Update Dashboard Siswa**
File: `siswa_dashboard.php`

- ✅ Tampilkan kolom Level SP di bagian Surat Pelanggaran
- ✅ Color-coding untuk setiap level
- ✅ Informasi lebih jelas tentang status surat yang diterima

---

## 🔄 Flow Sistem SP

```
Pelanggaran Dicatat
        ↓
Guru/BK Buat Surat → Pilih Level SP (SP1/SP2/SP3/LE)
        ↓
Sistem Generate Nomor Surat (TGL/LEVEL/BULAN/TAHUN)
        ↓
Update Level SP Siswa (0→1→2→3→4)
        ↓
Surat Tercetak dengan Template Sesuai Level
        ↓
Orang Tua Terima & Tanda Tangan
        ↓
Update Status: "Belum Dikirim" → "Terkirim"
```

---

## 📊 Struktur Level SP

| Level | Nama | Deskripsi | Kondisi |
|-------|------|-----------|---------|
| 0 | Belum Ada SP | Siswa belum mendapat surat pelanggaran | - |
| 1 | SP1 | Surat Pelanggaran Pertama | Pelanggaran Ringan |
| 2 | SP2 | Surat Pelanggaran Kedua | Pelanggaran Sedang |
| 3 | SP3 | Surat Pelanggaran Ketiga | Pelanggaran Berat |
| 4 | LE | Level Ekstensif | Pelanggaran Ekstensif (Tindakan Final) |

---

## 🎨 Color Coding di UI

- **SP1** - `bg-blue-100 text-blue-800` (Ringan)
- **SP2** - `bg-yellow-100 text-yellow-800` (Sedang)  
- **SP3** - `bg-orange-100 text-orange-800` (Berat)
- **LE** - `bg-red-100 text-red-800` (Ekstensif)

---

## 📝 Isi Surat untuk Setiap Level

### SP1 - Surat Pelanggaran Pertama
- Notifikasi awal untuk melakukan pembinaan
- Tone: Informatif dan edukatif
- Ekspektasi: Perbaikan dengan arahan dari orang tua

### SP2 - Surat Pelanggaran Kedua  
- Peringatan karena mengulangi pelanggaran
- Tone: Formal dan lebih tegas
- Ekspektasi: Tindakan pembinaan lebih serius dari orang tua

### SP3 - Surat Pelanggaran Ketiga
- Peringatan akhir sebelum tindakan definitif
- Tone: Sangat formal dan severe
- Ekspektasi: Komitmen perbaikan dari orang tua & siswa

### LE - Level Ekstensif
- Notifikasi tindakan final/ekspulsi
- Tone: Resmi dan binding
- Ekspektasi: Rapat dengan kedua belah pihak untuk keputusan final

---

### 7. **Surat Dokumen (Pernyataan & Pindah)**
File: `surat_dokumen.php`

Fitur baru:
- ✅ Hub untuk membuat Surat Pernyataan Siswa dan Surat Pindah
- ✅ Tab navigation antara jenis surat
- ✅ Auto-generate nomor surat
- ✅ CRUD lengkap untuk kedua jenis surat

### 8. **Print Surat Pindah**
File: `surat_pindah_print.php`

- Print surat pindah berdasarkan ID
- Template dengan data siswa, sekolah tujuan, dan alasan pindah
- Format print-friendly

### 9. **Daftar Pelanggaran Print**
File: `daftar_pelanggaran_print.php`

- Laporan pelanggaran dengan filter kelas dan rentang tanggal
- Mode preview dan print
- Kalkulasi total poin

### 10. **Database Seeder**
File: `database/seed_dummy.php`

- Seeder data dummy untuk testing
- Membuat data siswa, pelanggaran, dan surat contoh

---

## ✅ Checklist Implementasi

- [x] Update database schema
- [x] Create template surat berjenjang
- [x] Update halaman surat pelanggaran
- [x] Update halaman print surat
- [x] Update dashboard orang tua
- [x] Update dashboard siswa
- [x] Auto-generate nomor surat
- [x] Color-coding per level
- [x] Update siswa level_sp field
- [x] Surat Pernyataan Siswa
- [x] Surat Pindah Sekolah
- [x] Laporan Daftar Pelanggaran (filter & print)
- [x] Database seeder untuk dummy data
- [x] Hub dokumen surat (surat_dokumen.php)

---

## 🚀 Langkah Implementasi

1. **Jalankan SQL Migration:**
   - Buka file `database_migration_SP.sql`
   - Execute di database MySQL Anda

2. **Seed Data Dummy** (opsional):
   ```bash
   php database/seed_dummy.php
   ```

3. **Test Sistem:**
   - Login sebagai Guru/BK
   - Pergi ke menu Surat Pelanggaran → buat surat SP
   - Pergi ke Surat Dokumen → buat surat pernyataan / pindah
   - Pergi ke Daftar Pelanggaran → filter dan print laporan
   - Lihat hasil print dengan template yang sesuai

4. **Verifikasi di Dashboard:**
   - Cek di Dashboard Orang Tua → lihat kolom Level SP
   - Cek di Dashboard Siswa → lihat kolom Level SP
   - Verifikasi color-coding muncul dengan benar

---

## 📌 Notes

- Nomor surat otomatis di-generate berdasarkan: Tanggal/LevelAngka/Bulan/Tahun
- Setiap surat dibuat, siswa `level_sp` otomatis terupdate
- Orang tua & Siswa bisa melihat historical surat dengan level progressif
- Template surat print bisa langsung di-print atau save as PDF
- Surat Pernyataan dan Surat Pindah dikelola via `surat_dokumen.php`
- Laporan pelanggaran bisa difilter per kelas dan rentang tanggal

