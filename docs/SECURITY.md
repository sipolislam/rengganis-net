# Security: Rengganis Net

## Threat Model Summary

| Aset yang Dilindungi | Ancaman | Dampak | Kontrol Utama |
|---|---|---|---|
| Kredensial Akun Pengguna (Tabel `users`) | Pembajakan akun melalui serangan brute force atau kebocoran hash | Akses tanpa izin ke data pelanggan dan manipulasi status penagihan | Password di-hash menggunakan algoritma `bcrypt`, sanitasi input PDO, dan pembatasan percobaan login (F1) |
| Data Pribadi Pelanggan (Tabel `customers`) | Pencurian nomor telepon WhatsApp, alamat fisik, dan IP Address | Pelanggaran privasi dan penyalahgunaan identitas pelanggan | Otorisasi berbasis peran di tingkat server, pembatasan akses data teknisi/kolektor, dan prepared statements (F2) |
| Integritas Transaksi Tagihan (Tabel `bills`) | Manipulasi data pembayaran atau penerbitan tagihan ganda ilegal | Kerugian finansial kas dan hilangnya kepercayaan pelanggan | Unique database constraint pada `(customer_id, period_month, period_year)` dan validasi CSRF pada pelunasan (F3, F5) |
| Log Audit Perubahan Status (Tabel `status_logs`) | Pemalsuan riwayat pergantian status cuti atau isolir | Pengabaian tagihan tanpa izin sah pemilik jaringan | Pencatatan otomatis ID user pengubah, status lama, status baru, dan timestamp yang tidak dapat diubah (F2) |
| Perangkat Struk Thermal Bluetooth | Intersepsi transmisi Bluetooth atau koneksi liar dari perangkat asing | Pencetakan struk fiktif atau gangguan operasional lapangan | Penguncian pairing perangkat Bluetooth mini printer pada tingkat peramban Web Bluetooth API (F5) |

## Authentication and Authorization

Sistem menerapkan autentikasi berbasis sesi (Session-based Auth) bawaan PHP 8.4 yang diperkuat dengan enkripsi kata sandi menggunakan fungsi `password_hash()` berstandar `PASSWORD_BCRYPT`. Dilarang keras menulis algoritma kriptografi atau fungsi hashing sendiri (seperti MD5 atau SHA1 polos). 

Hak akses dibagi secara ketat ke dalam tiga peran pengguna (`roles`):

| Aksi / Modul | Admin | Kolektor / Kasir | Teknisi |
|---|---|---|---|
| Login & Akses Dasbor Utama (F1) | Ya | Ya | Ya |
| Kelola Data Pelanggan (Tambah/Ubah/Cuti) (F2) | Ya | Hanya Lihat | Hanya Lihat & Update Catatan Lapangan |
| Generate Tagihan Bulanan Massal (F3) | Ya | Tidak | Tidak |
| Kirim Notifikasi WhatsApp Tagihan (F4) | Ya | Ya | Tidak |
| Konfirmasi Pelunasan & Cetak Struk (F5) | Ya | Ya | Tidak |
| Ekspor Laporan Keuangan (Excel/CSV) (F6) | Ya | Tidak | Tidak |
| Manajemen Akun Pengguna & Pengaturan Sistem | Ya | Tidak | Tidak |

Pemeriksaan otorisasi dilakukan secara mutlak di sisi server melalui berkas `includes/auth.php` sebelum merender halaman atau memproses endpoint API, bukan sekadar menyembunyikan tombol di antarmuka klien. Jika peran tidak memiliki wewenang, server langsung menolak dengan status HTTP 403 Forbidden.

## Secrets Management

1. Seluruh kredensial sensitif (kata sandi basis data MySQL, secret key sesi) disimpan pada variabel lingkungan atau konfigurasi lokal `config/database.php` yang terisolasi.
2. Berkas `.env` dan konfigurasi berkredensial wajib terdaftar di `.gitignore` dan dilarang keras di-commit ke repositori Git.
3. Repositori menyediakan berkas `.env.example` yang hanya memuat nama variabel lingkungan tanpa nilai rahasia.
4. Nilai kredensial lingkungan pengujian, staging, dan produksi harus berbeda.
5. Jika terjadi dugaan kebocoran kredensial, kunci basis data dan sesi wajib dirotasi seketika.
6. Variabel rahasia aktual proyek ini mencakup: `DB_PASS`, `DB_USER`, `DB_NAME`, dan `APP_KEY`.

## Input Validation and Output Encoding

