# AGENTS.md — Rengganis Net

> Sistem informasi manajemen operasional, penagihan WiFi bulanan bebas tagihan fiktif cuti, dan cetak struk Bluetooth ESC/POS.

File ini dibaca agen AI di setiap sesi. Jaga tetap singkat (maksimal 150 baris); detail ada di `docs/`.

## Project

Aplikasi web manajemen operasional dan penagihan WiFi RT/RW Net untuk pengelola, kasir, dan teknisi lapangan (platform: `web`). Level proses yang aktif adalah **dasar** (1 pengembang, durasi 4 minggu).

## Read First

Urutan baca dokumen proyek untuk agen:
1. `docs/PRD.md` — Kebutuhan produk dan kriteria penerimaan fitur (F1–F6).
2. `docs/ARCHITECTURE.md` — Arsitektur sistem, skema basis data, dan diagram alur data.
3. `docs/TASKS.md` — Daftar task bertahap yang harus dikerjakan.
4. `docs/PROGRESS.md` — Status terkini pekerjaan (wajib dibaca di awal sesi).
5. `docs/WORKFLOW.md` — Alur kerja branching, commit, dan pull request.
6. `docs/GUARDRAILS.md` — Batasan mutlak dan aturan keselamatan kode (wajib dibaca di awal sesi).
7. `docs/EVALS.md` — Gerbang kualitas otomatis yang dapat dicek mesin.
8. `docs/CODE_STYLE.md` — Konvensi penulisan kode PHP dan Tailwind CSS.
9. `docs/SECURITY.md` — Kebijakan otentikasi peran dan perlindungan data.
10. `docs/TESTING.md` — Strategi pengujian dan checklist uji manual.
11. `docs/DESIGN_SYSTEM.md` — Token warna, tipografi, dan panduan antarmuka.
12. `docs/DECISIONS.md` — Catatan keputusan arsitektur (ADR-001 s/d ADR-006).
13. `docs/REVIEW.md` — Catatan peninjauan mandiri berkala.
14. `docs/LESSONS.md` — Catatan evaluasi dan perbaikan kesalahan agen.

Muat dokumen saat dibutuhkan; jangan membaca semuanya jika tidak perlu, kecuali `docs/PROGRESS.md`, `docs/TASKS.md`, dan `docs/GUARDRAILS.md` di awal sesi.

## Tech Stack

- **Frontend:** Tailwind CSS, Vanilla JavaScript, FontAwesome 6, Google Fonts (Plus Jakarta Sans & JetBrains Mono), Web Bluetooth API ESC/POS, html2canvas.
- **Backend:** PHP 8.4 Native dengan PDO MySQL, session management, dan password_hash bcrypt.
- **Database:** MySQL 8.0 (database rengganis_net).
- **Auth:** Session-based authentication dengan enkripsi kata sandi bcrypt dan token proteksi CSRF.
- **Hosting:** Server On-Premise Mini PC / Laragon Apache / Linux VPS.
- **Testing:** Skrip verifikasi otomatis PHP (`php tests/test_all_requirements.php`).
- **Kata Kunci Stack:** `PHP`, `MySQL`, `Tailwind CSS`, `Web Bluetooth ESC/POS`, `Laragon`.

Stack dipilih mahasiswa: jangan mengusulkan penggantian stack; ikuti konvensi resmi teknologi ini.

## Commands

Perintah ini berasal dari `SPEC.json` (`stack.commands`) dan identik dengan `docs/TESTING.md`, `docs/EVALS.md`, dan CI.

```bash
php -v
php -l index.php
php tests/test_all_requirements.php
php -v
```

Keterangan perintah:
- `install`: `php -v` (memeriksa ketersediaan runtime PHP pada lingkungan kerja)
- `lint`: `php -l index.php` (memvalidasi sintaks kode PHP)
- `test`: `php tests/test_all_requirements.php` (menjalankan seluruh pengujian fungsional otomatis)
- `build`: `php -v` (verifikasi lingkungan siap saji tanpa kompilasi SPA)
- `dev`: `php -S 127.0.0.1:8080` (menjalankan server lokal mandiri selain Apache Laragon)

## Conventions

1. Gunakan PDO prepared statements berparameter untuk semua query SQL (cegah SQL Injection).
2. Terapkan otorisasi peran ketat di sisi server pada `includes/auth.php` sebelum memproses data.
3. Gunakan token warna primer `#1E3A8A` dan tipografi Plus Jakarta Sans / JetBrains Mono.
4. Tangani pemutusan koneksi printer Bluetooth secara anggun dengan tombol fallback cetak browser.
5. Format commit wajib mengikuti Conventional Commits (`tipe(lingkup): ringkasan`).
6. Detail lengkap mengacu pada [CODE_STYLE](file:///d:/laragon/www/rengganis.net/docs/CODE_STYLE.md) dan [DESIGN_SYSTEM](file:///d:/laragon/www/rengganis.net/docs/DESIGN_SYSTEM.md).

## Workflow Rules

1. Kerjakan tepat satu task per sesi dari `docs/TASKS.md`.
2. Mulai pekerjaan dari branch baru sesuai nama task (misal `feat/T-003-batch-billing-engine`).
3. Jalankan gerbang `EVALS.md` (`php tests/test_all_requirements.php`) sebelum menyatakan selesai.
4. Perbarui status di `docs/PROGRESS.md` pada akhir sesi kerja.
5. Catat keputusan baru di `docs/DECISIONS.md` dan kesalahan agen di `docs/LESSONS.md`.
6. Berhenti dan minta review manusia sebelum menggabungkan kode ke `main`.

## Boundaries

### Always
- Baca `docs/PROGRESS.md` dan `docs/TASKS.md` sebelum memulai sesi.
- Jalankan skrip tes otomatis setelah setiap modifikasi kode.
- Gunakan nama dan ID fitur resmi (F1–F6) dari PRD.
- Buat commit kecil yang terfokus pada satu tujuan logis.

### Ask First
- Menambah pustaka atau dependensi baru.
- Mengubah skema 6 tabel basis data MySQL.
- Mengubah keputusan arsitektur di `docs/DECISIONS.md`.
- Menghapus berkas kode yang sudah ada di repositori.

### Never
- Melakukan commit atau push langsung ke cabang `main`.
- Melakukan force push (`git push --force`) pada riwayat bersama.
- Menaruh kredensial basis data atau rahasia plain-text di dalam kode sumber.
- Menonaktifkan atau melemahkan baris assertion di `tests/test_all_requirements.php`.
- Mengarang fungsi API atau pustaka palsu yang tidak ada di dokumentasi resmi.
- Menjalankan perintah destruktif seperti penghapusan tabel MySQL tanpa izin.
- Menerbitkan tagihan bulanan baru untuk pelanggan yang berstatus "Cuti".

## Definition of Done

1. Kriteria penerimaan (*acceptance criteria*) task terpenuhi.
2. Pengujian otomatis `php tests/test_all_requirements.php` bernilai 100% PASS.
3. Linter `php -l index.php` bersih tanpa galat sintaks.
4. Tidak ada berkas rahasia atau berkas sementara pada `git diff`.
5. Berkas pelacak `docs/PROGRESS.md` dan `docs/DECISIONS.md` telah diperbarui.
