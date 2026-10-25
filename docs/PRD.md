# Product Requirements Document: Rengganis Net

> Sistem informasi manajemen operasional, penagihan WiFi bulanan bebas tagihan fiktif cuti, dan cetak struk Bluetooth ESC/POS.

## Overview

| Field | Isi |
|---|---|
| Nama produk | Rengganis Net |
| Platform | web |
| Level proses | dasar |
| Tagline | Otomasi penagihan dan pencatatan WiFi lokal tanpa ribet door-to-door |
| Tim dan waktu | 1 anggota, 4 minggu |

## Problem

Petugas penagih layanan internet RT/RW Net Rengganis Net saat ini masih harus mendatangi rumah masing-masing pelanggan satu per satu secara manual untuk melakukan penagihan tagihan bulanan. Proses ini sangat tidak menghemat waktu dan tenaga pengelola, serta rawan memicu keterlambatan pembayaran dan kesalahan pencatatan transaksi manual di buku catatan fisik. Di sisi lain, pelanggan yang sedang mengajukan status cuti sementara sering kali mengalami salah tagih (tagihan fiktif) karena tidak adanya sinkronisasi status pelanggan dengan siklus pembuatan tagihan massal. Jika dibiarkan, operasional penagihan menjadi tidak efisien, rekapitulasi keuangan tidak akurat, dan kepuasan pelanggan menurun.

## Goals and Non-Goals

### Goals

1. Mengotomatisasi pembuatan tagihan bulanan seluruh pelanggan aktif dan pengiriman notifikasi tagihan via WhatsApp dengan opsi pembayaran online dan offline.
2. Menyediakan pencatatan pelunasan instan dengan bukti pembayaran struk fisik thermal Bluetooth ESC/POS serta struk digital format PNG.

### Non-Goals

- Membangun aplikasi mobile native APK terpisah di Google Play Store: ditunda karena antarmuka web responsif di browser ponsel sudah sangat mencukupi kebutuhan operasional lapangan.
- Integrasi payment gateway otomatis berbayar pihak ketiga seperti Midtrans atau Xendit: tidak dimasukkan pada rilis ini demi menghemat biaya operasional langganan gateway dan menjaga kesederhanaan alur kas lokal.

## Target Users

| Persona | Kebutuhan | Kutipan |
|---|---|---|
| Kolektor / Kasir Lapangan | Mengetahui daftar tagihan pelanggan dan mencetak struk fisik via printer Bluetooth langsung di lokasi pelanggan tanpa salah catat | "Setiap akhir bulan saya lelah menagih pintu ke pintu secara manual; rawan salah catat dan banyak warga tidak di rumah." (ilustrasi, dirumuskan asisten) |
| Pelanggan WiFi Rumahan | Menerima rincian tagihan secara tepat waktu melalui WhatsApp dengan opsi bayar online atau tunai yang jelas | "Saya ingin tahu tagihan lewat WhatsApp dan bisa bayar via transfer tanpa menunggu didatangi petugas." (ilustrasi, dirumuskan asisten) |

Kondisi pengguna: Sebagian besar petugas menggunakan ponsel Android kelas menengah di lapangan di bawah terik sinar matahari, serta membutuhkan antarmuka Bahasa Indonesia yang intuitif bagi pengguna awam.

## Core Features

### F1 - Autentikasi dan otorisasi multi-role (must)

Sistem login berbasis sesi dengan enkripsi password bcrypt untuk peran Admin, Kolektor/Kasir, dan Teknisi.

Acceptance Criteria:
- Jika pengguna memasukkan username dan password valid, sistem membuka sesi sesuai peran dan mengarahkan ke dasbor.
- Jika peran adalah Teknisi atau Kolektor yang mencoba mengakses menu Pengaturan Sistem atau Manajemen User, server menolak akses dengan status 403 atau pesan peringatan hak akses.
- Jika password salah, sistem menampilkan notifikasi kesalahan tanpa membocorkan eksistensi data pengguna lain.

### F2 - Kelola data pelanggan dan status layanan (must)

Pengelolaan data master pelanggan dengan status Aktif, Cuti, dan Isolir disertai pencatatan riwayat audit status_logs.

Acceptance Criteria:
- Jika admin menambahkan pelanggan baru dengan paket valid, data tersimpan di database dan muncul pada tabel pelanggan.
- Jika status pelanggan diubah menjadi cuti, sistem mencatat riwayat perubahan ke tabel status_logs berisi status lama, status baru, waktu, dan user pengubah.
- Jika pelanggan berstatus cuti diaktifkan kembali di pertengahan bulan berjalan, sistem langsung menerbitkan tagihan bulan berjalan untuk pelanggan tersebut.

### F3 - Pembuatan tagihan bulanan otomatis (must)

Batch billing engine untuk membuat tagihan seluruh pelanggan aktif tiap bulan dengan pencegahan tagihan ganda dan pengecualian pelanggan cuti.

