# Tasks: {{spec:meta.app_name}}

## Conventions

- **ID**: `T-001`, `T-002`, ... berurutan, unik.
- **Status**: `todo`, `doing`, `review`, `done`, `blocked`.
- **Ukuran**: `S` (< 1 jam), `M` (1 sampai 3 jam), `L` (setengah hari). Task lebih besar dari `L` harus dipecah.
- Satu task = satu branch = satu PR. Hanya satu task berstatus `doing` per orang.
- Setiap task merujuk fitur (F1, F2, ...) atau "setup"/"dokumen", dan memiliki kriteria selesai yang dapat dicek.

{{WRITE: tambahkan satu paragraf pendek bahwa agen hanya mengambil task dengan status todo dan semua dependensinya done, mengubah status ke doing saat mulai dan ke review saat PR dibuka. Mahasiswa mengubah ke done setelah merge.}}

## Milestones

{{WRITE: tabel Milestone, Tujuan, Fitur, Perkiraan minggu. Sesuaikan jumlah milestone dengan spec.meta.duration_weeks (1 sampai 2 minggu: dua milestone; 3 sampai 6 minggu: tiga sampai empat; lebih lama: sampai enam). Milestone pertama selalu "Fondasi dan setup", milestone terakhir "Penyempurnaan dan rilis". Seluruh fitur must ada sebelum milestone terakhir.}}

## Task List

{{WRITE: tabel dengan kolom persis: ID, Judul, Fitur, Ukuran, Status, Branch, Bergantung pada, Kriteria selesai. Semua baris data diawali "| T-" agar dapat diperiksa mesin. Urutan: T-001 setup repositori, tooling, dan kerangka minimal BESERTA satu tes contoh, sehingga di akhir T-001 semua perintah di spec.stack.commands lulus dan CI hijau; T-001 tidak boleh bergantung pada hasil task lain, dan tulis langkah konkretnya (perintah scaffold resmi, berkas yang dibuat). Untuk IoT, T-001 membuat environment firmware dan environment native untuk tes; bila spec.meta.starting_point adalah existing, T-001 adalah audit repositori yang sudah ada (struktur, perintah yang berjalan, tes yang ada, rahasia yang mungkin ter-commit) dan penyesuaian ke dokumen ini. T-002 adalah langkah pertama fitur must paling dasar (atau, untuk existing, perbaikan pertama hasil audit), lalu task per fitur dalam urutan dependensi (mulai fitur must), memecah tiap fitur menjadi task berukuran S sampai L dengan kriteria selesai dari spec.features[].acceptance. Setiap ID fitur di spec.features WAJIB muncul di minimal satu task, dan setiap kendali risiko R1, R2, dan seterusnya WAJIB muncul di kolom Fitur minimal satu task. Rujukan Bergantung pada hanya ke ID task yang ada. Jumlah task: ikuti panduan 1 minggu sekitar 10 sampai 15, 4 minggu sekitar 25 sampai 40. Semua status awal todo. Akhiri dengan task rilis, dokumentasi README, dan refleksi (isi LESSONS.md). Untuk iot sertakan task uji perangkat nyata dan keselamatan; untuk mobile dan desktop sertakan task build distribusi.}}

## Backlog

{{WRITE: daftar fitur should/could atau ide yang belum dijadwalkan dalam bentuk ID fitur dan satu kalimat; boleh kosong dengan tulisan "Kosong" bila semua fitur sudah terjadwal.}}
