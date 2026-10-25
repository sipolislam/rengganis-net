# Tech Baseline — Acuan Teknologi Terkini

**Terakhir diverifikasi: 2026-10-20** (pencarian web). Dosen: perbarui tanggal ini dan isi file ini setiap awal semester.

## Aturan Pemakaian (wajib untuk model)

1. File ini adalah **satu-satunya sumber** untuk nama teknologi dan versi yang boleh disebut sebagai "terkini". Jangan menambah angka versi dari ingatanmu.
2. Jika kamu punya akses web/shell, verifikasi dulu dengan perintah di kolom "Verifikasi". Jika tidak ada akses, tulis `latest stable (verifikasi dengan <perintah>)` di dokumen, **bukan** angka karangan.
3. Tanda **✅** = sudah dicek lewat sumber web pada tanggal di atas. Tanda **⚠️** = pengetahuan umum yang belum dicek ulang; boleh dipakai sebagai pilihan, tetapi cantumkan "verifikasi sebelum dipakai" di DECISIONS.md.
4. Jangan **merekomendasikan** teknologi di bagian **Usang** untuk proyek baru. Jika mahasiswa sendiri memilihnya (mis. diwajibkan mata kuliah atau proyek lanjutan), sampaikan risikonya sekali, hormati, dan catat di `DECISIONS.md`.
4b. Mahasiswa boleh memakai stack apa pun; ikuti bagian **[custom]** untuk stack yang tidak tercantum di sini.
5. Selalu cek bahwa nama paket benar-benar ada sebelum memasukkannya ke instruksi instalasi (model bisa salah mengeja atau mengarang nama paket; penyerang mendaftarkan nama palsu itu).

## [common]

### Praktik agen AI
- ✅ **AGENTS.md** adalah format terbuka lintas alat (sumber sekunder menyebut dikelola Linux Foundation / Agentic AI Foundation; cek agents.md), dibaca oleh Claude Code, Codex, Gemini CLI, Cursor, GitHub Copilot, dan agen lain. Letakkan di **root** repo, singkat, berisi perintah build/test, konvensi, dan batasan.
- ⚠️ File penunjuk per alat bila alat tidak membaca AGENTS.md otomatis: Claude Code → `CLAUDE.md` berisi satu baris `@AGENTS.md`; Gemini CLI → `GEMINI.md` (atau atur nama file konteks); Antigravity → cek dokumentasinya; cadangan universal: tulis di prompt pertama "Baca AGENTS.md dan semua file di docs/ dahulu".
- ⚠️ Jaga AGENTS.md ≤ 150 baris. Detail panjang ditaruh di docs/ dan hanya ditautkan. Konteks yang terlalu penuh menurunkan kualitas semua model.
- ⚠️ Pola kerja: satu tugas per sesi, kriteria selesai yang bisa dicek mesin, agen bekerja di branch sendiri, manusia me-review sebelum merge.

### Keamanan
- ✅ **OWASP Top 10:2025** (dirilis November 2025): A01 Broken Access Control, A02 Security Misconfiguration, A03 Software Supply Chain Failures, A04 Cryptographic Failures, A05 Injection, A06 Insecure Design, A07 Authentication Failures, A08 Software or Data Integrity Failures, A09 Security Logging and Alerting Failures, A10 Mishandling of Exceptional Conditions. SSRF kini bagian dari A01.
- ⚠️ Untuk fitur LLM di dalam aplikasi: rujuk OWASP Top 10 for LLM Applications (prompt injection, kebocoran data, penggunaan tool berlebihan).
- ⚠️ Hindari rahasia di repo: `.env` di `.gitignore`, sediakan `.env.example`, aktifkan secret scanning dan 2FA di GitHub, lockfile di-commit, aktifkan Dependabot.
- ⚠️ **UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP)** berlaku untuk aplikasi di Indonesia yang memproses data pribadi: kumpulkan seminimal mungkin, dapatkan persetujuan, jelaskan tujuan.

### Aksesibilitas dan kebutuhan pengguna
- ⚠️ **WCAG 2.2 level AA**: kontras teks 4,5:1 (teks besar dan elemen UI 3:1), target sentuh minimal 24×24 CSS px (rekomendasi 44–48 untuk mobile), fokus keyboard terlihat, tidak bergantung pada warna saja.
- ⚠️ Konteks Indonesia: dukung koneksi lambat/terputus, Android kelas menengah ke bawah, Bahasa Indonesia sebagai bahasa utama antarmuka, hemat kuota dan baterai.
- ⚠️ Pembayaran lokal: Midtrans, Xendit (QRIS, e-wallet, transfer bank). Gunakan mode sandbox untuk tugas kuliah.

### Git dan CI
- ⚠️ Git + GitHub; branch `main` terlindungi; Conventional Commits; PR wajib lolos CI. GitHub Actions: pin versi action (`@v4` minimal; untuk produksi pin ke commit SHA) dan periksa versi terbaru action sebelum rilis.

## [web]

