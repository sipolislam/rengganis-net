<?php
/**
 * Package Management for Rengganis Net (T-010 / Setup)
 * Luxury Neo-Skeuomorphic Layout with AdminLTE Sidebar
 */

$pageTitle = 'Paket Bandwidth';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAdmin()) {
        denyAccess('Hanya Admin Pengelola yang dapat menambah, mengubah, atau menghapus paket bandwidth (403 Forbidden).');
    }
    validateCsrfOrDie('packages.php');
    $action = $_POST['action'] ?? '';

    // 1. CREATE PACKAGE
    if ($action === 'create') {
        $name     = trim($_POST['name'] ?? '');
        $speed    = trim($_POST['speed'] ?? '');
        $price    = (float)($_POST['price'] ?? 0);
        $desc     = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name && $speed && $price > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO packages (name, speed, price, description, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$name, $speed, $price, $desc ?: null, $isActive]);
            setFlash('success', "Paket '{$name}' ({$speed}) berhasil ditambahkan ke katalog layanan.");
            header('Location: packages.php');
            exit;
        } else {
            setFlash('error', 'Nama paket, kecepatan, dan harga bulanan wajib diisi!');
        }
    }

    // 2. EDIT PACKAGE
    if ($action === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $speed    = trim($_POST['speed'] ?? '');
        $price    = (float)($_POST['price'] ?? 0);
        $desc     = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id > 0 && $name && $speed && $price > 0) {
            $stmt = $pdo->prepare("
                UPDATE packages 
                SET name = ?, speed = ?, price = ?, description = ?, is_active = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$name, $speed, $price, $desc ?: null, $isActive, $id]);
            setFlash('success', "Paket '{$name}' berhasil diperbarui.");
            header('Location: packages.php');
            exit;
        } else {
            setFlash('error', 'Data paket tidak valid. Pastikan semua kolom terisi dengan benar.');
        }
    }

    // 3. TOGGLE ACTIVE STATUS
    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE packages SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Status aktifasi paket berhasil diperbarui.');
        }
        header('Location: packages.php');
        exit;
    }

    // 4. DELETE PACKAGE
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $count = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE package_id = ?");
        $count->execute([$id]);
        $subscribers = (int)$count->fetchColumn();
        if ($subscribers > 0) {
            setFlash('error', "Paket tidak dapat dihapus karena masih digunakan oleh {$subscribers} pelanggan. Silakan nonaktifkan paket jika tidak ingin ditawarkan lagi.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Paket berhasil dihapus dari sistem.');
        }
        header('Location: packages.php');
        exit;
    }
}

