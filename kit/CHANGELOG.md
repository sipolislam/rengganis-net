# Changelog

## 1.2.0 — 2026-10-20

Hasil pilot uji lintas model (lihat `CROSS-MODEL-TEST.md` bagian 8).

### Ditambahkan
- Pertanyaan C-19 (nama aplikasi), C-20 (tujuan, metrik, non-goal), C-14b (kendali risiko R1… yang disetujui mahasiswa), I-15 (perilaku saat gagal untuk IoT: sensor error, jaringan putus, boot, konflik manual vs otomatis, notifikasi offline).
- Fase 2: pemeriksaan konflik antar kriteria acceptance sebelum SPEC dikonfirmasi; `meta.created_date`; `meta.assistant_proposed` untuk menandai nilai yang diusulkan asisten.
- `stack.commands.ci_setup` (mis. Laravel `key:generate`) yang dirender ke CI.
- DECISIONS: bagian *Assistant Proposals*. HARDWARE: bagian *Failure Behavior*.
- Validator: RISK, REFS (rujukan ADR/task yang hilang), PROPOSED, FAILSAFE, filler tanggal; konsistensi tidak lagi memberi PASS bila dokumen belum ada; sintaks Blade `{{ }}` tidak lagi dianggap slot.
- Aturan: dilarang mengklaim kemampuan atau pengalaman mahasiswa yang tidak ia nyatakan; contoh yang menyebut ID task/ADR harus benar; T-001 harus bisa selesai sendiri dengan CI hijau.
- Baseline: PlatformIO butuh `-e <env>`, tidak punya lockfile, tidak didukung Dependabot; catatan starter kit Laravel.
- RUBRIC A28–A34. Selftest 47 uji.

### Diubah
- Kolom "Alternatif" untuk lapisan stack pilihan mahasiswa kini "-" (rekomendasi kit cukup sekali di ADR-002).
- Label "ilustrasi" hanya untuk kutipan yang dirumuskan asisten.
- Skrip tidak lagi membuat `__pycache__` di dalam `kit/`; nama alat AI dinormalisasi ("claude-code" = "Claude Code").

## 1.1.0 — 2026-10-20

### Ditambahkan
- **Tech stack bebas.** C-16 (rekomendasi / stack sendiri / campuran) dan C-17 (ekosistem dan perintah). Bidang baru di SPEC: `stack.source`, `stack.custom_reason`, `stack.custom_layers`, `stack.ecosystems`, `stack.commands`, `stack.versions`. Pertanyaan pemilihan teknologi di bank platform ditandai `[STACK]`.
- `tech-baseline.md` bagian `[custom]`: aturan menghormati pilihan mahasiswa, serta perintah cek versi dan alat lint/tes untuk 9 ekosistem.
- **CI, `.gitignore`, dan Dependabot per ekosistem** (`tools/extras_lib.py`): node, python, php, dart-flutter, go, rust, jvm, dotnet, platformio, other; bisa digabung (mis. Tauri = node + rust).
- **Platform pendukung** (`meta.secondary_platforms`), misalnya mobile dengan panel admin web.
- **Kondisi awal proyek** (`meta.starting_point`, C-18): untuk proyek yang sudah punya kode, T-001 menjadi audit repositori.
- File penunjuk `CLAUDE.md` / `GEMINI.md` dibuat otomatis bila alat tersebut dipakai.
- Validator: pemeriksaan COMMANDS, CUSTOM, SECONDARY, CI; keluaran UTF-8 aman untuk konsol Windows.
- RUBRIC: butir A24–A27 dan dimensi B "Penghormatan pilihan" (total 24). Uji lintas model: ide U6 (stack sendiri).
- Selftest: varian Flutter + Laravel dan 9 mutasi baru (38 uji).

### Diubah
- Perintah di AGENTS.md, TESTING.md, dan EVALS.md kini disisipkan langsung dari `stack.commands` (slot `{{spec:...}}`), bukan ditulis ulang oleh model.
- CI lama per platform (hanya npm, dengan `cache: npm` yang gagal tanpa `package-lock.json`) diganti CI yang dirender dari SPEC.
- Bundel chat langkah 06 menyediakan folder per ekosistem; CI berisi `GANTI-PERINTAH` yang sengaja gagal sampai diganti.

### Diketahui
- Uji lintas model belum dijalankan.

## 1.0.0 — 2026-10-20
- Rilis awal: START, bank pertanyaan 4 platform, skema SPEC, 16 template + HARDWARE/LOOP, validator, bundel chat, contoh CatatKas, RUBRIC.