- ✅ **Next.js 16** (stabil sejak Oktober 2025; seri 16.2.x–16.3.x pada 2026). App Router, Turbopack, React 19.2. Verifikasi: `npm view next version`.
- ⚠️ TypeScript mode `strict`; Node.js **LTS aktif** (cek `node --version` dan nodejs.org/en/about/previous-releases).
- ⚠️ UI: Tailwind CSS v4 + shadcn/ui (atau MUI/Mantine). Form: React Hook Form + Zod.
- ⚠️ Data: PostgreSQL (Supabase atau Neon) dengan Drizzle atau Prisma; SQLite/Turso untuk proyek kecil.
- ⚠️ Auth: Supabase Auth, Better Auth, Auth.js, atau Clerk. Jangan menulis kriptografi/hash password sendiri.
- ⚠️ Alternatif non-JS: Laravel, Django, FastAPI + frontend terpisah, Astro/SvelteKit untuk situs konten.
- ⚠️ Laravel: starter kit resmi berubah antar versi (Breeze mungkin bukan lagi starter kit bawaan di versi terbaru); cek dokumentasi resmi Laravel sebelum menetapkan starter kit, dan catat di Open Questions bila belum pasti.
- ⚠️ Hosting: Vercel, Cloudflare, Render, Railway, atau VPS + Docker. Cek batas paket gratis.
- ⚠️ Tes: Vitest/Jest (unit), Playwright (E2E). Lint: ESLint; format: Prettier atau Biome.

## [mobile]

- ✅ **Expo SDK 56** (dirilis 21 Mei 2026) dengan **React Native 0.85** dan **React 19.2**. React Native 0.82+ mewajibkan **New Architecture** dan Hermes adalah mesin JS bawaan (butir ini dari sumber sekunder; konfirmasi di dokumentasi React Native). Verifikasi: `npm view expo version`, `npx expo-doctor`.
- ✅ Scaffold: `npx create-expo-app@latest`. Routing: Expo Router. Build/distribusi: EAS Build dan EAS Submit.
- ⚠️ Expo Go cukup untuk belajar; modul native khusus butuh *development build*.
- ⚠️ Alternatif: Flutter (Dart), Kotlin native (Jetpack Compose) untuk Android-saja, Kotlin Multiplatform.
- ⚠️ Data lokal: expo-sqlite atau MMKV; offline-first butuh strategi sinkronisasi yang eksplisit.
- ⚠️ Tes: Jest + React Native Testing Library; E2E: Maestro. Notifikasi push: Expo Notifications (FCM/APNs di belakangnya).
- ⚠️ Distribusi: APK/AAB untuk uji, Google Play internal testing, TestFlight (iOS butuh akun Apple Developer berbayar).

## [desktop]

- ✅ **Tauri 2** (Rust + WebView sistem; juga mendukung iOS/Android). Installer jauh lebih kecil daripada Electron. Scaffold: `npm create tauri-app@latest`. Verifikasi: `npm view @tauri-apps/cli version`.
- ✅ **Electron** tetap matang untuk kebutuhan Chromium seragam dan ekosistem Node penuh; cocok jika tim hanya menguasai JavaScript/Node.
- ⚠️ Alternatif: Flutter desktop, .NET (WinUI/WPF/MAUI) untuk Windows-sentris, Qt untuk aplikasi C++.
- ⚠️ Data lokal: SQLite. Auto-update: plugin updater Tauri atau electron-updater.
- ⚠️ Distribusi tepercaya butuh code signing (Windows) dan signing + notarization (macOS); untuk tugas kuliah boleh dibagikan tanpa signing dengan peringatan jelas.
- ⚠️ Izin akses file/serial/USB harus dideklarasikan eksplisit (Tauri: capabilities; Electron: contextIsolation + preload).

## [iot]

- ✅ Keluarga **ESP32**: **ESP32-C6** (Wi-Fi 6, Thread/Matter) dan ESP32-S3 didukung panduan ESP-Matter yang diperbarui Juli 2026. ⚠️ Peran lain dari pengetahuan umum: S3 untuk AI/kamera, H2 untuk Thread/Zigbee, C5 dual-band; cek lembar data Espressif sebelum memilih.
- ✅ Toolchain: **PlatformIO** (VS Code), ESP-IDF, Arduino framework; **ESPHome** untuk konfigurasi YAML + Home Assistant; Zephyr tersedia sebagai alternatif RTOS.
- ⚠️ Protokol: **MQTT** (v5 didukung banyak broker) dengan TLS; Matter/Thread untuk smart home interoperabel; HTTP/WebSocket untuk kasus sederhana.
- ⚠️ Broker: Mosquitto atau EMQX (self-host), HiveMQ Cloud (paket gratis). Dashboard: Home Assistant, Node-RED, Grafana, atau web/mobile buatan sendiri.
- ⚠️ Simulasi tanpa perangkat: **Wokwi** (ESP32/Arduino) untuk logika dasar; sensor/aktuator fisik tetap perlu diuji pada perangkat nyata.
- ⚠️ Produksi: OTA update bertanda tangan, secure boot dan flash encryption, kredensial unik per perangkat, jangan hardcode password Wi-Fi/API key di firmware yang di-commit, isolasi jaringan IoT (VLAN).
- ⚠️ Daya: deep sleep untuk baterai; hitung anggaran arus. **Tegangan jaringan (220 V)**: wajib modul relay berisolasi, casing tertutup, supervisi dosen/teknisi.
- ⚠️ Tes: unit test logika di host (`pio test -e native`), uji integrasi di perangkat, checklist uji manual.
- ⚠️ Bila `platformio.ini` punya env `native`, selalu sebut env firmware secara eksplisit (`pio run -e esp32dev`, `pio check -e esp32dev`) atau set `default_envs`; tanpa itu firmware ikut dibangun untuk komputer dan gagal.
- ⚠️ PlatformIO **tidak didukung Dependabot** dan **tidak punya lockfile**: pin versi pustaka di `lib_deps` dan periksa pembaruan manual (`pio pkg outdated`).
- ⚠️ Desain gagal-aman wajib diputuskan (I-15): keadaan aktuator saat sensor error, saat jaringan putus, saat boot, dan watchdog. Notifikasi "perangkat offline" harus dibuat di luar perangkat (Last Will MQTT atau pemeriksaan di server), karena perangkat yang mati tidak bisa melapor sendiri.

