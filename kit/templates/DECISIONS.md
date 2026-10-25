# Decisions: {{spec:meta.app_name}}

Catatan keputusan arsitektur (Architecture Decision Records). Keputusan **tidak dibatalkan diam-diam**: untuk mengubahnya, tambahkan ADR baru yang berstatus `Supersedes ADR-nnn` dan jelaskan alasannya.

## How to Use

{{WRITE: tiga butir: kapan membuat ADR (pilihan teknologi, perubahan skema, kompromi keamanan, penambahan dependensi besar), format ADR di bawah, dan bahwa agen wajib membaca ADR sebelum mengusulkan perubahan arsitektur.}}

## Decision Records

Semua ADR di bawah memakai tanggal {{spec:meta.created_date}} kecuali dibuat kemudian.

### ADR-001: Platform {{spec:meta.platform}}

{{WRITE: tulis dalam format baris: Status (Accepted), Tanggal, Konteks (kebutuhan dari spec), Keputusan, Konsekuensi, Alternatif yang dipertimbangkan. Berdasarkan spec.meta.platform dan jawaban C-02.}}

### ADR-002: Frontend dan UI

{{WRITE: format ADR yang sama untuk pilihan spec.stack.frontend, termasuk alasan dan alternatif. Bila spec.stack.source bukan recommended, Konteks wajib menyebut bahwa stack dipilih mahasiswa dan mengutip persis spec.stack.custom_reason; Alternatif berisi rekomendasi kit sebagai pembanding tanpa menyarankan perpindahan; Konsekuensi mencatat risiko yang disampaikan saat wawancara (bila ada).}}

### ADR-003: Backend dan data

{{WRITE: format ADR yang sama untuk pilihan spec.stack.backend dan spec.stack.database, termasuk model data pokok.}}

### ADR-004: Autentikasi dan keamanan

{{WRITE: format ADR yang sama untuk spec.stack.auth dan spec.security.auth_method, termasuk peran dari spec.security.roles dan keputusan terkait data pribadi.}}

### ADR-005: Hosting dan deployment

{{WRITE: format ADR yang sama untuk spec.stack.hosting dan distribusi (mobile, desktop, atau firmware sesuai platform).}}

### ADR-006: Strategi pengujian dan level proses

{{WRITE: format ADR yang sama untuk spec.stack.testing dan spec.meta.level, termasuk alasan memilih level tersebut. Alasan hanya dari jawaban mahasiswa (C-10, C-13) dan fakta teknis; JANGAN mengklaim kemampuan, kenyamanan, atau pengalaman mahasiswa yang tidak ia nyatakan.}}

## Delegated Choices

{{WRITE: bila spec.meta.delegated_questions tidak kosong: tabel ID pertanyaan, pilihan yang diambil asisten, alasan satu kalimat, dan kolom "Disetujui mahasiswa: ya". Jika kosong, tulis "Semua pilihan dibuat langsung oleh mahasiswa."}}

## Assistant Proposals

{{WRITE: tabel Bidang SPEC, Nilai yang diusulkan asisten, Alasan singkat untuk setiap jalur di spec.meta.assistant_proposed (tulis jalurnya persis, mis. meta.app_name). Tambahkan kolom "Disetujui mahasiswa". Bila daftarnya kosong, tulis bahwa semua nilai berasal dari mahasiswa. Juga catat di sini konflik antar kriteria yang diselesaikan saat penyusunan SPEC beserta keputusannya.}}

## Open Questions

{{WRITE: daftar hal yang belum pasti atau perlu diverifikasi: versi yang belum dicek (sertakan perintah verifikasi), batas paket gratis layanan, keputusan yang ditunda. Setiap butir punya pemilik (mahasiswa atau dosen) dan kapan harus diputuskan.}}
