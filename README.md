# Sistem Informasi Manajemen Pelanggan dan Tagihan WiFi Rengganis Net

Sistem berbasis web modern untuk mendigitalisasi operasional Rengganis Net: pengelolaan data pelanggan, otomatisasi pembuatan tagihan bulanan, manajemen status cuti/isolir/aktif, pelacakan pelunasan, serta pencetakan struk thermal Bluetooth (ESC/POS 58mm/80mm) dan unduh struk digital.

---

## 🚀 Fitur Utama & Kesesuaian PRD

| Kode | Fitur & Requirement | Implementasi |
|---|---|---|
| **FR-01** | **Autentikasi & Otorisasi** | Login berbasis sesi dengan enkripsi kata sandi **bcrypt**. Mendukung peran **Admin** (akses penuh) dan **Teknisi** (akses daftar pelanggan & status lapangan). |
| **FR-02** | **CRUD Data Pelanggan** | Form input lengkap: Kode Pelanggan, Nama, WhatsApp, Alamat, Paket WiFi, IP Address, Tgl Pasang, Status (Aktif/Cuti/Isolir), dan Catatan Teknis. |
| **FR-03** | **Manajemen Status "Cuti"** | Mengubah status pelanggan menjadi *Cuti* secara otomatis **mencegah penerbitan tagihan baru di bulan berikutnya tanpa denda**. Perubahan status diaudit ke tabel `status_logs`. Re-aktivasi di pertengahan bulan otomatis menerbitkan tagihan bulan berjalan. |
| **FR-04** | **Generate Tagihan Bulanan Otomatis** | Pembuatan tagihan bulanan massal untuk seluruh pelanggan *Aktif*. Pelanggan berstatus *Cuti* otomatis dilewati tanpa tagihan fiktif. Dilengkapi pencegahan duplikasi tagihan (*Guardrail Metric*). |
| **FR-05** | **Proses Pembayaran & Modal Struk** | Konfirmasi pelunasan tagihan sekali klik, mencatat metode bayar (Tunai, Transfer BCA/BRI, QRIS, DANA), tanggal bayar, dan nama kolektor/kasir. Langsung membuka modal struk pembayaran dengan 2 tombol utama. |
| **FR-06** | **Cetak Struk Thermal Bluetooth (ESC/POS)** | Terintegrasi langsung dengan **Web Bluetooth API** (GATT Service ESC/POS) untuk printer thermal mini 58mm & 80mm Bluetooth di HP Android/PC. Dilengkapi fallback layout cetak `@media print` 58mm. |
| **FR-07** | **Unduh Struk Digital (PNG & PDF)** | Render struk digital beresolusi tinggi menggunakan library **html2canvas** untuk disimpan sebagai gambar PNG dan tombol instan kirim struk via WhatsApp (`wa.me`). |
| **FR-08** | **Laporan Keuangan & Rekapitulasi** | Rekap pemasukan harian dan bulanan, pelacakan *Collection Rate* (%), rincian metode pembayaran, cetak laporan, dan fitur ekspor data ke format **CSV / Excel**. |

---

## 🛠️ Arsitektur & Teknologi

- **Backend**: PHP 8.4 (PDO MySQL, prepared statements, CSRF protection, session management)
- **Database**: MySQL 8.0 (`rengganis_net`)
- **Frontend**: Tailwind CSS, Vanilla JS, FontAwesome 6 Icons, Google Fonts (*Plus Jakarta Sans* & *JetBrains Mono*)
- **Thermal Printer Driver**: `assets/js/bluetooth-print.js` (ESC/POS command builder, chunked BLE transfer)
- **Digital Receipt Capture**: `html2canvas.min.js`
- **Lingkungan**: Laragon / Apache / CLI Built-in PHP Server

---

## 🔐 Akun Default (Demo & Pengujian)

| Peran (Role) | Username | Password | Deskripsi |
|---|---|---|---|
| **Admin Pengelola** | `admin` | `admin123` | Akses penuh: Pelanggan, Tagihan, Paket, Laporan, Pengaturan, User |
| **Teknisi Lapangan** | `teknisi` | `teknisi123` | Akses teknisi: Monitoring pelanggan aktif, cuti, dan isolir |
| **Kasir / Kolektor** | `kolektor` | `kolektor123` | Penagihan lapangan dan cetak struk |

*(Tersedia tombol bantuan pengisian instan di halaman login).*

---

## 🌐 Cara Mengakses Aplikasi

Aplikasi dapat diakses melalui browser:
1. **Via Laragon Apache**:
   ```
   http://localhost/rengganis.net/
   ```
2. **Via PHP Built-in Server**:
   ```
   http://127.0.0.1:8080/
   ```

---

## 🧪 Pengujian Otomatis

Seluruh 12 poin acceptance criteria dan functional requirements dapat diuji ulang kapan saja menggunakan skrip verifikasi otomatis:
```powershell
php tests/test_all_requirements.php
```
Hasil: **12 / 12 Pengujian Berhasil (100% Pass)**.
