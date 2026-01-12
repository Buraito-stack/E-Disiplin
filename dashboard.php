<?php

session_start();

require_once __DIR__ . '/app/config/Database.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

$database = new Database();
$conn = $database->connect();

$authController = new AuthController(null);

// Check if user is logged in
if (!$authController->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION;

// Get dashboard data from database
$siswaCount = 0;
$pelanggaranCount = 0;
$suratCount = 0;
$poinTotal = 0;

$result = $conn->query("SELECT COUNT(*) as total FROM siswa");
if ($result) $siswaCount = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM pelanggaran");
if ($result) $pelanggaranCount = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM surat_orang_tua");
if ($result) $suratCount = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM pelanggaran WHERE DATE(tanggal) >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
if ($result) $poinTotal = $result->fetch_assoc()['total'];

$title = 'Dashboard - E-Disiplin';
include __DIR__ . '/views/layouts/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-full flex items-center justify-center">
                        <span class="text-white font-bold">E</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">E-Disiplin</h1>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center bg-gray-100 rounded-lg px-3 py-2 w-64">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" placeholder="Cari..." class="bg-transparent ml-2 outline-none w-full text-sm" />
                    </div>
                    <button onclick="logout()" class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Selamat Datang, <?php echo ucfirst(explode(' ', $user['name'])[0]); ?></h2>
            <p class="text-gray-600 mt-1">Sistem Manajemen Poin Pelanggaran Siswa</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Card -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 mb-4">Profile</h3>
                            <div class="flex items-center gap-4">
                                <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center">
                                    <span class="text-white text-2xl font-bold"><?php echo substr($user['name'], 0, 1); ?></span>
                                </div>
                                <div>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo $user['name']; ?></p>
                                    <p class="text-sm text-gray-600 capitalize"><?php echo $user['role']; ?></p>
                                </div>
                            </div>
                        </div>
                        <button class="p-2 hover:bg-gray-100 rounded-lg transition">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Card 1 -->
                    <div class="bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl p-6 text-white">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="font-semibold">Total Siswa</h3>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-4xl font-bold"><?php echo $siswaCount; ?></p>
                        <p class="text-blue-100 text-sm mt-1">Data terdaftar</p>
                    </div>

                    <!-- Card 2 -->
                    <div class="bg-gradient-to-br from-red-400 to-red-600 rounded-2xl p-6 text-white">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="font-semibold">Pelanggaran</h3>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-4xl font-bold"><?php echo $pelanggaranCount; ?></p>
                        <p class="text-red-100 text-sm mt-1">Bulan ini</p>
                    </div>

                    <!-- Card 3 -->
                    <div class="bg-gradient-to-br from-green-400 to-green-600 rounded-2xl p-6 text-white">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="font-semibold">Surat Terkirim</h3>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="text-4xl font-bold"><?php echo $suratCount; ?></p>
                        <p class="text-green-100 text-sm mt-1">Total terkirim</p>
                    </div>

                    <!-- Card 4 -->
                    <div class="bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl p-6 text-white">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="font-semibold">Aktif 30 Hari</h3>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <p class="text-4xl font-bold"><?php echo $poinTotal; ?></p>
                        <p class="text-yellow-100 text-sm mt-1">Pelanggaran</p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-4">Akses Cepat</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <a href="#" class="flex items-center gap-3 p-3 hover:bg-gray-50 rounded-lg transition">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                                </svg>
                            </div>
                            <div class="text-sm font-medium text-gray-700">Data Siswa</div>
                        </a>
                        <a href="#" class="flex items-center gap-3 p-3 hover:bg-gray-50 rounded-lg transition">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 2a1 1 0 011-1h8a1 1 0 011 1v1h4a1 1 0 010 2h-1v11a2 2 0 01-2 2H4a2 2 0 01-2-2V5H1a1 1 0 010-2h4V2zm8 2H7v11h6V4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="text-sm font-medium text-gray-700">Pelanggaran</div>
                        </a>
                        <a href="#" class="flex items-center gap-3 p-3 hover:bg-gray-50 rounded-lg transition">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z" />
                                </svg>
                            </div>
                            <div class="text-sm font-medium text-gray-700">Laporan</div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Role Info -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-4">Informasi Akun</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Nama</p>
                            <p class="text-sm text-gray-900 font-medium"><?php echo $user['name']; ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Username</p>
                            <p class="text-sm text-gray-900 font-medium"><?php echo $user['username']; ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Role</p>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 capitalize">
                                    <?php echo $user['role']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-4">Statistik Cepat</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Sistem Aktif</span>
                            <span class="text-sm font-semibold text-green-600">✓ Online</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: 85%"></div>
                        </div>
                        <p class="text-xs text-gray-500">Uptime: 99.8%</p>
                    </div>
                </div>

                <!-- Footer Info -->
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl p-6 text-white">
                    <h4 class="font-semibold mb-2">Tips & Panduan</h4>
                    <p class="text-sm text-gray-300 mb-4">Manfaatkan fitur pencarian untuk menemukan data siswa dengan cepat.</p>
                    <button class="text-sm text-blue-400 hover:text-blue-300 font-medium">Baca dokumentasi →</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const logout = async () => {
    if (confirm('Yakin mau logout?')) {
        try {
            const response = await fetch('api/auth/logout.php');
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

<?php include __DIR__ . '/views/layouts/footer.php'; ?>

