# Rengganis Net

> Sistem informasi manajemen operasional, penagihan WiFi bulanan bebas tagihan fiktif cuti, dan cetak struk Bluetooth ESC/POS.

## About

Rengganis Net adalah sistem informasi berbasis web yang mendigitalisasi operasional penyedia layanan internet lokal (RT/RW Net). Sistem ini memecahkan masalah penagihan manual pintu ke pintu yang memakan banyak waktu dan tenaga, dengan menyediakan otomatisasi penerbitan tagihan bulanan bebas tagihan fiktif bagi pelanggan yang berstatus cuti, notifikasi tagihan WhatsApp terformat (opsi Online QR dan Offline Tunai Pas), serta pencetakan struk pembayaran instan via printer thermal Bluetooth ESC/POS di lapangan. Aplikasi ini dirancang untuk digunakan oleh pengelola jaringan (Admin), petugas penagih (Kolektor/Kasir), dan teknisi pemeliharaan lapangan.

## Features

Daftar fitur yang tercantum dalam spesifikasi proyek:
- **F1 - Autentikasi dan otorisasi multi-role (must)** — *Direncanakan / Siap diaudit*
- **F2 - Kelola data pelanggan dan status layanan (must)** — *Direncanakan / Siap diaudit*
- **F3 - Pembuatan tagihan bulanan otomatis (must)** — *Direncanakan / Siap diaudit*
- **F4 - Notifikasi tagihan dan pembayaran WhatsApp (must)** — *Direncanakan / Siap diaudit*
- **F5 - Pencatatan pelunasan dan cetak struk Bluetooth (must)** — *Direncanakan / Siap diaudit*
- **F6 - Laporan keuangan dan ekspor data (should)** — *Direncanakan / Siap diaudit*

## Tech Stack

- **Backend:** PHP 8.4 Native dengan PDO MySQL dan enkripsi kata sandi bcrypt.
- **Database:** MySQL 8.0 (skema relasional database `rengganis_net`).
- **Frontend:** Tailwind CSS, Vanilla JavaScript, FontAwesome 6, Google Fonts (Plus Jakarta Sans & JetBrains Mono), dan pustaka tangkapan kanvas `html2canvas`.
- **Integrasi Hardware:** Web Bluetooth API (GATT Service ESC/POS) untuk printer thermal mini 58mm & 80mm.
- **Server Lingkungan:** Laragon Apache / On-Premise Mini PC / Linux VPS.
- **Kata Kunci Stack:** `PHP`, `MySQL`, `Tailwind CSS`, `Web Bluetooth ESC/POS`, `Laragon`.

## Getting Started

Langkah-langkah menjalankan proyek pada lingkungan komputer pengembang baru:

1. **Prasyarat Sistem:**
   - Telah terpasang PHP 8.4 dengan ekstensi `pdo_mysql` aktif.
   - Telah terpasang MySQL 8.0 atau MariaDB (dapat menggunakan bundel Laragon).
2. **Kloning Repositori:**
   ```powershell
   git clone https://github.com/sipolislam/rengganis-net.git
   cd rengganis-net
   ```
3. **Instalasi Lingkungan:**
   Periksa ketersediaan runtime PHP:
   ```powershell
   php -v
   ```
4. **Konfigurasi Lingkungan:**
   Salin berkas template lingkungan `.env.example` menjadi `.env` (atau sesuaikan kredensial di `config/database.php`):
   ```powershell
   Copy-Item .env.example .env
   ```
   Pastikan pengaturan nama database (`rengganis_net`), user, dan password database telah sesuai.
5. **Inisialisasi Database:**
   Impor berkas skema awal ke MySQL:
   ```powershell
   mysql -u root -p rengganis_net < database/schema.sql
   ```
6. **Menjalankan Server Pengembangan:**
   Jalankan server bawaan PHP:
   ```powershell
   php -S 127.0.0.1:8080
   ```
   Atau jika menggunakan Laragon, buka alamat `http://localhost/rengganis.net/` pada peramban.

## Scripts and Commands

Daftar perintah resmi proyek yang berlaku identik di seluruh panduan agen, pengujian, dan CI:

