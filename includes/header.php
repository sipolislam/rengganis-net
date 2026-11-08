<?php
/**
 * Modern AdminLTE Neo-Skeuomorphic Header Layout for Rengganis Net
 * Futuristic Luxury Aesthetic (Neumorphism + Skeuomorphism Hybrid)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

requireLogin();
$currentUser = getCurrentUser();
$activePage = basename($_SERVER['PHP_SELF'], '.php');
$pdo = getDB();

// Quick badge counts for sidebar
$sidebarCustActive = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'aktif'")->fetchColumn();
$sidebarCustCuti   = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'cuti'")->fetchColumn();
$sidebarUnpaidBills = (int)$pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'belum_lunas' AND period_month = " . (int)date('n') . " AND period_year = " . (int)date('Y'))->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="description" content="Sistem Informasi Manajemen Operasional, Penagihan WiFi Bulanan Bebas Tagihan Fiktif Cuti, dan Cetak Struk Bluetooth ESC/POS">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' • Rengganis Net Cyber Panel' : 'Rengganis Net • Luxury WiFi Billing Management' ?></title>
    
    <!-- Google Fonts: Plus Jakarta Sans, Space Grotesk, JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=JetBrains+Mono:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Immediate Theme Initialization (Zero Flash-of-Unstyled-Theme / Anti-FOUC) -->
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('rengganis_theme');
                if (savedTheme === 'dark' || (!savedTheme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch(e) {}
        })();

        function updateThemeUI(isDark) {
            const btnLight = document.getElementById('themeBtnLight');
            const btnDark = document.getElementById('themeBtnDark');
            const metaThemeColor = document.querySelector('meta[name="theme-color"]');
            
            if (metaThemeColor) {
                metaThemeColor.setAttribute('content', isDark ? '#0b1120' : '#1e3a8a');
            }

            if (btnLight && btnDark) {
                if (isDark) {
                    btnLight.className = "flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-slate-200";
                    const lightIcon = btnLight.querySelector('i');
                    if (lightIcon) lightIcon.className = "fa-solid fa-sun text-xs text-slate-400";
                    
                    btnDark.className = "flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer neu-flat bg-slate-800 text-amber-300 shadow-sm border border-slate-700/60";
                    const darkIcon = btnDark.querySelector('i');
                    if (darkIcon) darkIcon.className = "fa-solid fa-moon text-xs text-amber-300 drop-shadow-[0_0_6px_rgba(252,211,77,0.5)]";
                } else {
                    btnLight.className = "flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer neu-flat bg-white text-amber-600 shadow-sm border border-amber-200/50";
                    const lightIcon = btnLight.querySelector('i');
                    if (lightIcon) lightIcon.className = "fa-solid fa-sun text-xs text-amber-500 drop-shadow-[0_0_6px_rgba(245,158,11,0.5)]";
                    
                    btnDark.className = "flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-slate-600";
                    const darkIcon = btnDark.querySelector('i');
                    if (darkIcon) darkIcon.className = "fa-solid fa-moon text-xs text-slate-400";
                }
            }
        }

        function setTheme(theme) {
            const isDark = (theme === 'dark');
            if (isDark) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('rengganis_theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('rengganis_theme', 'light');
            }
            updateThemeUI(isDark);
            try {
                window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: isDark ? 'dark' : 'light' } }));
            } catch(e) {}
        }

        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');
            setTheme(isDark ? 'light' : 'dark');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');
            updateThemeUI(isDark);
        });
    </script>
    
    <!-- Tailwind CSS (Play CDN) with Dark Mode enabled -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        cyber: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            200: '#bae0fd',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            900: '#082f49'
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                        tech: ['"Space Grotesk"', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome 6 Pro-grade Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- html2canvas for Digital Receipt Capture -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <!-- Luxury Neo-Skeuomorphic Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css?v=<?= file_exists(__DIR__ . '/../assets/css/custom.css') ? filemtime(__DIR__ . '/../assets/css/custom.css') : '1.0' ?>">

    <!-- CSRF Protection Token -->
    <meta name="csrf-token" content="<?= e(generateCsrfToken()) ?>">
    <script>
        window.csrfToken = "<?= e(generateCsrfToken()) ?>";
    </script>
</head>
<body class="bg-[#e9eff6] dark:bg-[#0b1120] text-slate-800 dark:text-slate-100 antialiased overflow-hidden flex h-screen h-[100dvh] transition-colors duration-200">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarBackdrop" onclick="toggleSidebar()" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm hidden md:hidden transition-opacity"></div>

    <!-- ====================================================================
         ADMINLTE STYLE LUXURY SIDEBAR (Neumorphic Surface + Skeuomorphic Glow)
         ==================================================================== -->
    <aside id="mainSidebar" class="fixed md:static inset-y-0 left-0 z-50 w-72 flex-shrink-0 flex flex-col bg-[#edf3f9] border-r border-white/60 shadow-[8px_0_24px_rgba(166,180,200,0.5)] transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
        
        <!-- Brand Header (Metallic Embossed Logo) -->
        <div class="h-20 flex items-center justify-between px-6 border-b border-slate-200/60 bg-gradient-to-b from-[#f8fbff] to-[#edf3f9] relative">
            <a href="index.php" class="flex items-center gap-3 group">
                <!-- 3D Skeuomorphic Hologram Logo -->
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-700 via-indigo-600 to-cyan-500 flex items-center justify-center text-white shadow-[0_8px_16px_rgba(37,99,235,0.4),inset_0_1px_1px_rgba(255,255,255,0.8),inset_0_-2px_0_rgba(0,0,0,0.3)] border border-blue-400/50 group-hover:scale-105 transition-transform">
                    <i class="fa-solid fa-wifi text-xl drop-shadow-[0_2px_4px_rgba(0,0,0,0.3)]"></i>
                </div>
                <div>
                    <span class="text-base font-extrabold tracking-tight font-tech bg-gradient-to-r from-blue-800 via-indigo-700 to-blue-900 bg-clip-text text-transparent block leading-tight">
                        RENGGANIS NET
                    </span>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-slate-400 flex items-center gap-1.5 mt-0.5">
                        <span class="led-pulse active-green"></span> FIBER BROADBAND
                    </span>
                </div>
            </a>

            <!-- Mobile Close Button -->
            <button onclick="toggleSidebar()" class="md:hidden w-8 h-8 rounded-lg neu-inset flex items-center justify-center text-slate-500 hover:text-slate-800">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <!-- User Profile Card (Tactile Neo-Skeuomorphic Panel) -->
        <div class="px-5 py-4">
            <div class="neu-inset rounded-2xl p-3 flex items-center gap-3">
                <div class="relative">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-slate-800 to-slate-900 flex items-center justify-center text-white shadow-md font-bold text-sm">
                        <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 led-pulse <?= $currentUser['role'] === 'admin' ? 'active-blue' : ($currentUser['role'] === 'kolektor' ? 'active-amber' : 'active-green') ?>"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-extrabold text-slate-800 truncate leading-tight"><?= e($currentUser['name']) ?></div>
                    <div class="mt-1 flex items-center gap-1">
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 shadow-sm border border-amber-300">
                                <i class="fa-solid fa-crown text-[8px]"></i> Admin
                            </span>
                        <?php elseif ($currentUser['role'] === 'kolektor'): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-cyan-600 text-white shadow-sm">
                                <i class="fa-solid fa-cash-register text-[8px]"></i> Kolektor
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-emerald-500 text-white shadow-sm">
                                <i class="fa-solid fa-wrench text-[8px]"></i> Teknisi
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Navigation Menu (AdminLTE Hierarchical Grouping) -->
        <nav class="flex-1 px-4 py-2 space-y-6 overflow-y-auto">
            
            <!-- GROUP 1: UTAMA & OPERASIONAL -->
            <div class="space-y-1.5">
                <div class="px-3 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">
                    Menu Utama
                </div>

                <a href="index.php" class="sidebar-link <?= $activePage === 'index' ? 'active' : '' ?>">
                    <div class="sidebar-icon-box">
                        <i class="fa-solid fa-gauge-high"></i>
                    </div>
                    <span class="flex-1">Dashboard</span>
                </a>

                <a href="customers.php" class="sidebar-link <?= $activePage === 'customers' ? 'active' : '' ?>">
                    <div class="sidebar-icon-box">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span class="flex-1">Pelanggan WiFi</span>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 text-blue-700 shadow-sm">
                        <?= $sidebarCustActive ?>
                    </span>
                </a>

                <a href="bills.php" class="sidebar-link <?= $activePage === 'bills' ? 'active' : '' ?>">
                    <div class="sidebar-icon-box">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <span class="flex-1">Tagihan & Billing</span>
                    <?php if ($sidebarUnpaidBills > 0): ?>
                        <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-rose-500 text-white shadow-[0_0_8px_rgba(244,63,94,0.6)] animate-pulse">
                            <?= $sidebarUnpaidBills ?>
                        </span>
                    <?php endif; ?>
                </a>

                <a href="packages.php" class="sidebar-link <?= $activePage === 'packages' ? 'active' : '' ?>">
                    <div class="sidebar-icon-box">
                        <i class="fa-solid fa-cubes"></i>
                    </div>
                    <span class="flex-1">Paket Bandwidth</span>
                </a>
            </div>

            <!-- GROUP 2: KEUANGAN & SISTEM (Admin Only) -->
            <?php if (isAdmin()): ?>
            <div class="space-y-1.5 pt-2 border-t border-slate-200/60">
                <div class="px-3 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">
                    Administrasi & Laporan
                </div>

                <a href="reports.php" class="sidebar-link <?= $activePage === 'reports' ? 'active' : '' ?>">
                    <div class="sidebar-icon-box">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <span class="flex-1">Laporan Keuangan</span>
                </a>

                <a href="settings.php" class="sidebar-link <?= $activePage === 'settings' ? 'active' : '' ?>">
                    <div class="sidebar-icon-box">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <span class="flex-1">Pengaturan Sistem</span>
                </a>

                <a href="users.php" class="sidebar-link <?= $activePage === 'users' ? 'active' : '' ?>">
                    <div class="sidebar-icon-box">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <span class="flex-1">Manajemen Pengguna</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- GROUP 3: HARDWARE / PERANGKAT CETAK -->
            <div class="pt-2 border-t border-slate-200/60">
                <div class="px-3 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">
                    Koneksi Hardware
                </div>
                <div class="neu-inset-sm rounded-xl p-3 text-[11px] space-y-1.5 text-slate-600">
                    <div class="flex items-center justify-between">
                        <span>Thermal Printer:</span>
                        <span class="font-bold text-blue-600 flex items-center gap-1">
                            <i class="fa-brands fa-bluetooth text-blue-500"></i> ESC/POS
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-slate-400">
                        <span>Lebar Kertas:</span>
                        <span class="font-mono font-bold text-slate-600">58mm / 80mm</span>
                    </div>
                </div>
            </div>

        </nav>

        <!-- Sidebar Footer Logout Button -->
        <div class="p-4 border-t border-slate-200/60 bg-[#edf3f9]">
            <a href="logout.php" class="w-full py-2.5 px-4 rounded-xl skeuo-btn-secondary text-xs font-bold flex items-center justify-center gap-2 text-rose-600 hover:text-rose-700 transition-colors">
                <i class="fa-solid fa-power-off"></i> Keluar Sistem
            </a>
        </div>

    </aside>

    <!-- ====================================================================
         MAIN CONTENT AREA WRAPPER (Fluid layout with luxury top navigation)
         ==================================================================== -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        
        <!-- Top Navbar (Skeuomorphic Brushed Metal Finish) -->
        <header class="h-20 flex-shrink-0 bg-gradient-to-b from-[#ffffff] to-[#edf3f9] border-b border-white/80 shadow-[0_4px_14px_rgba(166,180,200,0.3)] px-4 sm:px-8 flex items-center justify-between z-30">
            
            <!-- Left: Sidebar Toggle & Page Title -->
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <button onclick="toggleSidebar()" class="w-10 h-10 shrink-0 rounded-xl neu-flat flex items-center justify-center text-slate-600 hover:text-blue-600 active:scale-95 transition-all">
                    <i class="fa-solid fa-bars-staggered text-base"></i>
                </button>

                <div class="min-w-0">
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white tracking-tight font-tech truncate max-w-[110px] xs:max-w-[160px] sm:max-w-none">
                        <?= isset($pageTitle) ? e($pageTitle) : 'Overview Panel' ?>
                    </h2>
                    <div class="text-[11px] text-slate-400 font-medium hidden sm:flex items-center gap-1.5">
                        <span>Rengganis Net</span> &bull; 
                        <span class="text-blue-600 font-semibold">Management Console</span>
                    </div>
                </div>
            </div>

            <!-- Right: Realtime Digital Clock, Day/Night Mode & Quick Action -->
            <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                
                <!-- Live Clock Widget -->
                <div class="hidden lg:flex items-center gap-2 neu-inset px-3.5 py-2 rounded-xl text-xs font-mono font-bold text-slate-700 dark:text-slate-200">
                    <i class="fa-regular fa-clock text-blue-600 dark:text-cyan-400"></i>
                    <span id="liveClockWidget"><?= date('H:i:s') ?> WIB</span>
                </div>

                <!-- Day / Night Mode Toggle Widget (Fitur Tampilan Siang & Malam) -->
                <div class="flex items-center p-1 rounded-xl neu-inset gap-1 text-xs select-none" id="themeSwitchPill" role="group" aria-label="Mode Tampilan Siang / Malam">
                    <button type="button" onclick="setTheme('light')" id="themeBtnLight" class="flex items-center gap-1.5 px-2 py-1.5 sm:px-2.5 rounded-lg text-xs font-bold transition-all cursor-pointer neu-flat bg-white text-amber-600 shadow-sm border border-amber-200/50" title="Beralih ke Tampilan Siang (Light Mode)">
                        <i class="fa-solid fa-sun text-xs text-amber-500 drop-shadow-[0_0_6px_rgba(245,158,11,0.5)]"></i>
                        <span class="hidden sm:inline text-[11px] font-semibold">Siang</span>
                    </button>
                    <button type="button" onclick="setTheme('dark')" id="themeBtnDark" class="flex items-center gap-1.5 px-2 py-1.5 sm:px-2.5 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" title="Beralih ke Tampilan Malam (Dark Mode)">
                        <i class="fa-solid fa-moon text-xs text-slate-400"></i>
                        <span class="hidden sm:inline text-[11px] font-semibold">Malam</span>
                    </button>
                </div>

                <!-- Quick Action Button: Tambah Pelanggan Baru (Tampil di Layar HP & Laptop) -->
                <?php if (isAdmin()): ?>
                <a href="customers.php?action=create" onclick="<?php if ($activePage === 'customers'): ?>openAddCustomerModal(); return false;<?php endif; ?>" class="inline-flex items-center gap-1.5 px-2.5 py-2 sm:px-3.5 sm:py-2 rounded-xl skeuo-btn-primary text-xs font-bold shadow-md active:scale-95 transition-all" title="Tambah Pelanggan Baru">
                    <i class="fa-solid fa-user-plus text-xs"></i>
                    <span class="hidden sm:inline">Pelanggan Baru</span>
                    <span class="inline sm:hidden text-[11px] font-bold">+ Pelanggan</span>
                </a>
                <?php endif; ?>

                <!-- User Profile Quick Icon -->
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl neu-flat flex items-center justify-center text-blue-700 dark:text-cyan-400 font-bold text-xs sm:text-sm shadow-sm" title="<?= e($currentUser['name']) ?>">
                    <i class="fa-solid fa-user-shield"></i>
                </div>

            </div>
        </header>

        <!-- Scrollable Main View Container -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
            
            <!-- Flash Message Alerts with Luxury Tactile Frames -->
            <?php if ($flashSuccess = getFlash('success')): ?>
                <div class="p-4 rounded-2xl skeuo-panel border-l-4 border-l-emerald-500 text-emerald-900 text-xs flex items-center justify-between gap-3 shadow-md animate-fade-in">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <span class="font-semibold text-sm"><?= e($flashSuccess) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950 p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($flashError = getFlash('error')): ?>
                <div class="p-4 rounded-2xl skeuo-panel border-l-4 border-l-rose-500 text-rose-900 text-xs flex items-center justify-between gap-3 shadow-md animate-fade-in">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <span class="font-semibold text-sm"><?= e($flashError) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-950 p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($flashInfo = getFlash('info')): ?>
                <div class="p-4 rounded-2xl skeuo-panel border-l-4 border-l-blue-500 text-blue-900 text-xs flex items-center justify-between gap-3 shadow-md animate-fade-in">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <span class="font-semibold text-sm"><?= e($flashInfo) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-blue-700 hover:text-blue-950 p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>
