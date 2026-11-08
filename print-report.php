<?php
/**
 * Formal A4 Printable Document View for Rengganis Net
 * Tailored for Owner / Admin auditing, archival, and field collections
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
$filterMethod  = $_GET['method'] ?? 'all';
$filterCollector = $_GET['collector'] ?? 'all';

// Normalize date range
if ($filterMode === 'date_range') {
    if (empty($startDate)) $startDate = date('Y-m-01');
    if (empty($endDate))   $endDate   = date('Y-m-d');
    $periodLabel = formatDate($startDate) . ' s/d ' . formatDate($endDate);
    $docCodePeriod = date('Ymd', strtotime($startDate)) . '-' . date('Ymd', strtotime($endDate));
    $backLinkParam = "mode=date_range&start_date=" . urlencode($startDate) . "&end_date=" . urlencode($endDate);
} else {
    $filterMode = 'month';
    $periodLabel = getMonthName($selectedMonth) . ' ' . $selectedYear;
    $docCodePeriod = $selectedYear . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT);
    $backLinkParam = "mode=month&month={$selectedMonth}&year={$selectedYear}";
}

$backLinkParam .= "&status=" . urlencode($filterStatus);
if ($filterMethod !== 'all') $backLinkParam .= "&method=" . urlencode($filterMethod);
if ($filterCollector !== 'all') $backLinkParam .= "&collector=" . urlencode($filterCollector);

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

// 3. Overall Aggregates for Selected Filter
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
$monthAgg = $stmtAgg->fetch();

$totalIncome = (float)($monthAgg['total_income'] ?? 0);
$totalUnpaid = (float)($monthAgg['total_unpaid'] ?? 0);
$countPaid   = (int)($monthAgg['count_paid'] ?? 0);
$countUnpaid = (int)($monthAgg['count_unpaid'] ?? 0);
$totalBills  = (int)($monthAgg['total_bills'] ?? 0);
$collectionRate = $totalBills > 0 ? round(($countPaid / $totalBills) * 100, 1) : 0;

// 4. Query Detailed Bills with Filter
$sql = "
    SELECT b.*, c.customer_code, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
           p.name AS package_name, p.speed AS package_speed
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN packages p ON c.package_id = p.id
    WHERE {$whereClause}
    ORDER BY b.status ASC, b.payment_date ASC, b.due_date ASC, b.id ASC
";
$stmtBills = $pdo->prepare($sql);
$stmtBills->execute($params);
$bills = $stmtBills->fetchAll();

// 5. Payment Methods Breakdown
if ($filterMode === 'date_range') {
    $stmtMethods = $pdo->prepare("
        SELECT 
            IFNULL(payment_method, 'Tunai') AS method,
            COUNT(*) AS count,
            SUM(amount) AS total
        FROM bills
        WHERE status = 'lunas' 
          AND DATE(payment_date) BETWEEN ? AND ?
        GROUP BY payment_method
        ORDER BY total DESC
    ");
    $stmtMethods->execute([$startDate, $endDate]);
} else {
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
}
$methods = $stmtMethods->fetchAll();

$reportTitle = "LAPORAN REKAPITULASI PENDAPATAN & PENAGIHAN";
if ($filterStatus === 'lunas') {
    $reportTitle = "LAPORAN REALISASI PEMBAYARAN & PEMASUKAN LUNAS";
} elseif ($filterStatus === 'belum_lunas') {
    $reportTitle = "DAFTAR REKAPITULASI TAGIHAN MENUNGGAK (LEMBAR PENAGIHAN)";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($reportTitle) ?> - <?= e($periodLabel) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.4;
            padding: 20px 0;
        }

        .no-print-bar {
            max-width: 900px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 14px 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .btn-print {
            background: #0f172a;
            color: #ffffff;
        }
        .btn-print:hover {
            background: #334155;
        }

        .btn-back {
            background: #f8fafc;
            color: #475569;
            border-color: #cbd5e1;
        }
        .btn-back:hover {
            background: #e2e8f0;
        }

        /* Formal Document Sheet */
        .sheet {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px 45px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border-radius: 4px;
        }

        /* Kop Surat Resmi */
        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 15px;
            border-bottom: 2px solid #0f172a;
            margin-bottom: 2px;
        }
        .kop-border-sub {
            border-bottom: 1px solid #0f172a;
            margin-bottom: 20px;
        }

        .kop-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .kop-logo {
            width: 55px;
            height: 55px;
            background: #1e3a8a;
            border-radius: 12px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }
        .kop-info h1 {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        .kop-info p {
            font-size: 10px;
            color: #475569;
            margin-top: 1px;
        }

        .doc-title-block {
            text-align: center;
            margin-bottom: 20px;
        }
        .doc-title-block h2 {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #0f172a;
        }
        .doc-title-block .doc-meta {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }

        /* KPI Summary Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .kpi-card {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .kpi-card .kpi-label {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
        }
        .kpi-card .kpi-value {
            font-size: 14px;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            color: #0f172a;
            margin-top: 3px;
        }
        .kpi-card .kpi-sub {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Formal Data Tables */
        table.formal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 20px;
        }
        table.formal-table th, table.formal-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
        }
        table.formal-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
        }
        table.formal-table tr:nth-child(even) td {
            background-color: #fafbfc;
        }

        .badge-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-lunas {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .badge-unpaid {
            background: #ffe4e6;
            color: #9f1239;
            border: 1px solid #fecdd3;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .font-bold { font-weight: 700; }

        /* Signature block */
        .signature-block {
            display: flex;
            justify-content: space-between;
            margin-top: 35px;
            page-break-inside: avoid;
        }
        .sig-box {
            width: 220px;
            text-align: center;
        }
        .sig-space {
            height: 60px;
        }
        .sig-line {
            border-bottom: 1px solid #0f172a;
            font-weight: 700;
            font-size: 11px;
            padding-bottom: 2px;
        }

        /* Print Specific CSS */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                color: #000000 !important;
            }
            .no-print-bar {
                display: none !important;
            }
            .sheet {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            @page {
                size: A4 portrait;
                margin: 15mm 15mm 15mm 15mm;
            }
            table.formal-table th {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .kpi-card {
                border: 1px solid #94a3b8 !important;
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .badge-lunas, .badge-unpaid {
                border: 1px solid #64748b !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- Non-printable Toolbar -->
    <div class="no-print-bar">
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="reports.php?<?= $backLinkParam ?>" class="btn-action btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Panel Laporan
            </a>
            <span style="font-size: 12px; color: #64748b;">|</span>
            <span style="font-size: 12px; font-weight: 600; color: #334155;">Mode Pratinjau Cetak Formal (A4)</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn-action btn-print">
                <i class="fa-solid fa-print"></i> Cetak / Simpan PDF (Ctrl+P)
            </button>
        </div>
    </div>

    <!-- Printable Official Sheet -->
    <div class="sheet">

        <!-- KOP SURAT FORMAL -->
        <div class="kop-surat">
            <div class="kop-brand">
                <div class="kop-logo">
                    <i class="fa-solid fa-wifi"></i>
                </div>
                <div class="kop-info">
                    <h1><?= e($companyName) ?></h1>
                    <p><strong><?= e($companyTagline) ?></strong> &bull; Izin Penyelenggara Jaringan Internet</p>
                    <p><?= e($companyAddress) ?> &bull; Telp/WA: <?= e($companyPhone) ?> &bull; Email: <?= e($companyEmail) ?></p>
                </div>
            </div>
            <div style="text-align: right; font-size: 10px; color: #475569;">
                <div><strong>KODE DOKUMEN:</strong></div>
                <div class="font-mono" style="font-weight: 700; color: #0f172a;">REP/RNG/<?= $docCodePeriod ?>/<?= strtoupper(substr($filterStatus, 0, 3)) ?></div>
            </div>
        </div>
        <div class="kop-border-sub"></div>

        <!-- DOKUMEN TITLE -->
        <div class="doc-title-block">
            <h2><?= e($reportTitle) ?></h2>
            <div class="doc-meta">
                Periode: <strong><?= e($periodLabel) ?></strong> &bull;
                Filter Status: <strong><?= strtoupper(str_replace('_', ' ', $filterStatus)) ?></strong> &bull;
                Waktu Cetak: <strong><?= date('d/m/Y H:i') ?> WIB</strong>
            </div>
        </div>

        <!-- KPI SUMMARY BOXES -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Total Tagihan Periode</div>
                <div class="kpi-value"><?= formatRupiah($monthAgg['total_bill_amount'] ?? 0) ?></div>
                <div class="kpi-sub"><?= (int)$monthAgg['total_bills'] ?> Lembar Invoice</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Pemasukan Diterima (Lunas)</div>
                <div class="kpi-value" style="color: #15803d;"><?= formatRupiah($totalIncome) ?></div>
                <div class="kpi-sub"><?= $countPaid ?> Pelanggan Terbayar</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Sisa Tertunggak (Piutang)</div>
                <div class="kpi-value" style="color: #b91c1c;"><?= formatRupiah($totalUnpaid) ?></div>
                <div class="kpi-sub"><?= $countUnpaid ?> Tagihan Belum Dibayar</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Efisiensi Kolektibilitas</div>
                <div class="kpi-value" style="color: #1d4ed8;"><?= $collectionRate ?>%</div>
                <div class="kpi-sub">Target Penagihan 100%</div>
            </div>
        </div>

        <!-- CASH FLOW BREAKDOWN (METODE BAYAR) -->
        <?php if (!empty($methods) && $filterStatus !== 'belum_lunas'): ?>
            <div style="margin-bottom: 15px;">
                <div style="font-weight: 800; font-size: 10px; text-transform: uppercase; color: #475569; margin-bottom: 6px;">
                    <i class="fa-solid fa-cash-register"></i> Rekapitulasi Realisasi Berdasarkan Metode Pembayaran:
                </div>
                <table class="formal-table" style="margin-bottom: 15px;">
                    <thead>
                        <tr>
                            <th>Metode Pembayaran</th>
                            <th class="text-center">Jumlah Transaksi</th>
                            <th class="text-right">Total Pemasukan Masuk</th>
                            <th class="text-right">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($methods as $m): ?>
                            <?php $pct = $totalIncome > 0 ? round(($m['total'] / $totalIncome) * 100, 1) : 0; ?>
                            <tr>
                                <td class="font-bold"><?= e($m['method']) ?></td>
                                <td class="text-center font-mono"><?= $m['count'] ?> transaksi</td>
                                <td class="text-right font-mono font-bold"><?= formatRupiah($m['total']) ?></td>
                                <td class="text-right font-mono"><?= $pct ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- RINCIAN TRANSAKSI TABLE -->
        <div>
            <div style="font-weight: 800; font-size: 10px; text-transform: uppercase; color: #475569; margin-bottom: 6px;">
                <i class="fa-solid fa-list-check"></i> Rincian Seluruh Transaksi & Pelanggan (<?= count($bills) ?> Data):
            </div>
            
            <table class="formal-table">
                <thead>
                    <tr>
                        <th style="width: 28px;" class="text-center">No</th>
                        <th>No. Invoice</th>
                        <th>ID Pel.</th>
                        <th>Nama Pelanggan</th>
                        <th>Alamat / Area</th>
                        <th>Paket</th>
                        <th class="text-right">Tarif Tagihan</th>
                        <th class="text-center">Status</th>
                        <th>Tgl Bayar</th>
                        <th>Metode</th>
                        <th>Kolektor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bills)): ?>
                        <tr>
                            <td colspan="11" class="text-center" style="padding: 20px; color: #64748b;">
                                Tidak ada data tagihan yang sesuai dengan kriteria filter terpilih.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1; 
                        $subtotalAmount = 0;
                        ?>
                        <?php foreach ($bills as $b): ?>
                            <?php $subtotalAmount += (float)$b['amount']; ?>
                            <tr>
                                <td class="text-center font-mono"><?= $no++ ?></td>
                                <td class="font-mono font-bold"><?= e($b['bill_number']) ?></td>
                                <td class="font-mono"><?= e($b['customer_code']) ?></td>
                                <td class="font-bold"><?= e($b['customer_name']) ?></td>
                                <td style="max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= e($b['customer_address']) ?>
                                </td>
                                <td><?= e($b['package_name']) ?></td>
                                <td class="text-right font-mono font-bold"><?= formatRupiah($b['amount']) ?></td>
                                <td class="text-center">
                                    <?php if ($b['status'] === 'lunas'): ?>
                                        <span class="badge-status badge-lunas">Lunas</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-unpaid">Belum Lunas</span>
                                    <?php endif; ?>
                                </td>
                                <td class="font-mono" style="font-size: 9px;"><?= $b['payment_date'] ? date('d/m/y H:i', strtotime($b['payment_date'])) : '-' ?></td>
                                <td><?= e($b['payment_method'] ?: '-') ?></td>
                                <td><?= e($b['collector_name'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- GRAND TOTAL ROW -->
                        <tr style="background-color: #f1f5f9; font-weight: 800;">
                            <td colspan="6" class="text-right" style="padding: 8px; font-size: 10px;">
                                TOTAL KESELURUHAN (<?= count($bills) ?> Transaksi):
                            </td>
                            <td class="text-right font-mono" style="padding: 8px; font-size: 11px; color: #0f172a;">
                                <?= formatRupiah($subtotalAmount) ?>
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- LEMBAR PENGESAHAN / TANDA TANGAN FORMAL -->
        <div class="signature-block">
            <div class="sig-box">
                <div>Dibuat & Diverifikasi Oleh,</div>
                <div style="font-weight: 700; color: #64748b; font-size: 10px;">Admin / Kasir Penagihan</div>
                <div class="sig-space"></div>
                <div class="sig-line">( <?= e($_SESSION['user_name'] ?? 'Admin Operasional') ?> )</div>
                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Petugas Loket / Kolektor</div>
            </div>

            <div class="sig-box">
                <div>Sumber Malang, <?= formatDate(date('Y-m-d')) ?></div>
                <div style="font-weight: 700; color: #0f172a; font-size: 10px;">Mengetahui & Menyetujui,</div>
                <div class="sig-space"></div>
                <div class="sig-line">( Pemilik Rengganis Net )</div>
                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Owner / Penanggung Jawab</div>
            </div>
        </div>

    </div>

</body>
</html>
