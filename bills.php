<?php
/**
 * Billing Management & Payments for Rengganis Net
 * Luxury Neo-Skeuomorphic Layout with AdminLTE Sidebar
 */

$pageTitle = 'Tagihan & Billing';
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

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-tech">Tagihan & Billing WiFi</h1>
            <p class="text-xs text-slate-500 font-medium">Automasi penerbitan invoice bulanan, verifikasi pelunasan, dan struk thermal</p>
        </div>

        <?php if (isAdmin()): ?>
        <button onclick="triggerGenerateBills()" class="px-5 py-2.5 rounded-2xl skeuo-btn-emerald text-xs font-black shadow-lg flex items-center justify-center gap-2">
            <i class="fa-solid fa-bolt text-amber-300"></i> Generate Tagihan <?= getMonthName($filterMonth) ?> <?= $filterYear ?>
        </button>
        <?php endif; ?>
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

    <!-- Filter Controls (Neumorphic Inset Bar) -->
    <div class="neu-flat rounded-3xl p-4 sm:p-5">
        <form action="bills.php" method="GET" class="flex flex-wrap items-center justify-between gap-3 text-xs">
            
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
                <i class="fa-solid fa-file-invoice text-4xl mb-3 opacity-30"></i>
                <p class="text-sm font-bold text-slate-800">Tidak ada tagihan untuk periode terpilih.</p>
                <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Generate Tagihan" di atas untuk membuat invoice baru bagi pelanggan aktif.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
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
                                    <?php if (!$isPaid): ?>
                                        <?php if (hasRole(['admin', 'kolektor'])): ?>
                                        <button onclick='openPaymentModal(<?= $billJson ?>)' class="px-4 py-2 rounded-xl skeuo-btn-primary text-xs font-bold shadow-md flex items-center gap-1.5 ml-auto">
                                            <i class="fa-solid fa-money-bill-wave"></i> Bayar
                                        </button>
                                        <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-400 bg-slate-100 ml-auto" title="Hanya Admin & Kolektor">
                                            <i class="fa-solid fa-lock text-[9px]"></i> Kasir Only
                                        </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button onclick='loadAndShowReceipt(<?= $bill['id'] ?>)' class="px-4 py-2 rounded-xl skeuo-btn-secondary text-xs font-bold flex items-center gap-1.5 ml-auto text-blue-700">
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
    if (!confirm('Apakah Anda yakin ingin men-generate tagihan untuk semua pelanggan aktif pada bulan <?= getMonthName($filterMonth) ?> <?= $filterYear ?>?\n\nCatatan: Pelanggan yang berstatus CUTI akan otomatis DILEWATI tanpa denda (FR-03).')) {
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
