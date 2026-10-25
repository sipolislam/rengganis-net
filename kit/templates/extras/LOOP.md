# Loop Engineering: {{spec:meta.app_name}}

Dokumen ini mengatur agen yang berputar secara semi-otonom. Putaran hanya boleh berjalan jika gerbang di `docs/EVALS.md` dapat dijalankan oleh mesin dan `docs/GUARDRAILS.md` dipatuhi.

## Loop Definition

{{WRITE: definisikan satu putaran: Pilih task dari TASKS.md -> Rencana singkat -> Implementasi -> Jalankan gerbang EVALS -> Perbaiki bila gagal -> Ulas oleh agen peninjau -> Commit dan PR -> Berhenti untuk review manusia. Spesifik pada proyek ini dengan perintah nyata.}}

## Roles

{{WRITE: empat peran agen dengan instruksi singkat masing-masing yang siap salin: Planner (memecah task, tidak menulis kode), Coder (mengimplementasikan satu task di branch), Tester (menulis dan menjalankan tes dari acceptance fitur), Reviewer (membaca diff dengan REVIEW.md, hanya melaporkan). Tiap peran: masukan, keluaran, dan apa yang tidak boleh dilakukan. Semua peran tunduk pada AGENTS.md dan GUARDRAILS.md.}}

## Stop Conditions

{{WRITE: tabel Kondisi berhenti, Tindakan. Wajib: semua gerbang EVALS lulus; tiga kali gagal pada masalah yang sama; perubahan melebihi batas ukuran PR; butuh persetujuan di daftar Ask Before; biaya atau kuota mencapai batas; menemukan kontradiksi antara dokumen; menemukan rahasia atau data pribadi nyata; iterasi mencapai batas maksimum.}}

## Iteration Limits and Budget

{{WRITE: batas konkret: iterasi maksimum per task (saran 5), percobaan perbaikan per masalah (3), waktu maksimum per putaran, jumlah task per sesi (1), batas biaya atau kuota alat AI yang dipakai mahasiswa (spec.meta.ai_tools). Jelaskan bahwa batas ini menjaga agen tidak berputar tanpa akhir.}}

## Hand-off Between Agents

{{WRITE: cara agen menyerahkan pekerjaan lewat berkas, bukan ingatan: PROGRESS.md untuk keadaan, TASKS.md untuk status, branch dan PR untuk hasil, LESSONS.md untuk pelajaran. Format pesan serah terima singkat yang dipakai.}}

## Human Checkpoints

{{WRITE: titik manusia wajib di dalam loop: persetujuan rencana untuk task berukuran L, review PR sebelum merge, persetujuan perubahan skema, ringkasan akhir sesi. Mahasiswa mengisi catatan pemahaman di PR (REVIEW.md bagian Author Comprehension Check).}}

## Observability

{{WRITE: cara melihat apa yang dilakukan agen: riwayat commit, log CI, entri Session Log di PROGRESS.md, dan daftar perintah yang dijalankan bila alatnya menyediakan. Cara menghentikan putaran darurat.}}
