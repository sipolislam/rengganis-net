<?php
/**
 * Package Management for Rengganis Net
 * Luxury Neo-Skeuomorphic Layout with AdminLTE Sidebar
 */

$pageTitle = 'Paket Bandwidth';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name  = trim($_POST['name'] ?? '');
        $speed = trim($_POST['speed'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $desc  = trim($_POST['description'] ?? '');

        if ($name && $speed && $price > 0) {
            $stmt = $pdo->prepare("INSERT INTO packages (name, speed, price, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $speed, $price, $desc ?: null]);
            setFlash('success', "Paket '{$name}' berhasil ditambahkan.");
            header('Location: packages.php');
            exit;
        } else {
            setFlash('error', 'Nama paket, kecepatan, dan harga wajib diisi!');
        }
    }

    if ($action === 'edit') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $speed = trim($_POST['speed'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $desc  = trim($_POST['description'] ?? '');

        if ($id > 0 && $name && $speed && $price > 0) {
            $stmt = $pdo->prepare("UPDATE packages SET name = ?, speed = ?, price = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $speed, $price, $desc ?: null, $id]);
            setFlash('success', "Paket '{$name}' berhasil diperbarui.");
            header('Location: packages.php');
            exit;
        } else {
            setFlash('error', 'Data paket tidak valid.');
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $count = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE package_id = ?");
        $count->execute([$id]);
        if ($count->fetchColumn() > 0) {
            setFlash('error', 'Paket tidak dapat dihapus karena masih digunakan oleh pelanggan aktif.');
        } else {
            $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Paket berhasil dihapus.');
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
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-tech">Paket Layanan Bandwidth</h1>
            <p class="text-xs text-slate-500 font-medium">Varian kecepatan internet fiber, kuota unlimited, dan tarif bulanan Rengganis Net</p>
        </div>

        <?php if (isAdmin()): ?>
        <button onclick="openAddPackageModal()" class="px-5 py-2.5 rounded-2xl skeuo-btn-primary text-xs font-black shadow-lg flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Tambah Paket Baru
        </button>
        <?php endif; ?>
    </div>

    <!-- Futuristic Hardware-style Package Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($packages as $pkg): ?>
            <?php $pkgJson = htmlspecialchars(json_encode($pkg), ENT_QUOTES, 'UTF-8'); ?>
            <div class="neu-flat rounded-3xl p-6 flex flex-col justify-between relative overflow-hidden transition-all duration-300 hover:-translate-y-1.5 group border border-white/80">
                
                <!-- Metallic Accent Strip -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-400"></div>

                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800 border border-blue-200 shadow-sm font-tech">
                            <span class="led-pulse active-blue"></span> <?= e($pkg['speed']) ?>
                        </span>
                        <div class="text-[11px] font-bold text-slate-500 flex items-center gap-1">
                            <i class="fa-solid fa-users text-slate-400"></i> <?= (int)$pkg['subscriber_count'] ?> User
                        </div>
                    </div>

                    <h3 class="text-base font-black text-slate-900 font-tech"><?= e($pkg['name']) ?></h3>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed font-medium"><?= e($pkg['description'] ?: 'Koneksi unlimited broadband 24/7.') ?></p>
                </div>

                <div class="mt-8 pt-4 border-t border-slate-200/60 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-slate-400 block uppercase font-black tracking-wider">Tarif Bulanan</span>
                        <span class="text-lg font-black text-blue-700 font-mono"><?= formatRupiah($pkg['price']) ?></span>
                    </div>

                    <?php if (isAdmin()): ?>
                    <div class="flex items-center gap-2">
                        <button onclick='openEditPackageModal(<?= $pkgJson ?>)' class="w-8 h-8 rounded-xl skeuo-btn-secondary text-slate-700 flex items-center justify-center" title="Edit Paket">
                            <i class="fa-solid fa-pen text-xs"></i>
                        </button>
                        <?php if ((int)$pkg['subscriber_count'] === 0): ?>
                        <form action="packages.php" method="POST" onsubmit="return confirm('Hapus paket ini?')" class="inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                            <button type="submit" class="w-8 h-8 rounded-xl skeuo-btn-secondary text-rose-600 flex items-center justify-center" title="Hapus Paket">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </form>
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
    <div class="bg-[#edf3f9] rounded-3xl max-w-md w-full shadow-[0_25px_60px_rgba(0,0,0,0.4)] border border-white/80 overflow-hidden p-6">
        <h3 class="text-base font-black text-slate-900 mb-4 flex items-center gap-2 font-tech">
            <i class="fa-solid fa-cubes text-blue-600"></i> Tambah Paket WiFi
        </h3>
        <form action="packages.php" method="POST" class="space-y-3.5 text-xs">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Paket *</label>
                <input type="text" name="name" required placeholder="Contoh: Paket Gaming Ultra 50 Mbps" class="neu-input w-full p-2.5 rounded-xl font-bold">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kecepatan *</label>
                    <input type="text" name="speed" required placeholder="Contoh: 50 Mbps" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Harga Bulanan (Rp) *</label>
                    <input type="number" name="price" required placeholder="300000" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                </div>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Deskripsi Paket</label>
                <textarea name="description" rows="2" placeholder="Keterangan peruntukan perangkat..." class="neu-input w-full p-2.5 rounded-xl font-medium"></textarea>
            </div>
            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeAddPackageModal()" class="w-1/3 py-2.5 rounded-xl skeuo-btn-secondary font-bold">Batal</button>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl skeuo-btn-primary font-black shadow-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT PAKET -->
<div id="editPackageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-md p-4 overflow-y-auto">
    <div class="bg-[#edf3f9] rounded-3xl max-w-md w-full shadow-[0_25px_60px_rgba(0,0,0,0.4)] border border-white/80 overflow-hidden p-6">
        <h3 class="text-base font-black text-slate-900 mb-4 flex items-center gap-2 font-tech">
            <i class="fa-solid fa-pen text-blue-600"></i> Edit Paket WiFi
        </h3>
        <form action="packages.php" method="POST" class="space-y-3.5 text-xs">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="editPkgId" name="id" value="">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Paket *</label>
                <input type="text" id="editPkgName" name="name" required class="neu-input w-full p-2.5 rounded-xl font-bold">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kecepatan *</label>
                    <input type="text" id="editPkgSpeed" name="speed" required class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Harga Bulanan (Rp) *</label>
                    <input type="number" id="editPkgPrice" name="price" required class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                </div>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Deskripsi</label>
                <textarea id="editPkgDesc" name="description" rows="2" class="neu-input w-full p-2.5 rounded-xl font-medium"></textarea>
            </div>
            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeEditPackageModal()" class="w-1/3 py-2.5 rounded-xl skeuo-btn-secondary font-bold">Batal</button>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl skeuo-btn-dark font-black shadow-lg">Simpan</button>
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
    document.getElementById('editPackageModal').classList.remove('hidden');
    document.getElementById('editPackageModal').classList.add('flex');
}
function closeEditPackageModal() {
    document.getElementById('editPackageModal').classList.add('hidden');
    document.getElementById('editPackageModal').classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
