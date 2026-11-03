# Bank Pertanyaan — IoT

Muat setelah C-02 = IoT. Versi dan nama teknologi: `tech-baseline.md` bagian `[iot]`. Pertanyaan keselamatan fisik (I-10) **tidak boleh dilewati**.

Untuk IoT, bidang `stack` dipakai begini: `frontend` = dasbor/aplikasi pengguna, `backend` = broker + layanan pemroses, `database` = penyimpanan data deret waktu, `hosting` = tempat broker/dasbor berjalan, `testing` = strategi uji firmware.


> **Stack bebas:** pertanyaan bertanda **[STACK]** memilih teknologi. Jika `stack.source` = `custom` atau `mixed` (C-16), untuk lapisan yang sudah ditentukan mahasiswa jangan tampilkan opsinya; konfirmasi isiannya dan tetap ajukan bagian "Tambahkan:". Opsi di bawah adalah **rekomendasi, bukan batasan**; jawaban teks bebas berupa teknologi lain selalu diterima.

### I-01 ★ Tujuan sistem
- A. **Monitoring** — membaca sensor dan menampilkan/menyimpan data.
- B. **Kontrol** — menyalakan/mematikan atau mengatur perangkat dari jauh.
- C. **Otomasi** — aturan otomatis (mis. jika suhu > batas, nyalakan kipas).
- D. **Edge AI** — pengenalan (gambar/suara/pola) di perangkat atau dekat perangkat.
Boleh gabungan. Tanyakan satu kalimat: "Apa keputusan yang diambil pengguna dari data/kontrol ini?"
→ spec: `problem.*`, `features[]`

### I-02 [STACK] ★ Papan mikrokontroler
- A. **ESP32 (klasik/DevKit)** — Wi-Fi dan Bluetooth, murah, paling banyak contoh. **(Rekomendasi pemula)**
- B. **ESP32-S3** — lebih kuat untuk kamera/AI ringan dan USB.
- C. **ESP32-C6/H2** — mendukung Wi-Fi 6, Thread/Zigbee, dan Matter; pilih bila targetnya smart home interoperabel.
- D. Raspberry Pi (Linux) sebagai pusat/gateway atau untuk visi komputer berat.
- E. Arduino tanpa jaringan (perlu modul tambahan) — hanya bila sudah memiliki papannya.
- F. Lainnya (sebutkan).
→ spec: `platform_specific.iot.board`

### I-03 ★ Sensor dan aktuator
Minta daftar komponen konkret: sensor (suhu/kelembapan, jarak, gerak, gas, kamera, dsb.) dan aktuator (relay, motor, LED, buzzer, layar). Untuk setiap komponen tanyakan model/tipe bila sudah ada. Jangan mengarang model; jika belum tahu, tulis kategori dan tandai "model dipilih kemudian".
→ spec: `platform_specific.iot.sensors`, `platform_specific.iot.actuators`

### I-04 [STACK] ★ Kerangka kerja firmware
- A. **PlatformIO + Arduino framework (C++)** — mudah, banyak pustaka, dikelola di VS Code. **(Rekomendasi pemula)**
- B. ESP-IDF — kendali penuh dan fitur terbaru Espressif; kurva belajar lebih curam.
- C. **ESPHome (YAML)** — tanpa menulis banyak kode, terintegrasi Home Assistant; fleksibilitas lebih terbatas.
- D. MicroPython/CircuitPython — cepat dicoba; performa dan memori lebih terbatas.
→ spec: `stack.backend` (catat firmware di `stack.extra`), `stack.keywords`

### I-05 [STACK] ★ Konektivitas dan protokol
- A. **Wi-Fi + MQTT dengan TLS** — standar de facto untuk telemetri dan perintah. **(Rekomendasi pemula)**
- B. Wi-Fi + HTTP/WebSocket — sederhana, kurang efisien untuk banyak perangkat.
- C. BLE — dekat ke ponsel, hemat daya.
- D. LoRa — jarak jauh, data kecil, butuh gateway.
- E. Thread/Matter — interoperabel dengan ekosistem smart home.
→ spec: `platform_specific.iot.connectivity`, `platform_specific.iot.protocol`

### I-06 [STACK] Broker, penyimpanan, dan dasbor
Broker: **Mosquitto** (self-host) / EMQX / HiveMQ Cloud / bawaan Home Assistant. Dasbor: Home Assistant / Node-RED / Grafana / web atau mobile buatan sendiri. Data deret waktu: InfluxDB / PostgreSQL (TimescaleDB) / Supabase. Rekomendasi pemula: Mosquitto + Node-RED atau dasbor web sederhana. Tambahkan: nada visual dan warna utama dasbor (atau Z).
→ spec: `platform_specific.iot.broker`, `stack.frontend`, `stack.database`, `stack.hosting`

