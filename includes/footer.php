<?php
/**
 * Modern Footer Layout & Neo-Skeuomorphic Modals for Rengganis Net
 */
?>
        </main> <!-- End of scrollable main -->

        <!-- Bottom System Bar (Subtle tactile status) -->
        <footer class="h-10 flex-shrink-0 bg-gradient-to-r from-[#edf3f9] to-[#e4ebf4] border-t border-white/80 px-6 flex items-center justify-between text-[11px] text-slate-500 z-20">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-700">Rengganis Net Console</span> &bull; 
                <span>PHP 8.4 & MySQL 8.0</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5"><span class="led-pulse active-green"></span> Server Engine Active</span>
                <span class="hidden sm:inline text-slate-400">ESC/POS Bluetooth & 58mm Thermal Engine</span>
            </div>
        </footer>

    </div> <!-- End of content wrapper -->

</div> <!-- End of outer layout -->

    <!-- ====================================================================
         MODAL 1: PROSES PEMBAYARAN TAGIHAN (SKEUOMORPHIC CASH DRAWER STYLE)
         ==================================================================== -->
    <div id="paymentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-md p-4 overflow-y-auto">
        <div class="bg-[#edf3f9] rounded-3xl max-w-md w-full shadow-[0_25px_60px_rgba(0,0,0,0.4)] border border-white/80 overflow-hidden transform transition-all animate-scale-up">
            
            <!-- Metallic Beveled Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-800 text-white flex items-center justify-between shadow-md relative">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center text-white border border-white/30 shadow-inner">
                        <i class="fa-solid fa-money-bill-transfer text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base leading-tight font-tech">Proses Pembayaran</h3>
                        <p class="text-xs text-blue-100/80">Konfirmasi penerimaan tagihan WiFi</p>
                    </div>
                </div>
                <button onclick="closePaymentModal()" type="button" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Form Body with Tactile Inset Panels -->
            <form id="paymentForm" onsubmit="submitPayment(event)" class="p-6 space-y-4">
                <input type="hidden" id="payBillId" name="bill_id" value="">
                
                <!-- Bill Summary Panel -->
                <div class="neu-inset rounded-2xl p-4 space-y-2">
                    <div class="flex justify-between items-center text-xs text-slate-500">
                        <span>No. Invoice:</span>
                        <span id="payBillNumber" class="font-mono font-bold text-slate-800">-</span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-slate-500">
                        <span>Nama Pelanggan:</span>
                        <span id="payCustomerName" class="font-extrabold text-slate-900">-</span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-slate-500">
                        <span>Periode Tagihan:</span>
                        <span id="payPeriod" class="font-semibold text-slate-800">-</span>
                    </div>
                    <div class="pt-2 border-t border-slate-300/60 flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-700">TOTAL BAYAR:</span>
                        <span id="payAmount" class="text-lg font-black text-blue-600 font-mono">Rp 0</span>
                    </div>
                </div>

                <!-- Payment Method Select -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
                    <select id="payMethod" name="payment_method" class="neu-input w-full px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-800">
                        <option value="Tunai">💵 Tunai / Cash (Lapangan/Kantor)</option>
                        <option value="Transfer BCA">🏦 Transfer BCA</option>
                        <option value="Transfer BRI">🏦 Transfer BRI</option>
                        <option value="Transfer Mandiri">🏦 Transfer Mandiri</option>
                        <option value="DANA / Gopay">📱 DANA / Gopay / OVO</option>
                        <option value="QRIS">⚡ QRIS Statis</option>
                    </select>
                </div>

                <!-- Collector / Kasir -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Petugas / Kolektor</label>
                    <input type="text" id="payCollector" name="collector_name" value="<?= e($currentUser['name']) ?>" required class="neu-input w-full px-3.5 py-2.5 rounded-xl text-xs font-bold">
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan (Opsional)</label>
                    <input type="text" id="payNotes" name="notes" placeholder="Contoh: Titip tetangga / Pas di lokasi" class="neu-input w-full px-3.5 py-2.5 rounded-xl text-xs">
                </div>

                <!-- Action Buttons -->
                <div class="pt-2 flex items-center gap-3">
                    <button type="button" onclick="closePaymentModal()" class="w-1/3 py-3 px-4 rounded-xl skeuo-btn-secondary text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitPay" class="w-2/3 py-3 px-4 rounded-xl skeuo-btn-emerald text-xs font-black flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check"></i> Konfirmasi Lunas
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ====================================================================
         MODAL 2: BUKTI PEMBAYARAN STRUK THERMAL & DIGITAL CARD (FUTURISTIC)
         ==================================================================== -->
    <div id="receiptModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/70 backdrop-blur-md p-4 overflow-y-auto">
        <div class="bg-[#edf3f9] rounded-3xl max-w-lg w-full shadow-[0_25px_60px_rgba(0,0,0,0.5)] border border-white/80 overflow-hidden transform transition-all animate-scale-up">
            
            <!-- Dark Cyber Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between border-b border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center">
                        <i class="fa-solid fa-receipt text-base"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm leading-tight font-tech">Bukti Pelunasan Resmi</h3>
                        <p class="text-[11px] text-slate-400">Pilih mode cetak thermal atau berkas digital</p>
                    </div>
                </div>
                <button onclick="closeReceiptModal()" type="button" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Body: Stylized Digital Receipt Card Preview -->
            <div class="p-6 bg-[#e2eaf4] flex flex-col items-center">
                
                <div id="digitalReceiptCard" class="w-full max-w-[340px] bg-white rounded-3xl p-6 shadow-[0_20px_35px_rgba(0,0,0,0.12)] border border-slate-200 text-slate-800 text-xs relative overflow-hidden">
                    
                    <!-- Decorative Hologram Top Strip -->
                    <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-500"></div>

                    <!-- Header -->
                    <div class="text-center pb-4 border-b border-dashed border-slate-300">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-gradient-to-tr from-blue-700 to-indigo-600 text-white flex items-center justify-center mb-2 shadow-md shadow-blue-500/30">
                            <i class="fa-solid fa-wifi text-xl"></i>
                        </div>
                        <h4 class="font-black text-base tracking-tight text-slate-900 font-tech">RENGGANIS NET</h4>
                        <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">Solusi Internet Cepat & Hemat</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Jl. Raya Rengganis No. 45 &bull; CS: 0812-3456-7890</p>
                    </div>

                    <!-- Status Banner -->
                    <div class="py-3 flex items-center justify-between border-b border-dashed border-slate-300">
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Status Tagihan</span>
                            <span class="inline-flex items-center gap-1.5 text-xs font-black text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> LUNAS
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Tgl Bayar</span>
                            <span id="receiptCardDate" class="text-[11px] font-bold text-slate-800">-</span>
                        </div>
                    </div>

                    <!-- Details Table -->
                    <div class="py-3 space-y-1.5 border-b border-dashed border-slate-300 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500">No. Invoice</span>
                            <span id="receiptCardNumber" class="font-mono font-bold text-slate-900">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">ID Pelanggan</span>
                            <span id="receiptCardCode" class="font-mono font-bold text-blue-600">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Nama Pelanggan</span>
                            <span id="receiptCardCust" class="font-extrabold text-slate-900">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Alamat</span>
                            <span id="receiptCardAddress" class="text-right text-slate-700 font-semibold max-w-[170px] truncate">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Periode</span>
                            <span id="receiptCardPeriod" class="font-bold text-slate-800">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Paket Layanan</span>
                            <span id="receiptCardPkg" class="font-bold text-blue-700">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Metode Bayar</span>
                            <span id="receiptCardMethod" class="font-semibold text-slate-800">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Kasir / Kolektor</span>
                            <span id="receiptCardCollector" class="font-semibold text-slate-800">-</span>
                        </div>
                    </div>

                    <!-- Total Amount -->
                    <div class="pt-3 pb-2 flex items-center justify-between">
                        <span class="font-black text-slate-700 text-xs uppercase tracking-wider">TOTAL PEMBAYARAN</span>
                        <span id="receiptCardAmount" class="font-black text-blue-700 text-lg font-mono">Rp 0</span>
                    </div>

                    <!-- Footer Note -->
                    <div class="pt-2 text-center text-[10px] text-slate-400 border-t border-slate-100">
                        <p class="font-medium">Terima kasih atas pembayaran Anda.</p>
                        <p class="text-[9px] mt-0.5">Simpan struk ini sebagai bukti transaksi resmi yang sah.</p>
                    </div>

                </div>

            </div>

            <!-- Footer Buttons (Acceptance Criteria 14: 2 Main Actions + WhatsApp) -->
            <div class="p-5 bg-[#edf3f9] border-t border-white/80 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    
                    <!-- BUTTON 1: Cetak Thermal (ESC/POS Bluetooth & 58mm window.print) -->
                    <button type="button" id="btnPrintThermal" onclick="handlePrintThermal()" class="w-full py-3 px-4 rounded-xl skeuo-btn-dark font-extrabold text-xs flex items-center justify-center gap-2">
                        <i class="fa-solid fa-print text-blue-400"></i> Cetak Thermal
                    </button>

                    <!-- BUTTON 2: Unduh Struk Digital (PNG image via html2canvas) -->
                    <button type="button" id="btnDownloadDigital" onclick="handleDownloadDigitalReceipt()" class="w-full py-3 px-4 rounded-xl skeuo-btn-emerald font-extrabold text-xs flex items-center justify-center gap-2">
                        <i class="fa-solid fa-file-image"></i> Unduh Digital
                    </button>

                </div>

                <!-- Secondary Action: WhatsApp Direct Link -->
                <a id="btnShareWhatsApp" href="#" target="_blank" class="w-full py-2.5 px-4 rounded-xl skeuo-btn-secondary text-xs font-bold flex items-center justify-center gap-2 text-emerald-700">
                    <i class="fa-brands fa-whatsapp text-sm text-emerald-600"></i> Kirim Struk via WhatsApp
                </a>

                <div class="text-center pt-1">
                    <button type="button" onclick="closeReceiptModal()" class="text-xs text-slate-400 hover:text-slate-600 font-bold">
                        Tutup Jendela
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ====================================================================
         MODAL 3: UBAH STATUS PELANGGAN (AKTIF/CUTI/ISOLIR - FR-03)
         ==================================================================== -->
    <div id="statusModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-md p-4 overflow-y-auto">
        <div class="bg-[#edf3f9] rounded-3xl max-w-md w-full shadow-[0_25px_60px_rgba(0,0,0,0.4)] border border-white/80 overflow-hidden transform transition-all animate-scale-up">
            
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <i class="fa-solid fa-arrows-spin text-base"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm leading-tight font-tech">Ubah Status Langganan</h3>
                        <p class="text-xs text-slate-400 font-mono" id="statusCustomerName">-</p>
                    </div>
                </div>
                <button onclick="closeStatusModal()" type="button" class="w-8 h-8 rounded-xl bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form id="statusForm" onsubmit="submitCustomerStatus(event)" class="p-6 space-y-4">
                <input type="hidden" id="statusCustomerId" name="customer_id" value="">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Status Baru</label>
                    <select id="statusSelect" name="new_status" class="neu-input w-full px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-800">
                        <option value="aktif">🟢 Aktif (Berlangganan Normal)</option>
                        <option value="cuti">🟡 Cuti / Libur Sementara (Bebas Tagihan)</option>
                        <option value="isolir">🔴 Isolir (Tunggakan / Ditangguhkan)</option>
                    </select>
                </div>

                <!-- Special Notice for CUTI status (FR-03 PRD requirement) -->
                <div id="cutiNotice" class="neu-inset rounded-2xl p-3.5 text-xs text-amber-900 space-y-1">
                    <div class="font-extrabold flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-amber-600"></i> Ketentuan Status Cuti (FR-03):
                    </div>
                    <p class="text-[11px] text-amber-800 leading-relaxed font-medium">
                        Sistem secara otomatis <strong>TIDAK AKAN</strong> membuat tagihan baru untuk pelanggan ini di bulan berikutnya tanpa dikenakan denda.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Alasan / Catatan Perubahan</label>
                    <textarea id="statusReason" name="reason" rows="2" placeholder="Contoh: Mudik keluar kota 1 bulan / Renovasi rumah" class="neu-input w-full px-3.5 py-2.5 rounded-xl text-xs"></textarea>
                </div>

                <div class="pt-2 flex items-center gap-3">
                    <button type="button" onclick="closeStatusModal()" class="w-1/3 py-2.5 px-4 rounded-xl skeuo-btn-secondary text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitStatus" class="w-2/3 py-2.5 px-4 rounded-xl skeuo-btn-dark text-xs font-black">
                        Simpan Perubahan
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ====================================================================
         HIDDEN THERMAL PRINT AREA FOR @media print (58mm Thermal Rolls)
         ==================================================================== -->
    <div id="thermalReceiptPrintArea" class="hidden">
        <div style="text-align: center; margin-bottom: 4px;">
            <div style="font-weight: bold; font-size: 13px;">RENGGANIS NET</div>
            <div style="font-size: 9px;">Solusi Internet Cepat & Hemat</div>
            <div style="font-size: 8px;">Telp: 0812-3456-7890</div>
            <div style="font-size: 8px;">Jl. Raya Rengganis No. 45</div>
        </div>
        <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
        <div style="display: flex; justify-content: space-between; font-size: 10px;">
            <span>No. Struk:</span>
            <span id="thermalBillNumber" style="font-weight: bold;">-</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 10px;">
            <span>Tanggal:</span>
            <span id="thermalDate">-</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 10px;">
            <span>ID Pel:</span>
            <span id="thermalCode">-</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 10px;">
            <span>Nama:</span>
            <span id="thermalCust" style="font-weight: bold;">-</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 9px;">
            <span>Alamat:</span>
            <span id="thermalAddress">-</span>
        </div>
        <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
        <div style="display: flex; justify-content: space-between; font-size: 10px;">
            <span>Periode:</span>
            <span id="thermalPeriod">-</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 10px;">
            <span>Paket:</span>
            <span id="thermalPkg">-</span>
        </div>
        <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
        <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: bold;">
            <span>TOTAL:</span>
            <span id="thermalAmount">Rp 0</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: bold;">
            <span>STATUS:</span>
            <span>LUNAS</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 10px;">
            <span>Metode:</span>
            <span id="thermalMethod">Tunai</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 10px;">
            <span>Kasir:</span>
            <span id="thermalCollector">Admin</span>
        </div>
        <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
        <div style="text-align: center; font-size: 9px; margin-top: 4px;">
            <div>Terima kasih atas pembayaran Anda.</div>
            <div>Simpan struk ini sbg bukti sah.</div>
        </div>
        <div style="height: 15mm;"></div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/bluetooth-print.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        // Sidebar Toggle Handler (Desktop & Mobile)
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (window.innerWidth < 768) {
                // Mobile behavior
                if (sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.remove('-translate-x-full');
                    backdrop.classList.remove('hidden');
                } else {
                    sidebar.classList.add('-translate-x-full');
                    backdrop.classList.add('hidden');
                }
            } else {
                // Desktop collapse behavior
                sidebar.classList.toggle('md:hidden');
            }
        }

        // Live Digital Clock
        function updateLiveClock() {
            const el = document.getElementById('liveClockWidget');
            if (!el) return;
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            el.textContent = `${h}:${m}:${s} WIB`;
        }
        setInterval(updateLiveClock, 1000);

        // AJAX Payment Submission
        async function submitPayment(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitPay');
            const originalBtn = btn.innerHTML;

            const billId = document.getElementById('payBillId').value;
            const method = document.getElementById('payMethod').value;
            const collector = document.getElementById('payCollector').value;
            const notes = document.getElementById('payNotes').value;

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

                const token = window.csrfToken || '';
                const response = await fetch('api/bills.php?action=pay', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': token
                    },
                    body: JSON.stringify({
                        bill_id: billId,
                        payment_method: method,
                        collector_name: collector,
                        notes: notes,
                        csrf_token: token
                    })
                });

                const res = await response.json();
                if (!res.success) {
                    throw new Error(res.message || 'Gagal memproses pembayaran');
                }

                closePaymentModal();
                showReceiptModal(res.data);

                // Update row in table if on bills page or dashboard
                const row = document.getElementById(`bill-row-${billId}`);
                if (row) {
                    row.querySelector('.status-col').innerHTML = `
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                            <span class="led-pulse active-green"></span> Lunas
                        </span>
                    `;
                    const actionCol = row.querySelector('.action-col');
                    if (actionCol) {
                        actionCol.innerHTML = `
                            <button onclick='showReceiptModal(${JSON.stringify(res.data)})' class="px-3 py-1.5 rounded-xl skeuo-btn-secondary text-xs font-bold flex items-center gap-1.5">
                                <i class="fa-solid fa-receipt text-blue-600"></i> Struk
                            </button>
                        `;
                    }
                }

                showToast('Pembayaran berhasil diproses dan berstatus Lunas!');
            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalBtn;
            }
        }

        // AJAX Status Update Submission (FR-03 & Analytics)
        async function submitCustomerStatus(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitStatus');
            const originalBtn = btn.innerHTML;

            const custId = document.getElementById('statusCustomerId').value;
            const newStatus = document.getElementById('statusSelect').value;
            const reason = document.getElementById('statusReason').value;

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

                const response = await fetch('api/customers.php?action=update_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_id: custId,
                        new_status: newStatus,
                        reason: reason
                    })
                });

                const res = await response.json();
                if (!res.success) {
                    throw new Error(res.message || 'Gagal mengubah status');
                }

                closeStatusModal();
                showToast('Status pelanggan berhasil diperbarui!');
                setTimeout(() => window.location.reload(), 700);
            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalBtn;
            }
        }
    </script>
</body>
</html>
