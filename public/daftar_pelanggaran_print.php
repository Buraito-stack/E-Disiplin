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

requireRoles(['admin', 'guru', 'bk']);

$filterKelas = trim($_GET['kelas'] ?? '');
$filterDari = trim($_GET['dari'] ?? '');
$filterSampai = trim($_GET['sampai'] ?? '');

$where = [];
$params = [];
$types = '';

if ($filterKelas !== '') {
    $where[] = 's.kelas = ?';
    $params[] = $filterKelas;
    $types .= 's';
}
if ($filterDari !== '') {
    $where[] = 'p.tanggal >= ?';
    $params[] = $filterDari;
    $types .= 's';
}
if ($filterSampai !== '') {
    $where[] = 'p.tanggal <= ?';
    $params[] = $filterSampai;
    $types .= 's';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$rows = [];
$stmt = $conn->prepare("SELECT p.tanggal, s.nama, s.kelas, j.nama_jenis, j.poin, p.keterangan
    FROM pelanggaran p
    JOIN siswa s ON s.id_siswa = p.id_siswa
    JOIN jenis_pelanggaran j ON j.id_jenis = p.id_jenis
    {$whereSql}
    ORDER BY p.tanggal DESC, s.nama ASC");

if ($stmt) {
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) $rows = $result->fetch_all(MYSQLI_ASSOC);
}

$kelasOptions = [];
$result = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas ASC");
if ($result) $kelasOptions = array_column($result->fetch_all(MYSQLI_ASSOC), 'kelas');

require_once __DIR__ . '/../app/templates/SuratTemplate.php';

if (isset($_GET['print'])) {
    echo getTemplateDaftarPelanggaran([
        'rows' => $rows,
        'filter_kelas' => $filterKelas ?: 'Semua Kelas',
        'filter_dari' => $filterDari,
        'filter_sampai' => $filterSampai,
        'tanggal_cetak' => date('Y-m-d'),
    ]);
    exit;
}

$title = 'Cetak Daftar Pelanggaran - E-Disiplin';
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
                        <p class="text-xs text-gray-500">Cetak Daftar Pelanggaran</p>
                    </div>
                </div>
                <a href="pelanggaran.php" class="text-sm text-blue-600 hover:underline px-3">← Kembali</a>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Cetak Daftar Pelanggaran</h2>
            <p class="text-gray-600 mt-1">Filter data lalu cetak sebagai dokumen A4 landscape.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="text-sm font-medium text-gray-700">Kelas</label>
                    <select name="kelas" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelasOptions as $k): ?>
                            <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $filterKelas === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($k); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Tanggal Dari</label>
                    <input type="date" name="dari" value="<?php echo htmlspecialchars($filterDari); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Tanggal Sampai</label>
                    <input type="date" name="sampai" value="<?php echo htmlspecialchars($filterSampai); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700">Filter</button>
                    <button type="submit" name="print" value="1" formtarget="_blank" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Cetak PDF</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Preview Data (<?php echo count($rows); ?> pelanggaran)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border-collapse">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-200">
                            <th class="py-2 pr-3 w-8">No</th>
                            <th class="py-2 pr-3">Tanggal</th>
                            <th class="py-2 pr-3">Nama Siswa</th>
                            <th class="py-2 pr-3">Kelas</th>
                            <th class="py-2 pr-3">Jenis Pelanggaran</th>
                            <th class="py-2 pr-3 w-12">Poin</th>
                            <th class="py-2">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="7" class="py-4 text-gray-500 text-center">Tidak ada data pelanggaran dengan filter tersebut.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $i => $row): ?>
                                <tr class="border-t border-gray-100 <?php echo $i % 2 === 1 ? 'bg-gray-50' : ''; ?>">
                                    <td class="py-2 pr-3 text-center text-gray-500"><?php echo $i + 1; ?></td>
                                    <td class="py-2 pr-3"><?php echo htmlspecialchars($row['tanggal']); ?></td>
                                    <td class="py-2 pr-3 font-medium text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td class="py-2 pr-3"><?php echo htmlspecialchars($row['kelas']); ?></td>
                                    <td class="py-2 pr-3"><?php echo htmlspecialchars($row['nama_jenis']); ?></td>
                                    <td class="py-2 pr-3 text-center"><?php echo htmlspecialchars((string)$row['poin']); ?></td>
                                    <td class="py-2"><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
