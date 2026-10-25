# RUBRIC

Dua bagian: **A** untuk pemeriksaan diri oleh model (ya/tidak), **B** untuk penilaian dosen terhadap hasil dan perbandingan antar model.

## Bagian A — Pemeriksaan diri (dijalankan model di Fase 4)

Jawab setiap butir **ya** atau **tidak**. Perbaiki semua yang "tidak" sebelum menyerahkan hasil.

**Struktur**
1. Semua 16 file ada (14 di `docs/`, `AGENTS.md` dan `README.md` di root), ditambah `docs/HARDWARE.md` bila iot dan `docs/LOOP.md` bila level loop.
2. Setiap file diawali judul `# ...` dan memakai semua heading `## ...` dari templatenya, tanpa mengubah teks heading.
3. Tidak ada slot `{{WRITE:` atau `{{spec:` tersisa di file mana pun (sintaks proyek seperti Blade `{{ $x }}` boleh).
4. Tidak ada teks pengisi seperti "TBD", "lorem", atau "isi di sini".

**Konsistensi (satu sumber kebenaran)**
5. Setiap ID fitur di `SPEC.json` muncul di `PRD.md`, `TASKS.md`, dan `EVALS.md`, dengan nama dan prioritas yang sama.
6. Kriteria acceptance di `PRD.md` dan `EVALS.md` berasal dari `spec.features[].acceptance`, tanpa tambahan klaim baru.
7. Setiap kata di `stack.keywords` muncul di `ARCHITECTURE.md` dan `AGENTS.md`.
8. Perintah lint, tes, build, dan jalankan **identik** di `AGENTS.md`, `TESTING.md`, `EVALS.md`, `README.md`, dan `ci.yml`.
9. Nama aplikasi sama persis di semua file.
10. `PRD.md` mengutip persis `student_reflection.biggest_risk`.

**Kekhususan**
11. Tiap dokumen menyebut istilah proyek (nama fitur, entitas data, peran) dan bukan hanya kalimat umum.
12. `TASKS.md` memecah setiap fitur menjadi task berukuran S sampai L dengan kriteria selesai yang dapat dicek; `T-001` adalah setup.
13. `DECISIONS.md` memuat ADR untuk setiap pilihan utama beserta alternatif yang ditolak.

**Kebenaran dan keterkinian**
14. Tidak ada angka versi, harga, atau statistik yang tidak berasal dari `tech-baseline.md` atau hasil verifikasi.
15. Tidak ada teknologi dari daftar "Usang" di `tech-baseline.md`.
16. `SECURITY.md` memuat sepuluh baris OWASP Top 10:2025 dengan penerapan konkret (bukan "sesuai standar").
17. Bila ada data pribadi (`data.personal_data` bukan `none`), `SECURITY.md` membahas minimisasi dan UU PDP.

**Keamanan dan git**
18. Tidak ada rahasia, kata sandi, atau token di dokumen mana pun; hanya nama variabel.
19. `GUARDRAILS.md` melarang commit ke `main`, force push, melemahkan tes, dan perintah destruktif tanpa persetujuan.
20. `.gitignore` mengecualikan `.env`; ada `.github/workflows/ci.yml`, `pull_request_template.md`, dan `.env.example`.
21. Bila `platform = iot` dan `mains_voltage = true`: peringatan tegangan jaringan (220 V) ada di `GUARDRAILS.md` dan `HARDWARE.md`.

**Kepemilikan mahasiswa**
22. Pertanyaan refleksi dijawab mahasiswa sendiri; tidak ada kalimat refleksi yang ditulis model.
23. Pilihan yang didelegasikan tercatat di `meta.delegated_questions` dan `DECISIONS.md`.

**Stack bebas**
24. Bila `stack.source` bukan `recommended`: semua dokumen ditulis untuk stack pilihan mahasiswa (bukan stack rekomendasi), `DECISIONS.md` mengutip `stack.custom_reason`, dan tidak ada saran berulang untuk berpindah stack.
25. Perintah `stack.commands` sama persis di AGENTS.md, TESTING.md, EVALS.md, dan ci.yml; `ci.yml` memakai setup untuk setiap ekosistem di `stack.ecosystems` dan tidak berisi `GANTI-PERINTAH`.
26. Bila ada `meta.secondary_platforms`, ARCHITECTURE.md menjelaskan peran dan stack tiap platform pendukung.
27. Bila `meta.starting_point = existing`, `T-001` adalah audit repositori yang sudah ada, bukan setup dari nol.

**Kejujuran dan kelayakan kerja**
28. Tidak ada klaim tentang kemampuan, kenyamanan, atau pengalaman mahasiswa yang tidak ia nyatakan.
29. Setiap nilai yang diusulkan asisten tercatat di `meta.assistant_proposed` dan `DECISIONS.md` bagian *Assistant Proposals*; kutipan persona hanya diberi label "ilustrasi" bila memang dirumuskan asisten.
30. Konflik antar kriteria acceptance (waktu, keadaan, izin, data) sudah diperiksa dan diselesaikan bersama mahasiswa sebelum dokumen ditulis.
31. Kendali risiko R1… muncul di PRD, EVALS, dan TASKS.
32. `T-001` dapat diselesaikan sendiri: setelahnya semua perintah `stack.commands` lulus dan CI hijau, tanpa menunggu task lain.
33. Semua rujukan `ADR-nnn` dan `T-nnn` menunjuk ke entri yang ada dan topiknya benar.
34. Untuk IoT, perilaku saat sensor error, jaringan putus, boot, dan konflik manual vs otomatis tertulis di HARDWARE.md beserta cara mengujinya.

## Bagian B — Penilaian dosen (0–3 per dimensi)

Skala: **0** gagal, **1** lemah, **2** memadai, **3** sangat baik. Nilai dilakukan terhadap hasil akhir, idealnya tanpa mengetahui model mana yang menghasilkannya.

| Dimensi | Pertanyaan penilaian |
|---|---|
| Kelengkapan | Apakah semua file, heading, dan kolom ada dan terisi? |
| Konsistensi | Apakah ID fitur, stack, perintah, dan nama sama di semua dokumen? |
| Kekhususan | Apakah dokumen akan salah bila ditempel ke proyek lain? (semakin salah, semakin spesifik) |
| Keterkinian | Apakah teknologi sesuai `tech-baseline.md` dan tidak ada fakta karangan? |
| Keterpakaian agen | Bila agen coding hanya diberi `AGENTS.md` dan `docs/`, dapatkah ia mengerjakan `T-001` tanpa bertanya? |
| Keamanan | Apakah risiko nyata proyek ini ditangani, bukan daftar umum? |
| Kualitas pertanyaan | Apakah wawancara memakai bank pertanyaan, menyertakan opsi Z, dan tidak menjawab refleksi? |
| Penghormatan pilihan | Bila mahasiswa memilih stack sendiri, apakah model mengikutinya tanpa menggiring dan tetap menerapkan keamanan dan pengujian yang setara? (Nilai 3 bila stack rekomendasi dipakai.) |

**Total maksimum 24.** Acuan: ≥ 19 siap pakai; 14–18 perlu perbaikan kit atau pemeriksaan manusia; < 14 model tidak cocok tanpa versi kit yang lebih sederhana.

Lihat `CROSS-MODEL-TEST.md` untuk protokol membandingkan beberapa model.
