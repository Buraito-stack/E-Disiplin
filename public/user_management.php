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

requireRoles(['admin']);

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

    if ($action === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'guru';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($username === '' || $password === '' || $name === '') {
            $alertMessage = 'Username, password, dan nama wajib diisi.';
            $alertType = 'error';
        } else {
            $check = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            if ($check) {
                $check->bind_param('s', $username);
                $check->execute();
                $exists = $check->get_result();
                if ($exists && $exists->num_rows > 0) {
                    $alertMessage = 'Username sudah digunakan.';
                    $alertType = 'error';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare('INSERT INTO users (username, password, email, name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)');
                    if ($stmt) {
                        $stmt->bind_param('sssssi', $username, $hash, $email, $name, $role, $isActive);
                        if ($stmt->execute()) {
                            $alertMessage = 'User berhasil dibuat.';
                            $alertType = 'success';
                        } else {
                            $alertMessage = 'Gagal membuat user.';
                            $alertType = 'error';
                        }
                    }
                }
            }
        }
    }

    if ($action === 'update_user') {
        $id = (int)($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? 'guru';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE users SET role = ?, is_active = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('sii', $role, $isActive, $id);
                if ($stmt->execute()) {
                    $alertMessage = 'User berhasil diperbarui.';
                    $alertType = 'success';
                } else {
                    $alertMessage = 'Gagal memperbarui user.';
                    $alertType = 'error';
                }
            }
        }
    }

    if ($action === 'delete_user') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            if ($id === (int)($_SESSION['user_id'] ?? 0)) {
                $alertMessage = 'Tidak bisa menghapus akun sendiri.';
                $alertType = 'error';
            } else {
                $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
                if ($stmt) {
                    $stmt->bind_param('i', $id);
                    if ($stmt->execute()) {
                        $alertMessage = 'User berhasil dihapus.';
                        $alertType = 'success';
                    } else {
                        $alertMessage = 'Gagal menghapus user.';
                        $alertType = 'error';
                    }
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
$result = $conn->query('SELECT COUNT(*) as total FROM users');
if ($result) $totalRows = (int)$result->fetch_assoc()['total'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$getPageNumbers = function (int $current, int $total): array {
    $window = 5;
    $start = max(1, $current - 2);
    $end = min($total, $start + $window - 1);
    $start = max(1, $end - $window + 1);
    return range($start, $end);
};

$users = [];
$stmt = $conn->prepare('SELECT id, username, name, email, role, is_active, created_at FROM users ORDER BY id DESC LIMIT ? OFFSET ?');
if ($stmt) {
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) $users = $result->fetch_all(MYSQLI_ASSOC);
}

$title = 'Kelola User - E-Disiplin';
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
                        <p class="text-xs text-gray-500">Kelola User</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center bg-gray-100 rounded-lg px-3 py-2 w-64">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="searchUser" placeholder="Cari user..." class="bg-transparent ml-2 outline-none w-full text-sm" />
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
                    <h2 class="text-2xl font-bold text-gray-900">Kelola User</h2>
                    <p class="text-gray-600 mt-1">Tambah, ubah role, dan aktif/nonaktif user.</p>
                </div>
                <button onclick="openUserCreate()" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Tambah User</button>
            </div>
        </div>

        <?php if ($alertMessage): ?>
            <div class="mb-6 rounded-xl border <?php echo $alertType === 'success' ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'; ?> p-4 text-sm <?php echo $alertType === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
                <?php echo htmlspecialchars($alertMessage); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Daftar User</h3>
                <span class="text-xs text-gray-500">Total <?php echo $totalRows; ?> user</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" id="tableUser">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4">Username</th>
                            <th class="py-2 pr-4">Nama</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">Role</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <?php if (empty($users)): ?>
                            <tr><td colspan="6" class="py-3 text-gray-500">Belum ada user.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $row): ?>
                                <tr class="border-t border-gray-100">
                                    <td class="py-2 pr-4 font-medium text-gray-900"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['email'] ?? '-'); ?></td>
                                    <td class="py-2 pr-4"><?php echo htmlspecialchars($row['role']); ?></td>
                                    <td class="py-2 pr-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo (int)$row['is_active'] === 1 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo (int)$row['is_active'] === 1 ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <div class="flex items-center gap-3">
                                            <button type="button" class="text-blue-600 hover:text-blue-700" title="Edit" onclick='openUserEdit(<?php echo json_encode($row); ?>)'>
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 20h9" />
                                                    <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="text-red-600 hover:text-red-700" title="Hapus" onclick="openUserDelete(<?php echo (int)$row['id']; ?>, '<?php echo htmlspecialchars($row['username'], ENT_QUOTES); ?>')">
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

