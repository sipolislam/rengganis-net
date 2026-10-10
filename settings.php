<?php
/**
 * System Settings for Rengganis Net
 */

$pageTitle = 'Pengaturan Sistem';
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsToUpdate = [
        'company_name'     => trim($_POST['company_name'] ?? 'Rengganis Net'),
        'company_tagline'  => trim($_POST['company_tagline'] ?? ''),
        'company_phone'    => trim($_POST['company_phone'] ?? ''),
        'company_email'    => trim($_POST['company_email'] ?? ''),
        'company_address'  => trim($_POST['company_address'] ?? ''),
        'billing_due_day'  => (string)(int)($_POST['billing_due_day'] ?? 10),
        'receipt_footer'   => trim($_POST['receipt_footer'] ?? ''),
        'bank_info'        => trim($_POST['bank_info'] ?? '')
    ];

    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settingsToUpdate as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    setFlash('success', 'Pengaturan sistem berhasil disimpan.');
    header('Location: settings.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div>
        <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight">Pengaturan Rengganis Net</h1>
        <p class="text-xs md:text-sm text-slate-500">Konfigurasi profil usaha, informasi struk pembayaran, dan ketentuan billing</p>
    </div>

    <!-- Settings Form -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form action="settings.php" method="POST" class="space-y-6 text-xs">
            
            <!-- Section 1: Profil Usaha -->
            <div class="space-y-4">
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-2">
                    <i class="fa-solid fa-building text-blue-600"></i> Identitas Rengganis Net
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Usaha / Provider *</label>
                        <input type="text" name="company_name" required value="<?= e(getSetting($pdo, 'company_name', 'Rengganis Net')) ?>" class="w-full p-2.5 rounded-xl border border-slate-300 font-semibold focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Tagline Slogan</label>
                        <input type="text" name="company_tagline" value="<?= e(getSetting($pdo, 'company_tagline', 'Solusi Internet Cepat & Hemat')) ?>" class="w-full p-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">No. Kontak / WhatsApp *</label>
                        <input type="text" name="company_phone" required value="<?= e(getSetting($pdo, 'company_phone', '0812-3456-7890')) ?>" class="w-full p-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Email Resmi</label>
                        <input type="email" name="company_email" value="<?= e(getSetting($pdo, 'company_email', 'admin@rengganis.net')) ?>" class="w-full p-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Alamat Kantor / Basecamp</label>
                    <textarea name="company_address" rows="2" class="w-full p-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500"><?= e(getSetting($pdo, 'company_address', 'Jl. Raya Rengganis No. 45, Sumber Malang')) ?></textarea>
                </div>
            </div>

            <!-- Section 2: Ketentuan Billing & Cetak Struk -->
            <div class="space-y-4 pt-4 border-t border-slate-100">
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-2">
                    <i class="fa-solid fa-receipt text-emerald-600"></i> Struk Thermal & Penagihan
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Jatuh Tempo Tiap Bulan</label>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500 font-semibold">Tanggal:</span>
                            <input type="number" min="1" max="28" name="billing_due_day" value="<?= e(getSetting($pdo, 'billing_due_day', '10')) ?>" class="w-24 p-2.5 rounded-xl border border-slate-300 font-bold focus:ring-2 focus:ring-blue-500 text-center">
                            <span class="text-slate-500">setiap bulan</span>
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Lebar Kertas Printer Thermal Default</label>
                        <select class="w-full p-2.5 rounded-xl border border-slate-300 font-semibold bg-white" disabled>
                            <option selected>58mm (Standard ESC/POS Bluetooth Mini)</option>
                            <option>80mm (Desktop Thermal)</option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Kompatibel otomatis dengan printer thermal Bluetooth 58mm & 80mm.</p>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Pesan Footer Struk Pembayaran</label>
                    <textarea name="receipt_footer" rows="2" class="w-full p-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500"><?= e(getSetting($pdo, 'receipt_footer', "Terima kasih atas pembayaran Anda.\nSimpan struk ini sebagai bukti pembayaran yang sah.")) ?></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Informasi Rekening Transfer / Pembayaran Digital</label>
                    <textarea name="bank_info" rows="3" class="w-full p-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 font-mono"><?= e(getSetting($pdo, 'bank_info', "BCA: 123-456-7890 (a.n Rengganis Net)\nBRI: 0987-01-023456-50-1 (a.n Rengganis Net)\nDANA/Gopay: 0812-3456-7890")) ?></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-md shadow-blue-500/20 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan
                </button>
            </div>

        </form>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
