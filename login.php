<?php
/**
 * Login Page for Rengganis Net
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_phone'] = $user['phone'];

            setFlash('success', "Selamat datang kembali, {$user['name']}!");
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password yang Anda masukkan salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rengganis Net</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-xl shadow-blue-500/25 mb-4 transform hover:rotate-3 transition-transform">
                <i class="fa-solid fa-wifi text-2xl"></i>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-white">RENGGANIS NET</h1>
            <p class="text-sm text-blue-200/80 font-medium mt-1">Sistem Informasi Manajemen Pelanggan & Tagihan WiFi</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/15 rounded-3xl p-8 shadow-2xl">
            
            <h2 class="text-lg font-bold text-white mb-2">Masuk ke Akun</h2>
            <p class="text-xs text-slate-300 mb-6">Gunakan akun Admin atau Teknisi Anda untuk melanjutkan.</p>

            <?php if ($error): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-200 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-rose-400"></i>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($flashErr = getFlash('error')): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-200 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-amber-400"></i>
                    <span><?= e($flashErr) ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-user text-sm"></i>
                        </span>
                        <input type="text" id="inputUsername" name="username" required placeholder="Masukkan username" value="<?= e($_POST['username'] ?? '') ?>" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/5 border border-white/20 text-white placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </span>
                        <input type="password" id="inputPassword" name="password" required placeholder="Masukkan password" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/5 border border-white/20 text-white placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold text-sm shadow-lg shadow-blue-500/30 hover:from-blue-500 hover:to-indigo-500 transition-all flex items-center justify-center gap-2">
                        <span>Masuk Sistem</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </form>

            <!-- Quick Demo Login Helpers -->
            <div class="mt-8 pt-6 border-t border-white/10 text-center">
                <p class="text-[11px] text-slate-400 mb-2.5 font-medium">Bantuan Login Cepat (Demo):</p>
                <div class="flex items-center justify-center gap-2">
                    <button type="button" onclick="fillCreds('admin', 'admin123')" class="px-3 py-1.5 rounded-lg bg-blue-500/20 hover:bg-blue-500/30 border border-blue-400/30 text-blue-200 text-xs font-semibold transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-crown text-[10px] text-amber-300"></i> Admin
                    </button>
                    <button type="button" onclick="fillCreds('teknisi', 'teknisi123')" class="px-3 py-1.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-400/30 text-emerald-200 text-xs font-semibold transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-wrench text-[10px] text-emerald-300"></i> Teknisi
                    </button>
                </div>
            </div>

        </div>

        <div class="mt-6 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> Rengganis Net. All rights reserved.
        </div>

    </div>

    <script>
        function fillCreds(u, p) {
            document.getElementById('inputUsername').value = u;
            document.getElementById('inputPassword').value = p;
        }
    </script>
</body>
</html>
