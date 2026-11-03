# Protokol Uji Lintas Model

Tujuan: mengukur apakah kit menghasilkan kualitas yang sebanding di beberapa model AI, dan menemukan model mana yang membutuhkan kit versi lebih sederhana. Pilot pertama sudah dijalankan pada 2026-10-20 (lihat bagian 8). Protokol penuh, termasuk model non-Claude, belum dijalankan.

## 1. Rancangan

- **Model**: pilih 4–6, campurkan kelas besar dan kecil (mis. model unggulan dan model hemat dari tiga penyedia berbeda, serta satu model terbuka).
- **Ide uji** (enam, memakai jawaban wawancara yang sudah ditulis tetap di bagian 2 agar variasi hanya berasal dari model):

| Kode | Platform | Ide | Yang diuji |
|---|---|---|---|
| U1 | web | Antrean pasien puskesmas desa dengan pendaftaran daring | Data sensitif, UU PDP, peran, keamanan |
| U2 | mobile | Pencatat hasil panen offline untuk petani kecil | Offline, koneksi buruk, perangkat murah |
| U3 | iot | Pemantau suhu penyimpanan dengan ESP32 dan relay pendingin 220 V | Keselamatan tegangan jaringan, MQTT, OTA |
| U4 | desktop | Kasir offline UMKM dengan printer struk | Akses perangkat keras, penyimpanan lokal |
| U5 | (ambigu) | "Aplikasi untuk membantu mahasiswa belajar" | Penanganan ide samar, kualitas pertanyaan |
| U6 | web (stack sendiri) | Sistem peminjaman alat lab dengan Laravel + MySQL ("syarat mata kuliah") | Kepatuhan pada stack pilihan, perintah composer di CI |

- **Ulangan**: 3 kali per sel (model × ide) untuk melihat variasi. Pengaturan sama: tanpa web search kecuali alatnya memang bawaan, suhu bawaan.
- **Dua mode**: agen (dengan akses berkas) dan chat (bundel `dist/chat`), bila kedua mode relevan.

## 2. Jawaban wawancara tetap

Untuk tiap ide, siapkan satu lembar jawaban (huruf pilihan untuk tiap ID pertanyaan, teks refleksi C-14 dan C-15 yang sama). Bacakan atau tempel jawaban yang sama pada setiap model. Sertakan beberapa jawaban Z agar perilaku rekomendasi ikut teruji.

## 3. Pengumpulan data

Per proses simpan: SPEC.json, seluruh dokumen, log percakapan, jumlah giliran, waktu, dan hasil `validate.py` (jumlah FAIL dan WARN).

## 4. Penilaian

1. Jalankan `python tools/validate.py` untuk tiap hasil; catat jumlah FAIL.
2. Dua penilai (dosen atau asisten) menilai **buta** (tanpa tahu modelnya) memakai `RUBRIC.md` Bagian B, 0–3 per dimensi, total 24.
3. Uji keterpakaian agen: beri satu agen coding hanya `AGENTS.md` dan `docs/`, minta mengerjakan `T-001`; catat apakah ia selesai tanpa bertanya.
4. Hitung rata-rata, nilai minimum, dan simpangan per model; selisih antar penilai di atas 3 poin perlu didiskusikan.

## 5. Lembar hasil

| Model | Mode | Ide | Ulangan | FAIL validator | Skor penilai 1 | Skor penilai 2 | Agen lolos T-001 | Catatan |
|---|---|---|---|---|---|---|---|---|
| | | | | | | | | |

## 6. Keputusan

- Rata-rata ≥ 19 dan minimum ≥ 16: model **cocok** dengan kit saat ini.
- Rata-rata 14–18: model cocok dengan syarat mahasiswa menjalankan validator dan pemeriksaan silang (`07`).
- Rata-rata < 14 atau banyak FAIL pada dimensi yang sama: buat **kit-lite** untuk model itu (kelompok lebih kecil, satu file per giliran, contoh terisi lebih banyak) dan uji ulang.
- Dimensi yang rendah di semua model menunjukkan kelemahan kit: perbaiki template atau bank pertanyaan, bukan memilih model.

## 7. Ancaman terhadap kesahihan

Model berubah tanpa pemberitahuan sehingga hasil dapat bergeser; ulangi tiap semester. Penilaian bergantung pada rubrik, jadi kalibrasi penilai dengan dua contoh sebelum menilai. Hasil pada ide uji belum tentu mewakili semua ide mahasiswa.

## 8. Hasil pilot 2026-10-20

**Cakupan.** Model Claude saja: Haiku, Sonnet, Opus. Fable gagal dijalankan karena kredit penggunaan, dan model penyedia lain (ChatGPT, Gemini) tidak tersedia di lingkungan uji. Dua ide: U3 (IoT, relay 220 V, stack rekomendasi) dan U6 (Laravel + MySQL, stack pilihan mahasiswa, level loop). Satu ulangan per sel, mode agen, lembar jawaban wawancara tetap (tidak interaktif, sehingga dimensi "Kualitas pertanyaan" tidak dinilai; maksimum 21). Dua penilai AI terpisah (Sonnet dan Opus) menilai buta paket yang sudah dianonimkan.

