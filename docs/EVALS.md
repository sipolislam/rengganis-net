# Evals: Rengganis Net

Evals adalah kriteria lulus yang **dapat dicek mesin**. Agen AI coding dan pengembang manusia memakai dokumen ini untuk memutuskan secara objektif apakah suatu pekerjaan pengembangan telah selesai dengan benar dan layak diterima.

## Purpose

Dokumen Evals ini berfungsi sebagai gerbang verifikasi kualitas otomatis untuk proyek Rengganis Net pada level proses **dasar**. Pada tingkatan ini, seluruh kriteria penerimaan fungsional, kepatuhan arsitektur, dan kendali risiko dievaluasi secara lokal dan otomatis pada alur kerja integrasi berkelanjutan (CI) sebelum perubahan digabungkan ke cabang utama `main`.

## Quality Gates

Semua gerbang di tabel harus lulus sebelum merge ke `main`.

| Gerbang | Perintah | Kriteria lulus | Dijalankan di |
|---|---|---|---|
| Lint | `php -l index.php` | Tanpa galat | Lokal dan CI |
| Tes | `php tests/test_all_requirements.php` | Semua tes lulus | Lokal dan CI |
| Build | `php -v` | Build berhasil | Lokal dan CI |

Gerbang kualitas tambahan:
- **Validasi Spesifikasi Proyek:** Eksekusi skrip `python kit/tools/validate.py --root . --strict` menghasilkan status tanpa galat (*0 FAIL*).
- **Pemeriksaan Sintaks Modul Kritis:** Eksekusi `php -l bills.php` dan `php -l customers.php` bersih tanpa error sintaks PHP.
- **Pemeriksaan Kredensial Bersih:** Pemindaian diff Git memastikan tidak ada berkas `.env` atau kata sandi basis data yang bocor ke commit.

## Feature Acceptance Evals

| ID Fitur / Kendali | Eval (Nama Pengujian / Skenario) | Cara Dijalankan | Kriteria Lulus |
|---|---|---|---|
| **F1** | Autentikasi Pengguna & Role Check | Otomatis via `tests/test_all_requirements.php` (TEST 2) | Admin dan Teknisi terdaftar dengan password hash bcrypt valid; login dengan password salah ditolak. |
| **F2** | Manajemen Pelanggan & Status Cuti | Otomatis via `tests/test_all_requirements.php` (TEST 4) | Data pelanggan cuti tercatat di database dan riwayat perubahan status terdokumentasi di tabel `status_logs`. |
| **F3** | Batch Billing Engine & Idempotensi | Otomatis via `tests/test_all_requirements.php` (TEST 6) | Batch billing sukses men-generate tagihan baru untuk pelanggan aktif dan otomatis melewati pelanggan berstatus cuti. |
| **F4** | Format Notifikasi Tagihan WhatsApp | Manual terverifikasi via klik ikon WhatsApp di `bills.php` | Tautan `wa.me` membuka pesan terformat dengan nama, nominal, opsi bayar Online QR, dan teks bayar Offline siapkan uang tunai pas. |
| **F5** | Pencatatan Pelunasan & Modal Struk | Otomatis via `tests/test_all_requirements.php` (TEST 7) & Manual | Tagihan lunas memiliki timestamp pembayaran, nama kolektor tercatat, dan modal struk menampilkan opsi cetak Bluetooth ESC/POS. |
| **F6** | Laporan Keuangan & Ekspor CSV/Excel | Otomatis via query verifikasi data dan manual di `reports.php` | Total omzet pada laporan sesuai penjumlahan tagihan lunas dan tombol ekspor mengunduh berkas spreadsheet valid. |
| **R1** | Kendali Duplikasi Tagihan & Cuti | Otomatis via `tests/test_all_requirements.php` (TEST 6 & Guardrail) | Eksekusi ulang batch billing pada periode yang sama menghasilkan 0 tagihan baru; pelanggan cuti menghasilkan 0 tagihan baru. |
| **R2** | Kendali Template Pesan WhatsApp 2 Cabang | Manual terverifikasi via dialog rincian pesan WhatsApp | Pesan tagihan memuat cabang opsi Online (QR) dan Offline (instruksi tunai pas); tidak ada pesan ambigu. |
| **R3** | Kendali Fallback Cetak Bluetooth | Manual terverifikasi saat Bluetooth printer dinonaktifkan | Aplikasi tidak mengalami crash atau hang saat printer mati; tombol cetak browser (@media print 58mm) dan unduh PNG tetap aktif. |

## CI Configuration

Alur integrasi berkelanjutan didefinisikan pada `.github/workflows/ci.yml`:
1. **Pemicu:** Setiap pull request dan dorongan (*push*) ke cabang `main`.
2. **Urutan Langkah:**
   - Menyiapkan lingkungan runtime PHP 8.4 menggunakan action `shivammathur/setup-php@v2`.
   - Menjalankan layanan basis data MySQL 8.0 pada container CI.
   - Mengimpor skema awal basis data dari `database/schema.sql`.
   - Menjalankan pemeriksaan sintaks kode (`php -l index.php`).
   - Menjalankan seluruh pengujian fungsional terpadu (`php tests/test_all_requirements.php`).
3. **Perlindungan Cabang:** Cabang `main` dilindungi di GitHub sehingga merger hanya dapat disetujui jika seluruh langkah CI berstatus hijau (sukses).

## Regression Policy

1. Setiap bug atau regresi yang ditemukan wajib dibuatkan kasus uji yang membuktikan kegagalan tersebut sebelum kode diperbaiki.
2. Dilarang keras menonaktifkan atau mengomentari assertion pada `tests/test_all_requirements.php` untuk meloloskan CI.
3. Jika terdapat perubahan aturan bisnis yang sah (misal penambahan paket WiFi baru atau penyesuaian denda), perubahan tersebut wajib didiskusikan dan dicatat dalam `docs/DECISIONS.md`.

## Failure Handling

Jika terjadi kegagalan pada gerbang kualitas:
1. Periksa log keluaran terminal secara saksama untuk mengidentifikasi baris kode atau query yang menyebabkan kegagalan.
2. Lakukan reproduksi galat secara lokal pada lingkungan server Laragon / CLI.
3. Agen AI coding dibatasi maksimal 3 kali percobaan perbaikan mandiri untuk satu masalah; jika belum berhasil, agen wajib berhenti dan meminta arahan pengembang manusia.
4. Setiap kegagalan kritis dan solusinya dicatat secara ringkas pada berkas `docs/LESSONS.md` sebagai pembelajaran proyek.
