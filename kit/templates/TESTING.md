# Testing: {{spec:meta.app_name}}

## Strategy

{{WRITE: satu paragraf strategi pengujian sesuai spec.stack.testing, spec.meta.level, dan platform spec.meta.platform. Jelaskan piramida pengujian yang dipakai (banyak unit, sedikit E2E) dan mengapa cukup untuk proyek berdurasi spec.meta.duration_weeks minggu.}}

## Test Types

{{WRITE: tabel Jenis, Alat, Lokasi berkas, Kapan dijalankan. Alat harus berasal dari spec.stack.testing dan tech-baseline.md (web: Vitest atau Jest plus Playwright; mobile: Jest dan React Native Testing Library plus Maestro; desktop: unit plus E2E jendela utama; iot: pio test di komputer plus simulasi Wokwi plus uji perangkat nyata). Untuk setiap jenis, tulis perintah menjalankannya.}}

## What Must Be Tested

{{WRITE: daftar wajib uji turunan dari spec.features: untuk setiap fitur dengan prioritas must, sebut ID fitur, perilaku kunci yang diuji, dan jenis tes. Tambahkan wajib uji keamanan: otorisasi per peran, validasi input, dan perilaku saat galat/jaringan putus.}}

## Commands

Perintah utama (dari `stack.commands`, identik dengan AGENTS.md, EVALS.md, dan CI):

| Tujuan | Perintah |
|---|---|
| Instal | `{{spec:stack.commands.install}}` |
| Lint | `{{spec:stack.commands.lint}}` |
| Semua tes | `{{spec:stack.commands.test}}` |
| Build | `{{spec:stack.commands.build}}` |

{{WRITE: perintah tambahan yang lazim untuk alat tes di spec.stack.testing dan ekosistem spec.stack.ecosystems: menjalankan satu berkas tes, mode watch, pemeriksaan tipe (spec.stack.commands.typecheck bila tidak kosong), dan tes E2E bila ada. Jangan menciptakan skrip yang tidak ada di stack; bila skrip perlu dibuat saat setup, tulis bahwa task setup (T-001 atau T-002) yang membuatnya.}}

## Coverage and Quality Bar

{{WRITE: target cakupan realistis (saran: logika bisnis tinggi, UI wajar; jangan memaksa 100 persen), aturan bahwa tes tidak boleh dihapus atau dilemahkan agar lulus, dan aturan tes baru untuk setiap bug yang diperbaiki.}}

## Test Data

{{WRITE: cara menyiapkan data uji: data fiktif/seed, tidak memakai data pribadi asli (terutama bila spec.data.personal_data bukan none), reset basis data uji, dan penggunaan sandbox untuk layanan eksternal (mis. pembayaran).}}

## Manual Test Checklist

{{WRITE: daftar periksa uji manual yang dapat dicentang, spesifik untuk platform: web (peramban utama, layar kecil, keyboard saja), mobile (perangkat kelas menengah, mode offline, izin ditolak, rotasi), desktop (sistem operasi target, instal bersih, pembaruan), iot (daya dinyalakan ulang, jaringan putus dan pulih, sensor lepas, kegagalan relay aman). Minimal sepuluh butir.}}

## Definition of Done

{{WRITE: daftar periksa selesai satu task: kriteria acceptance fitur terpenuhi, tes baru ditulis dan lulus, lint dan tipe bersih, build berhasil, tidak ada rahasia di diff, dokumen terkait (PROGRESS, DECISIONS) diperbarui, PR di-review.}}
