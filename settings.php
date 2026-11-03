<?php
/**
 * System Settings for Rengganis Net
 * Luxury Neo-Skeuomorphic Layout with AdminLTE Sidebar
 */

$pageTitle = 'Pengaturan Sistem';
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie('settings.php');
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

    setFlash('success', 'Konfigurasi sistem berhasil diperbarui.');
    header('Location: settings.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl space-y-6">

    <!-- Header -->
    <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-tech">Konfigurasi Rengganis Net</h1>
        <p class="text-xs text-slate-500 font-medium">Profil penyedia layanan, ketentuan tanggal jatuh tempo, dan footer struk thermal</p>
    </div>

    <!-- Settings Form (Neumorphic Card) -->
    <div class="neu-flat rounded-3xl p-6 sm:p-8">
        <form action="settings.php" method="POST" class="space-y-6 text-xs">
            <?= csrfField() ?>
            
            <!-- Section 1: Profil Usaha -->
            <div class="space-y-4">
                <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 border-b border-slate-200/60 pb-3 font-tech">
                    <i class="fa-solid fa-building text-blue-600"></i> Identitas Rengganis Net
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Usaha / Provider *</label>
                        <input type="text" name="company_name" required value="<?= e(getSetting($pdo, 'company_name', 'Rengganis Net')) ?>" class="neu-input w-full p-2.5 rounded-xl font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tagline Slogan</label>
                        <input type="text" name="company_tagline" value="<?= e(getSetting($pdo, 'company_tagline', 'Solusi Internet Cepat & Hemat')) ?>" class="neu-input w-full p-2.5 rounded-xl font-medium">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">No. Kontak / WhatsApp CS *</label>
                        <input type="text" name="company_phone" required value="<?= e(getSetting($pdo, 'company_phone', '0812-3456-7890')) ?>" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Resmi</label>
                        <input type="email" name="company_email" value="<?= e(getSetting($pdo, 'company_email', 'admin@rengganis.net')) ?>" class="neu-input w-full p-2.5 rounded-xl font-mono">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Kantor / Basecamp</label>
                    <textarea name="company_address" rows="2" class="neu-input w-full p-2.5 rounded-xl font-medium"><?= e(getSetting($pdo, 'company_address', 'Jl. Raya Rengganis No. 45, Sumber Malang')) ?></textarea>
                </div>
            </div>

            <!-- Section 2: Ketentuan Billing & Cetak Struk -->
            <div class="space-y-4 pt-4 border-t border-slate-200/60">
                <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 border-b border-slate-200/60 pb-3 font-tech">
                    <i class="fa-solid fa-receipt text-emerald-600"></i> Struk Thermal & Penagihan
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Jatuh Tempo Bulanan</label>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500 font-bold">Tanggal</span>
                            <input type="number" min="1" max="28" name="billing_due_day" value="<?= e(getSetting($pdo, 'billing_due_day', '10')) ?>" class="neu-input w-24 p-2.5 rounded-xl font-black text-center font-mono text-sm">
                            <span class="text-slate-500 font-bold">setiap bulan</span>
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Standar Printer Thermal</label>
                        <div class="neu-inset-sm p-2.5 rounded-xl text-slate-700 font-bold flex items-center justify-between">
                            <span>58mm / 80mm ESC/POS</span>
                            <span class="text-emerald-600 text-xs flex items-center gap-1 font-black">
                                <span class="led-pulse active-green"></span> Bluetooth Driver Ready
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pesan Footer Struk Pembayaran</label>
                    <textarea name="receipt_footer" rows="2" class="neu-input w-full p-2.5 rounded-xl font-medium"><?= e(getSetting($pdo, 'receipt_footer', "Terima kasih atas pembayaran Anda.\nSimpan struk ini sebagai bukti pembayaran yang sah.")) ?></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Rekening Bank Transfer / E-Wallet</label>
                    <textarea name="bank_info" rows="3" class="neu-input w-full p-2.5 rounded-xl font-mono text-xs"><?= e(getSetting($pdo, 'bank_info', "BCA: 123-456-7890 (a.n Rengganis Net)\nBRI: 0987-01-023456-50-1 (a.n Rengganis Net)\nDANA/Gopay: 0812-3456-7890")) ?></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-slate-200/60 flex justify-end">
                <button type="submit" class="px-6 py-3 rounded-2xl skeuo-btn-primary font-black shadow-lg flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Konfigurasi
                </button>
            </div>

        </form>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
