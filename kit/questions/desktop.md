# Bank Pertanyaan — Desktop

Muat setelah C-02 = Desktop. Versi dan nama teknologi: `tech-baseline.md` bagian `[desktop]`.


> **Stack bebas:** pertanyaan bertanda **[STACK]** memilih teknologi. Jika `stack.source` = `custom` atau `mixed` (C-16), untuk lapisan yang sudah ditentukan mahasiswa jangan tampilkan opsinya; konfirmasi isiannya dan tetap ajukan bagian "Tambahkan:". Opsi di bawah adalah **rekomendasi, bukan batasan**; jawaban teks bebas berupa teknologi lain selalu diterima.

### D-01 [STACK] ★ Framework
- A. **Tauri 2 (Rust + web UI)** — installer kecil dan model keamanan ketat; bagian Rust minimal bisa dibantu agen. **(Rekomendasi pemula bila nyaman dengan web)**
- B. **Electron** — Chromium dan Node penuh, ekosistem terbesar; ukuran aplikasi besar.
- C. Flutter desktop — satu basis kode dengan mobile bila diperlukan.
- D. .NET (WinUI/WPF/MAUI) — cocok bila hanya Windows dan sudah akrab dengan C#.
→ spec: `platform_specific.desktop.framework`, `stack.frontend`, `stack.backend`

### D-02 ★ Sistem operasi target
Boleh lebih dari satu: Windows, macOS, Linux. Ingatkan bahwa menguji dan membangun untuk macOS memerlukan perangkat Apple.
→ spec: `platform_specific.desktop.os_targets`

### D-03 [STACK] ★ Penyimpanan data
- A. **SQLite lokal** — satu file, tanpa server. **(Rekomendasi pemula)**
- B. Berkas biasa (JSON/CSV) — hanya untuk data kecil.
- C. Basis data jarak jauh + sinkronisasi — butuh backend dan strategi offline.
Tambahkan: "Entitas data utama dan field-nya?"
→ spec: `stack.database`, `data.entities[]`

### D-04 Akses perangkat keras/sistem (boleh lebih dari satu)
- A. Baca/tulis berkas pengguna
- B. Printer
- C. Port serial/USB (mis. Arduino, scanner)
- D. Kamera/mikrofon
- E. Notifikasi sistem
- F. Tidak ada
Setiap izin harus dideklarasikan eksplisit dan dicatat di `SECURITY.md`.
→ spec: `platform_specific.desktop.hardware_access`

### D-05 Pembaruan otomatis dan distribusi
- A. Tanpa pembaruan otomatis; kirim installer manual. **(Rekomendasi pemula)**
- B. Pembaruan otomatis dari GitHub Releases.
Distribusi: installer Windows (MSI/NSIS), DMG/PKG macOS, AppImage/DEB Linux. Jelaskan bahwa distribusi publik yang tepercaya membutuhkan code signing (dan notarization di macOS).
→ spec: `platform_specific.desktop.updater`, `platform_specific.desktop.distribution`

### D-06 [STACK] Autentikasi dan sinkronisasi
- A. Tanpa akun (aplikasi lokal).
- B. Akun layanan terkelola untuk sinkronisasi antar perangkat.
→ spec: `stack.auth`, `security.auth_method`

### D-07 Tampilan
Nada visual, warna utama, mode gelap, kepadatan informasi (aplikasi desktop sering butuh tabel/pintasan keyboard). Wajibkan navigasi keyboard dan fokus terlihat.
→ spec: `design.*`

### D-08 [STACK] Pengujian
- A. Unit test logika + satu alur E2E (Playwright/WebDriver) untuk jendela utama. **(Rekomendasi pemula)**
- B. Unit test saja.
- C. Uji manual per sistem operasi dengan checklist (wajib dilengkapi).
→ spec: `stack.testing`

### D-09 ★ Kata kunci stack
Berlaku juga untuk stack pilihan mahasiswa: ambil kata kunci dari teknologi yang ia sebut.
Kamu merumuskan 3–6 kata kunci, mis. `Tauri`, `Rust`, `React`, `SQLite`, dan konfirmasikan.
→ spec: `stack.keywords`
