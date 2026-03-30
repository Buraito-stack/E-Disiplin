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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token verification (CRITICAL SECURITY CHECK)
    if (!SecurityHelper::verifyCSRFToken()) {
        $alertMessage = 'Akses ditolak: token tidak valid. Silakan muat ulang halaman.';
        $alertType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

    if ($action === 'create_siswa') {
        $nama = trim($_POST['nama'] ?? '');
        $nis = trim($_POST['nis'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $namaOrtu = trim($_POST['nama_orang_tua'] ?? '');
        $kontakOrtu = trim($_POST['kontak_orang_tua'] ?? '');
        $pekerjaanOrtu = trim($_POST['pekerjaan_orang_tua'] ?? '');
        $alamatOrtu = trim($_POST['alamat_orang_tua'] ?? '');

        if ($nama === '' || $nis === '' || $kelas === '') {
            $alertMessage = 'Nama, NIS, dan kelas wajib diisi.';
            $alertType = 'error';
        } else {
            $check = $conn->prepare('SELECT id_siswa FROM siswa WHERE nis = ? LIMIT 1');
            if ($check) {
                $check->bind_param('s', $nis);
                $check->execute();
                $exists = $check->get_result();
                if ($exists && $exists->num_rows > 0) {
                    $alertMessage = 'NIS sudah digunakan.';
                    $alertType = 'error';
                } else {
                    $stmt = $conn->prepare('INSERT INTO siswa (nama, nis, kelas, alamat, nama_orang_tua, kontak_orang_tua, pekerjaan_orang_tua, alamat_orang_tua) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    if ($stmt) {
                        $stmt->bind_param('ssssssss', $nama, $nis, $kelas, $alamat, $namaOrtu, $kontakOrtu, $pekerjaanOrtu, $alamatOrtu);
                        if ($stmt->execute()) {
                            SecurityHelper::auditLog($conn, 'CREATE', 'siswa', $conn->insert_id, "NIS: $nis, Nama: $nama");
                            $alertMessage = 'Siswa berhasil ditambahkan.';
                            $alertType = 'success';
                        } else {
                            $alertMessage = 'Gagal menambahkan siswa.';
                            $alertType = 'error';
                        }
                    }
                }
            }
        }
    }

    if ($action === 'update_siswa') {
        $id = (int)($_POST['id_siswa'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $nis = trim($_POST['nis'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $namaOrtu = trim($_POST['nama_orang_tua'] ?? '');
        $kontakOrtu = trim($_POST['kontak_orang_tua'] ?? '');
        $pekerjaanOrtu = trim($_POST['pekerjaan_orang_tua'] ?? '');
        $alamatOrtu = trim($_POST['alamat_orang_tua'] ?? '');

        if ($id <= 0 || $nama === '' || $nis === '' || $kelas === '') {
            $alertMessage = 'Data siswa belum lengkap.';
            $alertType = 'error';
        } else {
            $check = $conn->prepare('SELECT id_siswa FROM siswa WHERE nis = ? AND id_siswa != ? LIMIT 1');
            if ($check) {
                $check->bind_param('si', $nis, $id);
                $check->execute();
                $exists = $check->get_result();
                if ($exists && $exists->num_rows > 0) {
                    $alertMessage = 'NIS sudah digunakan.';
                    $alertType = 'error';
                } else {
                    $stmt = $conn->prepare('UPDATE siswa SET nama = ?, nis = ?, kelas = ?, alamat = ?, nama_orang_tua = ?, kontak_orang_tua = ?, pekerjaan_orang_tua = ?, alamat_orang_tua = ? WHERE id_siswa = ?');
                    if ($stmt) {
                        $stmt->bind_param('ssssssssi', $nama, $nis, $kelas, $alamat, $namaOrtu, $kontakOrtu, $pekerjaanOrtu, $alamatOrtu, $id);
                        if ($stmt->execute()) {
                            SecurityHelper::auditLog($conn, 'UPDATE', 'siswa', $id, "NIS: $nis, Nama: $nama");
                            $alertMessage = 'Data siswa berhasil diperbarui.';
                            $alertType = 'success';
                        } else {
                            $alertMessage = 'Tidak berhasil mempebaharui data siswa';
                            $alertType = 'error';
                        }
                    }
                }
            }
        }
    }

    if ($action === 'delete_siswa') {
        $id = (int)($_POST['id_siswa'] ?? 0);
        if ($id > 0) {
            $conn->begin_transaction();
            try {
                // Delete surat_orang_tua linked via pelanggaran
                $stmt = $conn->prepare('DELETE so FROM surat_orang_tua so JOIN pelanggaran p ON p.id_pelanggaran = so.id_pelanggaran WHERE p.id_siswa = ?');
                if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); }

                // Delete pelanggaran
                $stmt = $conn->prepare('DELETE FROM pelanggaran WHERE id_siswa = ?');
                if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); }

                // Delete surat_perjanjian
                $stmt = $conn->prepare('DELETE FROM surat_perjanjian WHERE id_siswa = ?');
                if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); }

                // Delete surat_pindah
                $stmt = $conn->prepare('DELETE FROM surat_pindah WHERE id_siswa = ?');
                if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); }

                // Delete siswa
                $stmt = $conn->prepare('DELETE FROM siswa WHERE id_siswa = ?');
                if ($stmt) {
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                }

                $conn->commit();
                SecurityHelper::auditLog($conn, 'DELETE', 'siswa', $id, 'Cascade delete termasuk pelanggaran & surat');
                $alertMessage = 'Siswa dan semua data terkait berhasil dihapus.';
                $alertType = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $alertMessage = 'Gagal menghapus siswa: ' . $e->getMessage();
                $alertType = 'error';
            }
        }
    }

    if ($action === 'import_csv') {
        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            if ($handle) {
                $header = fgetcsv($handle);
                if (!$header) {
                    $alertMessage = 'File CSV kosong.';
                    $alertType = 'error';
                } else {
                    $header = array_map(function($h) { return strtolower(trim($h)); }, $header);
                    $required = ['nama', 'nis', 'kelas'];
                    $missing = array_diff($required, $header);
                    if (!empty($missing)) {
                        $alertMessage = 'Kolom wajib tidak ditemukan: ' . implode(', ', $missing) . '. Header harus: nama, nis, kelas';
                        $alertType = 'error';
                    } else {
                        $imported = 0;
                        $skipped = 0;
                        $stmtCheck = $conn->prepare('SELECT id_siswa FROM siswa WHERE nis = ? LIMIT 1');
                        $stmtInsert = $conn->prepare('INSERT INTO siswa (nama, nis, kelas, alamat, nama_orang_tua, kontak_orang_tua, pekerjaan_orang_tua, alamat_orang_tua) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

                        while (($row = fgetcsv($handle)) !== false) {
                            if (count($row) < count($header)) continue;
                            $data = array_combine($header, $row);
                            $nama = trim($data['nama'] ?? '');
                            $nis = trim($data['nis'] ?? '');
                            $kelas = trim($data['kelas'] ?? '');
                            if ($nama === '' || $nis === '' || $kelas === '') { $skipped++; continue; }

                            $stmtCheck->bind_param('s', $nis);
                            $stmtCheck->execute();
                            if ($stmtCheck->get_result()->num_rows > 0) { $skipped++; continue; }

                            $alamat = trim($data['alamat'] ?? '');
                            $namaOrtu = trim($data['nama_orang_tua'] ?? '');
                            $kontakOrtu = trim($data['kontak_orang_tua'] ?? '');
                            $pekerjaanOrtu = trim($data['pekerjaan_orang_tua'] ?? '');
                            $alamatOrtu = trim($data['alamat_orang_tua'] ?? '');
                            $stmtInsert->bind_param('ssssssss', $nama, $nis, $kelas, $alamat, $namaOrtu, $kontakOrtu, $pekerjaanOrtu, $alamatOrtu);
                            if ($stmtInsert->execute()) { $imported++; } else { $skipped++; }
                        }
                        SecurityHelper::auditLog($conn, 'IMPORT_CSV', 'siswa', null, "Imported: $imported, Skipped: $skipped");
                        $alertMessage = "Import selesai: $imported data berhasil, $skipped dilewati (duplikat/kosong).";
                        $alertType = 'success';
                    }
                }
                fclose($handle);
            }
        } else {
            $alertMessage = 'Pilih file CSV terlebih dahulu.';
            $alertType = 'error';
        }
    }
    }
}

