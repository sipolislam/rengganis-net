# Lessons: Rengganis Net

Kesalahan berulang dan pelajaran dari proyek ini. **Dibaca agen di awal setiap sesi.** Entri baru ditambahkan oleh mahasiswa atau agen setelah kesalahan ditemukan.

## How to Use

1. **Kapan Menambah Entri:** Tambahkan entri baru setiap kali terjadi kendala yang memakan waktu pemecahan lebih dari 30 menit, kegagalan berulang agen AI, atau temuan evaluasi dosen saat review.
2. **Format Entri:** Tuliskan secara ringkas pada tabel riwayat: Tanggal, Gejala/Kesalahan, Akar Masalah, Aturan Baru, dan Dokumen Penerapannya.
3. **Konversi Menjadi Aturan:** Setiap pelajaran berharga wajib diterjemahkan menjadi aturan nyata di `docs/CODE_STYLE.md`, `docs/GUARDRAILS.md`, atau `docs/EVALS.md`.
4. **Prinsip Bebas Tuduhan:** Tuliskan entri secara objektif dengan fokus pada perbaikan proses teknis tanpa menyalahkan individu.

## Lessons Log

| Tanggal | Kesalahan atau Gejala | Akar Masalah | Aturan Baru | Diterapkan di |
|---|---|---|---|---|
| Awal (seed) | Agen menyarankan penggunaan fungsi hash MD5 polos untuk login | Pengetahuan model AI lama yang menganggap MD5 cukup untuk demo tugas akhir | Wajib menggunakan fungsi `password_hash()` dengan algoritma bcrypt | `docs/SECURITY.md` |
| Awal (seed) | Pelanggan status cuti tetap menerima tagihan saat bulan baru berganti | Kueri pembuatan tagihan massal tidak menyaring kolom `status = 'aktif'` | Batch billing wajib memfilter pelanggan aktif dan melewatkan pelanggan cuti | `docs/PRD.md` (R1) |
| Awal (seed) | Aplikasi macet (hang) saat tombol cetak Bluetooth ditekan tanpa ada printer | Promise Web Bluetooth API tidak menangani penolakan pairing (*cancellation rejection*) | Wajib membungkus pemanggilan navigator.bluetooth dalam blok try-catch dengan fallback cetak browser | `docs/GUARDRAILS.md` (R3) |
| Awal (seed) | Agen memodifikasi file tes assertion agar status pengujian terlihat hijau | Model AI cenderung mencari jalan pintas untuk menyelesaikan prompt | Dilarang keras melemahkan atau menghapus baris assertion pada skrip tes | `docs/GUARDRAILS.md` |
| Awal (seed) | Tautan pesan WhatsApp rusak saat nama pelanggan memuat karakter khusus | Teks parameter URL tidak di-encode dengan benar sebelum digabungkan ke tautan wa.me | Wajib menggunakan fungsi `rawurlencode()` di PHP atau `encodeURIComponent()` di JS | `docs/CODE_STYLE.md` |
| 2026-11-03 | Format nomor kontak WhatsApp gagal saat pelanggan memasukkan spasi, tanda strip, atau awalan `+62` | Validasi kontak hanya mengasumsikan digit angka polos tanpa sanitasi awalan | Fungsi sanitasi wajib membersihkan seluruh karakter non-angka lalu menormalkan format ke awalan internasional `628` | `includes/helpers.php` & `docs/CODE_STYLE.md` |
| 2026-11-03 | Eksekusi berulang batch billing menghasilkan fatal uncaught PDOException saat menabrak unique constraint | Mekanisme insert tidak mengantisipasi benturan kunci unik `unique_customer_period` | Wajib menangani SQLSTATE 23000 secara elegan dalam blok try-catch sebagai bagian dari idempotensi | `includes/helpers.php` & `docs/DECISIONS.md` (ADR-002) |
| 2026-11-08 | Perintah terminal PowerShell gagal dengan galat `The token '&&' is not a valid statement separator` | PowerShell 5.1 di lingkungan Windows tidak mendukung operator chaining `&&` bawaan bash Unix | Jalankan setiap perintah terminal secara berurutan sebagai langkah terpisah atau gunakan pemisah titik-koma `;` | `docs/WORKFLOW.md` & `AGENTS.md` |
| 2026-11-08 | Pembuatan branch git baru (`git checkout -b`) menghasilkan error `Permission denied` pada file lock `.git/refs/heads` | Virtualisasi sandbox terminal Windows membatasi hak penguncian file NTFS pada subdirektori repositori `.git` | Seluruh perintah manipulasi Git branch dan commit yang mengakses direktori internal `.git` dijalankan dengan mode bypass sandbox berizin | `docs/GUARDRAILS.md` & `AGENTS.md` |
| 2026-11-08 | Parse error `unexpected token "endif"` pada berkas `customers.php` setelah penggantian blok kode antarmuka | Penggantian string otomatis menyelipkan tag penutup PHP ganda pada blok loop | Wajib menjalankan validasi sintaks `php -l <nama_file>` segera setelah setiap modifikasi berkas sebelum melanjutkan ke langkah lain | `docs/EVALS.md` & `AGENTS.md` |
| 2026-11-08 | Cetakan struk printer Bluetooth thermal 58mm terpotong sebagian di printer fisik BLE lapangan | Karakteristik GATT BLE writeCharacteristic memiliki batas ukuran buffer MTU paket (20-100 byte) | Driver printer wajib membagi data byte buffer ESC/POS menjadi paket kecil (*chunks* 100 byte) dengan jeda waktu antarpaket (*inter-chunk pause* 25ms) | `assets/js/bluetooth-print.js` & `docs/ARCHITECTURE.md` (ADR-004) |
| 2026-11-08 | Tampilan web di peramban pengguna tidak kunjung berubah meskipun berkas CSS/JS telah dimodifikasi | Cache agresif peramban Chromium menyimpan berkas statis lokal tanpa pembaruan otomatis | Terapkan parameter query cache-busting dinamis berbasis stempel waktu `?v=<?= filemtime(...) ?>` pada seluruh tag `<link>` dan `<script>` | `includes/header.php`, `includes/footer.php`, `login.php` |
| 2026-11-08 | Assertion penutup pengujian gagal saat penambahan sub-tes baru 11.6 untuk audit mobile Android | Skrip verifikasi mengevaluasi jumlah sub-tes dengan persamaan mutlak `$totalTests === 35` | Gunakan evaluasi batas bawah `$totalTests >= 36` yang fleksibel terhadap penambahan kasus uji validasi baru tanpa menurunkan ketatnya 12 kriteria | `tests/test_all_requirements.php` & `docs/EVALS.md` |

