<?php
/**
 * 403 Forbidden Error View for Rengganis Net
 * Luxury Neo-Skeuomorphic Cyber Console Aesthetic
 */

if (!headers_sent()) {
    http_response_code(403);
}

$currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
$roleTitle = match ($currentUser['role'] ?? '') {
    'admin'    => 'Admin Pengelola',
    'kolektor' => 'Kolektor / Kasir Lapangan',
    'teknisi'  => 'Teknisi Lapangan',
    default    => 'Tamu / Belum Teridentifikasi'
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Akses Ditolak • Rengganis Net</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-gradient-to-br from-[#0f172a] via-[#1e293b] to-[#0f172a] text-slate-100 min-h-screen flex items-center justify-center p-4 relative overflow-hidden select-none">

    <!-- Ambient Glowing Spheres -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-rose-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-amber-600/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-lg w-full relative z-10">
        
        <!-- Luxury Neo-Skeuomorphic Console Card -->
        <div class="bg-[#1e293b]/90 backdrop-blur-2xl border border-rose-500/30 rounded-3xl p-8 sm:p-10 shadow-[0_30px_70px_rgba(0,0,0,0.6),inset_0_1px_1px_rgba(255,255,255,0.15)] text-center">
            
            <!-- Warning 3D Hologram Badge -->
            <div class="w-20 h-20 mx-auto rounded-3xl bg-gradient-to-tr from-rose-700 via-rose-600 to-amber-500 flex items-center justify-center text-white shadow-[0_12px_28px_rgba(225,29,72,0.45),inset_0_1px_1px_rgba(255,255,255,0.8),inset_0_-3px_0_rgba(0,0,0,0.4)] border border-rose-400/50 mb-6">
                <i class="fa-solid fa-shield-halved text-3xl drop-shadow-[0_2px_4px_rgba(0,0,0,0.4)]"></i>
            </div>

            <!-- Error Code & Title -->
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-500/20 border border-rose-400/40 text-rose-300 text-xs font-mono font-bold uppercase tracking-wider mb-3">
                <span class="led-pulse active-rose"></span> HTTP 403 FORBIDDEN
            </div>
            
            <h1 class="text-2xl sm:text-3xl font-black text-white font-tech tracking-tight mb-2">
                Akses Ditolak
            </h1>
            
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed mb-6 font-medium">
                <?= isset($message) ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : 'Anda tidak memiliki hak akses yang mencukupi untuk membuka menu atau memproses aksi ini.' ?>
            </p>

            <!-- User Status Console Panel -->
            <?php if ($currentUser): ?>
            <div class="bg-slate-900/80 rounded-2xl p-4 border border-white/10 mb-6 text-left font-mono text-xs space-y-2">
                <div class="flex items-center justify-between text-slate-400 text-[11px]">
                    <span>Pengguna Aktif:</span>
                    <span class="text-white font-bold font-sans"><?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?> (@<?= htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8') ?>)</span>
                </div>
                <div class="flex items-center justify-between text-slate-400 text-[11px]">
                    <span>Hak Akses Terdaftar:</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-sans uppercase <?= $currentUser['role'] === 'admin' ? 'bg-amber-400/20 text-amber-300 border border-amber-400/40' : ($currentUser['role'] === 'kolektor' ? 'bg-cyan-400/20 text-cyan-300 border border-cyan-400/40' : 'bg-emerald-400/20 text-emerald-300 border border-emerald-400/40') ?>">
                        <?= htmlspecialchars($roleTitle, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <div class="flex items-center justify-between text-slate-400 text-[11px] pt-1 border-t border-white/5">
                    <span>Kebijakan Otorisasi:</span>
                    <span class="text-amber-300 font-bold">Strict Server-side (F1)</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="index.php" class="w-full sm:w-auto px-6 py-3 rounded-xl skeuo-btn-primary font-black text-xs shadow-lg flex items-center justify-center gap-2">
                    <i class="fa-solid fa-house text-xs"></i>
                    <span>Kembali ke Dashboard</span>
                </a>
                <a href="logout.php" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-white/10 text-slate-300 font-bold text-xs transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                    <span>Ganti Akun</span>
                </a>
            </div>

        </div>

        <div class="mt-6 text-center text-[11px] text-slate-400 font-medium">
            &copy; <?= date('Y') ?> Rengganis Net &bull; Role-Based Access Control Security Module
        </div>

    </div>

</body>
</html>
