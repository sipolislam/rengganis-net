# Code Style: Rengganis Net

## Language and Tooling

- **Bahasa & Lingkungan:** PHP 8.4 Native (dengan PDO MySQL) dan Vanilla JavaScript (ES6+) pada sisi frontend.
- **Linter & Pemeriksa Sintaks:** Validasi sintaks kode PHP menggunakan CLI bawaan PHP.
- **Perintah Lint Resmi:**
  ```powershell
  php -l index.php
  ```
- **Aturan Penulisan:** Mengikuti standar umum PSR-12 untuk penulisan PHP (indentasi 4 spasi, kurung kurawal pembuka class dan function pada baris baru, kurung kurawal alur kontrol pada baris yang sama).

## Naming

| Objek | Konvensi | Contoh di Proyek Ini |
|---|---|---|
| Berkas Controller / Halaman | kebab-case / snake_case `.php` | `bills.php`, `customers.php`, `print-report.php` |
| Berkas Skrip JavaScript | kebab-case `.js` | `bluetooth-print.js`, `html2canvas.min.js` |
| Folder / Direktori | lowercase singkat | `api/`, `assets/`, `config/`, `includes/`, `tests/`, `docs/` |
| Variabel PHP | camelCase | `$customerId`, `$monthlyBill`, `$cutiCustomer` |
| Fungsi PHP | camelCase | `generateMonthlyBills()`, `processBillPayment()`, `getSetting()` |
| Kelas / Objek | PascalCase | `DatabaseConnection`, `BluetoothPrinterDriver` |
| Konstanta | UPPER_SNAKE_CASE | `APP_NAME`, `MAX_PRINT_WIDTH_58MM` |
| Tabel Basis Data | snake_case jamak | `users`, `customers`, `packages`, `bills`, `status_logs` |
| Kolom Basis Data | snake_case tunggal | `customer_code`, `period_month`, `payment_method` |
| Variabel Lingkungan | UPPER_SNAKE_CASE | `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` |

## Structure and Patterns

1. **Pemisahan Tanggung Jawab:**
   - Direktori `config/` hanya mengurus koneksi basis data PDO dan inisialisasi lingkungan.
   - Direktori `includes/` memuat logika autentikasi (`auth.php`), fungsi pembantu kalkulasi bisnis (`helpers.php`), dan kerangka layout (`header.php`, `footer.php`).
   - Direktori `api/` menyediakan endpoint JSON untuk request asinkronus (AJAX).
   - Berkas controller di root menangani penerimaan input, validasi hak akses, dan perenderan antarmuka.
2. **Ukuran Berkas & Fungsi:** Fungsi bisnis disarankan berukuran maksimal 60 baris kode dan berfokus pada satu tanggung jawab logis.
3. **Contoh Kode Benar vs Salah (Fitur F3 - Batch Billing):**

*Kode Benar (Menggunakan PDO Prepared Statement & Validasi Status Cuti):*
```php
// BENAR: Menggunakan prepared statement dan melewati pelanggan status cuti
$stmt = $pdo->prepare("SELECT id, package_id FROM customers WHERE status = 'aktif'");
$stmt->execute();
$activeCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($activeCustomers as $cust) {
    // Periksa apakah tagihan periode berjalan sudah ada (idempotensi)
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE customer_id = ? AND period_month = ? AND period_year = ?");
    $checkStmt->execute([$cust['id'], $month, $year]);
    if ($checkStmt->fetchColumn() == 0) {
        $insertStmt = $pdo->prepare("INSERT INTO bills (bill_no, customer_id, period_month, period_year, amount, status) VALUES (?, ?, ?, ?, ?, 'belum_lunas')");
        $insertStmt->execute([$billNo, $cust['id'], $month, $year, $amount]);
    }
}
```

*Kode Salah (Rentan SQL Injection & Menerbitkan Tagihan Fiktif untuk Pelanggan Cuti):*
```php
// SALAH: Penggabungan string SQL dan tidak mengecek status cuti
$customers = $pdo->query("SELECT * FROM customers")->fetchAll();
foreach ($customers as $c) {
    // Bahaya: Tanpa prepared statement dan menagih semua orang termasuk yang cuti
    $pdo->query("INSERT INTO bills (customer_id, amount) VALUES (" . $c['id'] . ", 150000)");
}
```

## Error Handling

