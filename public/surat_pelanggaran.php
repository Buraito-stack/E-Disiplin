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

$user = $_SESSION;

$alertMessage = null;
$alertType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_surat'])) {
    // CSRF Token verification (CRITICAL SECURITY CHECK)
    if (!SecurityHelper::verifyCSRFToken()) {
        $alertMessage = 'Akses ditolak: token tidak valid. Silakan muat ulang halaman.';
        $alertType = 'error';
    } else {
        $idPelanggaran = isset($_POST['id_pelanggaran']) ? (int)$_POST['id_pelanggaran'] : 0;
        $levelSp = trim($_POST['level_sp'] ?? 'SP1');
        $tanggalCetak = date('Y-m-d');
        $statusKirim = 'Belum Dikirim';

        if ($idPelanggaran <= 0 || empty($levelSp)) {
            $alertMessage = 'Pilih pelanggaran dan level surat terlebih dahulu.';
            $alertType = 'error';
        } else {
            $checkStmt = $conn->prepare("SELECT id_surat_orang_tua FROM surat_orang_tua WHERE id_pelanggaran = ? LIMIT 1");
            if ($checkStmt) {
                $checkStmt->bind_param('i', $idPelanggaran);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                if ($checkResult && $checkResult->num_rows > 0) {
                    $alertMessage = 'Surat untuk pelanggaran ini sudah pernah dibuat.';
                    $alertType = 'error';
                } else {
                    // Get siswa dari pelanggaran
                    $getSiswaStmt = $conn->prepare("SELECT p.id_siswa, s.level_sp FROM pelanggaran p JOIN siswa s ON p.id_siswa = s.id_siswa WHERE p.id_pelanggaran = ?");
                    if ($getSiswaStmt) {
                        $getSiswaStmt->bind_param('i', $idPelanggaran);
                        $getSiswaStmt->execute();
                        $siswaResult = $getSiswaStmt->get_result();
                        if ($siswaResult && $siswaResult->num_rows > 0) {
                            $siswaData = $siswaResult->fetch_assoc();
                            $idSiswa = (int)$siswaData['id_siswa'];
                            $levelSpSebelum = $siswaData['level_sp'];

                            // Map SP ke angka
                            $levelSpMap = ['SP1' => 1, 'SP2' => 2, 'SP3' => 3, 'LE' => 4];
                            $levelSpAngka = $levelSpMap[$levelSp] ?? 1;

                            // Generate nomor surat
                            $tanggun = date('n'); // bulan
                            $tahun = date('Y');
                            $tglCetak = date('d');
                            $nomorSurat = "$tglCetak/$levelSpAngka/$tanggun/$tahun";

                            // Insert surat
                            $insertStmt = $conn->prepare("INSERT INTO surat_orang_tua (id_pelanggaran, tanggal_cetak, status_kirim, level_sp, nomor_surat) VALUES (?, ?, ?, ?, ?)");
                            if ($insertStmt) {
                                $insertStmt->bind_param('issss', $idPelanggaran, $tanggalCetak, $statusKirim, $levelSp, $nomorSurat);
                                if ($insertStmt->execute()) {
                                    // Update level_sp siswa
                                    $updateSiswaStmt = $conn->prepare("UPDATE siswa SET level_sp = ?, tanggal_sp_terakhir = NOW() WHERE id_siswa = ?");
                                    if ($updateSiswaStmt) {
                                        $updateSiswaStmt->bind_param('ii', $levelSpAngka, $idSiswa);
                                        $updateSiswaStmt->execute();
                                    }

                                    $alertMessage = "Surat pelanggaran $levelSp berhasil dibuat (No: $nomorSurat).";
                                    $alertType = 'success';
                                } else {
                                    $alertMessage = 'Gagal membuat surat pelanggaran.';
                                    $alertType = 'error';
                                }
                            } else {
                                $alertMessage = 'Gagal menyiapkan pembuatan surat.';
                                $alertType = 'error';
                            }
                        }
                    }
                }
            } else {
                $alertMessage = 'Gagal memeriksa surat pelanggaran.';
                $alertType = 'error';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF Token verification (CRITICAL SECURITY CHECK)
    if (!SecurityHelper::verifyCSRFToken()) {
        $alertMessage = 'Akses ditolak: token tidak valid. Silakan muat ulang halaman.';
        $alertType = 'error';
    } else {
        $action = $_POST['action'];

        if ($action === 'update_surat') {
            $id = (int)($_POST['id_surat_orang_tua'] ?? 0);
            $status = trim($_POST['status_kirim'] ?? '');
            if ($id > 0) {
                $stmt = $conn->prepare('UPDATE surat_orang_tua SET status_kirim = ? WHERE id_surat_orang_tua = ?');
                if ($stmt) {
                    $stmt->bind_param('si', $status, $id);
                    if ($stmt->execute()) {
                        $alertMessage = 'Status surat berhasil diperbarui.';
                        $alertType = 'success';
                    } else {
                        $alertMessage = 'Gagal memperbarui status surat.';
                        $alertType = 'error';
                    }
                }
            }
        }

        if ($action === 'delete_surat') {
            $id = (int)($_POST['id_surat_orang_tua'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare('DELETE FROM surat_orang_tua WHERE id_surat_orang_tua = ?');
                if ($stmt) {
                    $stmt->bind_param('i', $id);
                    if ($stmt->execute()) {
                        $alertMessage = 'Surat berhasil dihapus.';
                        $alertType = 'success';
                    } else {
                        $alertMessage = 'Gagal menghapus surat.';
                        $alertType = 'error';
                    }
                }
            }
        }
    }
}

$pelanggaranSelectList = [];
$result = $conn->query("SELECT p.id_pelanggaran, s.nama, s.kelas, j.nama_jenis, p.tanggal FROM pelanggaran p JOIN siswa s ON s.id_siswa = p.id_siswa JOIN jenis_pelanggaran j ON j.id_jenis = p.id_jenis ORDER BY p.tanggal DESC, p.id_pelanggaran DESC LIMIT 100");
if ($result) $pelanggaranSelectList = $result->fetch_all(MYSQLI_ASSOC);

$perPage = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$totalRows = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM surat_orang_tua");
if ($result) $totalRows = (int)$result->fetch_assoc()['total'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$getPageNumbers = function (int $current, int $total): array {
    $window = 5;
    $start = max(1, $current - 2);
    $end = min($total, $start + $window - 1);
    $start = max(1, $end - $window + 1);
    return range($start, $end);
};

$suratList = [];
$stmt = $conn->prepare("SELECT so.id_surat_orang_tua, so.tanggal_cetak, so.status_kirim, so.level_sp, so.nomor_surat, p.id_pelanggaran, s.nama, s.kelas, j.nama_jenis FROM surat_orang_tua so JOIN pelanggaran p ON p.id_pelanggaran = so.id_pelanggaran JOIN siswa s ON s.id_siswa = p.id_siswa JOIN jenis_pelanggaran j ON j.id_jenis = p.id_jenis ORDER BY so.tanggal_cetak DESC, so.id_surat_orang_tua DESC LIMIT ? OFFSET ?");
if ($stmt) {
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) $suratList = $result->fetch_all(MYSQLI_ASSOC);
}

$title = 'Surat Pelanggaran - E-Disiplin';
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
                        <p class="text-xs text-gray-500">Surat Pelanggaran</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center bg-gray-100 rounded-lg px-3 py-2 w-64">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="searchSurat" placeholder="Cari surat..." class="bg-transparent ml-2 outline-none w-full text-sm" />
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 dock-safe">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Buat Surat Pelanggaran</h2>
            <p class="text-gray-600 mt-1">Pilih pelanggaran untuk membuat surat.</p>
        </div>

        <?php if ($alertMessage): ?>
            <div class="mb-6 rounded-xl border <?php echo $alertType === 'success' ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'; ?> p-4 text-sm <?php echo $alertType === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
                <?php echo htmlspecialchars($alertMessage); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-8">
                        <label class="text-sm font-medium text-gray-700">Pilih Pelanggaran</label>
                        <select name="id_pelanggaran" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Pilih Pelanggaran --</option>
                            <?php foreach ($pelanggaranSelectList as $row): ?>
                                <option value="<?php echo (int)$row['id_pelanggaran']; ?>">
                                    <?php echo htmlspecialchars($row['tanggal'] . ' - ' . $row['nama'] . ' (' . $row['kelas'] . ') - ' . $row['nama_jenis']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-4">
                        <label class="text-sm font-medium text-gray-700">Level Surat</label>
                        <select name="level_sp" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="SP1">SP 1 - Pelanggaran Ringan</option>
                            <option value="SP2">SP 2 - Pelanggaran Sedang</option>
                            <option value="SP3">SP 3 - Pelanggaran Berat</option>
                            <option value="LE">LE - Level Ekstensif</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="buat_surat" class="w-full bg-blue-600 text-white text-sm font-medium py-2 rounded-lg hover:bg-blue-700 transition">Buat Surat</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Surat Pelanggaran Terbaru</h3>
                <span class="text-xs text-gray-500">Data per halaman</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" id="tableSurat">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4">Tanggal Cetak</th>
                            <th class="py-2 pr-4">Siswa</th>
                            <th class="py-2 pr-4">Kelas</th>
                            <th class="py-2 pr-4">Jenis</th>
                            <th class="py-2 pr-4">Level SP</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <?php if (empty($suratList)): ?>
                            <tr><td colspan="7" class="py-3 text-gray-500">Belum ada surat pelanggaran.</td></tr>
                        <?php else: ?>
                            <?php foreach ($suratList as $row): ?>
                                <tr class="border-t border-gray-100">
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['tanggal_cetak']); ?></td>
                                    <td class="py-2 pr-4 font-medium text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['kelas']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['nama_jenis']); ?></td>
                                    <td class="py-2 pr-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php 
                                            $levelSp = $row['level_sp'] ?? 'SP1';
                                            if ($levelSp === 'SP1') echo 'bg-blue-100 text-blue-800';
                                            elseif ($levelSp === 'SP2') echo 'bg-yellow-100 text-yellow-800';
                                            elseif ($levelSp === 'SP3') echo 'bg-orange-100 text-orange-800';
                                            else echo 'bg-red-100 text-red-800';
                                            ?>">
                                            <?php echo htmlspecialchars($row['level_sp'] ?? 'SP1'); ?>
                                        </span>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                            <?php echo htmlspecialchars($row['status_kirim'] ?: 'Belum Dikirim'); ?>
                                        </span>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <div class="flex items-center gap-3">
                                            <a href="surat_print.php?id=<?php echo (int)$row['id_surat_orang_tua']; ?>" target="_blank" class="text-blue-600 hover:text-blue-700" title="Print / PDF">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M6 9V2h12v7" />
                                                    <path d="M6 18h12v4H6z" />
                                                    <path d="M6 13h12" />
                                                    <path d="M6 14h12" />
                                                </svg>
                                            </a>
                                            <button type="button" class="text-indigo-600 hover:text-indigo-700" title="Status" onclick='openSuratEdit(<?php echo json_encode($row); ?>)'>
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 6v6l4 2" />
                                                    <circle cx="12" cy="12" r="9" />
                                                </svg>
                                            </button>
                                            <button type="button" class="text-red-600 hover:text-red-700" title="Hapus" onclick="openSuratDelete(<?php echo (int)$row['id_surat_orang_tua']; ?>)">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M3 6h18" />
                                                    <path d="M8 6V4h8v2" />
                                                    <path d="M19 6l-1 14H6L5 6" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between mt-6 text-sm">
                <span class="text-gray-500">Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?></span>
                <div class="flex items-center gap-2">
                    <a href="?page=<?php echo max(1, $page - 1); ?>" class="px-3 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 <?php echo $page <= 1 ? 'pointer-events-none opacity-50' : ''; ?>">Sebelumnya</a>
                    <?php foreach ($getPageNumbers($page, $totalPages) as $p): ?>
                        <a href="?page=<?php echo $p; ?>" class="px-3 py-1 rounded-lg border <?php echo $p === $page ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endforeach; ?>
                    <a href="?page=<?php echo min($totalPages, $page + 1); ?>" class="px-3 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 <?php echo $page >= $totalPages ? 'pointer-events-none opacity-50' : ''; ?>">Berikutnya</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="suratEditModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Ubah Status Surat</h3>
            <button type="button" onclick="closeModal('suratEditModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="update_surat" />
            <input type="hidden" name="id_surat_orang_tua" id="edit_id_surat" />
            <div class="modal-body">
                <label class="text-sm text-gray-600">Status Kirim</label>
                <select name="status_kirim" id="edit_status_surat" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="Belum Dikirim">Belum Dikirim</option>
                    <option value="Terkirim">Terkirim</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('suratEditModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-blue-600 text-white">Update</button>
            </div>
        </form>
    </div>
</div>

<div id="suratDeleteModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Hapus Surat</h3>
            <button type="button" onclick="closeModal('suratDeleteModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="delete_surat" />
            <input type="hidden" name="id_surat_orang_tua" id="delete_id_surat" />
            <div class="modal-body">
                <p class="text-sm text-gray-600">Yakin ingin menghapus surat ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('suratDeleteModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-red-600 text-white">Hapus</button>
            </div>
        </form>
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

<script>
const searchSurat = document.getElementById('searchSurat');
const tableSurat = document.getElementById('tableSurat');
if (searchSurat && tableSurat) {
    searchSurat.addEventListener('input', () => {
        const query = searchSurat.value.toLowerCase();
        const rows = tableSurat.querySelectorAll('tbody tr');
        rows.forEach((row) => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
}
</script>

<script>
const openModal = (id) => {
    const el = document.getElementById(id);
    if (el) el.classList.add('is-open');
};

const closeModal = (id) => {
    const el = document.getElementById(id);
    if (el) el.classList.remove('is-open');
};

const openSuratEdit = (row) => {
    document.getElementById('edit_id_surat').value = row.id_surat_orang_tua;
    document.getElementById('edit_status_surat').value = row.status_kirim || 'Belum Dikirim';
    openModal('suratEditModal');
};

const openSuratDelete = (id) => {
    document.getElementById('delete_id_surat').value = id;
    openModal('suratDeleteModal');
};
</script>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
