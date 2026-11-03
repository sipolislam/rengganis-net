# Design System: {{spec:meta.app_name}}

## Principles

{{WRITE: tiga sampai lima prinsip desain turunan dari spec.design.tone, spec.users, dan spec.user_conditions. Tiap prinsip satu kalimat plus satu contoh penerapan di aplikasi ini.}}

## Color Tokens

{{WRITE: tabel Token, Nilai, Penggunaan. Mulai dari spec.design.primary_color sebagai token primer; turunkan token sekunder, latar, permukaan, teks, teks redup, batas, sukses, peringatan, bahaya, dan fokus. Sediakan nilai terang dan, bila spec.design.dark_mode true, nilai gelap. Pastikan kontras teks minimal 4,5:1 dan elemen UI minimal 3:1 (WCAG 2.2 AA); tulis rasio yang dimaksud dan tandai bila belum dihitung.}}

## Typography

{{WRITE: tabel Gaya, Ukuran, Bobot, Tinggi baris, Penggunaan untuk judul, subjudul, isi, keterangan, dan kode bila perlu. Gunakan spec.design.font; jika kosong pilih font sistem atau font bebas lisensi umum. Ukuran isi minimal 16 px (web) atau setara pada platform lain.}}

## Spacing and Layout

{{WRITE: skala jarak (mis. 4 px) dengan nama token, grid atau kerangka layout, lebar konten maksimum, dan aturan padding. Sesuaikan platform spec.meta.platform: web dan desktop (breakpoint atau ukuran jendela minimum), mobile (area aman dan satu tangan), iot (tata letak layar/dasbor atau pola LED dan buzzer sebagai antarmuka).}}

## Components

{{WRITE: daftar komponen yang benar-benar dibutuhkan fitur di spec.features (tombol, input, kartu, tabel, dialog, notifikasi, navigasi, dan seterusnya). Untuk tiap komponen: varian, status (default, hover/fokus, nonaktif, galat, memuat), dan aturan pemakaian. Rujuk pustaka UI dari spec.stack.frontend sehingga tidak membuat ulang komponen yang sudah ada.}}

## Accessibility

{{WRITE: daftar periksa aksesibilitas sesuai WCAG 2.2 AA: kontras, fokus keyboard terlihat, urutan tab, label formulir, teks alternatif, target sentuh (minimal 24x24 CSS px, rekomendasi 44 sampai 48 pada mobile), tidak mengandalkan warna saja, gerak berkurang, dan pembaca layar. Tambahkan butir untuk kebutuhan di spec.user_conditions.}}

## Motion and Feedback

{{WRITE: aturan animasi singkat (durasi, easing, hormati preferensi gerak berkurang), keadaan memuat, kosong, galat, dan sukses beserta teks pesan yang ramah dan menjelaskan langkah berikutnya. Berikan contoh tiga pesan galat nyata untuk fitur aplikasi ini.}}

## Content and Voice

{{WRITE: gaya bahasa antarmuka dalam bahasa spec.meta.language: nada, panjang kalimat, istilah baku yang dipakai konsisten (glosarium lima sampai sepuluh istilah milik aplikasi ini), format tanggal, angka, dan mata uang untuk Indonesia bila relevan.}}
