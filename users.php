<?php
/**
 * User & Role Management for Rengganis Net
 * Luxury Neo-Skeuomorphic Layout with AdminLTE Sidebar
 */

$pageTitle = 'Manajemen Pengguna';
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pdo = getDB();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie('users.php');
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = trim($_POST['role'] ?? 'teknisi');
        $role     = in_array($role, ['admin', 'kolektor', 'teknisi']) ? $role : 'teknisi';
        $phone    = trim($_POST['phone'] ?? '');

        if ($username && $password && $name) {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                setFlash('error', "Username '{$username}' sudah digunakan.");
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email, role, phone) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hash, $name, $email ?: null, $role, $phone ?: null]);
                setFlash('success', "Pengguna {$name} ({$username}) berhasil dibuat dengan peran " . ucfirst($role) . ".");
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
        $email    = trim($_POST['email'] ?? '');
        $role     = trim($_POST['role'] ?? 'teknisi');
        $role     = in_array($role, ['admin', 'kolektor', 'teknisi']) ? $role : 'teknisi';
        $phone    = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($id > 0 && $name) {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email ?: null, $role, $phone ?: null, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $email ?: null, $role, $phone ?: null, $id]);
            }
            setFlash('success', "Data pengguna {$name} berhasil diperbarui.");
        }
        header('Location: users.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$currentUser['id']) {
            setFlash('error', 'Anda tidak dapat menghapus akun Anda sendiri!');
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
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-tech">Manajemen Akun & Otorisasi</h1>
            <p class="text-xs text-slate-500 font-medium">Pengelolaan hak akses Admin Pengelola dan Teknisi Lapangan Rengganis Net</p>
        </div>

        <button onclick="openAddUserModal()" class="px-5 py-2.5 rounded-2xl skeuo-btn-primary text-xs font-black shadow-lg flex items-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Tambah Pengguna Baru
        </button>
    </div>

    <!-- Users Table (Tactile Neumorphic Surface) -->
    <div class="neu-flat rounded-3xl overflow-hidden border border-white/80 shadow-md">
        <!-- Desktop Table View (hidden on mobile < md) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/70 text-slate-400 uppercase font-black text-[10px] border-b">
                        <th class="py-3.5 px-5">Nama & Username</th>
                        <th class="py-3.5 px-4">Hak Akses (Role)</th>
                        <th class="py-3.5 px-4">Kontak WhatsApp</th>
                        <th class="py-3.5 px-4">Tgl Terdaftar</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($users as $u): ?>
                        <?php $uJson = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8'); ?>
                        <tr class="hover:bg-white/60 transition-colors">
                            <td class="py-3.5 px-5">
                                <div class="font-black text-slate-900"><?= e($u['name']) ?></div>
                                <div class="font-mono text-[11px] text-blue-600 font-bold">@<?= e($u['username']) ?></div>
                                <?php if (!empty($u['email'])): ?>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5"><i class="fa-regular fa-envelope text-[9px] mr-1"></i><?= e($u['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 shadow-sm border border-amber-300">
                                        <i class="fa-solid fa-crown text-[9px]"></i> Admin Pengelola
                                    </span>
                                <?php elseif ($u['role'] === 'kolektor'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-cyan-100 text-cyan-800 border border-cyan-300 shadow-sm">
                                        <i class="fa-solid fa-cash-register text-[9px]"></i> Kolektor / Kasir
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                                        <i class="fa-solid fa-wrench text-[9px]"></i> Teknisi Lapangan
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 font-mono font-bold">
                                <?= e($u['phone'] ?: '-') ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 font-medium">
                                <?= formatDate($u['created_at']) ?>
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button onclick='openEditUserModal(<?= $uJson ?>)' class="w-8 h-8 rounded-xl skeuo-btn-secondary text-slate-700 flex items-center justify-center" title="Edit Akun">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    <?php if ($u['id'] !== $currentUser['id']): ?>
                                    <form action="users.php" method="POST" onsubmit="return confirm('Hapus akun <?= e($u['name']) ?>?')" class="inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="w-8 h-8 rounded-xl skeuo-btn-secondary text-rose-600 flex items-center justify-center" title="Hapus Akun">
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

        <!-- Mobile Field Cards View (visible on mobile < md) -->
        <div class="p-3 sm:p-4 space-y-3.5 block md:hidden">
            <?php foreach ($users as $u): ?>
                <?php 
                $uJson = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8'); 
                $dispPhone = $u['phone'] ? formatDisplayPhone($u['phone']) : '-';
                $waNumber = $u['phone'] ? formatWhatsAppNumber($u['phone']) : '';
                ?>
                <div class="p-4 rounded-2xl neu-inset bg-white/75 border border-white shadow-sm space-y-3 mobile-card-shadow">
                    <!-- Top: Name, Username & Role Badge -->
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                        <div>
                            <h4 class="font-black text-sm text-slate-900 tracking-tight"><?= e($u['name']) ?></h4>
                            <span class="font-mono text-blue-600 font-bold text-xs tracking-tight">@<?= e($u['username']) ?></span>
                            <?php if (!empty($u['email'])): ?>
                                <div class="text-[10px] text-slate-500 font-mono mt-0.5"><i class="fa-regular fa-envelope mr-1"></i><?= e($u['email']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 shadow-sm border border-amber-300">
                                    <i class="fa-solid fa-crown text-[8px]"></i> Admin
                                </span>
                            <?php elseif ($u['role'] === 'kolektor'): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-cyan-100 text-cyan-800 border border-cyan-300 shadow-sm">
                                    <i class="fa-solid fa-cash-register text-[8px]"></i> Kasir
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                                    <i class="fa-solid fa-wrench text-[8px]"></i> Teknisi
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contact & Created Date -->
                    <div class="flex items-center justify-between text-xs">
                        <?php if ($waNumber): ?>
                            <a href="https://wa.me/<?= $waNumber ?>" target="_blank" class="text-emerald-700 hover:text-emerald-800 font-bold inline-flex items-center gap-1.5 touch-target-mobile">
                                <i class="fa-brands fa-whatsapp text-base"></i> <span><?= e($dispPhone) ?></span>
                            </a>
                        <?php else: ?>
                            <span class="text-slate-400 font-mono text-[11px]"><i class="fa-solid fa-phone text-slate-300 mr-1"></i> Tidak ada no. HP</span>
                        <?php endif; ?>
                        <span class="text-slate-500 font-medium text-[11px]">
                            <i class="fa-regular fa-calendar text-slate-400 mr-1"></i><?= formatDate($u['created_at']) ?>
                        </span>
                    </div>

                    <!-- Touch Actions -->
                    <div class="pt-2 border-t border-slate-200/60 flex items-center gap-2">
                        <button onclick='openEditUserModal(<?= $uJson ?>)' class="flex-1 py-2.5 px-3 rounded-xl skeuo-btn-secondary text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5 touch-target-mobile active:scale-95 transition-transform" title="Edit Akun">
                            <i class="fa-solid fa-pen text-xs"></i> <span>Edit Akun</span>
                        </button>
                        <?php if ($u['id'] !== $currentUser['id']): ?>
                        <form action="users.php" method="POST" onsubmit="return confirm('Hapus akun <?= e($u['name']) ?>?')" class="flex-1">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="w-full py-2.5 px-3 rounded-xl skeuo-btn-secondary text-rose-600 font-bold text-xs flex items-center justify-center gap-1.5 touch-target-mobile active:scale-95 transition-transform" title="Hapus Akun">
                                <i class="fa-solid fa-trash text-xs"></i> <span>Hapus</span>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- MODAL TAMBAH USER -->
<div id="addUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-md p-4 overflow-y-auto">
    <div class="bg-[#edf3f9] rounded-3xl max-w-md w-full shadow-[0_25px_60px_rgba(0,0,0,0.4)] border border-white/80 overflow-hidden p-6 text-xs">
        <h3 class="text-base font-black text-slate-900 mb-4 flex items-center gap-2 font-tech">
            <i class="fa-solid fa-user-plus text-blue-600"></i> Tambah Pengguna Baru
        </h3>
        <form action="users.php" method="POST" class="space-y-3.5">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                <input type="text" name="name" required placeholder="Contoh: Budi Santoso" class="neu-input w-full p-2.5 rounded-xl font-bold">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Email Akun (untuk Reset OTP) *</label>
                <input type="email" name="email" required placeholder="user@rengganis.net" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Username *</label>
                    <input type="text" name="username" required placeholder="budisantoso" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Password *</label>
                    <input type="password" name="password" required placeholder="Min 6 karakter" class="neu-input w-full p-2.5 rounded-xl font-bold">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Role / Peran *</label>
                    <select name="role" required class="neu-input w-full p-2.5 rounded-xl font-bold">
                        <option value="teknisi">Teknisi Lapangan</option>
                        <option value="kolektor">Kolektor / Kasir</option>
                        <option value="admin">Admin Pengelola</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">No. WhatsApp</label>
                    <input type="text" name="phone" placeholder="0812345678" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                </div>
            </div>
            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeAddUserModal()" class="w-1/3 py-2.5 rounded-xl skeuo-btn-secondary font-bold">Batal</button>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl skeuo-btn-primary font-black shadow-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT USER -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-md p-4 overflow-y-auto">
    <div class="bg-[#edf3f9] rounded-3xl max-w-md w-full shadow-[0_25px_60px_rgba(0,0,0,0.4)] border border-white/80 overflow-hidden p-6 text-xs">
        <h3 class="text-base font-black text-slate-900 mb-4 flex items-center gap-2 font-tech">
            <i class="fa-solid fa-pen text-blue-600"></i> Edit Pengguna
        </h3>
        <form action="users.php" method="POST" class="space-y-3.5">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="editUserId" name="id" value="">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                <input type="text" id="editUserName" name="name" required class="neu-input w-full p-2.5 rounded-xl font-bold">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Email Akun (untuk Reset OTP)</label>
                <input type="email" id="editUserEmail" name="email" placeholder="user@rengganis.net" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Role / Peran *</label>
                    <select id="editUserRole" name="role" required class="neu-input w-full p-2.5 rounded-xl font-bold">
                        <option value="teknisi">Teknisi Lapangan</option>
                        <option value="kolektor">Kolektor / Kasir</option>
                        <option value="admin">Admin Pengelola</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">No. WhatsApp</label>
                    <input type="text" id="editUserPhone" name="phone" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                </div>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Ubah Password (Kosongkan jika tetap)</label>
                <input type="password" name="password" placeholder="Isi password baru..." class="neu-input w-full p-2.5 rounded-xl">
            </div>
            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeEditUserModal()" class="w-1/3 py-2.5 rounded-xl skeuo-btn-secondary font-bold">Batal</button>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl skeuo-btn-dark font-black shadow-lg">Simpan</button>
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
    document.getElementById('editUserEmail').value = u.email || '';
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