Acceptance Criteria:
- Jika batch billing dijalankan untuk periode bulan dan tahun tertentu, sistem hanya menerbitkan tagihan baru untuk pelanggan berstatus aktif.
- Jika pelanggan berstatus cuti, sistem secara otomatis melewatkan pelanggan tersebut sehingga menghasilkan 0 tagihan baru tanpa denda fiktif.
- Jika proses generate tagihan dijalankan ulang pada periode yang sama, sistem menghasilkan 0 tagihan baru karena pencegahan duplikasi tagihan (idempotensi).

### F4 - Notifikasi tagihan dan pembayaran WhatsApp (must)

Pembuatan pesan otomatis WhatsApp melalui tautan wa.me dengan rincian tagihan dan 2 cabang opsi pembayaran (Online QR dan Offline Tunai Pas).

Acceptance Criteria:
- Jika tombol kirim WhatsApp tagihan ditekan, sistem membuka tautan wa.me berisi rincian nama pelanggan, periode, nominal, opsi transfer online dengan kode QR, dan opsi offline dengan teks siapkan uang tunai pas.
- Jika tombol kirim WhatsApp bukti pelunasan ditekan pada tagihan lunas, sistem menghasilkan teks konfirmasi pembayaran resmi beserta nomor referensi transaksi.

### F5 - Pencatatan pelunasan dan cetak struk Bluetooth (must)

Konfirmasi pelunasan tagihan sekali klik dengan multi-metode bayar, pencetakan struk thermal Bluetooth ESC/POS 58mm/80mm, dan unduh struk PNG.

Acceptance Criteria:
- Jika kasir mencatat pelunasan dengan metode bayar valid, status tagihan berubah menjadi lunas, tanggal bayar tercatat, dan modal struk otomatis terbuka.
- Jika tombol cetak Bluetooth ditekan dan printer terhubung via Web Bluetooth API, perintah ESC/POS terkirim dan struk tercetak pada kertas thermal 58mm/80mm.
- Jika koneksi Bluetooth gagal atau tidak tersedia, sistem tidak mengalami crash dan menyediakan fallback cetak browser (@media print) atau tombol unduh struk format PNG.

### F6 - Laporan keuangan dan ekspor data (should)

Rekapitulasi pemasukan harian dan bulanan, pelacakan collection rate, dan ekspor laporan ke format CSV dan Excel.

Acceptance Criteria:
- Jika pengguna memilih rentang tanggal atau bulan pada laporan, sistem menampilkan total omzet yang sesuai dengan penjumlahan tagihan berstatus lunas.
- Jika tombol ekspor CSV atau Excel ditekan, browser mengunduh berkas spreadsheet berisi tabel rincian transaksi lengkap dengan header dan baris ringkasan KPI.

## User Flows

1. **Pembuatan Tagihan Bulanan dan Notifikasi WhatsApp (F1, F3, F4):** Admin login ke dasbor, membuka modul Tagihan, memilih periode bulan berjalan, dan menekan tombol Generate Tagihan. Sistem memproses seluruh pelanggan aktif, melewatkan pelanggan cuti, dan menampilkan ringkasan jumlah tagihan terbit. Admin atau kasir kemudian menekan ikon WhatsApp pada baris tagihan pelanggan untuk membuka tautan wa.me berisi rincian nominal dan instruksi bayar (Opsi Online via QR atau Opsi Offline dengan menyiapkan uang pas). Jika generate dijalankan ulang, sistem mendeteksi duplikasi dan menampilkan pesan bahwa seluruh tagihan periode tersebut sudah ada.
2. **Penerimaan Pembayaran dan Cetak Struk Bluetooth (F1, F5):** Kasir/kolektor membuka daftar tagihan belum lunas melalui ponsel, mencari nama atau kode pelanggan, menekan tombol Bayar, memilih metode bayar (Tunai, Transfer BCA/BRI, QRIS, DANA), lalu mengonfirmasi pelunasan. Sistem menandai status tagihan menjadi lunas dan langsung membuka modal struk pembayaran. Kasir menekan tombol "Cetak Struk Bluetooth" untuk mengirim data ESC/POS ke printer thermal mini. Bila printer Bluetooth terputus, kasir beralih ke tombol cetak browser atau mengunduh struk gambar PNG untuk dibagikan via WhatsApp.
3. **Pembaruan Status Cuti Pelanggan dan Audit Log (F1, F2):** Teknisi atau admin menerima laporan pelanggan yang ingin berhenti sementara. Admin membuka data pelanggan, mengubah status menjadi "Cuti", dan memasukkan alasan cuti. Sistem mencatat transaksi ini ke tabel status_logs. Saat batch billing bulan depan berjalan, pelanggan cuti otomatis dilewati. Jika pelanggan kembali aktif di pertengahan bulan, status dikembalikan ke "Aktif" dan sistem otomatis membuat tagihan prorata/penuh bulan berjalan.

## AI Features

