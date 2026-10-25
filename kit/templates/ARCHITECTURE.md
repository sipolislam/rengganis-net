# Architecture: {{spec:meta.app_name}}

## Overview

{{WRITE: tiga sampai lima kalimat yang menjelaskan bagaimana sistem bekerja secara garis besar, menyebut platform spec.meta.platform dan komponen utama dari spec.stack.}}

## Tech Stack

| Lapisan | Pilihan | Alasan | Alternatif yang ditolak |
|---|---|---|---|
{{WRITE: satu baris tabel per lapisan: Frontend/UI, Backend, Database, Auth, Hosting, Testing, dan setiap item spec.stack.extra. Isi Pilihan dari spec.stack. Alasan satu kalimat yang spesifik pada kebutuhan proyek ini; untuk lapisan yang dipilih mahasiswa sendiri (spec.stack.source custom, atau lapisan di spec.stack.custom_layers bila mixed) gunakan spec.stack.custom_reason dan tulis "(pilihan mahasiswa)". Alternatif: untuk lapisan pilihan mahasiswa tulis tanda pisah (-), karena rekomendasi kit cukup disebut sekali di DECISIONS ADR-002; untuk lapisan lain tulis alternatif yang ditolak. Versi: dari spec.stack.versions bila ada, selain itu "latest stable (verifikasi: perintah dari tech-baseline.md)".}}

## System Diagram

```mermaid
{{WRITE: diagram Mermaid (flowchart LR atau TD) yang menunjukkan komponen, arah aliran data, dan batas kepercayaan (klien, server, layanan eksternal; untuk iot: perangkat, broker, dasbor). Gunakan nama komponen yang sama dengan tabel Tech Stack. Hanya sintaks Mermaid, tanpa teks lain.}}
```

## Project Structure

```text
{{WRITE: pohon folder proyek yang sesuai framework di spec.stack, dengan komentar singkat tiap folder penting. Sertakan docs/, AGENTS.md, README.md, .github/. Gunakan struktur standar framework tersebut; jangan menciptakan folder yang tidak lazim.}}
```

## Data Model

{{WRITE: untuk setiap entitas di spec.data.entities tulis tabel kolom: Field, Tipe, Keterangan. Tambahkan relasi antar entitas dalam kalimat atau diagram Mermaid erDiagram. Tandai field yang berisi data pribadi sesuai spec.data.personal_data.}}

## Data Flow

{{WRITE: tuliskan dua sampai tiga alur data utama sebagai urutan langkah dari pemicu sampai penyimpanan dan tampilan, merujuk ID fitur (F1, F2, ...). Sebutkan tempat validasi input dan otorisasi dilakukan.}}

## External Services

{{WRITE: tabel Layanan, Fungsi, Data yang dikirim, Batas/biaya gratis (tulis "cek paket terbaru" bila tidak pasti), Rencana cadangan bila layanan gagal. Jika tidak ada layanan eksternal, tulis satu kalimat.}}

## Environments and Configuration

{{WRITE: tiga lingkungan (lokal, staging/preview, produksi) dengan perbedaannya. Daftar variabel lingkungan yang dibutuhkan dengan nama saja (tanpa nilai), mana yang rahasia, dan di mana nilainya disimpan. Ingatkan bahwa .env tidak di-commit dan .env.example disediakan.}}

## Deployment

{{WRITE: langkah deployment dari merge ke main sampai produksi sesuai spec.stack.hosting, termasuk perintah build, di mana CI berjalan (GitHub Actions), cara rollback, dan cara memeriksa kesehatan. Untuk mobile: build dan distribusi (spec.platform_specific.mobile.distribution). Untuk desktop: pengemasan installer dan penandatanganan. Untuk iot: cara flash firmware, OTA bila spec.platform_specific.iot.ota true, dan pemulihan perangkat.}}

## Platform Notes

{{WRITE: bila spec.meta.secondary_platforms tidak kosong, mulai dengan subbagian per platform pendukung (sebut nama platformnya, mis. "Platform pendukung: web") yang menjelaskan fungsinya, stack-nya, dan cara berbagi data/API dengan platform utama. Lalu catatan khusus platform spec.meta.platform. Web: rendering, caching, SEO bila relevan. Mobile: izin perangkat, perilaku offline sesuai spec, ukuran aplikasi, perangkat kelas menengah ke bawah. Desktop: izin sistem/capabilities, penyimpanan lokal, pembaruan. IoT: papan, pin, daya, interval kirim, topik MQTT dan format payload, perilaku saat jaringan putus. Maksimal 12 butir.}}

## Constraints and Trade-offs

{{WRITE: daftar batasan nyata (waktu spec.meta.duration_weeks, ukuran tim, paket gratis layanan, kemampuan tim) dan trade-off sadar yang diambil, masing-masing satu kalimat. Rujuk nomor ADR di DECISIONS.md; pastikan nomor ADR yang dirujuk benar membahas topik tersebut (ADR-001 platform, ADR-002 frontend, ADR-003 backend dan data, ADR-004 auth dan keamanan, ADR-005 hosting, ADR-006 pengujian dan level). Prioritas dan ruang lingkup fitur dirujuk ke PRD, bukan ke ADR.}}
