<?php

require_once __DIR__ . '/../app/config/bootstrap.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/middleware/RoleMiddleware.php';
require_once __DIR__ . '/../app/helpers/SettingsHelper.php';

$database = new Database();
$conn = $database->connect();

$authController = new AuthController(null);

if (!$authController->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

requireRoles(['admin']);

$alertMessage = null;
$alertType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!SecurityHelper::verifyCSRFToken()) {
        $alertMessage = 'Token tidak valid. Muat ulang halaman.';
        $alertType = 'error';
    } else {
        $settingsKeys = ['nama_sekolah', 'nama_guru_bk', 'nama_wakasek', 'nama_kepala_sekolah'];
        foreach ($settingsKeys as $key) {
            $value = trim($_POST[$key] ?? '');
            if ($value !== '') {
                SettingsHelper::set($conn, $key, $value);
            }
        }
        SecurityHelper::auditLog($conn, 'UPDATE', 'app_settings', null, 'Pengaturan sistem diperbarui');
        $alertMessage = 'Pengaturan berhasil disimpan.';
    }
}

$settings = SettingsHelper::getAll($conn);

$title = 'Pengaturan Sistem - E-Disiplin';
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
                        <p class="text-xs text-gray-500">Pengaturan Sistem</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="dashboard.php" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Kembali">
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

    <div class="max-w-2xl mx-auto px-4 py-8 dock-safe">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Pengaturan Sistem</h2>
            <p class="text-gray-600 mt-1">Konfigurasi data sekolah yang digunakan di seluruh sistem.</p>
        </div>

        <?php if ($alertMessage): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $alertType === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700'; ?>">
                <p class="text-sm font-medium"><?php echo htmlspecialchars($alertMessage); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
                <h3 class="font-semibold text-gray-900 mb-4">Informasi Sekolah</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sekolah</label>
                        <input type="text" name="nama_sekolah"
                               value="<?php echo htmlspecialchars($settings['nama_sekolah'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nama sekolah">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kepala Sekolah</label>
                        <input type="text" name="nama_kepala_sekolah"
                               value="<?php echo htmlspecialchars($settings['nama_kepala_sekolah'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nama lengkap beserta gelar">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
                <h3 class="font-semibold text-gray-900 mb-4">Pejabat Default Surat</h3>
                <p class="text-xs text-gray-500 mb-4">Nama-nama ini akan muncul otomatis saat membuat surat baru. Bisa diubah per surat.</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Guru BK</label>
                        <input type="text" name="nama_guru_bk"
                               value="<?php echo htmlspecialchars($settings['nama_guru_bk'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nama guru BK beserta gelar">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Wakil Kepala Sekolah</label>
                        <input type="text" name="nama_wakasek"
                               value="<?php echo htmlspecialchars($settings['nama_wakasek'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nama wakasek beserta gelar">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-6 rounded-lg transition">
                Simpan Pengaturan
            </button>
        </form>
    </div>
</div>

<script>
const logout = async () => {
    if (confirm('Yakin mau logout?')) {
        try {
            const response = await fetch('endpoint/auth/logout.php');
            const data = await response.json();
            if (data.success) { window.location.href = data.redirect; }
        } catch (error) { console.error('Error:', error); }
    }
};
</script>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
