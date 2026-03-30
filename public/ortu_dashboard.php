<?php

require_once __DIR__ . '/../app/config/bootstrap.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/middleware/RoleMiddleware.php';
require_once __DIR__ . '/../app/middleware/AccessControl.php';

$database = new Database();
$conn = $database->connect();

$authController = new AuthController(null);

if (!$authController->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

requireRoles(['orangtua']);

$user = $_SESSION;
$userId = (int)$user['user_id'];

$accessControl = new AccessControl($conn);

$siswaList = [];
$selectedSiswa = null;
$pelanggaranAnak = [];
$totalPoinAnak = 0;
$totalSuratAnak = 0;

$siswaList = $accessControl->getAuthorizedSiswaList();

if (!empty($_GET['siswa_id'])) {
    $siswaId = (int)$_GET['siswa_id'];
    
    // VERIFY siswa belongs to this parent (CRITICAL SECURITY CHECK)
    $selectedSiswa = $accessControl->verifySiswaOwnership($siswaId);
    
    if (!$selectedSiswa) {
        // Log unauthorized access attempt
        SecurityHelper::logSecurityEvent(
            'UNAUTHORIZED_ACCESS_ATTEMPT',
            $_SESSION['user_id'],
            "Attempted to access siswa_id=$siswaId which is not theirs"
        );
        http_response_code(403);
        die('Akses ditolak. Anda hanya dapat melihat data anak Anda sendiri.');
    }
    
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
                $pelanggaranAnak = $pelangResult->fetch_all(MYSQLI_ASSOC);
                
                $totalPoinAnak = array_sum(array_column($pelanggaranAnak, 'poin'));
            }
            
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
                $suratAnak = $suratResult->fetch_all(MYSQLI_ASSOC);
                $totalSuratAnak = count($suratAnak);
            }
}

$title = 'Dashboard Orang Tua - E-Disiplin';
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
                        <p class="text-xs text-gray-500">Dashboard Orang Tua</p>
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 dock-safe">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Selamat Datang, <?php echo htmlspecialchars($user['name']); ?></h2>
            <p class="text-gray-600 mt-1">Monitor riwayat pelanggaran siswa</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <h3 class="font-semibold text-gray-900 mb-4">Pilih Siswa</h3>
            <?php if (empty($siswaList)): ?>
                <p class="text-sm text-gray-500">Belum ada data siswa yang terkait dengan akun Anda.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <?php foreach ($siswaList as $siswa): ?>
                        <a href="?siswa_id=<?php echo (int)$siswa['id_siswa']; ?>" 
                           class="p-4 border rounded-lg hover:border-blue-500 hover:bg-blue-50 transition <?php echo isset($selectedSiswa) && $selectedSiswa['id_siswa'] == $siswa['id_siswa'] ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>">
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($siswa['nama']); ?></p>
                            <p class="text-sm text-gray-600">NIS: <?php echo htmlspecialchars($siswa['nis']); ?></p>
                            <p class="text-sm text-gray-600">Kelas: <?php echo htmlspecialchars($siswa['kelas']); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($selectedSiswa): ?>
            <div class="space-y-6">
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl p-6 border border-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-4">Informasi Siswa</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Nama</p>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($selectedSiswa['nama']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">NIS</p>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($selectedSiswa['nis']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Kelas</p>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($selectedSiswa['kelas']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Poin Pelanggaran</p>
                            <p class="font-semibold text-red-600 text-lg"><?php echo $totalPoinAnak; ?> Poin</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-sm text-gray-600 mb-2">Total Pelanggaran</h3>
                        <p class="text-3xl font-bold text-red-600"><?php echo count($pelanggaranAnak); ?></p>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-sm text-gray-600 mb-2">Surat Dikirim</h3>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $totalSuratAnak; ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-4">Riwayat Pelanggaran</h3>
                    <?php if (empty($pelanggaranAnak)): ?>
                        <p class="text-sm text-gray-500">Tidak ada riwayat pelanggaran.</p>
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
                                    <?php foreach ($pelanggaranAnak as $pelanggaran): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 pr-4 font-medium"><?php echo htmlspecialchars(date('d/m/Y', strtotime($pelanggaran['tanggal']))); ?></td>
                                            <td class="py-3 pr-4"><?php echo htmlspecialchars($pelanggaran['nama_jenis']); ?></td>
                                            <td class="py-3 pr-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <?php echo (int)$pelanggaran['poin']; ?> poin
                                                </span>
                                            </td>
                                            <td class="py-3 pr-4 text-gray-600"><?php echo htmlspecialchars($pelanggaran['keterangan'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($suratAnak)): ?>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="font-semibold text-gray-900 mb-4">Surat Pelanggaran</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 border-b border-gray-100">
                                        <th class="py-3 pr-4">Tanggal Cetak</th>
                                        <th class="py-3 pr-4">Jenis</th>
                                        <th class="py-3 pr-4">Level SP</th>
                                        <th class="py-3 pr-4">Status</th>
                                        <th class="py-3 pr-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($suratAnak as $surat): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 pr-4 font-medium"><?php echo htmlspecialchars(date('d/m/Y', strtotime($surat['tanggal_cetak']))); ?></td>
                                            <td class="py-3 pr-4"><?php echo htmlspecialchars($surat['nama_jenis']); ?></td>
                                            <td class="py-3 pr-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    <?php 
                                                    $levelSp = $surat['level_sp'] ?? 'SP1';
                                                    if ($levelSp === 'SP1') echo 'bg-blue-100 text-blue-800';
                                                    elseif ($levelSp === 'SP2') echo 'bg-yellow-100 text-yellow-800';
                                                    elseif ($levelSp === 'SP3') echo 'bg-orange-100 text-orange-800';
                                                    else echo 'bg-red-100 text-red-800';
                                                    ?>">
                                                    <?php echo htmlspecialchars($surat['level_sp'] ?? 'SP1'); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 pr-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    <?php echo $surat['status_kirim'] === 'Terkirim' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo htmlspecialchars($surat['status_kirim'] ?? 'Belum Dikirim'); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 pr-4">
                                                <a href="surat_print.php?id=<?php echo (int)$surat['id_surat_orang_tua']; ?>" target="_blank" class="text-blue-600 hover:text-blue-700 font-medium">Print</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl p-12 shadow-sm border border-gray-100 text-center">
                <p class="text-gray-600 mb-4">Silakan pilih siswa untuk melihat riwayat pelanggaran.</p>
            </div>
        <?php endif; ?>
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
