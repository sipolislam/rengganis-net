<?php
/**
 * Luxury Neo-Skeuomorphic Dashboard Overview for Rengganis Net
 * AdminLTE Sidebar Layout with Neumorphic + Skeuomorphic Finish
 */

$pageTitle = 'Dashboard Utama';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

$curMonth = (int)date('n');
$curYear = (int)date('Y');
$monthName = getMonthName($curMonth);

// 1. Metrics: Customer Counts
$stmtCust = $pdo->query("
    SELECT 
        COUNT(*) AS total_all,
        SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) AS total_aktif,
        SUM(CASE WHEN status = 'cuti' THEN 1 ELSE 0 END) AS total_cuti,
        SUM(CASE WHEN status = 'isolir' THEN 1 ELSE 0 END) AS total_isolir
    FROM customers
");
$custStats = $stmtCust->fetch();

// 2. Metrics: Current Month Financials (Oktober 2026)
$stmtFinance = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_bills,
        SUM(CASE WHEN status = 'lunas' THEN amount ELSE 0 END) AS total_income,
        SUM(CASE WHEN status = 'lunas' THEN 1 ELSE 0 END) AS count_paid,
        SUM(CASE WHEN status = 'belum_lunas' THEN amount ELSE 0 END) AS total_unpaid,
        SUM(CASE WHEN status = 'belum_lunas' THEN 1 ELSE 0 END) AS count_unpaid
    FROM bills
    WHERE period_month = ? AND period_year = ?
");
$stmtFinance->execute([$curMonth, $curYear]);
$finStats = $stmtFinance->fetch();

$totalIncome = (float)($finStats['total_income'] ?? 0);
$totalUnpaid = (float)($finStats['total_unpaid'] ?? 0);
$countPaid = (int)($finStats['count_paid'] ?? 0);
$countUnpaid = (int)($finStats['count_unpaid'] ?? 0);
$totalBills = (int)($finStats['total_bills'] ?? 0);

// Collection Rate (%): Paid bills / Total active bills * 100
$collectionRate = $totalBills > 0 ? round(($countPaid / $totalBills) * 100, 1) : 0;

