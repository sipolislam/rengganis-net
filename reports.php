<?php
/**
 * Financial & Collection Reports for Rengganis Net
 * Luxury Neo-Skeuomorphic Layout with AdminLTE Sidebar (F6 / T-020)
 * Advanced Excel & Official Formal Print Views with Date Range & Monthly Filters
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAdmin();

$pdo = getDB();

// 1. Filter Parameters (F6 / T-020)
$filterMode    = $_GET['mode'] ?? (!empty($_GET['start_date']) || !empty($_GET['end_date']) ? 'date_range' : 'month');
$startDate     = $_GET['start_date'] ?? '';
$endDate       = $_GET['end_date'] ?? '';
$selectedMonth = (int)($_GET['month'] ?? date('n'));
$selectedYear  = (int)($_GET['year'] ?? date('Y'));
$filterStatus  = $_GET['status'] ?? 'all'; // all, lunas, belum_lunas
$filterMethod  = $_GET['method'] ?? 'all'; // all, Tunai, Transfer BCA, dll
$filterCollector = $_GET['collector'] ?? 'all';
$exportType    = $_GET['export'] ?? '';

// Normalize date range
if ($filterMode === 'date_range') {
    if (empty($startDate)) $startDate = date('Y-m-01');
    if (empty($endDate))   $endDate   = date('Y-m-d');
    $periodLabel = formatDate($startDate) . ' s/d ' . formatDate($endDate);
    $queryParamString = "mode=date_range&start_date=" . urlencode($startDate) . "&end_date=" . urlencode($endDate);
} else {
    $filterMode = 'month';
    $periodLabel = getMonthName($selectedMonth) . ' ' . $selectedYear;
    $queryParamString = "mode=month&month={$selectedMonth}&year={$selectedYear}";
}

$queryParamString .= "&status=" . urlencode($filterStatus);
if ($filterMethod !== 'all') $queryParamString .= "&method=" . urlencode($filterMethod);
if ($filterCollector !== 'all') $queryParamString .= "&collector=" . urlencode($filterCollector);

$companyName    = getSetting($pdo, 'company_name', 'Rengganis Net');
$companyTagline = getSetting($pdo, 'company_tagline', 'Solusi Internet Cepat & Hemat');
$companyPhone   = getSetting($pdo, 'company_phone', '0812-3456-7890');
$companyEmail   = getSetting($pdo, 'company_email', 'admin@rengganis.net');
$companyAddress = getSetting($pdo, 'company_address', 'Jl. Raya Rengganis No. 45, Sumber Malang');

// 2. Build Dynamic SQL Filters (F6 / T-020)
$whereConditions = [];
$params = [];

if ($filterMode === 'date_range') {
    if ($filterStatus === 'lunas') {
        $whereConditions[] = "b.status = 'lunas' AND DATE(b.payment_date) BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDate;
        $params[':end_date']   = $endDate;
    } elseif ($filterStatus === 'belum_lunas') {
        $whereConditions[] = "b.status = 'belum_lunas' AND b.due_date BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDate;
        $params[':end_date']   = $endDate;
    } else {
        $whereConditions[] = "((b.status = 'lunas' AND DATE(b.payment_date) BETWEEN :start_date1 AND :end_date1) OR (b.status = 'belum_lunas' AND b.due_date BETWEEN :start_date2 AND :end_date2))";
        $params[':start_date1'] = $startDate;
        $params[':end_date1']   = $endDate;
        $params[':start_date2'] = $startDate;
        $params[':end_date2']   = $endDate;
    }
} else {
    $whereConditions[] = "b.period_month = :m AND b.period_year = :y";
    $params[':m'] = $selectedMonth;
    $params[':y'] = $selectedYear;

    if ($filterStatus === 'lunas') {
        $whereConditions[] = "b.status = 'lunas'";
    } elseif ($filterStatus === 'belum_lunas') {
        $whereConditions[] = "b.status = 'belum_lunas'";
    }
}

if ($filterMethod !== 'all' && !empty($filterMethod)) {
    $whereConditions[] = "b.payment_method = :method";
    $params[':method'] = $filterMethod;
}

if ($filterCollector !== 'all' && !empty($filterCollector)) {
    $whereConditions[] = "b.collector_name = :collector";
    $params[':collector'] = $filterCollector;
}

$whereClause = implode(' AND ', $whereConditions);

// 3. Overall Financial Aggregates for Filtered Dataset (F6 / T-020)
$stmtAgg = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_bills,
        SUM(b.amount) AS total_bill_amount,
        SUM(CASE WHEN b.status = 'lunas' THEN b.amount ELSE 0 END) AS total_income,
        SUM(CASE WHEN b.status = 'lunas' THEN 1 ELSE 0 END) AS count_paid,
        SUM(CASE WHEN b.status = 'belum_lunas' THEN b.amount ELSE 0 END) AS total_unpaid,
        SUM(CASE WHEN b.status = 'belum_lunas' THEN 1 ELSE 0 END) AS count_unpaid
    FROM bills b
    WHERE {$whereClause}
");
$stmtAgg->execute($params);
$reportAgg = $stmtAgg->fetch();

$totalIncome    = (float)($reportAgg['total_income'] ?? 0);
$totalUnpaid    = (float)($reportAgg['total_unpaid'] ?? 0);
$countPaid      = (int)($reportAgg['count_paid'] ?? 0);
$countUnpaid    = (int)($reportAgg['count_unpaid'] ?? 0);
$totalBills     = (int)($reportAgg['total_bills'] ?? 0);
$totalBillAmount = (float)($reportAgg['total_bill_amount'] ?? 0);
$collectionRate = $totalBills > 0 ? round(($countPaid / $totalBills) * 100, 1) : 0;

// Customer counts
$stmtCustCounts = $pdo->query("
    SELECT 
        SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) AS total_aktif,
        SUM(CASE WHEN status = 'cuti' THEN 1 ELSE 0 END) AS total_cuti,
        SUM(CASE WHEN status = 'isolir' THEN 1 ELSE 0 END) AS total_isolir
    FROM customers
");
$custStats = $stmtCustCounts->fetch();

// 4. Query Detailed Bills with Filter
$sql = "
    SELECT b.*, c.customer_code, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
           p.name AS package_name, p.speed AS package_speed
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN packages p ON c.package_id = p.id
    WHERE {$whereClause}
    ORDER BY b.status ASC, b.payment_date DESC, b.due_date ASC, b.id ASC
";
$stmtBills = $pdo->prepare($sql);
$stmtBills->execute($params);
$bills = $stmtBills->fetchAll();

// 5. Payment Methods Breakdown for Filtered Range
$sqlMethods = "
    SELECT 
        COALESCE(NULLIF(TRIM(b.payment_method), ''), 'Tunai') AS method,
        COUNT(*) AS count,
        SUM(b.amount) AS total
    FROM bills b
    WHERE {$whereClause} AND b.status = 'lunas'
    GROUP BY COALESCE(NULLIF(TRIM(b.payment_method), ''), 'Tunai')
    ORDER BY total DESC
";
$stmtMethods = $pdo->prepare($sqlMethods);
$stmtMethods->execute($params);
$methodRows = $stmtMethods->fetchAll();

// 6. Daily Income Breakdown for Filtered Range
$sqlDaily = "
    SELECT 
        DATE(b.payment_date) AS pay_day,
        COUNT(*) AS transactions,
        SUM(b.amount) AS daily_income
    FROM bills b
    WHERE {$whereClause} AND b.status = 'lunas'
    GROUP BY DATE(b.payment_date)
    ORDER BY pay_day DESC
";
$stmtDaily = $pdo->prepare($sqlDaily);
$stmtDaily->execute($params);
$dailyRows = $stmtDaily->fetchAll();

// Available Collectors for dropdown filter
$stmtAllCollectors = $pdo->query("
    SELECT DISTINCT collector_name 
    FROM bills 
    WHERE collector_name IS NOT NULL AND TRIM(collector_name) != '' 
    ORDER BY collector_name ASC
");
$availableCollectors = $stmtAllCollectors->fetchAll(PDO::FETCH_COLUMN);

// Common metadata for export handlers (F6 / T-021)
$exportMeta = [
    'company_name' => $companyName,
    'company_tagline' => $companyTagline,
    'company_phone' => $companyPhone,
    'company_address' => $companyAddress,
    'company_email' => $companyEmail,
    'period_label' => $periodLabel,
    'filter_mode' => $filterMode,
    'filter_status' => $filterStatus,
    'filter_method' => $filterMethod,
    'filter_collector' => $filterCollector,
    'user_name' => $_SESSION['user_name'] ?? 'Admin Operasional',
    'total_cuti' => (int)($custStats['total_cuti'] ?? 0)
];

// =========================================================================
// HANDLER 1: EXPORT EXCEL PROFESIONAL (.xls FORMAT - F6 / T-021)
// =========================================================================
if ($exportType === 'excel') {
    $safeName = $filterMode === 'date_range' ? "{$startDate}_sd_{$endDate}" : "{$selectedYear}-" . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT);
    $filename = "Laporan-Keuangan-RengganisNet-{$safeName}.xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo buildFinancialReportExcelHtml($bills, $reportAgg, $methodRows, $exportMeta);
    exit;
}

// =========================================================================
// HANDLER 2: EXPORT CSV MURNI DENGAN RINGKASAN KPI (F6 / T-021)
// =========================================================================
if ($exportType === 'csv') {
    $safeName = $filterMode === 'date_range' ? "{$startDate}_sd_{$endDate}" : "{$selectedYear}-" . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT);
    $filename = "Data-Tagihan-RengganisNet-{$safeName}.csv";
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo buildFinancialReportCsvString($bills, $reportAgg, $methodRows, $exportMeta);
    exit;
}

// =========================================================================
// VIEW WEB: DASHBOARD LAPORAN (LUXURY NEO-SKEUOMORPHIC)
// =========================================================================
$pageTitle = 'Laporan Keuangan & Audit';
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">

    <!-- Top Header & Action Buttons -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100/70 border border-blue-200 text-xs font-bold text-blue-800 mb-1.5">
                <span class="led-pulse active-green"></span>
                <span>Modul Keuangan & Audit Penagihan (F6)</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-tech">Rekapitulasi Keuangan & Audit Kas</h1>
            <p class="text-xs text-slate-500 font-medium">Laporan terstruktur untuk analisis pemilik usaha, monitoring kas riil, dan cetak dokumen formal</p>
        </div>

        <!-- 3 Primary Export & Print Actions -->
        <div class="flex flex-wrap items-center gap-2.5">
            
            <!-- BUTTON 1: UNDUH EXCEL TERFORMAT -->
            <button type="submit" form="reportFilterForm" name="export" value="excel" class="px-4 py-2.5 rounded-2xl skeuo-btn-emerald text-xs font-black shadow-lg flex items-center gap-2 cursor-pointer" title="Unduh Spreadsheet Excel Resmi">
                <i class="fa-solid fa-file-excel text-sm"></i> Unduh Excel (.xls)
            </button>

            <!-- BUTTON 2: CETAK FORMAL RESMI A4 -->
            <a href="print-report.php?<?= $queryParamString ?>" target="_blank" class="px-4 py-2.5 rounded-2xl skeuo-btn-dark text-xs font-black shadow-lg flex items-center gap-2" title="Cetak Format Dokumen Standar A4">
                <i class="fa-solid fa-print text-sm text-blue-400"></i> Cetak Dokumen Resmi (A4)
            </a>

            <!-- BUTTON 3: UNDUH CSV DATA MURNI -->
            <button type="submit" form="reportFilterForm" name="export" value="csv" class="px-3.5 py-2.5 rounded-2xl skeuo-btn-secondary text-xs font-bold flex items-center gap-1.5 text-slate-600 cursor-pointer" title="Unduh CSV Kompatibel Database">
                <i class="fa-solid fa-file-csv"></i> CSV
            </button>

        </div>
    </div>

    <!-- FILTER CONTROL BAR: DUAL-MODE (RENTANG TANGGAL & BULANAN - F6 / T-020) -->
    <div class="neu-flat rounded-3xl p-5 border border-white/80 shadow-md">
        <form id="reportFilterForm" action="reports.php" method="GET" class="space-y-4">
            
            <!-- Filter Header & Mode Toggle -->
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-200/60">
                <div class="flex items-center gap-2 text-xs font-black text-slate-800">
                    <i class="fa-solid fa-sliders text-blue-600"></i>
                    <span>FILTER LAPORAN KEUANGAN:</span>
                </div>

                <!-- Mode Selector Radio Buttons -->
                <div class="inline-flex rounded-xl bg-slate-200/80 p-0.5 border border-slate-300 shadow-inner text-xs font-bold">
                    <label class="cursor-pointer px-3 py-1.5 rounded-lg transition-all <?= $filterMode === 'date_range' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                        <input type="radio" name="mode" value="date_range" onchange="toggleFilterMode('date_range')" class="hidden" <?= $filterMode === 'date_range' ? 'checked' : '' ?>>
                        <i class="fa-solid fa-calendar-week mr-1"></i> Rentang Tanggal
                    </label>
                    <label class="cursor-pointer px-3 py-1.5 rounded-lg transition-all <?= $filterMode === 'month' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                        <input type="radio" name="mode" value="month" onchange="toggleFilterMode('month')" class="hidden" <?= $filterMode === 'month' ? 'checked' : '' ?>>
                        <i class="fa-solid fa-calendar-days mr-1"></i> Siklus Bulanan
                    </label>
                </div>
            </div>

            <!-- Filter Controls Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3.5 text-xs">
                
                <!-- DATE RANGE INPUTS (Visible when mode=date_range) -->
                <div id="dateRangeInputs" class="sm:col-span-2 grid grid-cols-2 gap-2 <?= $filterMode === 'month' ? 'hidden' : '' ?>">
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-500 mb-1">Tanggal Mulai</label>
                        <input type="date" id="inputStartDate" name="start_date" value="<?= e($startDate) ?>" class="neu-input w-full px-3 py-2 rounded-xl font-bold text-slate-800 text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-500 mb-1">Tanggal Selesai</label>
                        <input type="date" id="inputEndDate" name="end_date" value="<?= e($endDate) ?>" class="neu-input w-full px-3 py-2 rounded-xl font-bold text-slate-800 text-xs">
                    </div>
                </div>

                <!-- MONTHLY INPUTS (Visible when mode=month) -->
                <div id="monthlyInputs" class="sm:col-span-2 grid grid-cols-2 gap-2 <?= $filterMode === 'date_range' ? 'hidden' : '' ?>">
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-500 mb-1">Pilih Bulan</label>
                        <select name="month" class="neu-input w-full px-3 py-2 rounded-xl font-bold text-slate-800 text-xs">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= getMonthName($m) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-500 mb-1">Pilih Tahun</label>
                        <select name="year" class="neu-input w-full px-3 py-2 rounded-xl font-bold text-slate-800 text-xs">
                            <?php for ($y = 2024; $y <= 2028; $y++): ?>
                                <option value="<?= $y ?>" <?= $y === $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- Status Tagihan Select -->
                <div>
                    <label class="block text-[10px] font-extrabold uppercase text-slate-500 mb-1">Status Pembayaran</label>
                    <select name="status" class="neu-input w-full px-3 py-2 rounded-xl font-bold text-slate-800 text-xs">
                        <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Semua Status (Lunas & Belum)</option>
                        <option value="lunas" <?= $filterStatus === 'lunas' ? 'selected' : '' ?>>Lunas Saja (Kas Masuk)</option>
                        <option value="belum_lunas" <?= $filterStatus === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas Saja (Piutang)</option>
                    </select>
                </div>

                <!-- Metode Pembayaran Select -->
                <div>
                    <label class="block text-[10px] font-extrabold uppercase text-slate-500 mb-1">Metode Bayar</label>
                    <select name="method" class="neu-input w-full px-3 py-2 rounded-xl font-bold text-slate-800 text-xs">
                        <option value="all" <?= $filterMethod === 'all' ? 'selected' : '' ?>>Semua Metode</option>
                        <option value="Tunai" <?= $filterMethod === 'Tunai' ? 'selected' : '' ?>>Tunai (Cash)</option>
                        <option value="Transfer BCA" <?= $filterMethod === 'Transfer BCA' ? 'selected' : '' ?>>Transfer BCA</option>
                        <option value="Transfer BRI" <?= $filterMethod === 'Transfer BRI' ? 'selected' : '' ?>>Transfer BRI</option>
                        <option value="QRIS" <?= $filterMethod === 'QRIS' ? 'selected' : '' ?>>QRIS</option>
                    </select>
                </div>

                <!-- Petugas / Kolektor Select -->
                <div>
                    <label class="block text-[10px] font-extrabold uppercase text-slate-500 mb-1">Petugas / Kolektor</label>
                    <select name="collector" class="neu-input w-full px-3 py-2 rounded-xl font-bold text-slate-800 text-xs">
                        <option value="all" <?= $filterCollector === 'all' ? 'selected' : '' ?>>Semua Petugas</option>
                        <?php foreach ($availableCollectors as $col): ?>
                            <option value="<?= e($col) ?>" <?= $filterCollector === $col ? 'selected' : '' ?>><?= e($col) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tombol Aksi Filter -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl skeuo-btn-primary font-black text-xs shadow-md flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-filter"></i>
                        <span>Terapkan</span>
                    </button>
                    <a href="reports.php" class="py-2.5 px-3 rounded-xl skeuo-btn-secondary text-slate-600 hover:text-slate-800 font-bold text-xs" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>

            </div>

            <!-- Active Filter Badge Information -->
            <div class="pt-2 flex flex-wrap items-center justify-between text-xs text-slate-500 border-t border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-slate-400">Rentang Aktif:</span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-black text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-lg border border-blue-200">
                        <i class="fa-solid fa-calendar-day text-[10px]"></i> <?= e($periodLabel) ?>
                    </span>
                    <?php if ($filterStatus !== 'all'): ?>
                        <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-lg <?= $filterStatus === 'lunas' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                            <?= strtoupper($filterStatus) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="text-[11px] font-bold text-slate-500">
                    Menampilkan: <strong class="text-blue-700 font-mono"><?= count($bills) ?> Transaksi</strong>
                </div>
            </div>

        </form>
    </div>

    <!-- EXECUTIVE FINANCIAL SUMMARY CARDS (F6 / T-020) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- CARD 1: Total Nilai Tagihan Terbit -->
        <div class="neu-flat rounded-3xl p-5 border border-white/80 shadow-md">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Nilai Tagihan</span>
            <div id="reportTotalBillAmount" class="text-xl font-black text-slate-900 mt-2 font-mono"><?= formatRupiah($totalBillAmount) ?></div>
            <div class="text-[11px] text-slate-500 mt-1 font-semibold"><?= $totalBills ?> Lembar Faktur Terfilter</div>
        </div>

        <!-- CARD 2: Total Pemasukan Kas Diterima (Lunas) -->
        <div class="neu-flat rounded-3xl p-5 border border-white/80 shadow-md">
            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 flex items-center gap-1.5">
                <span class="led-pulse active-green"></span> Pemasukan Kas Diterima
            </span>
            <div id="reportTotalIncome" class="text-xl font-black text-emerald-700 mt-2 font-mono"><?= formatRupiah($totalIncome) ?></div>
            <div class="text-[11px] text-emerald-600 mt-1 font-semibold"><?= $countPaid ?> Pelanggan Lunas Terverifikasi</div>
        </div>

        <!-- CARD 3: Sisa Tertunggak (Piutang) -->
        <div class="neu-flat rounded-3xl p-5 border border-white/80 shadow-md">
            <span class="text-[10px] font-black uppercase tracking-widest text-rose-500 flex items-center gap-1.5">
                <span class="led-pulse active-rose"></span> Sisa Tertunggak (Piutang)
            </span>
            <div id="reportTotalUnpaid" class="text-xl font-black text-rose-600 mt-2 font-mono"><?= formatRupiah($totalUnpaid) ?></div>
            <div class="text-[11px] text-rose-500 mt-1 font-semibold"><?= $countUnpaid ?> Tagihan Belum Lunas</div>
        </div>

        <!-- CARD 4: Tingkat Kolektibilitas (Collection Rate) -->
        <div class="neu-flat rounded-3xl p-5 border border-white/80 shadow-md">
            <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 flex items-center gap-1.5">
                <span class="led-pulse active-green"></span> Collection Rate
            </span>
            <div id="reportCollectionRate" class="text-xl font-black text-indigo-700 mt-2 font-mono"><?= $collectionRate ?>%</div>
            <div class="w-full neu-inset-sm rounded-full h-2 mt-2 overflow-hidden p-0.5">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-full rounded-full" style="width: <?= min(100, max(0, $collectionRate)) ?>%"></div>
            </div>
        </div>

    </div>

    <!-- CASH FLOW & DAILY BREAKDOWN ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Cash Flow by Payment Method (1 Col) -->
        <div id="reportMethodsBreakdown" class="neu-flat rounded-3xl p-5 space-y-4 border border-white/80 shadow-md">
            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
                <i class="fa-solid fa-cash-register text-emerald-600"></i> Saluran Kas Masuk (Metode Bayar)
            </h3>

            <?php if (empty($methodRows)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">Belum ada transaksi pembayaran lunas pada rentang ini.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($methodRows as $m): ?>
                        <?php $pct = $totalIncome > 0 ? round(($m['total'] / $totalIncome) * 100, 1) : 0; ?>
                        <div class="p-3.5 rounded-2xl neu-inset flex items-center justify-between">
                            <div>
                                <div class="font-extrabold text-slate-900 text-xs"><?= e($m['method']) ?></div>
                                <div class="text-[10px] text-slate-400 font-bold"><?= $m['count'] ?> transaksi (<?= $pct ?>%)</div>
                            </div>
                            <div class="text-right font-black text-emerald-700 font-mono text-xs">
                                <?= formatRupiah($m['total']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Daily Breakdown Summary (2 Cols) -->
        <div id="reportDailyRevenueBreakdown" class="lg:col-span-2 neu-flat rounded-3xl p-5 space-y-4 border border-white/80 shadow-md">
            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
                <i class="fa-solid fa-calendar-days text-blue-600"></i> Tren Pemasukan Harian
            </h3>

            <?php if (empty($dailyRows)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">Belum ada pemasukan lunas pada rentang filter ini.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/70 text-slate-400 uppercase font-black text-[10px] border-b">
                                <th class="py-2.5 px-3">Tanggal Pelunasan</th>
                                <th class="py-2.5 px-3 text-center">Transaksi</th>
                                <th class="py-2.5 px-3 text-right">Kas Masuk Harian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($dailyRows as $d): ?>
                                <tr class="hover:bg-white/60">
                                    <td class="py-2.5 px-3 font-bold text-slate-900 font-mono">
                                        <?= formatDate($d['pay_day']) ?>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 font-black font-mono text-[10px]"><?= $d['transactions'] ?> struk</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-black text-emerald-700 font-mono">
                                        <?= formatRupiah($d['daily_income']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- DETAILED TRANSACTIONS TABLE (With Grand Total Footer) -->
    <div class="neu-flat rounded-3xl p-5 space-y-4 border border-white/80 shadow-md">
        
        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
                <i class="fa-solid fa-list-check text-blue-600"></i> Rincian Transaksi & Pelanggan (<?= count($bills) ?> Data)
            </h3>
            <span class="text-xs text-slate-400 font-mono font-bold">Periode: <?= e($periodLabel) ?></span>
        </div>

        <?php if (empty($bills)): ?>
            <div class="p-8 text-center text-slate-400">
                <i class="fa-solid fa-file-invoice text-3xl text-slate-300 mb-2 block"></i>
                <p class="font-bold text-slate-700">Tidak ada data transaksi yang sesuai filter terpilih.</p>
                <p class="text-xs text-slate-400 mt-1">Coba sesuaikan tanggal atau pilih status pembayaran lainnya.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100/70 text-slate-400 uppercase font-black text-[10px] border-b">
                            <th class="py-3 px-3">No</th>
                            <th class="py-3 px-3">No. Invoice</th>
                            <th class="py-3 px-3">ID Pelanggan</th>
                            <th class="py-3 px-3">Nama Pelanggan</th>
                            <th class="py-3 px-3">Alamat / Area</th>
                            <th class="py-3 px-3">Paket WiFi</th>
                            <th class="py-3 px-3 text-right">Tarif Tagihan</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-3">Tgl Bayar</th>
                            <th class="py-3 px-3">Metode Bayar</th>
                            <th class="py-3 px-3">Kolektor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php 
                        $no = 1;
                        $tableSubtotal = 0;
                        ?>
                        <?php foreach ($bills as $p): ?>
                            <?php $tableSubtotal += (float)$p['amount']; ?>
                            <tr class="hover:bg-white/60">
                                <td class="py-2.5 px-3 font-mono text-slate-400"><?= $no++ ?></td>
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-900"><?= e($p['bill_number']) ?></td>
                                <td class="py-2.5 px-3 font-mono font-bold text-blue-600"><?= e($p['customer_code']) ?></td>
                                <td class="py-2.5 px-3 font-extrabold text-slate-900"><?= e($p['customer_name']) ?></td>
                                <td class="py-2.5 px-3 text-slate-500 max-w-[150px] truncate" title="<?= e($p['customer_address']) ?>"><?= e($p['customer_address']) ?></td>
                                <td class="py-2.5 px-3 text-slate-800 font-semibold"><?= e($p['package_name']) ?></td>
                                <td class="py-2.5 px-3 text-right font-black text-slate-900 font-mono"><?= formatRupiah($p['amount']) ?></td>
                                <td class="py-2.5 px-3 text-center"><?= getStatusBadge($p['status']) ?></td>
                                <td class="py-2.5 px-3 text-slate-600 font-mono text-[11px]"><?= $p['payment_date'] ? date('d/m/Y H:i', strtotime($p['payment_date'])) : '-' ?></td>
                                <td class="py-2.5 px-3 font-bold text-slate-800"><?= e($p['payment_method'] ?: '-') ?></td>
                                <td class="py-2.5 px-3 text-slate-600 font-medium"><?= e($p['collector_name'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- GRAND TOTAL ROW -->
                        <tr class="bg-slate-100/80 font-black border-t-2 border-slate-300">
                            <td colspan="6" class="py-3.5 px-3 text-right uppercase tracking-wider text-slate-700">
                                Total Keseluruhan (<?= count($bills) ?> Data):
                            </td>
                            <td id="reportTableTotal" class="py-3.5 px-3 text-right font-mono text-sm text-blue-700">
                                <?= formatRupiah($tableSubtotal) ?>
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- Mode Switcher JS -->
<script>
function toggleFilterMode(mode) {
    const dateRangeInputs = document.getElementById('dateRangeInputs');
    const monthlyInputs = document.getElementById('monthlyInputs');
    if (mode === 'date_range') {
        dateRangeInputs.classList.remove('hidden');
        monthlyInputs.classList.add('hidden');
    } else {
        dateRangeInputs.classList.add('hidden');
        monthlyInputs.classList.remove('hidden');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