## [custom]

Berlaku bila `stack.source` = `custom` atau `mixed` (mahasiswa memakai stack pilihannya sendiri). Daftar di bagian platform di atas adalah **rekomendasi**, bukan daftar yang diizinkan.

1. **Pilihan mahasiswa menang.** Jangan mengganti stack, jangan menulis dokumen seolah memakai stack rekomendasi, dan jangan mengulang saran perpindahan. Satu catatan risiko singkat diperbolehkan (lihat C-16), dan dicatat di `DECISIONS.md`.
2. **Versi**: pakai `stack.versions` dari mahasiswa. Bila kosong, tulis `latest stable (verifikasi: <perintah>)` memakai tabel di bawah. Jangan mengisi angka dari ingatan.
3. **Struktur folder dan perintah**: ikuti konvensi resmi framework tersebut (dokumentasi resmi atau perintah scaffold resminya). Bila tidak yakin, tulis "ikuti struktur hasil `<perintah scaffold resmi>`" dan buat task setup untuk memastikannya.
4. **Keamanan tetap berlaku penuh**: OWASP Top 10:2025, rahasia di `.env`, validasi input, otorisasi di server berlaku untuk stack apa pun. Terjemahkan ke alat ekosistem itu (mis. Laravel: Form Request, Policy, Sanctum; Django: forms/serializers, permission classes; Spring: Bean Validation, Spring Security; Flutter: penyimpanan aman untuk token).
5. **Alat lint dan tes**: gunakan yang standar di ekosistemnya dan tuangkan ke `stack.commands`.
6. **Model tidak mengenal teknologinya**: akui, minta tautan dokumentasi resmi dari mahasiswa, dan tandai bagian yang belum pasti di `DECISIONS.md` → *Open Questions*.

| Ekosistem | Cek versi terbaru | Alat lint/format umum | Alat tes umum |
|---|---|---|---|
| node | `npm view <paket> version` | ESLint, Prettier, Biome | Vitest, Jest, Playwright |
| python | `pip index versions <paket>` | Ruff | pytest |
| php | `composer show -a <vendor/paket>` | Laravel Pint, PHP-CS-Fixer, PHPStan | PHPUnit, Pest (Laravel di CI butuh `ci_setup`: `cp .env.example .env && php artisan key:generate`) |
| dart-flutter | `flutter --version`, `dart pub outdated` | `flutter analyze` | `flutter test` |
| go | `go list -m -versions <modul>` | `go vet`, gofmt, golangci-lint | `go test` |
| rust | `cargo search <crate>` | rustfmt, clippy | `cargo test` |
| jvm | Maven Central / `./gradlew dependencyUpdates` (plugin) | ktlint, detekt, Checkstyle | JUnit |
| dotnet | `dotnet list package --outdated` | `dotnet format` | xUnit, NUnit, MSTest |
| platformio | `pio pkg outdated` | `pio check` | `pio test` (Unity) |

## Usang — jangan direkomendasikan untuk proyek baru

| Usang | Gantinya |
|---|---|
| Create React App (`create-react-app`) | Next.js, Vite, atau Expo (mobile) |
| `react-native init` | `npx create-expo-app@latest` atau framework yang direkomendasikan RN |
| OWASP Top 10:2021 | OWASP Top 10:2025 |
| Node.js 16/18, Python 3.8 | Node.js LTS aktif, Python versi yang masih didukung |
| Tauri 1 untuk proyek baru | Tauri 2 |
| Password/API key di kode atau repo | `.env` + secret manager |
| Menyimpan password dengan MD5/SHA1 | Argon2/bcrypt lewat library auth |

## Cara memperbarui file ini (untuk dosen)

1. Jalankan perintah verifikasi di tiap bagian. 2. Cari rilis besar baru (framework utama, OWASP, Expo, Tauri, ESP-IDF). 3. Ubah baris, pindahkan ⚠️ menjadi ✅ bila sudah dicek, dan ganti tanggal di atas. 4. Jalankan `python tools/selftest.py` agar kit tetap konsisten.
