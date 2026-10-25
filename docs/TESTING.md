# Testing: Rengganis Net

## Strategy

Strategi pengujian Rengganis Net dirancang untuk memastikan keandalan fungsional operasional penagihan dan integritas data tanpa membebani alur kerja pengembang tunggal dalam durasi 4 minggu. Pengujian memadukan skrip verifikasi otomatis terpadu pada sisi backend dengan pengujian alur interaktif manual pada antarmuka web dan koneksi Bluetooth di perangkat nyata. Seluruh aturan bisnis inti (seperti pencegahan tagihan fiktif bagi pelanggan cuti, pencegahan duplikasi batch billing, dan enkripsi password bcrypt) diverifikasi secara otomatis melalui skrip eksekusi CLI mandiri yang dapat dijalankan secara instan di komputer lokal maupun pada alur kerja CI GitHub Actions.

## Test Types

| Jenis Pengujian | Alat | Lokasi Berkas | Kapan Dijalankan | Perintah Eksekusi |
|---|---|---|---|---|
| Verifikasi Kebutuhan Otomatis | CLI Native PHP | `tests/test_all_requirements.php` | Sebelum setiap commit dan pada alur CI GitHub Actions | `php tests/test_all_requirements.php` |
| Validasi Sintaks / Lint | PHP CLI Checker | Seluruh berkas `.php` | Setiap perubahan kode pada controller dan helper | `php -l index.php` |
| Uji Integrasi Bluetooth ESC/POS | Web Bluetooth API & Printer Fisik | `assets/js/bluetooth-print.js` | Saat pengujian perangkat keras langsung di browser Chrome Android | Manual via modal struk di browser |
| Uji Tangkapan Struk Digital | html2canvas & Canvas API | `bills.php` | Pengujian unduh struk gambar PNG resolusi tinggi | Manual via tombol "Unduh Struk PNG" |
| Uji Responsivitas Tampilan | Chrome DevTools Device Mode | Antarmuka aplikasi | Setiap modifikasi komponen antarmuka Tailwind | Manual pada viewport 360px hingga 1280px |

## What Must Be Tested

Daftar pengujian wajib yang diturunkan dari fitur prioritas must (`SPEC.json`):
1. **F1 - Autentikasi dan Hak Akses Peran:**
   - Verifikasi login berhasil untuk pengguna valid (Admin, Teknisi, Kolektor).
   - Verifikasi enkripsi kata sandi menggunakan hash bcrypt yang valid.
   - Verifikasi penolakan akses (HTTP 403 atau pengalihan) saat Teknisi/Kolektor mencoba mengakses menu pengaturan sistem.
2. **F2 - Manajemen Data Pelanggan dan Status Cuti:**
   - Verifikasi penambahan pelanggan baru dengan relasi paket yang valid.
   - Verifikasi pencatatan audit log ke tabel `status_logs` saat status pelanggan diubah menjadi cuti.
   - Verifikasi reaktivasi pelanggan cuti langsung menerbitkan tagihan bulan berjalan.
3. **F3 - Pembuatan Tagihan Bulanan Otomatis & Idempotensi:**
   - Verifikasi batch billing hanya menerbitkan tagihan baru untuk pelanggan berstatus aktif.
   - Verifikasi pelanggan berstatus cuti mutlak tidak diterbitkan tagihan baru (0 tagihan fiktif).
   - Verifikasi bahwa eksekusi ulang pada periode yang sama menghasilkan 0 tagihan baru (bebas tagihan duplikat).
4. **F4 - Notifikasi Tagihan WhatsApp:**
   - Verifikasi perakitan tautan `wa.me` yang memuat teks rincian tagihan lengkap dengan opsi bayar Online QR dan instruksi bayar Offline Tunai Pas.
5. **F5 - Pencatatan Pelunasan & Cetak Struk:**
   - Verifikasi pembaruan status tagihan menjadi `lunas`, stempel waktu pelunasan, dan pencatatan nama kolektor.
   - Verifikasi pengiriman byte buffer ESC/POS ke printer thermal Bluetooth 58mm/80mm.
   - Verifikasi aktivasi fallback cetak browser (@media print) atau unduh PNG saat koneksi Bluetooth terputus.

## Commands

Perintah utama (dari `stack.commands`, identik dengan AGENTS.md, EVALS.md, dan CI):

| Tujuan | Perintah |
|---|---|
| Instal | `php -v` |
| Lint | `php -l index.php` |
| Semua tes | `php tests/test_all_requirements.php` |
| Build | `php -v` |

