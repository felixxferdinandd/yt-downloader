# YouTubeDown - High-Performance YouTube Media Downloader

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Python](https://img.shields.io/badge/Python-3.10%2B-3776AB?style=for-the-badge&logo=python&logoColor=white)](https://python.org)
[![Engine](https://img.shields.io/badge/Engine-yt--dlp-red?style=for-the-badge&logo=youtube&logoColor=white)](https://github.com/yt-dlp/yt-dlp)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

A modern, high-performance web application designed for fast YouTube video and audio downloading, featuring an Apple Liquid Dark Glass interface and a hybrid backend architecture (PHP Gateway + Python Local Stream Daemon).

---

## Key Features

- **Apple Liquid Dark Glass UI**: Responsive user interface built with backdrop blur effects, subtle gradients, fluid micro-interactions, full mobile and desktop support, and zero third-party advertisements.
- **Multi-Engine Pipeline**:
  - Powered by native yt-dlp (pure Python standalone zipapp, lightweight without heavy binary compilation overhead).
  - Instant stream extraction utilizing the Android player client to circumvent datacenter IP throttling and bot verification challenges.
- **Comprehensive Video Resolutions**:
  - MP4 format: 1080p Full HD, 720p HD, 480p, and 360p.
- **High-Fidelity Audio Extraction**:
  - MP3 format with configurable bitrates: 320 kbps (Studio Quality), 256 kbps, 192 kbps, and 128 kbps.
- **Direct Binary Streamer**: Downloads are proxied in real time through chunked streaming (`download.php`) directly to the user's browser, preventing server disk storage exhaustion.
- **Shared Hosting Compatibility**: Includes a localized HTTP loopback daemon operating on port 5155 to run seamlessly in constrained environments where `shell_exec` is restricted (such as cPanel / CloudLinux CageFS).

---

## System Architecture

```
[ Client / Web Browser ]
         │   ▲
         │   │   (Info Request / Stream Binary)
         ▼   │
┌────────────────────────────────────────────────────────┐
│  Web Server Frontend & API (PHP 8.x)                   │
│  ├── index.php      : Apple Dark Glass UI              │
│  ├── app.js         : State controller & clipboard     │
│  ├── api.php        : Gateway & API dispatcher         │
│  └── download.php   : High-throughput chunk streamer   │
└────────────────────────────────────────────────────────┘
         │   ▲
         │   │   (Local Loopback HTTP / Port 5155)
         ▼   │
┌────────────────────────────────────────────────────────┐
│  Local Stream Daemon (Python 3.11)                      │
│  ├── daemon.py      : Non-blocking HTTP socket service │
│  └── yt-dlp         : Pure Python zipapp extractor     │
└────────────────────────────────────────────────────────┘
```

---

## Directory Structure

```
yt-downloader/
├── .gitignore         # Ignores logs, caches, and local system environments
├── LICENSE            # Official open-source MIT License
├── README.md          # Comprehensive technical documentation
├── api.php            # API endpoints, URL validation, and stream dispatcher
├── app.js             # Frontend controller, event listeners, and UI state
├── daemon.py          # Python HTTP daemon for yt-dlp stream resolution
├── download.php       # Binary stream proxy for direct file delivery
├── index.php          # Main presentation layer (HTML5 / Dark Glass CSS)
└── yt-dlp             # Standalone Python zipapp yt-dlp engine (~3MB)
```

---

## System Requirements

- Web Server: Apache, Nginx, or LiteSpeed
- PHP: Version 8.0 or higher (with `cURL` and `json` extensions enabled)
- Python: Version 3.10 or 3.11+
- Available Port: Local loopback port `5155` (configurable in `daemon.py` and `api.php`)

---

## Installation and Setup

### 1. Clone the Repository
```bash
git clone https://github.com/felixxferdinandd/yt-downloader.git
cd yt-downloader
```

### 2. Local Development Environment (XAMPP / Standalone PHP)
1. Place the repository inside your web server root (e.g., `C:\xampp\htdocs\yt-downloader` or `/var/www/html/`).
2. Start the Python stream daemon in your terminal:
   ```bash
   python daemon.py
   ```
3. Open your browser and navigate to:
   ```
   http://localhost/yt-downloader/
   ```

### 3. Production Deployment (cPanel / Linux VPS)
1. Upload all project files to your target public directory (e.g., `public_html/`).
2. Open your cPanel Terminal or connect via SSH to the server.
3. Launch the daemon as a detached background service:
   ```bash
   pkill -9 -f daemon.py
   nohup /usr/bin/python3 daemon.py > daemon.log 2>&1 &
   ```
   *(For CloudLinux Alt-Python installations, use: `/opt/alt/python311/bin/python3 daemon.py`)*.
4. Verify daemon health:
   ```bash
   curl -s http://127.0.0.1:5155/status
   ```
   Expected response:
   ```json
   {"status":"online","port":5155,"has_ytdlp_module":true,"python_version":"3.11.x"}
   ```

---

## API Documentation

All API responses are delivered in standard JSON format:

| Method | Endpoint | Parameters | Description |
| :--- | :--- | :--- | :--- |
| GET | `/api.php?action=info` | `url` (required) | Fetches video metadata including title, thumbnail, duration, and available format tracks. |
| GET | `/api.php?action=download` | `url`, `format`, `quality` | Resolves and returns the direct media stream URL from the yt-dlp engine. |
| GET | `/api.php?action=status` | None | Checks connectivity between the PHP API gateway and the Python daemon. |
| GET | `/api.php?action=log` | None | Retrieves recent operational output from the daemon log. |

---

## Daemon Heartbeat (Cron Job Automation)

To guarantee the local daemon remains running indefinitely and restarts automatically after server reboots, add the following entry to your system crontab or cPanel Cron Jobs (recommended interval: every 15 or 30 minutes):

```bash
pgrep -f "daemon.py" > /dev/null || (cd /home/username/public_html && nohup python3 daemon.py > daemon.log 2>&1 &)
```

---

## Author

- **Felix Ferdinand** - Developer & Maintainer  
  GitHub: [@felixxferdinandd](https://github.com/felixxferdinandd)

---

## License

This project is open-source and licensed under the [MIT License](LICENSE).  
You are free to use, modify, and distribute this software for personal, educational, and non-commercial development.

> **Disclaimer**: This tool is developed strictly for educational and lawful personal backup purposes. Users are solely responsible for ensuring compliance with the terms of service and copyright policies of the media platforms accessed.
