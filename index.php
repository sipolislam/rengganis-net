<?php
/**
 * Dashboard Overview for Rengganis Net
 */

$pageTitle = 'Dashboard Ringkasan';
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

// Collection Rate (PRD Section 11): Paid bills / Total active bills * 100
$collectionRate = $totalBills > 0 ? round(($countPaid / $totalBills) * 100, 1) : 0;

// 3. Unpaid & Overdue Bills for Current & Previous Periods
$stmtPending = $pdo->prepare("
    SELECT b.*, c.customer_code, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
           p.name AS package_name, p.speed AS package_speed
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN packages p ON c.package_id = p.id
    WHERE b.status = 'belum_lunas'
    ORDER BY b.due_date ASC, b.id DESC
    LIMIT 10
");
$stmtPending->execute();
$pendingBills = $stmtPending->fetchAll();

// 4. Recent Status Changes Audit Log (FR-03 & Section 11)
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

    <!-- Top Greeting & Header Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 rounded-3xl p-6 md:p-8 text-white shadow-xl shadow-blue-900/10 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
        
        <div class="relative z-10 space-y-1">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur text-xs font-semibold text-blue-200">
                <i class="fa-solid fa-calendar-day"></i> Periode <?= $monthName ?> <?= $curYear ?>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">Halo, <?= e($currentUser['name']) ?>!</h1>
            <p class="text-sm text-blue-100/90 max-w-xl">
                Sistem billing dan monitoring pelanggan WiFi siap beroperasi. Pantau tagihan, catat pelunasan, dan cetak struk thermal lapangan dengan cepat.
            </p>
        </div>

        <?php if (isAdmin()): ?>
        <div class="relative z-10 flex flex-wrap items-center gap-2.5">
            <button onclick="triggerGenerateBills()" class="px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-900 font-bold text-xs shadow-md shadow-amber-400/20 transition-all flex items-center gap-2">
                <i class="fa-solid fa-bolt"></i> Generate Tagihan <?= $monthName ?>
            </button>
            <a href="customers.php?action=create" class="px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-white font-semibold text-xs border border-white/20 transition-all flex items-center gap-2">
                <i class="fa-solid fa-user-plus"></i> Tambah Pelanggan
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- METRICS CARDS GRID (FR-08 & Section 10 UX) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
        
        <!-- CARD 1: Pemasukan Bulan Ini -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pemasukan (<?= $monthName ?>)</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="fa-solid fa-wallet text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-xl md:text-2xl font-extrabold text-slate-900"><?= formatRupiah($totalIncome) ?></div>
                <div class="text-xs text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-check-circle"></i> <?= $countPaid ?> tagihan terbayar
                </div>
            </div>
        </div>

        <!-- CARD 2: Tagihan Tertunggak / Belum Lunas -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Belum Lunas (<?= $monthName ?>)</span>
                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-xl md:text-2xl font-extrabold text-rose-600"><?= formatRupiah($totalUnpaid) ?></div>
                <div class="text-xs text-rose-500 font-semibold mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= $countUnpaid ?> pelanggan menunggak
                </div>
            </div>
        </div>

        <!-- CARD 3: Status Pelanggan (Aktif & Cuti) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pelanggan Aktif</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i class="fa-solid fa-users text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-xl md:text-2xl font-extrabold text-slate-900"><?= (int)$custStats['total_aktif'] ?> <span class="text-xs font-normal text-slate-500">User</span></div>
                <div class="text-xs text-amber-600 font-semibold mt-1 flex items-center gap-1.5" title="Pelanggan cuti otomatis tidak ditagih (FR-03)">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <span><?= (int)$custStats['total_cuti'] ?> Sedang Cuti</span>
                    <?php if ((int)$custStats['total_isolir'] > 0): ?>
                        <span class="text-rose-600 ml-1">• <?= (int)$custStats['total_isolir'] ?> Isolir</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- CARD 4: Collection Rate (%) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Collection Rate</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="fa-solid fa-percent text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-xl md:text-2xl font-extrabold text-indigo-700"><?= $collectionRate ?>%</div>
                <!-- Progress bar -->
                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                    <div class="bg-indigo-600 h-1.5 rounded-full" style="width: <?= min(100, max(0, $collectionRate)) ?>%"></div>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">Target kolektibilitas: 100%</div>
            </div>
        </div>

    </div>

    <!-- MAIN TWO-COLUMN CONTENT AREA -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT COLUMN: Daftar Tagihan Menunggak & Siap Ditagih (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            
            <div class="p-5 border-b border-slate-200/80 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                        <i class="fa-solid fa-hand-holding-dollar text-sm"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 text-sm">Tagihan Belum Lunas (Menunggak)</h2>
                        <p class="text-xs text-slate-500">Daftar pelanggan yang perlu ditagih atau diproses</p>
                    </div>
                </div>
                <a href="bills.php?status=belum_lunas" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors flex items-center gap-1">
                    Lihat Semua <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <?php if (empty($pendingBills)): ?>
                <div class="p-8 text-center text-slate-400">
                    <div class="w-12 h-12 mx-auto rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mb-2">
                        <i class="fa-solid fa-circle-check text-xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">Luar biasa! Tidak ada tagihan menunggak.</p>
                    <p class="text-xs text-slate-400 mt-1">Semua tagihan untuk periode ini telah diselesaikan.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 text-slate-500 border-b border-slate-200 uppercase font-bold text-[10px] tracking-wider">
                                <th class="py-3 px-4">Pelanggan</th>
                                <th class="py-3 px-4">Paket & Periode</th>
                                <th class="py-3 px-4">Jatuh Tempo</th>
                                <th class="py-3 px-4 text-right">Tagihan</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
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
                                <tr id="bill-row-<?= $bill['id'] ?>" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-900"><?= e($bill['customer_name']) ?></div>
                                        <div class="text-[11px] text-slate-500 font-mono"><?= e($bill['customer_code']) ?> • <?= e($bill['customer_phone']) ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-slate-800"><?= e($bill['package_name']) ?></div>
                                        <div class="text-[11px] text-slate-400"><?= getMonthName((int)$bill['period_month']) ?> <?= $bill['period_year'] ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-rose-600 font-semibold"><?= formatDate($bill['due_date']) ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-extrabold text-slate-900">
                                        <?= formatRupiah($bill['amount']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-center status-col">
                                        <?= getStatusBadge($bill['status']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-right action-col">
                                        <button onclick='openPaymentModal(<?= $billJson ?>)' class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition-all flex items-center gap-1.5 ml-auto">
                                            <i class="fa-solid fa-money-bill-wave"></i>
                                            <span>Bayar</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>

        <!-- RIGHT COLUMN: Log Aktivitas Status & Informasi Cuti (1 Col) -->
        <div class="space-y-6">
            
            <!-- Ketentuan Status Cuti (FR-03 Informational Banner) -->
            <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200/80 rounded-2xl p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fa-solid fa-mug-hot text-sm"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-bold text-amber-900 text-xs">Aturan Status "Cuti" (FR-03)</h3>
                        <p class="text-xs text-amber-800 leading-relaxed">
                            Pelanggan berstatus <strong>Cuti</strong> secara otomatis dikecualikan dari siklus generate tagihan bulanan tanpa dikenakan denda keterlambatan.
                        </p>
                        <a href="customers.php?status=cuti" class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-900 hover:underline pt-1">
                            Kelola Pelanggan Cuti (<?= (int)$custStats['total_cuti'] ?>) &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Status Changes Log (FR-03 & Section 11 Analytics) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-blue-600"></i> Riwayat Perubahan Status
                    </h3>
                </div>

                <?php if (empty($recentLogs)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center">Belum ada riwayat perubahan status.</p>
                <?php else: ?>
                    <div class="divide-y divide-slate-100 mt-2">
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="py-2.5 space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-800"><?= e($log['customer_name']) ?></span>
                                    <span class="text-[10px] text-slate-400"><?= formatDate($log['created_at'], true) ?></span>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs">
                                    <?= getStatusBadge($log['old_status']) ?>
                                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
                                    <?= getStatusBadge($log['new_status']) ?>
                                </div>
                                <?php if ($log['reason']): ?>
                                    <p class="text-[11px] text-slate-500 italic bg-slate-50 rounded px-2 py-1">
                                        "<?= e($log['reason']) ?>"
                                    </p>
                                <?php endif; ?>
                                <div class="text-[10px] text-slate-400">
                                    Oleh: <span class="font-medium text-slate-600"><?= e($log['changed_by']) ?></span>
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
    if (!confirm('Apakah Anda yakin ingin men-generate tagihan untuk semua pelanggan aktif pada bulan <?= $monthName ?> <?= $curYear ?>? Pelanggan berstatus "Cuti" akan otomatis dilewati.')) {
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
