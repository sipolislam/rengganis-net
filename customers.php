<?php
/**
 * Customer Management for Rengganis Net
 * FR-02 & FR-03 (CRUD + Status Cuti/Aktif/Isolir Management)
 */

$pageTitle = 'Manajemen Pelanggan';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$currentUser = getCurrentUser();

// Handle Form Submissions (Create, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // 1. CREATE CUSTOMER
    if ($action === 'create' && isAdmin()) {
        $code    = trim($_POST['customer_code'] ?? '');
        $name    = trim($_POST['name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $pkgId   = (int)($_POST['package_id'] ?? 0);
        $ip      = trim($_POST['ip_address'] ?? '');
        $date    = trim($_POST['installation_date'] ?? date('Y-m-d'));
        $status  = trim($_POST['status'] ?? 'aktif');
        $notes   = trim($_POST['notes'] ?? '');

        if (empty($name) || empty($phone) || empty($address) || $pkgId <= 0) {
            setFlash('error', 'Semua kolom bertanda bintang (*) wajib diisi!');
        } else {
            // Auto generate code if empty
            if (empty($code)) {
                $maxCode = $pdo->query("SELECT MAX(id) FROM customers")->fetchColumn();
                $code = 'RNG-' . str_pad((string)($maxCode + 1), 3, '0', STR_PAD_LEFT);
            }

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO customers (customer_code, name, phone, address, package_id, ip_address, installation_date, status, notes, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$code, $name, $phone, $address, $pkgId, $ip ?: null, $date, $status, $notes ?: null]);
                $newCustId = $pdo->lastInsertId();

                // If active, generate current month's bill
                if ($status === 'aktif') {
                    $curMonth = (int)date('n');
                    $curYear = (int)date('Y');
                    generateCustomerBill($pdo, $newCustId, $curMonth, $curYear);
                }

                setFlash('success', "Pelanggan {$name} ({$code}) berhasil ditambahkan!");
                header('Location: customers.php');
                exit;
            } catch (Exception $e) {
                setFlash('error', 'Gagal menambahkan pelanggan: ' . $e->getMessage());
            }
        }
    }

    // 2. UPDATE CUSTOMER
    if ($action === 'edit' && isAdmin()) {
        $id      = (int)($_POST['id'] ?? 0);
        $code    = trim($_POST['customer_code'] ?? '');
        $name    = trim($_POST['name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $pkgId   = (int)($_POST['package_id'] ?? 0);
        $ip      = trim($_POST['ip_address'] ?? '');
        $date    = trim($_POST['installation_date'] ?? date('Y-m-d'));
        $notes   = trim($_POST['notes'] ?? '');

        if ($id <= 0 || empty($name) || empty($phone) || empty($address) || $pkgId <= 0) {
            setFlash('error', 'Semua kolom bertanda bintang (*) wajib diisi!');
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE customers 
                    SET customer_code = ?, name = ?, phone = ?, address = ?, package_id = ?, ip_address = ?, installation_date = ?, notes = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$code, $name, $phone, $address, $pkgId, $ip ?: null, $date, $notes ?: null, $id]);
                setFlash('success', "Data pelanggan {$name} berhasil diperbarui!");
                header('Location: customers.php');
                exit;
            } catch (Exception $e) {
                setFlash('error', 'Gagal memperbarui pelanggan: ' . $e->getMessage());
            }
        }
    }

    // 3. DELETE CUSTOMER
    if ($action === 'delete' && isAdmin()) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                $stmt->execute([$id]);
                setFlash('success', 'Pelanggan berhasil dihapus dari sistem.');
                header('Location: customers.php');
                exit;
            } catch (Exception $e) {
                setFlash('error', 'Gagal menghapus pelanggan: ' . $e->getMessage());
            }
        }
    }
}

// Fetch Packages for Dropdowns
$packages = $pdo->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC")->fetchAll();

