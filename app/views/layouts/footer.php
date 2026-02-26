    <?php if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['username'])): ?>
        <div class="dock-spacer"></div>
        <?php
            $currentPage = basename($_SERVER['PHP_SELF']);
            $role = $_SESSION['role'] ?? '';
            $isStaff = in_array($role, ['admin', 'guru', 'bk'], true);
        ?>
        <nav class="dock" aria-label="Dock">
            <?php if ($role === 'orangtua'): ?>
                <a href="ortu_dashboard.php" class="dock-item <?php echo $currentPage === 'ortu_dashboard.php' ? 'is-active' : ''; ?>" data-label="Dashboard">
                    <span class="dock-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M3 12l9-9 9 9" />
                            <path d="M9 21V9h6v12" />
                        </svg>
                    </span>
                    <span class="dock-label">Dashboard</span>
                </a>
            <?php elseif ($role === 'siswa'): ?>
                <a href="siswa_dashboard.php" class="dock-item <?php echo $currentPage === 'siswa_dashboard.php' ? 'is-active' : ''; ?>" data-label="Dashboard">
                    <span class="dock-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M3 12l9-9 9 9" />
                            <path d="M9 21V9h6v12" />
                        </svg>
                    </span>
                    <span class="dock-label">Dashboard</span>
                </a>
            <?php else: ?>
                <a href="dashboard.php" class="dock-item <?php echo $currentPage === 'dashboard.php' ? 'is-active' : ''; ?>" data-label="Dashboard">
                    <span class="dock-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M3 12l9-9 9 9" />
                            <path d="M9 21V9h6v12" />
                        </svg>
                    </span>
                    <span class="dock-label">Dashboard</span>
                </a>
            <?php endif; ?>

            <?php if ($isStaff): ?>
                <a href="data_siswa.php" class="dock-item <?php echo $currentPage === 'data_siswa.php' ? 'is-active' : ''; ?>" data-label="Data Siswa">
                    <span class="dock-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M16 22v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 00-3-3.87" />
                            <path d="M16 3.13a4 4 0 010 7.75" />
                        </svg>
                    </span>
                    <span class="dock-label">Data Siswa</span>
                </a>
                <a href="pelanggaran.php" class="dock-item <?php echo $currentPage === 'pelanggaran.php' ? 'is-active' : ''; ?>" data-label="Pelanggaran">
                    <span class="dock-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M7 3h10v4H7z" />
                            <path d="M5 7h14v14H5z" />
                            <path d="M9 11h6M9 15h6" />
                        </svg>
                    </span>
                    <span class="dock-label">Pelanggaran</span>
                </a>
                <a href="surat_pelanggaran.php" class="dock-item <?php echo $currentPage === 'surat_pelanggaran.php' ? 'is-active' : ''; ?>" data-label="Surat">
                    <span class="dock-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 4h16v16H4z" />
                            <path d="M4 7l8 6 8-6" />
                        </svg>
                    </span>
                    <span class="dock-label">Surat</span>
                </a>
                <?php if ($role === 'admin'): ?>
                    <a href="user_management.php" class="dock-item <?php echo $currentPage === 'user_management.php' ? 'is-active' : ''; ?>" data-label="User">
                        <span class="dock-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 12a4 4 0 100-8 4 4 0 000 8z" />
                                <path d="M4 20a8 8 0 0116 0" />
                            </svg>
                        </span>
                        <span class="dock-label">User</span>
                    </a>
                <?php endif; ?>
            <?php elseif (in_array($role, ['orangtua', 'siswa'], true)): ?>
                <a href="cek_surat.php" class="dock-item <?php echo $currentPage === 'cek_surat.php' ? 'is-active' : ''; ?>" data-label="Surat">
                    <span class="dock-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 4h16v16H4z" />
                            <path d="M4 7l8 6 8-6" />
                        </svg>
                    </span>
                    <span class="dock-label">Surat</span>
                </a>
            <?php endif; ?>

            <!-- Settings / Reset Password -->
            <a href="reset_password.php" class="dock-item <?php echo $currentPage === 'reset_password.php' ? 'is-active' : ''; ?>" data-label="Pengaturan">
                <span class="dock-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span>
                <span class="dock-label">Pengaturan</span>
            </a>
        </nav>
    <?php endif; ?>
    <script src="/js/main.js"></script>
</body>
</html>
