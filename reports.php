<?php
/**
 * Financial & Collection Reports for Rengganis Net
 * Luxury Neo-Skeuomorphic Layout with AdminLTE Sidebar
 * Advanced Excel & Official Formal Print Views
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAdmin();

$pdo = getDB();

$selectedMonth = (int)($_GET['month'] ?? date('n'));
$selectedYear  = (int)($_GET['year'] ?? date('Y'));
$filterStatus  = $_GET['status'] ?? 'all'; // all, lunas, belum_lunas
$exportType    = $_GET['export'] ?? '';

$companyName    = getSetting($pdo, 'company_name', 'Rengganis Net');
$companyTagline = getSetting($pdo, 'company_tagline', 'Solusi Internet Cepat & Hemat');
$companyPhone   = getSetting($pdo, 'company_phone', '0812-3456-7890');
$companyEmail   = getSetting($pdo, 'company_email', 'admin@rengganis.net');
$companyAddress = getSetting($pdo, 'company_address', 'Jl. Raya Rengganis No. 45, Sumber Malang');

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

// Customer counts
$stmtCustCounts = $pdo->query("
    SELECT 
        SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) AS total_aktif,
        SUM(CASE WHEN status = 'cuti' THEN 1 ELSE 0 END) AS total_cuti,
        SUM(CASE WHEN status = 'isolir' THEN 1 ELSE 0 END) AS total_isolir
    FROM customers
");
$custStats = $stmtCustCounts->fetch();

// 2. Query Bills with Filter
$sql = "
    SELECT b.*, c.customer_code, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
           p.name AS package_name, p.speed AS package_speed
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN packages p ON c.package_id = p.id
    WHERE b.period_month = :m AND b.period_year = :y
";
$params = [':m' => $selectedMonth, ':y' => $selectedYear];

if ($filterStatus === 'lunas') {
    $sql .= " AND b.status = 'lunas'";
} elseif ($filterStatus === 'belum_lunas') {
    $sql .= " AND b.status = 'belum_lunas'";
}

$sql .= " ORDER BY b.status ASC, b.payment_date ASC, b.due_date ASC, b.id ASC";
$stmtBills = $pdo->prepare($sql);
$stmtBills->execute($params);
$bills = $stmtBills->fetchAll();

// 3. Payment Methods Breakdown
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

// 4. Daily Income Breakdown
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

// =========================================================================
// HANDLER 1: EXPORT EXCEL PROFESIONAL (.xls FORMAT)
// =========================================================================
if ($exportType === 'excel') {
    $filename = "Laporan-Keuangan-RengganisNet-" . $selectedYear . "-" . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="utf-8">';
    echo '<style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
        .title { font-size: 16pt; font-weight: bold; color: #1e3a8a; }
        .sub-title { font-size: 11pt; color: #475569; }
        .section-header { font-size: 12pt; font-weight: bold; background-color: #1e3a8a; color: #ffffff; padding: 6px; }
        .table-header { font-weight: bold; background-color: #e2e8f0; color: #0f172a; text-align: center; border: 1px solid #94a3b8; }
        .cell { border: 1px solid #cbd5e1; padding: 5px; }
        .cell-center { border: 1px solid #cbd5e1; padding: 5px; text-align: center; }
        .cell-right { border: 1px solid #cbd5e1; padding: 5px; text-align: right; }
        .kpi-label { font-weight: bold; background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px; }
        .kpi-val { font-weight: bold; border: 1px solid #cbd5e1; padding: 6px; text-align: right; }
        .kpi-sub { border: 1px solid #cbd5e1; padding: 6px; color: #64748b; font-size: 10pt; }
        .status-lunas { background-color: #dcfce7; color: #166534; font-weight: bold; text-align: center; border: 1px solid #cbd5e1; }
        .status-unpaid { background-color: #ffe4e6; color: #9f1239; font-weight: bold; text-align: center; border: 1px solid #cbd5e1; }
        .total-row { font-weight: bold; background-color: #f8fafc; border-top: 2px solid #0f172a; border-bottom: 2px solid #0f172a; padding: 6px; }
    </style></head><body>';

    // Header Perusahaan
    echo '<table>';
    echo '<tr><td colspan="10" class="title">' . htmlspecialchars(strtoupper($companyName)) . '</td></tr>';
    echo '<tr><td colspan="10" class="sub-title">' . htmlspecialchars($companyTagline) . ' | Telp/WA: ' . htmlspecialchars($companyPhone) . '</td></tr>';
    echo '<tr><td colspan="10" class="sub-title">Alamat: ' . htmlspecialchars($companyAddress) . '</td></tr>';
    echo '<tr><td colspan="10" style="font-weight: bold; padding-top: 10px; font-size: 13pt;">LAPORAN REKAPITULASI PENAGIHAN & PENDAPATAN BULANAN</td></tr>';
    echo '<tr><td colspan="10" style="color: #475569;">Periode: ' . getMonthName($selectedMonth) . ' ' . $selectedYear . ' | Tanggal Unduh: ' . date('d/m/Y H:i') . ' WIB | Petugas: ' . htmlspecialchars($_SESSION['user_name'] ?? 'Admin') . '</td></tr>';
    echo '<tr><td colspan="10"></td></tr>'; // Spacer
    echo '</table>';

    // TABEL 1: RINGKASAN EKSEKUTIF / KPI
    echo '<table border="1">';
    echo '<tr><td colspan="4" class="section-header">1. RINGKASAN EKSEKUTIF & METRIK KINERJA PENAGIHAN</td></tr>';
    echo '<tr class="table-header"><td>INDIKATOR KINERJA</td><td>KUANTITAS</td><td>TOTAL NOMINAL (RP)</td><td>KETERANGAN / STATUS</td></tr>';
    echo '<tr><td class="kpi-label">Total Tagihan Diterbitkan</td><td class="kpi-val">' . (int)$monthAgg['total_bills'] . ' Invoice</td><td class="kpi-val">' . formatRupiah($monthAgg['total_bill_amount'] ?? 0) . '</td><td class="kpi-sub">Total tagihan terbit periode ini</td></tr>';
    echo '<tr><td class="kpi-label">Total Pemasukan Diterima (Lunas)</td><td class="kpi-val" style="color: #166534;">' . $countPaid . ' Terbayar</td><td class="kpi-val" style="color: #166534;">' . formatRupiah($totalIncome) . '</td><td class="kpi-sub" style="background-color: #f0fdf4;">Dana riil kas masuk</td></tr>';
    echo '<tr><td class="kpi-label">Sisa Tertunggak (Piutang)</td><td class="kpi-val" style="color: #9f1239;">' . $countUnpaid . ' Tertunda</td><td class="kpi-val" style="color: #9f1239;">' . formatRupiah($totalUnpaid) . '</td><td class="kpi-sub" style="background-color: #fff1f2;">Prioritas penagihan lapangan</td></tr>';
    echo '<tr><td class="kpi-label">Tingkat Kolektibilitas (Collection Rate)</td><td class="kpi-val" style="color: #1d4ed8;">' . $collectionRate . '%</td><td class="kpi-val">-</td><td class="kpi-sub">' . ($collectionRate >= 80 ? 'Efisiensi Sangat Baik' : 'Perlu Peningkatan Penagihan') . '</td></tr>';
    echo '<tr><td class="kpi-label">Pelanggan Berstatus Cuti (FR-03)</td><td class="kpi-val">' . (int)$custStats['total_cuti'] . ' User</td><td class="kpi-val">-</td><td class="kpi-sub">Otomatis bebas tagihan tanpa akumulasi denda</td></tr>';
    echo '</table>';

    echo '<br>'; // Spacer

    // TABEL 2: REKAP METODE PEMBAYARAN
    echo '<table border="1">';
    echo '<tr><td colspan="4" class="section-header">2. REKAPITULASI BERDASARKAN METODE PEMBAYARAN</td></tr>';
    echo '<tr class="table-header"><td>METODE PEMBAYARAN</td><td>JUMLAH TRANSAKSI</td><td>TOTAL NOMINAL MASUK (RP)</td><td>KONTRIBUSI (%)</td></tr>';
    foreach ($methodRows as $m) {
        $pct = $totalIncome > 0 ? round(($m['total'] / $totalIncome) * 100, 1) : 0;
        echo '<tr>';
        echo '<td class="cell" style="font-weight: bold;">' . htmlspecialchars($m['method']) . '</td>';
        echo '<td class="cell-center">' . $m['count'] . ' Transaksi</td>';
        echo '<td class="cell-right" style="font-weight: bold;">' . formatRupiah($m['total']) . '</td>';
        echo '<td class="cell-center">' . $pct . '%</td>';
        echo '</tr>';
    }
    echo '<tr class="total-row"><td>TOTAL KAS MASUK</td><td style="text-align: center;">' . $countPaid . ' Transaksi</td><td style="text-align: right;">' . formatRupiah($totalIncome) . '</td><td style="text-align: center;">100%</td></tr>';
    echo '</table>';

    echo '<br>'; // Spacer

    // TABEL 3: RINCIAN LENGKAP INVOICE & PELANGGAN
    echo '<table border="1">';
    echo '<tr><td colspan="12" class="section-header">3. RINCIAN LENGKAP TAGIHAN & TRANSAKSI PELANGGAN (' . count($bills) . ' DATA)</td></tr>';
    echo '<tr class="table-header">';
    echo '<td>NO</td><td>NO INVOICE</td><td>ID PELANGGAN</td><td>NAMA PELANGGAN</td><td>NO TELEPON / WA</td><td>ALAMAT LENGKAP</td><td>PAKET WIFI</td><td>TARIF (RP)</td><td>STATUS</td><td>TANGGAL BAYAR</td><td>METODE BAYAR</td><td>PETUGAS / KASIR</td>';
    echo '</tr>';

    $no = 1;
    $grandTotal = 0;
    foreach ($bills as $b) {
        $grandTotal += (float)$b['amount'];
        $isLunas = $b['status'] === 'lunas';
        echo '<tr>';
        echo '<td class="cell-center">' . $no++ . '</td>';
        echo '<td class="cell" style="font-family: Consolas, monospace; font-weight: bold;">' . htmlspecialchars($b['bill_number']) . '</td>';
        echo '<td class="cell" style="font-family: Consolas, monospace;">' . htmlspecialchars($b['customer_code']) . '</td>';
        echo '<td class="cell" style="font-weight: bold;">' . htmlspecialchars($b['customer_name']) . '</td>';
        echo '<td class="cell" style="mso-number-format:\'@\';">' . htmlspecialchars($b['customer_phone']) . '</td>';
        echo '<td class="cell">' . htmlspecialchars($b['customer_address']) . '</td>';
        echo '<td class="cell">' . htmlspecialchars($b['package_name']) . '</td>';
        echo '<td class="cell-right" style="font-weight: bold;">' . formatRupiah($b['amount']) . '</td>';
        echo '<td class="' . ($isLunas ? 'status-lunas' : 'status-unpaid') . '">' . ($isLunas ? 'LUNAS' : 'BELUM LUNAS') . '</td>';
        echo '<td class="cell-center">' . ($b['payment_date'] ? date('d/m/Y H:i', strtotime($b['payment_date'])) : '-') . '</td>';
        echo '<td class="cell">' . htmlspecialchars($b['payment_method'] ?: '-') . '</td>';
        echo '<td class="cell">' . htmlspecialchars($b['collector_name'] ?: '-') . '</td>';
        echo '</tr>';
    }

    echo '<tr class="total-row">';
    echo '<td colspan="7" style="text-align: right; padding: 8px;">TOTAL KESELURUHAN (' . count($bills) . ' TRANSAKSI):</td>';
    echo '<td style="text-align: right; padding: 8px; font-size: 12pt; color: #1e3a8a;">' . formatRupiah($grandTotal) . '</td>';
    echo '<td colspan="4"></td>';
    echo '</tr>';
    echo '</table>';

    // LEMBAR TANDA TANGAN
    echo '<br><br>';
    echo '<table>';
    echo '<tr>';
    echo '<td colspan="4" style="text-align: center;">Dibuat Oleh,<br><strong>Admin Operasional</strong><br><br><br><br><u>( ' . htmlspecialchars($_SESSION['user_name'] ?? 'Admin') . ' )</u></td>';
    echo '<td colspan="4"></td>';
    echo '<td colspan="4" style="text-align: center;">Sumber Malang, ' . formatDate(date('Y-m-d')) . '<br>Mengetahui & Menyetujui,<br><strong>Pemilik / Owner Rengganis Net</strong><br><br><br><br><u>( Pemilik Usaha )</u></td>';
    echo '</tr>';
    echo '</table>';

    echo '</body></html>';
    exit;
}

// =========================================================================
// HANDLER 2: EXPORT CSV MURNI
// =========================================================================
if ($exportType === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Data-Tagihan-RengganisNet-' . $selectedYear . '-' . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) . '.csv');
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");
    fputcsv($output, ['No Invoice', 'Kode Pelanggan', 'Nama Pelanggan', 'No Telp', 'Alamat', 'Paket WiFi', 'Tarif', 'Status', 'Tanggal Bayar', 'Metode Bayar', 'Kolektor']);

    foreach ($bills as $b) {
        fputcsv($output, [
            $b['bill_number'],
            $b['customer_code'],
            $b['customer_name'],
            $b['customer_phone'],
            $b['customer_address'],
            $b['package_name'],
            $b['amount'],
            strtoupper($b['status']),
            $b['payment_date'] ?: '-',
            $b['payment_method'] ?: '-',
            $b['collector_name'] ?: '-'
        ]);
    }
    fclose($output);
    exit;
}

// =========================================================================
// VIEW WEB: DASHBOARD LAPORAN (LUXURY NEO-SKEUOMORPHIC)
// =========================================================================
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">

    <!-- Top Header & Action Buttons -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-tech">Rekapitulasi Keuangan & Audit Penagihan</h1>
            <p class="text-xs text-slate-500 font-medium">Laporan terstruktur untuk analisis pemilik usaha, monitoring kas, dan cetak dokumen formal</p>
        </div>

        <!-- 3 Primary Export & Print Actions -->
        <div class="flex flex-wrap items-center gap-2.5">
            
            <!-- BUTTON 1: UNDUH EXCEL TERFORMAT -->
            <a href="reports.php?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&status=<?= $filterStatus ?>&export=excel" class="px-4 py-2.5 rounded-2xl skeuo-btn-emerald text-xs font-black shadow-lg flex items-center gap-2">
                <i class="fa-solid fa-file-excel text-sm"></i> Unduh Excel (.xls)
            </a>

            <!-- BUTTON 2: CETAK FORMAL RESMI A4 -->
            <a href="print-report.php?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&status=<?= $filterStatus ?>" target="_blank" class="px-4 py-2.5 rounded-2xl skeuo-btn-dark text-xs font-black shadow-lg flex items-center gap-2">
                <i class="fa-solid fa-print text-sm text-blue-400"></i> Cetak Dokumen Resmi (A4)
            </a>

            <!-- BUTTON 3: UNDUH CSV DATA MURNI -->
            <a href="reports.php?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&status=<?= $filterStatus ?>&export=csv" class="px-3.5 py-2.5 rounded-2xl skeuo-btn-secondary text-xs font-bold flex items-center gap-1.5 text-slate-600">
                <i class="fa-solid fa-file-csv"></i> CSV
            </a>

        </div>
    </div>

    <!-- Filter Control Bar (Periode & Status Filter) -->
    <div class="neu-flat rounded-3xl p-4 sm:p-5">
        <form action="reports.php" method="GET" class="flex flex-wrap items-center justify-between gap-3 text-xs">
            
            <div class="flex flex-wrap items-center gap-3">
                <span class="font-extrabold text-slate-700">Filter Analisis:</span>
                
                <!-- Month Select -->
                <select name="month" class="neu-input px-3.5 py-2 rounded-xl font-bold text-slate-800">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= getMonthName($m) ?></option>
                    <?php endfor; ?>
                </select>

                <!-- Year Select -->
                <select name="year" class="neu-input px-3.5 py-2 rounded-xl font-bold text-slate-800">
                    <?php for ($y = 2025; $y <= 2027; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>

                <!-- Filter Status Tagihan -->
                <select name="status" class="neu-input px-3.5 py-2 rounded-xl font-bold text-slate-800">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Semua Tagihan (Rekap Lengkap)</option>
                    <option value="lunas" <?= $filterStatus === 'lunas' ? 'selected' : '' ?>>Lunas Saja (Realisasi Kas Masuk)</option>
                    <option value="belum_lunas" <?= $filterStatus === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas Saja (Lembar Penagihan)</option>
                </select>

                <button type="submit" class="px-5 py-2 skeuo-btn-primary rounded-xl font-black shadow-md">
                    Terapkan Filter
                </button>
            </div>

            <div class="text-[11px] font-bold text-slate-400">
                Menampilkan data: <strong class="text-blue-600"><?= count($bills) ?> Transaksi</strong>
            </div>

        </form>
    </div>

    <!-- EXECUTIVE SUMMARY CARDS (4-Column Neumorphic Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <div class="neu-flat rounded-3xl p-5">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Nilai Tagihan Terbit</span>
            <div class="text-xl font-black text-slate-900 mt-2 font-mono"><?= formatRupiah($monthAgg['total_bill_amount'] ?? 0) ?></div>
            <div class="text-[11px] text-slate-500 mt-1 font-semibold"><?= (int)$monthAgg['total_bills'] ?> Lembar Invoice Bulan Ini</div>
        </div>

        <div class="neu-flat rounded-3xl p-5">
            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 flex items-center gap-1.5">
                <span class="led-pulse active-green"></span> Pemasukan Kas Diterima
            </span>
            <div class="text-xl font-black text-emerald-700 mt-2 font-mono"><?= formatRupiah($totalIncome) ?></div>
            <div class="text-[11px] text-emerald-600 mt-1 font-semibold"><?= $countPaid ?> Pelanggan Lunas Terverifikasi</div>
        </div>

        <div class="neu-flat rounded-3xl p-5">
            <span class="text-[10px] font-black uppercase tracking-widest text-rose-500 flex items-center gap-1.5">
                <span class="led-pulse active-rose"></span> Sisa Tertunggak (Piutang)
            </span>
            <div class="text-xl font-black text-rose-600 mt-2 font-mono"><?= formatRupiah($totalUnpaid) ?></div>
            <div class="text-[11px] text-rose-500 mt-1 font-semibold"><?= $countUnpaid ?> Tagihan Perlu Ditagih</div>
        </div>

        <div class="neu-flat rounded-3xl p-5">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Tingkat Kolektibilitas</span>
            <div class="text-xl font-black text-indigo-700 mt-2 font-mono"><?= $collectionRate ?>%</div>
            <div class="w-full neu-inset-sm rounded-full h-2 mt-2 overflow-hidden p-0.5">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-full rounded-full" style="width: <?= min(100, max(0, $collectionRate)) ?>%"></div>
            </div>
        </div>

    </div>

    <!-- CASH FLOW & DAILY BREAKDOWN ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Cash Flow by Payment Method (1 Col) -->
        <div class="neu-flat rounded-3xl p-5 space-y-4">
            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
                <i class="fa-solid fa-cash-register text-emerald-600"></i> Saluran Kas Masuk (Metode Bayar)
            </h3>

            <?php if (empty($methodRows)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">Belum ada transaksi pembayaran lunas.</p>
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
        <div class="lg:col-span-2 neu-flat rounded-3xl p-5 space-y-4">
            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
                <i class="fa-solid fa-calendar-days text-blue-600"></i> Tren Pemasukan Harian
            </h3>

            <?php if (empty($dailyRows)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">Belum ada pemasukan lunas pada periode ini.</p>
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
    <div class="neu-flat rounded-3xl p-5 space-y-4">
        
        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
                <i class="fa-solid fa-list-check text-blue-600"></i> Rincian Seluruh Transaksi & Pelanggan (<?= count($bills) ?> Data)
            </h3>
            <span class="text-xs text-slate-400 font-mono font-bold">Periode: <?= getMonthName($selectedMonth) ?> <?= $selectedYear ?></span>
        </div>

        <?php if (empty($bills)): ?>
            <div class="p-8 text-center text-slate-400">
                <p class="font-bold text-slate-700">Tidak ada data transaksi yang sesuai filter terpilih.</p>
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
                            <td class="py-3.5 px-3 text-right font-mono text-sm text-blue-700">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
