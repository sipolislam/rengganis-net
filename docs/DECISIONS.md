# Decisions: Rengganis Net

Catatan keputusan arsitektur (Architecture Decision Records). Keputusan **tidak dibatalkan diam-diam**: untuk mengubahnya, tambahkan ADR baru yang berstatus `Supersedes ADR-nnn` dan jelaskan alasannya.

## How to Use

1. ADR dibuat setiap kali terdapat pemilihan teknologi pokok, perubahan skema basis data, kompromi keamanan, atau penambahan dependensi utama.
2. Setiap rekaman mengikuti format standar: Status, Tanggal, Konteks, Keputusan, Konsekuensi, dan Alternatif yang dipertimbangkan.
3. Agen AI coding wajib membaca ADR sebelum mengusulkan modifikasi pada arsitektur atau dependensi proyek.

## Decision Records

Semua ADR di bawah memakai tanggal 2026-10-25 kecuali dibuat kemudian.

### ADR-001: Platform web

- **Status:** Accepted
- **Tanggal:** 2026-10-25
- **Konteks:** Sistem membutuhkan akses lintas perangkat yang fleksibel bagi admin di komputer desktop dan kasir/teknisi di smartphone Android tanpa perlu instalasi aplikasi terpisah melalui toko aplikasi.
- **Keputusan:** Menggunakan platform Web responsif yang didukung Web Bluetooth API untuk komunikasi langsung ke perangkat printer thermal mini di lapangan.
- **Konsekuensi:** Memerlukan peramban berbasis Chromium (Chrome/Edge Android) agar fitur Web Bluetooth API dapat berjalan normal.
- **Alternatif yang Dipertimbangkan:** Aplikasi Android native (APK) — ditolak karena memerlukan proses kompilasi terpisah, pemeliharaan ganda, dan lebih lambat dideploy untuk tugas akhir 4 minggu.

### ADR-002: Frontend dan UI

- **Status:** Accepted
- **Tanggal:** 2026-10-25
- **Konteks:** Stack dipilih mahasiswa: "Melanjutkan dan mengaudit codebase operasional yang sudah berjalan di repositori ini". Antarmuka membutuhkan styling modern yang ringan, cepat dimuat pada smartphone Android kelas menengah di lapangan, serta memiliki kontras visual tinggi di bawah sinar matahari.
- **Keputusan:** Menggunakan Tailwind CSS dipadukan dengan Vanilla JavaScript, FontAwesome 6, Google Fonts (Plus Jakarta Sans & JetBrains Mono), dan pustaka tangkapan kanvas `html2canvas`.
- **Konsekuensi:** Tidak menggunakan bundler SPA yang kompleks, sehingga logika interaktif ditulis langsung menggunakan Vanilla JS modular.
- **Alternatif yang Dipertimbangkan:** React / Next.js atau Bootstrap — ditolak karena kode antarmuka yang ada sudah berjalan sangat baik dan ringan tanpa beban dependensi node_modules berlebih.

### ADR-003: Backend dan data

- **Status:** Accepted
- **Tanggal:** 2026-10-25
- **Konteks:** Sistem membutuhkan pemrosesan logika bisnis penagihan massal yang cepat, handal, dan mudah dikonfigurasi di lingkungan server lokal RT/RW Net.
- **Keputusan:** Menggunakan PHP 8.4 Native dengan ekstensi PDO MySQL yang terhubung ke database relasional MySQL 8.0 (`rengganis_net`).
- **Konsekuensi:** Pengembang bertanggung jawab mengelola struktur query secara rapi menggunakan prepared statements tanpa ORM pihak ketiga.
- **Alternatif yang Dipertimbangkan:** Node.js Express atau Laravel — ditolak demi menjaga kesederhanaan deployment pada server Laragon lokal tanpa overhead framework.

### ADR-004: Autentikasi dan keamanan

