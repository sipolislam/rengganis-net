# Workflow: {{spec:meta.app_name}}

## Cycle

Setiap task mengikuti siklus yang sama:

`Plan -> Branch -> Implement -> Test -> Review -> Commit -> Pull Request -> Merge -> Update Docs`

{{WRITE: jelaskan tiap langkah siklus dalam satu sampai dua kalimat, spesifik pada proyek ini (nama file dokumen, perintah dari EVALS.md, nama cabang contoh memakai ID task nyata seperti feat/T-003-nama-singkat). Sebutkan di langkah mana manusia wajib memeriksa.}}

## Session Start Ritual

{{WRITE: daftar bernomor yang dijalankan agen dan manusia di awal setiap sesi: baca AGENTS.md; baca PROGRESS.md untuk keadaan terakhir; baca LESSONS.md; pilih tepat satu task dari TASKS.md dengan status todo; ubah statusnya menjadi doing; pastikan berada di branch yang benar (bukan main) dan working tree bersih.}}

## Session End Ritual

{{WRITE: daftar bernomor di akhir sesi: jalankan gerbang di EVALS.md; commit kecil dengan pesan sesuai CODE_STYLE.md; perbarui status task; tulis ringkasan di PROGRESS.md (selesai, berikutnya, hambatan); catat keputusan baru di DECISIONS.md dan kesalahan agen di LESSONS.md; push branch dan buka PR; berhenti dan minta review manusia.}}

## Branching Strategy

{{WRITE: aturan cabang: main selalu stabil dan dilindungi; fitur di feat/T-nnn-nama; perbaikan di fix/T-nnn-nama; dokumen di docs/nama; satu branch satu task; branch dihapus setelah merge; tidak ada commit langsung ke main; rebase atau merge main ke branch sebelum PR sesuai preferensi tim (pilih satu dan tulis). Sesuaikan dengan spec.meta.team_size.}}

## Commit and Pull Request Rules

{{WRITE: aturan commit (rujuk CODE_STYLE.md), isi PR yang wajib (ringkasan, ID task, ID fitur, cara menguji, hasil gerbang EVALS, tangkapan layar atau foto perangkat bila relevan), ukuran PR yang disarankan (kecil, satu tujuan), dan syarat merge (CI hijau dan satu review manusia). Rujuk .github/pull_request_template.md.}}

## Human-in-the-Loop Checkpoints

{{WRITE: tabel Titik kontrol, Siapa, Yang diperiksa. Wajib mencakup: sebelum merge, sebelum menambah dependensi besar, sebelum mengubah skema data atau arsitektur, sebelum perintah destruktif, sebelum memakai layanan berbayar, dan sebelum data nyata dipakai. Level spec.meta.level menentukan seberapa sering: dasar (setiap task), menengah (setiap PR), loop (di batas iterasi dan sebelum merge).}}

## Handling Blockers

{{WRITE: langkah bila agen atau mahasiswa macet: coba maksimal tiga pendekatan berbeda, catat apa yang sudah dicoba di PROGRESS.md, tandai task blocked, minta bantuan (dosen, teman, dokumentasi resmi). Larang agen melewati blocker dengan mematikan tes, menurunkan keamanan, atau mengarang API.}}

## Level Notes

{{WRITE: tiga butir pendek untuk level dasar, menengah, dan loop; tandai level yang aktif sesuai spec.meta.level dengan kata "AKTIF". Level loop merujuk docs/LOOP.md.}}
