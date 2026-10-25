# Skema SPEC.json

`SPEC.json` adalah satu-satunya sumber kebenaran. Salin `spec.template.json`, isi semua bidang. Validator (`tools/validate.py`) memeriksa struktur ini. Nilai tipe `enum` harus persis seperti tertulis (huruf kecil).

## Bidang

| Kunci | Tipe | Aturan |
|---|---|---|
| `meta.app_name` | string | Nama tampilan, 2–40 karakter |
| `meta.slug` | string | kebab-case, huruf kecil/angka/`-` |
| `meta.one_liner` | string | Satu kalimat ≤ 140 karakter |
| `meta.platform` | enum | `web` / `mobile` / `desktop` / `iot` |
| `meta.level` | enum | `dasar` / `menengah` / `loop` |
| `meta.language` | enum | `id` / `en` |
| `meta.ai_tools` | string[] | Alat AI yang dipakai mahasiswa (boleh `["chat-only"]`) |
| `meta.team_size` | integer | ≥ 1 |
| `meta.duration_weeks` | integer | ≥ 1 |
| `meta.delegated_questions` | string[] | ID pertanyaan yang dijawab "Z" (boleh kosong) |
| `meta.secondary_platforms` | string[] | Platform pendukung selain `meta.platform`, mis. `["web"]` untuk panel admin aplikasi mobile (boleh kosong; tidak boleh memuat `meta.platform`) |
| `meta.created_date` | string | Tanggal SPEC dibuat, `YYYY-MM-DD`; dipakai di ADR dan PROGRESS |
| `meta.assistant_proposed` | string[] | Jalur bidang yang diisi usulan asisten, bukan mahasiswa, mis. `["meta.app_name", "metrics", "users[1].quote"]` (boleh kosong) |
| `meta.starting_point` | enum | `new` (mulai dari nol) / `existing` (sudah ada kode; T-001 menjadi audit dan penyesuaian repo) |
| `problem.statement` | string | Masalah dalam 1–3 kalimat |
| `problem.who` | string | Siapa yang mengalaminya |
| `problem.current_alternative` | string | Cara mereka mengatasinya sekarang |
| `goals` | string[] | ≥ 2, terukur |
| `non_goals` | string[] | ≥ 1, hal yang sengaja tidak dikerjakan |
| `users` | object[] | ≥ 1: `persona`, `need`, `quote` |
| `user_conditions` | string[] | Mis. `koneksi lambat`, `Android kelas menengah` (boleh kosong) |
| `features` | object[] | ≥ 3; lihat di bawah |
| `metrics` | object[] | ≥ 2: `name`, `target`, `how_measured` |
| `stack` | object | `frontend`, `backend`, `database`, `auth`, `hosting`, `testing` (string), `keywords` (string[] ≥ 3), `extra` (string[]), plus bidang stack bebas di bawah |
| `data` | object | `entities` (≥ 1: `name`, `fields` string[]), `personal_data` (enum), `sensitive_notes` |
| `security` | object | `roles` (string[] ≥ 1), `auth_method`, `secrets_handling` |
| `ai_in_app` | object | `mode` (enum `none`/`api`/`on_device`), `purpose` |
| `design` | object | `tone`, `primary_color` (hex `#RRGGBB`), `font`, `dark_mode` (boolean) |
| `platform_specific` | object | Harus memuat kunci sesuai `meta.platform`; lihat di bawah |
| `risks` | string[] | ≥ 2 risiko teknis/proyek (dari asisten boleh) |
| `student_reflection` | object | `biggest_risk`, `why_this_project`: **kata-kata mahasiswa**, tidak boleh kosong; `risk_controls` (string[] ≥ 1): aturan teruji untuk risiko itu yang disetujui mahasiswa (C-14b), menjadi R1, R2, … |

### `features[]`

| Kunci | Aturan |
|---|---|
| `id` | `F1`, `F2`, … berurutan, unik |
| `name` | Pendek, kata benda/kata kerja |
| `description` | 1–2 kalimat |
| `priority` | `must` / `should` / `could`; minimal 1 `must`, saran ≤ 5 `must` |
| `acceptance` | string[] ≥ 2, setiap butir **dapat diuji** (ada aksi dan hasil yang teramati) |

### Stack bebas: `stack.source`, `stack.custom_reason`, `stack.ecosystems`, `stack.commands`

Mahasiswa **boleh memakai stack apa pun**. Bidang ini mencatat dari mana stack berasal dan bagaimana menjalankannya, sehingga dokumen, CI, dan agen tetap konsisten apa pun pilihannya.

