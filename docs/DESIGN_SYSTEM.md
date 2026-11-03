# Design System: Rengganis Net

## Principles

1. **Jelas dan Berdaya Saing:** Setiap elemen antarmuka menyajikan informasi operasional penagihan dengan hierarki visual tegas, memudahkan kasir di lapangan melihat status tagihan dalam sekali pandang.
2. **Kontras Tinggi untuk Penggunaan Lapangan:** Palet warna dirancang dengan kontras tajam (WCAG 2.2 AA) agar teks tetap terbaca dengan jelas oleh kolektor saat mengoperasikan ponsel di bawah terik sinar matahari.
3. **Efisiensi Interaksi Minimum:** Seluruh tindakan berulang (seperti konfirmasi bayar, cetak struk Bluetooth, atau kirim WhatsApp) dapat diselesaikan dalam 1 hingga 2 ketukan jari.
4. **Resilience & Fallback Jelas:** Sistem selalu menyediakan alternatif visual cadangan yang jelas jika terjadi kegagalan transmisi Bluetooth atau koneksi internet terputus.

## Color Tokens

Palet warna menggunakan tema profesional *Navy Slate & Amber*, mengacu pada token primer `#1E3A8A`:

| Token | Nilai Terang | Nilai Gelap | Penggunaan & Rasio Kontras |
|---|---|---|---|
| `color-primary` | `#1E3A8A` (Deep Blue) | `#3B82F6` (Bright Blue) | Elemen identitas utama, header, tombol aksi primer (Kontras 8,5:1 terhadap putih) |
| `color-secondary` | `#0F172A` (Slate 900) | `#1E293B` (Slate 800) | Sidebar navigasi, teks judul utama (Kontras 12,2:1 terhadap putih) |
| `color-background`| `#F8FAFC` (Slate 50) | `#0B0F19` (Dark Navy) | Latar belakang seluruh halaman aplikasi |
| `color-surface` | `#FFFFFF` (White) | `#151E2E` (Navy Card) | Latar kartu informasi, tabel data, dan jendela dialog modal |
| `color-text-main` | `#0F172A` (Slate 900) | `#F8FAFC` (Slate 50) | Teks isi utama paragraf dan label formulir |
| `color-text-muted`| `#64748B` (Slate 500) | `#94A3B8` (Slate 400) | Teks keterangan bantuan, timestamp, dan catatan kaki |
| `color-border` | `#E2E8F0` (Slate 200) | `#334155` (Slate 700) | Garis batas kartu, pemisah baris tabel, dan input formulir |
| `color-success` | `#16A34A` (Green 600) | `#22C55E` (Green 500) | Status tagihan lunas, pelanggan aktif, dan notifikasi berhasil |
| `color-warning` | `#D97706` (Amber 600) | `#F59E0B` (Amber 500) | Status pelanggan cuti, peringatan tagihan mendekati jatuh tempo |
| `color-danger` | `#DC2626` (Red 600) | `#EF4444` (Red 500) | Status tagihan belum lunas, pelanggan isolir, dan pesan galat |
| `color-focus` | `#2563EB` (Blue 600) | `#60A5FA` (Blue 400) | Cincin fokus keyboard untuk aksesibilitas visual |

## Typography

Tipografi aplikasi memadukan **Plus Jakarta Sans** untuk antarmuka modern dan **JetBrains Mono** untuk angka keuangan serta kode transaksi:

| Gaya | Ukuran | Bobot | Tinggi Baris | Penggunaan |
|---|---|---|---|---|
| Display / H1 | 24 px (1.5rem) | 700 (Bold) | 32 px | Judul halaman utama, ringkasan metrik dasbor |
| Section / H2 | 20 px (1.25rem) | 600 (Semibold) | 28 px | Judul modul, header kartu besar |
| Card / H3 | 16 px (1.0rem) | 600 (Semibold) | 24 px | Judul tabel, header modal dialog |
| Body Main | 16 px (1.0rem) | 400 (Regular) | 24 px | Teks isi utama, nilai input formulir, paragraf |
| Small / Muted | 13 px (0.8125rem) | 400 (Regular) | 18 px | Catatan kaki, badge status, keterangan tanggal |
| Code / Financial | 15 px (0.9375rem) | 500 (Medium) | 22 px | Nominal rupiah, kode pelanggan, IP Address (JetBrains Mono) |

## Spacing and Layout

Sistem tata letak berbasis kelipatan 4 px:
- **Skala Spacing:** `p-1` (4px), `p-2` (8px), `p-3` (12px), `p-4` (16px), `p-6` (24px), `p-8` (32px).
- **Kerangka Tata Letak:** Menggunakan struktur sidebar tetap di sisi kiri pada desktop (`w-64`) dan navigasi responsif yang dapat diciutkan pada layar mobile.
- **Lebar Konten Maksimum:** Konten dibatasi pada `max-w-7xl` (1280px) untuk menjaga keterbacaan data pada layar monitor lebar.
- **Area Sentuh Mobile:** Seluruh tombol dan baris aksi dioptimalkan dengan tinggi minimal 44 px untuk memudahkan ketukan jari pada smartphone Android.

## Components

1. **Tombol (Button):**
   - *Varian:* Primary (Deep Blue solid), Success (Green solid), Warning (Amber solid), Danger (Red solid), Ghost / Outline (Border Slate).
   - *Status:* Default, Hover (perubahan kecerahan 10%), Focus (cincin outline 2px), Disabled (opacity 50%, kursor not-allowed), Loading (ikon spinner berputar).
   - *Aturan:* Tombol aksi primer harus selalu unik per area kerja agar tidak membingungkan pengguna.
