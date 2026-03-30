<?php

require_once __DIR__ . '/../app/config/bootstrap.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

$database = new Database();
$conn = $database->connect();

$authController = new AuthController(null);

if (!$authController->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION;
$userId = (int)$user['user_id'];
$alertMessage = null;
$alertType = 'success';
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!SecurityHelper::verifyCSRFToken()) {
        $alertMessage = 'Akses ditolak: token tidak valid. Silakan muat ulang halaman.';
        $alertType = 'error';
    } elseif ($action === 'update_profile') {
        $newName = trim($_POST['name'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');

        if (empty($newName)) {
            $alertMessage = 'Nama tidak boleh kosong.';
            $alertType = 'error';
        } elseif ($newEmail !== '' && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $alertMessage = 'Format email tidak valid.';
            $alertType = 'error';
        } else {
            $stmt = $conn->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('ssi', $newName, $newEmail, $userId);
                if ($stmt->execute()) {
                    $_SESSION['name'] = $newName;
                    $_SESSION['email'] = $newEmail;
                    $user['name'] = $newName;
                    $user['email'] = $newEmail;
                    $alertMessage = 'Profil berhasil diperbarui.';
                    $alertType = 'success';
                } else {
                    $alertMessage = 'Gagal memperbarui profil.';
                    $alertType = 'error';
                }
            }
        }
    } elseif ($action === 'update_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $alertMessage = 'Semua field password harus diisi.';
            $alertType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $alertMessage = 'Password baru dan konfirmasi tidak cocok.';
            $alertType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $alertMessage = 'Password minimal 6 karakter.';
            $alertType = 'error';
        } else {
            $stmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    $userData = $result->fetch_assoc();
                    if (!password_verify($oldPassword, $userData['password'])) {
                        $alertMessage = 'Password lama tidak sesuai.';
                        $alertType = 'error';
                    } else {
                        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                        $updateStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                        if ($updateStmt) {
                            $updateStmt->bind_param('si', $hashed, $userId);
                            if ($updateStmt->execute()) {
                                $alertMessage = 'Password berhasil diperbarui.';
                                $alertType = 'success';
                            } else {
                                $alertMessage = 'Gagal memperbarui password.';
                                $alertType = 'error';
                            }
                        }
                    }
                }
            }
        }
    }
}

// Fetch fresh user data
$stmt = $conn->prepare('SELECT name, email, username, role FROM users WHERE id = ? LIMIT 1');
$currentUser = null;
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $currentUser = $result->fetch_assoc();
    }
}
$currentUser = $currentUser ?? ['name' => $user['name'], 'email' => $user['email'] ?? '', 'username' => $user['username'], 'role' => $user['role']];

$title = 'Pengaturan Akun - E-Disiplin';
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
                        <p class="text-xs text-gray-500">Pengaturan Akun</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <?php
                        $role = $_SESSION['role'] ?? '';
                        if ($role === 'orangtua' || $role === 'ortu') {
                            $backUrl = 'ortu_dashboard.php';
                        } elseif ($role === 'siswa') {
                            $backUrl = 'siswa_dashboard.php';
                        } else {
                            $backUrl = 'dashboard.php';
                        }
                    ?>
                    <a href="<?php echo $backUrl; ?>" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Kembali">
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

        <?php if ($alertMessage): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $alertType === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700'; ?>">
                <p class="text-sm font-medium"><?php echo htmlspecialchars($alertMessage); ?></p>
            </div>
        <?php endif; ?>

        <!-- Profile Card -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center gap-4 mb-2">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-white text-2xl font-bold"><?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?></span>
                </div>
                <div>
                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($currentUser['name']); ?></p>
                    <p class="text-sm text-gray-500">@<?php echo htmlspecialchars($currentUser['username']); ?></p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize mt-1">
                        <?php echo htmlspecialchars($currentUser['role']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Edit Profil -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Profil</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <input type="hidden" name="action" value="update_profile" />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" disabled
                           value="<?php echo htmlspecialchars($currentUser['username']); ?>"
                           class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                    <p class="text-xs text-gray-400 mt-1">Username tidak dapat diubah</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" required
                           value="<?php echo htmlspecialchars($currentUser['name']); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Masukkan nama lengkap">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email"
                           value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="contoh@email.com">
                </div>

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                    Simpan Profil
                </button>
            </form>
        </div>

        <!-- Ganti Password -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ganti Password</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <input type="hidden" name="action" value="update_password" />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Lama</label>
                    <input type="password" name="old_password" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Masukkan password lama">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <input type="password" name="new_password" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Minimal 6 karakter">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Ulangi password baru">
                </div>

                <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-xs text-amber-700">Minimal 6 karakter. Gunakan kombinasi huruf dan angka.</p>
                </div>

                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                    Update Password
                </button>
            </form>
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
