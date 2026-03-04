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
$alertMessage = null;
$alertType = 'success';
$resetSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token verification (CRITICAL SECURITY CHECK)
    if (!SecurityHelper::verifyCSRFToken()) {
        $alertMessage = 'Akses ditolak: token tidak valid. Silakan muat ulang halaman.';
        $alertType = 'error';
    } else {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $alertMessage = 'Semua field harus diisi.';
            $alertType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $alertMessage = 'Password baru dan konfirmasi password tidak cocok.';
            $alertType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $alertMessage = 'Password minimal 6 karakter.';
            $alertType = 'error';
        } else {
            // Get current user data
            $userStmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
            if ($userStmt) {
                $userId = (int)$user['user_id'];
                $userStmt->bind_param('i', $userId);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                
                if ($userResult && $userResult->num_rows > 0) {
                    $userData = $userResult->fetch_assoc();
                    
                    // Verify old password
                    if (!password_verify($oldPassword, $userData['password'])) {
                        $alertMessage = 'Password lama tidak sesuai.';
                        $alertType = 'error';
                    } else {
                        // Update password
                        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                        $updateStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                        if ($updateStmt) {
                            $updateStmt->bind_param('si', $hashedPassword, $userId);
                            if ($updateStmt->execute()) {
                                $alertMessage = 'Password berhasil diperbarui.';
                                $alertType = 'success';
                                $resetSuccess = true;
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

$title = 'Reset Password - E-Disiplin';
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
                        <p class="text-xs text-gray-500">Reset Password</p>
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

    <!-- Main Content -->
    <div class="flex items-center justify-center min-h-[calc(100vh-64px)] px-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">Reset Password</h2>
                    <p class="text-gray-600 mt-2">Ubah password akun Anda</p>
                </div>

                <?php if ($alertMessage): ?>
                    <div class="mb-6 p-4 rounded-lg <?php echo $alertType === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700'; ?>">
                        <p class="text-sm font-medium"><?php echo htmlspecialchars($alertMessage); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!$resetSuccess): ?>
                    <form method="POST" class="space-y-4">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                        
                        <!-- Old Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password Lama</label>
                            <input type="password" name="old_password" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Masukkan password lama">
                        </div>

                        <!-- New Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                            <input type="password" name="new_password" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Minimal 6 karakter">
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Ulangi password baru">
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition mt-6">
                            Update Password
                        </button>
                    </form>

                    <!-- Password Requirements -->
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-xs font-semibold text-blue-900 mb-2">Syarat Password:</p>
                        <ul class="text-xs text-blue-700 space-y-1">
                            <li>✓ Minimal 6 karakter</li>
                            <li>✓ Gunakan kombinasi huruf dan angka</li>
                            <li>✓ Jangan gunakan password yang mudah ditebak</li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <p class="text-gray-600 mb-4">Password Anda berhasil diperbarui!</p>
                        <p class="text-sm text-gray-500 mb-6">Silakan login kembali dengan password baru Anda.</p>
                        <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                            Kembali ke Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>
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
