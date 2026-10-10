<?php
/**
 * Header Layout for Rengganis Net
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

requireLogin();
$currentUser = getCurrentUser();
$activePage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - Rengganis Net' : 'Rengganis Net - Billing & Pelanggan WiFi' ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Play CDN for rapid reactive styling) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace']
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- html2canvas for Digital Receipt Image Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <!-- Custom CSS & Print Media -->
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans antialiased pb-20 md:pb-6">

    <!-- Top Navigation Bar -->
    <nav class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200/80 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Brand & Logo -->
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-wifi text-lg"></i>
                        </div>
                        <div>
                            <span class="text-lg font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-700 to-indigo-700 tracking-tight leading-none block">Rengganis Net</span>
                            <span class="text-[11px] text-slate-500 font-medium">Billing & Manajemen Pelanggan</span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <div class="hidden md:flex items-center gap-1">
                    <a href="index.php" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition-colors <?= $activePage === 'index' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-gauge-high mr-1.5 opacity-70"></i> Dashboard
                    </a>
                    <a href="customers.php" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition-colors <?= $activePage === 'customers' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-users mr-1.5 opacity-70"></i> Pelanggan
                    </a>
                    <a href="bills.php" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition-colors <?= $activePage === 'bills' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-file-invoice-dollar mr-1.5 opacity-70"></i> Tagihan
                    </a>
                    <a href="packages.php" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition-colors <?= $activePage === 'packages' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-cubes mr-1.5 opacity-70"></i> Paket
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="reports.php" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition-colors <?= $activePage === 'reports' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-chart-line mr-1.5 opacity-70"></i> Laporan
                    </a>
                    <a href="settings.php" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition-colors <?= $activePage === 'settings' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-gear mr-1.5 opacity-70"></i> Pengaturan
                    </a>
                    <a href="users.php" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition-colors <?= $activePage === 'users' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-user-shield mr-1.5 opacity-70"></i> Pengguna
                    </a>
                    <?php endif; ?>
                </div>

                <!-- User Profile & Quick Actions -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 pl-3 border-l border-slate-200">
                        <div class="text-right hidden sm:block">
                            <div class="text-xs font-bold text-slate-800 leading-tight"><?= e($currentUser['name']) ?></div>
                            <div class="text-[10px] uppercase font-semibold tracking-wider text-blue-600">
                                <?= $currentUser['role'] === 'admin' ? '<span class="text-blue-600"><i class="fa-solid fa-crown mr-1"></i>Admin</span>' : '<span class="text-emerald-600"><i class="fa-solid fa-wrench mr-1"></i>Teknisi</span>' ?>
                            </div>
                        </div>
                        <a href="logout.php" title="Keluar / Logout" class="w-9 h-9 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 flex items-center justify-center transition-colors">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation (Optimized for Smartphone Field Collection) -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200 shadow-lg px-2 py-1">
        <div class="grid grid-cols-5 text-center">
            <a href="index.php" class="flex flex-col items-center py-1.5 <?= $activePage === 'index' ? 'text-blue-600 font-bold' : 'text-slate-500' ?>">
                <i class="fa-solid fa-gauge-high text-base"></i>
                <span class="text-[10px] mt-1">Beranda</span>
            </a>
            <a href="customers.php" class="flex flex-col items-center py-1.5 <?= $activePage === 'customers' ? 'text-blue-600 font-bold' : 'text-slate-500' ?>">
                <i class="fa-solid fa-users text-base"></i>
                <span class="text-[10px] mt-1">Pelanggan</span>
            </a>
            <a href="bills.php" class="flex flex-col items-center py-1.5 <?= $activePage === 'bills' ? 'text-blue-600 font-bold' : 'text-slate-500' ?>">
                <div class="relative">
                    <i class="fa-solid fa-file-invoice-dollar text-base"></i>
                </div>
                <span class="text-[10px] mt-1">Tagihan</span>
            </a>
            <?php if (isAdmin()): ?>
            <a href="reports.php" class="flex flex-col items-center py-1.5 <?= $activePage === 'reports' ? 'text-blue-600 font-bold' : 'text-slate-500' ?>">
                <i class="fa-solid fa-chart-line text-base"></i>
                <span class="text-[10px] mt-1">Laporan</span>
            </a>
            <a href="settings.php" class="flex flex-col items-center py-1.5 <?= $activePage === 'settings' ? 'text-blue-600 font-bold' : 'text-slate-500' ?>">
                <i class="fa-solid fa-gear text-base"></i>
                <span class="text-[10px] mt-1">Setelan</span>
            </a>
            <?php else: ?>
            <a href="packages.php" class="flex flex-col items-center py-1.5 <?= $activePage === 'packages' ? 'text-blue-600 font-bold' : 'text-slate-500' ?>">
                <i class="fa-solid fa-cubes text-base"></i>
                <span class="text-[10px] mt-1">Paket</span>
            </a>
            <a href="logout.php" class="flex flex-col items-center py-1.5 text-rose-500">
                <i class="fa-solid fa-power-off text-base"></i>
                <span class="text-[10px] mt-1">Keluar</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Container -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <!-- Flash Messages Display -->
        <?php if ($flashSuccess = getFlash('success')): ?>
            <div class="mb-5 flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm animate-fade-in">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                <div class="text-sm font-medium"><?= e($flashSuccess) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($flashError = getFlash('error')): ?>
            <div class="mb-5 flex items-center gap-3 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 shadow-sm animate-fade-in">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg"></i>
                <div class="text-sm font-medium"><?= e($flashError) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($flashInfo = getFlash('info')): ?>
            <div class="mb-5 flex items-center gap-3 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 shadow-sm animate-fade-in">
                <i class="fa-solid fa-circle-info text-blue-600 text-lg"></i>
                <div class="text-sm font-medium"><?= e($flashInfo) ?></div>
            </div>
        <?php endif; ?>
