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
 * Open the Post-Payment & General Receipt Modal
 * Acceptance Criteria: When payment is completed, shows 2 main buttons: "Cetak Thermal" and "Unduh Struk Digital"
 */
function showReceiptModal(receiptData) {
    window.currentReceiptData = receiptData;

    // Populate Digital Card
    document.getElementById('receiptCardNumber').textContent = receiptData.bill_number;
    document.getElementById('receiptCardDate').textContent = receiptData.payment_date || receiptData.date;
    document.getElementById('receiptCardCode').textContent = receiptData.customer_code;
    document.getElementById('receiptCardCust').textContent = receiptData.customer_name;
    document.getElementById('receiptCardAddress').textContent = receiptData.customer_address || '-';
    document.getElementById('receiptCardPeriod').textContent = receiptData.period;
    document.getElementById('receiptCardPkg').textContent = receiptData.package_name + ' (' + (receiptData.package_speed || '') + ')';
    document.getElementById('receiptCardAmount').textContent = receiptData.amount_formatted;
    document.getElementById('receiptCardMethod').textContent = receiptData.payment_method || 'Tunai';
    document.getElementById('receiptCardCollector').textContent = receiptData.collector_name || 'Admin';

    // Populate Hidden Thermal Print Emulation
    document.getElementById('thermalBillNumber').textContent = receiptData.bill_number;
    document.getElementById('thermalDate').textContent = receiptData.payment_date || receiptData.date;
    document.getElementById('thermalCode').textContent = receiptData.customer_code;
    document.getElementById('thermalCust').textContent = receiptData.customer_name;
    document.getElementById('thermalAddress').textContent = receiptData.customer_address || '-';
    document.getElementById('thermalPeriod').textContent = receiptData.period;
    document.getElementById('thermalPkg').textContent = receiptData.package_name;
    document.getElementById('thermalAmount').textContent = receiptData.amount_formatted;
    document.getElementById('thermalMethod').textContent = receiptData.payment_method || 'Tunai';
    document.getElementById('thermalCollector').textContent = receiptData.collector_name || 'Admin';

    // Configure WhatsApp Button link
    const waBtn = document.getElementById('btnShareWhatsApp');
    if (waBtn && receiptData.customer_phone) {
        let phone = receiptData.customer_phone.replace(/\D/g, '');
        if (phone.startsWith('0')) {
            phone = '62' + phone.substring(1);
        }
        const text = `*BUKTI PEMBAYARAN WIFI RENGGANIS NET*\n` +
            `--------------------------------\n` +
            `No. Struk : ${receiptData.bill_number}\n` +
            `Pelanggan : ${receiptData.customer_name} (${receiptData.customer_code})\n` +
            `Periode   : ${receiptData.period}\n` +
            `Paket     : ${receiptData.package_name}\n` +
            `Total     : ${receiptData.amount_formatted}\n` +
            `Status    : *LUNAS*\n` +
            `Tgl Bayar : ${receiptData.payment_date || receiptData.date}\n` +
            `Metode    : ${receiptData.payment_method || 'Tunai'}\n` +
            `--------------------------------\n` +
            `Terima kasih telah berlangganan internet Rengganis Net!`;
        waBtn.href = `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
    }

    const modal = document.getElementById('receiptModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeReceiptModal() {
    const modal = document.getElementById('receiptModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
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
function openStatusModal(customer) {
    document.getElementById('statusCustomerId').value = customer.id;
    document.getElementById('statusCustomerName').textContent = customer.name + ' (' + customer.customer_code + ')';
    document.getElementById('statusSelect').value = customer.status;
    document.getElementById('statusReason').value = '';
    
    // Warning text if changing to Cuti
    const cutiNotice = document.getElementById('cutiNotice');
    if (customer.status === 'cuti') {
        cutiNotice.classList.remove('hidden');
    } else {
        cutiNotice.classList.add('hidden');
    }

    const modal = document.getElementById('statusModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeStatusModal() {
    const modal = document.getElementById('statusModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Toggle Cuti notice on status change in modal
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('statusSelect');
    if (sel) {
        sel.addEventListener('change', (e) => {
            const cutiNotice = document.getElementById('cutiNotice');
            if (cutiNotice) {
                if (e.target.value === 'cuti') {
                    cutiNotice.classList.remove('hidden');
                } else {
                    cutiNotice.classList.add('hidden');
                }
            }
        });
    }
});
