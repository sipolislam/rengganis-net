# Evals: {{spec:meta.app_name}}

Evals adalah kriteria lulus yang **dapat dicek mesin**. Agen dan manusia memakai dokumen ini untuk memutuskan apakah suatu pekerjaan selesai.

## Purpose

{{WRITE: dua sampai tiga kalimat: mengapa evals dipakai di proyek ini dan bagaimana kaitannya dengan level spec.meta.level (dasar: gerbang manual dan lokal; menengah: gerbang di CI; loop: gerbang otomatis yang menghentikan putaran agen).}}

## Quality Gates

Semua gerbang di tabel harus lulus sebelum merge ke `main`.

| Gerbang | Perintah | Kriteria lulus | Dijalankan di |
|---|---|---|---|
| Lint | `{{spec:stack.commands.lint}}` | Tanpa galat | Lokal dan CI |
| Tes | `{{spec:stack.commands.test}}` | Semua tes lulus | Lokal dan CI |
| Build | `{{spec:stack.commands.build}}` | Build berhasil | Lokal dan CI |

{{WRITE: lanjutkan dengan gerbang tambahan sebagai daftar atau tabel kedua: pemeriksaan tipe (spec.stack.commands.typecheck bila tidak kosong), tes E2E bila ada, pemindaian dependensi yang sesuai ekosistem spec.stack.ecosystems (mis. npm audit, pip-audit, composer audit, cargo audit, dotnet list package --vulnerable), dan secret scanning GitHub. Untuk iot sertakan uji perangkat nyata manual; untuk mobile dan desktop pemeriksaan konfigurasi build yang relevan. Jangan mengubah teks perintah di tabel di atas.}}

## Feature Acceptance Evals

{{WRITE: tabel untuk SETIAP fitur di spec.features tanpa kecuali: kolom ID fitur, Eval (nama tes atau skenario), Cara dijalankan (otomatis dengan perintah, atau manual dengan langkah), Kriteria lulus. Kriteria diturunkan langsung dari spec.features[].acceptance; fitur must wajib punya eval otomatis, fitur should/could boleh manual. Setelah baris fitur, tambahkan satu baris untuk SETIAP kendali risiko R1, R2, dan seterusnya dari spec.student_reflection.risk_controls (kolom ID berisi R1 dan seterusnya) dengan eval otomatis bila mungkin. Jangan menambah kriteria yang tidak ada di spec.}}

## CI Configuration

{{WRITE: jelaskan alur .github/workflows/ci.yml: pemicu (pull request dan push ke main), urutan langkah, versi runtime yang dipakai (ikuti tech-baseline.md), cache dependensi, dan bahwa branch main dilindungi sehingga PR hanya bisa merge bila CI hijau. Sebutkan bahwa versi GitHub Actions harus dicek ulang dan untuk produksi di-pin ke SHA.}}

## Regression Policy

{{WRITE: aturan: setiap bug yang diperbaiki mendapat tes yang gagal sebelum perbaikan dan lulus sesudahnya; tes yang gagal tidak boleh dinonaktifkan; jika tes harus berubah, jelaskan alasannya di PR dan DECISIONS.md.}}

## Failure Handling

{{WRITE: apa yang dilakukan saat gerbang gagal: baca log, perbaiki penyebab, jalankan ulang; batas percobaan perbaikan otomatis oleh agen (saran: maksimal 3 kali per masalah, lalu berhenti dan minta manusia); cara mencatat ke LESSONS.md.}}
