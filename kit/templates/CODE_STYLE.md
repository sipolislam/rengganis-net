# Code Style: {{spec:meta.app_name}}

## Language and Tooling

{{WRITE: bahasa dan versi (dari spec.stack.versions bila ada; selain itu rujuk tech-baseline.md, tanpa angka karangan), pemformat dan linter standar ekosistem spec.stack.ecosystems (lihat tabel di tech-baseline.md bagian [custom]; mis. ESLint + Prettier/Biome untuk TypeScript, Ruff untuk Python, Pint untuk Laravel, flutter analyze untuk Dart, rustfmt dan clippy untuk Rust), mode ketat bila tersedia, dan perintah lint persis spec.stack.commands.lint.}}

## Naming

{{WRITE: tabel Objek, Konvensi, Contoh yang memakai istilah nyata proyek ini (entitas dari spec.data.entities, fitur dari spec.features): berkas, folder, variabel, fungsi, tipe/kelas, konstanta, komponen, tabel dan kolom basis data, variabel lingkungan, topik MQTT untuk iot.}}

## Structure and Patterns

{{WRITE: aturan struktur kode sesuai framework di spec.stack: pemisahan tanggung jawab (UI, logika, akses data), ukuran maksimum fungsi dan berkas yang disarankan, pola yang dipakai, dan pola yang dihindari. Beri satu contoh kecil kode yang benar dan salah untuk satu fitur nyata dari spec.features.}}

## Error Handling

{{WRITE: aturan penanganan galat: tidak menelan galat diam-diam, galat yang bisa dipulihkan dibanding fatal, pesan untuk pengguna dibanding log untuk pengembang, perilaku saat jaringan putus (terutama bila spec.user_conditions memuat koneksi lambat atau meta.platform adalah mobile/iot).}}

## Comments and Documentation

{{WRITE: komentar menjelaskan "mengapa" bukan "apa"; docstring untuk fungsi publik; tidak meninggalkan kode yang dikomentari; pembaruan dokumen bersamaan dengan kode (PROGRESS.md, DECISIONS.md bila ada keputusan baru).}}

## Commit Messages

Format Conventional Commits: `tipe(ruang-lingkup): ringkasan imperatif`.

{{WRITE: tabel tipe yang dipakai (feat, fix, docs, test, refactor, chore, ci) dengan satu contoh nyata per tipe untuk proyek ini, satu commit satu perubahan logis, ringkasan maksimal 72 karakter, rujuk ID task (mis. T-003) di badan commit atau PR.}}

## Dependencies

{{WRITE: aturan menambah dependensi: dibutuhkan nyata, dipelihara aktif, lisensi kompatibel, verifikasi nama paket benar-benar ada dan milik penerbit yang dimaksud sebelum instal (waspadai nama paket karangan atau salah eja), lockfile di-commit, pin versi untuk produksi, catat dependensi besar di DECISIONS.md.}}

## Forbidden Patterns

{{WRITE: daftar larangan konkret untuk proyek ini, minimal delapan: rahasia di kode, query SQL hasil penyambungan string, any tanpa alasan (TypeScript), menonaktifkan lint atau tes agar lulus, mengubah tes agar lulus tanpa memperbaiki penyebab, kode tak terpakai, dependensi ganda untuk fungsi yang sama, pesan galat yang membocorkan detail internal, ditambah larangan khusus platform dari spec.meta.platform.}}

## AI-Generated Code Rules

{{WRITE: aturan untuk kode buatan AI: penulis (mahasiswa) wajib bisa menjelaskan setiap perubahan; perubahan diminta kecil dan fokus satu tugas; jalankan tes dan lint sebelum menerima; periksa keberadaan API/fungsi/paket yang disarankan; jangan menerima kode yang tidak dipahami; catat kesalahan berulang agen di LESSONS.md.}}
