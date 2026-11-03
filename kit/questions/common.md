# Bank Pertanyaan Umum (semua platform)

**Cara menyajikan**: tampilkan teks pertanyaan, opsi berhuruf dengan satu kalimat penjelasan, lalu **Z. Belum tahu — bantu saya memilih**. Tanda ★ = wajib bahkan pada jalur cepat. Kolom `→ spec` menunjukkan bidang `SPEC.json` yang diisi.

---

## Kelompok 1 — Ide dan Pengguna

### C-01 ★ Masalah apa yang ingin diselesaikan? (teks bebas)
Tanyakan tiga hal: siapa yang mengalami masalah, bagaimana mereka mengatasinya sekarang, apa akibatnya jika dibiarkan.
→ spec: `problem.statement`, `problem.who`, `problem.current_alternative`

### C-19 ★ Nama aplikasi
Tanyakan nama aplikasi (boleh nama kerja). Jika mahasiswa memilih Z, usulkan 2–3 nama pendek, biarkan ia memilih, lalu catat `meta.app_name` di `meta.assistant_proposed`.
→ spec: `meta.app_name`, `meta.slug`

### C-02 ★ Platform utama aplikasi
- A. **Web** — dibuka lewat browser, tanpa instalasi; cocok untuk sebagian besar ide dan paling mudah dibagikan.
- B. **Mobile** — aplikasi Android/iOS; cocok bila butuh kamera, lokasi, notifikasi, atau dipakai sambil bergerak.
- C. **Desktop** — aplikasi Windows/macOS/Linux; cocok bila butuh akses file/perangkat keras lokal atau bekerja offline.
- D. **IoT** — perangkat dengan sensor/aktuator (mis. ESP32) plus dashboard; cocok untuk monitoring dan kontrol dunia fisik.
→ spec: `meta.platform` (a=web, b=mobile, c=desktop, d=iot). Muat `questions/<platform>.md` setelah ini.

Lanjutkan dengan: "Apakah ada platform pendukung? Misalnya aplikasi mobile dengan panel admin web, atau perangkat IoT dengan aplikasi mobile." Jika ya, catat sebagai platform pendukung. Pertanyaan bank platform pendukung **tidak** diajukan semuanya; cukup tanyakan stack dan fitur yang ada di platform tersebut, lalu jelaskan di ARCHITECTURE.
→ spec: `meta.secondary_platforms`

### C-18 Kondisi awal proyek
- A. **Mulai dari nol**: repositori baru.
- B. **Sudah ada kode**: melanjutkan proyek, tugas, atau template yang sudah ada. Minta mahasiswa menyebut stack yang sudah dipakai; stack itu menjadi `custom` dan T-001 menjadi audit repositori (struktur, perintah yang sudah jalan, tes yang ada).
→ spec: `meta.starting_point` (a=new, b=existing)

### C-03 Siapa pengguna utamanya? (boleh pilih lebih dari satu)
- A. Masyarakat umum
- B. Pelajar/mahasiswa
- C. UMKM atau pelaku usaha kecil
- D. Anggota organisasi/komunitas/institusi (internal)
- E. Petani, nelayan, atau pekerja lapangan
- F. Lainnya (sebutkan)
Lanjutkan dengan: "Satu orang nyata seperti apa yang paling terbantu? Apa yang ia katakan tentang masalahnya?"
→ spec: `users[]` (`persona`, `need`, `quote`). Kutipan boleh dirumuskan mahasiswa; jangan menciptakan testimoni seolah dari orang sungguhan.

### C-04 ★ Fitur inti — pilih 3 sampai 6 dari daftar atau tulis sendiri
- A. Login dan akun pengguna
- B. Tambah/ubah/hapus/lihat data (CRUD)
- C. Pencarian dan filter
- D. Notifikasi (email/push/WhatsApp)
- E. Pembayaran atau pencatatan transaksi
- F. Unggah dan simpan file/foto
- G. Dasbor dan grafik
- H. Ekspor laporan (PDF/Excel)
- I. Peta/lokasi
- J. Chat atau komentar
- K. Fitur AI (rekomendasi, klasifikasi, asisten)
- L. Lainnya (sebutkan)
Untuk **setiap** fitur terpilih, tanyakan: "Bagaimana kita tahu fitur ini berhasil? (aksi apa → hasil apa yang terlihat)". Itu menjadi `acceptance`.
→ spec: `features[]`

### C-05 ★ Prioritas fitur
Minta mahasiswa menandai tiap fitur: **must** (tanpa ini aplikasi tidak berguna), **should** (penting tapi bisa menyusul), **could** (bonus). Saran: maksimal 5 `must` agar proyek selesai.
→ spec: `features[].priority`

