# Rengganis Net

> Sistem informasi manajemen operasional, penagihan WiFi bulanan bebas tagihan fiktif cuti, dan cetak struk Bluetooth ESC/POS.

## About

Rengganis Net adalah sistem informasi berbasis web yang mendigitalisasi operasional penyedia layanan internet lokal (RT/RW Net). Sistem ini memecahkan masalah penagihan manual pintu ke pintu yang memakan banyak waktu dan tenaga, dengan menyediakan otomatisasi penerbitan tagihan bulanan bebas tagihan fiktif bagi pelanggan yang berstatus cuti, notifikasi tagihan WhatsApp terformat (opsi Online QR dan Offline Tunai Pas), serta pencetakan struk pembayaran instan via printer thermal Bluetooth ESC/POS di lapangan. Aplikasi ini dirancang untuk digunakan oleh pengelola jaringan (Admin), petugas penagih (Kolektor/Kasir), dan teknisi pemeliharaan lapangan.

## Features

Seluruh 6 fitur utama (F1–F6) beserta 3 kendali risiko mutlak (R1–R3) telah selesai diimplementasikan, diaudit, dan terverifikasi 100% lulus pada pengujian otomatis:

- **F1 - Autentikasi dan otorisasi multi-role (must)** — *Selesai & Terverifikasi*
  - Kontrol akses berbasis peran (RBAC) 3 tingkat: Admin Pengelola, Kolektor/Kasir, dan Teknisi Lapangan.
  - Penyimpanan kredensial berstandar industri menggunakan `password_hash` bcrypt.
  - Proteksi anti-CSRF token pada setiap form POST dan endpoint API asinkronus (AJAX).
  - Penolakan otorisasi di sisi server dengan kode status HTTP 403 Forbidden untuk rute terproteksi.
- **F2 - Kelola data pelanggan dan status layanan (must)** — *Selesai & Terverifikasi*
  - Manajemen data master pelanggan (kode pelanggan otomatis, nama, kontak, alamat, paket WiFi, IP statis).
  - Tiga status siklus hidup langganan: `aktif`, `cuti` (libur sementara), dan `isolir` (nonaktif tunggakan).
  - Validasi ketat nomor kontak WhatsApp Indonesia (`08xx` atau `628xx`) dengan tautan instan `wa.me`.
  - Pencatatan riwayat audit perubahan status secara otomatis ke tabel `status_logs` (audit trail).
  - Pencarian cepat terindeks multi-parameter (nama, kode pelanggan, IP, kontak) dan filter paket.
  - Tampilan dwimode responsif: *Desktop Table View* dan *Mobile Field Cards* untuk layar sentuh ponsel.
- **F3 - Pembuatan tagihan bulanan otomatis (must)** — *Selesai & Terverifikasi*
  - Mesin penagihan massal (*batch billing engine*) bulanan dengan penomoran invoice resmi `INV/YYYYMM/XXXX`.
  - Tanggal jatuh tempo dinamis yang dapat disesuaikan pada konfigurasi sistem.
  - **Kendali Risiko R1 (0 Tagihan Fiktif):** Pengecualian mutlak pelanggan berstatus "Cuti" dari tagihan baru bulanan secara otomatis.
  - **Idempotensi Penuh:** Proteksi ganda pada level logika aplikasi dan integritas basis data melalui unique constraint `(customer_id, period_month, period_year)` SQLSTATE 23000 untuk mencegah tagihan ganda.
  - **Reaktivasi Cuti:** Fitur pemulihan status dari cuti ke aktif yang otomatis menerbitkan tagihan bulan berjalan secara proporsional.
- **F4 - Notifikasi tagihan dan pembayaran WhatsApp (must)** — *Selesai & Terverifikasi*
  - Template pesan WhatsApp tagihan berformat rapi dengan **Kendali Risiko R2 (Dual-Option)**: Opsi 1 Transfer/QRIS Online & Opsi 2 Pembayaran Tunai Pas Langsung di Lokasi.
  - Tombol aksi sekali-klik yang langsung mengarahkan pesan ke WhatsApp Web atau aplikasi WhatsApp seluler.
  - Template kuitansi pelunasan sah dengan nomor transaksi resmi pasca konfirmasi pembayaran.