// Filters & Search
$filterStatus = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT c.*, p.name AS package_name, p.speed AS package_speed, p.price AS package_price
    FROM customers c
    JOIN packages p ON c.package_id = p.id
    WHERE 1=1
";
$params = [];

if ($filterStatus !== 'all' && in_array($filterStatus, ['aktif', 'cuti', 'isolir'])) {
    $sql .= " AND c.status = :status";
    $params[':status'] = $filterStatus;
}

if (!empty($search)) {
    $sql .= " AND (c.name LIKE :q OR c.customer_code LIKE :q OR c.phone LIKE :q OR c.address LIKE :q OR c.ip_address LIKE :q)";
    $params[':q'] = "%{$search}%";
}

$sql .= " ORDER BY c.customer_code ASC";
$stmtCust = $pdo->prepare($sql);
$stmtCust->execute($params);
$customers = $stmtCust->fetchAll();

// Count badges for status tabs
$countTabs = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) AS total_aktif,
        SUM(CASE WHEN status = 'cuti' THEN 1 ELSE 0 END) AS total_cuti,
        SUM(CASE WHEN status = 'isolir' THEN 1 ELSE 0 END) AS total_isolir
    FROM customers
")->fetch();
?>

<div class="space-y-6">

    <!-- Page Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight">Manajemen Pelanggan</h1>
            <p class="text-xs md:text-sm text-slate-500">Kelola data pelanggan, paket langganan, dan status cuti WiFi</p>
        </div>
        
        <?php if (isAdmin()): ?>
        <button onclick="openAddCustomerModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/25 transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Tambah Pelanggan Baru
        </button>
        <?php endif; ?>
    </div>

    <!-- Filter Tabs & Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-4">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            
            <!-- Status Tabs -->
            <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
                <a href="customers.php<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" class="px-3 py-1.5 rounded-xl transition-colors <?= $filterStatus === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    Semua (<?= (int)$countTabs['total'] ?>)
                </a>
                <a href="customers.php?status=aktif<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1.5 rounded-xl transition-colors <?= $filterStatus === 'aktif' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' ?>">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1"></span> Aktif (<?= (int)$countTabs['total_aktif'] ?>)
                </a>
                <a href="customers.php?status=cuti<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1.5 rounded-xl transition-colors <?= $filterStatus === 'cuti' ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' ?>" title="Pelanggan cuti tidak diterbitkan tagihan (FR-03)">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-400 mr-1"></span> Cuti / Libur (<?= (int)$countTabs['total_cuti'] ?>)
                </a>
                <a href="customers.php?status=isolir<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1.5 rounded-xl transition-colors <?= $filterStatus === 'isolir' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' ?>">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-400 mr-1"></span> Isolir (<?= (int)$countTabs['total_isolir'] ?>)
                </a>
            </div>

            <!-- Search Input -->
            <form action="customers.php" method="GET" class="flex items-center gap-2">
                <?php if ($filterStatus !== 'all'): ?>
                    <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
                <?php endif; ?>
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama, IP, kode, telp..." class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none bg-slate-50">
                </div>
                <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-semibold hover:bg-slate-700 transition-colors">
                    Cari
                </button>
                <?php if (!empty($search)): ?>
                    <a href="customers.php?status=<?= e($filterStatus) ?>" class="text-xs text-rose-500 hover:underline">Reset</a>
                <?php endif; ?>
            </form>

        </div>

    </div>

    <!-- Customers Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        
        <?php if (empty($customers)): ?>
            <div class="p-12 text-center text-slate-400">
                <i class="fa-solid fa-users-slash text-3xl mb-3 opacity-40"></i>
                <p class="text-sm font-semibold text-slate-700">Tidak ada pelanggan ditemukan.</p>
                <p class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter status Anda.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 border-b border-slate-200 uppercase font-bold text-[10px] tracking-wider">
                            <th class="py-3.5 px-4">Kode & Nama</th>
                            <th class="py-3.5 px-4">Kontak & Alamat</th>
                            <th class="py-3.5 px-4">Paket WiFi</th>
                            <th class="py-3.5 px-4">IP Address</th>
                            <th class="py-3.5 px-4">Tgl Pasang</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($customers as $c): ?>
                            <?php
                            $cJson = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
                            // Clean phone for WhatsApp direct click
                            $cleanPhone = preg_replace('/\D/', '', $c['phone']);
                            if (str_starts_with($cleanPhone, '0')) {
                                $cleanPhone = '62' . substr($cleanPhone, 1);
                            }
                            ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-extrabold text-slate-900"><?= e($c['name']) ?></div>
                                    <div class="font-mono text-[11px] text-blue-600 font-semibold"><?= e($c['customer_code']) ?></div>
                                </td>
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="flex items-center gap-1.5">
                                        <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-semibold inline-flex items-center gap-1" title="Kirim Chat WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i> <?= e($c['phone']) ?>
                                        </a>
                                    </div>
                                    <div class="text-[11px] text-slate-500 truncate" title="<?= e($c['address']) ?>"><?= e($c['address']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800"><?= e($c['package_name']) ?></div>
                                    <div class="text-[11px] text-slate-500 font-semibold"><?= formatRupiah($c['package_price']) ?> / bln</div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600">
                                    <?= e($c['ip_address'] ?: '-') ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <?= formatDate($c['installation_date']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <?= getStatusBadge($c['status']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        
                                        <!-- Tombol Ubah Status (FR-03 Cuti/Aktif/Isolir) -->
                                        <button onclick='openStatusModal(<?= $cJson ?>)' title="Ubah Status Langganan (FR-03)" class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition-colors">
                                            <i class="fa-solid fa-arrows-rotate text-xs"></i>
                                        </button>

                                        <!-- Tombol Detail / Riwayat -->
                                        <button onclick='viewCustomerDetail(<?= $c['id'] ?>)' title="Lihat Detail & Riwayat Tagihan" class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition-colors">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </button>

                                        <?php if (isAdmin()): ?>
                                        <!-- Tombol Edit -->
                                        <button onclick='openEditCustomerModal(<?= $cJson ?>)' title="Edit Data Pelanggan" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>

                                        <!-- Tombol Hapus -->
                                        <form action="customers.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pelanggan <?= e($c['name']) ?> beserta seluruh riwayatnya?')" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" title="Hapus Pelanggan" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 transition-colors">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- ========================================== -->
<!-- MODAL: TAMBAH PELANGGAN BARU (FR-02)       -->
<!-- ========================================== -->
<div id="addCustomerModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden">
        
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white flex items-center justify-between">
            <h3 class="font-bold text-base"><i class="fa-solid fa-user-plus mr-2"></i> Tambah Pelanggan Baru</h3>
            <button onclick="closeAddCustomerModal()" type="button" class="text-white/80 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="customers.php" method="POST" class="p-6 space-y-3.5 text-xs">
            <input type="hidden" name="action" value="create">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Kode Pelanggan (Opsional)</label>
                    <input type="text" name="customer_code" placeholder="Otomatis RNG-XXX jika kosong" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Status Awal</label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="aktif">Aktif</option>
                        <option value="cuti">Cuti / Libur</option>
                        <option value="isolir">Isolir</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                <input type="text" name="name" required placeholder="Contoh: Ahmad Fauzi" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 font-medium">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">No. WhatsApp / HP *</label>
                    <input type="text" name="phone" required placeholder="08123456789" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">IP Address</label>
                    <input type="text" name="ip_address" placeholder="192.168.10.XX" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Paket WiFi *</label>
                    <select name="package_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">-- Pilih Paket --</option>
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?= $pkg['id'] ?>"><?= e($pkg['name']) ?> (<?= formatRupiah($pkg['price']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Pasang</label>
                    <input type="date" name="installation_date" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 font-medium">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Alamat Lengkap *</label>
                <textarea name="address" rows="2" required placeholder="RT/RW, Dusun, Patokan rumah..." class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                <input type="text" name="notes" placeholder="Catatan teknis / tiang FO..." class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="pt-3 flex items-center gap-3">
                <button type="button" onclick="closeAddCustomerModal()" class="w-1/3 py-2.5 rounded-xl border border-slate-200 font-semibold hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-md shadow-blue-500/20">
                    Simpan Pelanggan
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: EDIT PELANGGAN                      -->
<!-- ========================================== -->
<div id="editCustomerModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden">
        
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 class="font-bold text-base"><i class="fa-solid fa-pen-to-square mr-2"></i> Edit Data Pelanggan</h3>
            <button onclick="closeEditCustomerModal()" type="button" class="text-white/80 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="customers.php" method="POST" class="p-6 space-y-3.5 text-xs">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="editCustId" name="id" value="">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Kode Pelanggan</label>
                    <input type="text" id="editCustCode" name="customer_code" required class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono font-semibold focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Pasang</label>
                    <input type="date" id="editCustDate" name="installation_date" required class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 font-medium">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                <input type="text" id="editCustName" name="name" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 font-bold">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">No. WhatsApp / HP *</label>
                    <input type="text" id="editCustPhone" name="phone" required class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">IP Address</label>
                    <input type="text" id="editCustIp" name="ip_address" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Paket WiFi *</label>
                <select id="editCustPkg" name="package_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                    <?php foreach ($packages as $pkg): ?>
                        <option value="<?= $pkg['id'] ?>"><?= e($pkg['name']) ?> (<?= formatRupiah($pkg['price']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Alamat Lengkap *</label>
                <textarea id="editCustAddress" name="address" rows="2" required class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                <input type="text" id="editCustNotes" name="notes" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="pt-3 flex items-center gap-3">
                <button type="button" onclick="closeEditCustomerModal()" class="w-1/3 py-2.5 rounded-xl border border-slate-200 font-semibold hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold shadow-md">
                    Simpan Perubahan
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: DETAIL PELANGGAN & RIWAYAT TAGIHAN  -->
<!-- ========================================== -->
<div id="detailCustomerModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-100 overflow-hidden">
        
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 class="font-bold text-base"><i class="fa-solid fa-address-card mr-2"></i> Profil & Riwayat Pelanggan</h3>
            <button onclick="closeDetailCustomerModal()" type="button" class="text-white/80 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div id="detailContent" class="p-6 space-y-5 text-xs max-h-[80vh] overflow-y-auto">
            <!-- Loaded dynamically via AJAX -->
            <div class="text-center py-8 text-slate-400">
                <i class="fa-solid fa-spinner fa-spin text-xl"></i> Memuat data...
            </div>
        </div>

    </div>
</div>

<script>
function openAddCustomerModal() {
    document.getElementById('addCustomerModal').classList.remove('hidden');
    document.getElementById('addCustomerModal').classList.add('flex');
}
function closeAddCustomerModal() {
    document.getElementById('addCustomerModal').classList.add('hidden');
    document.getElementById('addCustomerModal').classList.remove('flex');
}

function openEditCustomerModal(cust) {
    document.getElementById('editCustId').value = cust.id;
    document.getElementById('editCustCode').value = cust.customer_code;
    document.getElementById('editCustName').value = cust.name;
    document.getElementById('editCustPhone').value = cust.phone;
    document.getElementById('editCustAddress').value = cust.address;
    document.getElementById('editCustPkg').value = cust.package_id;
    document.getElementById('editCustIp').value = cust.ip_address || '';
    document.getElementById('editCustDate').value = cust.installation_date;
    document.getElementById('editCustNotes').value = cust.notes || '';

    document.getElementById('editCustomerModal').classList.remove('hidden');
    document.getElementById('editCustomerModal').classList.add('flex');
}
function closeEditCustomerModal() {
    document.getElementById('editCustomerModal').classList.add('hidden');
    document.getElementById('editCustomerModal').classList.remove('flex');
}

async function viewCustomerDetail(id) {
    const modal = document.getElementById('detailCustomerModal');
    const container = document.getElementById('detailContent');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    container.innerHTML = `<div class="text-center py-8 text-slate-400"><i class="fa-solid fa-spinner fa-spin text-xl"></i> Memuat detail pelanggan...</div>`;

    try {
        const res = await fetch(`api/customers.php?action=get_detail&id=${id}`).then(r => r.json());
        if (!res.success) throw new Error(res.message);

        const c = res.customer;
        const bills = res.bills;
        const logs = res.logs;

        let billsHtml = bills.length === 0 ? '<p class="text-slate-400 py-2">Belum ada catatan tagihan.</p>' : '';
        bills.forEach(b => {
            const isLunas = b.status === 'lunas';
            billsHtml += `
                <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                    <div>
                        <div class="font-bold text-slate-800">${b.bill_number}</div>
                        <div class="text-[10px] text-slate-400">Periode: Bulan ${b.period_month}/${b.period_year} • Jatuh Tempo: ${b.due_date}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-slate-900">Rp ${Number(b.amount).toLocaleString('id-ID')}</div>
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold ${isLunas ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}">
                            ${isLunas ? 'LUNAS' : 'BELUM LUNAS'}
                        </span>
                    </div>
                </div>
            `;
        });

        let logsHtml = logs.length === 0 ? '<p class="text-slate-400 py-2">Belum ada riwayat perubahan status.</p>' : '';
        logs.forEach(l => {
            logsHtml += `
                <div class="py-1.5 text-[11px] border-b border-slate-100 last:border-0">
                    <div class="flex justify-between text-slate-500">
                        <span><strong class="uppercase">${l.old_status}</strong> &rarr; <strong class="uppercase text-blue-600">${l.new_status}</strong></span>
                        <span class="text-[10px] text-slate-400">${l.created_at}</span>
                    </div>
                    ${l.reason ? `<div class="italic text-slate-600 mt-0.5">"${l.reason}"</div>` : ''}
                    <div class="text-[10px] text-slate-400">Oleh: ${l.changed_by}</div>
                </div>
            `;
        });

        container.innerHTML = `
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="text-base font-extrabold text-slate-900">${c.name}</h4>
                        <div class="font-mono text-xs text-blue-600 font-semibold">${c.customer_code} • IP: ${c.ip_address || '-'}</div>
                    </div>
                    <div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase ${c.status === 'aktif' ? 'bg-emerald-100 text-emerald-800' : (c.status === 'cuti' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')}">
                            ${c.status}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-200 text-slate-600">
                    <div><strong>WhatsApp:</strong> ${c.phone}</div>
                    <div><strong>Paket:</strong> ${c.package_name} (${c.package_speed})</div>
                    <div><strong>Tgl Pasang:</strong> ${c.installation_date}</div>
                    <div><strong>Alamat:</strong> ${c.address}</div>
                </div>
            </div>

            <div>
                <h5 class="font-bold text-slate-800 mb-2 uppercase text-[11px] tracking-wider"><i class="fa-solid fa-receipt mr-1 text-blue-600"></i> Riwayat Tagihan Terakhir</h5>
                <div class="bg-white rounded-xl border border-slate-200 p-3 divide-y divide-slate-100">
                    ${billsHtml}
                </div>
            </div>

            <div>
                <h5 class="font-bold text-slate-800 mb-2 uppercase text-[11px] tracking-wider"><i class="fa-solid fa-clock-rotate-left mr-1 text-amber-600"></i> Riwayat Status Langganan</h5>
                <div class="bg-white rounded-xl border border-slate-200 p-3">
                    ${logsHtml}
                </div>
            </div>
        `;
    } catch (e) {
        container.innerHTML = `<div class="p-4 bg-rose-50 text-rose-700 rounded-xl">Error: ${e.message}</div>`;
    }
}
function closeDetailCustomerModal() {
    document.getElementById('detailCustomerModal').classList.add('hidden');
    document.getElementById('detailCustomerModal').classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
