# Tasks: Rengganis Net

## Conventions

- **ID**: `T-001`, `T-002`, ... berurutan, unik.
- **Status**: `todo`, `doing`, `review`, `done`, `blocked`.
- **Ukuran**: `S` (< 1 jam), `M` (1 sampai 3 jam), `L` (setengah hari). Task lebih besar dari `L` harus dipecah.
- Satu task = satu branch = satu PR. Hanya satu task berstatus `doing` per orang.
- Setiap task merujuk fitur (F1, F2, ...) atau "Setup"/"Dokumen", dan memiliki kriteria selesai yang dapat dicek.

Agen AI hanya boleh mengambil task dengan status `todo` yang seluruh dependensinya berstatus `done`. Agen mengubah status menjadi `doing` saat mulai bekerja pada branch baru, dan mengubahnya menjadi `review` saat Pull Request dibuka. Mahasiswa mengonfirmasi penggabungan kode dan mengubah status task menjadi `done` setelah branch di-merge ke `main`.

## Milestones

| Milestone | Tujuan | Fitur Terkait | Perkiraan Waktu |
|---|---|---|---|
| M1: Fondasi & Audit Repositori | Audit codebase yang ada, validasi autentikasi peran, dan pengujian database | F1, F2, Setup | Minggu 1 |
| M2: Engine Billing & Notifikasi | Otomasi batch billing, pengecualian cuti, idempotensi, dan pesan WhatsApp | F3, F4, R1, R2 | Minggu 2 |
| M3: Pelunasan & Struk Bluetooth | Konfirmasi pelunasan, driver printer ESC/POS, fallback cetak, dan struk PNG | F5, R3 | Minggu 3 |
| M4: Laporan, Evaluasi & Rilis | Dasbor omzet, ekspor spreadsheet, verifikasi 12 test fungsional, dan rilis | F6, Dokumen | Minggu 4 |

## Task List