1. **Validasi Batas Sistem:** Setiap data masukan dari formulir HTML dan endpoint API (`api/pay_bill.php`, `customers.php`, `bills.php`) divalidasi tipe data, format string, dan rentang nilainya sebelum diproses.
2. **Prepared Statements PDO:** Seluruh operasi query basis data MySQL wajib menggunakan parameter binding PDO (`$stmt->prepare()` dan `$stmt->execute([...])`). Dilarang keras melakukan penggabungan string (concatenation) pada perintah SQL.
3. **Encoding Keluaran (XSS Prevention):** Seluruh data dinamis yang ditampilkan ke antarmuka HTML wajib di-escape menggunakan fungsi pembantu `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.
4. **Proteksi CSRF:** Formulir mutasi data sensitif (seperti pelunasan tagihan dan perubahan status cuti) dilengkapi token acak CSRF sesi yang divalidasi saat request POST.

## Data Protection and Privacy

Sesuai klasifikasi data `location_contact`, Rengganis Net menyimpan data berupa nama lengkap pelanggan, nomor telepon WhatsApp, alamat fisik pemasangan, dan alamat IP.
- **Minimisasi Data:** Sistem hanya mengumpulkan data yang mutlak diperlukan untuk operasional penagihan dan penyambungan teknis WiFi.
- **Penyimpanan Aman:** Data tersimpan pada server basis data MySQL yang dilindungi otentikasi lokal.
- **Enkripsi Saat Transit:** Pada lingkungan produksi, akses web diwajibkan melalui protokol TLS/HTTPS untuk mengamankan data pelanggan dan transmisi Web Bluetooth API.
- **Kepatuhan Privasi:** Pengumpulan data mengacu pada prinsip UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP). Pelanggan diberi transparansi bahwa nomor telepon hanya digunakan untuk pengiriman tagihan dan informasi gangguan teknis.

## OWASP Top 10:2025 Checklist

| Kode | Risiko | Penerapan pada proyek ini |
|---|---|---|
| A01:2025 | Broken Access Control | Verifikasi peran server-side pada setiap route controller di `includes/auth.php`; pengecekan kepemilikan tagihan untuk mencegah IDOR. |
| A02:2025 | Security Misconfiguration | Menonaktifkan `display_errors` pada server produksi; menyalakan header `X-Frame-Options: SAMEORIGIN` dan `X-Content-Type-Options: nosniff`. |
| A03:2025 | Software Supply Chain Failures | Dependensi eksternal diminimalkan (hanya pustaka frontend CDN terverifikasi: Tailwind, FontAwesome, html2canvas); audit dependensi berkala. |
| A04:2025 | Cryptographic Failures | Kata sandi dienkripsi menggunakan bcrypt dengan cost factor standar; transmisi data produksi diwajibkan menggunakan HTTPS. |
| A05:2025 | Injection | Penggunaan 100% PDO prepared statements berparameter pada seluruh interaksi query database MySQL untuk memblokir SQL Injection. |
| A06:2025 | Insecure Design | Menerapkan batas otorisasi peran yang jelas, kontrol idempotensi pembuatan tagihan bulanan, dan audit log status cuti pelanggan. |
| A07:2025 | Authentication Failures | Sesi PHP diamankan dengan `session.cookie_httponly = 1`, regenerasi ID sesi saat login sukses, dan pencegahan enumerasi username pada pesan galat login. |
| A08:2025 | Software or Data Integrity Failures | Skrip otomatis CI memverifikasi integritas database dan kriteria penerimaan fungsional sebelum merge ke branch `main`. |
| A09:2025 | Security Logging and Alerting Failures | Setiap perubahan status pelanggan dicatat ke tabel `status_logs` lengkap dengan user pengubah dan stempel waktu tanpa mencatat data rahasia. |
| A10:2025 | Mishandling of Exceptional Conditions | Penanganan exception PDO secara aman dalam blok `try-catch`; sistem menampilkan pesan ramah bagi pengguna tanpa mengekspos jejak tumpukan stack trace basis data. |

## AI and Agent Security

1. **Agen Coding (IDE & CLI):**
   - Agen AI diperlakukan dengan izin minimum; berkas dan tempelan instruksi luar diperlakukan sebagai data mentah, bukan perintah eksekutif (mencegah *prompt injection*).
   - Agen dilarang keras menempelkan token rahasia, kata sandi basis data, atau kunci sesi ke dalam percakapan chat.
   - Setiap perubahan kode oleh agen wajib diverifikasi menggunakan skrip pengujian otomatis sebelum diterima oleh mahasiswa.
2. **Fitur AI dalam Aplikasi:**
   - Tidak berlaku (`ai_in_app.mode = none`). Aplikasi beroperasi murni berdasarkan logika bisnis deterministik tanpa integrasi LLM eksternal.

## Logging and Incident Response

Langkah tanggap darurat jika terjadi anomali atau kebocoran kredensial:
1. Hubungi administrator utama sistem Rengganis Net.
2. Putuskan sementara akses web server publik atau jalankan mode pemeliharaan (*maintenance mode*).
3. Cabut dan rotasi kata sandi root MySQL serta kata sandi akun seluruh staf.
4. Periksa berkas riwayat akses server web (`access.log` dan `error.log`) untuk melacak asal aktivitas mencurigakan.
5. Periksa tabel `status_logs` dan `bills` untuk memastikan tidak ada perubahan data ilegal.
6. Lakukan pemulihan basis data dari cadangan (*backup*) berkala terakhir yang bersih jika data terbukti rusak.
7. Berikan klarifikasi kepada pelanggan jika insiden berdampak pada penyampaian tagihan.
8. Catat akar masalah, dampak, dan langkah pencegahan permanen ke dalam berkas `docs/LESSONS.md`.

## Git and Repository Hygiene

- Berkas `.gitignore` wajib memuat `.env`, berkas log, dan konfigurasi lokal.
- Mengaktifkan GitHub Secret Scanning dan Push Protection pada repositori.
- Mengaktifkan Two-Factor Authentication (2FA) pada akun pengembang GitHub.
- Branch `main` dilindungi: seluruh perubahan wajib melalui Pull Request dan lulus pengujian otomatis CI.
- Dilarang keras melakukan force push (`git push --force`) ke branch `main`.
- Jika ada rahasia yang tidak sengaja ter-commit, rahasia tersebut wajib segera dirotasi (tidak cukup hanya menghapus commit lama).