$perPage = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$totalRows = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM siswa");
if ($result) $totalRows = (int)$result->fetch_assoc()['total'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$getPageNumbers = function (int $current, int $total): array {
    $window = 5;
    $start = max(1, $current - 2);
    $end = min($total, $start + $window - 1);
    $start = max(1, $end - $window + 1);
    return range($start, $end);
};

$siswaList = [];
$stmt = $conn->prepare("SELECT id_siswa, nama, nis, kelas, alamat, nama_orang_tua, kontak_orang_tua, pekerjaan_orang_tua, alamat_orang_tua FROM siswa ORDER BY id_siswa DESC LIMIT ? OFFSET ?");
if ($stmt) {
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) $siswaList = $result->fetch_all(MYSQLI_ASSOC);
}

$title = 'Data Siswa - E-Disiplin';
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
                        <p class="text-xs text-gray-500">Data Siswa</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center bg-gray-100 rounded-lg px-3 py-2 w-64">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="searchSiswa" placeholder="Cari siswa..." class="bg-transparent ml-2 outline-none w-full text-sm" />
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
                    <h2 class="text-2xl font-bold text-gray-900">Daftar Siswa</h2>
                    <p class="text-gray-600 mt-1">Menampilkan data siswa per halaman.</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="openModal('importCsvModal')" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50">Import CSV</button>
                    <button onclick="openSiswaCreate()" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Tambah Siswa</button>
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
                <table class="min-w-full text-sm" id="tableSiswa">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4">NIS</th>
                            <th class="py-2 pr-4">Nama</th>
                            <th class="py-2 pr-4">Kelas</th>
                            <th class="py-2 pr-4">Alamat</th>
                            <th class="py-2 pr-4">Orang Tua</th>
                            <th class="py-2 pr-4">Kontak</th>
                            <th class="py-2 pr-4">Pekerjaan</th>
                            <th class="py-2 pr-4">Alamat Orang Tua</th>
                            <th class="py-2 pr-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <?php if (empty($siswaList)): ?>
                            <tr><td colspan="9" class="py-3 text-gray-500">Belum ada data siswa.</td></tr>
                        <?php else: ?>
                            <?php foreach ($siswaList as $row): ?>
                                <tr class="border-t border-gray-100">
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['nis']); ?></td>
                                    <td class="py-2 pr-4 font-medium text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['kelas']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['alamat']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['nama_orang_tua']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['kontak_orang_tua']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['pekerjaan_orang_tua']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['alamat_orang_tua']); ?></td>
                                    <td class="py-2 pr-4">
                                        <div class="flex items-center gap-3">
                                            <button type="button" class="text-blue-600 hover:text-blue-700" title="Edit" onclick='openSiswaEdit(<?php echo json_encode($row); ?>)'>
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 20h9" />
                                                    <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="text-red-600 hover:text-red-700" title="Hapus" onclick="openSiswaDelete(<?php echo (int)$row['id_siswa']; ?>, '<?php echo htmlspecialchars($row['nama'], ENT_QUOTES); ?>')">
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

