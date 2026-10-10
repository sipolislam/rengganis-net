<?php
/**
 * Package Management for Rengganis Net
 */

$pageTitle = 'Paket Layanan WiFi';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// Handle CRUD POST
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
        // Check if package has subscribers
        $count = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE package_id = ?");
        $count->execute([$id]);
        if ($count->fetchColumn() > 0) {
            setFlash('error', 'Paket tidak dapat dihapus karena masih digunakan oleh pelanggan.');
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
            <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight">Daftar Paket Layanan WiFi</h1>
            <p class="text-xs md:text-sm text-slate-500">Kelola varian kecepatan bandwidth dan tarif bulanan internet Rengganis Net</p>
        </div>

        <?php if (isAdmin()): ?>
        <button onclick="openAddPackageModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/25 transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Tambah Paket Baru
        </button>
        <?php endif; ?>
    </div>

    <!-- Package Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <?php foreach ($packages as $pkg): ?>
            <?php $pkgJson = htmlspecialchars(json_encode($pkg), ENT_QUOTES, 'UTF-8'); ?>
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            <?= e($pkg['speed']) ?>
                        </span>
                        <div class="text-[11px] font-semibold text-slate-500 flex items-center gap-1">
                            <i class="fa-solid fa-users text-slate-400"></i> <?= (int)$pkg['subscriber_count'] ?> User
                        </div>
                    </div>

                    <h3 class="text-base font-extrabold text-slate-900"><?= e($pkg['name']) ?></h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed"><?= e($pkg['description'] ?: 'Tidak ada deskripsi.') ?></p>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-slate-400 block uppercase font-bold">Tarif Bulanan</span>
                        <span class="text-lg font-black text-blue-600"><?= formatRupiah($pkg['price']) ?></span>
                    </div>

                    <?php if (isAdmin()): ?>
                    <div class="flex items-center gap-1.5">
                        <button onclick='openEditPackageModal(<?= $pkgJson ?>)' class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs transition-colors" title="Edit Paket">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <?php if ((int)$pkg['subscriber_count'] === 0): ?>
                        <form action="packages.php" method="POST" onsubmit="return confirm('Hapus paket ini?')" class="inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                            <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs transition-colors" title="Hapus Paket">
                                <i class="fa-solid fa-trash"></i>
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
<div id="addPackageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6">
        <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-cubes text-blue-600"></i> Tambah Paket WiFi
        </h3>
        <form action="packages.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Paket</label>
                <input type="text" name="name" required placeholder="Contoh: Paket Gamer 50 Mbps" class="w-full p-2.5 border rounded-xl">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kecepatan</label>
                    <input type="text" name="speed" required placeholder="Contoh: 50 Mbps" class="w-full p-2.5 border rounded-xl">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Harga Bulanan (Rp)</label>
                    <input type="number" name="price" required placeholder="300000" class="w-full p-2.5 border rounded-xl">
                </div>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="2" placeholder="Keterangan peruntukan paket..." class="w-full p-2.5 border rounded-xl"></textarea>
            </div>
            <div class="pt-2 flex gap-2">
                <button type="button" onclick="closeAddPackageModal()" class="w-1/3 py-2.5 border rounded-xl">Batal</button>
                <button type="submit" class="w-2/3 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT PAKET -->
<div id="editPackageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6">
        <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-pen text-blue-600"></i> Edit Paket WiFi
        </h3>
        <form action="packages.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="editPkgId" name="id" value="">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Paket</label>
                <input type="text" id="editPkgName" name="name" required class="w-full p-2.5 border rounded-xl">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kecepatan</label>
                    <input type="text" id="editPkgSpeed" name="speed" required class="w-full p-2.5 border rounded-xl">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Harga Bulanan (Rp)</label>
                    <input type="number" id="editPkgPrice" name="price" required class="w-full p-2.5 border rounded-xl">
                </div>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Deskripsi</label>
                <textarea id="editPkgDesc" name="description" rows="2" class="w-full p-2.5 border rounded-xl"></textarea>
            </div>
            <div class="pt-2 flex gap-2">
                <button type="button" onclick="closeEditPackageModal()" class="w-1/3 py-2.5 border rounded-xl">Batal</button>
                <button type="submit" class="w-2/3 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow">Simpan Perubahan</button>
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
