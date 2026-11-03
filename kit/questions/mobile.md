# Bank Pertanyaan — Mobile

Muat setelah C-02 = Mobile. Versi dan nama teknologi: `tech-baseline.md` bagian `[mobile]`. Opsi **(Rekomendasi pemula)** adalah default untuk jawaban Z.


> **Stack bebas:** pertanyaan bertanda **[STACK]** memilih teknologi. Jika `stack.source` = `custom` atau `mixed` (C-16), untuk lapisan yang sudah ditentukan mahasiswa jangan tampilkan opsinya; konfirmasi isiannya dan tetap ajukan bagian "Tambahkan:". Opsi di bawah adalah **rekomendasi, bukan batasan**; jawaban teks bebas berupa teknologi lain selalu diterima.

### M-01 [STACK] ★ Framework
- A. **Expo (React Native + TypeScript)** — satu basis kode untuk Android dan iOS, uji cepat di ponsel lewat Expo Go, build awan lewat EAS. **(Rekomendasi pemula)**
- B. Flutter (Dart) — tampilan konsisten, performa baik; bahasa baru bagi yang belum mengenal Dart.
- C. Android native (Kotlin + Jetpack Compose) — kendali penuh, hanya Android.
- D. Lainnya (sebutkan).
→ spec: `platform_specific.mobile.framework`, `stack.frontend`

### M-02 ★ Sistem operasi target
- A. Android saja — paling mudah dan murah untuk didistribusikan. **(Rekomendasi pemula)**
- B. Android dan iOS — uji iOS butuh perangkat Apple dan akun developer berbayar untuk distribusi.
- C. iOS saja.
Tambahkan: versi Android minimum (saran: ikuti default framework; jangan menebak angka) dan apakah perangkat pengguna kelas menengah ke bawah.
→ spec: `platform_specific.mobile.target_os`, `min_android`

### M-03 ★ Mode offline
- A. Tidak perlu offline.
- B. **Cache baca** — data terakhir tetap terlihat saat offline; penulisan butuh internet. **(Rekomendasi pemula)**
- C. **Offline-first** — pengguna dapat menulis saat offline dan disinkronkan nanti; butuh aturan penyelesaian konflik, jauh lebih kompleks.
→ spec: `platform_specific.mobile.offline`

### M-04 [STACK] ★ Backend dan data
- A. **Backend terkelola** (Supabase/Firebase) — tanpa server sendiri. **(Rekomendasi pemula)**
- B. API buatan sendiri (Node/FastAPI/Laravel) — lebih fleksibel, lebih banyak pekerjaan.
- C. Hanya data lokal di perangkat (SQLite) — tanpa akun, tanpa sinkronisasi.
Tambahkan: "Entitas data utama dan field-nya?" → `data.entities[]`
→ spec: `stack.backend`, `stack.database`

### M-05 [STACK] Autentikasi
- A. Email + password (layanan terkelola).
- B. Login Google.
- C. OTP nomor telepon (biaya SMS/WhatsApp perlu diperhitungkan).
- D. Tanpa login.
→ spec: `stack.auth`, `security.auth_method`

### M-06 Fitur perangkat (boleh lebih dari satu)
- A. Kamera/galeri
- B. Lokasi (GPS)
- C. Notifikasi push
- D. Biometrik (sidik jari/wajah)
- E. Bluetooth/BLE
- F. Penyimpanan file
- G. Tidak ada
Setiap izin (permission) harus dijelaskan alasannya kepada pengguna; catat di `SECURITY.md`.
→ spec: `platform_specific.mobile.native_features`

### M-07 Distribusi
- A. Berbagi APK langsung untuk demo/tugas. **(Rekomendasi pemula)**
- B. Google Play internal testing.
- C. TestFlight/App Store (butuh akun Apple Developer berbayar).
- D. Hanya dijalankan di emulator/Expo Go.
→ spec: `platform_specific.mobile.distribution`, `stack.hosting`

### M-08 Tampilan
Tanyakan nada visual, warna utama, mode gelap, dan apakah memakai pustaka komponen (mis. React Native Paper) atau komponen sendiri. Ingatkan target sentuh minimal 44–48 dp dan teks yang terbaca di bawah sinar matahari bila relevan.
→ spec: `design.*`

### M-09 [STACK] Pengujian
- A. Jest + React Native Testing Library untuk logika dan komponen; Maestro untuk satu alur E2E. **(Rekomendasi pemula)**
- B. Hanya Jest.
- C. Uji manual dengan checklist di perangkat nyata (wajib dilengkapi walau memilih A/B).
→ spec: `stack.testing`

### M-10 ★ Kata kunci stack
Berlaku juga untuk stack pilihan mahasiswa: ambil kata kunci dari teknologi yang ia sebut.
Kamu merumuskan 3–6 kata kunci, mis. `Expo`, `React Native`, `TypeScript`, `Supabase`, dan konfirmasikan ke mahasiswa.
→ spec: `stack.keywords`