<div id="siswaCreateModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Tambah Siswa</h3>
            <button type="button" onclick="closeModal('siswaCreateModal')">✕</button>
        </div>
        <form method="POST">
            <div class="modal-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <input type="hidden" name="action" value="create_siswa" />
                <div>
                    <label class="text-sm text-gray-600">Nama</label>
                    <input name="nama" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">NIS</label>
                    <input name="nis" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Kelas</label>
                    <input name="kelas" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Alamat</label>
                    <input name="alamat" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Nama Orang Tua</label>
                    <input name="nama_orang_tua" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Kontak Orang Tua</label>
                    <input name="kontak_orang_tua" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Pekerjaan Orang Tua</label>
                    <input name="pekerjaan_orang_tua" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Alamat Orang Tua</label>
                    <input name="alamat_orang_tua" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('siswaCreateModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-blue-600 text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="siswaEditModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Edit Siswa</h3>
            <button type="button" onclick="closeModal('siswaEditModal')">✕</button>
        </div>
        <form method="POST">
            <div class="modal-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <input type="hidden" name="action" value="update_siswa" />
                <input type="hidden" name="id_siswa" id="edit_id_siswa" />
                <div>
                    <label class="text-sm text-gray-600">Nama</label>
                    <input name="nama" id="edit_nama" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">NIS</label>
                    <input name="nis" id="edit_nis" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Kelas</label>
                    <input name="kelas" id="edit_kelas" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Alamat</label>
                    <input name="alamat" id="edit_alamat" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Nama Orang Tua</label>
                    <input name="nama_orang_tua" id="edit_nama_orang_tua" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Kontak Orang Tua</label>
                    <input name="kontak_orang_tua" id="edit_kontak_orang_tua" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Pekerjaan Orang Tua</label>
                    <input name="pekerjaan_orang_tua" id="edit_pekerjaan_orang_tua" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Alamat Orang Tua</label>
                    <input name="alamat_orang_tua" id="edit_alamat_orang_tua" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('siswaEditModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-blue-600 text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="siswaDeleteModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Hapus Siswa</h3>
            <button type="button" onclick="closeModal('siswaDeleteModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
            <input type="hidden" name="action" value="delete_siswa" />
            <input type="hidden" name="id_siswa" id="delete_id_siswa" />
            <div class="modal-body">
                <p class="text-sm text-gray-600">Yakin ingin menghapus <span id="delete_name" class="font-semibold text-gray-900"></span>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('siswaDeleteModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-red-600 text-white">Hapus</button>
            </div>
        </form>
    </div>
</div>

<div id="importCsvModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Import Data Siswa (CSV)</h3>
            <button type="button" onclick="closeModal('importCsvModal')">&#10005;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <input type="hidden" name="action" value="import_csv" />
                <div>
                    <label class="text-sm text-gray-600">File CSV</label>
                    <input type="file" name="csv_file" accept=".csv" required class="mt-1 w-full text-sm border border-gray-200 rounded-lg px-3 py-2" />
                </div>
                <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
                    <p class="text-xs font-semibold text-blue-900 mb-1">Format CSV:</p>
                    <p class="text-xs text-blue-700">Header wajib: <strong>nama, nis, kelas</strong></p>
                    <p class="text-xs text-blue-700">Opsional: alamat, nama_orang_tua, kontak_orang_tua, pekerjaan_orang_tua, alamat_orang_tua</p>
                    <p class="text-xs text-blue-600 mt-1">NIS yang sudah ada akan dilewati (tidak duplikat).</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('importCsvModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-green-600 text-white">Import</button>
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

const openSiswaCreate = () => openModal('siswaCreateModal');

const openSiswaEdit = (row) => {
    document.getElementById('edit_id_siswa').value = row.id_siswa;
    document.getElementById('edit_nama').value = row.nama || '';
    document.getElementById('edit_nis').value = row.nis || '';
    document.getElementById('edit_kelas').value = row.kelas || '';
    document.getElementById('edit_alamat').value = row.alamat || '';
    document.getElementById('edit_nama_orang_tua').value = row.nama_orang_tua || '';
    document.getElementById('edit_kontak_orang_tua').value = row.kontak_orang_tua || '';
    document.getElementById('edit_pekerjaan_orang_tua').value = row.pekerjaan_orang_tua || '';
    document.getElementById('edit_alamat_orang_tua').value = row.alamat_orang_tua || '';
    openModal('siswaEditModal');
};

const openSiswaDelete = (id, name) => {
    document.getElementById('delete_id_siswa').value = id;
    document.getElementById('delete_name').textContent = name;
    openModal('siswaDeleteModal');
};
</script>

<script>
const searchSiswa = document.getElementById('searchSiswa');
const tableSiswa = document.getElementById('tableSiswa');
if (searchSiswa && tableSiswa) {
    searchSiswa.addEventListener('input', () => {
        const query = searchSiswa.value.toLowerCase();
        const rows = tableSiswa.querySelectorAll('tbody tr');
        rows.forEach((row) => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
}
</script>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
