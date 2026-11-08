/**
 * Web Bluetooth ESC/POS Thermal Printing Driver for Rengganis Net
 * Supports 58mm (32 chars/line) & 80mm (48 chars/line) Bluetooth Thermal Printers
 * Also provides window.print() fallback with 58mm styling
 */

class BluetoothEscPosPrinter {
    constructor(defaultWidth = 58) {
        this.device = null;
        this.server = null;
        this.characteristic = null;
        this.isConnected = false;
        this.isConnecting = false;
        
        let initialWidth = defaultWidth;
        if (typeof localStorage !== 'undefined') {
            const saved = localStorage.getItem('rengganis_paper_width');
            if (saved) {
                initialWidth = parseInt(saved, 10);
            }
        }
        this.paperWidth = (initialWidth === 80) ? 80 : 58;
        this.onDisconnectedCallback = null;
        this.onConnectedCallback = null;
    }

    /**
     * Check if Web Bluetooth API is supported in current browser
     */
    isSupported() {
        return (typeof navigator !== 'undefined' && navigator.bluetooth !== undefined);
    }

    /**
     * Set thermal paper width (58mm = 32 cols, 80mm = 48 cols)
     */
    setPaperWidth(width) {
        const w = parseInt(width, 10);
        this.paperWidth = (w === 80) ? 80 : 58;
        if (typeof localStorage !== 'undefined') {
            try {
                localStorage.setItem('rengganis_paper_width', String(this.paperWidth));
            } catch (e) {}
        }
        return this.paperWidth;
    }

    /**
     * Get active thermal paper width
     */
    getPaperWidth() {
        return this.paperWidth;
    }

    /**
     * Get max columns per line for paper width
     */
    getMaxColumns(paperWidth = null) {
        const width = paperWidth || this.paperWidth;
        return (width === 80) ? 48 : 32;
    }

    /**
     * Known Bluetooth printer service UUIDs (standard & vendor-specific)
     */
    getPrinterServices() {
        return [
            '000018f0-0000-1000-8000-00805f9b34fb', // Standard Thermal Printer Service
            'e7810a71-73ae-499d-8c15-faa9aef0c3f2', // Posnet / MPT-II / Rego
            '49535343-fe7d-4ae5-8fa9-9fafd205e455', // ISSC Transparent Serial
            '0000ff00-0000-1000-8000-00805f9b34fb', // Common 58mm/80mm POS printers (ZJ-58, GOOJPRT, Panda)
            '0000fee7-0000-1000-8000-00805f9b34fb', // Tencent/WeChat IoT POS
            '0000ae30-0000-1000-8000-00805f9b34fb', // Cashino / Zhuhai
            '0000af30-0000-1000-8000-00805f9b34fb', // RPP-02N
            '0000fff0-0000-1000-8000-00805f9b34fb', // Generic BLE SPP
            '0000ffe0-0000-1000-8000-00805f9b34fb', // HM-10 / CC2541 BLE UART
            '6e400001-b5a3-f393-e0a9-e50e24dcca9e'  // Nordic Semiconductor NUS
        ];
    }

    /**
     * Register disconnection listener callback
     */
    onDisconnected(callback) {
        this.onDisconnectedCallback = callback;
    }

    /**
     * Register connection listener callback
     */
    onConnected(callback) {
        this.onConnectedCallback = callback;
    }

    /**
     * Connect to Bluetooth Thermal Printer
     */
    async connect() {
        if (!this.isSupported()) {
            throw new Error('Web Bluetooth tidak didukung pada browser ini. Silakan gunakan Google Chrome di HP Android atau PC.');
        }

        if (this.isConnecting) {
            throw new Error('Sedang dalam proses menghubungkan ke printer...');
        }

        this.isConnecting = true;

        try {
            console.log('Mencari printer Bluetooth...');
            this.device = await navigator.bluetooth.requestDevice({
                acceptAllDevices: true,
                optionalServices: this.getPrinterServices()
            });

            this.device.addEventListener('gattserverdisconnected', (event) => {
                this.isConnected = false;
                this.server = null;
                this.characteristic = null;
                console.log('Printer Bluetooth terputus');
                if (typeof this.onDisconnectedCallback === 'function') {
                    this.onDisconnectedCallback(event);
                }
            });

            console.log('Menghubungkan ke GATT Server...');
            this.server = await this.device.gatt.connect();

            // Search through possible printer services
            let foundCharacteristic = null;
            const services = this.getPrinterServices();

            for (const sUuid of services) {
                try {
                    const service = await this.server.getPrimaryService(sUuid);
                    const characteristics = await service.getCharacteristics();
                    for (const char of characteristics) {
                        if (char.properties.write || char.properties.writeWithoutResponse) {
                            foundCharacteristic = char;
                            break;
                        }
                    }
                    if (foundCharacteristic) break;
                } catch (e) {
                    // Try next service
                }
            }

            if (!foundCharacteristic) {
                // If known services failed, try getting all primary services
                try {
                    const allServices = await this.server.getPrimaryServices();
                    for (const s of allServices) {
                        const chars = await s.getCharacteristics();
                        for (const c of chars) {
                            if (c.properties.write || c.properties.writeWithoutResponse) {
                                foundCharacteristic = c;
                                break;
                            }
                        }
                        if (foundCharacteristic) break;
                    }
                } catch (e) {
                    console.error('Gagal membaca services:', e);
                }
            }

            if (!foundCharacteristic) {
                throw new Error('Karakteristik penulisan data printer tidak ditemukan.');
            }

            this.characteristic = foundCharacteristic;
            this.isConnected = true;
            console.log('Printer Bluetooth berhasil terhubung!');

            if (typeof this.onConnectedCallback === 'function') {
                this.onConnectedCallback(this.device);
            }

            return true;
        } catch (error) {
            this.isConnected = false;
            console.error('Error menghubungkan Bluetooth printer:', error);
            throw error;
        } finally {
            this.isConnecting = false;
        }
    }

