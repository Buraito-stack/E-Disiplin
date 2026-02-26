<?php

require_once __DIR__ . '/../app/config/bootstrap.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/middleware/RoleMiddleware.php';

$database = new Database();
$conn = $database->connect();

$authController = new AuthController(null);

if (!$authController->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

requireRoles(['siswa']);

$user = $_SESSION;
$username = $user['username'];

// Get data siswa berdasarkan NIS (username)
$siswaData = null;
$pelanggaranSiswa = [];
$totalPoinSiswa = 0;
$suratSiswa = [];
$totalSuratSiswa = 0;

$stmt = $conn->prepare("SELECT * FROM siswa WHERE nis = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $siswaData = $result->fetch_assoc();
        $siswaId = (int)$siswaData['id_siswa'];
        
        // Get pelanggaran siswa
        $pelangStmt = $conn->prepare(
            "SELECT p.id_pelanggaran, p.tanggal, p.keterangan, j.nama_jenis, j.poin
             FROM pelanggaran p
             JOIN jenis_pelanggaran j ON j.id_jenis = p.id_jenis
             WHERE p.id_siswa = ?
             ORDER BY p.tanggal DESC"
        );
        if ($pelangStmt) {
            $pelangStmt->bind_param('i', $siswaId);
            $pelangStmt->execute();
            $pelangResult = $pelangStmt->get_result();
            $pelanggaranSiswa = $pelangResult->fetch_all(MYSQLI_ASSOC);
            $totalPoinSiswa = array_sum(array_column($pelanggaranSiswa, 'poin'));
        }
        
        // Get surat pelanggaran siswa
        $suratStmt = $conn->prepare(
            "SELECT so.id_surat_orang_tua, so.tanggal_cetak, so.status_kirim, so.level_sp, p.tanggal, j.nama_jenis
             FROM surat_orang_tua so
             JOIN pelanggaran p ON p.id_pelanggaran = so.id_pelanggaran
             JOIN jenis_pelanggaran j ON j.id_jenis = p.id_jenis
             WHERE p.id_siswa = ?
             ORDER BY so.tanggal_cetak DESC"
        );
        if ($suratStmt) {
            $suratStmt->bind_param('i', $siswaId);
            $suratStmt->execute();
            $suratResult = $suratStmt->get_result();
            $suratSiswa = $suratResult->fetch_all(MYSQLI_ASSOC);
            $totalSuratSiswa = count($suratSiswa);
        }
    }
}