// Fetch packages with subscriber count
$packages = $pdo->query("
    SELECT p.*, COUNT(c.id) AS subscriber_count
    FROM packages p
    LEFT JOIN customers c ON p.id = c.package_id
    GROUP BY p.id
    ORDER BY p.price ASC
")->fetchAll();

// Summary stats calculation
$totalPackages = count($packages);
$activePackages = 0;
$totalSubscribers = 0;
$totalPriceSum = 0;
foreach ($packages as $pkg) {
    if ((int)$pkg['is_active'] === 1) $activePackages++;
    $totalSubscribers += (int)$pkg['subscriber_count'];
    $totalPriceSum += (float)$pkg['price'];
}
$avgPrice = $totalPackages > 0 ? ($totalPriceSum / $totalPackages) : 0;
?>

<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-tech">Paket Layanan Bandwidth</h1>
            <p class="text-xs text-slate-500 font-medium">Katalog varian kecepatan internet fiber, kuota unlimited, dan tarif bulanan Rengganis Net</p>
        </div>

        <?php if (isAdmin()): ?>
        <button onclick="openAddPackageModal()" class="px-5 py-2.5 rounded-2xl skeuo-btn-primary text-xs font-black shadow-lg flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Tambah Paket Baru
        </button>
        <?php endif; ?>
    </div>

    <!-- 4-Column Neo-Skeuomorphic Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Card 1: Total Packages -->
        <div class="neu-flat rounded-3xl p-5 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Varian Paket</span>
                <div class="text-2xl font-black text-slate-900 mt-1 font-mono"><?= $totalPackages ?> <span class="text-xs font-bold text-slate-400">Paket</span></div>
                <div class="text-[11px] text-slate-500 mt-1 font-semibold">Tersedia di sistem</div>
            </div>
            <div class="w-12 h-12 rounded-2xl neu-inset flex items-center justify-center text-blue-600 text-lg shadow-inner">
                <i class="fa-solid fa-cubes"></i>
            </div>
        </div>

        <!-- Card 2: Active Packages -->
        <div class="neu-flat rounded-3xl p-5 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 flex items-center gap-1.5">
                    <span class="led-pulse active-green"></span> Paket Aktif
                </span>
                <div class="text-2xl font-black text-emerald-700 mt-1 font-mono"><?= $activePackages ?> <span class="text-xs font-bold text-slate-400">Siap Jual</span></div>
                <div class="text-[11px] text-emerald-600 mt-1 font-semibold">Bisa dipilih pelanggan baru</div>
            </div>
            <div class="w-12 h-12 rounded-2xl neu-inset flex items-center justify-center text-emerald-600 text-lg shadow-inner">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 3: Total Subscribers -->
        <div class="neu-flat rounded-3xl p-5 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600">Pelanggan Terhubung</span>
                <div class="text-2xl font-black text-indigo-700 mt-1 font-mono"><?= $totalSubscribers ?> <span class="text-xs font-bold text-slate-400">User</span></div>
                <div class="text-[11px] text-slate-500 mt-1 font-semibold">Total pelanggan aktif & cuti</div>
            </div>
            <div class="w-12 h-12 rounded-2xl neu-inset flex items-center justify-center text-indigo-600 text-lg shadow-inner">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <!-- Card 4: Average Monthly Price -->
        <div class="neu-flat rounded-3xl p-5 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Rata-rata Tarif</span>
                <div class="text-xl font-black text-slate-900 mt-1 font-mono"><?= formatRupiah($avgPrice) ?></div>
                <div class="text-[11px] text-slate-500 mt-1 font-semibold">Per bulan / pelanggan</div>
            </div>
            <div class="w-12 h-12 rounded-2xl neu-inset flex items-center justify-center text-amber-600 text-lg shadow-inner">
                <i class="fa-solid fa-tag"></i>
            </div>
        </div>

    </div>

    <!-- Futuristic Hardware-style Package Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($packages as $pkg): ?>
            <?php 
            $isActive = ((int)$pkg['is_active'] === 1);
            $pkgJson = htmlspecialchars(json_encode($pkg), ENT_QUOTES, 'UTF-8'); 
            ?>
            <div class="neu-flat rounded-3xl p-6 flex flex-col justify-between relative overflow-hidden transition-all duration-300 hover:-translate-y-1.5 group border <?= $isActive ? 'border-white/80' : 'border-slate-300/60 opacity-80' ?>">
                
                <!-- Metallic Accent Strip -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r <?= $isActive ? 'from-blue-600 via-indigo-600 to-cyan-400' : 'from-slate-400 via-slate-500 to-slate-400' ?>"></div>

                <div>
                    <!-- Speed & Status Badges -->
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black <?= $isActive ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-200 text-slate-600 border border-slate-300' ?> shadow-sm font-tech">
                            <span class="led-pulse <?= $isActive ? 'active-blue' : 'active-slate' ?>"></span> <?= e($pkg['speed']) ?>
                        </span>
                        
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider <?= $isActive ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-200 text-slate-600' ?>">
                                <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </div>
                    </div>

                    <h3 class="text-base font-black text-slate-900 font-tech"><?= e($pkg['name']) ?></h3>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed font-medium"><?= e($pkg['description'] ?: 'Koneksi internet unlimited broadband 24 jam nonstop.') ?></p>
                    
                    <div class="mt-4 flex items-center gap-1 text-[11px] font-bold text-slate-500">
                        <i class="fa-solid fa-users text-slate-400"></i> <?= (int)$pkg['subscriber_count'] ?> Pelanggan Terdaftar
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-200/60 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-slate-400 block uppercase font-black tracking-wider">Tarif Bulanan</span>
                        <span class="text-lg font-black text-blue-700 font-mono"><?= formatRupiah($pkg['price']) ?></span>
                    </div>

                    <?php if (isAdmin()): ?>
                    <div class="flex items-center gap-1.5">
                        <!-- Toggle Active Form Button -->
                        <form action="packages.php" method="POST" class="inline" title="<?= $isActive ? 'Nonaktifkan Paket' : 'Aktifkan Paket' ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                            <button type="submit" class="w-8 h-8 rounded-xl skeuo-btn-secondary <?= $isActive ? 'text-amber-600' : 'text-emerald-600' ?> flex items-center justify-center">
                                <i class="fa-solid <?= $isActive ? 'fa-pause' : 'fa-play' ?> text-xs"></i>
                            </button>
                        </form>

                        <!-- Edit Button -->
                        <button onclick='openEditPackageModal(<?= $pkgJson ?>)' class="w-8 h-8 rounded-xl skeuo-btn-secondary text-slate-700 flex items-center justify-center" title="Edit Paket">
                            <i class="fa-solid fa-pen text-xs"></i>
                        </button>

                        <!-- Delete Button (Only if 0 subscribers) -->
                        <?php if ((int)$pkg['subscriber_count'] === 0): ?>
                        <form action="packages.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket \'<?= e($pkg['name']) ?>\'? Tindakan ini tidak dapat dibatalkan.')" class="inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                            <button type="submit" class="w-8 h-8 rounded-xl skeuo-btn-secondary text-rose-600 flex items-center justify-center" title="Hapus Paket">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-300 flex items-center justify-center cursor-not-allowed" title="Paket tidak dapat dihapus karena digunakan oleh <?= (int)$pkg['subscriber_count'] ?> pelanggan">
                            <i class="fa-solid fa-lock text-xs"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- MODAL TAMBAH PAKET -->
<div id="addPackageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-md p-4 overflow-y-auto">
    <div class="bg-[#edf3f9] rounded-3xl max-w-md w-full shadow-[0_25px_60px_rgba(0,0,0,0.4)] border border-white/80 overflow-hidden p-6 animate-scale-up">
        <div class="flex items-center justify-between mb-4 border-b border-slate-200/60 pb-3">
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2 font-tech">
                <i class="fa-solid fa-cubes text-blue-600"></i> Tambah Paket WiFi
            </h3>
            <button onclick="closeAddPackageModal()" type="button" class="w-8 h-8 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-500 flex items-center justify-center">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <form action="packages.php" method="POST" class="space-y-3.5 text-xs">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Paket *</label>
                <input type="text" name="name" required placeholder="Contoh: Paket Gaming Ultra 50 Mbps" class="neu-input w-full p-2.5 rounded-xl font-bold">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kecepatan Bandwidth *</label>
                    <input type="text" name="speed" required placeholder="Contoh: 50 Mbps" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tarif Bulanan (Rp) *</label>
                    <input type="number" name="price" required min="10000" step="5000" placeholder="300000" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                </div>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Deskripsi Paket</label>
                <textarea name="description" rows="2" placeholder="Keterangan peruntukan pelanggan atau perangkat..." class="neu-input w-full p-2.5 rounded-xl font-medium"></textarea>
            </div>
            <div class="neu-inset-sm rounded-xl p-3">
                <label class="flex items-center gap-2 font-bold text-slate-800 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                    <span>Paket Aktif (Tersedia untuk pendaftaran pelanggan baru)</span>
                </label>
            </div>
            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeAddPackageModal()" class="w-1/3 py-2.5 rounded-xl skeuo-btn-secondary font-bold">Batal</button>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl skeuo-btn-primary font-black shadow-lg">Simpan Paket</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT PAKET -->
<div id="editPackageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-md p-4 overflow-y-auto">
    <div class="bg-[#edf3f9] rounded-3xl max-w-md w-full shadow-[0_25px_60px_rgba(0,0,0,0.4)] border border-white/80 overflow-hidden p-6 animate-scale-up">
        <div class="flex items-center justify-between mb-4 border-b border-slate-200/60 pb-3">
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2 font-tech">
                <i class="fa-solid fa-pen text-blue-600"></i> Edit Paket WiFi
            </h3>
            <button onclick="closeEditPackageModal()" type="button" class="w-8 h-8 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-500 flex items-center justify-center">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <form action="packages.php" method="POST" class="space-y-3.5 text-xs">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="editPkgId" name="id" value="">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Paket *</label>
                <input type="text" id="editPkgName" name="name" required class="neu-input w-full p-2.5 rounded-xl font-bold">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kecepatan Bandwidth *</label>
                    <input type="text" id="editPkgSpeed" name="speed" required class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tarif Bulanan (Rp) *</label>
                    <input type="number" id="editPkgPrice" name="price" required min="10000" step="5000" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                </div>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Deskripsi Paket</label>
                <textarea id="editPkgDesc" name="description" rows="2" class="neu-input w-full p-2.5 rounded-xl font-medium"></textarea>
            </div>
            <div class="neu-inset-sm rounded-xl p-3">
                <label class="flex items-center gap-2 font-bold text-slate-800 cursor-pointer">
                    <input type="checkbox" id="editPkgActive" name="is_active" value="1" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                    <span>Paket Aktif (Tersedia untuk pendaftaran pelanggan baru)</span>
                </label>
            </div>
            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeEditPackageModal()" class="w-1/3 py-2.5 rounded-xl skeuo-btn-secondary font-bold">Batal</button>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl skeuo-btn-dark font-black shadow-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddPackageModal() {
    document.getElementById('addPackageModal').classList.remove('hidden');
    document.getElementById('addPackageModal').classList.add('flex');
}
function closeAddPackageModal() {
    document.getElementById('addPackageModal').classList.add('hidden');
    document.getElementById('addPackageModal').classList.remove('flex');
}
function openEditPackageModal(pkg) {
    document.getElementById('editPkgId').value = pkg.id;
    document.getElementById('editPkgName').value = pkg.name;
    document.getElementById('editPkgSpeed').value = pkg.speed;
    document.getElementById('editPkgPrice').value = pkg.price;
    document.getElementById('editPkgDesc').value = pkg.description || '';
    document.getElementById('editPkgActive').checked = (Number(pkg.is_active) === 1);
    document.getElementById('editPackageModal').classList.remove('hidden');
    document.getElementById('editPackageModal').classList.add('flex');
}
function closeEditPackageModal() {
    document.getElementById('editPackageModal').classList.add('hidden');
    document.getElementById('editPackageModal').classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