2. **Badge Status:**
   - *Status Pelanggan:* Aktif (`bg-green-100 text-green-800`), Cuti (`bg-amber-100 text-amber-800`), Isolir (`bg-red-100 text-red-800`).
   - *Status Tagihan:* Lunas (`bg-emerald-100 text-emerald-800`), Belum Lunas (`bg-rose-100 text-rose-800`).
3. **Kartu Statistik (KPI Card):**
   - Wadah berlatar putih dengan bayangan halus (`shadow-sm hover:shadow-md transition`), menampilkan ikon berwarna, angka metrik besar (JetBrains Mono), dan persentase perubahan.
4. **Tabel Data (Data Table):**
   - Header berwarna abu-abu terang dengan teks semibold huruf kecil kapital. Setiap baris memiliki efek hover `bg-slate-50` dan padding vertikal lega (12px) untuk kemudahan membaca.
5. **Jendela Modal (Dialog):**
   - Menampilkan latar belakang gelap transparan (`bg-black/50 backdrop-blur-xs`), judul jelas, tombol silang penutup di pojok kanan atas, serta tombol aksi konfirmasi di bagian bawah. Khusus modal struk, disediakan pratinjau struk 58mm dengan tombol cetak Bluetooth ESC/POS yang menonjol.
6. **Notifikasi Toast / Alert:**
   - Menampilkan pesan konfirmasi sukses, peringatan, atau galat sistem di pojok kanan atas layar dengan ikon sesuai dan tombol tutup otomatis dalam 4 detik.

## Accessibility

Daftar periksa kepatuhan terhadap WCAG 2.2 AA:
- **Kontras Warna:** Seluruh teks memiliki rasio kontras minimal 4,5:1 terhadap latar belakangnya.
- **Fokus Keyboard:** Setiap tombol, tautan, dan elemen input memiliki cincin fokus terlihat jelas (`ring-2 ring-blue-500 ring-offset-2`).
- **Urutan Tab:** Urutan navigasi tab keyboard mengikuti alur logis dari atas ke bawah dan kiri ke kanan.
- **Label Formulir:** Setiap input teks, dropdown, dan textarea memiliki pasangan tag `<label>` yang terhubung via atribut `for` dan `id`.
- **Target Sentuh:** Seluruh tombol aksi pada versi mobile memiliki ukuran area klik minimal 44 x 44 CSS px.
- **Bukan Hanya Warna:** Status tagihan dan pelanggan tidak hanya dibedakan dengan warna, melainkan selalu disertai teks label eksplisit ("Lunas", "Belum Lunas", "Cuti").
- **Gerak Berkurang:** Menghormati pengaturan sistem operasi `prefers-reduced-motion` dengan mematikan transisi berat.

## Motion and Feedback

- **Durasi Animasi:** Menggunakan durasi mikro 150ms hingga 200ms dengan transisi `cubic-bezier(0.4, 0, 0.2, 1)`.
- **Indikator Memuat (Loading State):** Tombol yang sedang memproses request AJAX menampilkan ikon putar (spinner) dan teks dinonaktifkan untuk mencegah klik ganda.
- **Keadaan Kosong (Empty State):** Jika tabel data tidak memiliki rekaman (misal belum ada tagihan), sistem menampilkan ilustrasi visual minimalis, pesan bersahabat, dan tombol ajakan bertindak (CTA).
- **Contoh Pesan Galat Nyata:**
  1. *Koneksi Bluetooth:* "Printer Bluetooth tidak terdeteksi. Pastikan Bluetooth aktif dan printer dalam jarak dekat, atau gunakan tombol Cetak Browser."
  2. *Validasi Pelanggan:* "Nomor WhatsApp tidak valid. Masukkan nomor telepon aktif yang diawali dengan 08 atau 62."
  3. *Batch Billing Duplikat:* "Tagihan untuk periode Oktober 2026 sudah pernah diterbitkan. Sistem membatalkan proses untuk mencegah duplikasi tagihan."

## Content and Voice

- **Nada Bahasa:** Lugas, informatif, profesional, dan ramah pengguna.
- **Panjang Kalimat:** Singkat, padat, dan langsung pada inti informasi.
- **Glosarium Istilah Baku:**
  - *Pelanggan:* Pengguna akhir layanan WiFi RT/RW Net.
  - *Paket:* Pilihan kuota kecepatan dan harga bulanan internet.
  - *Cuti:* Status penonaktifan sementara layanan atas permintaan pelanggan tanpa dikenakan tagihan baru.
  - *Isolir:* Pemutusan sementara sambungan internet karena keterlambatan pelunasan tagihan.
  - *Struk Thermal:* Bukti pembayaran fisik yang dicetak menggunakan printer mini berpita panas (58mm/80mm).
  - *Collection Rate:* Persentase perbandingan antara nominal tagihan yang berhasil ditagih dengan total potensi tagihan bulanan.
- **Format Tanggal:** Format baku Indonesia: `DD MMMM YYYY` (contoh: 25 Oktober 2026) atau `DD/MM/YYYY`.
- **Format Mata Uang:** Standar moneter Indonesia: `Rp 150.000` (menggunakan pemisah titik untuk ribuan).
