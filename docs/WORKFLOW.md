# Workflow: Rengganis Net

## Cycle

Setiap task mengikuti siklus yang sama:

`Plan -> Branch -> Implement -> Test -> Review -> Commit -> Pull Request -> Merge -> Update Docs`

1. **Plan:** Tinjau task yang akan dikerjakan di `docs/TASKS.md`, pahami acceptance criteria dan fitur terkait di `docs/PRD.md`.
2. **Branch:** Buat cabang kerja baru dari `main` dengan format nama sesuai task, contoh: `feat/T-003-batch-billing-engine` atau `fix/T-002-cuti-validation`.
3. **Implement:** Tulis kode implementasi secara terfokus pada berkas modul yang relevan.
4. **Test:** Eksekusi skrip pengujian otomatis `php tests/test_all_requirements.php` dan validasi linter `php -l index.php`.
5. **Review (Manusia):** Mahasiswa meninjau perubahan diff kode untuk memastikan logika bisnis sesuai kebutuhan nyata.
6. **Commit:** Buat commit kecil dengan pesan terstruktur mengacu pada `docs/CODE_STYLE.md`.
7. **Pull Request:** Buka PR menuju `main` dengan mencantumkan ID task dan ringkasan pengujian.
8. **Merge:** Lakukan penggabungan cabang ke `main` setelah seluruh alur CI lolos.
9. **Update Docs:** Perbarui status task di `docs/PROGRESS.md` dan dokumentasikan keputusan baru di `docs/DECISIONS.md`.

## Session Start Ritual

1. Baca berkas `AGENTS.md` untuk memahami konteks dan aturan kerja utama.
2. Periksa status terkini pada `docs/PROGRESS.md` untuk melihat task terakhir yang telah diselesaikan.
3. Baca `docs/LESSONS.md` untuk mengingat kesalahan sebelumnya agar tidak terulang.
4. Pilih tepat satu task berstatus `todo` dari `docs/TASKS.md`, lalu ubah statusnya menjadi `doing`.
5. Pastikan working tree Git dalam keadaan bersih (`git status`), lalu buat branch baru sesuai nama task (jangan bekerja langsung di `main`).

## Session End Ritual

1. Jalankan seluruh gerbang kualitas yang tercantum di `docs/EVALS.md` (`php tests/test_all_requirements.php` dan `php -l index.php`).
2. Buat commit logis terstruktur dengan pesan Conventional Commits yang merujuk ID task terkait.
3. Perbarui status task di `docs/TASKS.md` menjadi `done` dan cantumkan ringkasan di `docs/PROGRESS.md`.
4. Jika ada keputusan arsitektur atau teknis baru, catat di `docs/DECISIONS.md`. Jika ada kegagalan agen, catat di `docs/LESSONS.md`.
5. Dorong branch ke remote repositori (`git push origin <nama-branch>`), buka Pull Request, berhenti, dan minta review manusia.

## Branching Strategy

- Cabang `main` dilindungi dan selalu mencerminkan kode stabil yang siap digunakan di lapangan.
- Cabang fitur baru menggunakan format: `feat/T-nnn-nama-singkat`.
- Cabang perbaikan kutu menggunakan format: `fix/T-nnn-nama-singkat`.
- Cabang pembaruan dokumentasi menggunakan format: `docs/nama-singkat`.
- Satu cabang hanya menangani satu task spesifik; setelah digabungkan (*merge*), cabang kerja segera dihapus.
- Dilarang keras melakukan commit langsung ke `main`.
- Sebelum mengajukan Pull Request, lakukan sinkronisasi branch dengan `main` melalui rebase atau merge untuk mencegah konflik.

## Commit and Pull Request Rules

- Format commit wajib mematuhi Conventional Commits (`tipe(lingkup): ringkasan imperatif`) maksimal 72 karakter (lihat [CODE_STYLE](file:///d:/laragon/www/rengganis.net/docs/CODE_STYLE.md)).
- Isi Pull Request wajib memuat:
  1. Ringkasan tujuan perubahan dan ID task (misal T-003).
  2. ID fitur PRD yang terdampak (misal F3).
  3. Bukti hasil pengujian gerbang EVALS (`php tests/test_all_requirements.php`).
  4. Tangkapan layar antarmuka atau foto hasil cetak struk Bluetooth bila relevan.
- Syarat merge ke `main`: seluruh pemeriksaan CI GitHub Actions berstatus hijau dan telah disetujui melalui satu review manusia.

## Human-in-the-Loop Checkpoints

| Titik Kontrol | Siapa | Yang Diperiksa |
|---|---|---|
| Sebelum Merge ke `main` | Mahasiswa / Pengembang | Memverifikasi seluruh acceptance criteria terpenuhi dan CI hijau |
| Sebelum Menambah Dependensi Baru | Mahasiswa | Memastikan lisensi terbuka dan dependensi benar-benar dibutuhkan |
| Sebelum Mengubah Skema Database | Mahasiswa | Memastikan migrasi tidak merusak data pelanggan atau tagihan yang ada |
| Sebelum Perintah Destruktif | Mahasiswa | Memastikan perintah seperti penghapusan tabel MySQL dilakukan sengaja |
| Sebelum Integrasi Layanan Berbayar | Mahasiswa | Memastikan tidak ada biaya tak terduga (misal kuota API WhatsApp berbayar) |
| Sebelum Memakai Data Riil Lapangan | Mahasiswa & Dosen | Memastikan kepatuhan UU PDP dan anonimisasi data pribadi |

## Handling Blockers

1. Jika agen AI atau mahasiswa mengalami kebuntuan (*blocked*), coba maksimal 3 pendekatan solusi teknis yang berbeda secara berurutan.
2. Catat seluruh percobaan yang gagal dan kendalanya secara transparan pada `docs/PROGRESS.md`.
3. Tandai status task di `docs/TASKS.md` menjadi `blocked`.
4. Minta bantuan kepada dosen pembimbing atau rujuk dokumentasi resmi PHP dan MySQL.
5. Agen dilarang keras mencoba melewati *blocker* dengan cara mematikan fungsi pengujian, melemahkan validasi keamanan, atau mengarang sintaks API.

## Level Notes

- **Level Dasar (AKTIF):** Satu pengembang mandiri, alur task terarah, gerbang pengujian lokal otomatis terverifikasi via skrip PHP dan CI GitHub Actions.
- **Level Menengah:** Alur kolaborasi multi-pengembang dengan review kode rekan tim dan staging deployment otomatis.
- **Level Loop:** Agen AI berputar mandiri secara berulang dengan batasan ketat (merujuk pada berkas `docs/LOOP.md`).
