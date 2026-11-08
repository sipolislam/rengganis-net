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
if (typeof window !== 'undefined') {
    window.currentReceiptData = null;
}

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

    // Dynamic Footer Texts (T-022)
    if (receiptData.receipt_footer) {
        const fLines = receiptData.receipt_footer.split('\n').map(l => l.trim()).filter(Boolean);
        const f1 = document.getElementById('receiptCardFooterText1');
        const f2 = document.getElementById('receiptCardFooterText2');
        if (f1 && fLines[0]) f1.textContent = fLines[0];
        if (f2 && fLines[1]) f2.textContent = fLines[1];

        const thFooter = document.getElementById('thermalReceiptFooter');
        if (thFooter && fLines.length > 0) {
            thFooter.textContent = '';
            fLines.forEach(l => {
                const div = document.createElement('div');
                div.textContent = l;
                thFooter.appendChild(div);
            });
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
        
        // Update Bluetooth status bar UI (T-017)
        if (typeof updateBtStatusUI === 'function') {
            updateBtStatusUI();
        }

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
 * Switch receipt paper width (58mm / 80mm - T-016)
 */
function setReceiptPaperWidth(width) {
    const w = (parseInt(width, 10) === 80) ? 80 : 58;
    if (window.thermalPrinter) {
        window.thermalPrinter.setPaperWidth(w);
    }
    try {
        localStorage.setItem('rengganis_paper_width', String(w));
    } catch (e) {}

    const btn58 = document.getElementById('btnPaperWidth58');
    const btn80 = document.getElementById('btnPaperWidth80');
    if (btn58 && btn80) {
        if (w === 80) {
            btn80.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-blue-700 shadow-sm transition-all';
            btn58.className = 'px-2.5 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition-all';
        } else {
            btn58.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-blue-700 shadow-sm transition-all';
            btn80.className = 'px-2.5 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition-all';
        }
    }
    showToast(`Format struk diatur untuk printer thermal ${w}mm (${w === 80 ? '48' : '32'} kolom)`);
}

/**
 * Update Bluetooth Connection Status in Modal UI (R3 / T-017)
 */
function updateBtStatusUI(statusOverride = null) {
    const indicator = document.getElementById('btStatusIndicator');
    const statusText = document.getElementById('btStatusText');
    const reconnectBtn = document.getElementById('btnReconnectBluetooth');
    if (!indicator || !statusText) return;

    const status = statusOverride || (window.thermalPrinter ? window.thermalPrinter.getConnectionStatus() : { supported: false, connected: false, label: 'Tidak Didukung', deviceName: null });

    if (!status.supported) {
        indicator.className = 'led-pulse active-rose shrink-0';
        indicator.title = 'Web Bluetooth tidak didukung browser ini';
        statusText.textContent = 'Bluetooth Tidak Didukung';
        statusText.className = 'text-xs font-bold text-rose-600 truncate block';
        if (reconnectBtn) reconnectBtn.classList.add('hidden');
        return;
    }

    if (reconnectBtn) reconnectBtn.classList.remove('hidden');

    if (status.connected) {
        indicator.className = 'led-pulse active-green shrink-0';
        indicator.title = 'Terhubung ke ' + (status.deviceName || 'Printer');
        statusText.textContent = 'Terhubung: ' + (status.deviceName || 'Printer Bluetooth');
        statusText.className = 'text-xs font-bold text-emerald-700 truncate block';
    } else {
        indicator.className = 'led-pulse active-amber shrink-0';
        indicator.title = 'Bluetooth Terputus / Belum Tersambung';
        statusText.textContent = status.deviceName ? `Terputus (${status.deviceName})` : 'Siaga / Belum Tersambung';
        statusText.className = 'text-xs font-bold text-slate-600 truncate block';
    }
}

/**
 * Action: Sambung Ulang / Reconnect Printer Bluetooth (R3 / T-017)
 */
async function handleReconnectBluetooth() {
    if (!window.thermalPrinter) {
        showToast('Driver printer belum dimuat.', 'error');
        return;
    }

    const btn = document.getElementById('btnReconnectBluetooth');
    const originalContent = btn ? btn.innerHTML : '';
    const statusText = document.getElementById('btStatusText');

    try {
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-arrows-rotate animate-spin text-[11px]"></i> <span>Menyambung...</span>`;
        }
        if (statusText) {
            statusText.textContent = 'Mencari printer Bluetooth...';
            statusText.className = 'text-xs font-bold text-blue-600 truncate block';
        }

        await window.thermalPrinter.reconnect();
        updateBtStatusUI();
        showToast('Printer Bluetooth berhasil disambungkan!', 'success');
    } catch (err) {
        console.warn('Gagal reconnect printer Bluetooth:', err);
        updateBtStatusUI();
        showToast('Gagal menyambung printer: ' + (err.message || 'Perangkat tidak merespons. Pastikan printer hidup.'), 'warning');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    }
}

/**
 * Action: Fallback Cetak Dialog Browser (@media print 58mm / 80mm) (R3 / T-017)
 */
function handlePrintBrowserFallback() {
    if (!window.currentReceiptData) {
        showToast('Data struk tidak ditemukan.', 'warning');
        return;
    }

    const currentWidth = window.thermalPrinter ? (window.thermalPrinter.paperWidth || 58) : 58;
    
    // Apply width class to thermal print area
    const printArea = document.getElementById('thermalReceiptPrintArea');
    if (printArea) {
        if (parseInt(currentWidth, 10) === 80) {
            printArea.classList.add('paper-80mm');
            printArea.classList.remove('paper-58mm');
        } else {
            printArea.classList.remove('paper-80mm');
            printArea.classList.add('paper-58mm');
        }
    }

    showToast(`Membuka dialog cetak browser (${currentWidth}mm)...`, 'info');
    
    if (window.thermalPrinter && typeof window.thermalPrinter.printBrowser === 'function') {
        window.thermalPrinter.printBrowser(currentWidth);
    } else {
        window.print();
    }
}

/**
 * Action: Cetak Thermal Bluetooth ESC/POS with Graceful Fallback (R3 / T-017)
 */
async function handlePrintThermal() {
    if (!window.currentReceiptData) return;
    const btn = document.getElementById('btnPrintThermal');
    const originalText = btn ? btn.innerHTML : '';

    try {
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg> Mencetak...
            `;
        }

        if (window.thermalPrinter && window.thermalPrinter.isSupported()) {
            try {
                await window.thermalPrinter.printEscPos(window.currentReceiptData);
                updateBtStatusUI();
                showToast('Struk berhasil dicetak ke printer Bluetooth thermal!');
            } catch (btErr) {
                console.warn('Bluetooth printer direct print failed or cancelled:', btErr);
                updateBtStatusUI();
                showToast('Koneksi printer Bluetooth gagal atau dibatalkan. Mengalihkan ke Cetak Browser...', 'warning');
                // Graceful fallback to browser print dialog without crashing/hanging (R3)
                handlePrintBrowserFallback();
            }
        } else {
            // Direct print fallback for unsupported browsers
            showToast('Web Bluetooth tidak tersedia. Mengalihkan ke Cetak Browser...', 'info');
            handlePrintBrowserFallback();
        }
    } catch (e) {
        console.error('Fatal print error:', e);
        showToast('Gagal mencetak: ' + e.message, 'error');
        handlePrintBrowserFallback();
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
}

/**
 * Format and sanitize receipt filename (T-018)
 */
function getSafeReceiptFilename(billNumber) {
    const raw = String(billNumber || 'INV-0000').trim();
    const clean = raw.replace(/[^a-zA-Z0-9_-]/g, '-').replace(/-+/g, '-');
    return `Struk-${clean}.png`;
}

/**
 * In-memory HTML5 Canvas 2D Renderer for High-Resolution Digital Receipts (T-018)
 * Serves as ultra-crisp fallback if html2canvas is blocked or unavailable offline.
 */
function renderReceiptCanvasFallback(receiptData) {
    const data = receiptData || {};
    const width = 720;
    const height = 1060;
    
    let canvas;
    if (typeof document !== 'undefined' && typeof document.createElement === 'function') {
        canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
    } else {
        // Return structured mock for non-browser / unit test environment
        return {
            width,
            height,
            toDataURL: () => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        };
    }

    const ctx = canvas.getContext('2d');
    if (!ctx) return canvas;

    // Background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);

    // Decorative Hologram Top Strip
    const grad = ctx.createLinearGradient(0, 0, width, 0);
    grad.addColorStop(0, '#1E3A8A');
    grad.addColorStop(0.5, '#4F46E5');
    grad.addColorStop(1, '#06B6D4');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, width, 12);

    // Header Content
    ctx.textAlign = 'center';
    
    // Company Name
    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 30px "Plus Jakarta Sans", sans-serif';
    const compName = data.company_name || 'RENGGANIS NET';
    ctx.fillText(compName, width / 2, 70);

    // Tagline
    ctx.fillStyle = '#64748b';
    ctx.font = 'bold 15px "Plus Jakarta Sans", sans-serif';
    const compTagline = (data.company_tagline || 'SOLUSI INTERNET CEPAT & HEMAT').toUpperCase();
    ctx.fillText(compTagline, width / 2, 98);

    // Address & CS
    ctx.fillStyle = '#94a3b8';
    ctx.font = '13px "Plus Jakarta Sans", sans-serif';
    const compAddr = data.company_address || 'Jl. Raya Rengganis No. 45';
    const compPhone = data.company_phone || '0812-3456-7890';
    ctx.fillText(`${compAddr} • CS: ${compPhone}`, width / 2, 122);

    // Helper function to draw dashed line
    const drawDashedLine = (y) => {
        ctx.beginPath();
        ctx.setLineDash([6, 6]);
        ctx.moveTo(40, y);
        ctx.lineTo(width - 40, y);
        ctx.strokeStyle = '#cbd5e1';
        ctx.lineWidth = 1.5;
        ctx.stroke();
        ctx.setLineDash([]);
    };

    drawDashedLine(145);

    // Status Banner Box (LUNAS)
    // Left: Status Tagihan badge
    ctx.textAlign = 'left';
    ctx.fillStyle = '#94a3b8';
    ctx.font = 'bold 13px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('STATUS TAGIHAN', 45, 175);

    // Badge pill
    ctx.fillStyle = '#ecfdf5';
    ctx.beginPath();
    if (typeof ctx.roundRect === 'function') {
        ctx.roundRect(45, 188, 120, 36, 8);
    } else {
        ctx.rect(45, 188, 120, 36);
    }
    ctx.fill();
    ctx.strokeStyle = '#a7f3d0';
    ctx.lineWidth = 1;
    ctx.stroke();

    // Green dot
    ctx.fillStyle = '#10b981';
    ctx.beginPath();
    ctx.arc(63, 206, 5, 0, Math.PI * 2);
    ctx.fill();

    // LUNAS text
    ctx.fillStyle = '#065f46';
    ctx.font = 'bold 16px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('LUNAS', 78, 212);

    // Right: Tanggal Bayar
    ctx.textAlign = 'right';
    ctx.fillStyle = '#94a3b8';
    ctx.font = 'bold 13px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('TGL BAYAR', width - 45, 175);

    ctx.fillStyle = '#1e293b';
    ctx.font = 'bold 16px "JetBrains Mono", monospace';
    const payDate = data.payment_date || data.date || '-';
    ctx.fillText(payDate, width - 45, 208);

    drawDashedLine(245);

    // Table of Details
    const startY = 285;
    const lineSpacing = 42;
    const rows = [
        { label: 'No. Invoice', value: data.bill_number || '-', isMono: true, isBold: true, color: '#0f172a' },
        { label: 'ID Pelanggan', value: data.customer_code || '-', isMono: true, isBold: true, color: '#2563eb' },
        { label: 'Nama Pelanggan', value: data.customer_name || '-', isBold: true, color: '#0f172a' },
        { label: 'Alamat', value: data.customer_address || '-', color: '#334155' },
        { label: 'Periode', value: data.period || '-', color: '#1e293b' },
        { label: 'Paket Layanan', value: (data.package_name || '-') + (data.package_speed ? ` (${data.package_speed})` : ''), isBold: true, color: '#1d4ed8' },
        { label: 'Metode Bayar', value: data.payment_method || 'Tunai', color: '#1e293b' },
        { label: 'Kasir / Kolektor', value: data.collector_name || 'Admin', color: '#1e293b' }
    ];

    if (data.notes && String(data.notes).trim()) {
        rows.push({ label: 'Catatan', value: String(data.notes).trim(), color: '#475569', isItalic: true });
    }

    rows.forEach((r, idx) => {
        const curY = startY + (idx * lineSpacing);
        // Label (left)
        ctx.textAlign = 'left';
        ctx.fillStyle = '#64748b';
        ctx.font = '15px "Plus Jakarta Sans", sans-serif';
        ctx.fillText(r.label, 45, curY);

        // Value (right)
        ctx.textAlign = 'right';
        ctx.fillStyle = r.color || '#0f172a';
        let fontStyle = '';
        if (r.isItalic) fontStyle += 'italic ';
        if (r.isBold) fontStyle += 'bold ';
        const fontFamily = r.isMono ? '"JetBrains Mono", monospace' : '"Plus Jakarta Sans", sans-serif';
        ctx.font = `${fontStyle}16px ${fontFamily}`;

        // Truncate if too long
        let val = String(r.value || '-');
        if (val.length > 36) val = val.substring(0, 33) + '...';
        ctx.fillText(val, width - 45, curY);
    });

    const totalSectionY = startY + (rows.length * lineSpacing) + 20;
    drawDashedLine(totalSectionY);

    // Total Amount Box
    ctx.textAlign = 'left';
    ctx.fillStyle = '#334155';
    ctx.font = 'bold 16px "Plus Jakarta Sans", sans-serif';
    ctx.fillText('TOTAL PEMBAYARAN', 45, totalSectionY + 50);

    ctx.textAlign = 'right';
    ctx.fillStyle = '#1d4ed8';
    ctx.font = 'bold 28px "JetBrains Mono", monospace';
    ctx.fillText(data.amount_formatted || 'Rp 0', width - 45, totalSectionY + 52);

    // Footer divider & notes
    const footerY = totalSectionY + 95;
    ctx.beginPath();
    ctx.moveTo(40, footerY);
    ctx.lineTo(width - 40, footerY);
    ctx.strokeStyle = '#f1f5f9';
    ctx.lineWidth = 1;
    ctx.stroke();

    ctx.textAlign = 'center';
    ctx.fillStyle = '#94a3b8';
    ctx.font = '13px "Plus Jakarta Sans", sans-serif';

    let footer1 = data.receipt_footer_line1;
    let footer2 = data.receipt_footer_line2;
    if (data.receipt_footer) {
        const lines = data.receipt_footer.split('\n').map(l => l.trim()).filter(Boolean);
        if (lines[0]) footer1 = lines[0];
        if (lines[1]) footer2 = lines[1];
    }
    if (!footer1) footer1 = 'Terima kasih atas pembayaran Anda.';
    if (!footer2) footer2 = 'Simpan struk ini sebagai bukti transaksi resmi yang sah.';

    ctx.fillText(footer1, width / 2, footerY + 30);
    ctx.font = '12px "Plus Jakarta Sans", sans-serif';
    ctx.fillText(footer2, width / 2, footerY + 50);

    return canvas;
}

/**
 * Export digital receipt card as PNG image with high resolution (T-018)
 * Leverages html2canvas when available; seamlessly falls back to Canvas 2D engine.
 */
async function exportReceiptAsPng(receiptData, options = {}) {
    const data = receiptData || window.currentReceiptData;
    if (!data) throw new Error('Data struk tidak ditemukan');

    const filename = getSafeReceiptFilename(data.bill_number);
    let canvas = null;

    const receiptEl = (typeof document !== 'undefined') ? document.getElementById(options.elementId || 'digitalReceiptCard') : null;

    if (typeof html2canvas === 'function' && receiptEl) {
        try {
            canvas = await html2canvas(receiptEl, {
                scale: options.scale || 3,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                logging: false,
                scrollX: 0,
                scrollY: 0
            });
        } catch (h2cErr) {
            console.warn('html2canvas capture warning, falling back to Canvas 2D engine:', h2cErr);
            canvas = renderReceiptCanvasFallback(data);
        }
    } else {
        canvas = renderReceiptCanvasFallback(data);
    }

    if (!canvas || typeof canvas.toDataURL !== 'function') {
        throw new Error('Gagal merender gambar canvas');
    }

    const dataUrl = canvas.toDataURL('image/png', 1.0);

    // Automatic file download trigger in browser
    if (typeof document !== 'undefined' && typeof document.createElement === 'function') {
        const link = document.createElement('a');
        link.download = filename;
        link.href = dataUrl;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    return {
        success: true,
        filename: filename,
        dataUrl: dataUrl,
        canvas: canvas
    };
}

/**
 * Action: Unduh Struk Digital (PNG image via html2canvas & Canvas 2D fallback - T-018)
 */
async function handleDownloadDigitalReceipt() {
    if (!window.currentReceiptData) {
        showToast('Data struk belum siap untuk diunduh.', 'warning');
        return;
    }
    const btn = document.getElementById('btnDownloadDigital');
    const originalText = btn ? btn.innerHTML : '';

    try {
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-emerald-700 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg> Merender PNG...
            `;
        }

        const result = await exportReceiptAsPng(window.currentReceiptData, { scale: 3 });
        showToast(`Struk digital (${result.filename}) berhasil diunduh!`, 'success');
    } catch (err) {
        console.error('Download error:', err);
        showToast('Gagal membuat gambar struk: ' + (err.message || 'Terjadi kesalahan sistem'), 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
}

/**
 * Change Customer Status Modal (Aktif, Cuti, Isolir)
 */
if (typeof window !== 'undefined') {
    window.currentStatusCustomer = null;
}

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
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        const sel = document.getElementById('statusSelect');
        if (sel) {
            sel.addEventListener('change', (e) => {
                const oldStatus = window.currentStatusCustomer ? window.currentStatusCustomer.status : '';
                toggleModalStatusNotices(oldStatus, e.target.value);
            });
        }

        // Initialize paper width button state (T-016)
        try {
            const savedWidth = localStorage.getItem('rengganis_paper_width') || '58';
            if (window.thermalPrinter) {
                window.thermalPrinter.setPaperWidth(savedWidth);
            }
            const btn58 = document.getElementById('btnPaperWidth58');
            const btn80 = document.getElementById('btnPaperWidth80');
            if (btn58 && btn80 && savedWidth === '80') {
                btn80.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-blue-700 shadow-sm transition-all';
                btn58.className = 'px-2.5 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition-all';
            }
        } catch (e) {}

        // Bluetooth printer event listeners & status sync (T-017)
        if (window.thermalPrinter) {
            window.thermalPrinter.onConnectedCallback = () => {
                if (typeof updateBtStatusUI === 'function') updateBtStatusUI();
            };
            window.thermalPrinter.onDisconnectedCallback = () => {
                if (typeof updateBtStatusUI === 'function') updateBtStatusUI();
            };
            if (typeof updateBtStatusUI === 'function') updateBtStatusUI();
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
}

// Global attachments for browser environment (T-018)
if (typeof window !== 'undefined') {
    window.getSafeReceiptFilename = getSafeReceiptFilename;
    window.renderReceiptCanvasFallback = renderReceiptCanvasFallback;
    window.exportReceiptAsPng = exportReceiptAsPng;
}

// Module export for Node.js unit testing (T-018)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        getSafeReceiptFilename,
        renderReceiptCanvasFallback,
        exportReceiptAsPng
    };
}
