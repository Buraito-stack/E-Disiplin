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

requireRoles(['orangtua', 'siswa']);

$role = $_SESSION['role'] ?? '';
if ($role === 'orangtua') {
    header('Location: ortu_dashboard.php');
    exit;
} elseif ($role === 'siswa') {
    header('Location: siswa_dashboard.php');
    exit;
}

$nis = trim($_GET['nis'] ?? '');
$suratList = [];

if ($nis !== '') {
    // Ownership verification: siswa can only see own NIS, orangtua only their child
    $allowed = false;
    if ($role === 'siswa') {
        $allowed = ($nis === ($_SESSION['username'] ?? ''));
    } elseif ($role === 'orangtua') {
        require_once __DIR__ . '/../app/middleware/AccessControl.php';
        $ac = new AccessControl($conn);
        $ownSiswa = $ac->getAuthorizedSiswaList();
        foreach ($ownSiswa as $s) {
            if ($s['nis'] === $nis) { $allowed = true; break; }
        }
    } else {
        $allowed = in_array($role, ['admin', 'guru', 'bk', 'guru_mapel'], true);
    }

    if ($allowed) {
        $stmt = $conn->prepare(
            "SELECT so.id_surat_orang_tua, so.tanggal_cetak, so.status_kirim,
                    p.tanggal AS tanggal_pelanggaran, p.keterangan,
                    s.nama, s.nis, s.kelas,
                    j.nama_jenis, j.poin
             FROM surat_orang_tua so
             JOIN pelanggaran p ON p.id_pelanggaran = so.id_pelanggaran
             JOIN siswa s ON s.id_siswa = p.id_siswa
             JOIN jenis_pelanggaran j ON j.id_jenis = p.id_jenis
             WHERE s.nis = ?
             ORDER BY so.tanggal_cetak DESC, so.id_surat_orang_tua DESC"
        );
        if ($stmt) {
            $stmt->bind_param('s', $nis);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) $suratList = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
}

$title = 'Cek Surat - E-Disiplin';
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
                        <p class="text-xs text-gray-500">Cek Surat Pelanggaran</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <form method="GET" class="hidden sm:flex items-center bg-gray-100 rounded-lg px-3 py-2 w-72">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="nis" value="<?php echo htmlspecialchars($nis); ?>" placeholder="Masukkan NIS..." class="bg-transparent ml-2 outline-none w-full text-sm" />
                    </form>
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
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Cek Surat Pelanggaran</h2>
            <p class="text-gray-600 mt-1">Masukkan NIS untuk melihat surat pelanggaran.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <?php if ($nis === ''): ?>
                <p class="text-sm text-gray-500">Silakan masukkan NIS pada kotak pencarian.</p>
            <?php elseif (empty($suratList)): ?>
                <p class="text-sm text-gray-500">Tidak ada surat pelanggaran untuk NIS tersebut.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 pr-4">Tanggal Cetak</th>
                                <th class="py-2 pr-4">Nama</th>
                                <th class="py-2 pr-4">Kelas</th>
                                <th class="py-2 pr-4">Jenis</th>
                                <th class="py-2 pr-4">Poin</th>
                                <th class="py-2 pr-4">Status</th>
                                <th class="py-2 pr-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            <?php foreach ($suratList as $row): ?>
                                <tr class="border-t border-gray-100">
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['tanggal_cetak']); ?></td>
                                    <td class="py-2 pr-4 font-medium text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['kelas']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['nama_jenis']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars((string)$row['poin']); ?></td>
                                    <td class="py-2 pr-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                            <?php echo htmlspecialchars($row['status_kirim'] ?: 'Belum Dikirim'); ?>
                                        </span>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <a href="surat_print.php?id=<?php echo (int)$row['id_surat_orang_tua']; ?>" target="_blank" class="text-blue-600 hover:text-blue-700">Print / PDF</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
