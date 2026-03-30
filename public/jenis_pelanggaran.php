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

$_dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0];
$_addCol = function(string $table, string $col, string $def) use ($conn, $_dbName): void {
    $r = $conn->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='{$_dbName}' AND TABLE_NAME='{$table}' AND COLUMN_NAME='{$col}'");
    if ($r && $r->fetch_row()[0] == 0) $conn->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
};
$_addCol('jenis_pelanggaran', 'deskripsi', 'TEXT DEFAULT NULL');

$alertMessage = null;
$alertType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token verification (CRITICAL SECURITY CHECK)
    if (!SecurityHelper::verifyCSRFToken()) {
        $alertMessage = 'Akses ditolak: token tidak valid. Silakan muat ulang halaman.';
        $alertType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

    if ($action === 'create_jenis') {
        $namaJenis = trim($_POST['nama_jenis'] ?? '');
        $poin = (int)($_POST['poin'] ?? 0);
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if ($namaJenis === '' || $poin <= 0) {
            $alertMessage = 'Nama jenis dan poin wajib diisi (poin harus lebih dari 0).';
            $alertType = 'error';
        } else {
            $stmt = $conn->prepare('INSERT INTO jenis_pelanggaran (nama_jenis, poin, deskripsi) VALUES (?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('sis', $namaJenis, $poin, $deskripsi);
                if ($stmt->execute()) {
                    SecurityHelper::auditLog($conn, 'CREATE', 'jenis_pelanggaran', $conn->insert_id, "Nama: $namaJenis, Poin: $poin");
                    $alertMessage = 'Jenis pelanggaran berhasil ditambahkan.';
                    $alertType = 'success';
                } else {
                    $alertMessage = 'Gagal menambahkan jenis pelanggaran.';
                    $alertType = 'error';
                }
            }
        }
    }

    if ($action === 'update_jenis') {
        $id = (int)($_POST['id_jenis'] ?? 0);
        $namaJenis = trim($_POST['nama_jenis'] ?? '');
        $poin = (int)($_POST['poin'] ?? 0);
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if ($id <= 0 || $namaJenis === '' || $poin <= 0) {
            $alertMessage = 'Data jenis pelanggaran belum lengkap.';
            $alertType = 'error';
        } else {
            $stmt = $conn->prepare('UPDATE jenis_pelanggaran SET nama_jenis = ?, poin = ?, deskripsi = ? WHERE id_jenis = ?');
            if ($stmt) {
                $stmt->bind_param('sisi', $namaJenis, $poin, $deskripsi, $id);
                if ($stmt->execute()) {
                    SecurityHelper::auditLog($conn, 'UPDATE', 'jenis_pelanggaran', $id, "Nama: $namaJenis, Poin: $poin");
                    $alertMessage = 'Jenis pelanggaran berhasil diperbarui.';
                    $alertType = 'success';
                } else {
                    $alertMessage = 'Gagal memperbarui jenis pelanggaran.';
                    $alertType = 'error';
                }
            }
        }
    }

    if ($action === 'delete_jenis') {
        $id = (int)($_POST['id_jenis'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM jenis_pelanggaran WHERE id_jenis = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    SecurityHelper::auditLog($conn, 'DELETE', 'jenis_pelanggaran', $id, '');
                    $alertMessage = 'Jenis pelanggaran berhasil dihapus.';
                    $alertType = 'success';
                } else {
                    $alertMessage = 'Gagal menghapus jenis pelanggaran.';
                    $alertType = 'error';
                }
            }
        }
    }
    }
}

$perPage = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$totalRows = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM jenis_pelanggaran");
if ($result) $totalRows = (int)$result->fetch_assoc()['total'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$getPageNumbers = function (int $current, int $total): array {
    $window = 5;
    $start = max(1, $current - 2);
    $end = min($total, $start + $window - 1);
    $start = max(1, $end - $window + 1);
    return range($start, $end);
};

$jenisList = [];
$stmt = $conn->prepare("SELECT id_jenis, nama_jenis, poin, deskripsi FROM jenis_pelanggaran ORDER BY id_jenis DESC LIMIT ? OFFSET ?");
if ($stmt) {
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) $jenisList = $result->fetch_all(MYSQLI_ASSOC);
}