Perintah pengujian tambahan:
- Menjalankan server lokal pengembangan:
  ```powershell
  php -S 127.0.0.1:8080
  ```
- Memeriksa sintaks seluruh berkas controller utama:
  ```powershell
  php -l bills.php; php -l customers.php; php -l reports.php
  ```

## Coverage and Quality Bar

- **Logika Bisnis Kritis:** Seluruh 12 poin acceptance criteria dan guardrail metrics pada skrip `tests/test_all_requirements.php` wajib bernilai **100% PASS** (12 dari 12 tes berhasil).
- **Integritas Tes:** Dilarang menghapus assertion atau melemahkan kondisi uji agar hasil pengujian terlihat lulus.
- **Kebijakan Perbaikan Bug:** Setiap kali ada laporan kutu (*bug*), buat skrip tes atau assertion baru yang memicu kegagalan tersebut sebelum menuliskan kode perbaikannya.

## Test Data

- Basis data lokal disiapkan menggunakan skema resmi pada `database/schema.sql`.
- Data pelanggan pengujian wajib menggunakan nama fiktif, nomor telepon uji coba (contoh: `081234567890`), dan alamat contoh tanpa mencantumkan data pribadi warga nyata.
- Akun pengujian standar yang disediakan:
  - Admin: `admin` / `admin123`
  - Teknisi: `teknisi` / `teknisi123`
  - Kolektor: `kolektor` / `kolektor123`
- Database uji coba dapat direset kapan saja dengan menjalankan impor ulang `database/schema.sql`.

## Manual Test Checklist

Daftar periksa uji manual (minimal 10 butir pengujian langsung):
1. [ ] Buka halaman `login.php`, coba login dengan password salah, pastikan muncul pesan kesalahan yang ramah.
2. [ ] Login sebagai `admin`, pastikan dasbor menampilkan widget statistik total pelanggan, omzet, dan collection rate.
3. [ ] Login sebagai `teknisi`, pastikan menu Pengaturan Sistem dan Manajemen User tidak dapat diakses.
4. [ ] Buka halaman `customers.php`, ubah salah satu pelanggan menjadi status "Cuti", pastikan status berubah menjadi badge kuning.
5. [ ] Periksa database, pastikan riwayat perubahan status pelanggan tercatat pada tabel `status_logs`.
6. [ ] Buka `bills.php`, jalankan generate tagihan untuk bulan depan, pastikan pelanggan cuti tidak mendapatkan tagihan baru.
7. [ ] Jalankan generate tagihan sekali lagi untuk bulan yang sama, pastikan sistem menolak dengan pesan tagihan sudah ada (0 duplikat).
8. [ ] Klik ikon WhatsApp pada salah satu tagihan, pastikan URL `wa.me` terbuka dengan teks terformat berisi opsi Online QR dan Offline Tunai Pas.
9. [ ] Lakukan pelunasan salah satu tagihan, pastikan status berubah hijau "Lunas" dan modal struk otomatis muncul.
10. [ ] Pada modal struk, tekan tombol "Cetak Struk Bluetooth" dengan printer mini terhubung, pastikan struk tercetak rapi pada kertas thermal 58mm.
11. [ ] Matikan printer Bluetooth, tekan kembali tombol cetak, pastikan muncul notifikasi error yang ramah dan tombol "Cetak via Browser / Unduh PNG" aktif sebagai cadangan.
12. [ ] Buka halaman `reports.php`, pilih rentang tanggal, lalu tekan tombol "Ekspor Excel/CSV", pastikan berkas spreadsheet berhasil terunduh.

## Definition of Done

Suatu task pekerjaan dinyatakan selesai (*Done*) jika memenuhi seluruh kriteria berikut:
1. Seluruh kriteria penerimaan fungsional fitur yang dikerjakan telah terpenuhi secara utuh.
2. Skrip pengujian otomatis `php tests/test_all_requirements.php` dijalankan dan menghasilkan **100% PASS**.
3. Validasi sintaks kode `php -l index.php` bersih tanpa galat.
4. Tidak ada rahasia, kata sandi plain-text, atau berkas sementara pada `git diff`.
5. Dokumen pelacak `docs/PROGRESS.md` dan catatan keputusan `docs/DECISIONS.md` telah diperbarui.
6. Pull Request atau commit telah ditinjau dan diverifikasi sebelum digabungkan ke branch `main`.
