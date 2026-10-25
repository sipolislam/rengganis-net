# Bank Pertanyaan — Web

Muat setelah C-02 = Web. Sajikan 3–4 pertanyaan per giliran. Opsi bertanda **(Rekomendasi pemula)** adalah default bila mahasiswa memilih Z. Versi dan nama teknologi: lihat `tech-baseline.md` bagian `[web]`.


> **Stack bebas:** pertanyaan bertanda **[STACK]** memilih teknologi. Jika `stack.source` = `custom` atau `mixed` (C-16), untuk lapisan yang sudah ditentukan mahasiswa jangan tampilkan opsinya; konfirmasi isiannya dan tetap ajukan bagian "Tambahkan:". Opsi di bawah adalah **rekomendasi, bukan batasan**; jawaban teks bebas berupa teknologi lain selalu diterima.

### W-01 [STACK] ★ Model aplikasi web
- A. **Framework full-stack** (Next.js) — frontend dan backend dalam satu proyek; paling sedikit bagian yang bergerak. **(Rekomendasi pemula)**
- B. **Frontend dan API terpisah** — mis. React/Vue + FastAPI/Laravel/Express; cocok bila sudah menguasai backend tertentu atau akan melayani aplikasi mobile juga.
- C. **Situs statis/konten** (Astro/SvelteKit + CMS) — cepat dan murah, tetapi interaksi terbatas.
→ spec: `platform_specific.web.rendering`, `stack.frontend`, `stack.backend`

### W-02 [STACK] Bahasa pemrograman utama
- A. TypeScript (frontend + backend sama bahasa). **(Rekomendasi pemula)**
- B. Python (FastAPI/Django) untuk backend, TypeScript untuk frontend.
- C. PHP (Laravel) — cocok bila sudah akrab dengan PHP.
- D. Lainnya (sebutkan).
→ spec: `stack.backend` (bahasa/framework)

### W-03 [STACK] ★ Database
- A. **PostgreSQL terkelola** (Supabase/Neon) — relasional, paket gratis tersedia, paling fleksibel. **(Rekomendasi pemula)**
- B. SQLite/Turso — sangat sederhana untuk data kecil, kurang cocok untuk banyak penulis bersamaan.
- C. MySQL/MariaDB — umum di hosting Indonesia dan Laravel.
- D. NoSQL (MongoDB/Firestore) — cocok untuk data bentuk bebas; hati-hati dengan relasi dan konsistensi.
Tambahkan: "Entitas data utama apa saja, dan field-nya?" → spec: `data.entities[]`
→ spec: `stack.database`

### W-04 [STACK] ★ Autentikasi
- A. **Layanan terkelola** (Supabase Auth / Clerk / Better Auth / Auth.js) — aman dan cepat; jangan menulis hash password sendiri. **(Rekomendasi pemula)**
- B. Login sosial (Google) saja.
- C. Email + password dengan verifikasi email.
- D. Tidak perlu login (hanya data publik).
→ spec: `stack.auth`, `security.auth_method`

### W-05 [STACK] Tampilan (UI)
- A. Tailwind CSS + shadcn/ui. **(Rekomendasi pemula)**
- B. Pustaka komponen siap pakai (MUI/Mantine/Ant Design).
- C. CSS sendiri tanpa pustaka.
Tambahkan: nada visual (mis. ramah, formal, minimalis), warna utama, apakah perlu mode gelap.
→ spec: `design.*`, `stack.frontend`

### W-06 [STACK] Hosting dan deployment
- A. **Vercel atau Cloudflare** — deploy dari GitHub, cocok untuk Next.js dan situs statis. **(Rekomendasi pemula)**
- B. Render/Railway — cocok untuk backend terpisah.
- C. VPS + Docker — kendali penuh, butuh pemeliharaan.
- D. Hosting kampus/institusi.
→ spec: `stack.hosting`

### W-07 Layanan tambahan (boleh lebih dari satu)
- A. Penyimpanan file (Supabase Storage/Cloudflare R2/S3)
- B. Email transaksional (Resend/Brevo)
- C. Pembayaran Indonesia (Midtrans/Xendit, mode sandbox)
- D. Realtime (WebSocket/Supabase Realtime)
- E. Peta (Leaflet + OpenStreetMap)
- F. Analitik (Plausible/Umami)
- G. Tidak ada
→ spec: `stack.extra[]`

### W-08 Kebutuhan web khusus
- A. Dapat dipasang seperti aplikasi (PWA) dan bekerja saat koneksi buruk.
- B. SEO penting (halaman publik ingin ditemukan di mesin pencari).
- C. Dua bahasa atau lebih.
- D. Tidak ada kebutuhan khusus.
→ spec: `platform_specific.web.seo`, `platform_specific.web.i18n`, `user_conditions`

### W-09 [STACK] Pengujian
- A. Unit test (Vitest/Jest) + satu alur E2E utama dengan Playwright. **(Rekomendasi pemula)**
- B. Unit test saja.
- C. Uji manual dengan checklist (tidak disarankan sebagai satu-satunya).
→ spec: `stack.testing`

### W-10 ★ Kata kunci stack
Berlaku juga untuk stack pilihan mahasiswa: ambil kata kunci dari teknologi yang ia sebut.
Setelah semua jawaban, **kamu** (bukan mahasiswa) merumuskan 3–6 kata kunci ringkas, mis. `Next.js`, `TypeScript`, `PostgreSQL`, `Supabase`, `Tailwind CSS`, dan tunjukkan ke mahasiswa untuk dikonfirmasi.
→ spec: `stack.keywords`
