<?php
/**
 * Financial & Collection Reports for Rengganis Net
 * FR-08: Laporan pemasukan harian dan bulanan
 */

$pageTitle = 'Laporan Keuangan';
require_once __DIR__ . '/includes/auth.php';
requireAdmin();
$pdo = getDB();

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $month = (int)($_GET['month'] ?? date('n'));
    $year  = (int)($_GET['year'] ?? date('Y'));

    $stmt = $pdo->prepare("
        SELECT b.bill_number, c.customer_code, c.name AS customer_name, c.phone, p.name AS package_name,
               b.amount, b.status, b.payment_date, b.payment_method, b.collector_name
        FROM bills b
        JOIN customers c ON b.customer_id = c.id
        JOIN packages p ON c.package_id = p.id
        WHERE b.period_month = ? AND b.period_year = ?
        ORDER BY b.payment_date ASC, b.id ASC
    ");
    $stmt->execute([$month, $year]);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan-RengganisNet-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.csv');
    $output = fopen('php://output', 'w');
    // BOM for Excel
    fputs($output, "\xEF\xBB\xBF");
    fputcsv($output, ['No Invoice', 'Kode Pelanggan', 'Nama Pelanggan', 'No Telp', 'Paket WiFi', 'Nominal', 'Status', 'Tanggal Bayar', 'Metode Bayar', 'Kolektor']);

    foreach ($rows as $r) {
        fputcsv($output, [
            $r['bill_number'],
            $r['customer_code'],
            $r['customer_name'],
            $r['phone'],
            $r['package_name'],
            $r['amount'],
            strtoupper($r['status']),
            $r['payment_date'] ?: '-',
            $r['payment_method'] ?: '-',
            $r['collector_name'] ?: '-'
        ]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/includes/header.php';

$selectedMonth = (int)($_GET['month'] ?? date('n'));
$selectedYear  = (int)($_GET['year'] ?? date('Y'));

// 1. Overall Monthly Aggregates
$stmtMonth = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_bills,
        SUM(amount) AS total_bill_amount,
        SUM(CASE WHEN status = 'lunas' THEN amount ELSE 0 END) AS total_income,
        SUM(CASE WHEN status = 'lunas' THEN 1 ELSE 0 END) AS count_paid,
        SUM(CASE WHEN status = 'belum_lunas' THEN amount ELSE 0 END) AS total_unpaid,
        SUM(CASE WHEN status = 'belum_lunas' THEN 1 ELSE 0 END) AS count_unpaid
    FROM bills
    WHERE period_month = ? AND period_year = ?
");
$stmtMonth->execute([$selectedMonth, $selectedYear]);
$monthAgg = $stmtMonth->fetch();

$totalIncome = (float)($monthAgg['total_income'] ?? 0);
$totalUnpaid = (float)($monthAgg['total_unpaid'] ?? 0);
$countPaid   = (int)($monthAgg['count_paid'] ?? 0);
$countUnpaid = (int)($monthAgg['count_unpaid'] ?? 0);
$totalBills  = (int)($monthAgg['total_bills'] ?? 0);
$collectionRate = $totalBills > 0 ? round(($countPaid / $totalBills) * 100, 1) : 0;

// 2. Daily Income Breakdown
$stmtDaily = $pdo->prepare("
    SELECT 
        DATE(payment_date) AS pay_day,
        COUNT(*) AS transactions,
        SUM(amount) AS daily_income,
        GROUP_CONCAT(payment_method SEPARATOR ', ') AS methods
    FROM bills
    WHERE status = 'lunas' 
      AND period_month = ? AND period_year = ?
    GROUP BY DATE(payment_date)
    ORDER BY pay_day DESC
");
$stmtDaily->execute([$selectedMonth, $selectedYear]);
$dailyRows = $stmtDaily->fetchAll();

// 3. Payment Method Breakdown (Tunai vs Transfer vs DANA/QRIS)
$stmtMethods = $pdo->prepare("
    SELECT 
        IFNULL(payment_method, 'Tunai') AS method,
        COUNT(*) AS count,
        SUM(amount) AS total
    FROM bills
    WHERE status = 'lunas' 
      AND period_month = ? AND period_year = ?
    GROUP BY payment_method
    ORDER BY total DESC
");
$stmtMethods->execute([$selectedMonth, $selectedYear]);
$methodRows = $stmtMethods->fetchAll();

// 4. Detail Paid Transactions
$stmtPaidDetails = $pdo->prepare("
    SELECT b.*, c.customer_code, c.name AS customer_name, p.name AS package_name
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN packages p ON c.package_id = p.id
    WHERE b.status = 'lunas' AND b.period_month = ? AND b.period_year = ?
    ORDER BY b.payment_date DESC
");
$stmtPaidDetails->execute([$selectedMonth, $selectedYear]);
$paidTransactions = $stmtPaidDetails->fetchAll();
?>

<div class="space-y-6">

    <!-- Header & Export Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight">Laporan Rekapitulasi & Pemasukan</h1>
            <p class="text-xs md:text-sm text-slate-500">Analisis arus kas tagihan WiFi harian, bulanan, dan tingkat kolektibilitas</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="reports.php?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&export=csv" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2">
                <i class="fa-solid fa-file-excel"></i> Unduh CSV / Excel
            </a>
            <button onclick="window.print()" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md transition-all flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Filter Month/Year -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <form action="reports.php" method="GET" class="flex flex-wrap items-center gap-3 text-xs">
            <span class="font-bold text-slate-700">Pilih Periode:</span>
            <select name="month" class="px-3 py-1.5 rounded-xl border border-slate-300 font-semibold bg-white">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= getMonthName($m) ?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="px-3 py-1.5 rounded-xl border border-slate-300 font-semibold bg-white">
                <?php for ($y = 2025; $y <= 2027; $y++): ?>
                    <option value="<?= $y ?>" <?= $y === $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold">
                Tampilkan
            </button>
        </form>
    </div>

    <!-- Monthly Summary Banner -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Pemasukan Masuk</span>
            <div class="text-xl md:text-2xl font-black text-emerald-600 mt-2"><?= formatRupiah($totalIncome) ?></div>
            <div class="text-xs text-slate-500 mt-1"><?= $countPaid ?> dari <?= $totalBills ?> tagihan lunas</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Sisa Tunggakan</span>
            <div class="text-xl md:text-2xl font-black text-rose-600 mt-2"><?= formatRupiah($totalUnpaid) ?></div>
            <div class="text-xs text-slate-500 mt-1"><?= $countUnpaid ?> invoice tertunda</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Collection Rate</span>
            <div class="text-xl md:text-2xl font-black text-indigo-700 mt-2"><?= $collectionRate ?>%</div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2">
                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: <?= min(100, max(0, $collectionRate)) ?>%"></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Nilai Tagihan</span>
            <div class="text-xl md:text-2xl font-black text-slate-900 mt-2"><?= formatRupiah($monthAgg['total_bill_amount'] ?? 0) ?></div>
            <div class="text-xs text-slate-500 mt-1">Periode <?= getMonthName($selectedMonth) ?> <?= $selectedYear ?></div>
        </div>
    </div>

    <!-- Daily Breakdown Table & Payment Methods -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Daily Breakdown (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i class="fa-solid fa-calendar-days text-blue-600"></i> Rekap Pemasukan Harian
            </h3>

            <?php if (empty($dailyRows)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">Belum ada transaksi pembayaran lunas pada bulan ini.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] border-b">
                                <th class="py-2.5 px-3">Tanggal</th>
                                <th class="py-2.5 px-3 text-center">Jumlah Transaksi</th>
                                <th class="py-2.5 px-3 text-right">Pemasukan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($dailyRows as $d): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5 px-3 font-semibold text-slate-800">
                                        <?= formatDate($d['pay_day']) ?>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold"><?= $d['transactions'] ?> struk</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-extrabold text-emerald-700">
                                        <?= formatRupiah($d['daily_income']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment Methods (1 Col) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-indigo-600"></i> Metode Pembayaran
            </h3>

            <?php if (empty($methodRows)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">Belum ada data metode bayar.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($methodRows as $m): ?>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-800 text-xs"><?= e($m['method']) ?></div>
                                <div class="text-[10px] text-slate-500"><?= $m['count'] ?> transaksi</div>
                            </div>
                            <div class="text-right font-extrabold text-blue-600 text-xs">
                                <?= formatRupiah($m['total']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Detail Paid Invoices Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
        <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
            <i class="fa-solid fa-list-check text-emerald-600"></i> Rincian Pembayaran Lunas (<?= count($paidTransactions) ?> Transaksi)
        </h3>

        <?php if (!empty($paidTransactions)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] border-b">
                            <th class="py-2.5 px-3">No. Invoice</th>
                            <th class="py-2.5 px-3">Pelanggan</th>
                            <th class="py-2.5 px-3">Paket</th>
                            <th class="py-2.5 px-3">Tgl Bayar</th>
                            <th class="py-2.5 px-3">Metode</th>
                            <th class="py-2.5 px-3">Kasir</th>
                            <th class="py-2.5 px-3 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($paidTransactions as $p): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-2.5 px-3 font-mono font-semibold text-slate-800"><?= e($p['bill_number']) ?></td>
                                <td class="py-2.5 px-3 font-bold text-slate-900"><?= e($p['customer_name']) ?> (<?= e($p['customer_code']) ?>)</td>
                                <td class="py-2.5 px-3 text-slate-700"><?= e($p['package_name']) ?></td>
                                <td class="py-2.5 px-3 text-slate-600"><?= formatDate($p['payment_date'], true) ?></td>
                                <td class="py-2.5 px-3 font-medium text-slate-700"><?= e($p['payment_method'] ?: 'Tunai') ?></td>
                                <td class="py-2.5 px-3 text-slate-600"><?= e($p['collector_name'] ?: 'Admin') ?></td>
                                <td class="py-2.5 px-3 text-right font-extrabold text-emerald-700"><?= formatRupiah($p['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
