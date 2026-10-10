<?php
/**
 * User & Role Management for Rengganis Net
 * FR-01: Admin & Teknisi accounts
 */

$pageTitle = 'Manajemen Pengguna';
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pdo = getDB();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $name     = trim($_POST['name'] ?? '');
        $role     = trim($_POST['role'] ?? 'teknisi');
        $phone    = trim($_POST['phone'] ?? '');

        if ($username && $password && $name) {
            // Check existing
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                setFlash('error', "Username '{$username}' sudah digunakan.");
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, phone) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hash, $name, $role, $phone ?: null]);
                setFlash('success', "Pengguna {$name} ({$username}) berhasil dibuat.");
            }
        } else {
            setFlash('error', 'Semua kolom wajib diisi!');
        }
        header('Location: users.php');
        exit;
    }

    if ($action === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $role     = trim($_POST['role'] ?? 'teknisi');
        $phone    = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($id > 0 && $name) {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, role = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $role, $phone ?: null, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, role = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $role, $phone ?: null, $id]);
            }
            setFlash('success', "Data pengguna {$name} berhasil diperbarui.");
        }
        header('Location: users.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$currentUser['id']) {
            setFlash('error', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang login!');
        } elseif ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Pengguna berhasil dihapus.');
        }
        header('Location: users.php');
        exit;
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, name ASC")->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight">Manajemen Pengguna & Hak Akses</h1>
            <p class="text-xs md:text-sm text-slate-500">Kelola akun Admin Pengelola dan Teknisi Lapangan Rengganis Net</p>
        </div>

        <button onclick="openAddUserModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/25 transition-all flex items-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Tambah Pengguna Baru
        </button>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] border-b">
                        <th class="py-3 px-4">Nama & Username</th>
                        <th class="py-3 px-4">Hak Akses (Role)</th>
                        <th class="py-3 px-4">No. HP / WhatsApp</th>
                        <th class="py-3 px-4">Tgl Terdaftar</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($users as $u): ?>
                        <?php $uJson = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8'); ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900"><?= e($u['name']) ?></div>
                                <div class="font-mono text-[11px] text-slate-500">@<?= e($u['username']) ?></div>
                            </td>
                            <td class="py-3 px-4">
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="fa-solid fa-crown text-[10px] text-amber-500"></i> Admin Pengelola
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-wrench text-[10px]"></i> Teknisi Lapangan
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-slate-600 font-mono">
                                <?= e($u['phone'] ?: '-') ?>
                            </td>
                            <td class="py-3 px-4 text-slate-500">
                                <?= formatDate($u['created_at']) ?>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button onclick='openEditUserModal(<?= $uJson ?>)' class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors" title="Edit Akun">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    <?php if ($u['id'] !== $currentUser['id']): ?>
                                    <form action="users.php" method="POST" onsubmit="return confirm('Hapus akun <?= e($u['name']) ?>?')" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 transition-colors" title="Hapus Akun">
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
    </div>

</div>

<!-- MODAL TAMBAH USER -->
<div id="addUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 text-xs">
        <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-user-plus text-blue-600"></i> Tambah Pengguna Baru
        </h3>
        <form action="users.php" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                <input type="text" name="name" required placeholder="Contoh: Budi Santoso" class="w-full p-2.5 border rounded-xl">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Username *</label>
                    <input type="text" name="username" required placeholder="budisantoso" class="w-full p-2.5 border rounded-xl font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Password *</label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full p-2.5 border rounded-xl">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Role / Peran *</label>
                    <select name="role" required class="w-full p-2.5 border rounded-xl font-semibold bg-white">
                        <option value="teknisi">Teknisi Lapangan</option>
                        <option value="admin">Admin Pengelola</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">No. WhatsApp</label>
                    <input type="text" name="phone" placeholder="0812345678" class="w-full p-2.5 border rounded-xl">
                </div>
            </div>
            <div class="pt-2 flex gap-2">
                <button type="button" onclick="closeAddUserModal()" class="w-1/3 py-2.5 border rounded-xl">Batal</button>
                <button type="submit" class="w-2/3 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT USER -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 text-xs">
        <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-pen text-blue-600"></i> Edit Pengguna
        </h3>
        <form action="users.php" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="editUserId" name="id" value="">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                <input type="text" id="editUserName" name="name" required class="w-full p-2.5 border rounded-xl">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Role / Peran *</label>
                    <select id="editUserRole" name="role" required class="w-full p-2.5 border rounded-xl font-semibold bg-white">
                        <option value="teknisi">Teknisi Lapangan</option>
                        <option value="admin">Admin Pengelola</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">No. WhatsApp</label>
                    <input type="text" id="editUserPhone" name="phone" class="w-full p-2.5 border rounded-xl">
                </div>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Ubah Password (Kosongkan jika tidak diubah)</label>
                <input type="password" name="password" placeholder="Isi password baru..." class="w-full p-2.5 border rounded-xl">
            </div>
            <div class="pt-2 flex gap-2">
                <button type="button" onclick="closeEditUserModal()" class="w-1/3 py-2.5 border rounded-xl">Batal</button>
                <button type="submit" class="w-2/3 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('addUserModal').classList.remove('hidden');
    document.getElementById('addUserModal').classList.add('flex');
}
function closeAddUserModal() {
    document.getElementById('addUserModal').classList.add('hidden');
    document.getElementById('addUserModal').classList.remove('flex');
}
function openEditUserModal(u) {
    document.getElementById('editUserId').value = u.id;
    document.getElementById('editUserName').value = u.name;
    document.getElementById('editUserRole').value = u.role;
    document.getElementById('editUserPhone').value = u.phone || '';
    document.getElementById('editUserModal').classList.remove('hidden');
    document.getElementById('editUserModal').classList.add('flex');
}
function closeEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
    document.getElementById('editUserModal').classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