### Putaran 1: kit v1.1

| Model | Ide | FAIL validator | Penilai 1 | Penilai 2 | Rata-rata |
|---|---|---|---|---|---|
| Haiku | U3 | 0 | 17 | 14 | 15,5 |
| Sonnet | U3 | 0 | 18 | 18 | 18,0 |
| Opus | U3 | 0 | 20 | 20 | 20,0 |
| Haiku | U6 | 0 | 18 | 18 | 18,0 |
| Sonnet | U6 | 0 | 17 | 15 | 16,0 |
| Opus | U6 | 0 | 20 | 20 | 20,0 |

Rata-rata per model: Opus 20,0, Sonnet 17,0, Haiku 16,75. Semua model menghormati stack Laravel pilihan mahasiswa, tidak ada angka versi karangan, dan semua menulis peringatan 220 V.

**Temuan utama: validator v1.1 tidak membedakan kualitas.** Keenam paket lolos tanpa FAIL, padahal skor penilai berkisar 14 sampai 20. Kelemahan yang sama di semua model (masalah kit, bukan model):
1. Wawancara tidak menanyakan nama, metrik, non-goal, dan perilaku gagal perangkat, sehingga model mengisinya sendiri tanpa tanda.
2. Risiko yang ditulis mahasiswa (C-14) dikutip tetapi tidak diubah menjadi aturan teruji, sehingga tiap model memilih kebijakan sendiri untuk hal yang paling ditakuti mahasiswa.
3. Konflik antar kriteria (F5 "relay berubah dalam 5 detik" vs F3 "jeda 3 menit") hanya dikenali Opus.
4. Template ADR level proses mendorong klaim tentang kemampuan Git mahasiswa yang tidak pernah ia nyatakan (5 dari 6 paket).
5. CI gagal di repo baru: Laravel tanpa `APP_KEY`, PlatformIO tanpa `-e`. T-001 sering bergantung pada task lain.
6. Rujukan ADR menunjuk topik yang salah; tanggal ADR berupa teks pengisi.

### Putaran 2: kit v1.2 (perbaikan atas temuan di atas), model terlemah (Haiku)

Penilaian dilakukan dalam satu sesi atas 4 paket acak: Haiku v1.1 dan Haiku v1.2 untuk kedua ide.

| Kit | Ide | FAIL validator | Penilai 1 | Penilai 2 | Rata-rata |
|---|---|---|---|---|---|
| v1.1 | U3 | 0 | 12 | 12 | 12,0 |
| v1.2 | U3 | 0 | 20 | 19 | 19,5 |
| v1.1 | U6 | 0 | 15 | 15 | 15,0 |
| v1.2 | U6 | 0 | 19 | 20 | 19,5 |

Pada v1.2, Haiku menghasilkan desain gagal-aman lengkap (sensor error, Wi-Fi putus, boot, watchdog, notifikasi offline lewat Last Will), CI Laravel yang lulus di repo baru, kendali risiko R1–R3 yang teruji, dan T-001 yang dapat diselesaikan sendiri. Sisa cacat di v1.2: rujukan ADR untuk ruang lingkup masih menunjuk ADR-001, contoh commit menyebut nomor task yang salah, klaim "lockfile PlatformIO", dan pembatasan peran yang tidak diminta. Keempatnya sudah ditangani di template dan aturan akhir v1.2, tetapi belum diuji ulang.

### Batasan yang harus dibaca bersama angka di atas

- **Penilai berdrift antar sesi.** Paket Haiku v1.1 U3 yang sama dinilai 15,5 di putaran 1 dan 12,0 di putaran 2. Bandingkan skor hanya di dalam satu sesi penilaian.
- **Peningkatan v1.2 bercampur dua sebab**: perbaikan kit dan jawaban mahasiswa yang lebih lengkap karena kit kini menanyakan lebih banyak. Keduanya memang bagian dari perbaikan, tetapi tidak dapat dipisahkan dari data ini.
- **Penilai adalah model AI dari keluarga yang sama** dengan model yang dinilai. Penilaian dosen manusia tetap diperlukan.
- **Satu ulangan per sel dan dua ide saja.** Belum ada data untuk mobile, desktop, ide ambigu (U5), mode chat, atau model non-Claude.
- **Validator tetap tidak menilai mutu isi.** Ia menangkap kesalahan struktural (rujukan hilang, teks pengisi, perintah tidak konsisten, kendali risiko hilang), bukan keputusan desain yang buruk.

### Langkah berikutnya

1. Ulangi dengan model non-Claude (ChatGPT, Gemini, satu model terbuka) memakai bundel `dist/chat/` dan lembar jawaban yang sama.
2. Tambahkan U2 (mobile), U4 (desktop), dan U5 (ide ambigu, mode interaktif dengan mahasiswa sungguhan).
3. Minta dua dosen menilai buta sebagian paket untuk mengkalibrasi penilai AI.
