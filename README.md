# YouTubeDown - High-Performance YouTube Media Downloader

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Python](https://img.shields.io/badge/Python-3.10%2B-3776AB?style=for-the-badge&logo=python&logoColor=white)](https://python.org)
[![Engine](https://img.shields.io/badge/Engine-yt--dlp-red?style=for-the-badge&logo=youtube&logoColor=white)](https://github.com/yt-dlp/yt-dlp)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

Aplikasi web modern untuk mengunduh video dan audio YouTube secara instan dengan antarmuka Apple Liquid Dark Glass, performa tinggi, dan arsitektur backend ganda (PHP Gateway + Python Local Stream Daemon).

---

## Fitur Utama

- **Apple Liquid Dark Glass UI**: Antarmuka responsif berbasis backdrop blur, aksen gradien halus, optimal di perangkat seluler maupun desktop, serta bebas iklan pop-up pihak ketiga.
- **Multi-Engine Pipeline**:
  - Menggunakan engine native yt-dlp (zipapp murni, ringan tanpa overhead binary berat).
  - Ekstraksi stream instan via client player Android untuk menghindari throttling atau bot detection datacenter.
- **Pilihan Resolusi Video Lengkap**:
  - MP4: 1080p Full HD, 720p HD, 480p, dan 360p.
- **Ekstraksi Audio Berkualitas Tinggi**:
  - MP3 dengan opsi bitrate: 320 kbps (Studio), 256 kbps, 192 kbps, dan 128 kbps.
- **Direct Binary Streamer**: Pengunduhan langsung diteruskan ke browser pengguna secara chunking real-time (download.php) tanpa membebani penyimpanan server.
- **Bypass Limitasi Shared Hosting**: Dilengkapi daemon lokal berbasis socket loopback untuk bekerja mulus pada lingkungan hosting dengan restriksi shell_exec (seperti cPanel / CloudLinux CageFS).

---

## Arsitektur Sistem

```
[ Pengguna / Browser ]
        │  ▲
        │  │  (Permintaan Info / Download Stream)
        ▼  │
┌────────────────────────────────────────────────────────┐
│  Web Server Frontend & API (PHP 8.x)                   │
│  ├── index.php      : UI Apple Dark Glass             │
│  ├── app.js         : State controller & clipboard    │
│  ├── api.php        : Gateway & API dispatcher        │
│  └── download.php   : High-throughput chunk streamer  │
└────────────────────────────────────────────────────────┘
        │  ▲
        │  │  (HTTP Local Loopback / Port 5155)
        ▼  │
┌────────────────────────────────────────────────────────┐
│  Local Stream Daemon (Python 3.11)                     │
│  ├── daemon.py      : Non-blocking HTTP socket service │
│  └── yt-dlp         : Pure Python zipapp extractor    │
└────────────────────────────────────────────────────────┘
```

---

## Struktur Direktori

```
yt-downloader/
├── .gitignore         # Filter file log, cache, dan environment lokal
├── LICENSE            # Lisensi open-source resmi (MIT)
├── README.md          # Dokumentasi teknis lengkap proyek
├── api.php            # Endpoint API, validator URL, dan stream bridge
├── app.js             # Logika frontend, event handler, dan status UI
├── daemon.py          # Python HTTP daemon untuk ekstraksi stream yt-dlp
├── download.php       # Stream proxy untuk direct download file
├── index.php          # Halaman antarmuka utama (HTML5/CSS Dark Glass)
└── yt-dlp             # Python zipapp standalone yt-dlp engine (~3MB)
```

---

## Kebutuhan Sistem

- Web Server: Apache / Nginx / LiteSpeed
- PHP: Versi 8.0 atau lebih baru (ekstensi cURL dan json aktif)
- Python: Versi 3.10 atau 3.11+
- Port Tersedia: Port lokal 5155 (dapat disesuaikan di daemon.py dan api.php)

---

## Panduan Instalasi dan Penggunaan

### 1. Kloning Repositori
```bash
git clone https://github.com/felixxferdinandd/yt-downloader.git
cd yt-downloader
```

### 2. Menjalankan di Lingkungan Lokal (Pengembangan / XAMPP)
1. Letakkan folder proyek di dalam direktori web server (contoh: `C:\xampp\htdocs\yt-downloader` atau `/var/www/html/`).
2. Jalankan Python daemon di terminal:
   ```bash
   python daemon.py
   ```
3. Buka browser dan akses:
   ```
   http://localhost/yt-downloader/
   ```

### 3. Menjalankan di Lingkungan Production (cPanel / Linux VPS)
1. Unggah seluruh file ke direktori domain target (contoh: `public_html/`).
2. Buka Terminal cPanel atau SSH ke VPS.
3. Jalankan daemon di latar belakang (background process):
   ```bash
   pkill -9 -f daemon.py
   nohup /usr/bin/python3 daemon.py > daemon.log 2>&1 &
   ```
   *(Jika menggunakan Python versi spesifik seperti Alt-Python CloudLinux: gunakan path `/opt/alt/python311/bin/python3 daemon.py`)*.
4. Periksa apakah daemon sudah berjalan normal:
   ```bash
   curl -s http://127.0.0.1:5155/status
   ```
   Output respons:
   ```json
   {"status":"online","port":5155,"has_ytdlp_module":true,"python_version":"3.11.x"}
   ```

---

## Dokumentasi Endpoint API

Semua respons dikembalikan dalam format standar JSON:

| Method | Endpoint | Parameter | Deskripsi |
| :--- | :--- | :--- | :--- |
| GET | `/api.php?action=info` | `url` (wajib) | Mengambil metadata video, judul, thumbnail, durasi, dan format stream yang tersedia. |
| GET | `/api.php?action=download` | `url`, `format`, `quality` | Mengambil direct stream URL hasil resolusi engine yt-dlp. |
| GET | `/api.php?action=status` | - | Memeriksa status konektivitas antara PHP API dan Python Daemon. |
| GET | `/api.php?action=log` | - | Membaca output log operasi daemon terbaru. |

---

## Otomasi Heartbeat (Cron Job)

Untuk memastikan daemon selalu aktif tanpa perlu dijalankan ulang secara manual setelah server reboot, tambahkan konfigurasi berikut ke Cron Jobs cPanel (setiap 15 atau 30 menit):

```bash
pgrep -f "daemon.py" > /dev/null || (cd /home/username/public_html && nohup python3 daemon.py > daemon.log 2>&1 &)
```

---

## Author

- **Felix Ferdinand** - Developer & Maintainer  
  GitHub: [@felixxferdinandd](https://github.com/felixxferdinandd)

---

## Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).  
Bebas digunakan, dimodifikasi, dan didistribusikan untuk keperluan pembelajaran dan pengembangan non-komersial.

> **Disclaimer**: Proyek ini dibuat untuk tujuan edukasi dan penggunaan pribadi yang sah. Pengguna bertanggung jawab penuh atas hak cipta konten yang diunduh sesuai dengan ketentuan layanan platform terkait.