$title = 'Dashboard Siswa - E-Disiplin';
include __DIR__ . '/../app/views/layouts/header.php';
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
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">E-Disiplin</h1>
                        <p class="text-xs text-gray-500">Dashboard Siswa</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 dock-safe">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Selamat Datang, <?php echo htmlspecialchars($siswaData['nama'] ?? $user['name']); ?></h2>
            <p class="text-gray-600 mt-1">Pantau riwayat pelanggaran Anda</p>
        </div>

        <?php if ($siswaData): ?>
            <div class="space-y-6">
                <!-- Profile Card -->
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl p-6 border border-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-4">Profil Siswa</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Nama Lengkap</p>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($siswaData['nama']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">NIS</p>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($siswaData['nis']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Kelas</p>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($siswaData['kelas']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Alamat</p>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars(substr($siswaData['alamat'] ?? '-', 0, 50)); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-sm text-gray-600 mb-2">Total Pelanggaran</h3>
                        <p class="text-4xl font-bold text-red-600"><?php echo count($pelanggaranSiswa); ?></p>
                        <p class="text-xs text-gray-500 mt-2">Sepanjang masa</p>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-sm text-gray-600 mb-2">Total Poin</h3>
                        <p class="text-4xl font-bold text-orange-600"><?php echo $totalPoinSiswa; ?></p>
                        <p class="text-xs text-gray-500 mt-2">Akumulatif</p>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-sm text-gray-600 mb-2">Surat Dikirim</h3>
                        <p class="text-4xl font-bold text-blue-600"><?php echo $totalSuratSiswa; ?></p>
                        <p class="text-xs text-gray-500 mt-2">Untuk orang tua</p>
                    </div>
                </div>

                <!-- Tingkat Keseriusan -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-4">Status Poin</h3>
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                            <div class="flex justify-between mb-2">
                                <span class="text-sm text-gray-600">Poin Kumulatif</span>
                                <span class="text-sm font-semibold"><?php echo $totalPoinSiswa; ?>/100</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-yellow-400 to-red-600 h-2 rounded-full" 
                                     style="width: <?php echo min(($totalPoinSiswa / 100) * 100, 100); ?>%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <?php if ($totalPoinSiswa < 30): ?>
                                    ✅ Status Baik - Terus pertahankan!
                                <?php elseif ($totalPoinSiswa < 60): ?>
                                    ⚠️ Status Peringatan - Berkuranglah pelanggaran
                                <?php elseif ($totalPoinSiswa < 100): ?>
                                    🚨 Status Serius - Perhatian orang tua diperlukan
                                <?php else: ?>
                                    🔴 Status Kritis - Tindakan definitif diperlukan
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Pelanggaran -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-4">Riwayat Pelanggaran Anda</h3>
                    <?php if (empty($pelanggaranSiswa)): ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">Tidak ada pelanggaran. Pertahankan prestasi Anda! 🎉</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 border-b border-gray-100">
                                        <th class="py-3 pr-4">Tanggal</th>
                                        <th class="py-3 pr-4">Jenis Pelanggaran</th>
                                        <th class="py-3 pr-4">Poin</th>
                                        <th class="py-3 pr-4">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pelanggaranSiswa as $pelanggaran): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 pr-4 font-medium"><?php echo htmlspecialchars(date('d/m/Y', strtotime($pelanggaran['tanggal']))); ?></td>
                                            <td class="py-3 pr-4 font-medium text-gray-900"><?php echo htmlspecialchars($pelanggaran['nama_jenis']); ?></td>
                                            <td class="py-3 pr-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <?php echo (int)$pelanggaran['poin']; ?> poin
                                                </span>
                                            </td>
                                            <td class="py-3 pr-4 text-gray-600 text-xs"><?php echo htmlspecialchars(substr($pelanggaran['keterangan'] ?? '-', 0, 40)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Surat Pelanggaran -->
                <?php if (!empty($suratSiswa)): ?>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="font-semibold text-gray-900 mb-4">Surat Pelanggaran</h3>
                        <div class="space-y-3">
                            <?php foreach ($suratSiswa as $surat): ?>
                                <div class="flex items-center justify-between p-4 border border-gray-100 rounded-lg hover:bg-gray-50">
                                    <div>
                                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($surat['nama_jenis']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars(date('d/m/Y', strtotime($surat['tanggal_cetak']))); ?></p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php 
                                            $levelSp = $surat['level_sp'] ?? 'SP1';
                                            if ($levelSp === 'SP1') echo 'bg-blue-100 text-blue-800';
                                            elseif ($levelSp === 'SP2') echo 'bg-yellow-100 text-yellow-800';
                                            elseif ($levelSp === 'SP3') echo 'bg-orange-100 text-orange-800';
                                            else echo 'bg-red-100 text-red-800';
                                            ?>">
                                            <?php echo htmlspecialchars($levelSp); ?>
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $surat['status_kirim'] === 'Terkirim' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo htmlspecialchars($surat['status_kirim'] ?? 'Belum Dikirim'); ?>
                                        </span>
                                        <a href="surat_print.php?id=<?php echo (int)$surat['id_surat_orang_tua']; ?>" target="_blank" 
                                           class="text-blue-600 hover:text-blue-700 font-medium">Lihat</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl p-12 shadow-sm border border-gray-100 text-center">
                <p class="text-gray-600">Data siswa tidak ditemukan. Hubungi admin sekolah.</p>
            </div>
        <?php endif; ?>
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

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
