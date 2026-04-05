<?php

require_once __DIR__ . '/../app/config/bootstrap.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

$authController = new AuthController(null);

if (!$authController->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION;
$role = $user['role'] ?? '';

$title = 'Dokumentasi - E-Disiplin';
include __DIR__ . '/../app/views/layouts/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-full flex items-center justify-center">
                        <span class="text-white font-bold">E</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">E-Disiplin</h1>
                        <p class="text-xs text-gray-500">Dokumentasi & Panduan</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <?php
                        if ($role === 'orangtua') $backUrl = 'ortu_dashboard.php';
                        elseif ($role === 'siswa') $backUrl = 'siswa_dashboard.php';
                        else $backUrl = 'dashboard.php';
                    ?>
                    <a href="<?php echo $backUrl; ?>" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Kembali">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <button onclick="logout()" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Logout">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-4 py-8 dock-safe">

        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Panduan Penggunaan</h2>
            <p class="text-gray-500 mt-1">Panduan lengkap sesuai peran Anda: <span class="font-semibold capitalize text-blue-600"><?php echo htmlspecialchars($role); ?></span></p>
        </div>

        <!-- Panduan Umum (semua role) -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Tentang Sistem</h3>
            </div>
            <p class="text-sm text-gray-600 leading-relaxed">
                E-Disiplin adalah sistem pencatatan pelanggaran dan poin disiplin siswa. Sistem ini mencatat setiap pelanggaran,
                menghitung poin kumulatif, dan menghasilkan surat peringatan berjenjang (SP1 &rarr; SP2 &rarr; SP3 &rarr; LE)
                yang dikirim ke orang tua.
            </p>
        </div>

        <!-- Panduan Navigasi -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Navigasi</h3>
            </div>
            <ul class="text-sm text-gray-600 space-y-2">
                <li class="flex items-start gap-2">
                    <span class="text-gray-400 mt-0.5">&#8226;</span>
                    <span>Gunakan <strong>sidebar kiri</strong> (dock) untuk berpindah antar halaman.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-gray-400 mt-0.5">&#8226;</span>
                    <span>Klik <strong>foto profil</strong> di atas sidebar untuk masuk ke Pengaturan Akun (ganti nama, password).</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-gray-400 mt-0.5">&#8226;</span>
                    <span>Tombol <strong>logout</strong> ada di pojok kanan atas setiap halaman.</span>
                </li>
            </ul>
        </div>

        <?php if (in_array($role, ['admin', 'guru', 'bk'], true)): ?>
        <!-- ============================================================ -->
        <!-- PANDUAN STAFF (Admin, Guru, BK, Guru Mapel) -->
        <!-- ============================================================ -->

        <div class="mb-4 mt-8">
            <h3 class="text-lg font-bold text-gray-800">Panduan Staff</h3>
        </div>

        <!-- Data Siswa -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Data Siswa</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Halaman untuk mengelola data seluruh siswa.</p>
                <ol class="list-decimal list-inside space-y-1 pl-1">
                    <li><strong>Tambah Siswa</strong> &mdash; Klik tombol "Tambah", isi NIS, nama, kelas, alamat, dan data orang tua.</li>
                    <li><strong>Edit</strong> &mdash; Klik ikon edit di baris siswa yang ingin diubah.</li>
                    <li><strong>Hapus</strong> &mdash; Klik ikon hapus, konfirmasi penghapusan.</li>
                    <li><strong>Cari</strong> &mdash; Gunakan kolom pencarian untuk filter berdasarkan nama atau NIS.</li>
                </ol>
            </div>
        </div>

        <!-- Pelanggaran -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Pencatatan Pelanggaran</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Halaman untuk mencatat pelanggaran siswa.</p>
                <ol class="list-decimal list-inside space-y-1 pl-1">
                    <li><strong>Tambah Pelanggaran</strong> &mdash; Klik "Tambah", pilih siswa, pilih jenis pelanggaran, isi tanggal dan keterangan.</li>
                    <li>Poin otomatis dihitung dari jenis pelanggaran yang dipilih.</li>
                    <li>Gunakan <strong>pagination</strong> di bawah tabel untuk navigasi data banyak.</li>
                </ol>
            </div>
        </div>

        <!-- Surat Pelanggaran -->
        <?php if ($isStaff): ?>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Surat Pelanggaran (SP)</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Buat surat peringatan berjenjang untuk orang tua siswa.</p>
                <ol class="list-decimal list-inside space-y-1 pl-1">
                    <li>Pilih pelanggaran yang sudah dicatat.</li>
                    <li>Pilih <strong>level SP</strong>: SP1 (ringan), SP2 (sedang), SP3 (berat), LE (tindakan final).</li>
                    <li>Nomor surat otomatis di-generate.</li>
                    <li>Klik <strong>"Print"</strong> untuk mencetak surat.</li>
                    <li>Level SP siswa otomatis terupdate.</li>
                </ol>
                <div class="mt-3 p-3 bg-amber-50 rounded-lg border border-amber-100">
                    <p class="text-xs font-semibold text-amber-800 mb-1">Level SP &amp; Warna:</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">SP1 - Ringan</span>
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">SP2 - Sedang</span>
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">SP3 - Berat</span>
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">LE - Final</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Surat Dokumen -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Surat Dokumen Lainnya</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Halaman untuk membuat surat pernyataan siswa dan surat pindah.</p>
                <ul class="list-disc list-inside space-y-1 pl-1">
                    <li><strong>Surat Pernyataan</strong> &mdash; Surat yang ditandatangani siswa sebagai bentuk komitmen perbaikan.</li>
                    <li><strong>Surat Pindah</strong> &mdash; Surat keterangan pindah sekolah beserta alasan dan sekolah tujuan.</li>
                </ul>
                <p>Gunakan tab di atas form untuk berpindah jenis surat.</p>
            </div>
        </div>

        <!-- Laporan -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-cyan-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Laporan Pelanggaran</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Cetak laporan daftar pelanggaran untuk keperluan rekap.</p>
                <ol class="list-decimal list-inside space-y-1 pl-1">
                    <li>Filter berdasarkan <strong>kelas</strong> dan/atau <strong>rentang tanggal</strong>.</li>
                    <li>Klik "Preview" untuk melihat hasil.</li>
                    <li>Klik "Print" untuk mencetak laporan.</li>
                </ol>
            </div>
        </div>
        <?php endif; ?>

        <!-- User Management (Admin Only) -->
        <?php if ($role === 'admin'): ?>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Kelola User</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Khusus admin &mdash; kelola semua akun pengguna sistem.</p>
                <ol class="list-decimal list-inside space-y-1 pl-1">
                    <li><strong>Tambah User</strong> &mdash; Isi username, password, nama, email, dan pilih role.</li>
                    <li><strong>Edit</strong> &mdash; Ubah data user atau reset password mereka.</li>
                    <li><strong>Nonaktifkan</strong> &mdash; Nonaktifkan akun tanpa menghapus data.</li>
                </ol>
                <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-xs font-semibold text-gray-700 mb-1">Role yang tersedia:</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">admin</span>
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">guru</span>
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">bk</span>
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-pink-100 text-pink-800">orangtua</span>
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-cyan-100 text-cyan-800">siswa</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Dashboard -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-teal-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                        <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Dashboard</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Halaman utama menampilkan:</p>
                <ul class="list-disc list-inside space-y-1 pl-1">
                    <li>Statistik total siswa, pelanggaran, surat terkirim</li>
                    <li>Grafik tren pelanggaran 7 hari terakhir</li>
                    <li>Distribusi jenis pelanggaran</li>
                    <li>Aktivitas terbaru dan top pelanggaran</li>
                </ul>
            </div>
        </div>

        <?php elseif ($role === 'orangtua'): ?>
        <!-- ============================================================ -->
        <!-- PANDUAN ORANG TUA -->
        <!-- ============================================================ -->

        <div class="mb-4 mt-8">
            <h3 class="text-lg font-bold text-gray-800">Panduan Orang Tua</h3>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Dashboard Orang Tua</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Dashboard Anda menampilkan data pelanggaran anak yang terkait dengan akun Anda.</p>
                <ol class="list-decimal list-inside space-y-1 pl-1">
                    <li><strong>Pilih Siswa</strong> &mdash; Jika Anda memiliki lebih dari satu anak, pilih nama anak yang ingin dilihat.</li>
                    <li><strong>Lihat Pelanggaran</strong> &mdash; Tabel riwayat pelanggaran lengkap dengan tanggal, jenis, dan poin.</li>
                    <li><strong>Lihat Surat</strong> &mdash; Surat pelanggaran (SP) yang sudah diterbitkan. Klik "Print" untuk mencetak.</li>
                </ol>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Cek Surat</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Cek surat pelanggaran anak dengan memasukkan NIS di halaman <strong>Surat</strong>.</p>
                <ul class="list-disc list-inside space-y-1 pl-1">
                    <li>Masukkan NIS anak Anda.</li>
                    <li>Sistem menampilkan semua surat yang pernah diterbitkan.</li>
                    <li>Download atau cetak surat jika diperlukan.</li>
                </ul>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Memahami Level SP</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-3">
                <p>Surat pelanggaran diberikan secara berjenjang:</p>
                <div class="space-y-2">
                    <div class="flex items-center gap-3 p-2 bg-blue-50 rounded-lg">
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800">SP1</span>
                        <span>Peringatan pertama &mdash; pembinaan awal oleh guru.</span>
                    </div>
                    <div class="flex items-center gap-3 p-2 bg-yellow-50 rounded-lg">
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-yellow-100 text-yellow-800">SP2</span>
                        <span>Peringatan kedua &mdash; perlu perhatian lebih dari orang tua.</span>
                    </div>
                    <div class="flex items-center gap-3 p-2 bg-orange-50 rounded-lg">
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-orange-100 text-orange-800">SP3</span>
                        <span>Peringatan akhir &mdash; orang tua wajib hadir di sekolah.</span>
                    </div>
                    <div class="flex items-center gap-3 p-2 bg-red-50 rounded-lg">
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">LE</span>
                        <span>Tindakan final &mdash; rapat keputusan bersama pihak sekolah.</span>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($role === 'siswa'): ?>
        <!-- ============================================================ -->
        <!-- PANDUAN SISWA -->
        <!-- ============================================================ -->

        <div class="mb-4 mt-8">
            <h3 class="text-lg font-bold text-gray-800">Panduan Siswa</h3>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Dashboard Siswa</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Dashboard menampilkan data pelanggaran Anda secara pribadi.</p>
                <ul class="list-disc list-inside space-y-1 pl-1">
                    <li><strong>Profil Siswa</strong> &mdash; Nama, NIS, kelas, dan alamat Anda.</li>
                    <li><strong>Statistik</strong> &mdash; Total pelanggaran, poin kumulatif, dan jumlah surat.</li>
                    <li><strong>Bar Poin</strong> &mdash; Progress bar yang menunjukkan akumulasi poin Anda terhadap batas 100.</li>
                    <li><strong>Riwayat</strong> &mdash; Daftar semua pelanggaran yang pernah tercatat.</li>
                    <li><strong>Surat</strong> &mdash; Surat SP yang telah diterbitkan. Klik "Lihat" untuk membuka.</li>
                </ul>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Status Poin</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Poin pelanggaran dihitung kumulatif. Berikut status berdasarkan total poin:</p>
                <div class="space-y-2 mt-2">
                    <div class="flex items-center gap-3 p-2 bg-green-50 rounded-lg">
                        <span class="font-semibold text-green-700 w-20 text-xs">0 - 29</span>
                        <span>Status Baik &mdash; Terus pertahankan!</span>
                    </div>
                    <div class="flex items-center gap-3 p-2 bg-yellow-50 rounded-lg">
                        <span class="font-semibold text-yellow-700 w-20 text-xs">30 - 59</span>
                        <span>Peringatan &mdash; Kurangi pelanggaran.</span>
                    </div>
                    <div class="flex items-center gap-3 p-2 bg-orange-50 rounded-lg">
                        <span class="font-semibold text-orange-700 w-20 text-xs">60 - 99</span>
                        <span>Serius &mdash; Orang tua akan dihubungi.</span>
                    </div>
                    <div class="flex items-center gap-3 p-2 bg-red-50 rounded-lg">
                        <span class="font-semibold text-red-700 w-20 text-xs">100+</span>
                        <span>Kritis &mdash; Tindakan definitif akan diambil.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Cek Surat</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Anda juga bisa mengecek surat pelanggaran melalui halaman <strong>Surat</strong> di sidebar.</p>
                <p>Masukkan NIS Anda untuk melihat semua surat yang pernah diterbitkan.</p>
            </div>
        </div>

        <?php endif; ?>

        <!-- Pengaturan Akun (semua role) -->
        <div class="mb-4 mt-8">
            <h3 class="text-lg font-bold text-gray-800">Pengaturan Akun</h3>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Pengaturan Akun</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-2">
                <p>Akses via foto profil di atas sidebar, atau langsung ke halaman Pengaturan.</p>
                <ul class="list-disc list-inside space-y-1 pl-1">
                    <li><strong>Ganti Nama</strong> &mdash; Ubah nama tampilan Anda.</li>
                    <li><strong>Ganti Email</strong> &mdash; Ubah alamat email.</li>
                    <li><strong>Ganti Password</strong> &mdash; Masukkan password lama, lalu password baru (min. 6 karakter).</li>
                </ul>
                <p class="text-xs text-gray-400 mt-2">Username dan role tidak dapat diubah sendiri. Hubungi admin jika ada kesalahan.</p>
            </div>
        </div>

    </div>
</div>

<script>
const logout = async () => {
    if (confirm('Yakin mau logout?')) {
        try {
            const response = await fetch('endpoint/auth/logout.php');
            const data = await response.json();
            if (data.success) {
                window.location.href = data.redirect;
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
};
</script>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