- **Status:** Accepted
- **Tanggal:** 2026-10-25
- **Konteks:** Diperlukan sistem pembatasan wewenang untuk tiga peran (`admin`, `teknisi`, `kolektor`) dengan proteksi data pribadi kontak pelanggan (nama, nomor WhatsApp, IP address).
- **Keputusan:** Menggunakan autentikasi berbasis sesi PHP (`session_start()`) dengan enkripsi kata sandi menggunakan `password_hash()` algoritma bcrypt dan token proteksi CSRF pada form mutasi data.
- **Konsekuensi:** Sesi tersimpan di sisi server sehingga logout atau kedaluwarsa sesi secara efektif memutus hak akses pengguna.
- **Alternatif yang Dipertimbangkan:** Token JWT atau layanan Supabase Auth — ditolak agar aplikasi sepenuhnya independen dan dapat beroperasi di jaringan tertutup (intranet).

### ADR-005: Hosting dan deployment

- **Status:** Accepted
- **Tanggal:** 2026-10-25
- **Konteks:** Layanan internet RT/RW Net membutuhkan ketersediaan sistem di jaringan internal pengelola serta opsi akses publik bagi kasir keliling.
- **Keputusan:** Deployment menggunakan server lokal On-Premise Mini PC (Laragon Apache / Linux) dengan opsi tunneling atau porting ke VPS Cloud Linux di kemudian hari.
- **Konsekuensi:** Pemeliharaan server fisik lokal menjadi tanggung jawab pengelola jaringan setempat.
- **Alternatif yang Dipertimbangkan:** Serverless hosting (Vercel) — ditolak karena memerlukan database cloud terpisah yang menambah latensi dan biaya.

### ADR-006: Strategi pengujian dan level proses

- **Status:** Accepted
- **Tanggal:** 2026-10-25
- **Konteks:** Proyek dikerjakan secara mandiri oleh 1 mahasiswa dalam waktu 4 minggu dengan level proses **dasar**.
- **Keputusan:** Menggunakan skrip pengujian verifikasi terpadu otomatis `tests/test_all_requirements.php` yang memvalidasi 12 acceptance criteria kritis dalam satu eksekusi perintah CLI.
- **Konsekuensi:** Pengujian difokuskan pada integritas logika penagihan, database constraint, dan keamanan auth tanpa memerlukan setup framework pengujian unit yang berat.
- **Alternatif yang Dipertimbangkan:** PHPUnit atau Pest PHP — ditolak untuk menghindari kompleksitas instalasi paket Composer tambahan pada lingkungan pengujian cepat.

## Delegated Choices

Daftar pertanyaan yang didelegasikan mahasiswa (pilihan "Z") ke asisten:

| ID Pertanyaan | Pilihan yang Diambil Asisten | Alasan Singkat | Disetujui Mahasiswa |
|---|---|---|---|
| C-03 | Pelanggan WiFi dan Pengelola Internal | Menyelesaikan masalah penagihan fisik door-to-door bagi kasir dan pelanggan | Ya |
| C-04 | 6 Fitur Inti (F1 s/d F6) | Menjawab seluruh siklus kerja dari autentikasi, billing, struk, hingga laporan | Ya |
| C-05 | Prioritas 5 Must dan 1 Should | Memastikan fungsional inti penagihan selesai sebelum fitur laporan pelengkap | Ya |
| C-20 | Goals, Metrics, dan Non-Goals | Menjaga ruang lingkup terukur (akurasi tagihan 100%, efisiensi penagihan >60%) | Ya |
| C-06 | Data Kontak & Alamat (location_contact) | Esensial untuk penagihan dan pemeliharaan teknis jaringan di lapangan | Ya |
| C-07 | Tiga Peran: Admin, Teknisi, Kolektor | Memisahkan hak akses sistem secara tegas sesuai tugas operasional | Ya |
| C-08 | Tidak Memakai AI di Dalam Aplikasi | Memastikan sistem bekerja dengan aturan deterministik pasti tanpa biaya API | Ya |
| C-09 | Perangkat Murah, Bahasa Indonesia, Luar Ruangan | Menyesuaikan kondisi smartphone kasir di lapangan di bawah sinar matahari | Ya |
| C-16 | Stack Pilihan Sendiri (PHP/MySQL/Tailwind) | Melanjutkan codebase yang sudah ada dan terbukti lulus verifikasi fungsional | Ya |
| W-01 | Model Server-Side Rendered (SSR) PHP | Arsitektur ringan, responsif, dan mudah dijalankan di lingkungan Laragon | Ya |
| W-03 | Database MySQL 8.0 (rengganis_net) | Skema 6 tabel relasional sudah terstruktur dan teruji integritas datanya | Ya |
| W-04 | Session Auth PHP + bcrypt | Aman dari eksploitasi credential tanpa dependensi pihak ketiga berbayar | Ya |
| W-05 | Tailwind CSS + FontAwesome + Plus Jakarta Sans | Memberikan tampilan modern profesional dengan kontras visual tinggi | Ya |
| W-06 | Server On-Premise / Laragon Lokal | Ideal untuk server jaringan lokal RT/RW Net yang hemat biaya operasional | Ya |
| W-07 | Web Bluetooth ESC/POS & html2canvas | Fitur unggulan untuk pencetakan struk fisik thermal dan struk digital PNG | Ya |
| W-09 | Skrip Pengujian Otomatis Mandiri | Menguji seluruh kebutuhan fungsional secara instan dalam satu perintah | Ya |
| C-10 | Level Proses Dasar | Tepat untuk proyek capstone perseorangan agar fokus pada kualitas fungsional | Ya |
| C-11 | Alat AI: Antigravity / Gemini CLI | Sesuai lingkungan kerja IDE dan agen yang sedang aktif digunakan | Ya |
| C-13 | 1 Pengembang, Durasi 4 Minggu | Durasi standar capstone untuk audit, pengembangan, pengujian, dan sidang | Ya |

