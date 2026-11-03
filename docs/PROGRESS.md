# Progress: Rengganis Net

Dokumen hidup. Diperbarui di **akhir setiap sesi** supaya sesi berikutnya (oleh agen atau manusia) dapat langsung melanjutkan.

## Current State

Tanggal: 2026-11-03

Task `T-007` telah berhasil di-merge ke branch `main`. Task `T-008` (Pencegahan duplikasi tagihan bulanan / idempotensi) telah selesai dikerjakan pada branch `feat/T-008-billing-idempotency`. Eksekusi ulang proses generate tagihan pada periode yang sama terbukti 100% idempoten menghasilkan 0 tagihan baru, melacak jumlah tagihan aktif yang telah ada (`skipped`), dan menangani pencegahan duplikasi di dua lapisan: tingkat aplikasi (query pre-filter & pengecekan `generateCustomerBill`) dan tingkat database (relational unique constraint `unique_customer_period` yang memblokir SQLSTATE 23000 secara elegan). Respons API `api/bills.php` secara otomatis mendeteksi eksekusi ulang dan menginformasikan bahwa seluruh tagihan periode sudah terbit tanpa tagihan ganda. Antarmuka `bills.php` menampilkan status kelengkapan penerbitan tagihan dan kartu notifikasi proteksi duplikasi. Seluruh 22 verifikasi pada `tests/test_all_requirements.php` berhasil 100% PASS.

## Completed

| Tanggal | Task | PR/Commit | Catatan |
|---|---|---|---|
| 2026-10-25 | Dokumen proyek dihasilkan | - | Paket 16 dokumen lengkap dan pendukung git dari SPEC.json |
| 2026-10-25 | `T-001`: Audit repositori dan penyesuaian perintah | branch `chore/T-001-repo-audit` | Seluruh 12 verifikasi tes lulus 100%, sintaks PHP bersih, dan dukungan getenv ditambahkan |
| 2026-11-03 | `T-002`: Penguatan autentikasi dan otorisasi multi-role | branch `feat/T-002-auth-roles` | RBAC 3 role, proteksi CSRF, penolakan server-side 403, dan 14 test lolos |
| 2026-11-03 | `T-003`: Audit skema database MySQL dan foreign key | branch `chore/T-003-db-schema-audit` | 6 tabel relasional, foreign keys bertingkat, unique constraint idempotensi, dan 16 test lolos |
| 2026-11-03 | `T-004`: Manajemen data pelanggan dan validasi kontak | branch `feat/T-004-customer-crud` | Validasi kontak WA 08xx/628xx, format IPv4, link wa.me, dan 18 test lolos |
| 2026-11-03 | `T-005`: Pencatatan audit log status cuti pelanggan | branch `feat/T-005-status-cuti-logging` | Riwayat audit status_logs tersimpan otomatis, modal AJAX terproteksi CSRF, 19 test lolos |
| 2026-11-03 | `T-006`: Pembuatan batch billing engine bulanan | branch `feat/T-006-monthly-billing-engine` | Penomoran resmi INV/YYYYMM/XXXX berbasis transaksi aman, due_date dinamis, 20 test lolos |
| 2026-11-03 | `T-007`: Pengecualian otomatis pelanggan cuti dari tagihan | branch `feat/T-007-skip-cuti-billing` | Mutlak 0 tagihan fiktif, helper isCustomerExemptFromBilling, guardrail 11, 21 test lolos |

## In Progress

- `T-008`: Pencegahan duplikasi tagihan bulanan (idempotensi) (branch `feat/T-008-billing-idempotency`, status `review`)

## Next Up

1. `T-009`: Reaktivasi pelanggan cuti dengan tagihan bulan berjalan
2. `T-010`: Manajemen paket layanan internet WiFi
3. `T-011`: Pencarian pelanggan dan filter status tagihan

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
- 2026-11-03 | Antigravity AI & Saiful Islam | T-005 | Implementasi pencatatan otomatis riwayat audit status_logs (status lama, status baru, alasan, user, timestamp), integrasi CSRF pada modal status footer.php, pencatatan log pendaftaran cuti/isolir, dan penambahan uji otomatis verifikasi audit status di test_all_requirements.php (19/19 PASS) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-006
- 2026-11-03 | Antigravity AI & Saiful Islam | T-006 | Penyempurnaan batch billing engine: penomoran otomatis INV/YYYYMM/XXXX berbasis transaksi aman, integrasi billing_due_day dinamis, perhitungan akhir bulan akurat, validasi parameter periode API, dan penambahan pengujian TEST 6.1 (20/20 PASS) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-007
- 2026-11-03 | Antigravity AI & Saiful Islam | T-007 | Penegakan pengecualian mutlak pelanggan cuti dari tagihan (0 tagihan fiktif), helper isCustomerExemptFromBilling, pelacakan kode pelanggan cuti terlewat, kartu notifikasi proteksi tagihan fiktif di bills.php, dan pengujian TEST 6.2 (21/21 PASS) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-008
- 2026-11-03 | Antigravity AI & Saiful Islam | T-008 | Penjaminan idempotensi batch billing engine: pelacakan tagihan terbit vs aktif, proteksi ganda aplikasi dan database unique_customer_period (SQLSTATE 23000), respons cerdas API deteksi re-run, tombol & notifikasi kelengkapan tagihan di bills.php, dan pengujian TEST 6.3 (22/22 PASS) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-009
