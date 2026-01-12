<?php 
$title = 'Login - E-Disiplin';
include __DIR__ . '/layouts/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center p-4">
    <div class="login-container w-full max-w-5xl">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 items-stretch rounded-2xl overflow-hidden shadow-2xl bg-white">
            
            <!-- Left Side - Form -->
            <div class="p-8 lg:p-12 flex flex-col justify-center">
                <!-- Logo Icon -->
                <div class="mb-8">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center mb-6">
                        <span class="text-white font-bold text-2xl">✦</span>
                    </div>
                    <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-2">Masuk ke Sistem</h1>
                    <p class="text-gray-500">Kelola data pelanggaran siswa.</p>
                </div>

                <!-- Alert Container -->
                <div data-alert-container class="mb-6"></div>

                <!-- Form -->
                <form id="loginForm" method="POST" action="api/auth/login.php" class="space-y-5">
                    <!-- Email/Username -->
                    <div>
                        <label for="username" class="block text-sm font-semibold text-gray-900 mb-2">
                            Username
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            required
                            placeholder="Masukkan username Anda"
                            class="form-input w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-900 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                placeholder="Masukkan password"
                                class="form-input w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition pr-12"
                            />
                            <button
                                type="button"
                                id="passwordToggle"
                                class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600 transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                name="remember"
                                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                            />
                            <span class="text-sm text-gray-700 font-medium">Ingat saya</span>
                        </label>
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-700 font-semibold">
                            Lupa password?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="btn-login w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 rounded-lg transition duration-200 mt-8"
                    >
                        Log In
                    </button>
                </form>
            </div>

            <!-- Right Side - Geometric Decoration -->
            <div class="hidden lg:block bg-gradient-to-br from-blue-600 via-purple-600 to-blue-800 relative overflow-hidden p-8">
                <!-- SVG Background Elements -->
                <svg class="absolute inset-0 w-full h-full" viewBox="0 0 500 800" preserveAspectRatio="xMidYMid slice">
                    <!-- Large circles background -->
                    <circle cx="250" cy="150" r="120" fill="url(#grad1)" opacity="0.3"/>
                    <circle cx="150" cy="400" r="100" fill="url(#grad2)" opacity="0.2"/>
                    <circle cx="380" cy="500" r="90" fill="url(#grad3)" opacity="0.25"/>
                    
                    <!-- Gradients -->
                    <defs>
                        <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#a78bfa;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#7c3aed;stop-opacity:1" />
                        </linearGradient>
                        <linearGradient id="grad2" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#60a5fa;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:1" />
                        </linearGradient>
                        <linearGradient id="grad3" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#ec4899;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#d946ef;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                </svg>

                <!-- Geometric Shapes Layer -->
                <div class="absolute inset-0 overflow-hidden">
                    <!-- Top Left Circle -->
                    <div class="absolute -top-32 -left-32 w-64 h-64 rounded-full border-4 border-blue-300 opacity-20"></div>
                    
                    <!-- Top circles (purple tones) -->
                    <div class="absolute top-12 left-16 w-48 h-48 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 opacity-30 blur-2xl"></div>
                    <div class="absolute top-20 right-20 w-40 h-40 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 opacity-25 blur-2xl"></div>

                    <!-- Geometric shapes -->
                    <div class="absolute top-32 left-12 w-24 h-24 border-4 border-yellow-300 rounded-xl opacity-60 transform -rotate-45"></div>
                    <div class="absolute top-40 right-32 w-20 h-20 bg-cyan-400 rounded-full opacity-50 blur-sm"></div>
                    
                    <!-- Diamond shape -->
                    <div class="absolute top-60 left-1/3 w-6 h-6 bg-yellow-400 opacity-80 transform rotate-45"></div>
                    <div class="absolute top-64 left-2/3 w-4 h-4 bg-yellow-400 opacity-80 transform rotate-45"></div>

                    <!-- Middle triangles -->
                    <div class="absolute top-1/2 left-12 w-0 h-0 border-l-12 border-r-12 border-b-20 border-l-transparent border-r-transparent border-b-blue-400 opacity-40"></div>
                    
                    <!-- Star shape -->
                    <div class="absolute top-1/2 right-16 text-yellow-400 text-6xl opacity-60">✦</div>

                    <!-- Bottom shapes -->
                    <div class="absolute bottom-32 left-1/4 w-32 h-32 bg-cyan-500 rounded-2xl opacity-30 blur-xl transform skew-y-12"></div>
                    <div class="absolute bottom-20 right-12 w-24 h-24 border-4 border-purple-300 rounded-lg opacity-40"></div>
                    
                    <!-- Accent dots -->
                    <div class="absolute top-1/3 right-1/4 w-3 h-3 bg-blue-300 rounded-full opacity-70"></div>
                    <div class="absolute top-2/3 left-1/4 w-2 h-2 bg-purple-300 rounded-full opacity-70"></div>
                    <div class="absolute bottom-1/3 right-1/3 w-4 h-4 bg-cyan-400 rounded-full opacity-60"></div>

                    <!-- Vertical lines -->
                    <div class="absolute top-1/4 right-1/3 w-1 h-32 bg-gradient-to-b from-blue-400 to-transparent opacity-50"></div>
                    <div class="absolute top-1/2 left-1/2 w-1 h-24 bg-gradient-to-b from-purple-400 to-transparent opacity-50"></div>
                </div>

                <!-- Floating text -->
                <div class="absolute inset-0 flex flex-col justify-end p-8 pointer-events-none">
                    <p class="text-white/80 text-sm">© 2026 E-Disiplin</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        handleFormSubmit('loginForm', 'api/auth/login.php');
    });
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>