### I-07 Sumber daya listrik
- A. Adaptor/USB dari listrik (selalu menyala).
- B. **Baterai + deep sleep** — perlu anggaran arus dan interval kirim yang jarang.
- C. Panel surya + baterai.
→ spec: `platform_specific.iot.power`

### I-08 Pembaruan firmware (OTA)
- A. Tidak; flash lewat kabel. **(Rekomendasi pemula)**
- B. Ya, OTA lewat jaringan — penting bila perangkat sulit dijangkau; firmware harus bertanda tangan dan ada jalur pemulihan.
→ spec: `platform_specific.iot.ota`

### I-09 Simulasi dan perangkat
- A. Sudah memiliki perangkat fisik.
- B. Belum; mulai dengan simulasi (Wokwi) untuk logika, perangkat fisik menyusul. Ingatkan bahwa perilaku sensor/aktuator nyata tetap perlu diuji di perangkat.
→ spec: `platform_specific.iot.simulation`

### I-10 ★ Keselamatan fisik
- A. Hanya tegangan rendah (≤ 5–12 V, USB/baterai).
- B. **Mengendalikan beban tegangan jaringan (220 V)** — wajib relay/SSR berisolasi, kabel dan casing yang layak, serta supervisi dosen/teknisi. Beri peringatan tegas dan catat di `GUARDRAILS.md`.
- C. Beban lain berisiko (motor besar, pemanas, cairan, tabung gas).
→ spec: `platform_specific.iot.mains_voltage` (true untuk B), `risks[]`

### I-15 ★ Perilaku saat gagal (wajib, tidak boleh dilewati)
Jelaskan bahwa perangkat pasti akan mengalami: sensor rusak/terlepas, Wi-Fi atau broker putus, listrik padam lalu menyala lagi (boot ulang), dan perangkat hang. Tanyakan:
1. **Keadaan aktuator saat sensor error**:
   - A. Matikan aktuator (aman bagi beban, tetapi proses berhenti).
   - B. Pertahankan keadaan terakhir.
   - C. Jalankan pola cadangan (mis. nyala 10 menit, mati 20 menit) sampai sensor pulih.
2. **Saat Wi-Fi/broker putus**: otomasi tetap berjalan di perangkat (disarankan) atau berhenti?
3. **Saat boot/listrik kembali**: aktuator mulai dalam keadaan apa, dan apakah ada jeda pelindung (mis. kompresor/motor)?
4. **Perintah manual vs otomasi**: bila keduanya bertentangan (mis. jeda pelindung belum habis), mana yang menang dan apa yang ditampilkan ke pengguna?
5. **Notifikasi offline**: apakah pengguna diberi tahu bila perangkat tidak mengirim data selama waktu tertentu? (butuh pemantau di luar perangkat, mis. Last Will MQTT atau pemeriksaan di server)
Jika mahasiswa memilih Z, usulkan pilihan paling aman untuk bebannya, minta persetujuan, dan catat di `meta.assistant_proposed`.
→ spec: `platform_specific.iot.failsafe` (ringkasan satu paragraf jawaban 1–4), `platform_specific.iot.offline_alert` (boolean)

### I-11 Keamanan perangkat
Jelaskan lalu minta persetujuan: kredensial Wi-Fi dan broker tidak ditulis di kode yang di-commit (pakai file konfigurasi lokal `secrets.h`/`.env` yang di-`.gitignore`); TLS untuk MQTT; kredensial berbeda per perangkat; jaringan IoT dipisah dari jaringan utama bila memungkinkan; untuk produksi aktifkan secure boot dan flash encryption.
→ spec: `security.*`

### I-12 AI di tepi (edge AI)
- A. Tidak ada.
- B. **Di perangkat** (TinyML/model ringan) — latensi rendah dan privat; terbatas memori.
- C. **Di awan/server** — perangkat mengirim data, server menyimpulkan; butuh koneksi dan biaya.
→ spec: `platform_specific.iot.edge_ai`, `ai_in_app.*`

### I-13 [STACK] Pengujian
- A. Unit test logika di komputer (`pio test -e native`) + simulasi Wokwi + checklist uji perangkat nyata. **(Rekomendasi pemula)**
- B. Hanya uji manual di perangkat (wajib dilengkapi checklist tertulis).
→ spec: `stack.testing`

### I-14 ★ Kata kunci stack
Berlaku juga untuk stack pilihan mahasiswa: ambil kata kunci dari teknologi yang ia sebut.
Kamu merumuskan 3–6 kata kunci, mis. `ESP32`, `PlatformIO`, `MQTT`, `Mosquitto`, `Node-RED`, dan konfirmasikan.
→ spec: `stack.keywords`