Tidak ada fitur AI di dalam aplikasi pada versi ini. Sistem mengandalkan logika bisnis deterministik dan aturan relasional MySQL yang pasti.

## Non-Functional Requirements

| Kategori | Persyaratan |
|---|---|
| Performa | Waktu muat halaman dasbor dan data pelanggan di bawah 1 detik pada lingkungan server lokal Laragon; pembuatan tagihan massal untuk 500 pelanggan selesai dalam waktu kurang dari 3 detik. |
| Aksesibilitas | Memenuhi standar WCAG 2.2 AA dengan rasio kontras teks minimal 4,5:1, navigasi formulir keyboard yang jelas, dan indikator fokus visual yang tegas. |
| Kondisi Pengguna | Antarmuka responsif ramah sentuhan satu tangan untuk ponsel Android kelas menengah petugas di lapangan dan nyaman dibaca di bawah sinar matahari. |
| Bahasa | Seluruh teks antarmuka, label status, dan pesan dialog menggunakan Bahasa Indonesia yang baku dan komunikatif. |
| Privasi Data | Data pribadi yang disimpan mencakup nama, alamat, nomor telepon WhatsApp, dan IP Address (tingkat lokasi dan kontak). Seluruh input dibersihkan dengan prepared statement PDO untuk mencegah injeksi SQL. |
| Integritas & Ketersediaan | Database MySQL menerapkan foreign key constraint dan unique key index untuk menjamin tidak ada tagihan ganda atau data transaksi yatim (orphan record). |

## Success Metrics

| Metrik | Target | Cara Ukur |
|---|---|---|
| Akurasi penerbitan tagihan pelanggan cuti | 100% akurat (0 tagihan fiktif) | Menjalankan skrip verifikasi otomatis tests/test_all_requirements.php pada pelanggan berstatus cuti |
| Pencegahan tagihan duplikat saat batch billing | 0 tagihan ganda (idempoten) | Menjalankan batch generate tagihan dua kali berturut-turut pada periode yang sama dan memverifikasi tagihan baru bernilai 0 |
| Efisiensi waktu penyampaian tagihan dan bukti bayar | Waktu berkurang minimal 60% dibanding metode manual door-to-door | Waktu rata-rata pengiriman notifikasi tagihan WhatsApp dan cetak struk Bluetooth per transaksi di bawah 1 menit |

## Risks and Open Questions

**Risiko menurut mahasiswa:** "data pelanggan/tagihan salah hitung atau dobel saat generate bulanan, koneksi Bluetooth ke printer thermal terputus di lapangan, atau pelanggan mengabaikan pesan notifikasi tagihan di WhatsApp). nah jika ada balasan  dari pelanggan saya ingin pesan dari admin ada kata pembayaran online / ofline  jika memilih online tolong kirimkan link atau apa untuk otomatiskan ke pembayaranan menggunakan kode qr jika melilih offline tolong dalam pesennya ada kata siapkan uang tunai sejumlah tagihannya"

Kendali risiko yang disetujui mahasiswa:
- **R1** (F3): Sistem menerapkan database unique constraint pada (customer_id, period_month, period_year) dan validasi idempotensi saat generate tagihan; pelanggan berstatus cuti otomatis dilewati tanpa tagihan.
- **R2** (F4): Template pesan notifikasi WhatsApp menyertakan dua opsi pembayaran terstruktur: Opsi Online dengan tautan QRIS/pembayaran dan Opsi Offline dengan instruksi menyiapkan uang tunai pas sejumlah tagihan.
- **R3** (F5): Antarmuka cetak menyediakan tombol reconnect Bluetooth dan fallback cetak standar browser (@media print 58mm/80mm) serta ekspor struk gambar PNG agar tidak hang jika printer terputus.

Risiko teknis lain:
- Koneksi Web Bluetooth API ke printer thermal mini terputus saat kasir melakukan penagihan di lapangan. Mitigasi: implementasi protokol reconnect otomatis dan tombol fallback cetak browser / unduh gambar PNG.
- Potensi tagihan ganda atau tagihan salah sasaran untuk pelanggan cuti saat batch billing bulanan dijalankan. Mitigasi: penerapan indeks unique di MySQL dan filter `status = 'aktif'` yang diverifikasi dengan test otomatis.
- Pesan notifikasi tagihan WhatsApp diabaikan atau pelanggan bingung memilih cara bayar online vs offline. Mitigasi: template pesan disusun dengan opsi berurutan yang sangat eksplisit dan mencantumkan batas tanggal pembayaran.

Pertanyaan terbuka:
- Apakah ke depannya perlu integrasi Webhook WhatsApp otomatis tanpa perlu kasir menekan link wa.me secara manual? Verifikasi paket API pihak ketiga di masa mendatang.
- Apakah format struk thermal 80mm membutuhkan penambahan detail alamat lengkap pelanggan atau cukup kode pelanggan dan nama? Diputuskan cukup kode dan nama pada versi ini.