| Perintah | Fungsi | Keterangan |
|---|---|---|
| `php -v` | Instal / Cek Runtime | Memastikan ketersediaan lingkungan runtime PHP pada mesin |
| `php -l index.php` | Validasi Sintaks (Lint) | Memeriksa apakah ada galat sintaks pada berkas PHP utama |
| `php tests/test_all_requirements.php` | Pengujian Kebutuhan (Test) | Menjalankan seluruh 12 poin acceptance criteria otomatis |
| `php -v` | Verifikasi Build | Memastikan lingkungan siap rilis tanpa proses kompilasi SPA |
| `php -S 127.0.0.1:8080` | Server Pengembangan (Dev) | Menjalankan web server lokal mandiri untuk pengujian |

## Project Structure

```text
rengganis.net/
├── .github/          # Alur kerja integrasi berkelanjutan (CI) dan template PR
├── api/              # Endpoint internal untuk permintaan asinkronus (AJAX)
├── assets/           # Berkas statis frontend (CSS, JS, ikon, driver cetak Bluetooth)
├── config/           # Konfigurasi sistem dan koneksi basis data PDO MySQL
├── database/         # Skema struktur tabel dan data awal (schema.sql)
├── docs/             # Paket 14 dokumen spesifikasi, arsitektur, dan panduan proyek
├── includes/         # Modul bersama (autentikasi sesi, fungsi helper, header, footer)
├── kit/              # Pembangkit paket dokumen capstone project
├── tests/            # Skrip verifikasi otomatis kebutuhan fungsional (FR-01 s/d FR-08)
├── bills.php         # Manajemen tagihan, pembuatan massal, dan modal struk bayar
├── customers.php     # Manajemen data master pelanggan dan perubahan status cuti
├── index.php         # Dasbor utama ringkasan omzet, status, dan collection rate
├── login.php         # Halaman autentikasi masuk multi-role (Admin/Teknisi/Kasir)
├── logout.php        # Penutup sesi pengguna
├── packages.php      # Manajemen daftar paket layanan internet WiFi
├── reports.php       # Laporan keuangan berkala dan ekspor CSV / Excel
├── settings.php      # Pengaturan profil instansi dan teks kuitansi struk
├── users.php         # Manajemen pengguna sistem (Admin only)
├── AGENTS.md         # Panduan utama untuk agen AI coding di editor
├── README.md         # Dokumentasi pengenalan proyek ini
└── SPEC.json         # Spesifikasi tunggal sumber kebenaran (Single Source of Truth)
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
2. Buat cabang baru sesuai format: `feat/T-nnn-nama-singkat` atau `fix/T-nnn-nama-singkat`.
3. Tulis kode yang rapi dan patuhi standar [docs/CODE_STYLE.md](file:///d:/laragon/www/rengganis.net/docs/CODE_STYLE.md).
4. Pastikan seluruh pengujian otomatis pada [docs/EVALS.md](file:///d:/laragon/www/rengganis.net/docs/EVALS.md) lulus sebelum mengajukan Pull Request.
5. Agen AI yang membantu pengkodean wajib membaca dan mematuhi panduan [AGENTS.md](file:///d:/laragon/www/rengganis.net/AGENTS.md) dan [docs/GUARDRAILS.md](file:///d:/laragon/www/rengganis.net/docs/GUARDRAILS.md).

## AI Usage Disclosure

Proyek tugas akhir ini dirancang dan dikembangkan dengan bantuan alat kecerdasan buatan (AI) Antigravity / Gemini CLI. Seluruh keputusan arsitektur, pemilihan logika bisnis, dan pengkodean ditinjau secara kritis dan dipahami sepenuhnya oleh mahasiswa. Paket dokumen spesifikasi awal dihasilkan dari `SPEC.json` untuk menjamin konsistensi dokumen teknis dan integritas sistem.

## Status

Tahap audit dan verifikasi spesifikasi awal selesai; paket dokumen lengkap telah siap dan pengerjaan berfokus pada task `T-001` (lihat [docs/PROGRESS.md](file:///d:/laragon/www/rengganis.net/docs/PROGRESS.md)).
