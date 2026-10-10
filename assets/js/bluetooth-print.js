/**
 * Web Bluetooth ESC/POS Thermal Printing Driver for Rengganis Net
 * Supports 58mm (32 chars/line) & 80mm (48 chars/line) Bluetooth Thermal Printers
 * Also provides window.print() fallback with 58mm styling
 */

class BluetoothEscPosPrinter {
    constructor() {
        this.device = null;
        this.server = null;
        this.characteristic = null;
        this.isConnected = false;
        this.paperWidth = 58; // 58mm default (32 columns)
    }

    /**
     * Check if Web Bluetooth API is supported in current browser
     */
    isSupported() {
        return navigator.bluetooth !== undefined;
    }

    /**
     * Known Bluetooth printer service UUIDs
     */
    getPrinterServices() {
        return [
            '000018f0-0000-1000-8000-00805f9b34fb', // Standard thermal printer service
            'e7810a71-73ae-499d-8c15-faa9aef0c3f2', // Posnet / MPT-II
            '49535343-fe7d-4ae5-8fa9-9fafd205e455', // ISSC Transparent
            '0000ff00-0000-1000-8000-00805f9b34fb', // Common chinese 58mm printers
            '0000ae30-0000-1000-8000-00805f9b34fb',
            '0000fee7-0000-1000-8000-00805f9b34fb'
        ];
    }

    /**
     * Connect to Bluetooth Printer
     */
    async connect() {
        if (!this.isSupported()) {
            throw new Error('Web Bluetooth tidak didukung pada browser ini. Silakan gunakan Google Chrome di HP Android atau PC.');
        }

        try {
            console.log('Mencari printer Bluetooth...');
            this.device = await navigator.bluetooth.requestDevice({
                acceptAllDevices: true,
                optionalServices: this.getPrinterServices()
            });

            this.device.addEventListener('gattserverdisconnected', () => {
                this.isConnected = false;
                this.server = null;
                this.characteristic = null;
                console.log('Printer terputus');
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
            return true;
        } catch (error) {
            this.isConnected = false;
            console.error('Error menghubungkan Bluetooth printer:', error);
            throw error;
        }
    }

    /**
     * Helper to encode strings to Uint8Array ASCII / Windows-1252
     */
    textToBytes(text) {
        const encoder = new TextEncoder();
        return encoder.encode(text);
    }

    /**
     * Format a line with left and right columns padded to max line length (e.g. 32 chars for 58mm)
     */
    formatRow(left, right, maxLen = 32) {
        const leftStr = String(left || '');
        const rightStr = String(right || '');
        const spacesNeeded = maxLen - (leftStr.length + rightStr.length);
        if (spacesNeeded <= 0) {
            return leftStr + ' ' + rightStr + '\n';
        }
        return leftStr + ' '.repeat(spacesNeeded) + rightStr + '\n';
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
     * Build ESC/POS command buffer for receipt
     */
    buildEscPosCommands(data) {
        const ESC = 0x1B;
        const GS = 0x1D;
        const LF = 0x0A;
        const maxCols = this.paperWidth === 80 ? 48 : 32;
        const divider = '-'.repeat(maxCols) + '\n';
        const doubleDivider = '='.repeat(maxCols) + '\n';

        let bytes = [];

        // 1. Initialize Printer (ESC @)
        bytes.push(ESC, 0x40);

        // 2. Center Align Header (ESC a 1)
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

        // 3. Divider
        bytes.push(ESC, 0x61, 0x00); // Align left
        bytes.push(...this.textToBytes(doubleDivider));

        // 4. Receipt Details
        bytes.push(...this.textToBytes(this.formatRow('No. Struk', data.bill_number, maxCols)));
        bytes.push(...this.textToBytes(this.formatRow('Tanggal', data.payment_date || data.date, maxCols)));
        bytes.push(...this.textToBytes(this.formatRow('ID Pelanggan', data.customer_code, maxCols)));
        bytes.push(...this.textToBytes(this.formatRow('Nama', data.customer_name, maxCols)));
        if (data.customer_address) {
            const shortAddr = data.customer_address.length > 20 ? data.customer_address.substring(0, 19) + '..' : data.customer_address;
            bytes.push(...this.textToBytes(this.formatRow('Alamat', shortAddr, maxCols)));
        }

        bytes.push(...this.textToBytes(divider));
        bytes.push(...this.textToBytes(this.formatRow('Periode', data.period, maxCols)));
        bytes.push(...this.textToBytes(this.formatRow('Paket', data.package_name || data.package_speed, maxCols)));
        bytes.push(...this.textToBytes(divider));

        // 5. Total & Status (Bold)
        bytes.push(ESC, 0x45, 0x01); // Bold ON
        bytes.push(...this.textToBytes(this.formatRow('TOTAL BAYAR', data.amount_formatted, maxCols)));
        bytes.push(...this.textToBytes(this.formatRow('STATUS', 'LUNAS', maxCols)));
        bytes.push(ESC, 0x45, 0x00); // Bold OFF

        bytes.push(...this.textToBytes(this.formatRow('Metode', data.payment_method || 'Tunai', maxCols)));
        if (data.collector_name) {
            bytes.push(...this.textToBytes(this.formatRow('Kasir/Kolektor', data.collector_name, maxCols)));
        }

        bytes.push(...this.textToBytes(doubleDivider));

        // 6. Footer (Center align)
        bytes.push(ESC, 0x61, 0x01);
        bytes.push(...this.textToBytes((data.receipt_footer || 'Terima kasih atas pembayaran Anda.\nSimpan struk ini sebagai bukti sah.') + '\n'));

        // 7. Feed 4 lines & paper cut
        bytes.push(ESC, 0x64, 0x04); // Feed 4 lines
        bytes.push(GS, 0x56, 0x42, 0x00); // Partial cut

        return new Uint8Array(bytes);
    }

    /**
     * Send raw buffer to Bluetooth Printer in safe chunked packets
     */
    async printEscPos(data) {
        if (!this.isConnected || !this.characteristic) {
            await this.connect();
        }

        const buffer = this.buildEscPosCommands(data);
        const chunkSize = 100; // 100 bytes per packet to avoid BLE buffer drops

        for (let i = 0; i < buffer.length; i += chunkSize) {
            const chunk = buffer.slice(i, i + chunkSize);
            if (this.characteristic.writeValueWithResponse) {
                await this.characteristic.writeValueWithResponse(chunk);
            } else {
                await this.characteristic.writeValue(chunk);
            }
            // Small pause between chunks
            await new Promise(res => setTimeout(res, 25));
        }

        return true;
    }

    /**
     * Fallback standard print dialog for 58mm thermal
     */
    printBrowser() {
        window.print();
    }
}

// Global printer instance
window.thermalPrinter = new BluetoothEscPosPrinter();