| ID | Judul | Fitur | Ukuran | Status | Branch | Bergantung pada | Kriteria Selesai |
|---|---|---|---|---|---|---|---|
| T-001 | Audit repositori dan penyesuaian perintah | Setup | M | done | chore/T-001-repo-audit | - | Seluruh perintah stack.commands (lint, test) lulus tanpa galat dan tidak ada rahasia di Git |
| T-002 | Penguatan autentikasi dan otorisasi multi-role | F1 | M | done | feat/T-002-auth-roles | T-001 | Login aman dengan bcrypt, proteksi CSRF, dan penolakan akses menu admin untuk teknisi/kolektor |
| T-003 | Audit skema database MySQL dan foreign key | Setup | S | done | chore/T-003-db-schema-audit | T-001 | 6 tabel MySQL terkonfigurasi dengan foreign key dan unique constraint yang konsisten |
| T-004 | Manajemen data pelanggan dan validasi kontak | F2 | M | done | feat/T-004-customer-crud | T-003 | Form tambah/ubah pelanggan tersimpan di database dan validasi nomor WhatsApp berfungsi |
| T-005 | Pencatatan audit log status cuti pelanggan | F2 | M | done | feat/T-005-status-cuti-logging | T-004 | Perubahan status pelanggan ke cuti/isolir tercatat otomatis ke tabel status_logs |
| T-006 | Pembuatan batch billing engine bulanan | F3 | L | done | feat/T-006-monthly-billing-engine | T-004 | Batch billing menerbitkan tagihan baru untuk seluruh pelanggan berstatus aktif |
| T-007 | Pengecualian otomatis pelanggan cuti dari tagihan | F3, R1 | M | done | feat/T-007-skip-cuti-billing | T-005, T-006 | Pelanggan berstatus cuti dilewati saat generate bulanan (0 tagihan fiktif terbit) |
| T-008 | Pencegahan duplikasi tagihan bulanan (idempotensi) | F3, R1 | M | done | feat/T-008-billing-idempotency | T-006 | Eksekusi ulang generate tagihan pada periode sama menghasilkan 0 tagihan baru |
| T-009 | Reaktivasi pelanggan cuti dengan tagihan bulan berjalan | F2, F3 | M | done | feat/T-009-reaktivasi-cuti | T-007 | Reaktivasi pelanggan dari cuti ke aktif otomatis membuat tagihan periode berjalan |
| T-010 | Manajemen paket layanan internet WiFi | Setup | S | done | feat/T-010-packages-management | T-003 | Form CRUD paket internet berfungsi dan tarif terhubung ke nominal tagihan |
| T-011 | Pencarian pelanggan dan filter status tagihan | F2, F3 | S | done | feat/T-011-filter-search | T-004, T-006 | Pencarian nama/kode dan filter status lunas/belum lunas berfungsi cepat |
| T-012 | Template pesan WhatsApp tagihan dengan 2 cabang opsi bayar | F4, R2 | M | done | feat/T-012-wa-notification-template | T-006 | Tautan wa.me memuat opsi Online QR dan instruksi bayar Offline Tunai Pas |
| T-013 | Template pesan WhatsApp konfirmasi pelunasan | F4 | S | done | feat/T-013-wa-receipt-template | T-012 | Tombol WA menghasilkan teks kuitansi resmi dengan nomor transaksi tagihan |
| T-014 | Proses konfirmasi pelunasan multi-metode bayar | F5 | M | done | feat/T-014-payment-processing | T-006 | Tagihan berubah lunas, tercatat metode bayar, stempel waktu, dan nama kolektor |
| T-015 | Desain antarmuka modal struk pembayaran | F5 | S | done | feat/T-015-receipt-modal-ui | T-014 | Modal struk muncul otomatis pasca pelunasan dengan rincian pembayaran lengkap |
| T-016 | Driver Web Bluetooth API printer thermal ESC/POS | F5 | L | done | feat/T-016-bluetooth-escpos-driver | T-015 | Buffer byte ESC/POS terkirim via Bluetooth dan tercetak pada printer thermal 58mm/80mm |
| T-017 | Fallback cetak browser dan reconnect printer Bluetooth | F5, R3 | M | done | feat/T-017-bluetooth-fallback | T-016 | Sistem tidak hang saat Bluetooth mati; tombol cetak @media print 58mm aktif |
| T-018 | Render struk digital ke format gambar PNG | F5 | M | done | feat/T-018-digital-receipt-png | T-015 | Pustaka html2canvas merender struk menjadi gambar PNG tajam yang siap diunduh |
| T-019 | Dasbor analitik omzet dan collection rate | F6 | M | done | feat/T-019-dashboard-analytics | T-014 | Dasbor menampilkan KPI omzet bulanan, persentase tertagih, dan ringkasan metode |
| T-020 | Modul laporan keuangan dan filter tanggal | F6 | M | done | feat/T-020-financial-reports | T-014 | Laporan menampilkan total pemasukan yang akurat sesuai rentang tanggal yang dipilih |
| T-021 | Ekspor data laporan keuangan ke CSV dan Excel | F6 | M | todo | feat/T-021-export-excel-csv | T-020 | Tombol ekspor mengunduh berkas spreadsheet dengan tabel transaksi dan baris ringkasan |
| T-022 | Pengaturan profil usaha dan catatan kaki struk | Setup | S | todo | feat/T-022-company-settings | T-015 | Form settings memperbarui nama perusahaan, alamat, dan teks footer struk thermal |
| T-023 | Skrip pengujian otomatis 12 kriteria kebutuhan | Setup | M | todo | test/T-023-automated-test-suite | T-008, T-014 | Seluruh 12 tes di tests/test_all_requirements.php menghasilkan 100% PASS |
| T-024 | Uji operasional lapangan dan responsivitas mobile | F5 | M | todo | test/T-024-field-mobile-testing | T-017 | Antarmuka nyaman digunakan di smartphone Android dan uji cetak Bluetooth lapangan sukses |
| T-025 | Penyempurnaan dokumentasi repositori README | Dokumen | S | todo | docs/T-025-readme-documentation | T-023 | README.md memuat seluruh bagian wajib kit dan petunjuk operasional |
| T-026 | Refleksi akhir dan catatan pembelajaran | Dokumen | S | todo | docs/T-026-final-review-lessons | T-025 | Berkas REVIEW.md dan LESSONS.md terisi lengkap sebagai bahan evaluasi |

## Backlog

- Integrasi webhook WhatsApp resmi (Business API) jika kuota operasional bulanan telah dianggarkan di masa depan.
- Penambahan grafik tren pendapatan multi-tahun pada modul analitik.
