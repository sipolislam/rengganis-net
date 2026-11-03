# Docs-Kit: Pembangkit Paket Dokumen Proyek untuk Pengembangan dengan AI

Kit ini membantu mahasiswa mengubah **ide aplikasi** (web, mobile, desktop, atau IoT) menjadi **paket dokumen proyek** yang siap dibaca agen AI coding di editor mereka, sehingga proyek dapat dibangun secara terarah, teruji, dan terlacak di Git.

Cara kerjanya: model AI **mewawancarai** mahasiswa dengan pertanyaan berpilihan, menyusun satu `SPEC.json` yang dikonfirmasi mahasiswa, lalu **menghasilkan 16 dokumen** dari template yang sudah menetapkan struktur dan isinya. Kecerdasan sebanyak mungkin ada di kit (bank pertanyaan, template, aturan, validator), bukan di model, supaya hasilnya layak di model besar maupun kecil.

## Keluaran (16 file + pendukung)

| Lokasi | File |
|---|---|
| Root | `AGENTS.md`, `README.md` (proyek), `SPEC.json` |
| `docs/` | `PRD`, `ARCHITECTURE`, `DESIGN_SYSTEM`, `SECURITY`, `CODE_STYLE`, `TESTING`, `EVALS`, `WORKFLOW`, `GUARDRAILS`, `TASKS`, `PROGRESS`, `DECISIONS`, `REVIEW`, `LESSONS` (`.md`) |
| Bersyarat | `docs/HARDWARE.md` (IoT), `docs/LOOP.md` (level loop) |
| Pendukung git | `.gitignore`, `.github/workflows/ci.yml`, `.github/pull_request_template.md`, `.github/dependabot.yml`, `.env.example` (dirender sesuai ekosistem stack) |
| Penunjuk alat AI | `CLAUDE.md` / `GEMINI.md` berisi `@AGENTS.md`, bila alat tersebut dipakai |

`AGENTS.md` dan `README.md` berada di root karena alat AI dan GitHub mencarinya di sana.

## Tech stack bebas

Mahasiswa **boleh memakai tech stack apa pun**: Laravel, Django, Flutter, Spring Boot, Go, Svelte, .NET, MicroPython, atau lainnya. Opsi di bank pertanyaan hanyalah rekomendasi untuk yang belum punya preferensi.

- Pertanyaan **C-16** menawarkan tiga jalur: ikuti rekomendasi, stack sendiri, atau campuran. Bila memilih stack sendiri, mahasiswa menulis alasannya. Model dilarang menggiring ke stack lain dan hanya boleh menyampaikan satu catatan risiko.
- Pertanyaan **C-17** menetapkan `stack.ecosystems` (node, python, php, dart-flutter, go, rust, jvm, dotnet, platformio, other; boleh lebih dari satu) dan `stack.commands` (install, lint, test, build).
- Perintah itu disalin **persis** ke AGENTS.md, TESTING.md, EVALS.md, dan CI. Validator memeriksa kesamaannya.
- `scaffold_extras.py` membuat CI, `.gitignore`, dan Dependabot yang sesuai ekosistem, misalnya Flutter + Laravel menghasilkan setup Flutter dan PHP, `flutter analyze`, serta Dependabot `pub` dan `composer`.
- Alasan mahasiswa dikutip di `docs/DECISIONS.md`, sehingga dosen dapat menilai keputusan itu.

## Isi kit

```
kit/
├── START.md                  # pintu masuk untuk model (mode agen)
├── tech-baseline.md          # acuan teknologi terkini (diverifikasi 2026-10-20)
├── RUBRIC.md                 # A: periksa diri model; B: penilaian dosen
├── CROSS-MODEL-TEST.md       # protokol menguji kualitas antar model
├── CHANGELOG.md              # riwayat versi kit
├── questions/                # common, web, mobile, desktop, iot
├── schema/                   # spec.schema.md, spec.template.json
├── templates/                # 16 template + extras (HARDWARE, LOOP, gitignore, template PR) + _manifest.json
├── examples/catatkas/        # contoh terisi: SPEC + PRD + TASKS + AGENTS
├── tools/                    # validate.py, scaffold_extras.py, extras_lib.py, make_chat_bundles.py, selftest.py
└── dist/chat/<platform>/     # bundel siap tempel untuk mode chat
```

