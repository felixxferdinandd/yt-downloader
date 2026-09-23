#!/usr/bin/env python3
"""
YouTubeDown — Local Stream Daemon for cPanel Terminal
Listens on 127.0.0.1:5050 to bypass PHP shell_exec restrictions seamlessly.
Usage in cPanel Terminal:
    nohup python3 daemon.py > /dev/null 2>&1 &
"""

from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
import os
import sys
import subprocess
import json
import shutil

PORT = 5155

# Ensure local zipapp 'yt-dlp' is available in sys.path
cur_dir = os.path.dirname(os.path.abspath(__file__))
zip_candidate = os.path.join(cur_dir, 'yt-dlp')
if os.path.isfile(zip_candidate) and zip_candidate not in sys.path:
    sys.path.insert(0, zip_candidate)

try:
    import yt_dlp
except ImportError:
    yt_dlp = None

def get_ytdlp_bin():
    # 1. Check if local yt-dlp zipapp can be run with current python3.11
    cur_dir = os.path.dirname(os.path.abspath(__file__))
    zip_app = os.path.join(cur_dir, 'yt-dlp')
    if os.path.isfile(zip_app):
        return [sys.executable, zip_app]

    # 2. Check current python interpreter module
    try:
        res = subprocess.run([sys.executable, '-m', 'yt_dlp', '--version'], stdout=subprocess.PIPE, stderr=subprocess.PIPE, universal_newlines=True, timeout=5)
        if res.returncode == 0:
            return [sys.executable, '-m', 'yt_dlp']
    except Exception:
        pass

    # 3. Check candidate bins in PATH or user directories
    home = os.path.expanduser('~')
    candidates = [
        os.path.join(home, '.local', 'bin', 'yt-dlp'),
        '/opt/alt/python311/bin/yt-dlp',
        os.path.join(home, 'bin', 'yt-dlp'),
        '/usr/local/bin/yt-dlp',
        '/usr/bin/yt-dlp',
    ]
    for c in candidates:
        if os.path.isabs(c):
            if os.path.isfile(c) and os.access(c, os.X_OK):
                return [c]
        else:
            p = shutil.which(c)
            if p:
                return [p]

    return None

class YtDownHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        global yt_dlp
        parsed = urlparse(self.path)
        qs = parse_qs(parsed.query)

        # Dynamic check if yt-dlp was added
        if yt_dlp is None and os.path.isfile(zip_candidate):
            if zip_candidate not in sys.path:
                sys.path.insert(0, zip_candidate)
            try:
                import yt_dlp as loaded_module
                yt_dlp = loaded_module
            except Exception:
                pass

        if parsed.path == '/status':
            self.send_json(200, {
                'status': 'online',
                'port': PORT,
                'has_ytdlp_module': yt_dlp is not None,
                'python_version': sys.version
            })
            return

        if parsed.path == '/shutdown':
            self.send_json(200, {'status': 'shutting_down'})
            import threading
            threading.Thread(target=lambda: os._exit(0)).start()
            return

        if parsed.path == '/download':
            url = qs.get('url', [''])[0]
            fmt = qs.get('format', ['mp4'])[0].lower()
            quality = qs.get('quality', ['720'])[0]

            if not url:
                self.send_json(400, {'error': 'URL parameter missing'})
                return

            if fmt == 'mp3':
                format_sel = 'ba/bestaudio/b/best'
            else:
                format_sel = f'b[height<={quality}]/best[height<={quality}]/b/best'

            # ─── METHOD 1: Native Python yt_dlp Library (No Subprocess, No OOM Kill) ───
            if yt_dlp is not None:
                try:
                    ydl_opts = {
                        'format': format_sel,
                        'quiet': True,
                        'no_warnings': True,
                        'skip_download': True,
                        'extract_flat': False,
                        'extractor_args': {'youtube': {'player_client': ['android']}},
                        'nocheckcertificate': True,
                    }
                    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                        info = ydl.extract_info(url, download=False)
                        stream_url = info.get('url')
                        if not stream_url and 'requested_formats' in info:
                            for rf in info['requested_formats']:
                                if rf.get('url'):
                                    stream_url = rf.get('url')
                                    break
                        if not stream_url and 'formats' in info:
                            for f in reversed(info['formats']):
                                if f.get('url'):
                                    stream_url = f.get('url')
                                    break

                        if stream_url:
                            self.send_json(200, {
                                'status': 'success',
                                'stream_url': stream_url,
                                'format': fmt,
                                'quality': quality,
                                'method': 'python_module'
                            })
                            return
                except Exception as e:
                    try:
                        with open('daemon.log', 'a') as f_log:
                            f_log.write(f"\n[MODULE ERROR] {str(e)}\n")
                    except Exception:
                        pass

            # ─── METHOD 2: Subprocess Fallback ───
            cmd_base = get_ytdlp_bin()
            if not cmd_base:
                self.send_json(500, {'error': 'yt-dlp not found in Python environment'})
                return

            full_cmd = cmd_base + [
                '-g',
                '--no-playlist',
                '--no-warnings',
                '--no-check-certificates',
                '--extractor-args', 'youtube:player_client=android',
                '-f', format_sel,
                url
            ]
            try:
                proc = subprocess.run(full_cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, universal_newlines=True, timeout=30)
                out = proc.stdout.strip()
                err = proc.stderr.strip()
                try:
                    with open('daemon.log', 'a') as f_log:
                        f_log.write(f"\n[RUN] {' '.join(full_cmd)}\n[RC] {proc.returncode}\n[OUT] {out[:300]}\n[ERR] {err[:300]}\n")
                except Exception:
                    pass

                lines = [l.strip() for l in out.splitlines() if l.strip().startswith('http')]
                if lines:
                    self.send_json(200, {
                        'status': 'success',
                        'stream_url': lines[0],
                        'format': fmt,
                        'quality': quality,
                        'method': 'subprocess'
                    })
                    return
                else:
                    self.send_json(502, {
                        'error': 'Failed to extract stream URL from yt-dlp',
                        'returncode': proc.returncode,
                        'stderr': err[:300],
                        'stdout': out[:300]
                    })
            except Exception as e:
                self.send_json(500, {'error': str(e)})
            return

        self.send_json(404, {'error': 'Not found'})

    def send_json(self, code, data):
        payload = json.dumps(data).encode('utf-8')
        self.send_response(code)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Content-Length', str(len(payload)))
        self.end_headers()
        self.wfile.write(payload)

    def log_message(self, format, *args):
        pass

if __name__ == '__main__':
    try:
        sys.stdout.reconfigure(line_buffering=True)
        sys.stderr.reconfigure(line_buffering=True)
    except Exception:
        pass

    HTTPServer.allow_reuse_address = True
    try:
        server = HTTPServer(('127.0.0.1', PORT), YtDownHandler)
        print(f"YouTubeDown Daemon active on 127.0.0.1:{PORT}", flush=True)
        server.serve_forever()
    except Exception as e:
        print(f"FATAL ERROR ON PORT {PORT}: {e}", flush=True)