<div id="userCreateModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Tambah User</h3>
            <button type="button" onclick="closeModal('userCreateModal')">✕</button>
        </div>
        <form method="POST">
            <div class="modal-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <input type="hidden" name="action" value="create_user" />
                <div>
                    <label class="text-sm text-gray-600">Username</label>
                    <input name="username" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Nama</label>
                    <input name="name" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Email</label>
                    <input name="email" type="email" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Password</label>
                    <input name="password" type="password" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Role</label>
                    <select name="role" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="admin">Admin</option>
                        <option value="guru" selected>Guru</option>
                        <option value="bk">BK</option>
                        <option value="orangtua">Orang Tua</option>
                        <option value="siswa">Siswa</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 mt-6">
                    <input type="checkbox" name="is_active" id="create_is_active" checked />
                    <label for="create_is_active" class="text-sm text-gray-600">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('userCreateModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-blue-600 text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="userEditModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Edit User</h3>
            <button type="button" onclick="closeModal('userEditModal')">✕</button>
        </div>
        <form method="POST">
            <div class="modal-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <input type="hidden" name="action" value="update_user" />
                <input type="hidden" name="id" id="edit_user_id" />
                <div>
                    <label class="text-sm text-gray-600">Username</label>
                    <input id="edit_user_username" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" disabled />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Nama</label>
                    <input id="edit_user_name" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" disabled />
                </div>
                <div>
                    <label class="text-sm text-gray-600">Role</label>
                    <select name="role" id="edit_user_role" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="admin">Admin</option>
                        <option value="guru">Guru</option>
                        <option value="bk">BK</option>
                        <option value="orangtua">Orang Tua</option>
                        <option value="siswa">Siswa</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 mt-6">
                    <input type="checkbox" name="is_active" id="edit_is_active" />
                    <label for="edit_is_active" class="text-sm text-gray-600">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('userEditModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-blue-600 text-white">Update</button>
            </div>
        </form>
    </div>
</div>

<div id="userDeleteModal" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-900">Hapus User</h3>
            <button type="button" onclick="closeModal('userDeleteModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
            <input type="hidden" name="action" value="delete_user" />
            <input type="hidden" name="id" id="delete_user_id" />
            <div class="modal-body">
                <p class="text-sm text-gray-600">Yakin ingin menghapus <span id="delete_user_name" class="font-semibold text-gray-900"></span>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-3 py-2 rounded-lg border border-gray-200" onclick="closeModal('userDeleteModal')">Batal</button>
                <button type="submit" class="px-3 py-2 rounded-lg bg-red-600 text-white">Hapus</button>
            </div>
        </form>
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

<script>
const searchUser = document.getElementById('searchUser');
const tableUser = document.getElementById('tableUser');
if (searchUser && tableUser) {
    searchUser.addEventListener('input', () => {
        const query = searchUser.value.toLowerCase();
        const rows = tableUser.querySelectorAll('tbody tr');
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

const openUserCreate = () => openModal('userCreateModal');

const openUserEdit = (row) => {
    document.getElementById('edit_user_id').value = row.id;
    document.getElementById('edit_user_username').value = row.username || '';
    document.getElementById('edit_user_name').value = row.name || '';
    document.getElementById('edit_user_role').value = row.role || 'guru';
    document.getElementById('edit_is_active').checked = parseInt(row.is_active, 10) === 1;
    openModal('userEditModal');
};

const openUserDelete = (id, username) => {
    document.getElementById('delete_user_id').value = id;
    document.getElementById('delete_user_name').textContent = username;
    openModal('userDeleteModal');
};
</script>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>