## Cara pakai: mode agen (VS Code, Antigravity, Cursor, Claude Code, Codex, Gemini CLI)

1. Buat folder proyek, letakkan folder ini di dalamnya sebagai `kit/`.
2. Buka folder proyek di editor yang punya agen AI.
3. Ketik prompt: **"Baca `kit/START.md` dan jalankan semua fasenya. Ide aplikasiku: …"**
4. Jawab pertanyaan (huruf pilihan, atau Z bila belum tahu). Periksa ringkasan dan konfirmasi `SPEC.json`.
5. Agen menulis dokumen ke root proyek. Jalankan: `python kit/tools/validate.py` (butuh Python 3.9+; tanpa dependensi).
6. Gunakan prompt pertama yang diberikan agen di Fase 6 untuk mulai membangun, mulai dari `T-001`.

## Cara pakai: mode chat (ChatGPT, Gemini, Claude di browser)

Gunakan folder `dist/chat/<platform>/` (pilih web, mobile, desktop, atau iot). Ikuti `BACA-DULU.md` di dalamnya. Ringkasnya: tempel `00-wawancara.md` dan tulis idemu; simpan `SPEC.json` hasilnya; tempel `01` sampai `05` satu per satu (tiap berkas berdiri sendiri, sehingga model tidak perlu mengingat percakapan); salin file dari `06-pendukung-git/` (pilih folder ekosistemmu dan ganti `GANTI-PERINTAH` di CI); tempel `07-pemeriksaan.md` untuk pemeriksaan silang.

## Untuk dosen

- **Perbarui keterkinian** setiap awal semester: ubah `tech-baseline.md` (jalankan perintah verifikasi di dalamnya, ganti tanggal), lalu `python tools/selftest.py` dan `python tools/make_chat_bundles.py` untuk membangun ulang bundel.
- **Ukur kualitas lintas model**: ikuti `CROSS-MODEL-TEST.md` dan nilai dengan `RUBRIC.md` Bagian B. Hasilnya menunjukkan model mana yang perlu kit versi lebih sederhana.
- **Nilai proses, bukan hanya dokumen**: `meta.delegated_questions` dan `docs/DECISIONS.md` menunjukkan keputusan mana yang dibuat mahasiswa sendiri; `student_reflection` adalah kata-kata mahasiswa; riwayat commit dan PR menunjukkan kerja sebenarnya; `docs/REVIEW.md` memuat tiga pertanyaan pemahaman.
- **Sesuaikan** bank pertanyaan dan template sesuai mata kuliah. `validate.py` membaca heading wajib langsung dari template, jadi mengubah template tidak perlu mengubah validator; ubah ambang di `templates/_manifest.json`.

## Batasan yang perlu diketahui

- Validator memeriksa **struktur dan konsistensi** (berkas, heading, ID fitur, kata kunci stack, perintah, alasan stack pilihan, OWASP, git, CI, rahasia, panjang minimum), bukan kebenaran teknis. Review manusia tetap perlu.
- Kit menaikkan kualitas minimum lintas model, tetapi **hasil yang identik di semua model tidak dapat dijamin**. Pilot (Haiku, Sonnet, Opus; dua ide) menunjukkan model terkecil naik dari rata-rata 13,5 ke 19,5 dari 21 setelah perbaikan v1.2, setara model terbesar. Model non-Claude belum diuji. Rincian dan batasannya di `CROSS-MODEL-TEST.md` bagian 8.
- Angka versi cepat usang. Tanda ✅/⚠️ di `tech-baseline.md` menunjukkan mana yang sudah dicek; perlakukan ⚠️ sebagai "verifikasi sebelum dipakai".
- Untuk stack yang jarang dikenal model, kualitas dokumen bergantung pada dokumentasi yang diberikan mahasiswa; kit memerintahkan model mengaku tidak tahu dan menandai bagian yang belum pasti, bukan mengarang.
- Template menganggap agen coding dapat menjalankan perintah shell dan Git. Untuk alat yang hanya chat, mahasiswa perlu menyalin hasil secara manual.