- **F5 - Pencatatan pelunasan dan cetak struk Bluetooth (must)** — *Selesai & Terverifikasi*
  - Proses konfirmasi pelunasan multi-metode (Tunai, Transfer BCA/BRI/Mandiri, QRIS, Dompet Digital) dengan pencatatan stempel waktu dan identitas kasir/kolektor.
  - Modal antarmuka struk pembayaran bergaya modern (pratinjau kartu digital dan simulasi kertas thermal 58mm/80mm).
  - Driver Web Bluetooth API ESC/POS bawaan dengan segmentasi transmisi data (*chunked transmission* 100 bytes) untuk mencegah buffer drop pada koneksi BLE.
  - **Kendali Risiko R3 (Anti-Hang & Fallback):** Sistem tidak berhenti atau macet saat Bluetooth terputus, dilengkapi fallback cetak browser standar (`@media print` 58mm/80mm) dan tombol sambung ulang (*reconnect*).
  - Konversi struk digital menjadi berkas gambar PNG tajam resolusi tinggi melalui pustaka `html2canvas` dan fallback Canvas 2D native.
  - Optimalisasi antarmuka mobile lapangan untuk smartphone Android di bawah terik sinar matahari (*high contrast outdoor*, target sentuh nyaman min 44px).
- **F6 - Laporan keuangan dan ekspor data (should)** — *Selesai & Terverifikasi*
  - Dasbor analitik eksekutif dengan metrik KPI real-time: total omzet masuk, sisa piutang tertunggak, persentase tertagih (*collection rate*), dan ringkasan metode bayar.
  - Filter laporan keuangan fleksibel: mode rentang tanggal kustom (*date range picker*) atau rekapitulasi bulanan per tahun.
  - Filter lanjutan berdasarkan status pembayaran, metode pembayaran, dan petugas penagih.
  - Pratinjau cetak laporan fisik yang tersinkronisasi presisi dengan parameter filter aktif.
  - Ekspor data transaksi keuangan ke format CSV dan Microsoft Excel (HTML Spreadsheet ber-BOM UTF-8) lengkap dengan baris ringkasan grand total dan KPI.

## Tech Stack

Sistem Rengganis Net dibangun dengan tumpukan teknologi modern, ringan, dan tanpa dependensi rumit:

- **Backend:** PHP 8.4 Native dengan PDO MySQL, session management aman, dan enkripsi kata sandi `password_hash` bcrypt.
- **Database:** MySQL 8.0 (skema relasional 6 tabel: `users`, `packages`, `customers`, `bills`, `status_logs`, `settings`).
- **Frontend:** Tailwind CSS (Play CDN), Neo-Skeuomorphic Modern UI, Vanilla JavaScript ES6+, FontAwesome 6, Google Fonts (Plus Jakarta Sans & JetBrains Mono), dan pustaka tangkapan kanvas `html2canvas`.
- **Integrasi Hardware:** Web Bluetooth API (GATT Service ESC/POS) untuk printer thermal mini 58mm & 80mm.
- **Server Lingkungan:** Laragon Apache / PHP Built-in Server / Linux VPS / On-Premise Mini PC.
- **Kata Kunci Stack:** `PHP`, `MySQL`, `Tailwind CSS`, `Web Bluetooth ESC/POS`, `Laragon`.

## Getting Started

Langkah-langkah menjalankan proyek pada lingkungan komputer baru:

1. **Prasyarat Sistem:**
   - Telah terpasang PHP 8.4 (atau PHP 8.1+) dengan ekstensi `pdo_mysql` aktif.
   - Telah terpasang MySQL 8.0 atau MariaDB (dapat menggunakan paket Laragon).
   - Peramban modern berbasis Chromium (Google Chrome, Microsoft Edge, atau Opera) dengan dukungan Web Bluetooth API untuk fitur cetak thermal Bluetooth.

2. **Kloning Repositori:**
   ```powershell
   git clone https://github.com/sipolislam/rengganis-net.git
   cd rengganis.net
   ```

3. **Instalasi Lingkungan:**
   Periksa ketersediaan runtime PHP pada terminal kerja:
   ```powershell
   php -v
   ```

4. **Konfigurasi Lingkungan:**
   Salin berkas template konfigurasi `.env.example` menjadi `.env` (atau sesuaikan kredensial di `config/database.php`):
   ```powershell
   Copy-Item .env.example .env
   ```
   Pastikan pengaturan koneksi database MySQL telah sesuai:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=rengganis_net
   DB_USER=root
   DB_PASS=
   ```

5. **Inisialisasi Database:**
   Buat basis data dan impor skema awal beserta data benih (*seed data*) ke MySQL:
   ```powershell
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS rengganis_net CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p rengganis_net < database/schema.sql
   ```

6. **Menjalankan Server Pengembangan:**
   Jalankan server bawaan PHP:
   ```powershell
   php -S 127.0.0.1:8080
   ```
   Atau jika menggunakan bundel Laragon, letakkan folder repositori di `C:\laragon\www\rengganis.net` dan buka alamat `http://localhost/rengganis.net/` atau `http://rengganis.net.test/` pada peramban web.