### C-20 ★ Tujuan, ukuran keberhasilan, dan batasan
Tanyakan tiga hal dalam satu giliran:
1. "Apa dua hasil utama yang ingin dicapai?" → `goals`
2. "Bagaimana kamu akan mengukur bahwa proyek berhasil? Sebutkan minimal dua ukuran dan cara mengukurnya." → `metrics`
3. "Apa yang sengaja **tidak** akan dikerjakan di proyek ini?" → `non_goals`
Jika mahasiswa memilih Z untuk sebagian, usulkan dari jawaban C-01 dan C-04, minta persetujuan, dan catat jalur bidangnya di `meta.assistant_proposed` (mis. `metrics`, `non_goals`). Jangan menulis angka target sebagai fakta bila mahasiswa tidak menyebutnya; tandai "usulan".
→ spec: `goals`, `metrics`, `non_goals`

---

## Kelompok 2 — Data, Peran, AI, Kondisi Pengguna

### C-06 ★ Data pribadi apa yang diproses?
- A. Tidak ada data pribadi.
- B. Dasar: nama dan email.
- C. Lokasi, nomor telepon, kontak, atau foto.
- D. Sensitif: kesehatan, keuangan, data anak, biometrik, identitas resmi.
Jelaskan dampaknya: C dan D memicu bagian keamanan yang lebih ketat dan pertimbangan UU PDP; untuk D sarankan memakai data fiktif selama pengembangan.
→ spec: `data.personal_data`, `data.sensitive_notes`

### C-07 Siapa saja yang memakai aplikasi (peran)?
- A. Satu jenis pengguna saja.
- B. Pengguna biasa dan admin.
- C. Tiga peran atau lebih (sebutkan, mis. pemilik, staf, pelanggan).
→ spec: `security.roles`

### C-08 Apakah aplikasi memakai AI di dalamnya?
- A. Tidak.
- B. Ya, lewat API model (mis. memanggil layanan LLM) — mudah, tetapi ada biaya, batas penggunaan, dan data keluar dari aplikasi.
- C. Ya, model berjalan di perangkat sendiri — lebih privat dan offline, tetapi lebih berat dan lebih sulit.
Jika B atau C: tanyakan tujuan spesifik (satu kalimat) dan apa yang terjadi bila AI salah.
→ spec: `ai_in_app.mode`, `ai_in_app.purpose`

### C-09 Kondisi pengguna (boleh pilih lebih dari satu)
- A. Koneksi internet sering lambat atau putus.
- B. Perangkat murah/spesifikasi rendah.
- C. Pengguna perlu antarmuka Bahasa Indonesia (dan/atau daerah).
- D. Pengguna dengan kebutuhan aksesibilitas (penglihatan, motorik, dsb.).
- E. Dipakai di luar ruangan/terang matahari.
- F. Tidak ada kondisi khusus.
→ spec: `user_conditions[]`

---

## Kelompok 3 — Tech Stack (diajukan SEBELUM bank pertanyaan platform)

### C-16 ★ Bagaimana kamu ingin menentukan tech stack?
- A. **Ikuti rekomendasi kit**: saya pilih dari opsi yang disediakan untuk platform ini (cocok bila belum punya preferensi).
- B. **Stack pilihan saya sendiri**: saya sudah tahu atau wajib memakai teknologi tertentu (mis. Laravel, Django, Flutter, Spring Boot, Go, Svelte, .NET, MicroPython, atau teknologi lain).
- C. **Campuran**: sebagian lapisan saya tentukan sendiri, sisanya ikut rekomendasi.

Jika **B atau C**:
1. Minta mahasiswa menulis per lapisan yang ia tentukan: bahasa, framework UI/frontend, backend, database, auth, hosting/distribusi, pengujian, dan versi **hanya bila ia tahu pasti**.
2. Tanyakan alasannya dalam satu kalimat (mis. sudah menguasai, dipakai di tempat magang, syarat mata kuliah, ingin belajar). Alasan ini milik mahasiswa; jangan menggantinya.
3. **Hormati pilihan itu.** Jangan mengganti, menggiring, atau mengulang-ulang saran ke rekomendasi kit. Jika ada risiko nyata (teknologi di daftar "Usang" `tech-baseline.md`, tidak cocok dengan platform, atau berbayar), sampaikan **satu kali** dalam maksimal dua kalimat, lalu ikuti keputusan mahasiswa dan catat di `DECISIONS.md`.
4. Jika kamu kurang mengenal teknologi tersebut, katakan terus terang dan minta tautan dokumentasi resminya. Jangan mengarang perintah, struktur folder, atau nama paket.
5. Pada bank pertanyaan platform, pertanyaan bertanda **[STACK]** untuk lapisan yang sudah ditentukan mahasiswa: **jangan tampilkan opsinya**, cukup konfirmasi isiannya, lalu tetap ajukan bagian "Tambahkan:" (entitas data, desain, dan sebagainya). Pertanyaan tanpa tanda [STACK] (offline, perangkat keras, keselamatan, distribusi) tetap diajukan seperti biasa.

