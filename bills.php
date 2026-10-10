<?php
/**
 * Billing Management & Payments for Rengganis Net
 * FR-04, FR-05, FR-06, FR-07
 */

$pageTitle = 'Manajemen Tagihan & Pembayaran';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// Current filters
$filterMonth = (int)($_GET['month'] ?? date('n'));
$filterYear = (int)($_GET['year'] ?? date('Y'));
$filterStatus = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

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
    $sql .= " AND (b.bill_number LIKE :q OR c.name LIKE :q OR c.customer_code LIKE :q OR c.phone LIKE :q)";
    $params[':q'] = "%{$search}%";
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
?>

<div class="space-y-6">

    <!-- Header & Batch Generator -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight">Tagihan & Pembayaran WiFi</h1>
            <p class="text-xs md:text-sm text-slate-500">Kelola siklus invoice bulanan, verifikasi pelunasan, dan terbitkan bukti struk</p>
        </div>

        <?php if (isAdmin()): ?>
        <div class="flex items-center gap-2">
            <button onclick="triggerGenerateBills()" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xs shadow-md shadow-blue-500/25 transition-all flex items-center gap-2">
                <i class="fa-solid fa-bolt"></i> Generate Tagihan <?= getMonthName($filterMonth) ?> <?= $filterYear ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Period Stat Summary Banner -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Tagihan Periode Ini</span>
                <div class="text-lg font-extrabold text-slate-900 mt-1"><?= formatRupiah($totalAmount) ?></div>
                <div class="text-xs text-slate-500 mt-0.5"><?= (int)$summary['total_count'] ?> Lembar Invoice</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <i class="fa-solid fa-file-invoice text-lg"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Terbayar (Lunas)</span>
                <div class="text-lg font-extrabold text-emerald-700 mt-1"><?= formatRupiah($paidAmount) ?></div>
                <div class="text-xs text-emerald-600 mt-0.5"><?= (int)$summary['paid_count'] ?> Tagihan Selesai</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i class="fa-solid fa-circle-check text-lg"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-500">Tertunggak (Belum Bayar)</span>
                <div class="text-lg font-extrabold text-rose-600 mt-1"><?= formatRupiah($unpaidAmount) ?></div>
                <div class="text-xs text-rose-500 mt-0.5"><?= (int)$summary['unpaid_count'] ?> Tagihan Tertunda</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <i class="fa-solid fa-clock-rotate-left text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <form action="bills.php" method="GET" class="flex flex-wrap items-center justify-between gap-3 text-xs">
            
            <div class="flex flex-wrap items-center gap-2">
                <!-- Month Select -->
                <select name="month" class="px-3 py-1.5 rounded-xl border border-slate-300 font-semibold bg-white focus:ring-2 focus:ring-blue-500">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $filterMonth ? 'selected' : '' ?>><?= getMonthName($m) ?></option>
                    <?php endfor; ?>
                </select>

                <!-- Year Select -->
                <select name="year" class="px-3 py-1.5 rounded-xl border border-slate-300 font-semibold bg-white focus:ring-2 focus:ring-blue-500">
                    <?php for ($y = 2025; $y <= 2027; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $filterYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>

                <!-- Status Select -->
                <select name="status" class="px-3 py-1.5 rounded-xl border border-slate-300 font-semibold bg-white focus:ring-2 focus:ring-blue-500">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="belum_lunas" <?= $filterStatus === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas Saja</option>
                    <option value="lunas" <?= $filterStatus === 'lunas' ? 'selected' : '' ?>>Lunas Saja</option>
                </select>

                <button type="submit" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition-colors">
                    Filter Data
                </button>
            </div>

            <!-- Search box -->
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari no invoice, pelanggan..." class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 bg-slate-50">
            </div>

        </form>
    </div>

    <!-- Bills Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        
        <?php if (empty($bills)): ?>
            <div class="p-12 text-center text-slate-400">
                <i class="fa-solid fa-file-invoice text-3xl mb-3 opacity-40"></i>
                <p class="text-sm font-semibold text-slate-700">Tidak ada tagihan untuk periode terpilih.</p>
                <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Generate Tagihan" di atas untuk membuat invoice baru untuk pelanggan aktif.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 border-b border-slate-200 uppercase font-bold text-[10px] tracking-wider">
                            <th class="py-3.5 px-4">No. Invoice & Tgl</th>
                            <th class="py-3.5 px-4">Pelanggan</th>
                            <th class="py-3.5 px-4">Paket</th>
                            <th class="py-3.5 px-4">Jatuh Tempo</th>
                            <th class="py-3.5 px-4 text-right">Nominal</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
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
                            <tr id="bill-row-<?= $bill['id'] ?>" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-slate-900"><?= e($bill['bill_number']) ?></div>
                                    <div class="text-[10px] text-slate-400">Dibuat: <?= formatDate($bill['created_at']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-extrabold text-slate-900"><?= e($bill['customer_name']) ?></div>
                                    <div class="font-mono text-[11px] text-slate-500"><?= e($bill['customer_code']) ?> • <?= e($bill['customer_phone']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-800"><?= e($bill['package_name']) ?></div>
                                    <div class="text-[10px] text-blue-600 font-semibold"><?= e($bill['package_speed']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="<?= $isPaid ? 'text-slate-600' : 'text-rose-600 font-semibold' ?>">
                                        <?= formatDate($bill['due_date']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-extrabold text-slate-900">
                                    <?= formatRupiah($bill['amount']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center status-col">
                                    <?= getStatusBadge($bill['status']) ?>
                                    <?php if ($isPaid && $bill['payment_method']): ?>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><?= e($bill['payment_method']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right action-col">
                                    <?php if (!$isPaid): ?>
                                        <button onclick='openPaymentModal(<?= $billJson ?>)' class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition-all flex items-center gap-1.5 ml-auto">
                                            <i class="fa-solid fa-money-bill-wave"></i> Bayar
                                        </button>
                                    <?php else: ?>
                                        <button onclick='loadAndShowReceipt(<?= $bill['id'] ?>)' class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-semibold text-xs transition-colors flex items-center gap-1.5 ml-auto">
                                            <i class="fa-solid fa-receipt text-blue-600"></i> Struk
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

</div>

<script>
// Load receipt for already paid bill
async function loadAndShowReceipt(billId) {
    try {
        const res = await fetch(`api/bills.php?action=get_receipt&id=${billId}`).then(r => r.json());
        if (!res.success) throw new Error(res.message);
        showReceiptModal(res.data);
    } catch (e) {
        alert('Gagal memuat struk: ' + e.message);
    }
}

async function triggerGenerateBills() {
    if (!confirm('Apakah Anda yakin ingin men-generate tagihan untuk semua pelanggan aktif pada bulan <?= getMonthName($filterMonth) ?> <?= $filterYear ?>?\n\nCatatan: Pelanggan yang berstatus CUTI akan otomatis DILEWATI tanpa denda (sesuai FR-03).')) {
        return;
    }

    try {
        const response = await fetch('api/bills.php?action=generate_monthly', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                month: <?= $filterMonth ?>,
                year: <?= $filterYear ?>
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