7. **Kredensial Masuk Awal (Default Accounts):**
   Sistem telah dilengkapi akun bawaan untuk pengujian ketiga peran pengguna:
   - **Admin Pengelola:** Username: `admin` | Password: `admin123`
   - **Kolektor / Kasir:** Username: `kolektor` | Password: `kolektor123`
   - **Teknisi Lapangan:** Username: `teknisi` | Password: `teknisi123`

## Scripts and Commands

Daftar perintah resmi proyek yang berlaku identik di seluruh panduan agen, pengujian, dan CI:

| Perintah | Fungsi | Keterangan |
|---|---|---|
| `php -v` | Instal / Cek Runtime | Memastikan ketersediaan lingkungan runtime PHP pada mesin |
| `php -l index.php` | Validasi Sintaks (Lint) | Memeriksa apakah ada galat sintaks pada berkas PHP utama |
| `php tests/test_all_requirements.php` | Pengujian Kebutuhan (Test) | Menjalankan seluruh 12 kelompok pengujian fungsional otomatis (36 sub-pengujian) |
| `php -v` | Verifikasi Build | Memastikan lingkungan siap rilis tanpa proses kompilasi SPA |
| `php -S 127.0.0.1:8080` | Server Pengembangan (Dev) | Menjalankan web server lokal mandiri untuk pengujian |

## Project Structure

```text
rengganis.net/
├── .github/              # Alur kerja integrasi berkelanjutan (CI) dan template Pull Request
├── api/                  # Endpoint REST internal untuk permintaan asinkronus AJAX
│   ├── bills.php         # API bayar tagihan, WhatsApp template, dan pratinjau struk
│   ├── customers.php     # API pembaruan status langganan dan audit log
│   ├── dashboard.php     # API agregasi metrik omzet dan collection rate
│   └── packages.php      # API varian paket layanan bandwidth
├── assets/               # Berkas statis frontend
│   ├── css/              # custom.css (Neo-Skeuomorphic, kontras outdoor, print @media)
│   └── js/               # Driver Bluetooth ESC/POS (bluetooth-print.js) dan interaksi app (app.js)
├── config/               # Konfigurasi koneksi basis data PDO MySQL (database.php)
├── database/             # Skema basis data 6 tabel dan data benih (schema.sql)
├── docs/                 # Paket 14 dokumen spesifikasi, arsitektur, dan panduan proyek
├── includes/             # Modul bersama (auth RBAC, helpers, layout header & footer)
├── kit/                  # Alat verifikasi dan perkakas pembuat spesifikasi proyek
├── tests/                # Skrip verifikasi otomatis 12 kriteria kebutuhan (test_all_requirements.php)
├── bills.php             # Antarmuka penagihan bulanan, pencarian invoice, dan aksi pelunasan
├── customers.php         # Antarmuka manajemen pelanggan, filter paket, dan audit status cuti
├── index.php             # Dasbor analitik omzet, persentase tertagih, dan ringkasan kasir
├── login.php             # Halaman autentikasi masuk multi-role berproteksi CSRF
├── logout.php            # Skrip penutup sesi pengguna
├── packages.php          # Antarmuka manajemen paket bandwidth WiFi dan tarif
├── print-report.php      # Pratinjau cetak fisik laporan keuangan tersinkronisasi
├── reports.php           # Antarmuka laporan keuangan, filter tanggal, dan ekspor CSV/Excel
├── settings.php          # Pengaturan identitas usaha, kontak, dan footer kuitansi struk
├── users.php             # Antarmuka manajemen akun pengguna dan hak akses peran (Admin only)
├── AGENTS.md             # Panduan ringkas agen AI coding saat bekerja di editor
├── README.md             # Dokumentasi utama repositori proyek ini
└── SPEC.json             # Sumber kebenaran tunggal (Single Source of Truth)
```

## Documentation

Tautan lengkap ke seluruh dokumen proyek pada folder `docs/` dan root:

