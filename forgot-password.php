<?php
/**
 * Standalone Forgot Password Page with Email OTP for Rengganis Net
 * Luxury Neo-Skeuomorphic Cyber Aesthetic
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Lupa Kata Sandi • Rengganis Net</title>
    
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
        <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto rounded-3xl bg-gradient-to-tr from-blue-700 via-indigo-600 to-cyan-500 flex items-center justify-center text-white shadow-[0_12px_28px_rgba(37,99,235,0.45),inset_0_1px_1px_rgba(255,255,255,0.8),inset_0_-3px_0_rgba(0,0,0,0.4)] border border-blue-400/50 mb-3 transform hover:scale-105 transition-transform">
                <i class="fa-solid fa-shield-halved text-2xl drop-shadow-[0_2px_4px_rgba(0,0,0,0.4)]"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tight font-tech text-white">RENGGANIS NET</h1>
            <p class="text-xs text-blue-200/80 font-bold uppercase tracking-widest mt-1">Pemulihan Kata Sandi Akun</p>
        </div>

        <!-- Glass Console Card -->
        <div class="bg-[#1e293b]/90 backdrop-blur-2xl border border-white/20 rounded-3xl p-7 sm:p-8 shadow-[0_30px_70px_rgba(0,0,0,0.6),inset_0_1px_1px_rgba(255,255,255,0.2)]">
            
            <div class="flex items-center justify-between mb-5 pb-4 border-b border-white/10">
                <div>
                    <h2 class="text-base font-black text-white font-tech">Verifikasi OTP Email</h2>
                    <p class="text-[11px] text-slate-400">Atur ulang kata sandi dengan verifikasi 6 digit</p>
                </div>
                <a href="login.php" class="text-xs text-blue-400 hover:text-blue-300 font-bold transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    <span>Login</span>
                </a>
            </div>

            <!-- Global Alert Container -->
            <div id="pageAlert" class="hidden mb-4 p-3 rounded-xl text-xs flex items-center gap-2.5 font-bold"></div>

            <!-- STEP 1: MINTA KODE OTP -->
            <div id="step1" class="space-y-4">
                <p class="text-xs text-slate-300 leading-relaxed font-medium">
                    Masukkan <span class="text-white font-bold">Username</span> atau <span class="text-white font-bold">Alamat Email</span> akun Anda. Sistem akan mengirimkan 6 digit kode OTP ke email terdaftar.
                </p>

                <form onsubmit="handleRequestOtp(event)" class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-black text-slate-300 uppercase tracking-wider mb-1.5">Username / Alamat Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                <i class="fa-solid fa-envelope text-sm"></i>
                            </span>
                            <input type="text" id="pageIdentifier" required placeholder="Contoh: admin atau admin@rengganis.net" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-white/15 text-white placeholder-slate-500 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner">
                        </div>
                    </div>

                    <button type="submit" id="btnReq" class="w-full py-3 px-4 rounded-xl skeuo-btn-primary font-black text-xs shadow-lg flex items-center justify-center gap-2">
                        <span>Kirim Kode OTP ke Email</span>
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </form>
            </div>

            <!-- STEP 2: VERIFIKASI KODE OTP -->
            <div id="step2" class="hidden space-y-4">
                <div class="p-3.5 rounded-2xl bg-blue-500/15 border border-blue-400/30 text-blue-200 text-xs">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <i class="fa-solid fa-envelope-circle-check text-blue-400 text-sm"></i>
                        <span>Kode OTP Telah Terkirim!</span>
                    </div>
                    <p class="text-[11px] text-blue-100/80 leading-relaxed">
                        Periksa kotak masuk email <span id="targetEmailText" class="font-bold text-white font-mono">-</span>:
                    </p>
                </div>

                <!-- Local Dev Demo OTP Banner -->
                <div id="demoOtpBoxPage" class="hidden p-3 rounded-2xl bg-amber-500/20 border border-amber-400/50 text-amber-200 text-xs">
                    <span class="text-[10px] uppercase font-black tracking-widest text-amber-400 block mb-0.5">Mode Lokal / Simulasi Email:</span>
                    <span class="font-medium text-[11px]">Kode OTP Anda: </span>
                    <strong id="demoOtpCodePage" class="font-mono text-base font-black text-amber-300 tracking-wider">123456</strong>
                </div>

                <form onsubmit="handleVerifyOtp(event)" class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-black text-slate-300 uppercase tracking-wider mb-1.5 text-center">6 Digit Kode OTP</label>
                        <input type="text" id="pageOtpInput" maxlength="6" pattern="[0-9]{6}" required placeholder="000000" class="w-full py-3 text-center rounded-xl bg-slate-900/90 border border-white/20 text-white placeholder-slate-600 text-2xl font-black font-mono tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner">
                        <p class="text-[10px] text-slate-400 text-center mt-1.5 font-medium">
                            <i class="fa-solid fa-clock text-slate-500 mr-1"></i>Berlaku selama 15 menit
                        </p>
                    </div>

                    <button type="submit" id="btnVer" class="w-full py-3 px-4 rounded-xl skeuo-btn-emerald font-black text-xs shadow-lg flex items-center justify-center gap-2">
                        <span>Verifikasi Kode OTP</span>
                        <i class="fa-solid fa-check text-xs"></i>
                    </button>

                    <div class="text-center pt-1">
                        <button type="button" onclick="resendOtp()" id="btnResend" class="text-xs text-blue-400 hover:text-blue-300 font-bold transition-colors">
                            Belum terima email? Kirim ulang
                        </button>
                    </div>
                </form>
            </div>

            <!-- STEP 3: BUAT KATA SANDI BARU -->
            <div id="step3" class="hidden space-y-4">
                <p class="text-xs text-slate-300 leading-relaxed font-medium">
                    Verifikasi berhasil! Silakan tentukan kata sandi baru untuk akun Anda.
                </p>

                <form onsubmit="handleResetPassword(event)" class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-black text-slate-300 uppercase tracking-wider mb-1.5">Kata Sandi Baru</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </span>
                            <input type="password" id="pageNewPassword" required minlength="6" placeholder="Minimal 6 karakter" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-white/15 text-white placeholder-slate-500 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-300 uppercase tracking-wider mb-1.5">Konfirmasi Kata Sandi Baru</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                <i class="fa-solid fa-lock-open text-sm"></i>
                            </span>
                            <input type="password" id="pageConfirmPassword" required minlength="6" placeholder="Ulangi kata sandi baru" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-white/15 text-white placeholder-slate-500 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner">
                        </div>
                    </div>

                    <button type="submit" id="btnRes" class="w-full py-3 px-4 rounded-xl skeuo-btn-primary font-black text-xs shadow-lg flex items-center justify-center gap-2">
                        <span>Simpan Kata Sandi Baru</span>
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                    </button>
                </form>
            </div>

            <!-- STEP 4: SELESAI / SUKSES -->
            <div id="step4" class="hidden text-center py-4 space-y-4">
                <div class="w-16 h-16 mx-auto rounded-3xl bg-emerald-500/20 border border-emerald-400/50 flex items-center justify-center text-emerald-400 text-3xl shadow-lg">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <h4 class="text-base font-black text-white font-tech">Kata Sandi Berhasil Diperbarui!</h4>
                    <p class="text-xs text-slate-300 mt-1 font-medium">Akun Anda kini dapat diakses kembali dengan kata sandi baru.</p>
                </div>
                <div class="pt-2">
                    <a href="login.php" class="w-full py-3 px-4 rounded-xl skeuo-btn-emerald font-black text-xs shadow-lg flex items-center justify-center gap-2">
                        <span>Masuk ke Console Sekarang</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>

        </div>

        <div class="mt-6 text-center text-[11px] text-slate-400 font-medium">
            &copy; <?= date('Y') ?> Rengganis Net. All rights reserved.
        </div>

    </div>

    <script>
        let currentUserId = 0;
        let activeIdentifier = '';

        function showAlert(msg, type = 'error') {
            const box = document.getElementById('pageAlert');
            box.classList.remove('hidden', 'bg-rose-500/20', 'border-rose-500/50', 'text-rose-200', 'bg-emerald-500/20', 'border-emerald-500/50', 'text-emerald-200');
            if (type === 'error') {
                box.classList.add('bg-rose-500/20', 'border', 'border-rose-500/50', 'text-rose-200');
                box.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-rose-400"></i> <span>${msg}</span>`;
            } else {
                box.classList.add('bg-emerald-500/20', 'border', 'border-emerald-500/50', 'text-emerald-200');
                box.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-400"></i> <span>${msg}</span>`;
            }
        }

        function hideAlert() {
            document.getElementById('pageAlert').classList.add('hidden');
        }

        function setStep(num) {
            hideAlert();
            document.getElementById('step1').classList.toggle('hidden', num !== 1);
            document.getElementById('step2').classList.toggle('hidden', num !== 2);
            document.getElementById('step3').classList.toggle('hidden', num !== 3);
            document.getElementById('step4').classList.toggle('hidden', num !== 4);
        }

        async function handleRequestOtp(e) {
            e.preventDefault();
            const btn = document.getElementById('btnReq');
            const orig = btn.innerHTML;
            const identifier = document.getElementById('pageIdentifier').value.trim();

            if (!identifier) {
                showAlert('Silakan masukkan username atau email.');
                return;
            }

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim OTP...';
                hideAlert();

                const res = await fetch('api/auth.php?action=request_otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier: identifier })
                });

                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.message || 'Gagal mengirim OTP.');
                }

                currentUserId = data.user_id;
                activeIdentifier = identifier;
                document.getElementById('targetEmailText').innerText = data.masked_email;

                if (data.demo_otp) {
                    document.getElementById('demoOtpBoxPage').classList.remove('hidden');
                    document.getElementById('demoOtpCodePage').innerText = data.demo_otp;
                }

                setStep(2);
                document.getElementById('pageOtpInput').value = '';
                document.getElementById('pageOtpInput').focus();
            } catch (err) {
                showAlert(err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        }

        async function resendOtp() {
            if (!activeIdentifier) return;
            const btn = document.getElementById('btnResend');
            const orig = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Mengirim ulang...';
            try {
                const res = await fetch('api/auth.php?action=request_otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier: activeIdentifier })
                });
                const data = await res.json();
                if (data.success) {
                    showAlert('Kode OTP baru telah dikirimkan ke email Anda!', 'success');
                    if (data.demo_otp) {
                        document.getElementById('demoOtpBoxPage').classList.remove('hidden');
                        document.getElementById('demoOtpCodePage').innerText = data.demo_otp;
                    }
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (e) {
                showAlert('Gagal mengirim ulang OTP.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerText = orig;
            }
        }

        async function handleVerifyOtp(e) {
            e.preventDefault();
            const btn = document.getElementById('btnVer');
            const orig = btn.innerHTML;
            const otp = document.getElementById('pageOtpInput').value.trim();

            if (!otp || otp.length !== 6) {
                showAlert('Kode OTP harus 6 digit angka.');
                return;
            }

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memverifikasi...';
                hideAlert();

                const res = await fetch('api/auth.php?action=verify_otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: currentUserId, otp: otp })
                });

                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.message || 'Kode OTP tidak valid.');
                }

                setStep(3);
                document.getElementById('pageNewPassword').value = '';
                document.getElementById('pageConfirmPassword').value = '';
                document.getElementById('pageNewPassword').focus();
            } catch (err) {
                showAlert(err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        }

        async function handleResetPassword(e) {
            e.preventDefault();
            const btn = document.getElementById('btnRes');
            const orig = btn.innerHTML;
            const pwd = document.getElementById('pageNewPassword').value;
            const confirm = document.getElementById('pageConfirmPassword').value;

            if (pwd.length < 6) {
                showAlert('Kata sandi baru minimal 6 karakter.');
                return;
            }

            if (pwd !== confirm) {
                showAlert('Konfirmasi kata sandi tidak cocok.');
                return;
            }

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan Sandi...';
                hideAlert();

                const res = await fetch('api/auth.php?action=reset_password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: currentUserId,
                        password: pwd,
                        password_confirm: confirm
                    })
                });

                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.message || 'Gagal mengubah kata sandi.');
                }

                setStep(4);
            } catch (err) {
                showAlert(err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        }
    </script>
</body>
</html>
