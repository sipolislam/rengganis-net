/**
 * Application Core JS for Rengganis Net
 * Modals, Receipts, html2canvas export, WhatsApp sharing, Toasts
 */

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    const bgClass = type === 'success' ? 'bg-emerald-600' : (type === 'error' ? 'bg-rose-600' : 'bg-blue-600');
    toast.className = `fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-xl text-white shadow-xl ${bgClass} transform transition-all duration-300 ease-out translate-y-10 opacity-0`;
    toast.innerHTML = `
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M5 13l4 4L19 7' : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'}"></path>
        </svg>
        <span class="text-sm font-medium">${message}</span>
    `;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.remove('translate-y-10', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    }, 50);

    setTimeout(() => {
        toast.classList.remove('translate-y-0', 'opacity-100');
        toast.classList.add('translate-y-10', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Global active receipt data storage
window.currentReceiptData = null;

/**
 * Open the Payment Modal
 */
function openPaymentModal(bill) {
    document.getElementById('payBillId').value = bill.id;
    document.getElementById('payBillNumber').textContent = bill.bill_number;
    document.getElementById('payCustomerName').textContent = bill.customer_name;
    document.getElementById('payPeriod').textContent = bill.period_name;
    document.getElementById('payAmount').textContent = bill.amount_formatted;
    document.getElementById('payNotes').value = '';

    const modal = document.getElementById('paymentModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

/**
 * Load and Show Receipt Modal by Bill ID (Fetch from API)
 */
async function loadAndShowReceipt(billId) {
    if (!billId) return;
    try {
        const response = await fetch(`api/bills.php?action=get_receipt&id=${billId}`);
        const res = await response.json();
        if (!res.success) {
            throw new Error(res.message || 'Gagal memuat kuitansi tagihan');
        }
        showReceiptModal(res.data);
    } catch (err) {
        showToast('Gagal memuat struk: ' + err.message, 'error');
    }
}

/**
 * Open the Post-Payment & General Receipt Modal
 * Acceptance Criteria (F5 / T-015): Modal struk muncul otomatis pasca pelunasan dengan rincian pembayaran lengkap
 * Menampilkan 2 tombol aksi utama ("Cetak Thermal" & "Unduh Struk Digital") serta kirim kuitansi via WhatsApp.
 */
function showReceiptModal(receiptData) {
    if (!receiptData) return;
    window.currentReceiptData = receiptData;

    // Company branding (dynamic fallback)
    const compName = receiptData.company_name || 'RENGGANIS NET';
    const compTagline = receiptData.company_tagline || 'Solusi Internet Cepat & Hemat';
    const compPhone = receiptData.company_phone || '0812-3456-7890';
    const compAddress = receiptData.company_address || 'Jl. Raya Rengganis No. 45';

    const cardCompanyEl = document.getElementById('receiptCardCompany');
    if (cardCompanyEl) cardCompanyEl.textContent = compName;
    const cardTaglineEl = document.getElementById('receiptCardTagline');
    if (cardTaglineEl) cardTaglineEl.textContent = compTagline;
    const cardContactEl = document.getElementById('receiptCardContact');
    if (cardContactEl) cardContactEl.textContent = `${compAddress} • CS: ${compPhone}`;

    // Populate Digital Card
    const numEl = document.getElementById('receiptCardNumber');
    if (numEl) numEl.textContent = receiptData.bill_number || '-';

    const dateEl = document.getElementById('receiptCardDate');
    if (dateEl) dateEl.textContent = receiptData.payment_date || receiptData.date || '-';

    const codeEl = document.getElementById('receiptCardCode');
    if (codeEl) codeEl.textContent = receiptData.customer_code || '-';

    const custEl = document.getElementById('receiptCardCust');
    if (custEl) custEl.textContent = receiptData.customer_name || '-';

    const addrEl = document.getElementById('receiptCardAddress');
    if (addrEl) {
        addrEl.textContent = receiptData.customer_address || '-';
        addrEl.title = receiptData.customer_address || '';
    }

    const perEl = document.getElementById('receiptCardPeriod');
    if (perEl) perEl.textContent = receiptData.period || '-';

    const pkgEl = document.getElementById('receiptCardPkg');
    if (pkgEl) {
        const speedSuffix = receiptData.package_speed ? ` (${receiptData.package_speed})` : '';
        pkgEl.textContent = (receiptData.package_name || '-') + speedSuffix;
    }

    const amtEl = document.getElementById('receiptCardAmount');
    if (amtEl) amtEl.textContent = receiptData.amount_formatted || 'Rp 0';

    const methodEl = document.getElementById('receiptCardMethod');
    if (methodEl) methodEl.textContent = receiptData.payment_method || 'Tunai';

    const collEl = document.getElementById('receiptCardCollector');
    if (collEl) collEl.textContent = receiptData.collector_name || 'Admin';

    // Optional Notes Row
    const notesRow = document.getElementById('receiptCardNotesRow');
    const notesEl = document.getElementById('receiptCardNotes');
    if (notesRow && notesEl) {
        if (receiptData.notes && receiptData.notes.trim()) {
            notesEl.textContent = receiptData.notes.trim();
            notesRow.classList.remove('hidden');
        } else {
            notesRow.classList.add('hidden');
        }
    }

    // Populate Hidden Thermal Print Emulation
    const thCompName = document.getElementById('thermalCompanyName');
    if (thCompName) thCompName.textContent = compName;
    const thCompTagline = document.getElementById('thermalCompanyTagline');
    if (thCompTagline) thCompTagline.textContent = compTagline;
    const thCompPhone = document.getElementById('thermalCompanyPhone');
    if (thCompPhone) thCompPhone.textContent = `Telp: ${compPhone}`;
    const thCompAddr = document.getElementById('thermalCompanyAddress');
    if (thCompAddr) thCompAddr.textContent = compAddress;

    const thNum = document.getElementById('thermalBillNumber');
    if (thNum) thNum.textContent = receiptData.bill_number || '-';
    const thDate = document.getElementById('thermalDate');
    if (thDate) thDate.textContent = receiptData.payment_date || receiptData.date || '-';
    const thCode = document.getElementById('thermalCode');
    if (thCode) thCode.textContent = receiptData.customer_code || '-';
    const thCust = document.getElementById('thermalCust');
    if (thCust) thCust.textContent = receiptData.customer_name || '-';
    const thAddr = document.getElementById('thermalAddress');
    if (thAddr) thAddr.textContent = receiptData.customer_address || '-';
    const thPer = document.getElementById('thermalPeriod');
    if (thPer) thPer.textContent = receiptData.period || '-';
    const thPkg = document.getElementById('thermalPkg');
    if (thPkg) thPkg.textContent = receiptData.package_name || '-';
    const thAmt = document.getElementById('thermalAmount');
    if (thAmt) thAmt.textContent = receiptData.amount_formatted || 'Rp 0';
    const thMethod = document.getElementById('thermalMethod');
    if (thMethod) thMethod.textContent = receiptData.payment_method || 'Tunai';
    const thColl = document.getElementById('thermalCollector');
    if (thColl) thColl.textContent = receiptData.collector_name || 'Admin';

    const thNotesRow = document.getElementById('thermalNotesRow');
    const thNotes = document.getElementById('thermalNotes');
    if (thNotesRow && thNotes) {
        if (receiptData.notes && receiptData.notes.trim()) {
            thNotes.textContent = receiptData.notes.trim();
            thNotesRow.style.display = 'flex';
        } else {
            thNotesRow.style.display = 'none';
        }
    }

    // Configure WhatsApp Button link (T-013: use server-generated receipt URL or structured template)
    const waBtn = document.getElementById('btnShareWhatsApp');
    if (waBtn) {
        if (receiptData.wa_receipt_url) {
            waBtn.href = receiptData.wa_receipt_url;
        } else if (receiptData.customer_phone) {
            let phone = receiptData.customer_phone.replace(/\D/g, '');
            if (phone.startsWith('0')) {
                phone = '62' + phone.substring(1);
            }
            const text = `*BUKTI PEMBAYARAN SAH (KUITANSI DIGITAL)*\n` +
                `*${compName}* - ${compTagline}\n` +
                `---------------------------------------------\n` +
                `Terima kasih! Pembayaran tagihan internet WiFi Anda telah kami terima dan diverifikasi.\n\n` +
                `📄 No. Transaksi / Kuitansi : *${receiptData.bill_number}*\n` +
                `👤 Pelanggan : *${receiptData.customer_name}* (${receiptData.customer_code})\n` +
                `📦 Paket Layanan : *${receiptData.package_name}*\n` +
                `🗓️ Periode Tagihan : *${receiptData.period}*\n` +
                `💰 Total Pelunasan : *${receiptData.amount_formatted}*\n` +
                `💳 Metode Pembayaran : *${receiptData.payment_method || 'Tunai'}*\n` +
                `📅 Waktu Transaksi : *${receiptData.payment_date || receiptData.date}*\n` +
                `👨‍💼 Petugas Kasir / Kolektor : *${receiptData.collector_name || 'Admin Kasir'}*\n` +
                `✅ Status Pembayaran : *LUNAS (SAH)*\n\n` +
                `---------------------------------------------\n` +
                `Simpan bukti pembayaran ini sebagai bukti sah transaksi.\n` +
                `📞 Bantuan & Layanan: ${compPhone}`;
            waBtn.href = `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
        }
    }

    const modal = document.getElementById('receiptModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Auto-focus primary print button for keyboard accessibility
        setTimeout(() => {
            const printBtn = document.getElementById('btnPrintThermal');
            if (printBtn) printBtn.focus();
        }, 100);
    }
}

function closeReceiptModal() {
    const modal = document.getElementById('receiptModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

/**
 * Action: Cetak Thermal Bluetooth ESC/POS with Browser print fallback
 */
async function handlePrintThermal() {
    if (!window.currentReceiptData) return;
    const btn = document.getElementById('btnPrintThermal');
    const originalText = btn.innerHTML;

    try {
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg> Menghubungkan Bluetooth...
        `;

        if (window.thermalPrinter.isSupported()) {
            try {
                await window.thermalPrinter.printEscPos(window.currentReceiptData);
                showToast('Struk berhasil dicetak ke printer Bluetooth thermal!');
            } catch (btErr) {
                console.warn('Bluetooth printer direct failed or cancelled:', btErr);
                // Fallback to browser print dialog
                showToast('Membuka dialog cetak browser (58mm)...', 'info');
                window.print();
            }
        } else {
            // Direct print fallback
            showToast('Web Bluetooth tidak tersedia di browser ini. Membuka print dialog...', 'info');
            window.print();
        }
    } catch (e) {
        showToast('Gagal mencetak: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

/**
 * Action: Unduh Struk Digital (PNG image via html2canvas)
 */
async function handleDownloadDigitalReceipt() {
    if (!window.currentReceiptData) return;
    const btn = document.getElementById('btnDownloadDigital');
    const originalText = btn.innerHTML;

    try {
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-emerald-700 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg> Merender Struk...
        `;

        const receiptEl = document.getElementById('digitalReceiptCard');
        if (!receiptEl) throw new Error('Elemen struk tidak ditemukan');

        // Render with high resolution scale
        const canvas = await html2canvas(receiptEl, {
            scale: 2.5,
            useCORS: true,
            backgroundColor: '#ffffff'
        });

        const image = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        const filename = `Struk-${window.currentReceiptData.bill_number.replace(/\//g, '-')}.png`;
        link.download = filename;
        link.href = image;
        link.click();

        showToast('Struk digital berhasil diunduh!');
    } catch (err) {
        console.error('Download error:', err);
        showToast('Gagal membuat gambar struk: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

/**
 * Change Customer Status Modal (Aktif, Cuti, Isolir)
 */
window.currentStatusCustomer = null;

function toggleModalStatusNotices(oldStatus, newStatus) {
    const cutiNotice = document.getElementById('cutiNotice');
    const reaktivasiNotice = document.getElementById('reaktivasiNotice');
    
    if (cutiNotice) {
        if (newStatus === 'cuti') {
            cutiNotice.classList.remove('hidden');
        } else {
            cutiNotice.classList.add('hidden');
        }
    }
    
    if (reaktivasiNotice) {
        if (oldStatus === 'cuti' && newStatus === 'aktif') {
            reaktivasiNotice.classList.remove('hidden');
        } else {
            reaktivasiNotice.classList.add('hidden');
        }
    }
}

function openStatusModal(customer) {
    window.currentStatusCustomer = customer;
    document.getElementById('statusCustomerId').value = customer.id;
    document.getElementById('statusCustomerName').textContent = customer.name + ' (' + customer.customer_code + ')';
    document.getElementById('statusSelect').value = customer.status;
    document.getElementById('statusReason').value = '';
    
    toggleModalStatusNotices(customer.status, customer.status);

    const modal = document.getElementById('statusModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeStatusModal() {
    const modal = document.getElementById('statusModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Toggle Cuti and Reaktivasi notices on status change in modal
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('statusSelect');
    if (sel) {
        sel.addEventListener('change', (e) => {
            const oldStatus = window.currentStatusCustomer ? window.currentStatusCustomer.status : '';
            toggleModalStatusNotices(oldStatus, e.target.value);
        });
    }

    // Keyboard accessibility: ESC key to close active modals
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const receiptModal = document.getElementById('receiptModal');
            if (receiptModal && !receiptModal.classList.contains('hidden')) {
                closeReceiptModal();
                return;
            }
            const payModal = document.getElementById('paymentModal');
            if (payModal && !payModal.classList.contains('hidden')) {
                closePaymentModal();
                return;
            }
            const statusModal = document.getElementById('statusModal');
            if (statusModal && !statusModal.classList.contains('hidden')) {
                closeStatusModal();
                return;
            }
        }
    });
});
