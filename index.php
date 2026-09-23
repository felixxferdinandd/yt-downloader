<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTubeDown — High Performance Video & Audio Downloader</title>
    <meta name="description" content="Download video & audio YouTube gratis, cepat, tanpa iklan dengan kualitas hingga 4K dan MP3 320kbps. Ditenagai multi-engine yt-dlp.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%23EF4444'/%3E%3Cpolygon points='40,30 40,70 72,50' fill='white'/%3E%3C/svg%3E">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --bg-base: #050507;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-bg-hover: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-border-hover: rgba(255, 255, 255, 0.16);
            --glass-inner-glow: inset 0 1px 0 0 rgba(255, 255, 255, 0.06);
            --primary: #ef4444;
            --primary-glow: rgba(239, 68, 68, 0.25);
            --primary-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Glow & Grid */
        .ambient-glow {
            position: fixed;
            top: -150px;
            left: 50%;
            transform: translateX(-50%);
            width: 800px;
            height: 500px;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.12) 0%, rgba(220, 38, 38, 0.03) 50%, transparent 70%);
            pointer-events: none;
            z-index: 0;
            filter: blur(60px);
        }

        .ambient-grid {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }

        /* Glass Container Cards */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-inner-glow), 0 20px 40px -15px rgba(0, 0, 0, 0.7);
            border-radius: 20px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .glass-card:hover {
            border-color: var(--glass-border-hover);
        }

        /* Typography */
        h1, h2, h3, .brand-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.03em;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .text-gradient {
            background: linear-gradient(180deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .text-gradient-red {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 60%, #b91c1c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Buttons & Controls */
        .btn-primary {
            background: var(--primary-gradient);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 20px var(--primary-glow);
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.35);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-secondary);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-primary);
            border-color: rgba(255, 255, 255, 0.15);
        }

        /* Search Input Bar */
        .search-wrap {
            position: relative;
            display: flex;
            align-items: center;
            background: rgba(10, 10, 15, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 6px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
        }

        .search-wrap:focus-within {
            border-color: rgba(239, 68, 68, 0.5);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4), 0 0 0 3px rgba(239, 68, 68, 0.12);
        }

        .search-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-primary);
            font-size: 0.95rem;
            padding: 10px 14px;
            min-width: 0;
        }

        .search-input::placeholder {
            color: #475569;
        }

        /* Format Switcher Pills */
        .format-toggle {
            display: flex;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 4px;
            gap: 4px;
        }

        .format-btn {
            flex: 1;
            padding: 8px 16px;
            border-radius: 9px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .format-btn.active {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 2px 10px rgba(239, 68, 68, 0.15);
        }

        .format-btn:not(.active):hover {
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.03);
        }

        /* Quality Cards */
        .quality-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 12px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .quality-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.18);
            transform: translateY(-2px);
        }

        .quality-card.active {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.4);
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12);
        }

        .quality-card .q-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .quality-card.active .q-title {
            color: #f87171;
        }

        .quality-card .q-sub {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .quality-card.active .q-sub {
            color: rgba(248, 113, 113, 0.8);
        }

        /* Toast Container */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
            max-width: 380px;
            width: calc(100% - 48px);
        }

        .glass-toast {
            pointer-events: auto;
            background: rgba(15, 15, 22, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(120%);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .glass-toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .glass-toast.success { border-left: 3px solid #22c55e; }
        .glass-toast.error { border-left: 3px solid #ef4444; }
        .glass-toast.info { border-left: 3px solid #38bdf8; }

        /* Modal Backdrop */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 9000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-box {
            background: rgba(14, 14, 20, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8), inset 0 1px 0 rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            width: 100%;
            max-width: 440px;
            padding: 28px;
            transform: scale(0.95) translateY(10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-overlay.active .modal-box {
            transform: scale(1) translateY(0);
        }

        /* Spinner Animation */
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.25);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.65s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Pulse Dot */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #22c55e;
            box-shadow: 0 0 10px #22c55e;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.85); }
        }

        /* History items */
        .history-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }

        .history-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.12);
        }

        /* Utility classes */
        .hidden { display: none !important; }
        .clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Background Decor -->
    <div class="ambient-glow"></div>
    <div class="ambient-grid"></div>

    <!-- Toast Notifications -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Header Navigation -->
    <header style="position: relative; z-index: 10; border-bottom: 1px solid rgba(255, 255, 255, 0.04);">
        <div style="max-width: 900px; margin: 0 auto; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between;">
            <!-- Brand -->
            <a href="#" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; background: var(--primary-gradient); border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px var(--primary-glow);">
                    <i class="fab fa-youtube" style="color: #ffffff; font-size: 16px;"></i>
                </div>
                <div>
                    <span class="brand-title" style="font-size: 1.1rem; font-weight: 700; color: #ffffff;">YouTube<span style="color: #ef4444;">Down</span></span>
                    <div style="display: flex; align-items: center; gap: 5px; margin-top: 1px;">
                        <span class="status-dot"></span>
                        <span class="font-mono" style="font-size: 0.65rem; color: #94a3b8; letter-spacing: 0.02em;">yt-dlp v2.0 &bull; Online</span>
                    </div>
                </div>
            </a>

            <!-- Return Link -->
            <a href="https://portofelix.my.id" target="_blank" style="text-decoration: none; color: var(--text-secondary); font-size: 0.82rem; font-weight: 500; display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 9px; background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); transition: all 0.2s ease;">
                <i class="fas fa-arrow-left" style="font-size: 10px;"></i>
                <span>Portofolio Felix</span>
            </a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main style="position: relative; z-index: 10; flex: 1; max-width: 760px; width: 100%; margin: 0 auto; padding: 48px 20px 64px;">
        
        <!-- Hero Section -->
        <section style="text-align: center; margin-bottom: 40px;">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 100px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); margin-bottom: 18px;">
                <i class="fas fa-bolt" style="color: #ef4444; font-size: 11px;"></i>
                <span class="font-mono" style="font-size: 0.75rem; color: #fca5a5; font-weight: 500;">Multi-Engine Extraction Active</span>
            </div>
            
            <h1 class="text-gradient" style="font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 800; line-height: 1.15; margin-bottom: 14px;">
                Download Video & Audio<br>
                <span class="text-gradient-red">YouTube Tanpa Iklan.</span>
            </h1>
            
            <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; max-width: 520px; margin: 0 auto;">
                Tinggal paste URL, pilih format dan resolusi, simpan langsung tanpa redirect dan iklan spam. Ditenagai oleh native <span class="font-mono" style="color: #f87171;">yt-dlp</span> pipeline.
            </p>
        </section>

        <!-- Search / URL Input Card -->
        <section class="glass-card" style="padding: 24px; margin-bottom: 28px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                <label for="urlInput" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-link" style="color: #ef4444;"></i> Masukkan URL Video / Shorts
                </label>
                <button type="button" id="pasteBtn" onclick="pasteFromClipboard()" class="btn-secondary" style="padding: 4px 10px; font-size: 0.72rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fas fa-clipboard" style="font-size: 10px;"></i> Paste
                </button>
            </div>

            <div class="search-wrap">
                <i class="fab fa-youtube" style="color: #64748b; font-size: 18px; margin-left: 10px;"></i>
                <input type="text" id="urlInput" class="search-input" placeholder="https://www.youtube.com/watch?v=..." autocomplete="off" spellcheck="false">
                <button type="button" id="clearBtn" onclick="clearInput()" class="btn-secondary hidden" style="width: 32px; height: 32px; padding: 0; border-radius: 8px; margin-right: 6px;" title="Hapus">
                    <i class="fas fa-times" style="font-size: 11px;"></i>
                </button>
                <button type="button" id="fetchBtn" onclick="fetchVideo()" class="btn-primary" style="padding: 10px 20px; font-size: 0.88rem;">
                    <i class="fas fa-magnifying-glass"></i>
                    <span>Proses</span>
                </button>
            </div>

            <!-- Inline Error Box -->
            <div id="inlineError" class="hidden" style="margin-top: 14px; padding: 10px 14px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 10px; color: #fca5a5; font-size: 0.8rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-circle-exclamation" style="flex-shrink: 0;"></i>
                <span id="inlineErrorText"></span>
            </div>
        </section>

        <!-- Video Preview Section -->
        <section id="previewCard" class="glass-card hidden" style="padding: 24px; margin-bottom: 28px;">
            <!-- Video Info Row -->
            <div style="display: flex; gap: 18px; margin-bottom: 22px; flex-wrap: wrap;">
                <!-- Thumbnail -->
                <div style="position: relative; width: 220px; height: 125px; border-radius: 14px; overflow: hidden; background: #000; flex-shrink: 0; box-shadow: 0 8px 20px rgba(0,0,0,0.5);">
                    <img id="vThumb" src="" alt="Thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
                    <span id="vDuration" class="font-mono" style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.85); backdrop-filter: blur(4px); color: #fff; font-size: 0.7rem; font-weight: 600; padding: 3px 7px; border-radius: 6px;">0:00</span>
                </div>

                <!-- Metadata -->
                <div style="flex: 1; min-width: 240px; display: flex; flex-direction: column; justify-content: center;">
                    <h2 id="vTitle" class="clamp-2" style="font-size: 1.05rem; font-weight: 700; line-height: 1.4; color: #ffffff; margin-bottom: 8px;"></h2>
                    
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 12px;">
                        <span id="vChannel" style="color: var(--text-secondary); font-size: 0.85rem; font-weight: 500;"></span>
                        <i class="fas fa-circle-check" style="color: #38bdf8; font-size: 11px;" title="Verified"></i>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="copyVideoUrl()" class="btn-secondary" style="padding: 5px 12px; font-size: 0.75rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-share-nodes"></i> Salin Link
                        </button>
                        <a id="vExternalLink" href="#" target="_blank" class="btn-secondary" style="text-decoration: none; padding: 5px 12px; font-size: 0.75rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-arrow-up-right-from-square"></i> Buka Asli
                        </a>
                    </div>
                </div>
            </div>

            <!-- Format Switcher -->
            <div style="margin-bottom: 18px;">
                <div class="format-toggle">
                    <button type="button" id="btnFormatMp4" class="format-btn active" onclick="switchFormat('mp4')">
                        <i class="fas fa-video"></i> Video (MP4)
                    </button>
                    <button type="button" id="btnFormatMp3" class="format-btn" onclick="switchFormat('mp3')">
                        <i class="fas fa-music"></i> Audio (MP3)
                    </button>
                </div>
            </div>

            <!-- Quality Cards Grid: Video -->
            <div id="videoQualityGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 8px; margin-bottom: 22px;"></div>

            <!-- Quality Cards Grid: Audio -->
            <div id="audioQualityGrid" class="hidden" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 22px;"></div>

            <!-- Action Button -->
            <button type="button" id="startDownloadBtn" onclick="triggerDownloadFlow()" class="btn-primary" style="width: 100%; padding: 14px; font-size: 0.95rem; border-radius: 14px;">
                <i class="fas fa-cloud-arrow-down" style="font-size: 1.1rem;"></i>
                <span id="downloadBtnText">Unduh Sekarang (MP4 &bull; 720p)</span>
            </button>
        </section>

        <!-- Feature Highlights (Lightweight & Clean) -->
        <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 36px;">
            <div class="glass-card" style="padding: 18px 20px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <i class="fas fa-gauge-high" style="color: #ef4444; font-size: 13px;"></i>
                </div>
                <h3 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 4px;">Ultra Fast CLI Engine</h3>
                <p style="color: var(--text-muted); font-size: 0.78rem; line-height: 1.5;">Ekstraksi stream video langsung dengan pipeline native yt-dlp cPanel tanpa batasan.</p>
            </div>

            <div class="glass-card" style="padding: 18px 20px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <i class="fas fa-shield-halved" style="color: #22c55e; font-size: 13px;"></i>
                </div>
                <h3 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 4px;">Bebas Iklan & Trap</h3>
                <p style="color: var(--text-muted); font-size: 0.78rem; line-height: 1.5;">Tanpa pop-up iklan crypto/judi atau jebakan redirect ke situs eksternal berbahaya.</p>
            </div>

            <div class="glass-card" style="padding: 18px 20px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(56,189,248,0.1); border: 1px solid rgba(56,189,248,0.2); display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <i class="fas fa-sliders" style="color: #38bdf8; font-size: 13px;"></i>
                </div>
                <h3 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 4px;">Resolusi Lengkap</h3>
                <p style="color: var(--text-muted); font-size: 0.78rem; line-height: 1.5;">Mendukung kualitas asli 4K, 1080p Full HD hingga audio studio MP3 320kbps.</p>
            </div>
        </section>

        <!-- Download History Section -->
        <section class="glass-card" style="padding: 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <h3 style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clock-rotate-left" style="color: #ef4444;"></i> Riwayat Unduhan Terakhir
                </h3>
                <button type="button" onclick="clearAllHistory()" class="btn-secondary" style="padding: 4px 10px; font-size: 0.72rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fas fa-trash-can" style="font-size: 10px;"></i> Bersihkan
                </button>
            </div>

            <div id="historyList" style="display: flex; flex-direction: column; gap: 8px;"></div>
            
            <div id="emptyHistory" style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                <i class="fas fa-inbox" style="font-size: 24px; opacity: 0.4; margin-bottom: 8px; display: block;"></i>
                <span style="font-size: 0.82rem;">Belum ada riwayat unduhan pada browser ini.</span>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer style="position: relative; z-index: 10; border-top: 1px solid rgba(255, 255, 255, 0.04); padding: 24px 20px; text-align: center;">
        <div style="max-width: 900px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <p style="color: var(--text-muted); font-size: 0.78rem;">
                &copy; 2026 YouTubeDown &bull; Engineered by <a href="https://portofelix.my.id" target="_blank" style="color: var(--text-secondary); text-decoration: none; font-weight: 600;">Felix Ferdinand</a>
            </p>
            <div style="display: flex; align-items: center; gap: 14px;">
                <button type="button" onclick="openBugModal()" style="background: none; border: none; color: var(--text-muted); font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 5px;" onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='var(--text-muted)'">
                    <i class="fas fa-bug"></i> Laporkan Masalah
                </button>
                <a href="https://github.com/yt-dlp/yt-dlp" target="_blank" style="color: var(--text-muted); text-decoration: none; font-size: 0.75rem; display: flex; align-items: center; gap: 5px;">
                    <i class="fab fa-github"></i> yt-dlp core
                </a>
            </div>
        </div>
    </footer>

    <!-- Download Progress Modal -->
    <div id="downloadModal" class="modal-overlay">
        <div class="modal-box">
            <div style="text-align: center; margin-bottom: 20px;">
                <div id="modalStatusIcon" style="width: 52px; height: 52px; border-radius: 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px;">
                    <i class="fas fa-spinner fa-spin" style="color: #ef4444; font-size: 20px;"></i>
                </div>
                <h3 id="modalTitle" style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px;">Mempersiapkan Stream</h3>
                <p id="modalSubtitle" class="clamp-2" style="color: var(--text-muted); font-size: 0.82rem; line-height: 1.4;"></p>
            </div>

            <!-- Progress Bar -->
            <div id="modalProgressBarWrap" style="margin-bottom: 22px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px;">
                    <span id="modalProgressText" class="font-mono">Menghubungkan ke server stream...</span>
                    <span id="modalProgressPct" class="font-mono" style="color: #ef4444; font-weight: 600;">25%</span>
                </div>
                <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.06); border-radius: 100px; overflow: hidden;">
                    <div id="modalProgressBar" style="width: 25%; height: 100%; background: var(--primary-gradient); transition: width 0.3s ease; border-radius: 100px;"></div>
                </div>
            </div>

            <!-- Success / Error Status Box -->
            <div id="modalStatusBox" class="hidden" style="margin-bottom: 20px; padding: 12px; border-radius: 12px; font-size: 0.82rem; text-align: center;"></div>

            <!-- Fallback Mirrors Container -->
            <div id="modalFallbackWrap" class="hidden" style="margin-bottom: 20px;">
                <div style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 10px; text-align: left; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-server" style="color: #ef4444;"></i>
                    <span style="font-weight: 600;">Server Mirror Eksternal:</span>
                </div>
                <div id="modalMirrorButtons" style="display: flex; flex-direction: column; gap: 8px;"></div>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeDownloadModal()" class="btn-secondary" style="flex: 1; padding: 12px; font-size: 0.85rem;">
                    Tutup
                </button>
                <a id="modalDirectLink" href="#" download class="btn-primary hidden" style="flex: 2; padding: 12px; font-size: 0.85rem; text-decoration: none;">
                    <i class="fas fa-file-arrow-down"></i> Simpan File
                </a>
            </div>
        </div>
    </div>

    <!-- Bug Report Modal -->
    <div id="bugModal" class="modal-overlay">
        <div class="modal-box">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <h3 style="font-size: 1.05rem; font-weight: 700;">Laporkan Masalah / Bug</h3>
                <button type="button" onclick="closeBugModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 16px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p style="color: var(--text-muted); font-size: 0.82rem; margin-bottom: 14px; line-height: 1.5;">
                Jika video tertentu gagal diunduh atau mengalami kendala, tuliskan detailnya agar kami dapat menyesuaikan resolver engine.
            </p>
            <textarea id="bugText" rows="4" placeholder="Jelaskan URL dan kendala yang dialami..." style="width: 100%; background: rgba(0,0,0,0.4); border: 1px solid var(--glass-border); border-radius: 12px; padding: 12px; color: #fff; font-size: 0.85rem; outline: none; resize: none; margin-bottom: 16px; font-family: inherit;"></textarea>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeBugModal()" class="btn-secondary" style="flex: 1; padding: 10px; font-size: 0.85rem;">Batal</button>
                <button type="button" onclick="submitBugReport()" class="btn-primary" style="flex: 1.5; padding: 10px; font-size: 0.85rem;">
                    <i class="fas fa-paper-plane"></i> Kirim Laporan
                </button>
            </div>
        </div>
    </div>

    <script src="app.js?v=<?= time() ?>"></script>
</body>
</html>
