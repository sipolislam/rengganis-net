<?php
/**
 * Footer Layout & Global Modals for Rengganis Net
 */
?>
    </main>

    <!-- Footer Information -->
    <footer class="mt-auto border-t border-slate-200/80 bg-white py-6 hidden md:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-700">Rengganis Net</span> &copy; <?= date('Y') ?>. Sistem Informasi Manajemen Pelanggan & Tagihan.
            </div>
            <div class="flex items-center gap-4">
                <span><i class="fa-brands fa-bluetooth text-blue-500 mr-1"></i>ESC/POS Thermal 58mm/80mm</span>
                <span><i class="fa-solid fa-server text-emerald-500 mr-1"></i>PHP 8.4 & MySQL</span>
            </div>
        </div>
    </footer>

    <!-- ========================================== -->
    <!-- MODAL 1: PROSES PEMBAYARAN TAGIHAN (PAYMENT) -->
    <!-- ========================================== -->
    <div id="paymentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all animate-scale-up">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-money-bill-wave text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base leading-tight">Proses Pembayaran</h3>
                        <p class="text-xs text-blue-100">Konfirmasi penerimaan tagihan pelanggan</p>
                    </div>
                </div>
                <button onclick="closePaymentModal()" type="button" class="text-white/80 hover:text-white rounded-lg p-1.5 hover:bg-white/10 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Payment Form -->
            <form id="paymentForm" onsubmit="submitPayment(event)" class="p-6 space-y-4">
                <input type="hidden" id="payBillId" name="bill_id" value="">
                
                <!-- Bill Summary Card -->
                <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3.5 space-y-1.5">
                    <div class="flex justify-between items-center text-xs text-slate-500">
                        <span>No. Invoice:</span>
                        <span id="payBillNumber" class="font-mono font-semibold text-slate-800">-</span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-slate-500">
                        <span>Nama Pelanggan:</span>
                        <span id="payCustomerName" class="font-bold text-slate-900">-</span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-slate-500">
                        <span>Periode Tagihan:</span>
                        <span id="payPeriod" class="font-medium text-slate-800">-</span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 flex justify-between items-center">
                        <span class="text-xs font-semibold text-slate-700">Total Nominal:</span>
                        <span id="payAmount" class="text-base font-extrabold text-blue-600">Rp 0</span>
                    </div>
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
                    <select id="payMethod" name="payment_method" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium bg-white">
                        <option value="Tunai">Tunai / Cash (Lapangan/Kantor)</option>
                        <option value="Transfer BCA">Transfer BCA</option>
                        <option value="Transfer BRI">Transfer BRI</option>
                        <option value="Transfer Mandiri">Transfer Mandiri</option>
                        <option value="DANA / Gopay">DANA / Gopay / OVO</option>
                        <option value="QRIS">QRIS Statis</option>
                    </select>
                </div>

                <!-- Collector / Kasir -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Petugas / Kolektor</label>
                    <input type="text" id="payCollector" name="collector_name" value="<?= e($currentUser['name']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan (Opsional)</label>
                    <input type="text" id="payNotes" name="notes" placeholder="Contoh: Titip tetangga / Bayar pas di lokasi" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Action Buttons -->
                <div class="pt-2 flex items-center gap-3">
                    <button type="button" onclick="closePaymentModal()" class="w-1/3 py-2.5 px-4 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitPay" class="w-2/3 py-2.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-bold shadow-md shadow-blue-500/25 hover:from-blue-700 hover:to-indigo-700 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check"></i> Konfirmasi Lunas
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: BUKTI BAYAR / STRUK PEMBAYARAN    -->
    <!-- (Acceptance Criteria 14: Cetak Thermal & Unduh Struk Digital) -->
    <!-- ========================================== -->
    <div id="receiptModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all animate-scale-up">
            
            <!-- Header -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <i class="fa-solid fa-receipt text-base"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base leading-tight">Bukti Pembayaran WiFi</h3>
                        <p class="text-xs text-slate-400">Pilih format cetak atau unduh dokumen</p>
                    </div>
                </div>
                <button onclick="closeReceiptModal()" type="button" class="text-slate-400 hover:text-white rounded-lg p-1.5 hover:bg-slate-800 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Body: Preview Card -->
            <div class="p-6 bg-slate-100 flex flex-col items-center">
                
                <!-- Digital Receipt Element (Captured by html2canvas) -->
                <div id="digitalReceiptCard" class="w-full max-w-[340px] bg-white rounded-2xl p-5 shadow-lg border border-slate-200 text-slate-800 text-xs">
                    
                    <!-- Header Branding -->
                    <div class="text-center pb-3 border-b border-dashed border-slate-300">
                        <div class="w-10 h-10 mx-auto rounded-xl bg-blue-600 text-white flex items-center justify-center mb-1.5 shadow-md shadow-blue-500/20">
                            <i class="fa-solid fa-wifi text-lg"></i>
                        </div>
                        <h4 class="font-extrabold text-base tracking-tight text-slate-900">RENGGANIS NET</h4>
                        <p class="text-[11px] text-slate-500 font-medium">Solusi Internet Cepat & Hemat</p>
                        <p class="text-[10px] text-slate-400">Jl. Raya Rengganis No. 45 - WA: 0812-3456-7890</p>
                    </div>

                    <!-- Status Badge -->
                    <div class="py-2.5 flex items-center justify-between border-b border-dashed border-slate-300">
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-semibold">Status Tagihan</span>
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i> LUNAS
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block uppercase font-semibold">Tgl Bayar</span>
                            <span id="receiptCardDate" class="text-[11px] font-semibold text-slate-700">-</span>
                        </div>
                    </div>

                    <!-- Details Table -->
                    <div class="py-2.5 space-y-1.5 border-b border-dashed border-slate-300">
                        <div class="flex justify-between">
                            <span class="text-slate-500">No. Invoice</span>
                            <span id="receiptCardNumber" class="font-mono font-bold text-slate-800">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">ID Pelanggan</span>
                            <span id="receiptCardCode" class="font-mono font-semibold text-slate-700">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Nama Pelanggan</span>
                            <span id="receiptCardCust" class="font-bold text-slate-900">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Alamat</span>
                            <span id="receiptCardAddress" class="text-right text-slate-700 font-medium max-w-[170px] truncate">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Periode</span>
                            <span id="receiptCardPeriod" class="font-semibold text-slate-800">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Paket Layanan</span>
                            <span id="receiptCardPkg" class="font-semibold text-blue-600">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Metode Bayar</span>
                            <span id="receiptCardMethod" class="font-medium text-slate-700">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Kasir/Penagih</span>
                            <span id="receiptCardCollector" class="font-medium text-slate-700">-</span>
                        </div>
                    </div>

                    <!-- Total Amount -->
                    <div class="pt-3 pb-2 flex items-center justify-between">
                        <span class="font-bold text-slate-700 text-sm">TOTAL DIBAYAR</span>
                        <span id="receiptCardAmount" class="font-extrabold text-blue-600 text-base">Rp 0</span>
                    </div>

                    <!-- Footer Note -->
                    <div class="pt-2 text-center text-[10px] text-slate-400 border-t border-slate-100">
                        <p>Terima kasih atas pembayaran Anda.</p>
                        <p>Simpan struk ini sebagai bukti transaksi resmi.</p>
                    </div>

                </div>

            </div>

            <!-- Footer: 2 Required Primary Action Buttons + WhatsApp -->
            <div class="p-5 bg-white border-t border-slate-200 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    
                    <!-- BUTTON 1: Cetak Thermal (ESC/POS Bluetooth & 58mm window.print) -->
                    <button type="button" id="btnPrintThermal" onclick="handlePrintThermal()" class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2 group">
                        <i class="fa-solid fa-print text-blue-400 group-hover:scale-110 transition-transform"></i>
                        <span>Cetak Thermal</span>
                    </button>

                    <!-- BUTTON 2: Unduh Struk Digital (PNG image via html2canvas) -->
                    <button type="button" id="btnDownloadDigital" onclick="handleDownloadDigitalReceipt()" class="w-full py-3 px-4 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold text-sm shadow-sm transition-all flex items-center justify-center gap-2 group">
                        <i class="fa-solid fa-image text-emerald-600 group-hover:scale-110 transition-transform"></i>
                        <span>Unduh Struk Digital</span>
                    </button>

                </div>

                <!-- Secondary Action: WhatsApp Direct Link -->
                <div class="flex items-center justify-between pt-1">
                    <a id="btnShareWhatsApp" href="#" target="_blank" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-2">
                        <i class="fa-brands fa-whatsapp text-sm"></i> Kirim Struk via WhatsApp ke Pelanggan
                    </a>
                </div>

                <div class="text-center">
                    <button type="button" onclick="closeReceiptModal()" class="text-xs text-slate-400 hover:text-slate-600 font-medium">
                        Tutup Jendela
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 3: UBAH STATUS PELANGGAN (AKTIF/CUTI/ISOLIR) -->
    <!-- (FR-03 & Acceptance Criteria 14: Status Cuti Excludes Billing) -->
    <!-- ========================================== -->
    <div id="statusModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all animate-scale-up">
            
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <i class="fa-solid fa-arrows-rotate text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base leading-tight">Ubah Status Langganan</h3>
                        <p class="text-xs text-slate-400" id="statusCustomerName">-</p>
                    </div>
                </div>
                <button onclick="closeStatusModal()" type="button" class="text-slate-400 hover:text-white rounded-lg p-1.5 hover:bg-slate-800 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form id="statusForm" onsubmit="submitCustomerStatus(event)" class="p-6 space-y-4">
                <input type="hidden" id="statusCustomerId" name="customer_id" value="">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Status Baru</label>
                    <select id="statusSelect" name="new_status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="aktif">Aktif (Berlangganan Normal)</option>
                        <option value="cuti">Cuti / Libur Sementara (Bebas Tagihan)</option>
                        <option value="isolir">Isolir (Tunggakan / Ditangguhkan)</option>
                    </select>
                </div>

                <!-- Special Notice for CUTI status (FR-03 & PRD requirement) -->
                <div id="cutiNotice" class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-amber-600"></i> Ketentuan Status Cuti (FR-03):
                    </div>
                    <p class="text-amber-800 leading-relaxed">
                        Sistem secara otomatis <strong>TIDAK AKAN</strong> membuat tagihan baru untuk pelanggan ini di bulan berikutnya dan bebas denda penumpukan tagihan.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Alasan / Catatan Perubahan</label>
                    <textarea id="statusReason" name="reason" rows="2" placeholder="Contoh: Pelanggan bepergian keluar kota 1 bulan / Renovasi rumah" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="pt-2 flex items-center gap-3">
                    <button type="button" onclick="closeStatusModal()" class="w-1/3 py-2.5 px-4 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitStatus" class="w-2/3 py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold shadow-md transition-all flex items-center justify-center gap-2">
                        Simpan Perubahan
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- HIDDEN THERMAL PRINT AREA FOR @media print -->
    <!-- (Strictly formatted for 58mm thermal rolls)-->
    <!-- ========================================== -->
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
        // AJAX Payment Submission
        async function submitPayment(e) {
            e.preventDefault();
            const form = document.getElementById('paymentForm');
            const btn = document.getElementById('btnSubmitPay');
            const originalBtn = btn.innerHTML;

            const billId = document.getElementById('payBillId').value;
            const method = document.getElementById('payMethod').value;
            const collector = document.getElementById('payCollector').value;
            const notes = document.getElementById('payNotes').value;

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

                const response = await fetch('api/bills.php?action=pay', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        bill_id: billId,
                        payment_method: method,
                        collector_name: collector,
                        notes: notes
                    })
                });

                const res = await response.json();
                if (!res.success) {
                    throw new Error(res.message || 'Gagal memproses pembayaran');
                }

                // Close payment modal
                closePaymentModal();

                // Open the required Receipt Modal immediately with 2 options (FR-05, Acceptance Criteria 14)
                showReceiptModal(res.data);

                // Update row in table if on bills page or dashboard
                const row = document.getElementById(`bill-row-${billId}`);
                if (row) {
                    row.querySelector('.status-col').innerHTML = `
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                            <i class="fa-solid fa-check text-emerald-500"></i> Lunas
                        </span>
                    `;
                    const actionCol = row.querySelector('.action-col');
                    if (actionCol) {
                        actionCol.innerHTML = `
                            <button onclick='showReceiptModal(${JSON.stringify(res.data)})' class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 text-xs font-semibold transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-receipt text-blue-600"></i> Struk
                            </button>
                        `;
                    }
                }

                showToast('Pembayaran berhasil diproses dan lunas!');
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
                // Reload current page to refresh status badges & metrics
                setTimeout(() => window.location.reload(), 800);
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