    /**
     * Safely disconnect active printer connection
     */
    disconnect() {
        if (this.device && this.device.gatt && this.device.gatt.connected) {
            this.device.gatt.disconnect();
        }
        this.isConnected = false;
        this.server = null;
        this.characteristic = null;
    }

    /**
     * Helper to encode strings to Uint8Array byte array
     */
    textToBytes(text) {
        if (typeof TextEncoder !== 'undefined') {
            return Array.from(new TextEncoder().encode(text));
        }
        const bytes = [];
        for (let i = 0; i < text.length; i++) {
            const code = text.charCodeAt(i);
            bytes.push(code < 256 ? code : 63); // 63 is '?'
        }
        return bytes;
    }

    /**
     * Format a line with left and right columns padded to max line length
     */
    formatRow(left, right, maxLen = 32) {
        const leftStr = String(left || '').trim();
        const rightStr = String(right || '').trim();
        const available = maxLen - (leftStr.length + rightStr.length);
        if (available >= 1) {
            return leftStr + ' '.repeat(available) + rightStr + '\n';
        }
        if (leftStr.length + 1 <= maxLen) {
            const rightPad = Math.max(0, maxLen - rightStr.length);
            return leftStr + '\n' + ' '.repeat(rightPad) + rightStr + '\n';
        }
        return leftStr.substring(0, Math.max(1, maxLen - rightStr.length - 1)) + ' ' + rightStr + '\n';
    }

    /**
     * Format center aligned text
     */
    center(text, maxLen = 32) {
        const str = String(text || '').trim();
        if (str.length >= maxLen) return str + '\n';
        const pad = Math.floor((maxLen - str.length) / 2);
        return ' '.repeat(pad) + str + '\n';
    }

    /**
     * Wrap text into multiple lines of at most maxLen characters
     */
    wrapText(text, maxLen = 32) {
        const words = String(text || '').split(/\s+/);
        const lines = [];
        let currentLine = '';
        for (const word of words) {
            if (!word) continue;
            if ((currentLine + ' ' + word).trim().length <= maxLen) {
                currentLine = (currentLine + ' ' + word).trim();
            } else {
                if (currentLine) lines.push(currentLine);
                currentLine = word;
            }
        }
        if (currentLine) lines.push(currentLine);
        return lines;
    }

