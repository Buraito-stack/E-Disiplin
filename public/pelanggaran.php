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

requireRoles(['admin', 'guru', 'bk', 'guru_mapel']);

$user = $_SESSION;

$alertMessage = null;
$alertType = 'success';

$siswaOptions = [];
$jenisOptions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token verification (CRITICAL SECURITY CHECK)
    if (!SecurityHelper::verifyCSRFToken()) {
        $alertMessage = 'Akses ditolak: token tidak valid. Silakan muat ulang halaman.';
        $alertType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

    if ($action === 'create_pelanggaran') {
        $idSiswa = (int)($_POST['id_siswa'] ?? 0);
        $idJenis = (int)($_POST['id_jenis'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? '';
        $keterangan = trim($_POST['keterangan'] ?? '');

        if ($idSiswa <= 0 || $idJenis <= 0 || $tanggal === '') {
            $alertMessage = 'Siswa, jenis, dan tanggal wajib diisi.';
            $alertType = 'error';
        } else {
            $stmt = $conn->prepare('INSERT INTO pelanggaran (id_siswa, id_jenis, tanggal, keterangan) VALUES (?, ?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('iiss', $idSiswa, $idJenis, $tanggal, $keterangan);
                if ($stmt->execute()) {
                    SecurityHelper::auditLog($conn, 'CREATE', 'pelanggaran', $conn->insert_id, "Siswa ID: $idSiswa, Jenis ID: $idJenis");
                    $alertMessage = 'Pelanggaran berhasil ditambahkan.';
                    $alertType = 'success';
                } else {
                    $alertMessage = 'Gagal menambahkan pelanggaran.';
                    $alertType = 'error';
                }
            }
        }
    }

    if ($action === 'update_pelanggaran') {
        $id = (int)($_POST['id_pelanggaran'] ?? 0);
        $idSiswa = (int)($_POST['id_siswa'] ?? 0);
        $idJenis = (int)($_POST['id_jenis'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? '';
        $keterangan = trim($_POST['keterangan'] ?? '');

        if ($id <= 0 || $idSiswa <= 0 || $idJenis <= 0 || $tanggal === '') {
            $alertMessage = 'Data pelanggaran belum lengkap.';
            $alertType = 'error';
        } else {
            $stmt = $conn->prepare('UPDATE pelanggaran SET id_siswa = ?, id_jenis = ?, tanggal = ?, keterangan = ? WHERE id_pelanggaran = ?');
            if ($stmt) {
                $stmt->bind_param('iissi', $idSiswa, $idJenis, $tanggal, $keterangan, $id);
                if ($stmt->execute()) {
                    SecurityHelper::auditLog($conn, 'UPDATE', 'pelanggaran', $id, "Siswa ID: $idSiswa, Jenis ID: $idJenis");
                    $alertMessage = 'Pelanggaran berhasil diperbarui.';
                    $alertType = 'success';
                } else {
                    $alertMessage = 'Gagal memperbarui pelanggaran.';
                    $alertType = 'error';
                }
            }
        }
    }

    if ($action === 'delete_pelanggaran') {
        $id = (int)($_POST['id_pelanggaran'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM pelanggaran WHERE id_pelanggaran = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    SecurityHelper::auditLog($conn, 'DELETE', 'pelanggaran', $id, '');
                    $alertMessage = 'Pelanggaran berhasil dihapus.';
                    $alertType = 'success';
                } else {
                    $alertMessage = 'Gagal menghapus pelanggaran.';
                    $alertType = 'error';
                }
            }
        }
    }

    if ($action === 'bulk_delete') {
        $ids = $_POST['bulk_ids'] ?? '';
        $idArray = array_filter(array_map('intval', explode(',', $ids)));
        if (!empty($idArray)) {
            // Delete related surat_orang_tua first
            $placeholders = implode(',', array_fill(0, count($idArray), '?'));
            $types = str_repeat('i', count($idArray));

            $stmt = $conn->prepare("DELETE FROM surat_orang_tua WHERE id_pelanggaran IN ($placeholders)");
            if ($stmt) { $stmt->bind_param($types, ...$idArray); $stmt->execute(); }

            $stmt = $conn->prepare("DELETE FROM pelanggaran WHERE id_pelanggaran IN ($placeholders)");
            if ($stmt) {
                $stmt->bind_param($types, ...$idArray);
                if ($stmt->execute()) {
                    $count = $stmt->affected_rows;
                    SecurityHelper::auditLog($conn, 'BULK_DELETE', 'pelanggaran', null, "Deleted $count records");
                    $alertMessage = "$count pelanggaran berhasil dihapus.";
                    $alertType = 'success';
                }
            }
        } else {
            $alertMessage = 'Pilih pelanggaran yang ingin dihapus.';
            $alertType = 'error';
        }
    }
    }
}

$result = $conn->query("SELECT id_siswa, nama, kelas FROM siswa ORDER BY nama ASC");
if ($result) $siswaOptions = $result->fetch_all(MYSQLI_ASSOC);

$result = $conn->query("SELECT id_jenis, nama_jenis FROM jenis_pelanggaran ORDER BY nama_jenis ASC");
if ($result) $jenisOptions = $result->fetch_all(MYSQLI_ASSOC);

$perPage = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$totalRows = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM pelanggaran");
if ($result) $totalRows = (int)$result->fetch_assoc()['total'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$getPageNumbers = function (int $current, int $total): array {
    $window = 5;
    $start = max(1, $current - 2);
    $end = min($total, $start + $window - 1);
    $start = max(1, $end - $window + 1);
    return range($start, $end);
};

$pelanggaranList = [];
$stmt = $conn->prepare("SELECT p.id_pelanggaran, p.id_siswa, p.id_jenis, s.nama, s.kelas, j.nama_jenis, j.poin, p.tanggal, p.keterangan FROM pelanggaran p JOIN siswa s ON s.id_siswa = p.id_siswa JOIN jenis_pelanggaran j ON j.id_jenis = p.id_jenis ORDER BY p.tanggal DESC, p.id_pelanggaran DESC LIMIT ? OFFSET ?");
if ($stmt) {
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) $pelanggaranList = $result->fetch_all(MYSQLI_ASSOC);
}

$title = 'Data Pelanggaran - E-Disiplin';
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
                        <p class="text-xs text-gray-500">Data Pelanggaran</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center bg-gray-100 rounded-lg px-3 py-2 w-64">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="searchPelanggaran" placeholder="Cari pelanggaran..." class="bg-transparent ml-2 outline-none w-full text-sm" />
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
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Daftar Pelanggaran</h2>
                    <p class="text-gray-600 mt-1">Menampilkan data pelanggaran per halaman.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="daftar_pelanggaran_print.php" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50">Cetak Daftar</a>
                    <button onclick="openPelanggaranCreate()" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Tambah Pelanggaran</button>
                </div>
            </div>
        </div>

        <?php if ($alertMessage): ?>
            <div class="mb-6 rounded-xl border <?php echo $alertType === 'success' ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'; ?> p-4 text-sm <?php echo $alertType === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
                <?php echo htmlspecialchars($alertMessage); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" id="tablePelanggaran">
                    <div id="bulkBar" class="hidden mb-3 p-3 bg-red-50 border border-red-200 rounded-lg flex items-center justify-between">
                        <span class="text-sm text-red-700"><span id="bulkCount">0</span> data dipilih</span>
                        <button type="button" onclick="bulkDelete()" class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">Hapus Terpilih</button>
                    </div>
                    <form id="bulkForm" method="POST" class="hidden">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                        <input type="hidden" name="action" value="bulk_delete" />
                        <input type="hidden" name="bulk_ids" id="bulkIds" />
                    </form>
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-2 w-8"><input type="checkbox" id="checkAll" class="rounded" /></th>
                            <th class="py-2 pr-4">Tanggal</th>
                            <th class="py-2 pr-4">Siswa</th>
                            <th class="py-2 pr-4">Kelas</th>
                            <th class="py-2 pr-4">Jenis</th>
                            <th class="py-2 pr-4">Poin</th>
                            <th class="py-2 pr-4">Keterangan</th>
                            <th class="py-2 pr-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <?php if (empty($pelanggaranList)): ?>
                            <tr><td colspan="8" class="py-3 text-gray-500">Belum ada data pelanggaran.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pelanggaranList as $row): ?>
                                <tr class="border-t border-gray-100">
                                    <td class="py-2 pr-2"><input type="checkbox" class="bulk-check rounded" value="<?php echo (int)$row['id_pelanggaran']; ?>" /></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['tanggal']); ?></td>
                                    <td class="py-2 pr-4 font-medium text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['kelas']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['nama_jenis']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars((string)$row['poin']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                    <td class="py-2 pr-4">
                                        <div class="flex items-center gap-3">
                                            <button type="button" class="text-blue-600 hover:text-blue-700" title="Edit" onclick='openPelanggaranEdit(<?php echo json_encode($row); ?>)'>
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 20h9" />
                                                    <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="text-red-600 hover:text-red-700" title="Hapus" onclick="openPelanggaranDelete(<?php echo (int)$row['id_pelanggaran']; ?>)">
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