1. **Jangan Menelan Galat Diam-diam:** Dilarang menggunakan blok `catch` kosong tanpa penanganan log atau pesan pengguna.
2. **Penanganan Eksepsi PDO:** Seluruh transaksi basis data dibungkus dalam blok `try-catch (PDOException $e)` dengan mekanisme `rollback()` jika transaksi terputus di tengah jalan.
3. **Pemisahan Pesan Pengguna vs Log:** Pengguna akhir melihat pesan notifikasi bersahabat ("Terjadi kendala saat memproses tagihan, silakan coba beberapa saat lagi"), sementara pesan detail teknis ditulis ke berkas log server pengembang.
4. **Ketahanan Jaringan Lapangan:** Jika koneksi Bluetooth ke printer mini thermal terputus saat cetak struk, sistem tidak boleh macet (hang), melainkan menampilkan dialog bantuan koneksi ulang (*reconnect*) dan mengaktifkan tombol fallback cetak browser.

## Comments and Documentation

1. Komentar kode difokuskan untuk menjelaskan **"mengapa"** (alasan aturan bisnis), bukan menjelaskan apa yang sudah jelas terbaca dari sintaks kode.
2. Setiap fungsi penting pada `includes/helpers.php` wajib memiliki blok docstring yang menjelaskan parameter masukan, tipe keluaran, dan fungsi bisnisnya.
3. Dilarang meninggalkan blok kode mati atau kode uji coba lama yang dikomentari di branch `main`.
4. Setiap penambahan fitur atau perubahan aturan wajib dicatat pada `docs/PROGRESS.md` dan `docs/DECISIONS.md`.

## Commit Messages

Mengikuti format standar Conventional Commits: `tipe(ruang-lingkup): ringkasan imperatif` (maksimal 72 karakter).

| Tipe | Ruang Lingkup | Contoh Nyata Proyek Ini | Task Terkait |
|---|---|---|---|
| `feat` | billing | `feat(billing): implement idempotency check in batch generation` | T-003 |
| `feat` | printer | `feat(printer): add Bluetooth ESC/POS reconnection handler` | T-005 |
| `fix` | customer | `fix(customer): prevent bill creation for cuti status` | T-002 |
| `fix` | auth | `fix(auth): sanitize username input on login form` | T-001 |
| `docs` | spec | `docs(spec): finalize PRD and architecture specification` | T-006 |
| `test` | suite | `test(suite): add verification for duplicate billing guardrail` | T-004 |
| `refactor` | receipt | `refactor(receipt): streamline html2canvas image capture modal` | T-005 |
| `chore` | git | `chore(git): update .gitignore and GitHub Actions workflow` | T-007 |

## Dependencies

1. Setiap dependensi tambahan wajib memiliki alasan kebutuhan yang nyata, berlisensi terbuka (MIT/Apache 2.0), dan aktif dipelihara.
2. Verifikasi keaslian dan integritas berkas pustaka eksternal (misal `html2canvas.min.js`) sebelum dimasukkan ke dalam folder `assets/js/`.
3. Menghindari penambahan framework atau pustaka besar yang tidak diperlukan jika fungsionalitas dapat dipenuhi dengan fitur bawaan peramban atau fungsi native PHP.
4. Setiap penambahan pustaka frontend atau dependensi baru wajib dicatat pada `docs/DECISIONS.md`.

## Forbidden Patterns

Daftar pola terlarang yang tidak boleh ada di repositori Rengganis Net:
1. Menulis rahasia, kata sandi basis data, atau API key langsung di dalam berkas kode sumber.
2. Melakukan query basis data dengan penggabungan string (`$query = "SELECT * FROM ... " . $input`).
3. Menonaktifkan pemeriksaan linter atau mematikan skrip pengujian agar pekerjaan terlihat selesai.
4. Mengubah atau menghapus skrip tes agar lulus tanpa memperbaiki akar penyebab galat di kode aplikasi.
5. Membiarkan blok kode mati (*dead code*) atau fungsi usang yang tidak pernah dipanggil.
6. Memasang dua pustaka berbeda untuk menyelesaikan satu tugas yang sama (misal memasang dua library cetak struk berbeda).
7. Menampilkan pesan galat internal MySQL (seperti syntax error atau nama tabel) langsung ke layar pengguna publik.
8. Menerbitkan tagihan baru untuk pelanggan yang berstatus "Cuti" saat proses batch billing bulanan berlangsung.

## AI-Generated Code Rules

1. Mahasiswa sebagai penanggung jawab proyek wajib memahami alur dan maksud dari setiap baris kode yang dihasilkan oleh agen AI.
2. Permintaan perubahan kode kepada agen harus dipecah menjadi task-task kecil yang terisolasi dan terarah sesuai `docs/TASKS.md`.
3. Selalu jalankan verifikasi linter (`php -l index.php`) dan pengujian otomatis (`php tests/test_all_requirements.php`) sebelum menerima perubahan dari agen.
4. Mahasiswa dilarang menerima saran kode yang menggunakan pustaka fiktif atau fungsi yang tidak didukung oleh versi PHP 8.4.
5. Setiap kekeliruan berulang atau kegagalan agen AI wajib dicatat pada `docs/LESSONS.md`.