| Dokumen | Fungsi Utama |
|---|---|
| [SPEC.json](file:///d:/laragon/www/rengganis.net/SPEC.json) | Sumber kebenaran tunggal (*Single Source of Truth*) spesifikasi sistem |
| [AGENTS.md](file:///d:/laragon/www/rengganis.net/AGENTS.md) | Panduan instruksi kerja cepat agen AI coding di setiap sesi |
| [PRD.md](file:///d:/laragon/www/rengganis.net/docs/PRD.md) | Kebutuhan produk, persona pengguna, dan acceptance criteria fitur F1–F6 |
| [ARCHITECTURE.md](file:///d:/laragon/www/rengganis.net/docs/ARCHITECTURE.md) | Arsitektur web SSR, skema 6 tabel database, dan diagram sistem |
| [DESIGN_SYSTEM.md](file:///d:/laragon/www/rengganis.net/docs/DESIGN_SYSTEM.md) | Token desain warna Navy & Amber, tipografi, dan panduan aksesibilitas |
| [SECURITY.md](file:///d:/laragon/www/rengganis.net/docs/SECURITY.md) | Model ancaman, hak akses multi-role, dan checklist OWASP Top 10 |
| [CODE_STYLE.md](file:///d:/laragon/www/rengganis.net/docs/CODE_STYLE.md) | Standar penulisan kode PHP, konvensi penamaan, dan pola terlarang |
| [TESTING.md](file:///d:/laragon/www/rengganis.net/docs/TESTING.md) | Rencana pengujian, checklist manual, dan Definition of Done |
| [EVALS.md](file:///d:/laragon/www/rengganis.net/docs/EVALS.md) | Gerbang evaluasi kelayakan kode yang dapat dicek mesin |
| [WORKFLOW.md](file:///d:/laragon/www/rengganis.net/docs/WORKFLOW.md) | Alur kerja branching git, sesi kerja, dan aturan Pull Request |
| [GUARDRAILS.md](file:///d:/laragon/www/rengganis.net/docs/GUARDRAILS.md) | Batasan mutlak keselamatan kode dan kebijakan prompt injection |
| [TASKS.md](file:///d:/laragon/www/rengganis.net/docs/TASKS.md) | Daftar 26 task bertahap dari audit hingga penyempurnaan rilis |
| [PROGRESS.md](file:///d:/laragon/www/rengganis.net/docs/PROGRESS.md) | Catatan pelacak status pekerjaan yang diperbarui setiap sesi |
| [DECISIONS.md](file:///d:/laragon/www/rengganis.net/docs/DECISIONS.md) | Catatan keputusan arsitektur (ADR-001 s/d ADR-006) dan usulan asisten |
| [REVIEW.md](file:///d:/laragon/www/rengganis.net/docs/REVIEW.md) | Daftar periksa peninjauan kode mandiri dan template evaluasi PR |
| [LESSONS.md](file:///d:/laragon/www/rengganis.net/docs/LESSONS.md) | Refleksi kendala teknis dan pelajaran penting proyek |

## Contributing

Pengembangan sistem Rengganis Net mengikuti alur kerja yang teratur:
1. Pilih task berstatus `todo` dari [docs/TASKS.md](file:///d:/laragon/www/rengganis.net/docs/TASKS.md).
2. Buat cabang baru sesuai format: `feat/T-nnn-nama-singkat`, `fix/T-nnn-nama-singkat`, atau `docs/T-nnn-nama-singkat`.
3. Tulis kode yang rapi dan patuhi standar [docs/CODE_STYLE.md](file:///d:/laragon/www/rengganis.net/docs/CODE_STYLE.md).
4. Pastikan seluruh pengujian otomatis pada [docs/EVALS.md](file:///d:/laragon/www/rengganis.net/docs/EVALS.md) (`php tests/test_all_requirements.php`) bernilai 100% PASS sebelum mengajukan perubahan.
5. Agen AI yang membantu pengkodean wajib membaca dan mematuhi panduan [AGENTS.md](file:///d:/laragon/www/rengganis.net/AGENTS.md) dan [docs/GUARDRAILS.md](file:///d:/laragon/www/rengganis.net/docs/GUARDRAILS.md).

## AI Usage Disclosure

Proyek tugas akhir ini dirancang dan dikembangkan dengan bantuan alat kecerdasan buatan (AI) Antigravity / Gemini CLI. Seluruh keputusan arsitektur, pemilihan logika bisnis, dan pengkodean ditinjau secara kritis dan dipahami sepenuhnya oleh mahasiswa. Paket dokumen spesifikasi awal dihasilkan dari `SPEC.json` untuk menjamin konsistensi dokumen teknis dan integritas sistem.

## Status

Seluruh 24 task implementasi teknis dan pengujian (Task `T-001` hingga `T-024`) telah selesai 100% dan terverifikasi secara menyeluruh. Pengujian otomatis `php tests/test_all_requirements.php` menghasilkan 100% PASS (12/12 kriteria kebutuhan PRD dan 36/36 sub-pengujian sukses), validasi kit bernilai 21 PASS (0 FAIL, 0 WARN), dan sistem siap digunakan secara operasional. Rincian riwayat sesi pengerjaan dapat dilihat pada [docs/PROGRESS.md](file:///d:/laragon/www/rengganis.net/docs/PROGRESS.md).
