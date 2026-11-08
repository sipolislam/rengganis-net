<?php
/**
 * Billing Management & Payments for Rengganis Net
 * Luxury Neo-Skeuomorphic Layout with AdminLTE Sidebar
 */

$pageTitle = 'Tagihan & Billing';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// Current filters (T-011)
$filterMonth = (int)($_GET['month'] ?? date('n'));
$filterYear = (int)($_GET['year'] ?? date('Y'));
$filterStatus = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$isFiltered = ($filterStatus !== 'all' || !empty($search));

// Helper for building bills filter URLs preserving period and search
$buildBillsUrl = function($overrides = []) use ($filterMonth, $filterYear, $filterStatus, $search) {
    $params = [
        'month'  => $filterMonth,
        'year'   => $filterYear,
        'status' => $filterStatus,
        'search' => $search
    ];
    $merged = array_merge($params, $overrides);
    if ($merged['status'] === 'all') unset($merged['status']);
    if (empty($merged['search'])) unset($merged['search']);
    return 'bills.php?' . http_build_query($merged);
};

// Base Query
$sql = "
    SELECT b.*, c.customer_code, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
           p.name AS package_name, p.speed AS package_speed
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN packages p ON c.package_id = p.id
    WHERE b.period_month = :m AND b.period_year = :y
";
$params = [
    ':m' => $filterMonth,
    ':y' => $filterYear
];

if ($filterStatus !== 'all' && in_array($filterStatus, ['lunas', 'belum_lunas'])) {
    $sql .= " AND b.status = :status";
    $params[':status'] = $filterStatus;
}

if (!empty($search)) {
    // Unique named params for native PDO statement safety
    $sql .= " AND (b.bill_number LIKE :q1 OR c.name LIKE :q2 OR c.customer_code LIKE :q3 OR c.phone LIKE :q4)";
    $term = "%{$search}%";
    $params[':q1'] = $term;
    $params[':q2'] = $term;
    $params[':q3'] = $term;
    $params[':q4'] = $term;
}

$sql .= " ORDER BY b.status ASC, b.due_date ASC, b.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bills = $stmt->fetchAll();

// Period Summary
$stmtSummary = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_count,
        SUM(amount) AS total_amount,
        SUM(CASE WHEN status = 'lunas' THEN amount ELSE 0 END) AS paid_amount,
        SUM(CASE WHEN status = 'lunas' THEN 1 ELSE 0 END) AS paid_count,
        SUM(CASE WHEN status = 'belum_lunas' THEN amount ELSE 0 END) AS unpaid_amount,
        SUM(CASE WHEN status = 'belum_lunas' THEN 1 ELSE 0 END) AS unpaid_count
    FROM bills
    WHERE period_month = ? AND period_year = ?
");
$stmtSummary->execute([$filterMonth, $filterYear]);
$summary = $stmtSummary->fetch();

$totalAmount = (float)($summary['total_amount'] ?? 0);
$paidAmount = (float)($summary['paid_amount'] ?? 0);
$unpaidAmount = (float)($summary['unpaid_amount'] ?? 0);

// Count leave customers for FR-03/R1 exemption visibility
$stmtCutiCount = $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'cuti'");
$cutiCount = (int)$stmtCutiCount->fetchColumn();

// Idempotency Tracking: Compare active customers with already generated bills
$stmtActiveCount = $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'aktif'");
$activeCustomerCount = (int)$stmtActiveCount->fetchColumn();

