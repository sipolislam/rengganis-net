<?php
/**
 * Customer Management for Rengganis Net
 * Luxury Neo-Skeuomorphic Layout with AdminLTE Sidebar
 */

$pageTitle = 'Manajemen Pelanggan';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$currentUser = getCurrentUser();

// Handle Form Submissions (Create, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAdmin()) {
        denyAccess('Hanya Admin Pengelola yang dapat menambah, mengubah, atau menghapus data pelanggan (403 Forbidden).');
    }
    validateCsrfOrDie('customers.php');
    $action = $_POST['action'] ?? '';
    
    // 1. CREATE CUSTOMER
    if ($action === 'create') {
        $code    = trim($_POST['customer_code'] ?? '');
        $name    = trim($_POST['name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $pkgId   = (int)($_POST['package_id'] ?? 0);
        $ip      = trim($_POST['ip_address'] ?? '');
        $date    = trim($_POST['installation_date'] ?? date('Y-m-d'));
        $status  = trim($_POST['status'] ?? 'aktif');
        $notes   = trim($_POST['notes'] ?? '');
        $phoneValid = validatePhoneNumber($phone);
        $ipValid = empty($ip) || (bool)filter_var($ip, FILTER_VALIDATE_IP);
        if (empty($name) || empty($phone) || empty($address) || $pkgId <= 0) {
            setFlash('error', 'Semua kolom bertanda bintang (*) wajib diisi!');
        } elseif (!$phoneValid) {
            setFlash('error', 'Nomor WhatsApp / HP tidak valid! Masukkan nomor seluler Indonesia yang valid (contoh: 081234567890).');
        } elseif (!$ipValid) {
            setFlash('error', 'Format IP Address tidak valid! Masukkan format IPv4 yang benar (contoh: 192.168.1.10).');
        } else {
            // Validasi paket aktif
            $chkPkg = $pdo->prepare("SELECT id FROM packages WHERE id = ? AND is_active = 1");
            $chkPkg->execute([$pkgId]);
            if (!$chkPkg->fetch()) {
                setFlash('error', 'Paket layanan WiFi yang dipilih tidak valid atau sudah dinonaktifkan!');
                header('Location: customers.php');
                exit;
            }

            if (empty($code)) {
                $maxCode = $pdo->query("SELECT MAX(id) FROM customers")->fetchColumn();
                $code = 'RNG-' . str_pad((string)($maxCode + 1), 3, '0', STR_PAD_LEFT);
            } else {
                $chkCode = $pdo->prepare("SELECT id FROM customers WHERE customer_code = ?");
                $chkCode->execute([$code]);
                if ($chkCode->fetch()) {
                    setFlash('error', "Kode pelanggan '{$code}' sudah digunakan oleh pelanggan lain!");
                    header('Location: customers.php');
                    exit;
                }
            }

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO customers (customer_code, name, phone, address, package_id, ip_address, installation_date, status, notes, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$code, $name, $phone, $address, $pkgId, $ip ?: null, $date, $status, $notes ?: null]);
                $newCustId = $pdo->lastInsertId();

                if ($status === 'aktif') {
                    $curMonth = (int)date('n');
                    $curYear = (int)date('Y');
                    generateCustomerBill($pdo, $newCustId, $curMonth, $curYear);
                } else {
                    // Record audit log for initial cuti / isolir status (T-005 / F2)
                    $stmtInitLog = $pdo->prepare("INSERT INTO status_logs (customer_id, old_status, new_status, reason, changed_by, created_at) VALUES (?, 'aktif', ?, ?, ?, NOW())");
                    $stmtInitLog->execute([$newCustId, $status, $notes ?: ('Pendaftaran baru status ' . ucfirst($status)), $currentUser['name']]);
                }

                setFlash('success', "Pelanggan {$name} ({$code}) berhasil didaftarkan!");
                header('Location: customers.php');
                exit;
            } catch (Exception $e) {
                setFlash('error', 'Gagal menambahkan pelanggan: ' . $e->getMessage());
            }
        }
    }

    // 2. UPDATE CUSTOMER
    if ($action === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $code    = trim($_POST['customer_code'] ?? '');
        $name    = trim($_POST['name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $pkgId   = (int)($_POST['package_id'] ?? 0);
        $ip      = trim($_POST['ip_address'] ?? '');
        $date    = trim($_POST['installation_date'] ?? date('Y-m-d'));
        $notes   = trim($_POST['notes'] ?? '');
        $phoneValid = validatePhoneNumber($phone);
        $ipValid = empty($ip) || (bool)filter_var($ip, FILTER_VALIDATE_IP);

        if ($id <= 0 || empty($name) || empty($phone) || empty($address) || $pkgId <= 0) {
            setFlash('error', 'Semua kolom bertanda bintang (*) wajib diisi!');
        } elseif (!$phoneValid) {
            setFlash('error', 'Nomor WhatsApp / HP tidak valid! Masukkan nomor seluler Indonesia yang valid (contoh: 081234567890).');
        } elseif (!$ipValid) {
            setFlash('error', 'Format IP Address tidak valid! Masukkan format IPv4 yang benar (contoh: 192.168.1.10).');
        } else {
            // Validasi keunikan kode pelanggan
            $chkCode = $pdo->prepare("SELECT id FROM customers WHERE customer_code = ? AND id != ?");
            $chkCode->execute([$code, $id]);
            if ($chkCode->fetch()) {
                setFlash('error', "Kode pelanggan '{$code}' sudah digunakan oleh pelanggan lain!");
                header('Location: customers.php');
                exit;
            }

            // Validasi paket aktif
            $chkPkg = $pdo->prepare("SELECT id FROM packages WHERE id = ? AND is_active = 1");
            $chkPkg->execute([$pkgId]);
            if (!$chkPkg->fetch()) {
                setFlash('error', 'Paket layanan WiFi yang dipilih tidak valid atau sudah dinonaktifkan!');
                header('Location: customers.php');
                exit;
            }

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
    if ($action === 'delete') {
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

// Fetch Packages
$packages = $pdo->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC")->fetchAll();

// Filters & Search (T-011)
$filterStatus = $_GET['status'] ?? 'all';
$filterPackage = (int)($_GET['package_id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$isFiltered = ($filterStatus !== 'all' || !empty($search) || $filterPackage > 0);

// Helper for building filter URLs preserving other active params
$buildCustomerUrl = function($overrides = []) use ($filterStatus, $filterPackage, $search) {
    $params = [
        'status'     => $filterStatus,
        'package_id' => $filterPackage,
        'search'     => $search
    ];
    $merged = array_merge($params, $overrides);
    if ($merged['status'] === 'all') unset($merged['status']);
    if (empty($merged['package_id'])) unset($merged['package_id']);
    if (empty($merged['search'])) unset($merged['search']);
    return empty($merged) ? 'customers.php' : 'customers.php?' . http_build_query($merged);
};

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

if ($filterPackage > 0) {
    $sql .= " AND c.package_id = :package_id";
    $params[':package_id'] = $filterPackage;
}

if (!empty($search)) {
    // Unique named params for native PDO statement safety
    $sql .= " AND (c.name LIKE :q1 OR c.customer_code LIKE :q2 OR c.phone LIKE :q3 OR c.address LIKE :q4 OR c.ip_address LIKE :q5)";
    $term = "%{$search}%";
    $params[':q1'] = $term;
    $params[':q2'] = $term;
    $params[':q3'] = $term;
    $params[':q4'] = $term;
    $params[':q5'] = $term;
}

$sql .= " ORDER BY c.customer_code ASC";
$stmtCust = $pdo->prepare($sql);
$stmtCust->execute($params);
$customers = $stmtCust->fetchAll();

// Counts for filter pills
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

    <!-- Top Action & Title Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-tech">Database Pelanggan WiFi</h1>
            <p class="text-xs text-slate-500 font-medium">Monitoring status langganan, manajemen cuti, dan detail teknis jaringan</p>
        </div>
        
        <?php if (isAdmin()): ?>
        <button onclick="openAddCustomerModal()" class="w-full sm:w-auto px-5 py-2.5 rounded-2xl skeuo-btn-primary text-xs font-bold shadow-lg flex items-center justify-center gap-2 active:scale-95 transition-all">
            <i class="fa-solid fa-user-plus"></i> Tambah Pelanggan
        </button>
        <?php endif; ?>
    </div>

    <!-- Filter Pills & Search Panel (Neumorphic Surface - T-011) -->
    <div class="neu-flat rounded-3xl p-4 sm:p-5 flex flex-col gap-4">
        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Status Filter Pills -->
            <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                <a href="<?= e($buildCustomerUrl(['status' => 'all'])) ?>" class="px-4 py-2 rounded-xl transition-all <?= $filterStatus === 'all' ? 'skeuo-btn-dark' : 'skeuo-btn-secondary text-slate-600' ?>">
                    Semua (<?= (int)$countTabs['total'] ?>)
                </a>
                <a href="<?= e($buildCustomerUrl(['status' => 'aktif'])) ?>" class="px-4 py-2 rounded-xl transition-all flex items-center gap-1.5 <?= $filterStatus === 'aktif' ? 'skeuo-btn-emerald' : 'skeuo-btn-secondary text-emerald-700' ?>">
                    <span class="led-pulse active-green"></span> Aktif (<?= (int)$countTabs['total_aktif'] ?>)
                </a>
                <a href="<?= e($buildCustomerUrl(['status' => 'cuti'])) ?>" class="px-4 py-2 rounded-xl transition-all flex items-center gap-1.5 <?= $filterStatus === 'cuti' ? 'bg-amber-500 text-white shadow-md' : 'skeuo-btn-secondary text-amber-700' ?>" title="Pelanggan cuti tidak diterbitkan tagihan (FR-03)">
                    <span class="led-pulse active-amber"></span> Cuti / Libur (<?= (int)$countTabs['total_cuti'] ?>)
                </a>
                <a href="<?= e($buildCustomerUrl(['status' => 'isolir'])) ?>" class="px-4 py-2 rounded-xl transition-all flex items-center gap-1.5 <?= $filterStatus === 'isolir' ? 'bg-rose-600 text-white shadow-md' : 'skeuo-btn-secondary text-rose-700' ?>">
                    <span class="led-pulse active-rose"></span> Isolir (<?= (int)$countTabs['total_isolir'] ?>)
                </a>
            </div>

            <!-- Search & Package Filter with Neumorphic Inset -->
            <form action="customers.php" method="GET" class="flex flex-wrap items-center gap-2">
                <?php if ($filterStatus !== 'all'): ?>
                    <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
                <?php endif; ?>

                <!-- Package Dropdown Filter -->
                <select name="package_id" onchange="this.form.submit()" class="neu-input px-3 py-2 rounded-xl text-xs font-bold text-slate-700">
                    <option value="0">Semua Paket Layanan</option>
                    <?php foreach ($packages as $pkg): ?>
                        <option value="<?= $pkg['id'] ?>" <?= $filterPackage === (int)$pkg['id'] ? 'selected' : '' ?>>
                            <?= e($pkg['name']) ?> (<?= e($pkg['speed']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Search Input -->
                <div class="relative w-full sm:w-56">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama, IP, kode..." class="neu-input w-full pl-8 pr-3 py-2 rounded-xl text-xs font-semibold">
                </div>

                <button type="submit" class="px-4 py-2 skeuo-btn-dark rounded-xl text-xs font-bold">
                    Cari
                </button>

                <?php if ($isFiltered): ?>
                    <a href="customers.php" class="px-3.5 py-2 rounded-xl text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 font-bold transition-all flex items-center gap-1.5 text-xs shadow-sm" title="Reset seluruh filter dan pencarian">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Filter / Results Counter Summary Bar -->
        <div class="pt-2 border-t border-slate-200/60 flex flex-wrap items-center justify-between text-xs text-slate-500 font-medium">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800">Menampilkan <?= count($customers) ?> dari <?= (int)$countTabs['total'] ?> pelanggan</span>
                <?php if ($isFiltered): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        <i class="fa-solid fa-filter text-[9px]"></i> Filter Aktif:
                        <?= $filterStatus !== 'all' ? 'Status ' . ucfirst($filterStatus) : '' ?>
                        <?= $filterPackage > 0 ? ($filterStatus !== 'all' ? ' | ' : '') . 'Paket ID #' . $filterPackage : '' ?>
                        <?= !empty($search) ? (($filterStatus !== 'all' || $filterPackage > 0) ? ' | ' : '') . '"' . e($search) . '"' : '' ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if ($isFiltered): ?>
                <span class="text-[11px] text-slate-400">Hasil filter instan</span>
            <?php endif; ?>
        </div>

    </div>

    <!-- Customers Table (Tactile Neumorphic Frame) -->
    <div class="neu-flat rounded-3xl overflow-hidden border border-white/80 shadow-md">
        
        <?php if (empty($customers)): ?>
            <div class="p-12 text-center text-slate-400">
                <div class="w-14 h-14 rounded-2xl neu-inset mx-auto flex items-center justify-center text-rose-500 mb-3 shadow-inner">
                    <i class="fa-solid fa-magnifying-glass-chart text-2xl"></i>
                </div>
                <p class="text-sm font-bold text-slate-800">Tidak ada pelanggan ditemukan.</p>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    Kombinasi kata kunci atau filter status/paket yang dipilih tidak menghasilkan data pelanggan.
                </p>
                <?php if ($isFiltered): ?>
                    <div class="mt-4">
                        <a href="customers.php" class="px-4 py-2 rounded-xl skeuo-btn-secondary text-xs font-bold text-slate-700 hover:text-blue-600 transition-colors inline-flex items-center gap-2 shadow-sm">
                            <i class="fa-solid fa-arrow-rotate-left"></i> Bersihkan Filter & Tampilkan Semua
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- DESKTOP / TABLET VIEW: 7-COLUMN TABLE (hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100/70 text-slate-400 border-b border-slate-200 uppercase font-black text-[10px] tracking-wider">
                            <th class="py-3.5 px-5">Kode & Nama</th>
                            <th class="py-3.5 px-4">Kontak & Alamat</th>
                            <th class="py-3.5 px-4">Paket Bandwidth</th>
                            <th class="py-3.5 px-4">IP Address</th>
                            <th class="py-3.5 px-4">Tgl Pasang</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($customers as $c): ?>
                            <?php
                            $cJson = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
                            $waNumber = formatWhatsAppNumber($c['phone']);
                            $dispPhone = formatDisplayPhone($c['phone']);
                            ?>
                            <tr class="hover:bg-white/60 transition-colors">
                                <td class="py-3.5 px-5">
                                    <div class="font-black text-slate-900"><?= e($c['name']) ?></div>
                                    <div class="font-mono text-[11px] text-blue-600 font-bold"><?= e($c['customer_code']) ?></div>
                                </td>
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="flex items-center gap-1.5">
                                        <a href="https://wa.me/<?= $waNumber ?>" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-bold inline-flex items-center gap-1">
                                            <i class="fa-brands fa-whatsapp text-sm"></i> <?= e($dispPhone) ?>
                                        </a>
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-medium truncate" title="<?= e($c['address']) ?>"><?= e($c['address']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900"><?= e($c['package_name']) ?></div>
                                    <div class="text-[11px] text-slate-400 font-mono"><?= formatRupiah($c['package_price']) ?> / bln</div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] font-bold text-slate-600">
                                    <?= e($c['ip_address'] ?: '-') ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 font-medium">
                                    <?= formatDate($c['installation_date']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <?= getStatusBadge($c['status']) ?>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        
                                        <!-- Tombol Ubah Status (FR-03) -->
                                        <button onclick='openStatusModal(<?= $cJson ?>)' title="Ubah Status Langganan (FR-03)" class="w-8 h-8 rounded-xl skeuo-btn-secondary text-amber-700 flex items-center justify-center">
                                            <i class="fa-solid fa-arrows-spin text-xs"></i>
                                        </button>

                                        <!-- Tombol Detail / Riwayat -->
                                        <button onclick='viewCustomerDetail(<?= $c['id'] ?>)' title="Lihat Profil & Riwayat Tagihan" class="w-8 h-8 rounded-xl skeuo-btn-secondary text-blue-700 flex items-center justify-center">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </button>

                                        <?php if (isAdmin()): ?>
                                        <!-- Tombol Edit -->
                                        <button onclick='openEditCustomerModal(<?= $cJson ?>)' title="Edit Data Pelanggan" class="w-8 h-8 rounded-xl skeuo-btn-secondary text-slate-700 flex items-center justify-center">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>

                                        <!-- Tombol Hapus -->
                                        <form action="customers.php" method="POST" onsubmit="return confirm('Hapus pelanggan <?= e($c['name']) ?> beserta seluruh riwayatnya?')" class="inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" title="Hapus Pelanggan" class="w-8 h-8 rounded-xl skeuo-btn-secondary text-rose-600 hover:text-rose-700 flex items-center justify-center">
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

            <!-- MOBILE CARD VIEW: FIELD-OPTIMIZED TOUCH CARDS (visible on mobile < md) -->
            <div class="p-3 sm:p-4 space-y-3.5 block md:hidden">
                <?php foreach ($customers as $c): ?>
                    <?php
                    $cJson = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
                    $waNumber = formatWhatsAppNumber($c['phone']);
                    $dispPhone = formatDisplayPhone($c['phone']);
                    ?>
                    <div class="p-4 rounded-2xl neu-inset bg-white/75 border border-white shadow-sm space-y-3 mobile-card-shadow">
                        <!-- Top: Code, Name & Status Badge -->
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div>
                                <span class="font-mono text-blue-700 font-bold text-xs tracking-tight"><?= e($c['customer_code']) ?></span>
                                <h4 class="font-black text-sm text-slate-900 tracking-tight"><?= e($c['name']) ?></h4>
                            </div>
                            <div>
                                <?= getStatusBadge($c['status']) ?>
                            </div>
                        </div>

                        <!-- Contact & Address -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <a href="https://wa.me/<?= $waNumber ?>" target="_blank" class="text-emerald-700 hover:text-emerald-800 font-bold inline-flex items-center gap-1.5 touch-target-mobile">
                                    <i class="fa-brands fa-whatsapp text-base"></i> <span><?= e($dispPhone) ?></span>
                                </a>
                                <span class="font-mono text-[11px] text-slate-500 font-bold">IP: <?= e($c['ip_address'] ?: '-') ?></span>
                            </div>
                            <div class="text-[11px] text-slate-600 font-medium leading-relaxed">
                                <i class="fa-solid fa-location-dot text-slate-400 mr-1"></i><?= e($c['address']) ?>
                            </div>
                        </div>

                        <!-- Package & Pricing -->
                        <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Paket Langganan</span>
                                <span class="font-bold text-slate-800"><?= e($c['package_name']) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Tarif Bulanan</span>
                                <span class="font-black text-blue-700 font-mono"><?= formatRupiah($c['package_price']) ?></span>
                            </div>
                        </div>

                        <!-- Touch-Friendly Field Action Buttons -->
                        <div class="pt-1 grid grid-cols-2 gap-2">
                            <button onclick='openStatusModal(<?= $cJson ?>)' class="py-2.5 px-3 rounded-xl skeuo-btn-secondary text-amber-800 font-bold text-xs flex items-center justify-center gap-1.5 touch-target-mobile active:scale-95 transition-transform" title="Ubah Status Langganan">
                                <i class="fa-solid fa-arrows-spin text-amber-600"></i> <span>Ubah Status</span>
                            </button>

                            <button onclick='viewCustomerDetail(<?= $c['id'] ?>)' class="py-2.5 px-3 rounded-xl skeuo-btn-secondary text-blue-800 font-bold text-xs flex items-center justify-center gap-1.5 touch-target-mobile active:scale-95 transition-transform" title="Lihat Profil">
                                <i class="fa-solid fa-eye text-blue-600"></i> <span>Profil</span>
                            </button>

                            <?php if (isAdmin()): ?>
                            <button onclick='openEditCustomerModal(<?= $cJson ?>)' class="py-2 px-3 rounded-xl skeuo-btn-secondary text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5 touch-target-mobile active:scale-95 transition-transform" title="Edit Data">
                                <i class="fa-solid fa-pen-to-square"></i> <span>Edit</span>
                            </button>

                            <form action="customers.php" method="POST" onsubmit="return confirm('Hapus pelanggan <?= e($c['name']) ?> beserta seluruh riwayatnya?')" class="w-full">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="w-full py-2 px-3 rounded-xl skeuo-btn-secondary text-rose-700 font-bold text-xs flex items-center justify-center gap-1.5 touch-target-mobile active:scale-95 transition-transform" title="Hapus Pelanggan">
                                    <i class="fa-solid fa-trash text-rose-600"></i> <span>Hapus</span>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- ========================================== -->
<!-- MODAL: TAMBAH PELANGGAN (NEO-SKEUOMORPHIC) -->
<!-- ========================================== -->
<div id="addCustomerModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-md p-4 overflow-y-auto">
    <div class="bg-[#edf3f9] rounded-3xl max-w-lg w-full shadow-[0_25px_60px_rgba(0,0,0,0.4)] border border-white/80 overflow-hidden">
        
        <div class="px-6 py-4 bg-gradient-to-r from-blue-700 to-indigo-700 text-white flex items-center justify-between">
            <h3 class="font-extrabold text-sm font-tech"><i class="fa-solid fa-user-plus mr-2"></i> Tambah Pelanggan Baru</h3>
            <button onclick="closeAddCustomerModal()" type="button" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form action="customers.php" method="POST" class="p-6 space-y-3.5 text-xs">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Kode Pelanggan</label>
                    <input type="text" name="customer_code" placeholder="Otomatis RNG-XXX" class="neu-input w-full px-3 py-2 rounded-xl font-mono font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Status Awal</label>
                    <select name="status" class="neu-input w-full px-3 py-2 rounded-xl font-bold bg-white">
                        <option value="aktif">🟢 Aktif</option>
                        <option value="cuti">🟡 Cuti / Libur</option>
                        <option value="isolir">🔴 Isolir</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                <input type="text" name="name" required placeholder="Contoh: Ahmad Fauzi" class="neu-input w-full px-3 py-2 rounded-xl font-bold text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">No. WhatsApp / HP *</label>
                    <input type="tel" name="phone" required placeholder="081234567890" pattern="^(?:\+?62|0)8[1-9][0-9]{7,11}$" class="neu-input w-full px-3 py-2 rounded-xl font-bold">
                    <span class="text-[10px] text-slate-400 mt-1 block">Format: 08xx / 628xx (10–13 digit)</span>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">IP Address</label>
                    <input type="text" name="ip_address" placeholder="192.168.10.XX" class="neu-input w-full px-3 py-2 rounded-xl font-mono font-bold">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Paket WiFi *</label>
                    <select name="package_id" required class="neu-input w-full px-3 py-2 rounded-xl font-bold">
                        <option value="">-- Pilih Paket --</option>
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?= $pkg['id'] ?>"><?= e($pkg['name']) ?> (<?= formatRupiah($pkg['price']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Pasang</label>
                    <input type="date" name="installation_date" value="<?= date('Y-m-d') ?>" class="neu-input w-full px-3 py-2 rounded-xl font-bold">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Alamat Lengkap *</label>
                <textarea name="address" rows="2" required placeholder="RT/RW, Dusun, Patokan..." class="neu-input w-full px-3 py-2 rounded-xl font-medium"></textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                <input type="text" name="notes" placeholder="Catatan teknis FO..." class="neu-input w-full px-3 py-2 rounded-xl">
            </div>

            <div class="pt-2 flex items-center gap-3">
                <button type="button" onclick="closeAddCustomerModal()" class="w-1/3 py-2.5 rounded-xl skeuo-btn-secondary font-bold">
                    Batal
                </button>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl skeuo-btn-primary font-black shadow-lg">
                    Simpan Pelanggan
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: EDIT PELANGGAN                      -->
<!-- ========================================== -->
<div id="editCustomerModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-md p-4 overflow-y-auto">
    <div class="bg-[#edf3f9] rounded-3xl max-w-lg w-full shadow-[0_25px_60px_rgba(0,0,0,0.4)] border border-white/80 overflow-hidden">
        
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 class="font-extrabold text-sm font-tech"><i class="fa-solid fa-pen-to-square mr-2"></i> Edit Data Pelanggan</h3>
            <button onclick="closeEditCustomerModal()" type="button" class="w-8 h-8 rounded-xl bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form action="customers.php" method="POST" class="p-6 space-y-3.5 text-xs">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="editCustId" name="id" value="">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Kode Pelanggan</label>
                    <input type="text" id="editCustCode" name="customer_code" required class="neu-input w-full px-3 py-2 rounded-xl font-mono font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Pasang</label>
                    <input type="date" id="editCustDate" name="installation_date" required class="neu-input w-full px-3 py-2 rounded-xl font-bold">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                <input type="text" id="editCustName" name="name" required class="neu-input w-full px-3 py-2 rounded-xl font-bold text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">No. WhatsApp / HP *</label>
                    <input type="tel" id="editCustPhone" name="phone" required placeholder="081234567890" pattern="^(?:\+?62|0)8[1-9][0-9]{7,11}$" class="neu-input w-full px-3 py-2 rounded-xl font-bold">
                    <span class="text-[10px] text-slate-400 mt-1 block">Format: 08xx / 628xx (10–13 digit)</span>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">IP Address</label>
                    <input type="text" id="editCustIp" name="ip_address" class="neu-input w-full px-3 py-2 rounded-xl font-mono font-bold">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Paket WiFi *</label>
                <select id="editCustPkg" name="package_id" required class="neu-input w-full px-3 py-2 rounded-xl font-bold">
                    <?php foreach ($packages as $pkg): ?>
                        <option value="<?= $pkg['id'] ?>"><?= e($pkg['name']) ?> (<?= formatRupiah($pkg['price']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Alamat Lengkap *</label>
                <textarea id="editCustAddress" name="address" rows="2" required class="neu-input w-full px-3 py-2 rounded-xl font-medium"></textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                <input type="text" id="editCustNotes" name="notes" class="neu-input w-full px-3 py-2 rounded-xl">
            </div>

            <div class="pt-2 flex items-center gap-3">
                <button type="button" onclick="closeEditCustomerModal()" class="w-1/3 py-2.5 rounded-xl skeuo-btn-secondary font-bold">
                    Batal
                </button>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl skeuo-btn-dark font-black shadow-lg">
                    Simpan Perubahan
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: DETAIL PELANGGAN & RIWAYAT          -->
<!-- ========================================== -->
<div id="detailCustomerModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-md p-4 overflow-y-auto">
    <div class="bg-[#edf3f9] rounded-3xl max-w-2xl w-full shadow-[0_25px_60px_rgba(0,0,0,0.4)] border border-white/80 overflow-hidden">
        
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <h3 class="font-extrabold text-sm font-tech"><i class="fa-solid fa-address-card mr-2"></i> Profil & Riwayat Pelanggan</h3>
            <button onclick="closeDetailCustomerModal()" type="button" class="w-8 h-8 rounded-xl bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div id="detailContent" class="p-6 space-y-4 text-xs max-h-[80vh] overflow-y-auto">
            <div class="text-center py-8 text-slate-400 font-bold">
                <i class="fa-solid fa-spinner fa-spin text-xl"></i> Memuat data...
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'create') {
        openAddCustomerModal();
    }
});

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
                        <div class="font-bold text-slate-900 font-mono">${b.bill_number}</div>
                        <div class="text-[10px] text-slate-400">Periode: Bulan ${b.period_month}/${b.period_year} &bull; Jatuh Tempo: ${b.due_date}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-black text-slate-900 font-mono">Rp ${Number(b.amount).toLocaleString('id-ID')}</div>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black ${isLunas ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">
                            ${isLunas ? 'LUNAS' : 'BELUM LUNAS'}
                        </span>
                    </div>
                </div>
            `;
        });

        let logsHtml = logs.length === 0 ? '<p class="text-slate-400 py-2">Belum ada riwayat status.</p>' : '';
        logs.forEach(l => {
            logsHtml += `
                <div class="py-2 text-[11px] border-b border-slate-100 last:border-0 space-y-1">
                    <div class="flex justify-between text-slate-500">
                        <span><strong class="uppercase font-mono">${l.old_status}</strong> &rarr; <strong class="uppercase text-blue-600 font-mono">${l.new_status}</strong></span>
                        <span class="text-[10px] text-slate-400 font-mono">${l.created_at}</span>
                    </div>
                    ${l.reason ? `<div class="italic text-slate-600 neu-inset-sm rounded-lg px-2 py-1">"${l.reason}"</div>` : ''}
                    <div class="text-[10px] text-slate-400 font-medium">Petugas: ${l.changed_by}</div>
                </div>
            `;
        });

        container.innerHTML = `
            <div class="neu-inset rounded-2xl p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="text-base font-black text-slate-900 font-tech">${c.name}</h4>
                        <div class="font-mono text-xs text-blue-600 font-bold">${c.customer_code} &bull; IP: ${c.ip_address || '-'}</div>
                    </div>
                    <div>
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase ${c.status === 'aktif' ? 'bg-emerald-100 text-emerald-800' : (c.status === 'cuti' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')}">
                            ${c.status}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-300/60 text-slate-600 font-medium">
                    <div><strong>WhatsApp:</strong> ${c.phone}</div>
                    <div><strong>Paket:</strong> ${c.package_name} (${c.package_speed})</div>
                    <div><strong>Tgl Pasang:</strong> ${c.installation_date}</div>
                    <div><strong>Alamat:</strong> ${c.address}</div>
                </div>
            </div>

            <div class="neu-flat rounded-2xl p-4">
                <h5 class="font-black text-slate-800 mb-2 uppercase text-[11px] tracking-wider font-tech"><i class="fa-solid fa-receipt mr-1 text-blue-600"></i> Riwayat Tagihan</h5>
                <div class="divide-y divide-slate-100">${billsHtml}</div>
            </div>

            <div class="neu-flat rounded-2xl p-4">
                <h5 class="font-black text-slate-800 mb-2 uppercase text-[11px] tracking-wider font-tech"><i class="fa-solid fa-clock-rotate-left mr-1 text-amber-600"></i> Riwayat Status Cuti / Aktif</h5>
                <div>${logsHtml}</div>
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