    /**
     * Build ESC/POS command buffer for receipt
     * Supports 58mm (32 cols) and 80mm (48 cols)
     */
    buildEscPosCommands(data, paperWidth = null) {
        const ESC = 0x1B;
        const GS = 0x1D;
        const LF = 0x0A;
        const maxCols = this.getMaxColumns(paperWidth);
        const divider = '-'.repeat(maxCols) + '\n';
        const doubleDivider = '='.repeat(maxCols) + '\n';

        let bytes = [];

        // 1. Initialize Printer (ESC @)
        bytes.push(ESC, 0x40);

        // 2. Character Code Table PC437 (ESC t 0)
        bytes.push(ESC, 0x74, 0x00);

        // 3. Center Align Header (ESC a 1)
        bytes.push(ESC, 0x61, 0x01);

        // Bold + Double height & width for Brand Name (GS ! 0x11)
        bytes.push(GS, 0x21, 0x11);
        bytes.push(...this.textToBytes((data.company_name || 'RENGGANIS NET') + '\n'));

        // Normal size
        bytes.push(GS, 0x21, 0x00);
        bytes.push(...this.textToBytes((data.company_tagline || 'Solusi Internet Cepat & Hemat') + '\n'));
        if (data.company_phone) {
            bytes.push(...this.textToBytes('Telp: ' + data.company_phone + '\n'));
        }
        if (data.company_address) {
            bytes.push(...this.textToBytes(data.company_address + '\n'));
        }

        // 4. Divider (Align left)
        bytes.push(ESC, 0x61, 0x00);
        bytes.push(...this.textToBytes(doubleDivider));

        // 5. Receipt Details
        bytes.push(...this.textToBytes(this.formatRow('No. Struk', data.bill_number || '-', maxCols)));
        bytes.push(...this.textToBytes(this.formatRow('Tanggal', data.payment_date || data.date || '-', maxCols)));
        bytes.push(...this.textToBytes(this.formatRow('ID Pelanggan', data.customer_code || '-', maxCols)));
        bytes.push(...this.textToBytes(this.formatRow('Nama', data.customer_name || '-', maxCols)));

        if (data.customer_address) {
            const addr = String(data.customer_address).trim();
            const addrPrefix = 'Alamat: ';
            const maxAddrLen = maxCols - addrPrefix.length;
            if (addr.length <= maxAddrLen) {
                bytes.push(...this.textToBytes(this.formatRow('Alamat', addr, maxCols)));
            } else {
                bytes.push(...this.textToBytes('Alamat:\n'));
                const wrapped = this.wrapText(addr, maxCols - 2);
                for (const line of wrapped) {
                    bytes.push(...this.textToBytes('  ' + line + '\n'));
                }
            }
        }

        bytes.push(...this.textToBytes(divider));
        bytes.push(...this.textToBytes(this.formatRow('Periode', data.period || '-', maxCols)));
        
        const pkgDisplay = (data.package_name || '-') + (data.package_speed ? ' (' + data.package_speed + ')' : '');
        bytes.push(...this.textToBytes(this.formatRow('Paket Layanan', pkgDisplay, maxCols)));
        bytes.push(...this.textToBytes(divider));

        // 6. Total & Status (Bold)
        bytes.push(ESC, 0x45, 0x01); // Bold ON
        bytes.push(...this.textToBytes(this.formatRow('TOTAL BAYAR', data.amount_formatted || 'Rp 0', maxCols)));
        bytes.push(...this.textToBytes(this.formatRow('STATUS', 'LUNAS', maxCols)));
        bytes.push(ESC, 0x45, 0x00); // Bold OFF

        bytes.push(...this.textToBytes(this.formatRow('Metode Bayar', data.payment_method || 'Tunai', maxCols)));
        if (data.collector_name) {
            bytes.push(...this.textToBytes(this.formatRow('Kasir/Kolektor', data.collector_name, maxCols)));
        }
        if (data.notes && String(data.notes).trim()) {
            bytes.push(...this.textToBytes(this.formatRow('Catatan', String(data.notes).trim(), maxCols)));
        }

        bytes.push(...this.textToBytes(doubleDivider));

        // 7. Footer (Center align)
        bytes.push(ESC, 0x61, 0x01);
        const footerText = data.receipt_footer || 'Terima kasih atas pembayaran Anda.\nSimpan struk ini sebagai bukti sah.';
        const footerLines = footerText.split('\n');
        for (const fLine of footerLines) {
            if (fLine.trim()) {
                bytes.push(...this.textToBytes(this.center(fLine.trim(), maxCols)));
            }
        }

        // 8. Feed 4 lines & partial paper cut
        bytes.push(ESC, 0x64, 0x04); // Feed 4 lines
        bytes.push(GS, 0x56, 0x42, 0x00); // Partial cut

        return new Uint8Array(bytes);
    }

    /**
     * Send raw buffer to Bluetooth Printer in safe chunked packets
     */
    async printEscPos(data, paperWidth = null) {
        if (!this.isConnected || !this.characteristic) {
            await this.connect();
        }

        const buffer = this.buildEscPosCommands(data, paperWidth);
        const chunkSize = 100; // 100 bytes per packet to avoid BLE buffer drops

        for (let i = 0; i < buffer.length; i += chunkSize) {
            const chunk = buffer.slice(i, i + chunkSize);
            if (this.characteristic.writeValueWithResponse) {
                await this.characteristic.writeValueWithResponse(chunk);
            } else if (this.characteristic.writeValueWithoutResponse) {
                await this.characteristic.writeValueWithoutResponse(chunk);
            } else {
                await this.characteristic.writeValue(chunk);
            }
            // Inter-chunk pause
            await new Promise(res => setTimeout(res, 25));
        }

        return true;
    }

    /**
     * Fallback standard print dialog for 58mm thermal
     */
    printBrowser() {
        if (typeof window !== 'undefined' && typeof window.print === 'function') {
            window.print();
        }
    }
}

// Global printer instance for browser
if (typeof window !== 'undefined') {
    window.BluetoothEscPosPrinter = BluetoothEscPosPrinter;
    window.thermalPrinter = new BluetoothEscPosPrinter();
}

// Module export for Node.js / unit testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { BluetoothEscPosPrinter };
}