→ spec: `stack.source` (a=recommended, b=custom, c=mixed), `stack.custom_reason`, `stack.custom_layers`, `stack.versions`, dan isian lapisan di `stack.*`

### C-17 ★ Ekosistem dan perintah proyek (diajukan SETELAH bank pertanyaan platform)
Berdasarkan stack final (rekomendasi atau pilihan sendiri), **kamu** mengusulkan:
1. `stack.ecosystems`: satu atau lebih dari `node`, `python`, `php`, `dart-flutter`, `go`, `rust`, `jvm`, `dotnet`, `platformio`, `other`.
2. `stack.commands`: perintah `install`, `dev`, `lint`, `typecheck` (boleh kosong), `test`, `build` yang lazim untuk stack itu (lihat tabel contoh di `schema/spec.schema.md`).

Tampilkan sebagai tabel dan minta mahasiswa mengonfirmasi atau mengoreksi; bila mahasiswa sudah memakai perintah lain (mis. `pnpm`, `yarn`, `bun`, `poetry`, `uv`, `make`), pakai perintahnya. Perintah ini akan disalin persis ke AGENTS.md, TESTING.md, EVALS.md, dan CI.
→ spec: `stack.ecosystems`, `stack.commands`

---

## Kelompok 4 — Proses dan Alat

### C-10 ★ Level proses
- A. **Dasar** — satu pengembang, alur sederhana, pengujian minimum; cocok untuk pemula dan proyek 1–2 minggu.
- B. **Menengah** — tugas terstruktur, CI wajib, review rutin; cocok untuk proyek 3–6 minggu atau tim kecil.
- C. **Loop** — agen berputar (rencana → kode → uji → tinjau) dengan batas dan kondisi berhenti yang ketat; hanya bila sudah nyaman dengan Git dan pengujian otomatis. Menambah `docs/LOOP.md`.
→ spec: `meta.level`

### C-11 Alat AI yang akan dipakai (boleh lebih dari satu)
- A. Claude Code
- B. Codex
- C. Gemini CLI atau Antigravity
- D. Cursor
- E. GitHub Copilot
- F. Windsurf atau editor AI lain
- G. Hanya chat di browser (copy-paste manual)
→ spec: `meta.ai_tools`. Dipakai untuk menentukan file penunjuk dan gaya prompt pertama.

### C-12 Bahasa dokumen
- A. Bahasa Indonesia (heading tetap Inggris; isi Indonesia).
- B. English.
→ spec: `meta.language`

### C-13 Ukuran tim dan waktu
Tanyakan jumlah anggota dan berapa minggu tersedia. Gunakan untuk menentukan jumlah dan ukuran task di `TASKS.md` (aturan: tiap task selesai ≤ setengah hari; proyek 1 minggu ≈ 10–15 task, 4 minggu ≈ 25–40 task).
→ spec: `meta.team_size`, `meta.duration_weeks`

---

## Kelompok 5 — Refleksi (jawaban harus milik mahasiswa)

### C-14 ★ Menurutmu, apa risiko terbesar proyek ini? (teks bebas)
Jangan menjawab atau menyarankan jawaban. Jika mahasiswa menjawab sangat singkat, minta satu kalimat penjelasan.
→ spec: `student_reflection.biggest_risk`

### C-14b ★ Kendali untuk risiko itu
Setelah mahasiswa menjawab C-14 (jangan mengubah jawabannya), **kamu** mengusulkan 1–3 aturan konkret yang dapat diuji untuk mengendalikan risiko tersebut, dengan pola aksi → hasil teramati. Contoh: risiko "denda salah bila tanggal diubah" → "Hanya admin dapat mengubah tanggal kembali, wajib alasan, dan perubahan tercatat (siapa, kapan, nilai lama); denda dihitung ulang otomatis dari tanggal baru." Tampilkan usulan, minta mahasiswa memilih, mengubah, atau menulis sendiri. Aturan yang disetujui menjadi kendali risiko R1, R2, … yang wajib muncul di PRD, EVALS, dan TASKS.
→ spec: `student_reflection.risk_controls`

### C-15 Mengapa proyek ini penting bagimu? (teks bebas)
→ spec: `student_reflection.why_this_project`
