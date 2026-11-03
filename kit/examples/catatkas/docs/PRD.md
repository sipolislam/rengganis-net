# Product Requirements Document: CatatKas

> Pencatatan kas organisasi mahasiswa yang transparan dan bebas selisih saldo.

## Overview

| Field | Isi |
|---|---|
| Nama produk | CatatKas |
| Platform | web |
| Level proses | menengah |
| Tagline | Kas organisasi yang jelas dan bisa dipercaya |
| Tim dan waktu | 2 anggota, 4 minggu |

## Problem

Kas organisasi mahasiswa tingkat jurusan umumnya dicatat di buku fisik atau spreadsheet bersama yang dibagikan lewat grup chat. Karena banyak orang dapat mengedit, saldo sering selisih dan sulit dilacak siapa mengubah apa. Bendahara kehilangan waktu untuk rekonsiliasi, dan anggota ragu apakah iuran dipakai semestinya. Alternatif saat ini adalah buku kas dan spreadsheet, keduanya tanpa riwayat perubahan yang dapat dipercaya.

## Goals and Non-Goals

### Goals

1. Bendahara dapat mencatat satu transaksi kas dalam 30 detik atau kurang.
2. Saldo yang tampil selalu sama dengan total pemasukan dikurangi total pengeluaran yang tercatat.

### Non-Goals

- Integrasi pembayaran atau transfer bank otomatis: ditunda karena menambah risiko keamanan dan biaya di luar cakupan 4 minggu.
- Aplikasi mobile native: web yang nyaman di ponsel sudah memenuhi kebutuhan pengguna.

## Target Users

| Persona | Kebutuhan | Kutipan (ilustrasi perumusan tim) |
|---|---|---|
| Bendahara organisasi | Mencatat dan memperbaiki transaksi cepat tanpa takut saldo rusak | "Saya mau tahu persis siapa mengubah apa dan kapan." |
| Anggota biasa | Melihat saldo dan penggunaan iuran tanpa bertanya ke bendahara | "Saya ingin yakin uang iuran dipakai untuk kegiatan." |

Kondisi pengguna: koneksi internet kampus sering lambat dan sebagian besar anggota memakai ponsel Android kelas menengah, sehingga halaman harus ringan.

## Core Features

### F1 - Login anggota (must)

Anggota masuk dengan email dan kata sandi lewat layanan autentikasi terkelola.

Acceptance Criteria:
- Jika email terdaftar dan kata sandi benar, pengguna masuk dan diarahkan ke dasbor.
- Jika kata sandi salah, pengguna melihat pesan galat umum tanpa membocorkan apakah email terdaftar.

### F2 - Catat transaksi kas (must)

Bendahara menambah, mengubah, dan menghapus transaksi pemasukan atau pengeluaran dengan kategori, nominal, tanggal, dan catatan.

Acceptance Criteria:
- Jika bendahara menyimpan transaksi valid, transaksi muncul di daftar dan saldo berubah sesuai.
- Jika nominal bukan angka positif, formulir menolak dan menunjukkan pesan yang jelas.
- Jika transaksi diubah atau dihapus, tercatat riwayat perubahan berisi siapa dan kapan.

### F3 - Dasbor saldo (must)

Halaman utama menampilkan saldo terkini dan ringkasan pemasukan dan pengeluaran bulan berjalan.

Acceptance Criteria:
- Saldo di dasbor sama dengan total pemasukan dikurangi total pengeluaran di daftar transaksi.
- Ringkasan bulan berjalan berubah saat transaksi baru disimpan tanpa memuat ulang manual.

### F4 - Peran bendahara dan anggota (must)

Hanya bendahara yang dapat menambah, mengubah, atau menghapus transaksi; anggota hanya dapat melihat.

Acceptance Criteria:
- Jika anggota mencoba menambah transaksi lewat antarmuka atau permintaan langsung ke API, server menolak dengan status 403.
- Jika bendahara membuka halaman transaksi, tombol tambah, ubah, dan hapus tersedia.

### F5 - Ekspor laporan bulanan PDF (should)

Bendahara mengunduh laporan kas satu bulan dalam format PDF.

