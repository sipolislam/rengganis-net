<?php
/**
 * System & Business Profile Settings for Rengganis Net
 * Luxury Neo-Skeuomorphic Layout with AdminLTE Sidebar (Setup / T-022)
 * Comprehensive Identity, Thermal Receipt Footers, and Real-Time Live Print Simulator
 */

$pageTitle = 'Pengaturan Profil & Struk';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAdmin();

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie('settings.php');

    $companyName    = trim($_POST['company_name'] ?? 'Rengganis Net');
    $companyTagline = trim($_POST['company_tagline'] ?? '');
    $companyPhone   = trim($_POST['company_phone'] ?? '');
    $companyEmail   = trim($_POST['company_email'] ?? '');
    $companyAddress = trim($_POST['company_address'] ?? '');
    $billingDueDay  = (int)($_POST['billing_due_day'] ?? 10);
    $receiptFooter  = trim($_POST['receipt_footer'] ?? '');
    $bankInfo       = trim($_POST['bank_info'] ?? '');

    // Server-Side Guardrail Validations
    if (empty($companyName)) {
        setFlash('error', 'Nama Usaha / Provider tidak boleh kosong.');
        header('Location: settings.php');
        exit;
    }

    if ($billingDueDay < 1 || $billingDueDay > 28) {
        $billingDueDay = 10;
    }

    // Derive footer lines automatically for thermal & digital cards
    $footerLines = array_values(array_filter(array_map('trim', explode("\n", $receiptFooter))));
    $footerLine1 = $footerLines[0] ?? 'Terima kasih atas pembayaran Anda.';
    $footerLine2 = $footerLines[1] ?? 'Simpan struk ini sebagai bukti sah.';

    $settingsToUpdate = [
        'company_name'         => $companyName,
        'company_tagline'      => $companyTagline,
        'company_phone'        => $companyPhone,
        'company_email'        => $companyEmail,
        'company_address'      => $companyAddress,
        'billing_due_day'      => (string)$billingDueDay,
        'receipt_footer'       => $receiptFooter,
        'receipt_footer_line1' => $footerLine1,
        'receipt_footer_line2' => $footerLine2,
        'bank_info'            => $bankInfo
    ];

    foreach ($settingsToUpdate as $key => $val) {
        setSetting($pdo, $key, $val);
    }

    setFlash('success', 'Konfigurasi identitas usaha dan catatan kaki struk berhasil disimpan.');
    header('Location: settings.php');
    exit;
}

