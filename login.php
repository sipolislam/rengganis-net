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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Login Console • Rengganis Net</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css?v=<?= file_exists(__DIR__ . '/assets/css/custom.css') ? filemtime(__DIR__ . '/assets/css/custom.css') : '1.0' ?>">
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

                <div class="flex items-center justify-between text-xs pt-1">
                    <span class="text-slate-400 text-[11px]">Kendala autentikasi?</span>
                    <button type="button" onclick="openForgotModal()" class="text-blue-400 hover:text-blue-300 font-bold transition-colors inline-flex items-center gap-1.5 cursor-pointer text-xs group">
                        <i class="fa-solid fa-key text-[10px] group-hover:rotate-12 transition-transform"></i>
                        <span>Lupa Sandi?</span>
                    </button>
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

    <!-- ====================================================================
         MODAL LUPA KATA SANDI DENGAN VERIFIKASI OTP EMAIL
         ==================================================================== -->
    <div id="forgotModal" onclick="if(event.target === this) closeForgotModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-md p-4 overflow-y-auto">
        <div class="bg-[#1e293b]/95 border border-white/25 rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-[0_30px_70px_rgba(0,0,0,0.7),inset_0_1px_1px_rgba(255,255,255,0.2)] text-slate-100 relative overflow-hidden animate-scale-up">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-cyan-500 flex items-center justify-center text-white shadow-md">
                        <i class="fa-solid fa-shield-halved text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-white font-tech">Pemulihan Kata Sandi</h3>
                        <p class="text-[11px] text-slate-400">Verifikasi kode OTP melalui email terdaftar</p>
                    </div>
                </div>
                <button type="button" onclick="closeForgotModal()" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Global Alert Container -->
            <div id="forgotAlert" class="hidden mb-4 p-3 rounded-xl text-xs flex items-center gap-2.5 font-bold"></div>

            <!-- STEP 1: MINTA KODE OTP -->
            <div id="forgotStep1" class="space-y-4">
                <p class="text-xs text-slate-300 leading-relaxed font-medium">
                    Masukkan <span class="text-white font-bold">Username</span> atau <span class="text-white font-bold">Alamat Email</span> akun Anda. Sistem akan mengirimkan 6 digit kode verifikasi (OTP) ke email tersebut.
                </p>

                <form onsubmit="handleRequestOtp(event)" class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-black text-slate-300 uppercase tracking-wider mb-1.5">Username / Alamat Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                <i class="fa-solid fa-envelope text-sm"></i>
                            </span>
                            <input type="text" id="forgotIdentifier" required placeholder="Contoh: admin atau admin@rengganis.net" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-white/15 text-white placeholder-slate-500 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner">
                        </div>
                    </div>

                    <button type="submit" id="btnRequestOtp" class="w-full py-3 px-4 rounded-xl skeuo-btn-primary font-black text-xs shadow-lg flex items-center justify-center gap-2">
                        <span>Kirim Kode OTP ke Email</span>
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </form>
            </div>

            <!-- STEP 2: VERIFIKASI KODE OTP -->
            <div id="forgotStep2" class="hidden space-y-4">
                <div class="p-3.5 rounded-2xl bg-blue-500/15 border border-blue-400/30 text-blue-200 text-xs">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <i class="fa-solid fa-envelope-circle-check text-blue-400 text-sm"></i>
                        <span>Kode OTP Telah Dikirim!</span>
                    </div>
                    <p class="text-[11px] text-blue-100/80 leading-relaxed">
                        Periksa kotak masuk email <span id="forgotTargetEmail" class="font-bold text-white font-mono">-</span>. Masukkan 6 digit kode OTP di bawah ini:
                    </p>
                </div>

                <!-- Local Dev Demo OTP Banner -->
                <div id="demoOtpBox" class="hidden p-3 rounded-2xl bg-amber-500/20 border border-amber-400/50 text-amber-200 text-xs">
                    <span class="text-[10px] uppercase font-black tracking-widest text-amber-400 block mb-0.5">Mode Lokal / Simulasi Email:</span>
                    <span class="font-medium text-[11px]">Kode OTP Anda: </span>
                    <strong id="demoOtpCode" class="font-mono text-base font-black text-amber-300 tracking-wider">123456</strong>
                </div>

                <form onsubmit="handleVerifyOtp(event)" class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-black text-slate-300 uppercase tracking-wider mb-1.5 text-center">6 Digit Kode Verifikasi (OTP)</label>
                        <input type="text" id="forgotOtpInput" maxlength="6" pattern="[0-9]{6}" required placeholder="000000" class="w-full py-3 text-center rounded-xl bg-slate-900/90 border border-white/20 text-white placeholder-slate-600 text-2xl font-black font-mono tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner">
                        <p class="text-[10px] text-slate-400 text-center mt-1.5 font-medium">
                            <i class="fa-solid fa-clock text-slate-500 mr-1"></i>Kode berlaku selama 15 menit
                        </p>
                    </div>

                    <button type="submit" id="btnVerifyOtp" class="w-full py-3 px-4 rounded-xl skeuo-btn-emerald font-black text-xs shadow-lg flex items-center justify-center gap-2">
                        <span>Verifikasi Kode OTP</span>
                        <i class="fa-solid fa-check text-xs"></i>
                    </button>

                    <div class="text-center pt-1">
                        <button type="button" onclick="resendOtp()" id="btnResendOtp" class="text-xs text-blue-400 hover:text-blue-300 font-bold transition-colors">
                            Belum terima email? Kirim ulang
                        </button>
                    </div>
                </form>
            </div>

            <!-- STEP 3: BUAT KATA SANDI BARU -->
            <div id="forgotStep3" class="hidden space-y-4">
                <p class="text-xs text-slate-300 leading-relaxed font-medium">
                    Verifikasi OTP berhasil! Silakan tentukan kata sandi baru untuk akun Anda.
                </p>

                <form onsubmit="handleResetPassword(event)" class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-black text-slate-300 uppercase tracking-wider mb-1.5">Kata Sandi Baru</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </span>
                            <input type="password" id="forgotNewPassword" required minlength="6" placeholder="Minimal 6 karakter" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-white/15 text-white placeholder-slate-500 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-300 uppercase tracking-wider mb-1.5">Konfirmasi Kata Sandi Baru</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                <i class="fa-solid fa-lock-open text-sm"></i>
                            </span>
                            <input type="password" id="forgotConfirmPassword" required minlength="6" placeholder="Ulangi kata sandi baru" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-white/15 text-white placeholder-slate-500 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner">
                        </div>
                    </div>

                    <button type="submit" id="btnResetPassword" class="w-full py-3 px-4 rounded-xl skeuo-btn-primary font-black text-xs shadow-lg flex items-center justify-center gap-2">
                        <span>Simpan Kata Sandi Baru</span>
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                    </button>
                </form>
            </div>

            <!-- STEP 4: SELESAI / SUKSES -->
            <div id="forgotStep4" class="hidden text-center py-4 space-y-4">
                <div class="w-16 h-16 mx-auto rounded-3xl bg-emerald-500/20 border border-emerald-400/50 flex items-center justify-center text-emerald-400 text-3xl shadow-lg">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <h4 class="text-base font-black text-white font-tech">Kata Sandi Berhasil Diubah!</h4>
                    <p class="text-xs text-slate-300 mt-1 font-medium">Akun Anda kini dapat diakses dengan kata sandi baru.</p>
                </div>
                <div class="pt-2">
                    <button type="button" onclick="finishForgot()" class="w-full py-3 px-4 rounded-xl skeuo-btn-emerald font-black text-xs shadow-lg flex items-center justify-center gap-2">
                        <span>Masuk ke Console Sekarang</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Quick Fill Helper
        function fillCreds(u, p) {
            document.getElementById('inputUsername').value = u;
            document.getElementById('inputPassword').value = p;
        }

        // State variables for Forgot Password
        let resetUserId = 0;
        let lastIdentifier = '';
        let lastNewPassword = '';
        let lastUsername = '';

        function showForgotAlert(msg, type = 'error') {
            const box = document.getElementById('forgotAlert');
            box.classList.remove('hidden', 'bg-rose-500/20', 'border-rose-500/50', 'text-rose-200', 'bg-emerald-500/20', 'border-emerald-500/50', 'text-emerald-200');
            if (type === 'error') {
                box.classList.add('bg-rose-500/20', 'border', 'border-rose-500/50', 'text-rose-200');
                box.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-rose-400"></i> <span>${msg}</span>`;
            } else {
                box.classList.add('bg-emerald-500/20', 'border', 'border-emerald-500/50', 'text-emerald-200');
                box.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-400"></i> <span>${msg}</span>`;
            }
        }

        function hideForgotAlert() {
            document.getElementById('forgotAlert').classList.add('hidden');
        }

        function openForgotModal() {
            document.getElementById('forgotModal').classList.remove('hidden');
            document.getElementById('forgotModal').classList.add('flex');
            goToStep(1);
            hideForgotAlert();
            setTimeout(() => {
                document.getElementById('forgotIdentifier').focus();
            }, 100);
        }

        function closeForgotModal() {
            document.getElementById('forgotModal').classList.add('hidden');
            document.getElementById('forgotModal').classList.remove('flex');
            hideForgotAlert();
        }

        function goToStep(step) {
            hideForgotAlert();
            document.getElementById('forgotStep1').classList.toggle('hidden', step !== 1);
            document.getElementById('forgotStep2').classList.toggle('hidden', step !== 2);
            document.getElementById('forgotStep3').classList.toggle('hidden', step !== 3);
            document.getElementById('forgotStep4').classList.toggle('hidden', step !== 4);
        }

        // Handle Request OTP
        async function handleRequestOtp(e) {
            e.preventDefault();
            const btn = document.getElementById('btnRequestOtp');
            const originalBtn = btn.innerHTML;
            const identifier = document.getElementById('forgotIdentifier').value.trim();

            if (!identifier) {
                showForgotAlert('Silakan masukkan username atau email akun Anda.');
                return;
            }

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim OTP...';
                hideForgotAlert();

                const response = await fetch('api/auth.php?action=request_otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier: identifier })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Gagal mengirim kode OTP.');
                }

                resetUserId = data.user_id;
                lastIdentifier = identifier;
                lastUsername = data.username || identifier;

                document.getElementById('forgotTargetEmail').innerText = data.masked_email;

                if (data.demo_otp) {
                    document.getElementById('demoOtpBox').classList.remove('hidden');
                    document.getElementById('demoOtpCode').innerText = data.demo_otp;
                } else {
                    document.getElementById('demoOtpBox').classList.add('hidden');
                }

                goToStep(2);
                document.getElementById('forgotOtpInput').value = '';
                document.getElementById('forgotOtpInput').focus();
            } catch (err) {
                showForgotAlert(err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalBtn;
            }
        }

        // Resend OTP
        async function resendOtp() {
            if (!lastIdentifier) return;
            const btn = document.getElementById('btnResendOtp');
            const orig = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Mengirim ulang...';
            try {
                const response = await fetch('api/auth.php?action=request_otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier: lastIdentifier })
                });
                const data = await response.json();
                if (data.success) {
                    showForgotAlert('Kode OTP baru telah berhasil dikirimkan!', 'success');
                    if (data.demo_otp) {
                        document.getElementById('demoOtpBox').classList.remove('hidden');
                        document.getElementById('demoOtpCode').innerText = data.demo_otp;
                    }
                } else {
                    showForgotAlert(data.message, 'error');
                }
            } catch (e) {
                showForgotAlert('Gagal mengirim ulang OTP.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerText = orig;
            }
        }

        // Handle Verify OTP
        async function handleVerifyOtp(e) {
            e.preventDefault();
            const btn = document.getElementById('btnVerifyOtp');
            const originalBtn = btn.innerHTML;
            const otp = document.getElementById('forgotOtpInput').value.trim();

            if (!otp || otp.length !== 6) {
                showForgotAlert('Kode OTP harus terdiri dari 6 digit angka.');
                return;
            }

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memverifikasi...';
                hideForgotAlert();

                const response = await fetch('api/auth.php?action=verify_otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: resetUserId, otp: otp })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Kode OTP tidak valid.');
                }

                goToStep(3);
                document.getElementById('forgotNewPassword').value = '';
                document.getElementById('forgotConfirmPassword').value = '';
                document.getElementById('forgotNewPassword').focus();
            } catch (err) {
                showForgotAlert(err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalBtn;
            }
        }

        // Handle Reset Password
        async function handleResetPassword(e) {
            e.preventDefault();
            const btn = document.getElementById('btnResetPassword');
            const originalBtn = btn.innerHTML;
            const pwd = document.getElementById('forgotNewPassword').value;
            const confirm = document.getElementById('forgotConfirmPassword').value;

            if (pwd.length < 6) {
                showForgotAlert('Kata sandi baru minimal 6 karakter.');
                return;
            }

            if (pwd !== confirm) {
                showForgotAlert('Konfirmasi kata sandi tidak cocok.');
                return;
            }

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan Sandi...';
                hideForgotAlert();

                const response = await fetch('api/auth.php?action=reset_password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: resetUserId,
                        password: pwd,
                        password_confirm: confirm
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Gagal mereset kata sandi.');
                }

                lastNewPassword = pwd;
                goToStep(4);
            } catch (err) {
                showForgotAlert(err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalBtn;
            }
        }

        // Finish and Fill Credentials in Login Form
        function finishForgot() {
            closeForgotModal();
            if (lastUsername) {
                document.getElementById('inputUsername').value = lastUsername;
            }
            if (lastNewPassword) {
                document.getElementById('inputPassword').value = lastNewPassword;
            }
            document.getElementById('inputPassword').focus();
        }

        // Keyboard ESC handler
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeForgotModal();
            }
        });
    </script>
</body>
</html>