Acceptance Criteria:
- Jika bendahara memilih bulan dan menekan ekspor, PDF terunduh berisi daftar transaksi dan saldo akhir bulan.
- Jika bulan terpilih tidak punya transaksi, PDF tetap dibuat dan menyatakan tidak ada transaksi.

### F6 - Unggah bukti transaksi (could)

Bendahara melampirkan foto nota atau kuitansi pada sebuah transaksi.

Acceptance Criteria:
- Jika bendahara mengunggah JPG atau PNG di bawah batas ukuran, gambar tertaut ke transaksi dan dapat dilihat anggota.
- Jika berkas bukan gambar atau melewati batas ukuran, unggahan ditolak dengan pesan jelas.

## User Flows

1. **Mencatat pengeluaran (F1, F2, F3, F4):** bendahara login, membuka halaman transaksi, memilih "Tambah", mengisi jenis, kategori, nominal, tanggal, lalu menyimpan; daftar dan saldo di dasbor langsung berubah. Galat utama: nominal tidak valid atau koneksi putus, sehingga formulir tetap menyimpan isian dan menampilkan pesan untuk mencoba lagi.
2. **Anggota memeriksa kas (F1, F3):** anggota login, membuka dasbor, melihat saldo dan ringkasan bulan ini, lalu membuka daftar transaksi dalam mode baca. Galat utama: sesi kedaluwarsa mengarahkan kembali ke login.
3. **Laporan akhir bulan (F5, F6):** bendahara memilih bulan, melihat transaksi dan bukti, lalu mengekspor PDF untuk dibagikan di rapat.

## AI Features

Tidak ada fitur AI di dalam aplikasi pada versi ini.

## Non-Functional Requirements

| Kategori | Persyaratan |
|---|---|
| Performa | Halaman utama ringan dan dapat dipakai pada koneksi lambat kampus; hindari aset besar |
| Aksesibilitas | WCAG 2.2 AA: kontras memadai, navigasi keyboard, label formulir jelas |
| Perangkat | Nyaman di ponsel Android kelas menengah (layar kecil, satu tangan) |
| Bahasa | Antarmuka Bahasa Indonesia |
| Privasi | Hanya nama dan email anggota yang disimpan (data dasar); tidak ada data sensitif |
| Integritas data | Nominal dihitung di server dengan bilangan bulat satuan rupiah; setiap perubahan transaksi diaudit |

## Success Metrics

| Metrik | Target | Cara Ukur |
|---|---|---|
| Waktu mencatat satu transaksi | 30 detik atau kurang | Uji coba dengan bendahara memakai stopwatch pada 10 transaksi |
| Selisih saldo saat rekonsiliasi akhir bulan | 0 selisih | Bandingkan saldo aplikasi dengan buku kas pada akhir bulan uji coba |

## Risks and Open Questions

**Risiko menurut mahasiswa:** "Saya khawatir saldo salah hitung kalau ada transaksi yang diedit atau dihapus, dan bendahara jadi tidak percaya aplikasinya."

Mitigasi untuk risiko tersebut: saldo selalu dihitung dari seluruh transaksi di server (bukan disimpan sebagai angka terpisah yang bisa melenceng), setiap ubah atau hapus tercatat di tabel audit, dan ada tes yang membandingkan saldo dengan jumlah transaksi setelah serangkaian ubah dan hapus.

Kendali risiko yang disetujui mahasiswa:
- **R1** (F2, F3): Saldo selalu dihitung ulang di server dari seluruh transaksi, tidak disimpan sebagai angka terpisah; setiap ubah atau hapus transaksi tercatat di audit (siapa, kapan, nilai lama).

Risiko lain:
- Saldo salah hitung bila nominal dihitung di klien atau memakai bilangan desimal biner. Mitigasi: simpan nominal sebagai bilangan bulat rupiah dan hitung di server.
- Paket gratis Supabase atau Vercel dapat membatasi penggunaan saat uji coba bersama. Mitigasi: cek batas paket sebelum uji coba dan pantau penggunaan.

Pertanyaan terbuka:
- Apakah transaksi yang dihapus perlu dihapus permanen atau ditandai terhapus (soft delete)? Pemilik: mahasiswa bersama dosen, putuskan sebelum T-006.
- Batas ukuran unggahan bukti (F6) belum ditetapkan; cek batas paket penyimpanan terbaru.
