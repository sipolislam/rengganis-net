# Progress: Rengganis Net

Dokumen hidup. Diperbarui di **akhir setiap sesi** supaya sesi berikutnya (oleh agen atau manusia) dapat langsung melanjutkan.

## Current State

Tanggal: 2026-11-03

Task `T-003` telah berhasil di-merge ke branch `main`. Task `T-004` (Manajemen data pelanggan dan validasi kontak) telah selesai dikerjakan pada branch `feat/T-004-customer-crud`. Form CRUD pelanggan terhubung penuh ke tabel MySQL `customers` dengan relasi foreign key `packages`, dilengkapi validasi ketat nomor seluler/WhatsApp Indonesia (format 08xx/628xx), validasi format IPv4, pengecekan keunikan kode pelanggan, pengecekan ketersediaan paket aktif, pemformatan otomatis tautan `wa.me` dan display kontak, serta penambahan 2 pengujian verifikasi otomatis sehingga total pengujian kini mencapai 18/18 PASS (100%).

## Completed

| Tanggal | Task | PR/Commit | Catatan |
|---|---|---|---|
| 2026-10-25 | Dokumen proyek dihasilkan | - | Paket 16 dokumen lengkap dan pendukung git dari SPEC.json |
| 2026-10-25 | `T-001`: Audit repositori dan penyesuaian perintah | branch `chore/T-001-repo-audit` | Seluruh 12 verifikasi tes lulus 100%, sintaks PHP bersih, dan dukungan getenv ditambahkan |
| 2026-11-03 | `T-002`: Penguatan autentikasi dan otorisasi multi-role | branch `feat/T-002-auth-roles` | RBAC 3 role, proteksi CSRF, penolakan server-side 403, dan 14 test lolos |
| 2026-11-03 | `T-003`: Audit skema database MySQL dan foreign key | branch `chore/T-003-db-schema-audit` | 6 tabel relasional, foreign keys bertingkat, unique constraint idempotensi, dan 16 test lolos |

## In Progress

- `T-004`: Manajemen data pelanggan dan validasi kontak (branch `feat/T-004-customer-crud`, status `review`)

## Next Up

1. `T-005`: Pencatatan audit log status cuti pelanggan
2. `T-006`: Pembuatan batch billing engine bulanan
3. `T-007`: Pengecualian otomatis pelanggan cuti dari tagihan

## Blockers

Tidak ada

Format entri hambatan: `tanggal | task | hambatan | yang sudah dicoba | siapa yang bisa membantu`.

## Session Log

Format entri: `tanggal | siapa (manusia atau nama alat) | task | hasil | catatan untuk sesi berikutnya`.

- 2026-10-25 | Antigravity AI & Saiful Islam | Setup Dokumen | Berhasil menghasilkan paket dokumen spesifikasi lengkap, skema basis data, dan aturan operasional | Mulai pengerjaan dari task T-001 pada branch chore/T-001-repo-audit
- 2026-10-25 | Antigravity AI & Saiful Islam | T-001 | Audit repositori selesai: 12/12 tes lolos (100% Pass), sintaks PHP bersih tanpa error, database.php mendukung getenv | Siap melanjutkan task T-002 (Otorisasi multi-role)
- 2026-11-03 | Antigravity AI & Saiful Islam | T-002 | Implementasi kontrol RBAC 3 role (admin, kolektor, teknisi), proteksi CSRF, penolakan server-side 403 di routes & API, halaman error 403 khusus, dan verifikasi otomatis 14/14 PASS (100%) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-003
- 2026-11-03 | Antigravity AI & Saiful Islam | T-003 | Audit 6 tabel relasional MySQL, penambahan index komposit (`idx_bills_period`, `idx_bills_status`, `idx_customers_status`), sinkronisasi nama kolom di ARCHITECTURE.md, dan penambahan uji otomatis integritas FK & unique constraint di test_all_requirements.php (16/16 PASS) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-004
- 2026-11-03 | Antigravity AI & Saiful Islam | T-004 | Implementasi validasi nomor WhatsApp seluler Indonesia, validasi IPv4, pengecekan keunikan kode pelanggan & paket aktif, pemformatan tautan wa.me otomatis di tabel pelanggan, serta penambahan 2 pengujian verifikasi kontak dan persistensi pelanggan di test_all_requirements.php (18/18 PASS) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-005
