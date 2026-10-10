<?php
/**
 * Financial & Collection Reports for Rengganis Net
 * Luxury Neo-Skeuomorphic Layout with AdminLTE Sidebar
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
    fputs($output, "\xEF\xBB\xBF"); // BOM for Excel
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
        SUM(amount) AS daily_income
    FROM bills
    WHERE status = 'lunas' 
      AND period_month = ? AND period_year = ?
    GROUP BY DATE(payment_date)
    ORDER BY pay_day DESC
");
$stmtDaily->execute([$selectedMonth, $selectedYear]);
$dailyRows = $stmtDaily->fetchAll();

// 3. Payment Method Breakdown
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

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-tech">Laporan Rekapitulasi Keuangan</h1>
            <p class="text-xs text-slate-500 font-medium">Analisis arus kas tagihan WiFi harian, bulanan, dan rasio efisiensi penagihan</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="reports.php?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&export=csv" class="px-5 py-2.5 rounded-2xl skeuo-btn-emerald text-xs font-black shadow-lg flex items-center gap-2">
                <i class="fa-solid fa-file-excel"></i> Unduh CSV / Excel
            </a>
            <button onclick="window.print()" class="px-5 py-2.5 rounded-2xl skeuo-btn-dark text-xs font-black shadow-lg flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Filter Month/Year Bar (Neumorphic Inset) -->
    <div class="neu-flat rounded-3xl p-4 sm:p-5">
        <form action="reports.php" method="GET" class="flex flex-wrap items-center gap-3 text-xs">
            <span class="font-bold text-slate-700">Pilih Periode Laporan:</span>
            <select name="month" class="neu-input px-3.5 py-2 rounded-xl font-bold text-slate-800">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= getMonthName($m) ?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="neu-input px-3.5 py-2 rounded-xl font-bold text-slate-800">
                <?php for ($y = 2025; $y <= 2027; $y++): ?>
                    <option value="<?= $y ?>" <?= $y === $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="px-5 py-2 skeuo-btn-primary rounded-xl font-black shadow-md">
                Tampilkan
            </button>
        </form>
    </div>

    <!-- 4 Metrics Cards Grid (Neumorphic Surfaces) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="neu-flat rounded-3xl p-5">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Pemasukan Masuk</span>
            <div class="text-xl font-black text-emerald-700 mt-2 font-mono"><?= formatRupiah($totalIncome) ?></div>
            <div class="text-[11px] text-slate-500 mt-1 font-semibold"><?= $countPaid ?> dari <?= $totalBills ?> tagihan lunas</div>
        </div>

        <div class="neu-flat rounded-3xl p-5">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Sisa Tunggakan Belum Bayar</span>
            <div class="text-xl font-black text-rose-600 mt-2 font-mono"><?= formatRupiah($totalUnpaid) ?></div>
            <div class="text-[11px] text-slate-500 mt-1 font-semibold"><?= $countUnpaid ?> invoice tertunda</div>
        </div>

        <div class="neu-flat rounded-3xl p-5">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Collection Rate</span>
            <div class="text-xl font-black text-indigo-700 mt-2 font-mono"><?= $collectionRate ?>%</div>
            <div class="w-full neu-inset-sm rounded-full h-2 mt-2 overflow-hidden p-0.5">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-full rounded-full shadow-[0_0_8px_rgba(37,99,235,0.6)]" style="width: <?= min(100, max(0, $collectionRate)) ?>%"></div>
            </div>
        </div>

        <div class="neu-flat rounded-3xl p-5">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Potensi Tagihan</span>
            <div class="text-xl font-black text-slate-900 mt-2 font-mono"><?= formatRupiah($monthAgg['total_bill_amount'] ?? 0) ?></div>
            <div class="text-[11px] text-slate-500 mt-1 font-semibold">Periode <?= getMonthName($selectedMonth) ?> <?= $selectedYear ?></div>
        </div>
    </div>

    <!-- Daily Breakdown Table & Payment Methods -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Daily Breakdown (2 Cols) -->
        <div class="lg:col-span-2 neu-flat rounded-3xl p-5 space-y-4">
            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
                <i class="fa-solid fa-calendar-days text-blue-600"></i> Rekapitulasi Pemasukan Harian
            </h3>

            <?php if (empty($dailyRows)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">Belum ada transaksi pembayaran lunas pada bulan terpilih.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/70 text-slate-400 uppercase font-black text-[10px] border-b">
                                <th class="py-2.5 px-3">Tanggal Pelunasan</th>
                                <th class="py-2.5 px-3 text-center">Jumlah Transaksi</th>
                                <th class="py-2.5 px-3 text-right">Pemasukan Harian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($dailyRows as $d): ?>
                                <tr class="hover:bg-white/60">
                                    <td class="py-3 px-3 font-bold text-slate-900 font-mono">
                                        <?= formatDate($d['pay_day']) ?>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800 font-black font-mono text-[11px]"><?= $d['transactions'] ?> struk</span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-black text-emerald-700 font-mono text-sm">
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
        <div class="neu-flat rounded-3xl p-5 space-y-4">
            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
                <i class="fa-solid fa-chart-pie text-indigo-600"></i> Metode Pembayaran
            </h3>

            <?php if (empty($methodRows)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">Belum ada data metode bayar.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($methodRows as $m): ?>
                        <div class="p-3.5 rounded-2xl neu-inset flex items-center justify-between">
                            <div>
                                <div class="font-extrabold text-slate-900 text-xs"><?= e($m['method']) ?></div>
                                <div class="text-[10px] text-slate-400 font-bold"><?= $m['count'] ?> transaksi</div>
                            </div>
                            <div class="text-right font-black text-blue-700 font-mono text-xs">
                                <?= formatRupiah($m['total']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Detail Paid Invoices Table -->
    <div class="neu-flat rounded-3xl p-5 space-y-4">
        <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
            <i class="fa-solid fa-list-check text-emerald-600"></i> Rincian Pembayaran Selesai (<?= count($paidTransactions) ?> Transaksi)
        </h3>

        <?php if (!empty($paidTransactions)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100/70 text-slate-400 uppercase font-black text-[10px] border-b">
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
                            <tr class="hover:bg-white/60">
                                <td class="py-3 px-3 font-mono font-bold text-slate-900"><?= e($p['bill_number']) ?></td>
                                <td class="py-3 px-3 font-black text-slate-900"><?= e($p['customer_name']) ?> <span class="text-slate-400 font-mono">(<?= e($p['customer_code']) ?>)</span></td>
                                <td class="py-3 px-3 text-slate-700 font-medium"><?= e($p['package_name']) ?></td>
                                <td class="py-3 px-3 text-slate-600 font-mono"><?= formatDate($p['payment_date'], true) ?></td>
                                <td class="py-3 px-3 font-bold text-slate-800"><?= e($p['payment_method'] ?: 'Tunai') ?></td>
                                <td class="py-3 px-3 text-slate-600 font-medium"><?= e($p['collector_name'] ?: 'Admin') ?></td>
                                <td class="py-3 px-3 text-right font-black text-emerald-700 font-mono text-sm"><?= formatRupiah($p['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
