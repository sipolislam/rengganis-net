# AGENTS.md — CatatKas

> Pencatatan kas organisasi mahasiswa yang transparan dan bebas selisih saldo.

File ini dibaca agen AI di setiap sesi. Jaga tetap singkat (maksimal 150 baris); detail ada di `docs/`.

## Project

CatatKas adalah aplikasi web untuk mencatat kas organisasi mahasiswa. Pengguna: bendahara (menulis) dan anggota (membaca). Level proses: menengah.

## Read First

- `docs/PRD.md`: fitur F1 sampai F6 dan kriteria acceptance
- `docs/ARCHITECTURE.md`: stack, struktur folder, model data
- `docs/TASKS.md`: daftar task dan statusnya
- `docs/PROGRESS.md`: keadaan terakhir proyek
- `docs/WORKFLOW.md`: siklus kerja dan aturan branch
- `docs/GUARDRAILS.md`: batas yang tidak boleh dilanggar
- `docs/EVALS.md`: gerbang kualitas yang bisa dicek mesin
- `docs/CODE_STYLE.md`, `docs/SECURITY.md`, `docs/TESTING.md`, `docs/DESIGN_SYSTEM.md`
- `docs/DECISIONS.md`, `docs/REVIEW.md`, `docs/LESSONS.md`

Muat dokumen saat dibutuhkan; di awal sesi baca `docs/PROGRESS.md`, `docs/TASKS.md`, `docs/GUARDRAILS.md`, dan `docs/LESSONS.md`.

## Tech Stack

- Frontend: Next.js (App Router), TypeScript, Tailwind CSS, shadcn/ui
- Backend: Next.js Route Handlers dan Server Actions
- Database: PostgreSQL di Supabase dengan Drizzle ORM
- Auth: Supabase Auth (email dan kata sandi)
- Hosting: Vercel
- Testing: Vitest (unit) dan Playwright (satu alur E2E)
- Versi: latest stable; verifikasi dengan `npm view next version`

## Commands

```bash
npm install          # instal dependensi (tersedia setelah T-001)
npm run dev          # server pengembangan
npm run lint         # lint
npm run typecheck    # pemeriksaan tipe
npm test             # Vitest
npm run test:e2e     # Playwright (tersedia setelah T-011)
npm run build        # build produksi
```

## Conventions

- TypeScript mode `strict`; tanpa `any` tanpa alasan tertulis.
- Nominal uang disimpan sebagai bilangan bulat rupiah dan dihitung di server.
- Nama berkas kebab-case; komponen PascalCase; tabel dan kolom snake_case.
- Semua tulis data lewat Server Action atau Route Handler yang memeriksa peran di server.
- Pesan commit mengikuti Conventional Commits dan menyebut ID task (mis. `feat(T-006): tambah transaksi`).
- Antarmuka berbahasa Indonesia; ikuti token di `docs/DESIGN_SYSTEM.md`.

## Workflow Rules

- Kerjakan satu task per sesi dari `docs/TASKS.md`; mulai dari branch baru `feat/T-nnn-nama`.
- Jalankan gerbang di `docs/EVALS.md` sebelum menyatakan selesai.
- Perbarui `docs/PROGRESS.md` di akhir sesi; catat keputusan di `docs/DECISIONS.md` dan pelajaran di `docs/LESSONS.md`.
- Berhenti dan minta review manusia sebelum merge.

## Boundaries

### Always
- Baca `docs/PROGRESS.md` dahulu; jalankan tes dan lint; commit kecil; pakai ID fitur dari PRD.

### Ask First
- Dependensi baru, perubahan skema database, perubahan arsitektur, penggunaan layanan berbayar.

### Never
- Commit atau push langsung ke `main`; force push; menaruh rahasia di kode; mengubah `.env`; melemahkan atau menghapus tes agar lulus; mengarang API atau versi; menjalankan perintah destruktif tanpa persetujuan.

## Definition of Done

- Kriteria acceptance fitur terkait terpenuhi dan teruji.
- Lint, tipe, tes, dan build lulus.
- Tidak ada rahasia atau data pribadi nyata di diff.
- `docs/PROGRESS.md` dan dokumen terkait diperbarui.
- PR menjawab tiga pertanyaan pemahaman di `docs/REVIEW.md`.