// Current Values
$companyName    = getSetting($pdo, 'company_name', 'Rengganis Net');
$companyTagline = getSetting($pdo, 'company_tagline', 'Solusi Internet Cepat & Hemat');
$companyPhone   = getSetting($pdo, 'company_phone', '0812-3456-7890');
$companyEmail   = getSetting($pdo, 'company_email', 'admin@rengganis.net');
$companyAddress = getSetting($pdo, 'company_address', 'Jl. Raya Rengganis No. 45, Sumber Malang');
$billingDueDay  = getSetting($pdo, 'billing_due_day', '10');
$receiptFooter  = getSetting($pdo, 'receipt_footer', "Terima kasih atas pembayaran Anda.\nSimpan struk ini sebagai bukti pembayaran yang sah.");
$bankInfo       = getSetting($pdo, 'bank_info', "BCA: 123-456-7890 (a.n Rengganis Net)\nBRI: 0987-01-023456-50-1 (a.n Rengganis Net)\nDANA/Gopay: 0812-3456-7890");

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100/70 border border-blue-200 text-xs font-bold text-blue-800 mb-1.5">
                <span class="led-pulse active-green"></span>
                <span>Modul Pengaturan Usaha (T-022)</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-tech">Profil Usaha & Catatan Kaki Struk</h1>
            <p class="text-xs text-slate-500 font-medium">Pengaturan identitas bisnis, tanggal jatuh tempo penagihan bulanan, dan teks catatan kaki struk thermal</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-slate-200/80 text-slate-700 font-bold text-xs border border-slate-300 flex items-center gap-2">
                <i class="fa-solid fa-print text-blue-600"></i> Standar ESC/POS 58mm/80mm
            </span>
        </div>
    </div>

    <!-- Main 2-Column Grid (Form Left, Live Simulator Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: SETTINGS FORM (7 Cols) -->
        <div class="lg:col-span-7 space-y-6">
            
            <div class="neu-flat rounded-3xl p-6 sm:p-7 border border-white/80 shadow-md">
                <form id="settingsForm" action="settings.php" method="POST" class="space-y-6 text-xs">
                    <?= csrfField() ?>
                    
                    <!-- SECTION 1: PROFIL & IDENTITAS USAHA -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
                                <i class="fa-solid fa-building text-blue-600"></i> Identitas Bisnis & Provider WiFi (FR-06)
                            </h3>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Identitas Utama</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1.5">Nama Usaha / Provider *</label>
                                <input type="text" id="settingCompanyName" name="company_name" required value="<?= e($companyName) ?>" class="neu-input w-full p-2.5 rounded-xl font-black text-slate-800 text-xs focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1.5">Tagline / Slogan Layanan</label>
                                <input type="text" id="settingCompanyTagline" name="company_tagline" value="<?= e($companyTagline) ?>" class="neu-input w-full p-2.5 rounded-xl font-bold text-slate-700 text-xs">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1.5">No. Kontak / WhatsApp CS *</label>
                                <input type="text" id="settingCompanyPhone" name="company_phone" required value="<?= e($companyPhone) ?>" class="neu-input w-full p-2.5 rounded-xl font-bold font-mono text-slate-800 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1.5">Email Layanan & Korespondensi</label>
                                <input type="email" id="settingCompanyEmail" name="company_email" value="<?= e($companyEmail) ?>" class="neu-input w-full p-2.5 rounded-xl font-mono text-slate-700 text-xs">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1.5">Alamat Kantor / Basecamp Operasional</label>
                            <textarea id="settingCompanyAddress" name="company_address" rows="2" class="neu-input w-full p-2.5 rounded-xl font-medium text-slate-700 text-xs leading-relaxed"><?= e($companyAddress) ?></textarea>
                        </div>
                    </div>

                    <!-- SECTION 2: KETENTUAN PENAGIHAN & BANK -->
                    <div class="space-y-4 pt-4 border-t border-slate-200/60">
                        <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
                                <i class="fa-solid fa-calendar-check text-blue-600"></i> Ketentuan Tagihan & Rekening Pembayaran
                            </h3>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Billing Rules</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1.5">Tanggal Jatuh Tempo Bulanan</label>
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-500 font-bold">Tanggal</span>
                                    <input type="number" min="1" max="28" name="billing_due_day" value="<?= e($billingDueDay) ?>" class="neu-input w-24 p-2.5 rounded-xl font-black text-center font-mono text-sm text-blue-700">
                                    <span class="text-slate-500 font-bold">setiap bulan</span>
                                </div>
                            </div>
                            <div class="neu-inset rounded-2xl p-3 text-[11px] text-slate-600 space-y-1">
                                <div class="font-extrabold text-slate-800 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-info text-blue-600"></i> Otomasi Due Date:
                                </div>
                                <p class="leading-tight text-slate-500">
                                    Tagihan baru yang diterbitkan melalui batch billing otomatis jatuh tempo pada tanggal ini.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1.5">Rekening Bank Transfer & E-Wallet (Muncul di WhatsApp)</label>
                            <textarea name="bank_info" rows="3" class="neu-input w-full p-2.5 rounded-xl font-mono text-xs leading-relaxed"><?= e($bankInfo) ?></textarea>
                        </div>
                    </div>

                    <!-- SECTION 3: CATATAN KAKI STRUK (FR-07 / T-022) -->
                    <div class="space-y-4 pt-4 border-t border-slate-200/60">
                        <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 font-tech">
                                <i class="fa-solid fa-receipt text-emerald-600"></i> Catatan Kaki Struk Pembayaran (FR-07)
                            </h3>
                            <span class="text-[10px] text-emerald-600 font-bold uppercase tracking-wider">Thermal & Digital</span>
                        </div>

                        <!-- Template Quick-Insert Pills -->
                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1.5">Pilih Templat Cepat Catatan Kaki:</label>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" onclick="applyFooterTemplate(1)" class="px-2.5 py-1 rounded-lg skeuo-btn-secondary text-[11px] font-bold text-slate-700 flex items-center gap-1 hover:text-blue-700">
                                    <i class="fa-solid fa-check-double text-blue-600"></i> Standar Resmi
                                </button>
                                <button type="button" onclick="applyFooterTemplate(2)" class="px-2.5 py-1 rounded-lg skeuo-btn-secondary text-[11px] font-bold text-slate-700 flex items-center gap-1 hover:text-emerald-700">
                                    <i class="fa-solid fa-headset text-emerald-600"></i> Kontak Bantuan 24/7
                                </button>
                                <button type="button" onclick="applyFooterTemplate(3)" class="px-2.5 py-1 rounded-lg skeuo-btn-secondary text-[11px] font-bold text-slate-700 flex items-center gap-1 hover:text-amber-700">
                                    <i class="fa-solid fa-clock text-amber-600"></i> Pengingat Siklus Aktif
                                </button>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="font-bold text-slate-700 uppercase tracking-wider text-[10px]">Teks Catatan Kaki (Bisa Multi-Baris) *</label>
                                <span id="footerCharCount" class="text-[10px] font-mono text-slate-400 font-bold">0 karakter</span>
                            </div>
                            <textarea id="settingReceiptFooter" name="receipt_footer" rows="3" required class="neu-input w-full p-3 rounded-xl font-medium text-slate-800 text-xs leading-relaxed focus:ring-2 focus:ring-emerald-500"><?= e($receiptFooter) ?></textarea>
                            <p class="text-[10px] text-slate-400 mt-1">
                                Teks ini akan dicetak otomatis di baris terbawah struk printer Bluetooth 58mm/80mm, kuitansi digital, dan pesan bukti bayar WhatsApp.
                            </p>
                        </div>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="pt-4 border-t border-slate-200/60 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400 font-medium">Perubahan langsung aktif di seluruh struk cetak & laporan.</span>
                        <button type="submit" class="px-6 py-3 rounded-2xl skeuo-btn-primary font-black text-xs shadow-lg flex items-center gap-2 cursor-pointer transition-transform active:scale-95">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Konfigurasi
                        </button>
                    </div>

                </form>
            </div>

        </div>

        <!-- RIGHT COLUMN: LIVE INTERACTIVE SIMULATOR (5 Cols) -->
        <div class="lg:col-span-5 space-y-4 lg:sticky lg:top-20">
            
            <div class="neu-flat rounded-3xl p-5 border border-white/80 shadow-md space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200/60">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <h4 class="font-extrabold text-xs text-slate-900 uppercase tracking-wider font-tech">Pratinjau Struk Real-Time</h4>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-extrabold border border-emerald-200">
                        Live Preview 58mm
                    </span>
                </div>

                <!-- Realistic Thermal Receipt Sheet Preview -->
                <div class="bg-white rounded-2xl p-5 border border-slate-300 shadow-inner font-mono text-[11px] text-slate-800 leading-tight select-none">
                    
                    <!-- Paper Top Jagged Visual (Thermal Cut) -->
                    <div class="border-b-2 border-dotted border-slate-400 pb-3 mb-3 text-center">
                        <div id="simCompanyName" class="font-black text-sm uppercase tracking-tight text-slate-900"><?= e($companyName) ?></div>
                        <div id="simCompanyTagline" class="text-[9px] text-slate-600 mt-0.5 font-bold"><?= e($companyTagline) ?></div>
                        <div id="simCompanyContact" class="text-[9px] text-slate-500 mt-0.5">Telp: <?= e($companyPhone) ?></div>
                        <div id="simCompanyAddress" class="text-[9px] text-slate-500 mt-0.5"><?= e($companyAddress) ?></div>
                    </div>

                    <!-- Invoice Dummy Data -->
                    <div class="space-y-1 text-[10px] border-b border-dashed border-slate-300 pb-2.5 mb-2.5">
                        <div class="flex justify-between">
                            <span class="text-slate-500">No. Struk:</span>
                            <span class="font-bold text-slate-900">INV/<?= date('Ym') ?>/0001</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Tanggal:</span>
                            <span><?= date('d/m/Y H:i') ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">ID Pelanggan:</span>
                            <span class="font-bold text-blue-700">RNG-001</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Nama:</span>
                            <span class="font-bold">Budi Santoso</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Paket:</span>
                            <span>Paket Reguler 20 Mbps</span>
                        </div>
                    </div>

                    <!-- Payment Realization -->
                    <div class="space-y-1 text-[10px] border-b border-dashed border-slate-300 pb-2.5 mb-2.5">
                        <div class="flex justify-between font-bold text-xs text-slate-900">
                            <span>TOTAL BAYAR:</span>
                            <span class="text-emerald-700">Rp 150.000</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Metode Bayar:</span>
                            <span class="font-bold">Tunai (Cash)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Status:</span>
                            <span class="font-black text-emerald-600">LUNAS</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Kasir / Kolektor:</span>
                            <span>Admin Loket</span>
                        </div>
                    </div>

                    <!-- Simulated Dynamic Receipt Footer -->
                    <div class="text-center pt-1 text-[9px] text-slate-600 font-medium space-y-0.5" id="simReceiptFooter">
                        <?php 
                        $initFooterLines = array_values(array_filter(array_map('trim', explode("\n", $receiptFooter))));
                        foreach ($initFooterLines as $line):
                        ?>
                            <div><?= e($line) ?></div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paper Tear Guide -->
                    <div class="border-t-2 border-dotted border-slate-400 mt-4 pt-1 text-center text-[8px] text-slate-400 uppercase tracking-widest">
                        - - - Gunting / Sobek di Sini - - -
                    </div>

                </div>

                <div class="text-[11px] text-slate-500 text-center font-medium">
                    <i class="fa-solid fa-arrows-rotate text-blue-500 mr-1"></i> Tampilan di atas langsung berubah saat Anda mengetik di formulir kiri.
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Real-Time Interactive Preview Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputName     = document.getElementById('settingCompanyName');
        const inputTagline  = document.getElementById('settingCompanyTagline');
        const inputPhone    = document.getElementById('settingCompanyPhone');
        const inputAddress  = document.getElementById('settingCompanyAddress');
        const inputFooter   = document.getElementById('settingReceiptFooter');
        const charCountEl   = document.getElementById('footerCharCount');

        const simName       = document.getElementById('simCompanyName');
        const simTagline    = document.getElementById('simCompanyTagline');
        const simContact    = document.getElementById('simCompanyContact');
        const simAddress    = document.getElementById('simCompanyAddress');
        const simFooter     = document.getElementById('simReceiptFooter');

        function updateSimulator() {
            if (simName) simName.textContent = inputName.value.trim() || 'RENGGANIS NET';
            if (simTagline) simTagline.textContent = inputTagline.value.trim() || 'Solusi Internet Cepat & Hemat';
            if (simContact) simContact.textContent = 'Telp: ' + (inputPhone.value.trim() || '0812-3456-7890');
            if (simAddress) simAddress.textContent = inputAddress.value.trim() || 'Jl. Raya Rengganis No. 45';

            if (simFooter && inputFooter) {
                const text = inputFooter.value.trim();
                if (charCountEl) charCountEl.textContent = text.length + ' karakter';

                simFooter.innerHTML = '';
                const lines = text.split('\n').map(l => l.trim()).filter(Boolean);
                if (lines.length === 0) {
                    const d = document.createElement('div');
                    d.textContent = 'Terima kasih atas pembayaran Anda.';
                    simFooter.appendChild(d);
                } else {
                    lines.forEach(line => {
                        const div = document.createElement('div');
                        div.textContent = line;
                        simFooter.appendChild(div);
                    });
                }
            }
        }

        // Bind input listeners
        if (inputName) inputName.addEventListener('input', updateSimulator);
        if (inputTagline) inputTagline.addEventListener('input', updateSimulator);
        if (inputPhone) inputPhone.addEventListener('input', updateSimulator);
        if (inputAddress) inputAddress.addEventListener('input', updateSimulator);
        if (inputFooter) inputFooter.addEventListener('input', updateSimulator);

        updateSimulator();
    });

    // Quick footer templates
    function applyFooterTemplate(type) {
        const footerInput = document.getElementById('settingReceiptFooter');
        const phoneInput  = document.getElementById('settingCompanyPhone');
        if (!footerInput) return;

        const phone = phoneInput ? (phoneInput.value.trim() || '0812-3456-7890') : '0812-3456-7890';

        let tpl = '';
        if (type === 1) {
            tpl = "Terima kasih atas pembayaran Anda.\nSimpan struk ini sebagai bukti pembayaran yang sah.";
        } else if (type === 2) {
            tpl = "Layanan bantuan kendala teknis 24/7 hubungi WA: " + phone + "\nTerima kasih atas kepercayaan Anda.";
        } else if (type === 3) {
            tpl = "Masa aktif internet berlaku hingga tanggal jatuh tempo berikutnya.\nSimpan struk ini sebagai bukti sah.";
        }

        footerInput.value = tpl;
        footerInput.dispatchEvent(new Event('input'));
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>