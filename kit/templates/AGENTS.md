# AGENTS.md — {{spec:meta.app_name}}

> {{spec:meta.one_liner}}

File ini dibaca agen AI di setiap sesi. Jaga tetap singkat (maksimal 150 baris); detail ada di `docs/`.

## Project

{{WRITE: tiga baris maksimal: apa aplikasinya, platform spec.meta.platform, siapa penggunanya, dan level proses spec.meta.level.}}

## Read First

{{WRITE: daftar bertautan ke dokumen dalam urutan baca untuk agen, dengan satu frasa fungsi masing-masing: docs/PRD.md, docs/ARCHITECTURE.md, docs/TASKS.md, docs/PROGRESS.md, docs/WORKFLOW.md, docs/GUARDRAILS.md, docs/EVALS.md, docs/CODE_STYLE.md, docs/SECURITY.md, docs/TESTING.md, docs/DESIGN_SYSTEM.md, docs/DECISIONS.md, docs/REVIEW.md, docs/LESSONS.md. Tambahkan docs/HARDWARE.md bila iot dan docs/LOOP.md bila level loop. Tulis "Muat dokumen saat dibutuhkan; jangan membaca semuanya jika tidak perlu, kecuali PROGRESS.md, TASKS.md, dan GUARDRAILS.md di awal sesi."}}

## Tech Stack

{{WRITE: daftar ringkas satu baris per lapisan (frontend, backend, database, auth, hosting, testing, extra) dari spec.stack. Pastikan setiap kata di spec.stack.keywords tertulis persis. Versi: pakai spec.stack.versions bila ada; selain itu "latest stable" kecuali tercantum di tech-baseline.md. Bila spec.stack.source bukan recommended, tambahkan satu baris "Stack dipilih mahasiswa: jangan mengusulkan penggantian stack; ikuti konvensi resmi teknologi ini."}}

## Commands

Perintah ini berasal dari `SPEC.json` (`stack.commands`) dan identik dengan `docs/TESTING.md`, `docs/EVALS.md`, dan CI.

```bash
{{spec:stack.commands.install}}
{{spec:stack.commands.lint}}
{{spec:stack.commands.test}}
{{spec:stack.commands.build}}
```

{{WRITE: satu baris keterangan untuk masing-masing perintah di atas sesuai urutannya (instal, lint, tes, build), lalu tambahkan perintah dev dari spec.stack.commands.dev, typecheck dari spec.stack.commands.typecheck, dan ci_setup dari spec.stack.commands.ci_setup (persiapan lingkungan, juga dipakai sekali saat setup lokal) bila tidak kosong. Untuk perintah yang baru tersedia setelah task setup, tandai "(tersedia setelah T-001)". Jangan mengubah teks perintah.}}

## Conventions

{{WRITE: lima sampai delapan butir paling penting dari CODE_STYLE.md dan DESIGN_SYSTEM.md yang dipakai di setiap perubahan; selebihnya tautkan ke dokumennya.}}

## Workflow Rules

{{WRITE: aturan singkat: kerjakan satu task per sesi dari docs/TASKS.md; mulai dari branch baru; jalankan gerbang EVALS sebelum menyatakan selesai; perbarui docs/PROGRESS.md di akhir sesi; catat keputusan di docs/DECISIONS.md dan kesalahan yang dipelajari di docs/LESSONS.md; berhenti dan minta review manusia sebelum merge.}}

## Boundaries

### Always
{{WRITE: tiga sampai lima butir yang selalu dilakukan (mis. baca PROGRESS.md dahulu, jalankan tes, commit kecil, ikuti nama ID fitur dari PRD).}}

### Ask First
{{WRITE: tiga sampai lima butir yang butuh persetujuan (mis. dependensi baru, perubahan skema, perubahan arsitektur), rujuk docs/GUARDRAILS.md.}}

### Never
{{WRITE: lima sampai tujuh larangan paling penting dari docs/GUARDRAILS.md (main, force push, rahasia, melemahkan tes, mengarang API, perintah destruktif, data pribadi nyata). Tambahkan larangan khusus platform.}}

## Definition of Done

{{WRITE: lima butir ringkas dari docs/TESTING.md bagian Definition of Done.}}
