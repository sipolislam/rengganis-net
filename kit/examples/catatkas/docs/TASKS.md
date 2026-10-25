# Tasks: CatatKas

## Conventions

- **ID**: `T-001`, `T-002`, ... berurutan, unik.
- **Status**: `todo`, `doing`, `review`, `done`, `blocked`.
- **Ukuran**: `S` (< 1 jam), `M` (1 sampai 3 jam), `L` (setengah hari). Task lebih besar dari `L` harus dipecah.
- Satu task = satu branch = satu PR. Hanya satu task berstatus `doing` per orang.
- Setiap task merujuk fitur (F1, F2, ...) atau "setup"/"dokumen", dan memiliki kriteria selesai yang dapat dicek.

Agen hanya mengambil task berstatus `todo` yang semua dependensinya sudah `done`, mengubah status ke `doing` saat mulai dan ke `review` saat PR dibuka. Mahasiswa mengubah ke `done` setelah merge.

## Milestones

| Milestone | Tujuan | Fitur | Perkiraan |
|---|---|---|---|
| M1 Fondasi dan setup | Repositori, tooling, CI, kerangka Next.js berjalan | setup | Minggu 1 |
| M2 Inti kas | Login, peran, catat transaksi, dasbor | F1, F4, F2, F3 | Minggu 2 sampai 3 |
| M3 Pelengkap | Ekspor PDF dan unggah bukti | F5, F6 | Minggu 3 sampai 4 |
| M4 Penyempurnaan dan rilis | Uji akhir, README, refleksi, deploy ke Vercel | semua | Minggu 4 |

## Task List

| ID | Judul | Fitur | Ukuran | Status | Branch | Bergantung pada | Kriteria selesai |
|---|---|---|---|---|---|---|---|
| T-001 | Setup repositori, lint, tes, CI, .gitignore, .env.example | setup | M | todo | chore/T-001-setup | - | `npm run lint`, `npm test`, `npm run build` berjalan; CI hijau di PR |
| T-002 | Kerangka Next.js dan halaman hello-world terdeploy preview | setup | S | todo | chore/T-002-kerangka | T-001 | Halaman awal tampil di URL preview Vercel |
| T-003 | Skema database Drizzle dan migrasi (User, Transaction, TransactionAudit) | F2 | M | todo | feat/T-003-skema | T-002 | Migrasi berjalan di database lokal; tes skema lulus |
| T-004 | Login dan logout dengan Supabase Auth | F1 | M | todo | feat/T-004-login | T-002 | Email dan kata sandi benar masuk ke dasbor; salah menampilkan pesan umum |
| T-005 | Peran bendahara dan anggota di tingkat server | F4 | M | todo | feat/T-005-peran | T-004 | Permintaan tulis oleh anggota dibalas 403; tes otorisasi lulus |
| T-006 | Tambah transaksi dengan validasi nominal | F2 | M | todo | feat/T-006-tambah | T-003, T-005 | Transaksi valid tersimpan; nominal tidak valid ditolak dengan pesan |
| T-007 | Ubah dan hapus transaksi dengan audit | F2 | L | todo | feat/T-007-ubah-hapus | T-006 | Setiap ubah dan hapus menulis baris audit berisi siapa dan kapan |
| T-008 | Dasbor saldo dan ringkasan bulan berjalan | F3, R1 | M | todo | feat/T-008-dasbor | T-006 | Saldo sama dengan total masuk dikurangi keluar; tes saldo setelah ubah dan hapus lulus |
| T-009 | Ekspor laporan bulanan PDF | F5 | L | todo | feat/T-009-ekspor | T-008 | PDF terunduh berisi transaksi dan saldo akhir; bulan kosong tetap valid |
| T-010 | Unggah bukti transaksi ke penyimpanan | F6 | M | todo | feat/T-010-bukti | T-006 | JPG atau PNG valid tertaut ke transaksi; berkas tidak valid ditolak |
| T-011 | Tes E2E alur catat transaksi sampai saldo berubah | F1, F2, F3 | M | todo | test/T-011-e2e | T-008 | Satu alur Playwright lulus di CI |
| T-012 | Pemeriksaan aksesibilitas dan tampilan ponsel | F3 | M | todo | fix/T-012-a11y | T-008 | Kontras dan navigasi keyboard sesuai DESIGN_SYSTEM; layar kecil nyaman |
| T-013 | Tinjau keamanan: OWASP checklist, audit dependensi, header | F4 | M | todo | chore/T-013-keamanan | T-010 | Semua baris checklist SECURITY terverifikasi; npm audit tanpa temuan tinggi |
| T-014 | Deploy produksi, README final, dan isi LESSONS refleksi | setup | M | todo | docs/T-014-rilis | T-011, T-012, T-013 | Produksi berjalan; README akurat; LESSONS terisi |

## Backlog

Kosong: semua fitur F1 sampai F6 sudah terjadwal. Ide di luar cakupan (notifikasi pengingat iuran) dicatat di sini bila muncul.