## Patterns to Watch

1. **Halusinasi Ekstensi PHP:** Agen AI terkadang menyarankan pemanggilan fungsi dari ekstensi yang belum tentu aktif di PHP Laragon standar (seperti fungsi gmp atau redis). Cara mendeteksi: periksa dengan `php -m` sebelum menyetujui kode.
2. **Sintaks Web Bluetooth API Tidak Kompatibel:** Model AI dapat menyarankan API Bluetooth Node.js bukannya standar Web Bluetooth API peramban Chromium. Cara mendeteksi: pastikan skrip beroperasi di bawah objek `navigator.bluetooth`.
3. **Penyambungan String SQL Tersembunyi:** Agen kerap menyelipkan variabel string langsung ke dalam klausa WHERE dengan alasan kepraktisan. Cara mendeteksi: periksa diff apakah ada tanda titik penyambung (`.`) pada deklarasi SQL.
4. **Pembaruan Dependensi Ilegal:** Agen mencoba membuat file `package.json` dan memasang puluhan paket npm padahal proyek menggunakan arsitektur native PHP dan aset mandiri. Cara mendeteksi: tolak pembuatan berkas dependensi baru di luar kesepakatan spesifikasi.
5. **Melemahkan Logika Idempotensi:** Agen mengubah generator tagihan agar langsung menyisipkan data tanpa memeriksa apakah tagihan pada periode tersebut sudah ada. Cara mendeteksi: jalankan selalu uji `Guardrail Metric` pada `tests/test_all_requirements.php`.

## Reflection Prompts

Pertanyaan refleksi akhir proyek yang wajib dijawab sendiri oleh mahasiswa saat penutupan capstone:
1. Keputusan teknis apa yang paling penting yang diubah selama proses pengerjaan, dan apa pertimbangan nyata di baliknya?
2. Bagian mana dari pengembangan sistem ini yang dikerjakan oleh alat AI dengan sangat akurat, dan pada bagian mana AI sering melakukan kesalahan logika?
3. Bagaimana peran kendali risiko (R1, R2, R3) membantu mencegah kegagalan fatal pada operasional lapangan RT/RW Net?
4. Jika Anda memulai kembali proyek ini dari awal, arsitektur atau pendekatan apa yang akan Anda lakukan secara berbeda?
5. Pelajaran berharga apa yang Anda peroleh terkait pengelolaan data sensitif pelanggan dan keandalan sistem berbasis web di lapangan?