$title = 'Jenis Pelanggaran - E-Disiplin';
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
                        <p class="text-xs text-gray-500">Jenis Pelanggaran</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center bg-gray-100 rounded-lg px-3 py-2 w-64">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="searchJenis" placeholder="Cari jenis pelanggaran..." class="bg-transparent ml-2 outline-none w-full text-sm" />
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
                    <h2 class="text-2xl font-bold text-gray-900">Daftar Jenis Pelanggaran</h2>
                    <p class="text-gray-600 mt-1">Menampilkan data jenis pelanggaran per halaman.</p>
                </div>
                <button onclick="openJenisCreate()" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Tambah Jenis</button>
            </div>
        </div>

        <?php if ($alertMessage): ?>
            <div class="mb-6 rounded-xl border <?php echo $alertType === 'success' ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'; ?> p-4 text-sm <?php echo $alertType === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
                <?php echo htmlspecialchars($alertMessage); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" id="tableJenis">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4">No</th>
                            <th class="py-2 pr-4">Nama Jenis</th>
                            <th class="py-2 pr-4">Poin</th>
                            <th class="py-2 pr-4">Deskripsi</th>
                            <th class="py-2 pr-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <?php if (empty($jenisList)): ?>
                            <tr><td colspan="5" class="py-3 text-gray-500">Belum ada data jenis pelanggaran.</td></tr>
                        <?php else: ?>
                            <?php foreach ($jenisList as $idx => $row): ?>
                                <tr class="border-t border-gray-100">
                                    <td class="py-2 pr-4"><?php echo $offset + $idx + 1; ?></td>
                                    <td class="py-2 pr-4 font-medium text-gray-900"><?php echo htmlspecialchars($row['nama_jenis']); ?></td>
                                    <td class="py-2 pr-4"><?php echo (int)$row['poin']; ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['deskripsi'] ?? ''); ?></td>
                                    <td class="py-2 pr-4">
                                        <div class="flex items-center gap-3">
                                            <button type="button" class="text-blue-600 hover:text-blue-700" title="Edit" onclick='openJenisEdit(<?php echo json_encode($row); ?>)'>
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 20h9" />
                                                    <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="text-red-600 hover:text-red-700" title="Hapus" onclick="openJenisDelete(<?php echo (int)$row['id_jenis']; ?>, '<?php echo htmlspecialchars($row['nama_jenis'], ENT_QUOTES); ?>')">
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

<div id="jenisCreateModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Tambah Jenis Pelanggaran</h3>
            <button type="button" onclick="closeModal('jenisCreateModal')">&#10005;</button>
        </div>
        <form method="POST">
            <div class="modal-body grid grid-cols-1 gap-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <input type="hidden" name="action" value="create_jenis" />
                <div>
                    <label class="text-sm text-gray-600">Nama Jenis</label>
                    <input name="nama_jenis" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Poin</label>
                    <input name="poin" type="number" min="1" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('jenisCreateModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-blue-600 text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="jenisEditModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Edit Jenis Pelanggaran</h3>
            <button type="button" onclick="closeModal('jenisEditModal')">&#10005;</button>
        </div>
        <form method="POST">
            <div class="modal-body grid grid-cols-1 gap-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <input type="hidden" name="action" value="update_jenis" />
                <input type="hidden" name="id_jenis" id="edit_id_jenis" />
                <div>
                    <label class="text-sm text-gray-600">Nama Jenis</label>
                    <input name="nama_jenis" id="edit_nama_jenis" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Poin</label>
                    <input name="poin" id="edit_poin" type="number" min="1" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" rows="3" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('jenisEditModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-blue-600 text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="jenisDeleteModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Hapus Jenis Pelanggaran</h3>
            <button type="button" onclick="closeModal('jenisDeleteModal')">&#10005;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
            <input type="hidden" name="action" value="delete_jenis" />
            <input type="hidden" name="id_jenis" id="delete_id_jenis" />
            <div class="modal-body">
                <p class="text-sm text-gray-600">Yakin ingin menghapus <span id="delete_name" class="font-semibold text-gray-900"></span>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('jenisDeleteModal')">Batal</button>
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
const openModal = (id) => {
    const el = document.getElementById(id);
    if (el) el.classList.add('is-open');
};

const closeModal = (id) => {
    const el = document.getElementById(id);
    if (el) el.classList.remove('is-open');
};

const openJenisCreate = () => openModal('jenisCreateModal');

const openJenisEdit = (row) => {
    document.getElementById('edit_id_jenis').value = row.id_jenis;
    document.getElementById('edit_nama_jenis').value = row.nama_jenis || '';
    document.getElementById('edit_poin').value = row.poin || '';
    document.getElementById('edit_deskripsi').value = row.deskripsi || '';
    openModal('jenisEditModal');
};

const openJenisDelete = (id, name) => {
    document.getElementById('delete_id_jenis').value = id;
    document.getElementById('delete_name').textContent = name;
    openModal('jenisDeleteModal');
};
</script>

<script>
const searchJenis = document.getElementById('searchJenis');
const tableJenis = document.getElementById('tableJenis');
if (searchJenis && tableJenis) {
    searchJenis.addEventListener('input', () => {
        const query = searchJenis.value.toLowerCase();
        const rows = tableJenis.querySelectorAll('tbody tr');
        rows.forEach((row) => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
}
</script>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