## Assistant Proposals

Daftar usulan asisten yang dicatat pada `spec.meta.assistant_proposed`:

| Bidang SPEC | Nilai yang Diusulkan Asisten | Alasan Singkat | Disetujui Mahasiswa |
|---|---|---|---|
| `problem.who` | Pengelola, kolektor/kasir lapangan, dan pelanggan WiFi Rengganis Net | Merinci pihak terdampak dari penagihan manual door-to-door | Ya |
| `users[0].quote` | Kutipan ilustrasi persona kolektor/kasir lapangan | Menggambarkan kelelahan penagihan fisik pintu ke pintu di akhir bulan | Ya |
| `users[1].quote` | Kutipan ilustrasi persona pelanggan WiFi rumahan | Menggambarkan keinginan pelanggan menerima info WA dan opsi transfer | Ya |
| `metrics` | Target akurasi 100% (0 tagihan fiktif) dan efisiensi waktu >60% | Ukuran keberhasilan yang teruji dan dapat diukur secara kuantitatif | Ya |
| `non_goals` | Tidak membuat aplikasi APK native Play Store dan payment gateway berbayar | Mencegah pembengkakan ruang lingkup dan biaya di luar masa 4 minggu | Ya |
| `design` | Tema visual Navy Slate & Amber Accent (`#1E3A8A`), font Plus Jakarta Sans | Memberikan hierarki visual jelas dan keterbacaan tinggi di luar ruangan | Ya |
| `student_reflection.risk_controls` | Rumusan teknis konkret R1 (idempotensi), R2 (format WA), R3 (fallback Bluetooth) | Mengubah kekhawatiran risiko mahasiswa menjadi aturan teruji di sistem | Ya |

Konflik kriteria yang diselesaikan saat penyusunan SPEC:
1. *Konflik Status Cuti vs Tagihan Bulanan:* Diselesaikan dengan aturan mutlak bahwa tagihan pelanggan cuti dilewati (0 tagihan fiktif). Jika pelanggan diaktifkan kembali di tengah bulan, tagihan bulan berjalan langsung diterbitkan.
2. *Konflik Dua Cabang Opsi Bayar WhatsApp:* Diselesaikan dengan menyatukan opsi bayar Online (tautan QR) dan Offline (siapkan uang pas) dalam satu template pesan standar terstruktur.

## Open Questions

1. **Webhook Integrasi WhatsApp Otomatis:** Apakah diperlukan gateway WhatsApp API resmi berbayar untuk otomatisasi kirim pesan tanpa klik manual? Pemilik: Mahasiswa bersama Dosen; diputuskan pasca evaluasi capstone.
2. **Ukuran Lebar Kertas Struk Default:** Apakah printer kasir lapangan mayoritas memakai kertas thermal 58mm atau 80mm? Verifikasi dengan perintah pengujian perangkat di lapangan; saat ini driver mendukung keduanya.