// 3. Unpaid & Overdue Bills
$stmtPending = $pdo->prepare("
    SELECT b.*, c.customer_code, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
           p.name AS package_name, p.speed AS package_speed
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN packages p ON c.package_id = p.id
    WHERE b.status = 'belum_lunas'
    ORDER BY b.due_date ASC, b.id DESC
    LIMIT 8
");
$stmtPending->execute();
$pendingBills = $stmtPending->fetchAll();

// 4. Recent Status Changes Audit Log
$stmtRecentLogs = $pdo->query("
    SELECT sl.*, c.customer_code, c.name AS customer_name
    FROM status_logs sl
    JOIN customers c ON sl.customer_id = c.id
    ORDER BY sl.created_at DESC
    LIMIT 5
");
$recentLogs = $stmtRecentLogs->fetchAll();
?>

<div class="space-y-6">

    <!-- HERO BANNER: Futuristic Holographic Neo-Skeuomorphic Console -->
    <div class="skeuo-panel rounded-3xl p-6 sm:p-8 relative overflow-hidden bg-gradient-to-r from-[#1e293b] via-[#0f172a] to-[#1e1b4b] text-white shadow-[0_20px_45px_rgba(15,23,42,0.35)] border border-slate-700">
        
        <!-- Subtle Futuristic Radial Glows -->
        <div class="absolute -right-16 -top-16 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-48 -bottom-20 w-60 h-60 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold text-blue-200">
                    <span class="led-pulse active-green"></span>
                    <span>Siklus Penagihan Periode <?= $monthName ?> <?= $curYear ?></span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight font-tech text-white">
                    Panel Operasional Rengganis Net
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                    Automasi tagihan WiFi bulanan, monitoring status cuti/isolir tanpa denda fiktif, serta pencetakan struk thermal Bluetooth lapangan.
                </p>
            </div>

            <?php if (isAdmin()): ?>
            <div class="flex flex-wrap items-center gap-3">
                <button onclick="triggerGenerateBills()" class="px-5 py-3 rounded-2xl skeuo-btn-emerald text-xs font-black shadow-lg flex items-center gap-2">
                    <i class="fa-solid fa-bolt text-amber-300"></i> Generate Tagihan <?= $monthName ?>
                </button>
                <a href="customers.php?action=create" class="px-5 py-3 rounded-2xl skeuo-btn-secondary text-xs font-bold flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-blue-600"></i> Tambah Pelanggan
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- METRICS 4-COLUMN GRID (Tactile Neumorphic Cards with Skeuomorphic Sheen) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- CARD 1: Pemasukan Masuk -->
        <div class="neu-flat rounded-3xl p-5 relative overflow-hidden transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-400">Total Pemasukan</span>
                <div class="w-10 h-10 rounded-2xl neu-inset flex items-center justify-center text-emerald-600 shadow-inner">
                    <i class="fa-solid fa-vault text-base"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black text-slate-900 font-mono tracking-tight"><?= formatRupiah($totalIncome) ?></div>
                <div class="mt-2 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-100/70 px-2.5 py-0.5 rounded-full">
                        <span class="led-pulse active-green"></span> <?= $countPaid ?> Terbayar
                    </span>
                    <span class="text-[11px] text-slate-400">Bulan <?= $monthName ?></span>
                </div>
            </div>
        </div>

        <!-- CARD 2: Tagihan Belum Lunas -->
        <div class="neu-flat rounded-3xl p-5 relative overflow-hidden transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-400">Tagihan Tertunggak</span>
                <div class="w-10 h-10 rounded-2xl neu-inset flex items-center justify-center text-rose-600 shadow-inner">
                    <i class="fa-solid fa-clock-rotate-left text-base"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black text-rose-600 font-mono tracking-tight"><?= formatRupiah($totalUnpaid) ?></div>
                <div class="mt-2 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 bg-rose-100/70 px-2.5 py-0.5 rounded-full">
                        <span class="led-pulse active-rose"></span> <?= $countUnpaid ?> Tertunda
                    </span>
                    <span class="text-[11px] text-slate-400">Perlu ditagih</span>
                </div>
            </div>
        </div>

        <!-- CARD 3: Status Pelanggan -->
        <div class="neu-flat rounded-3xl p-5 relative overflow-hidden transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-400">Pelanggan Aktif</span>
                <div class="w-10 h-10 rounded-2xl neu-inset flex items-center justify-center text-blue-600 shadow-inner">
                    <i class="fa-solid fa-network-wired text-base"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black text-slate-900 font-mono tracking-tight">
                    <?= (int)$custStats['total_aktif'] ?> <span class="text-xs font-bold text-slate-400">User</span>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-100/70 px-2.5 py-0.5 rounded-full" title="Pelanggan cuti otomatis tidak ditagih (FR-03)">
                        <span class="led-pulse active-amber"></span> <?= (int)$custStats['total_cuti'] ?> Cuti
                    </span>
                    <?php if ((int)$custStats['total_isolir'] > 0): ?>
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 bg-rose-100/70 px-2 py-0.5 rounded-full">
                            <?= (int)$custStats['total_isolir'] ?> Isolir
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- CARD 4: Collection Rate -->
        <div class="neu-flat rounded-3xl p-5 relative overflow-hidden transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-400">Collection Rate</span>
                <div class="w-10 h-10 rounded-2xl neu-inset flex items-center justify-center text-indigo-600 shadow-inner">
                    <i class="fa-solid fa-chart-pie text-base"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black text-indigo-700 font-mono tracking-tight"><?= $collectionRate ?>%</div>
                <!-- Tactile Metallic Progress Bar -->
                <div class="w-full neu-inset-sm rounded-full h-2 mt-3 overflow-hidden p-0.5">
                    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-500 h-full rounded-full shadow-[0_0_8px_rgba(37,99,235,0.6)]" style="width: <?= min(100, max(0, $collectionRate)) ?>%"></div>
                </div>
                <div class="text-[10px] text-slate-400 mt-1.5 flex justify-between">
                    <span>Efisiensi: <?= $countPaid ?>/<?= $totalBills ?></span>
                    <span class="font-bold text-slate-600">Target 100%</span>
                </div>
            </div>
        </div>

    </div>

    <!-- MAIN TWO-COLUMN DASHBOARD GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT COLUMN: Pending Bills Table (2 Cols) -->
        <div class="lg:col-span-2 neu-flat rounded-3xl overflow-hidden border border-white/70">
            
            <div class="p-5 border-b border-slate-200/60 bg-gradient-to-b from-[#f9fbfe] to-[#eef4fb] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-hand-holding-dollar text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-sm font-tech">Tagihan Belum Lunas (Siap Ditagih)</h3>
                        <p class="text-[11px] text-slate-400 font-medium">Prioritas penagihan lapangan & loket kasir</p>
                    </div>
                </div>
                <a href="bills.php?status=belum_lunas" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors flex items-center gap-1.5 neu-flat-sm px-3 py-1.5 rounded-xl">
                    <span>Lihat Semua</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <?php if (empty($pendingBills)): ?>
                <div class="p-12 text-center text-slate-400">
                    <div class="w-14 h-14 mx-auto rounded-3xl neu-inset flex items-center justify-center text-emerald-500 mb-3">
                        <i class="fa-solid fa-circle-check text-2xl"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-800">Semua Tagihan Lunas!</p>
                    <p class="text-xs text-slate-400 mt-1">Tidak ada tunggakan pembayaran untuk periode saat ini.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/70 text-slate-400 border-b border-slate-200 uppercase font-black text-[10px] tracking-wider">
                                <th class="py-3 px-5">Pelanggan</th>
                                <th class="py-3 px-4">Paket & Periode</th>
                                <th class="py-3 px-4">Jatuh Tempo</th>
                                <th class="py-3 px-4 text-right">Tagihan</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($pendingBills as $bill): ?>
                                <?php
                                $billJson = htmlspecialchars(json_encode([
                                    'id'               => $bill['id'],
                                    'bill_number'      => $bill['bill_number'],
                                    'customer_id'      => $bill['customer_id'],
                                    'customer_code'    => $bill['customer_code'],
                                    'customer_name'    => $bill['customer_name'],
                                    'customer_phone'   => $bill['customer_phone'],
                                    'customer_address' => $bill['customer_address'],
                                    'period_name'      => getMonthName((int)$bill['period_month']) . ' ' . $bill['period_year'],
                                    'package_name'     => $bill['package_name'],
                                    'package_speed'    => $bill['package_speed'],
                                    'amount'           => (float)$bill['amount'],
                                    'amount_formatted' => formatRupiah($bill['amount'])
                                ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr id="bill-row-<?= $bill['id'] ?>" class="hover:bg-white/60 transition-colors">
                                    <td class="py-3.5 px-5">
                                        <div class="font-extrabold text-slate-900"><?= e($bill['customer_name']) ?></div>
                                        <div class="text-[11px] text-blue-600 font-mono font-semibold"><?= e($bill['customer_code']) ?> &bull; <?= e($bill['customer_phone']) ?></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800"><?= e($bill['package_name']) ?></div>
                                        <div class="text-[10px] text-slate-400 font-medium"><?= getMonthName((int)$bill['period_month']) ?> <?= $bill['period_year'] ?></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="text-rose-600 font-black font-mono"><?= formatDate($bill['due_date']) ?></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black text-slate-900 font-mono">
                                        <?= formatRupiah($bill['amount']) ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-center status-col">
                                        <?= getStatusBadge($bill['status']) ?>
                                    </td>
                                    <td class="py-3.5 px-5 text-right action-col">
                                        <button onclick='openPaymentModal(<?= $billJson ?>)' class="px-3.5 py-1.5 rounded-xl skeuo-btn-primary text-xs font-bold shadow-md flex items-center gap-1.5 ml-auto">
                                            <i class="fa-solid fa-money-bill-wave"></i> Bayar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>

        <!-- RIGHT COLUMN: Cuti Policy Widget & Audit Trail (1 Col) -->
        <div class="space-y-6">
            
            <!-- Cuti Policy Card (Tactile Neo-Skeuomorphic Gold Plate) -->
            <div class="skeuo-panel rounded-3xl p-5 border border-amber-200/80 bg-gradient-to-br from-[#fffdfa] to-[#fef8ea] text-amber-950">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-700 flex items-center justify-center font-bold flex-shrink-0 shadow-inner">
                        <i class="fa-solid fa-mug-hot text-base"></i>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="led-pulse active-amber"></span>
                            <h4 class="font-extrabold text-xs uppercase tracking-wider font-tech text-amber-900">Ketentuan Status "Cuti" (FR-03)</h4>
                        </div>
                        <p class="text-[11px] text-amber-800/90 leading-relaxed font-medium">
                            Pelanggan berstatus <strong>Cuti</strong> secara otomatis dikecualikan dari siklus penagihan bulanan berikutnya tanpa akumulasi denda fiktif.
                        </p>
                        <a href="customers.php?status=cuti" class="inline-flex items-center gap-1.5 text-xs font-black text-amber-900 hover:underline pt-1">
                            Kelola Pelanggan Cuti (<?= (int)$custStats['total_cuti'] ?>) &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Audit Trail: Recent Status Changes (Neumorphic Feed) -->
            <div class="neu-flat rounded-3xl p-5 space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200/60">
                    <h4 class="font-extrabold text-xs uppercase tracking-wider text-slate-800 flex items-center gap-2 font-tech">
                        <i class="fa-solid fa-clock-rotate-left text-blue-600"></i> Riwayat Status Pelanggan
                    </h4>
                </div>

                <?php if (empty($recentLogs)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center">Belum ada aktivitas perubahan status.</p>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="py-2.5 space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-black text-slate-900"><?= e($log['customer_name']) ?></span>
                                    <span class="text-[10px] text-slate-400 font-mono"><?= formatDate($log['created_at'], true) ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <?= getStatusBadge($log['old_status']) ?>
                                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
                                    <?= getStatusBadge($log['new_status']) ?>
                                </div>
                                <?php if ($log['reason']): ?>
                                    <p class="text-[11px] text-slate-600 italic neu-inset-sm rounded-lg px-2.5 py-1">
                                        "<?= e($log['reason']) ?>"
                                    </p>
                                <?php endif; ?>
                                <div class="text-[10px] text-slate-400 font-medium">
                                    Petugas: <span class="font-bold text-slate-600"><?= e($log['changed_by']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

</div>

<!-- JavaScript for Dashboard Actions -->
<script>
async function triggerGenerateBills() {
    if (!confirm('Apakah Anda yakin ingin men-generate tagihan untuk semua pelanggan aktif pada bulan <?= $monthName ?> <?= $curYear ?>?\n\nCatatan: Pelanggan yang berstatus CUTI akan otomatis DILEWATI tanpa tagihan fiktif (FR-03).')) {
        return;
    }

    try {
        const response = await fetch('api/bills.php?action=generate_monthly', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                month: <?= $curMonth ?>,
                year: <?= $curYear ?>
            })
        });
        const res = await response.json();
        if (res.success) {
            alert(res.message);
            window.location.reload();
        } else {
            alert('Gagal: ' + res.message);
        }
    } catch (e) {
        alert('Terjadi kesalahan: ' + e.message);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