| Kunci | Tipe | Aturan |
|---|---|---|
| `stack.source` | enum | `recommended` (mengikuti opsi kit) / `custom` (stack pilihan mahasiswa) / `mixed` (sebagian pilihan sendiri) |
| `stack.custom_reason` | string | **Wajib** bila `source` bukan `recommended`: alasan mahasiswa (mis. "dipakai di tempat magang", "sudah menguasai Laravel", "syarat mata kuliah") |
| `stack.custom_layers` | string[] | Lapisan yang dipilih sendiri bila `mixed`, mis. `["backend", "database"]` (boleh kosong untuk `recommended`) |
| `stack.ecosystems` | string[] | ≥ 1 dari: `node`, `python`, `php`, `dart-flutter`, `go`, `rust`, `jvm`, `dotnet`, `platformio`, `other`. Boleh lebih dari satu (Tauri = `["node","rust"]`, Laravel + Vite = `["php","node"]`). Menentukan CI, `.gitignore`, dan Dependabot |
| `stack.commands` | object | Perintah nyata proyek: `install`, `lint`, `test`, `build` (wajib, tidak kosong), `dev`, `typecheck`, `ci_setup` (boleh string kosong). `ci_setup` dijalankan di CI setelah instal dan sebelum lint/tes, untuk persiapan yang dibutuhkan lingkungan bersih (mis. Laravel: `cp .env.example .env && php artisan key:generate`). Perintah ini **disalin persis** ke AGENTS.md, TESTING.md, EVALS.md, dan ci.yml |
| `stack.versions` | string[] | Versi yang mahasiswa ketahui pasti, mis. `["PHP 8.3", "Laravel 12"]`. Kosongkan bila tidak tahu; jangan diisi tebakan model |

Contoh `commands` per ekosistem (sesuaikan dengan proyek):

| Ekosistem | install | lint | test | build |
|---|---|---|---|---|
| node (npm) | `npm ci` atau `npm install` | `npm run lint` | `npm test` | `npm run build` |
| python | `pip install -r requirements.txt` | `ruff check .` | `pytest` | `python -m compileall -q .` |
| php (Laravel) | `composer install && npm ci` | `./vendor/bin/pint --test` | `php artisan test` | `npm run build` (ci_setup: `cp .env.example .env && php artisan key:generate`) |
| dart-flutter | `flutter pub get` | `flutter analyze` | `flutter test` | `flutter build apk --debug` |
| go | `go mod download` | `go vet ./...` | `go test ./...` | `go build ./...` |
| jvm (Android Kotlin) | `./gradlew dependencies` | `./gradlew lint` | `./gradlew test` | `./gradlew assembleDebug` |
| dotnet | `dotnet restore` | `dotnet format --verify-no-changes` | `dotnet test` | `dotnet build` |
| platformio | `pio pkg install` | `pio check -e esp32dev` | `pio test -e native` | `pio run -e esp32dev` (selalu sebut `-e <env>` bila ada env `native`, agar firmware tidak dibangun untuk komputer) |

### `data.personal_data` (enum)

`none` · `basic` (nama/email) · `location_contact` (lokasi, kontak, foto) · `sensitive` (kesehatan, keuangan, anak, biometrik).

### `platform_specific` per platform

- `web`: `rendering` (mis. `fullstack-framework` / `spa-plus-api` / `static`), `i18n` (boolean), `seo` (boolean)
- `mobile`: `framework`, `target_os` (`android` / `ios` / `both`), `min_android` (string/angka), `offline` (`none` / `cache` / `offline-first`), `native_features` (string[]), `distribution`
- `desktop`: `framework`, `os_targets` (string[]), `hardware_access` (string[]), `updater` (boolean), `distribution`
- `iot`: `board`, `sensors` (string[]), `actuators` (string[]), `connectivity`, `protocol`, `broker`, `power`, `ota` (boolean), `simulation`, `mains_voltage` (boolean), `edge_ai` (`none` / `on_device` / `cloud`), `failsafe` (string: perilaku saat sensor error, jaringan putus, boot, dan konflik manual vs otomatis; dari I-15), `offline_alert` (boolean)

## Aturan konsistensi (diperiksa validator)

1. Setiap `features[].id` muncul di `PRD.md`, `TASKS.md`, dan `EVALS.md`.
2. Setiap kata di `stack.keywords` muncul di `ARCHITECTURE.md` dan `AGENTS.md`.
3. `meta.app_name` muncul di `README.md`, `PRD.md`, `AGENTS.md`.
4. `student_reflection.biggest_risk` dikutip di `PRD.md` bagian *Risks and Open Questions*.
5. Jika `platform = iot` dan `mains_voltage = true`, `GUARDRAILS.md` memuat peringatan tegangan jaringan.
6. Perintah `stack.commands.install/lint/test/build` muncul persis di `AGENTS.md`, `docs/TESTING.md`, `docs/EVALS.md`, dan `.github/workflows/ci.yml`.
7. Jika `stack.source` bukan `recommended`, `docs/DECISIONS.md` mengutip `stack.custom_reason`.
8. Untuk setiap platform di `meta.secondary_platforms`, `docs/ARCHITECTURE.md` menyebut platform tersebut.
9. Setiap kendali risiko R1, R2, … muncul di `PRD.md`, `EVALS.md`, dan `TASKS.md`; teksnya dikutip di PRD.
10. Setiap rujukan `ADR-nnn` dan `T-nnn` di dokumen mana pun benar-benar ada di `DECISIONS.md` dan `TASKS.md`.
11. Setiap jalur di `meta.assistant_proposed` disebut di `DECISIONS.md` bagian *Assistant Proposals*.
12. Untuk IoT, `platform_specific.iot.failsafe` dikutip di `HARDWARE.md`.