$stmtBilledActive = $pdo->prepare("
    SELECT COUNT(DISTINCT b.customer_id) FROM bills b 
    JOIN customers c ON b.customer_id = c.id 
    WHERE b.period_month = ? AND b.period_year = ? AND c.status = 'aktif'
");
$stmtBilledActive->execute([$filterMonth, $filterYear]);
$billedActiveCount = (int)$stmtBilledActive->fetchColumn();
$isFullyGenerated = ($activeCustomerCount > 0 && $billedActiveCount >= $activeCustomerCount);
?>

<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-tech">Tagihan & Billing WiFi</h1>
            <p class="text-xs text-slate-500 font-medium">Automasi penerbitan invoice bulanan, verifikasi pelunasan, dan struk thermal</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Quick Export Excel & CSV (F6 / T-021) -->
            <a href="reports.php?mode=month&month=<?= $filterMonth ?>&year=<?= $filterYear ?>&status=<?= urlencode($filterStatus) ?>&export=excel" class="px-3.5 py-2.5 rounded-2xl skeuo-btn-secondary text-xs font-bold text-slate-700 flex items-center gap-1.5 shadow-sm hover:text-emerald-700" title="Ekspor Data Tagihan Periode Ini ke Excel">
                <i class="fa-solid fa-file-excel text-emerald-600"></i> Excel
            </a>

            <a href="reports.php?mode=month&month=<?= $filterMonth ?>&year=<?= $filterYear ?>&status=<?= urlencode($filterStatus) ?>&export=csv" class="px-3 py-2.5 rounded-2xl skeuo-btn-secondary text-xs font-bold text-slate-600 flex items-center gap-1.5 shadow-sm hover:text-blue-700" title="Ekspor Data Tagihan Periode Ini ke CSV">
                <i class="fa-solid fa-file-csv"></i> CSV
            </a>

            <?php if (isAdmin()): ?>
            <button onclick="triggerGenerateBills()" class="px-5 py-2.5 rounded-2xl <?= $isFullyGenerated ? 'skeuo-btn-secondary text-slate-700' : 'skeuo-btn-emerald' ?> text-xs font-black shadow-lg flex items-center justify-center gap-2 transition-all">
                <i class="fa-solid <?= $isFullyGenerated ? 'fa-circle-check text-emerald-500' : 'fa-bolt text-amber-300' ?>"></i> 
                <?= $isFullyGenerated ? 'Tagihan ' . getMonthName($filterMonth) . ' ' . $filterYear . ' Terbit' : 'Generate Tagihan ' . getMonthName($filterMonth) . ' ' . $filterYear ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3-Column Period Summary Cards (Neumorphic Extrusions) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        
        <div class="neu-flat rounded-3xl p-5 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Tagihan Periode Ini</span>
                <div class="text-xl font-black text-slate-900 mt-1 font-mono"><?= formatRupiah($totalAmount) ?></div>
                <div class="text-[11px] text-slate-500 mt-1 font-semibold"><?= (int)$summary['total_count'] ?> Lembar Invoice</div>
            </div>
            <div class="w-12 h-12 rounded-2xl neu-inset flex items-center justify-center text-blue-600 text-lg shadow-inner">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
        </div>

        <div class="neu-flat rounded-3xl p-5 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 flex items-center gap-1.5">
                    <span class="led-pulse active-green"></span> Terbayar Lunas
                </span>
                <div class="text-xl font-black text-emerald-700 mt-1 font-mono"><?= formatRupiah($paidAmount) ?></div>
                <div class="text-[11px] text-emerald-600 mt-1 font-semibold"><?= (int)$summary['paid_count'] ?> Transaksi Selesai</div>
            </div>
            <div class="w-12 h-12 rounded-2xl neu-inset flex items-center justify-center text-emerald-600 text-lg shadow-inner">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="neu-flat rounded-3xl p-5 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-rose-500 flex items-center gap-1.5">
                    <span class="led-pulse active-rose"></span> Tertunggak
                </span>
                <div class="text-xl font-black text-rose-600 mt-1 font-mono"><?= formatRupiah($unpaidAmount) ?></div>
                <div class="text-[11px] text-rose-500 mt-1 font-semibold"><?= (int)$summary['unpaid_count'] ?> Invoice Tertunda</div>
            </div>
            <div class="w-12 h-12 rounded-2xl neu-inset flex items-center justify-center text-rose-600 text-lg shadow-inner">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>

    </div>

    <!-- Cuti Exemption & Idempotency Notice Card (FR-03 / R1) -->
    <div class="neu-flat rounded-3xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border border-amber-200/50 bg-gradient-to-r from-amber-50/60 via-amber-50/30 to-slate-50/50">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl neu-inset flex items-center justify-center text-amber-600 shadow-inner shrink-0">
                <i class="fa-solid fa-shield-halved text-lg"></i>
            </div>
            <div>
                <div class="text-xs font-black text-slate-800 tracking-tight flex flex-wrap items-center gap-2">
                    <span>Proteksi Tagihan Fiktif & Idempotensi (FR-03 / R1)</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <i class="fa-solid fa-check text-[8px]"></i> Bebas Tagihan Cuti
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase bg-blue-100 text-blue-800 border border-blue-200">
                        <i class="fa-solid fa-clone text-[8px]"></i> Idempoten (0 Dobel)
                    </span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1 font-medium leading-relaxed">
                    Sistem mendeteksi <strong class="text-amber-700 font-bold"><?= $cutiCount ?> pelanggan Cuti</strong> otomatis dilewati tanpa tagihan, dan <strong class="text-blue-700 font-bold"><?= $billedActiveCount ?> dari <?= $activeCustomerCount ?> pelanggan aktif</strong> telah memiliki invoice resmi periode ini tanpa risiko tagihan ganda.
                </div>
            </div>
        </div>
        <a href="customers.php?status=cuti" class="px-4 py-2 rounded-xl skeuo-btn-secondary text-xs font-bold text-slate-700 whitespace-nowrap shadow-sm hover:text-blue-700 transition-colors flex items-center gap-1.5 self-end sm:self-auto">
            <span>Data Cuti</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </a>
    </div>

    <!-- Filter Controls (Neumorphic Inset Bar - T-011) -->
    <div class="neu-flat rounded-3xl p-4 sm:p-5 flex flex-col gap-4">
        
        <!-- Quick Status Pills -->
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex flex-wrap items-center gap-2 font-bold">
                <a href="<?= e($buildBillsUrl(['status' => 'all'])) ?>" class="px-3.5 py-1.5 rounded-xl transition-all <?= $filterStatus === 'all' ? 'skeuo-btn-dark' : 'skeuo-btn-secondary text-slate-600' ?>">
                    Semua (<?= (int)$summary['total_count'] ?>)
                </a>
                <a href="<?= e($buildBillsUrl(['status' => 'belum_lunas'])) ?>" class="px-3.5 py-1.5 rounded-xl transition-all flex items-center gap-1.5 <?= $filterStatus === 'belum_lunas' ? 'bg-rose-600 text-white shadow-md' : 'skeuo-btn-secondary text-rose-700' ?>">
                    <span class="led-pulse active-rose"></span> Belum Lunas (<?= (int)$summary['unpaid_count'] ?>)
                </a>
                <a href="<?= e($buildBillsUrl(['status' => 'lunas'])) ?>" class="px-3.5 py-1.5 rounded-xl transition-all flex items-center gap-1.5 <?= $filterStatus === 'lunas' ? 'skeuo-btn-emerald' : 'skeuo-btn-secondary text-emerald-700' ?>">
                    <span class="led-pulse active-green"></span> Lunas (<?= (int)$summary['paid_count'] ?>)
                </a>
            </div>

            <!-- Active Results Counter Badge -->
            <div class="text-xs font-semibold text-slate-500">
                Menampilkan <strong class="text-slate-900"><?= count($bills) ?></strong> dari <?= (int)$summary['total_count'] ?> invoice
            </div>
        </div>

        <!-- Filter Form with Period & Search -->
        <form action="bills.php" method="GET" class="flex flex-wrap items-center justify-between gap-3 text-xs pt-3 border-t border-slate-200/60">
            
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Month Select -->
                <select name="month" class="neu-input px-3.5 py-2 rounded-xl font-bold text-slate-800">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $filterMonth ? 'selected' : '' ?>><?= getMonthName($m) ?></option>
                    <?php endfor; ?>
                </select>

                <!-- Year Select -->
                <select name="year" class="neu-input px-3.5 py-2 rounded-xl font-bold text-slate-800">
                    <?php for ($y = 2025; $y <= 2027; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $filterYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>

                <!-- Status Select -->
                <select name="status" class="neu-input px-3.5 py-2 rounded-xl font-bold text-slate-800">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="belum_lunas" <?= $filterStatus === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas Saja</option>
                    <option value="lunas" <?= $filterStatus === 'lunas' ? 'selected' : '' ?>>Lunas Saja</option>
                </select>

                <button type="submit" class="px-5 py-2 skeuo-btn-dark rounded-xl font-black">
                    Filter Data
                </button>

                <?php if ($isFiltered): ?>
                    <a href="bills.php?month=<?= $filterMonth ?>&year=<?= $filterYear ?>" class="px-3.5 py-2 rounded-xl text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 font-bold transition-all flex items-center gap-1.5 shadow-sm" title="Reset filter status dan pencarian">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Reset Filter
                    </a>
                <?php endif; ?>
            </div>

            <!-- Search box -->
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari invoice, nama..." class="neu-input w-full pl-8 pr-3 py-2 rounded-xl font-semibold">
            </div>

        </form>
    </div>

    <!-- Bills Table (Tactile Neumorphic Surface) -->
    <div class="neu-flat rounded-3xl overflow-hidden border border-white/80 shadow-md">
        
        <?php if (empty($bills)): ?>
            <div class="p-12 text-center text-slate-400">
                <div class="w-14 h-14 rounded-2xl neu-inset mx-auto flex items-center justify-center text-rose-500 mb-3 shadow-inner">
                    <i class="fa-solid fa-file-invoice text-2xl"></i>
                </div>
                <?php if ($isFiltered): ?>
                    <p class="text-sm font-bold text-slate-800">Tidak ada tagihan yang cocok dengan filter pencarian.</p>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                        Kata kunci <?= !empty($search) ? '"' . e($search) . '"' : '' ?> pada status <strong class="text-slate-600"><?= e($filterStatus) ?></strong> tidak menemukan tagihan untuk periode <?= getMonthName($filterMonth) ?> <?= $filterYear ?>.
                    </p>
                    <div class="mt-4">
                        <a href="bills.php?month=<?= $filterMonth ?>&year=<?= $filterYear ?>" class="px-4 py-2 rounded-xl skeuo-btn-secondary text-xs font-bold text-slate-700 hover:text-blue-600 transition-colors inline-flex items-center gap-2 shadow-sm">
                            <i class="fa-solid fa-arrow-rotate-left"></i> Bersihkan Filter & Tampilkan Semua Tagihan Periode Ini
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-sm font-bold text-slate-800">Tidak ada tagihan untuk periode terpilih.</p>
                    <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Generate Tagihan" di atas untuk membuat invoice baru bagi pelanggan aktif.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- DESKTOP / TABLET VIEW: 7-COLUMN TABLE (hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100/70 text-slate-400 border-b border-slate-200 uppercase font-black text-[10px] tracking-wider">
                            <th class="py-3.5 px-5">No. Invoice & Tgl</th>
                            <th class="py-3.5 px-4">Pelanggan</th>
                            <th class="py-3.5 px-4">Paket Bandwidth</th>
                            <th class="py-3.5 px-4">Jatuh Tempo</th>
                            <th class="py-3.5 px-4 text-right">Nominal</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($bills as $bill): ?>
                            <?php
                            $isPaid = ($bill['status'] === 'lunas');
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
                                    <div class="font-mono font-bold text-slate-900"><?= e($bill['bill_number']) ?></div>
                                    <div class="text-[10px] text-slate-400 font-mono">Dibuat: <?= formatDate($bill['created_at']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-black text-slate-900"><?= e($bill['customer_name']) ?></div>
                                    <div class="font-mono text-[11px] text-blue-600 font-bold"><?= e($bill['customer_code']) ?> &bull; <?= e($bill['customer_phone']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800"><?= e($bill['package_name']) ?></div>
                                    <div class="text-[10px] text-blue-600 font-black"><?= e($bill['package_speed']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="<?= $isPaid ? 'text-slate-600' : 'text-rose-600 font-black' ?> font-mono">
                                        <?= formatDate($bill['due_date']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-black text-slate-900 font-mono">
                                    <?= formatRupiah($bill['amount']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center status-col">
                                    <?= getStatusBadge($bill['status']) ?>
                                    <?php if ($isPaid && $bill['payment_method']): ?>
                                        <div class="text-[10px] text-slate-400 mt-0.5 font-semibold"><?= e($bill['payment_method']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-5 text-right action-col">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <?php if (!$isPaid): ?>
                                            <!-- WhatsApp Billing Notification Button (T-012 / F4 / R2) -->
                                            <?php $waBillUrl = buildBillingWhatsAppUrl($bill, $pdo); ?>
                                            <a href="<?= e($waBillUrl) ?>" target="_blank" 
                                               class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-md transition-all hover:scale-105"
                                               title="Kirim Notifikasi Tagihan WA (Opsi Online QR & Offline Tunai Pas)">
                                                <i class="fa-brands fa-whatsapp text-sm"></i>
                                                <span class="hidden md:inline">Kirim WA</span>
                                            </a>

                                            <?php if (hasRole(['admin', 'kolektor'])): ?>
                                            <button onclick='openPaymentModal(<?= $billJson ?>)' class="px-3.5 py-2 rounded-xl skeuo-btn-primary text-xs font-bold shadow-md flex items-center gap-1.5">
                                                <i class="fa-solid fa-money-bill-wave"></i> Bayar
                                            </button>
                                            <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-400 bg-slate-100" title="Hanya Admin & Kolektor">
                                                <i class="fa-solid fa-lock text-[9px]"></i> Kasir Only
                                            </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- WhatsApp Payment Receipt Button (T-013 / F4) -->
                                            <?php $waReceiptUrl = buildPaymentReceiptWhatsAppUrl($bill, $pdo); ?>
                                            <a href="<?= e($waReceiptUrl) ?>" target="_blank"
                                               class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-md transition-all hover:scale-105"
                                               title="Kirim Kuitansi Pelunasan via WhatsApp">
                                                <i class="fa-brands fa-whatsapp text-sm"></i>
                                                <span class="hidden md:inline">Kuitansi WA</span>
                                            </a>

                                            <button onclick='loadAndShowReceipt(<?= $bill['id'] ?>)' class="px-3.5 py-2 rounded-xl skeuo-btn-secondary text-xs font-bold flex items-center gap-1.5 text-blue-700 shadow-sm">
                                                <i class="fa-solid fa-receipt text-blue-600"></i> Struk
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- MOBILE CARD VIEW: FIELD-OPTIMIZED TOUCH CARDS (visible on mobile < md) -->
            <div class="p-3 sm:p-4 space-y-3.5 block md:hidden">
                <?php foreach ($bills as $bill): ?>
                    <?php
                    $isPaid = ($bill['status'] === 'lunas');
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
                    $waMobileNumber = formatWhatsAppNumber($bill['customer_phone']);
                    $dispMobilePhone = formatDisplayPhone($bill['customer_phone']);
                    ?>
                    <div id="mobile-bill-card-<?= $bill['id'] ?>" class="p-4 rounded-2xl neu-inset bg-white/75 border border-white shadow-sm space-y-3 mobile-card-shadow">
                        <!-- Top: Invoice & Status Pill -->
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">No. Invoice</span>
                                <span class="font-mono font-bold text-xs text-slate-900 tracking-tight"><?= e($bill['bill_number']) ?></span>
                            </div>
                            <div class="status-col-mobile">
                                <?= getStatusBadge($bill['status']) ?>
                            </div>
                        </div>

                        <!-- Customer Info -->
                        <div class="space-y-1">
                            <div class="font-black text-sm text-slate-900 tracking-tight"><?= e($bill['customer_name']) ?></div>
                            <div class="flex items-center justify-between text-xs text-slate-600 font-medium">
                                <span class="font-mono text-blue-700 font-bold"><?= e($bill['customer_code']) ?></span>
                                <a href="https://wa.me/<?= $waMobileNumber ?>" target="_blank" class="text-emerald-700 hover:text-emerald-800 font-bold inline-flex items-center gap-1">
                                    <i class="fa-brands fa-whatsapp text-sm"></i> <?= e($dispMobilePhone) ?>
                                </a>
                            </div>
                            <?php if (!empty($bill['customer_address'])): ?>
                                <div class="text-[11px] text-slate-500 truncate" title="<?= e($bill['customer_address']) ?>">
                                    <i class="fa-solid fa-location-dot text-[10px] text-slate-400 mr-1"></i><?= e($bill['customer_address']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Package & Nominal -->
                        <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Paket Bandwidth</span>
                                <div class="text-xs font-bold text-slate-800"><?= e($bill['package_name']) ?> <span class="text-blue-600 font-black">(<?= e($bill['package_speed']) ?>)</span></div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Total Tagihan</span>
                                <div class="text-base font-black text-slate-900 font-mono tracking-tight"><?= formatRupiah($bill['amount']) ?></div>
                            </div>
                        </div>

                        <!-- Due Date / Payment Method Status -->
                        <div class="text-[11px] flex items-center justify-between text-slate-500 font-medium">
                            <span>Jatuh Tempo: <strong class="<?= $isPaid ? 'text-slate-700' : 'text-rose-600 font-bold' ?>"><?= formatDate($bill['due_date']) ?></strong></span>
                            <?php if ($isPaid && $bill['payment_method']): ?>
                                <span class="text-emerald-700 font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-check-double text-[10px]"></i> <?= e($bill['payment_method']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Large Touch Action Buttons for Smartphone Field Operation -->
                        <div class="pt-1 flex items-center gap-2 action-col-mobile">
                            <?php if (!$isPaid): ?>
                                <?php $waBillUrl = buildBillingWhatsAppUrl($bill, $pdo); ?>
                                <a href="<?= e($waBillUrl) ?>" target="_blank" 
                                   class="w-1/2 py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-md active:scale-95 transition-transform touch-target-mobile"
                                   title="Kirim Notifikasi Tagihan WA">
                                    <i class="fa-brands fa-whatsapp text-sm"></i> <span>Kirim WA</span>
                                </a>

                                <?php if (hasRole(['admin', 'kolektor'])): ?>
                                <button onclick='openPaymentModal(<?= $billJson ?>)' class="w-1/2 py-2.5 px-3 rounded-xl skeuo-btn-primary text-xs font-black shadow-md flex items-center justify-center gap-1.5 active:scale-95 transition-transform touch-target-mobile">
                                    <i class="fa-solid fa-money-bill-wave"></i> <span>Bayar</span>
                                </button>
                                <?php else: ?>
                                <span class="w-1/2 py-2.5 px-3 rounded-xl bg-slate-100 text-slate-400 font-bold text-xs flex items-center justify-center gap-1.5 touch-target-mobile">
                                    <i class="fa-solid fa-lock text-[10px]"></i> <span>Kasir Only</span>
                                </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php $waReceiptUrl = buildPaymentReceiptWhatsAppUrl($bill, $pdo); ?>
                                <a href="<?= e($waReceiptUrl) ?>" target="_blank"
                                   class="w-1/2 py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-md active:scale-95 transition-transform touch-target-mobile"
                                   title="Kirim Kuitansi Pelunasan via WhatsApp">
                                    <i class="fa-brands fa-whatsapp text-sm"></i> <span>Kuitansi WA</span>
                                </a>

                                <button onclick='loadAndShowReceipt(<?= $bill['id'] ?>)' class="w-1/2 py-2.5 px-3 rounded-xl skeuo-btn-secondary text-xs font-black flex items-center justify-center gap-1.5 text-blue-700 shadow-sm active:scale-95 transition-transform touch-target-mobile">
                                    <i class="fa-solid fa-receipt text-blue-600"></i> <span>Struk</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</div>

<script>
async function triggerGenerateBills() {
    const isGenerated = <?= $isFullyGenerated ? 'true' : 'false' ?>;
    const promptMsg = isGenerated 
        ? 'Seluruh tagihan untuk pelanggan aktif periode <?= getMonthName($filterMonth) ?> <?= $filterYear ?> sudah terbit.\n\nSistem mengaktifkan proteksi idempotensi (0 tagihan ganda akan dibuat). Tetap jalankan pengecekan ulang?'
        : 'Apakah Anda yakin ingin men-generate tagihan untuk semua pelanggan aktif pada bulan <?= getMonthName($filterMonth) ?> <?= $filterYear ?>?\n\nCatatan:\n1. Pelanggan CUTI otomatis DILEWATI tanpa tagihan fiktif (FR-03).\n2. Tagihan yang sudah ada tidak akan diduplikasi (Idempotensi R1).';

    if (!confirm(promptMsg)) {
        return;
    }

    try {
        const token = window.csrfToken || '';
        const response = await fetch('api/bills.php?action=generate_monthly', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
            },
            body: JSON.stringify({
                month: <?= $filterMonth ?>,
                year: <?= $filterYear ?>,
                csrf_token: token
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