<div id="pelanggaranCreateModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Tambah Pelanggaran</h3>
            <button type="button" onclick="closeModal('pelanggaranCreateModal')">✕</button>
        </div>
        <form method="POST">
            <div class="modal-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <input type="hidden" name="action" value="create_pelanggaran" />
                <div>
                    <label class="text-sm text-gray-600">Siswa</label>
                    <select name="id_siswa" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required>
                        <option value="">-- Pilih Siswa --</option>
                        <?php foreach ($siswaOptions as $s): ?>
                            <option value="<?php echo (int)$s['id_siswa']; ?>"><?php echo htmlspecialchars($s['nama'] . ' (' . $s['kelas'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Jenis Pelanggaran</label>
                    <select name="id_jenis" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required>
                        <option value="">-- Pilih Jenis --</option>
                        <?php foreach ($jenisOptions as $j): ?>
                            <option value="<?php echo (int)$j['id_jenis']; ?>"><?php echo htmlspecialchars($j['nama_jenis']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Tanggal</label>
                    <input type="date" name="tanggal" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Keterangan</label>
                    <input name="keterangan" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('pelanggaranCreateModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-blue-600 text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="pelanggaranEditModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Edit Pelanggaran</h3>
            <button type="button" onclick="closeModal('pelanggaranEditModal')">✕</button>
        </div>
        <form method="POST">
            <div class="modal-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <input type="hidden" name="action" value="update_pelanggaran" />
                <input type="hidden" name="id_pelanggaran" id="edit_id_pelanggaran" />
                <div>
                    <label class="text-sm text-gray-600">Siswa</label>
                    <select name="id_siswa" id="edit_id_siswa" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required>
                        <option value="">-- Pilih Siswa --</option>
                        <?php foreach ($siswaOptions as $s): ?>
                            <option value="<?php echo (int)$s['id_siswa']; ?>"><?php echo htmlspecialchars($s['nama'] . ' (' . $s['kelas'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Jenis Pelanggaran</label>
                    <select name="id_jenis" id="edit_id_jenis" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required>
                        <option value="">-- Pilih Jenis --</option>
                        <?php foreach ($jenisOptions as $j): ?>
                            <option value="<?php echo (int)$j['id_jenis']; ?>"><?php echo htmlspecialchars($j['nama_jenis']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Tanggal</label>
                    <input type="date" name="tanggal" id="edit_tanggal" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Keterangan</label>
                    <input name="keterangan" id="edit_keterangan" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('pelanggaranEditModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-blue-600 text-white">Update</button>
            </div>
        </form>
    </div>
</div>

<div id="pelanggaranDeleteModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Hapus Pelanggaran</h3>
            <button type="button" onclick="closeModal('pelanggaranDeleteModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
            <input type="hidden" name="action" value="delete_pelanggaran" />
            <input type="hidden" name="id_pelanggaran" id="delete_id_pelanggaran" />
            <div class="modal-body">
                <p class="text-sm text-gray-600">Yakin ingin menghapus data pelanggaran ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('pelanggaranDeleteModal')">Batal</button>
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
const searchPelanggaran = document.getElementById('searchPelanggaran');
const tablePelanggaran = document.getElementById('tablePelanggaran');
if (searchPelanggaran && tablePelanggaran) {
    searchPelanggaran.addEventListener('input', () => {
        const query = searchPelanggaran.value.toLowerCase();
        const rows = tablePelanggaran.querySelectorAll('tbody tr');
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

const openPelanggaranCreate = () => openModal('pelanggaranCreateModal');

const openPelanggaranEdit = (row) => {
    document.getElementById('edit_id_pelanggaran').value = row.id_pelanggaran;
    document.getElementById('edit_id_siswa').value = row.id_siswa || '';
    document.getElementById('edit_id_jenis').value = row.id_jenis || '';
    document.getElementById('edit_tanggal').value = row.tanggal || '';
    document.getElementById('edit_keterangan').value = row.keterangan || '';
    openModal('pelanggaranEditModal');
};

const openPelanggaranDelete = (id) => {
    document.getElementById('delete_id_pelanggaran').value = id;
    openModal('pelanggaranDeleteModal');
};
</script>

<script>
const checkAll = document.getElementById('checkAll');
const bulkBar = document.getElementById('bulkBar');
const bulkCount = document.getElementById('bulkCount');

const updateBulkBar = () => {
    const checked = document.querySelectorAll('.bulk-check:checked');
    if (checked.length > 0) {
        bulkBar.classList.remove('hidden');
        bulkCount.textContent = checked.length;
    } else {
        bulkBar.classList.add('hidden');
    }
};

if (checkAll) {
    checkAll.addEventListener('change', () => {
        document.querySelectorAll('.bulk-check').forEach(cb => { cb.checked = checkAll.checked; });
        updateBulkBar();
    });
}

document.querySelectorAll('.bulk-check').forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
});

const bulkDelete = () => {
    const checked = document.querySelectorAll('.bulk-check:checked');
    const ids = Array.from(checked).map(cb => cb.value);
    if (ids.length === 0) return;
    if (!confirm('Yakin ingin menghapus ' + ids.length + ' pelanggaran terpilih?')) return;
    document.getElementById('bulkIds').value = ids.join(',');
    document.getElementById('bulkForm').submit();
};
</script>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
