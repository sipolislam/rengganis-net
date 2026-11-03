<?php
/**
 * Login Page for Rengganis Net
 * Luxury Neo-Skeuomorphic Cyber Console Aesthetic
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
    $csrfToken = $_POST['csrf_token'] ?? '';
    $username  = trim($_POST['username'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Token keamanan CSRF tidak valid atau sesi kedaluwarsa. Silakan muat ulang halaman.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation attacks (OWASP A07:2025)
            session_regenerate_id(true);

            $_SESSION['user_id']       = (int)$user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_name']     = $user['name'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['user_phone']    = $user['phone'];

            // Generate fresh CSRF token for the authenticated session
            $_SESSION['csrf_token']    = bin2hex(random_bytes(32));

            setFlash('success', "Selamat datang kembali, {$user['name']}!");
            header('Location: index.php');
            exit;
        } else {
            // Generic error message to prevent username enumeration
            $error = 'Username atau kata sandi yang Anda masukkan salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Console • Rengganis Net</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-gradient-to-br from-[#0f172a] via-[#1e293b] to-[#0f172a] text-slate-100 min-h-screen flex items-center justify-center p-4 relative overflow-hidden select-none">

    <!-- Ambient Futuristic Glow Spheres -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full relative z-10">
        
        <!-- Brand Hologram Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-3xl bg-gradient-to-tr from-blue-700 via-indigo-600 to-cyan-500 flex items-center justify-center text-white shadow-[0_12px_28px_rgba(37,99,235,0.45),inset_0_1px_1px_rgba(255,255,255,0.8),inset_0_-3px_0_rgba(0,0,0,0.4)] border border-blue-400/50 mb-4 transform hover:scale-105 transition-transform">
                <i class="fa-solid fa-wifi text-2xl drop-shadow-[0_2px_4px_rgba(0,0,0,0.4)]"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tight font-tech text-white">RENGGANIS NET</h1>
            <p class="text-xs text-blue-200/80 font-bold uppercase tracking-widest mt-1">Management Console &bull; Fiber Billing</p>
        </div>

        <!-- Luxury Neo-Skeuomorphic Glass Console Card -->
        <div class="bg-[#1e293b]/85 backdrop-blur-2xl border border-white/20 rounded-3xl p-8 shadow-[0_30px_70px_rgba(0,0,0,0.6),inset_0_1px_1px_rgba(255,255,255,0.2)]">
            
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/10">
                <div>
                    <h2 class="text-base font-black text-white font-tech">Autentikasi Pengguna</h2>
                    <p class="text-[11px] text-slate-400">Masuk dengan akun Admin, Kolektor, atau Teknisi</p>
                </div>
                <span class="led-pulse active-green"></span>
            </div>

            <?php if ($error): ?>
                <div class="mb-5 p-3.5 rounded-2xl bg-rose-500/20 border border-rose-500/50 text-rose-200 text-xs flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-rose-400 text-sm"></i>
                    <span class="font-bold"><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($flashErr = getFlash('error')): ?>
                <div class="mb-5 p-3.5 rounded-2xl bg-amber-500/20 border border-amber-500/50 text-amber-200 text-xs flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-amber-400 text-sm"></i>
                    <span class="font-bold"><?= e($flashErr) ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4">
                <?= csrfField() ?>
                <div>
                    <label class="block text-[11px] font-black text-slate-300 uppercase tracking-wider mb-1.5">Username Akun</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-user text-sm"></i>
                        </span>
                        <input type="text" id="inputUsername" name="username" required placeholder="Masukkan username" value="<?= e($_POST['username'] ?? '') ?>" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/80 border border-white/15 text-white placeholder-slate-500 text-xs font-bold font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-inner">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-300 uppercase tracking-wider mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </span>
                        <input type="password" id="inputPassword" name="password" required placeholder="Masukkan kata sandi" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/80 border border-white/15 text-white placeholder-slate-500 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-inner">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 px-4 rounded-xl skeuo-btn-primary font-black text-xs shadow-lg flex items-center justify-center gap-2">
                        <span>Masuk ke Console</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </form>

            <!-- Quick Demo Login Helpers -->
            <div class="mt-8 pt-6 border-t border-white/10 text-center">
                <p class="text-[10px] text-slate-400 mb-2.5 font-bold uppercase tracking-wider">Akses Cepat (Demo):</p>
                <div class="flex items-center justify-center gap-2 flex-wrap">
                    <button type="button" onclick="fillCreds('admin', 'admin123')" class="px-3 py-1.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 border border-amber-400/40 text-amber-200 text-xs font-black transition-colors flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-crown text-[10px] text-amber-400"></i> Admin
                    </button>
                    <button type="button" onclick="fillCreds('kolektor', 'kolektor123')" class="px-3 py-1.5 rounded-xl bg-cyan-500/20 hover:bg-cyan-500/30 border border-cyan-400/40 text-cyan-200 text-xs font-black transition-colors flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-cash-register text-[10px] text-cyan-400"></i> Kolektor
                    </button>
                    <button type="button" onclick="fillCreds('teknisi', 'teknisi123')" class="px-3 py-1.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-400/40 text-emerald-200 text-xs font-black transition-colors flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-wrench text-[10px] text-emerald-400"></i> Teknisi
                    </button>
                </div>
            </div>

        </div>

        <div class="mt-6 text-center text-[11px] text-slate-400 font-medium">
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
