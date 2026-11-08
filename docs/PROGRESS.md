# Progress: Rengganis Net

Dokumen hidup. Diperbarui di **akhir setiap sesi** supaya sesi berikutnya (oleh agen atau manusia) dapat langsung melanjutkan.

## Current State

Tanggal: 2026-11-08

Task `T-012` telah berhasil di-merge ke branch `main`. Task `T-013` (Template pesan WhatsApp konfirmasi pelunasan) telah selesai dikerjakan pada branch `feat/T-013-wa-receipt-template`. Template kuitansi digital WhatsApp resmi telah diimplementasikan memuat nomor transaksi invoice (`bill_number`), identitas pelanggan, detail paket, nominal pelunasan, metode pembayaran, stempel waktu, nama kolektor/kasir, dan footer tanda sah transaksi. Tombol Kuitansi WA terintegrasi pada `bills.php` untuk tagihan berstatus lunas, modal struk `footer.php` melalui `assets/js/app.js`, serta endpoint API `api/bills.php` (`action=get_wa_receipt` & `action=get_receipt`). Pengujian otomatis `TEST 4.2` di `tests/test_all_requirements.php` memverifikasi seluruh komponen teks kuitansi dan URL `wa.me` lulus 100% (27/27 PASS).

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
| 2026-11-03 | `T-008`: Pencegahan duplikasi tagihan bulanan (idempotensi) | branch `feat/T-008-billing-idempotency` | Idempotensi 100%, unique constraint trapping (SQLSTATE 23000), 22 test lolos |
| 2026-11-03 | `T-009`: Reaktivasi pelanggan cuti dengan tagihan bulan berjalan | branch `feat/T-009-reaktivasi-cuti` | Tagihan otomatis terbit saat cuti -> aktif, notifikasi modal reaktivasi, 23 test lolos |
| 2026-11-03 | `T-010`: Manajemen paket layanan internet WiFi | branch `feat/T-010-packages-management` | Form CRUD paket WiFi, is_active toggle, referential safety, api/packages.php, 24 test lolos |
| 2026-11-08 | `T-011`: Pencarian pelanggan dan filter status tagihan | branch `feat/T-011-filter-search` | Pencarian kode/nama/kontak/IP & filter paket di customers.php, filter status lunas/belum lunas & invoice di bills.php, API search, safe PDO params, 25 test lolos |
| 2026-11-08 | `T-012`: Template pesan WhatsApp tagihan dengan 2 cabang opsi bayar | branch `feat/T-012-wa-notification-template` | Template WhatsApp dual-channel (Online QR & Offline Tunai Pas), tombol aksi WA di bills.php, api/bills.php get_wa_billing, 26 test lolos |

## In Progress

- `T-011`: Pencarian pelanggan dan filter status tagihan (branch `feat/T-011-filter-search`, status `review`)

## Next Up

1. `T-012`: Template pesan WhatsApp tagihan dengan 2 cabang opsi bayar
2. `T-013`: Template pesan WhatsApp konfirmasi pelunasan
3. `T-014`: Proses konfirmasi pelunasan multi-metode bayar

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
- 2026-11-03 | Antigravity AI & Saiful Islam | T-009 | Otomasi penerbitan tagihan periode berjalan saat reaktivasi pelanggan cuti ke aktif (INV/YYYYMM/XXXX), penangkapan info tagihan di updateCustomerStatus, notifikasi reaktivasi pada footer.php dan app.js, serta penambahan pengujian TEST 6.4 (23/23 PASS) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-010
- 2026-11-03 | Antigravity AI & Saiful Islam | T-010 | Penyempurnaan CRUD paket layanan internet: penambahan toggle aktivasi is_active, proteksi hapus paket berpelanggan, pembuatan API api/packages.php, 4 kartu ringkasan metrik, dan penambahan pengujian otomatis TEST 3.0 (24/24 PASS) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-011
- 2026-11-08 | Antigravity AI & Saiful Islam | T-011 | Implementasi pencarian cepat dan filter multi-kriteria: pencarian kode/nama/kontak/IP & filter paket di customers.php, filter status lunas/belum lunas & pencarian invoice di bills.php, endpoint API search api/customers.php & api/bills.php, penanganan safe named parameter PDO, serta penambahan uji otomatis TEST 7.1 di test_all_requirements.php (25/25 PASS) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-012

- 2026-11-08 | Antigravity AI & Saiful Islam | T-012 | Implementasi template WhatsApp penagihan dual-channel (F4/R2): helper buildBillingWhatsAppMessage & buildBillingWhatsAppUrl di helpers.php, tombol kirim WA di bills.php, endpoint API get_wa_billing di api/bills.php, instruksi tunai pas, dan pengujian TEST 4.1 (26/26 PASS) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-013
- 2026-11-08 | Antigravity AI & Saiful Islam | T-013 | Implementasi template WhatsApp konfirmasi pelunasan / kuitansi digital resmi (F4): helper buildPaymentReceiptWhatsAppMessage & buildPaymentReceiptWhatsAppUrl di helpers.php, tombol Kuitansi WA di bills.php, integrasi showReceiptModal di app.js, endpoint get_wa_receipt di api/bills.php, dan pengujian TEST 4.2 (27/27 PASS) | Siap direview manusia sebelum merge ke main, lalu lanjut ke T-014