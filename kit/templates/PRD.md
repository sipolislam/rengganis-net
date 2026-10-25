# Product Requirements Document: {{spec:meta.app_name}}

> {{spec:meta.one_liner}}

## Overview

| Field | Isi |
|---|---|
| Nama produk | {{spec:meta.app_name}} |
| Platform | {{spec:meta.platform}} |
| Level proses | {{spec:meta.level}} |
| Tagline | {{WRITE: satu kalimat maksimal 12 kata yang menjelaskan nilai produk}} |
| Tim dan waktu | {{WRITE: tulis jumlah anggota dan durasi minggu dari spec.meta.team_size dan spec.meta.duration_weeks}} |

## Problem

{{WRITE: 3 sampai 5 kalimat dari spec.problem: siapa yang mengalami masalah, bagaimana mereka mengatasinya sekarang, dan akibatnya. Jangan menambah statistik atau klaim yang tidak ada di spec.}}

## Goals and Non-Goals

### Goals

{{WRITE: daftar bernomor dari spec.goals. Setiap goal harus terukur atau dapat diamati; bila goal di spec kurang terukur, pertajam tanpa mengubah maksudnya.}}

### Non-Goals

{{WRITE: daftar dari spec.non_goals dengan satu kalimat alasan mengapa sengaja tidak dikerjakan sekarang.}}

## Target Users

{{WRITE: tabel dengan kolom Persona, Kebutuhan, Kutipan dari spec.users. Tambahkan satu baris tentang kondisi pengguna dari spec.user_conditions bila tidak kosong. Kutipan yang jalurnya tercantum di spec.meta.assistant_proposed diberi label "(ilustrasi, dirumuskan asisten)"; kutipan dari mahasiswa ditulis tanpa label tersebut.}}

## Core Features

{{WRITE: untuk setiap item spec.features tulis satu blok persis dengan format: heading tingkat tiga berisi ID, nama, dan prioritas, misalnya "### F1 - Nama Fitur (must)"; satu sampai dua kalimat deskripsi; lalu daftar Acceptance Criteria berupa butir yang dapat diuji dengan pola aksi dan hasil teramati. ID harus identik dengan spec. Urutkan sesuai spec.}}

## User Flows

{{WRITE: dua sampai tiga alur pengguna utama sebagai langkah bernomor. Setiap alur menyebut ID fitur yang terlibat (F1, F2, dan seterusnya) dan keadaan galat utama (mis. koneksi putus, input salah).}}

## AI Features

{{WRITE: jika spec.ai_in_app.mode adalah none tulis satu kalimat bahwa tidak ada fitur AI dalam aplikasi. Jika api atau on_device, jelaskan tujuan, masukan dan keluaran, apa yang terjadi bila AI salah, bagaimana pengguna dapat mengoreksi, dan data apa yang dikirim keluar perangkat.}}

## Non-Functional Requirements

{{WRITE: tabel Kategori dan Persyaratan yang mencakup performa, aksesibilitas (WCAG 2.2 AA untuk web/mobile/desktop), kondisi pengguna dari spec.user_conditions, bahasa antarmuka, privasi data sesuai spec.data.personal_data, dan ketersediaan. Untuk iot sertakan keandalan perangkat, interval data, dan perilaku saat jaringan putus. Jangan menciptakan angka yang tidak ada di spec; bila perlu angka, tulis sebagai usulan yang perlu disetujui.}}

## Success Metrics

{{WRITE: tabel Metrik, Target, Cara Ukur dari spec.metrics tanpa mengubah target.}}

## Risks and Open Questions

{{WRITE: bagian pertama berjudul "Risiko menurut mahasiswa" berisi kutipan persis spec.student_reflection.biggest_risk. Lalu subbagian "Kendali risiko yang disetujui mahasiswa": daftar spec.student_reflection.risk_controls dengan ID R1, R2, dan seterusnya sesuai urutan, teks dikutip persis, dan sebut fitur (F) yang terdampak. Lalu daftar spec.risks dengan satu langkah mitigasi per risiko. Lalu daftar pertanyaan terbuka yang muncul saat menyusun dokumen. Jangan menulis angka target atau klaim yang tidak ada di spec.}}
