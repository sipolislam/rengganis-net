# Hardware: {{spec:meta.app_name}}

## Overview

{{WRITE: satu paragraf: papan dari spec.platform_specific.iot.board, tujuan perangkat, konektivitas, dan daya, memakai istilah proyek ini.}}

## Bill of Materials

{{WRITE: tabel Komponen, Jumlah, Fungsi, Model atau tipe, Catatan dari spec.platform_specific.iot.sensors dan actuators beserta papan dan catu daya. Untuk model yang belum dipilih tulis "model dipilih kemudian" dan jangan mengarang nomor tipe. Sertakan komponen pendukung (resistor, kabel, breadboard, relay berisolasi bila ada beban).}}

## Pin Mapping

{{WRITE: tabel Pin papan, Fungsi, Terhubung ke, Tegangan logika. Jangan mengklaim nomor pin yang pasti untuk papan tertentu jika tidak yakin; tulis "pin diverifikasi pada pinout resmi papan sebelum merakit" dan beri pin contoh hanya bila lazim. Peringatkan pin khusus (strapping, hanya input, dipakai flash) harus dicek pada dokumentasi papan.}}

## Wiring and Power

{{WRITE: deskripsi teks rangkaian (dan diagram Mermaid sederhana bila membantu), anggaran daya (arus tiap komponen sebagai perkiraan yang harus diukur), dan strategi daya sesuai spec.platform_specific.iot.power (deep sleep untuk baterai). Pisahkan jalur daya rendah dan tinggi.}}

## Communication and Payload

{{WRITE: protokol dan koneksi sesuai spec.platform_specific.iot.protocol dan broker: struktur topik MQTT (contoh nyata memakai slug proyek), format payload JSON dengan contoh, interval kirim, QoS, perilaku saat koneksi putus (buffer atau abaikan), dan penamaan ID perangkat unik.}}

## Firmware Notes

{{WRITE: kerangka firmware dari spec.stack, struktur proyek firmware, pustaka yang dipakai (tulis nama saja, verifikasi keberadaan dan versi), konfigurasi rahasia di berkas lokal yang di-gitignore, OTA bila spec.platform_specific.iot.ota true, dan langkah pemulihan bila firmware gagal.}}

## Simulation and Bench Testing

{{WRITE: rencana uji sesuai spec.platform_specific.iot.simulation: apa yang diuji di simulator (logika, aturan, format payload) dan apa yang wajib di perangkat nyata (kalibrasi sensor, aktuator, daya, jangkauan). Checklist uji bangku yang dapat dicentang, minimal delapan butir.}}

## Failure Behavior

Keputusan mahasiswa (I-15): {{spec:platform_specific.iot.failsafe}}

{{WRITE: jabarkan keputusan di atas menjadi tabel Kondisi, Perilaku aktuator, Notifikasi, Cara menguji untuk: sensor error atau terlepas, Wi-Fi atau broker putus, boot atau listrik kembali (termasuk jeda pelindung), perangkat hang (watchdog), dan konflik perintah manual dengan otomasi. Notifikasi offline sesuai spec.platform_specific.iot.offline_alert. Setiap baris harus dapat diuji di bangku uji.}}

## Safety

{{WRITE: ringkas keselamatan fisik dengan rujukan ke GUARDRAILS.md; jika spec.platform_specific.iot.mains_voltage true, ulangi peringatan tegangan jaringan (220 V) secara tegas dan syaratnya (komponen berisolasi, supervisi dosen atau teknisi, uji dengan beban aman lebih dulu).}}
