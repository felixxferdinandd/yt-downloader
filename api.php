<?php
/**
 * YouTubeDown — High-Performance Multi-Engine API
 * Exclusively engineered for Felix Ferdinand | ytdown.portofelix.my.id
 * Multi-Engine Architecture: Native CLI Pipeline (yt-dlp) + Cobalt/Proxy Stream Fallbacks
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? '';
switch ($action) {
    case 'info':
        handleInfo();
        break;
    case 'download':
        handleDownload();
        break;
    case 'status':
        $daemon = queryDaemon('status');
        echo json_encode([
            'daemon_online' => ($daemon && ($daemon['status'] ?? '') === 'online'),
            'daemon_response' => $daemon,
            'port' => 5155
        ]);
        exit;
    case 'log':
        header('Content-Type: text/plain');
        echo @file_get_contents(__DIR__ . '/daemon.log') ?: 'Empty log';
        exit;
    case 'pyinfo':
        $candidates = [
            '/usr/bin/python',
            '/usr/bin/python3',
            '/usr/bin/python3.6',
            '/usr/bin/python3.8',
            '/usr/bin/python3.9',
            '/usr/bin/python3.10',
            '/usr/bin/python3.11',
            '/usr/local/bin/python3',
            '/usr/local/bin/python3.8',
            '/usr/local/bin/python3.9',
            '/usr/local/bin/python3.10',
            '/usr/local/bin/python3.11',
        ];
        $alt = glob('/opt/alt/python*/bin/python3*') ?: [];
        $cpanel = glob('/usr/local/cpanel/3rdparty/bin/python*') ?: [];
        $all = array_merge($candidates, $alt, $cpanel);
        $found = [];
        foreach ($all as $p) {
            if (@file_exists($p)) {
                $found[] = $p;
            }
        }
        $p311Bins = glob('/opt/alt/python311/bin/*') ?: [];
        $altPips = glob('/opt/alt/*/bin/pip*') ?: [];
        header('Content-Type: application/json');
        echo json_encode([
            'python311_bins' => $p311Bins,
            'alt_pips' => $altPips,
        ]);
        exit;
    default:
        echo json_encode(['error' => 'Invalid action requested.']);
        exit;
}

// ─── EXTRACT YOUTUBE ID ───
function extractId($url) {
    $url = trim($url);
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return $url;
    }
    if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?(?:.*&)?v=|shorts\/|live\/))([a-zA-Z0-9_-]{11})/i', $url, $m)) {
        return $m[1];
    }
    if (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/i', $url, $m)) {
        return $m[1];
    }
    return null;
}

// ─── CHECK SHELL_EXEC AVAILABILITY ───
function isShellExecAvailable() {
    if (!function_exists('shell_exec')) return false;
    $disabled = ini_get('disable_functions');
    if ($disabled) {
        $arr = array_map('trim', explode(',', $disabled));
        if (in_array('shell_exec', $arr)) return false;
    }
    return true;
}

// ─── DETECT NATIVE CLI ENGINE ───
function getYtDlpCommand() {
    static $detectedCmd = null;
    if ($detectedCmd !== null) return $detectedCmd;

    if (!isShellExecAvailable()) {
        $detectedCmd = false;
        return false;
    }

    $candidates = [
        'yt-dlp',
        'python3 -m yt_dlp',
        'python -m yt_dlp',
        __DIR__ . '/bin/yt-dlp',
        '/usr/local/bin/yt-dlp',
        '/usr/bin/yt-dlp',
    ];

    $user = function_exists('get_current_user') ? get_current_user() : '';
    if ($user) {
        $candidates[] = "/home/{$user}/bin/yt-dlp";
        $candidates[] = "/home/{$user}/.local/bin/yt-dlp";
    }

    foreach ($candidates as $cmd) {
        $test = @shell_exec("{$cmd} --version 2>&1");
        if ($test && preg_match('/^\d{4}\.\d{2}\.\d{2}/m', trim($test))) {
            $detectedCmd = $cmd;
            return $detectedCmd;
        }
    }

    $detectedCmd = false;
    return false;
}

// ─── HTTP GET HELPER ───
function httpGet($url, $headers = []) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $reqHeaders = array_merge([
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ], $headers);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $reqHeaders,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response !== false && $code >= 200 && $code < 400) {
            return $response;
        }
    }

    if (function_exists('file_get_contents')) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0\r\nAccept: application/json\r\n",
                'timeout' => 15,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $response = @file_get_contents($url, false, $ctx);
        if ($response !== false) return $response;
    }

    return null;
}

// ─── HTTP POST HELPER ───
function httpPost($url, $data, $headers = []) {
    $payload = json_encode($data);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $reqHeaders = array_merge([
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ], $headers);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $reqHeaders,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response !== false && $code >= 200 && $code < 400) {
            return $response;
        }
    }

    return null;
}

// ─── QUERY LOCAL PYTHON DAEMON (cPanel Terminal) ───
function queryDaemon($endpoint, $params = []) {
    $queryString = http_build_query($params);
    $url = "http://127.0.0.1:5155/{$endpoint}" . ($queryString ? "?{$queryString}" : '');

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => ($endpoint === 'status') ? 2 : 25,
            CURLOPT_NOSIGNAL => 1,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response && $code === 200) {
            $json = json_decode($response, true);
            if ($json) return $json;
        }
    }
    return null;
}

// ─── HANDLE INFO REQUEST ───
function handleInfo() {
    $url = trim($_GET['url'] ?? '');
    if (empty($url)) {
        echo json_encode(['error' => 'URL YouTube tidak boleh kosong.']);
        exit;
    }

    $videoId = extractId($url);
    if (!$videoId) {
        echo json_encode(['error' => 'Format URL YouTube tidak valid. Gunakan link video atau shorts resmi.']);
        exit;
    }

    $canonicalUrl = "https://www.youtube.com/watch?v={$videoId}";

    // Engine 1: Native CLI extraction
    $cliCmd = getYtDlpCommand();
    if ($cliCmd) {
        $exec = "{$cliCmd} --dump-single-json --no-playlist --skip-download " . escapeshellarg($canonicalUrl) . " 2>&1";
        $jsonOut = @shell_exec($exec);
        if ($jsonOut) {
            $data = json_decode($jsonOut, true);
            if ($data && isset($data['title'])) {
                $duration = intval($data['duration'] ?? 0);
                $title = $data['title'];
                $channel = $data['uploader'] ?? $data['channel'] ?? 'YouTube Creator';
                $thumb = $data['thumbnail'] ?? "https://i.ytimg.com/vi/{$videoId}/maxresdefault.jpg";

                $videoFormats = [];
                $audioFormats = [];
                $seenHeights = [];

                if (!empty($data['formats'])) {
                    foreach ($data['formats'] as $f) {
                        $h = intval($f['height'] ?? 0);
                        $vcodec = $f['vcodec'] ?? 'none';
                        $acodec = $f['acodec'] ?? 'none';

                        if ($h > 0 && $vcodec !== 'none' && !isset($seenHeights[$h])) {
                            $seenHeights[$h] = true;
                            $label = $h >= 2160 ? '2160p (4K UHD)' :
                                    ($h >= 1440 ? '1440p (2K QHD)' :
                                    ($h >= 1080 ? '1080p (Full HD)' :
                                    ($h >= 720  ? '720p (HD)' :
                                    ($h >= 480  ? '480p (SD)' : "{$h}p"))));

                            $videoFormats[] = [
                                'height' => $h,
                                'quality' => $label,
                                'filesize' => $f['filesize'] ?? $f['filesize_approx'] ?? null,
                                'id' => $f['format_id'] ?? '',
                            ];
                        }
                    }
                    usort($videoFormats, function($a, $b) {
                        return $b['height'] - $a['height'];
                    });
                }

                if (empty($videoFormats)) {
                    $videoFormats = [
                        ['height' => 1080, 'quality' => '1080p (Full HD)'],
                        ['height' => 720, 'quality' => '720p (HD)'],
                        ['height' => 480, 'quality' => '480p (SD)'],
                        ['height' => 360, 'quality' => '360p (Mobile)'],
                    ];
                }

                $audioFormats = [
                    ['bitrate' => 320, 'quality' => '320 kbps (Studio High)'],
                    ['bitrate' => 192, 'quality' => '192 kbps (Standard HQ)'],
                    ['bitrate' => 128, 'quality' => '128 kbps (Compact)'],
                ];

                echo json_encode([
                    'id' => $videoId,
                    'title' => $title,
                    'thumbnail' => $thumb,
                    'channel' => $channel,
                    'duration' => $duration,
                    'engine' => 'cli',
                    'video_formats' => $videoFormats,
                    'audio_formats' => $audioFormats,
                ]);
                exit;
            }
        }
    }

    // Engine 2: YouTube oEmbed Fallback
    $oembedUrl = "https://www.youtube.com/oembed?url=" . urlencode($canonicalUrl) . "&format=json";
    $oembed = httpGet($oembedUrl);

    if (!$oembed) {
        echo json_encode(['error' => 'Tidak dapat menghubungi server YouTube. Periksa koneksi atau URL video.']);
        exit;
    }

    $info = json_decode($oembed, true);
    if (!$info || !isset($info['title'])) {
        echo json_encode(['error' => 'Video YouTube tidak ditemukan atau disetel privat.']);
        exit;
    }

    $thumbUrl = $info['thumbnail_url'] ?? "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";

    echo json_encode([
        'id' => $videoId,
        'title' => $info['title'],
        'thumbnail' => $thumbUrl,
        'channel' => $info['author_name'] ?? 'YouTube Creator',
        'duration' => 0,
        'engine' => 'oembed',
        'video_formats' => [
            ['height' => 2160, 'quality' => '2160p (4K UHD)'],
            ['height' => 1440, 'quality' => '1440p (2K QHD)'],
            ['height' => 1080, 'quality' => '1080p (Full HD)'],
            ['height' => 720,  'quality' => '720p (HD)'],
            ['height' => 480,  'quality' => '480p (SD)'],
            ['height' => 360,  'quality' => '360p (Mobile)'],
            ['height' => 240,  'quality' => '240p (Light)'],
            ['height' => 144,  'quality' => '144p (Eco)'],
        ],
        'audio_formats' => [
            ['bitrate' => 320, 'quality' => '320 kbps (Studio High)'],
            ['bitrate' => 192, 'quality' => '192 kbps (Standard HQ)'],
            ['bitrate' => 128, 'quality' => '128 kbps (Compact)'],
        ],
    ]);
}

// ─── HANDLE DOWNLOAD REQUEST ───
function handleDownload() {
    $url = trim($_GET['url'] ?? '');
    $format = strtolower(trim($_GET['format'] ?? 'mp4'));
    $quality = intval($_GET['quality'] ?? 720);

    if (empty($url)) {
        echo json_encode(['error' => 'Parameter URL kosong.']);
        exit;
    }

    $videoId = extractId($url);
    if (!$videoId) {
        echo json_encode(['error' => 'URL YouTube tidak valid.']);
        exit;
    }

    $canonicalUrl = "https://www.youtube.com/watch?v={$videoId}";

    // Retrieve video title for clean filename
    $oembedUrl = "https://www.youtube.com/oembed?url=" . urlencode($canonicalUrl) . "&format=json";
    $oembedData = json_decode(httpGet($oembedUrl) ?: '{}', true);
    $title = $oembedData['title'] ?? "YouTubeDown_{$videoId}";

    $safeTitle = preg_replace('/[<>:"\/\\\\|?*]/', '', $title);
    $safeTitle = preg_replace('/\s+/', ' ', trim($safeTitle));
    $safeTitle = mb_substr($safeTitle, 0, 70, 'UTF-8') ?: "video_{$videoId}";
    $ext = ($format === 'mp3') ? 'mp3' : 'mp4';
    $finalFilename = "{$safeTitle}.{$ext}";

    // ─── METHOD 1: Native CLI (yt-dlp) ───
    $cliCmd = getYtDlpCommand();
    if ($cliCmd) {
        if ($format === 'mp3') {
            $formatSelector = 'ba/b';
        } else {
            $formatSelector = "best[height<={$quality}][ext=mp4]/best[height<={$quality}]/bestvideo[height<={$quality}]+bestaudio/best";
        }

        $exec = "{$cliCmd} -g --no-playlist -f " . escapeshellarg($formatSelector) . " " . escapeshellarg($canonicalUrl) . " 2>&1";
        $out = @shell_exec($exec);

        if ($out) {
            $lines = array_filter(array_map('trim', explode("\n", $out)));
            $streamUrl = null;
            foreach ($lines as $line) {
                if (preg_match('/^https?:\/\//i', $line)) {
                    $streamUrl = $line;
                    break;
                }
            }

            if ($streamUrl) {
                $proxyUrl = "download.php?u=" . urlencode(base64_encode($streamUrl)) . "&f=" . urlencode(base64_encode($finalFilename));
                echo json_encode([
                    'status' => 'success',
                    'download_url' => $proxyUrl,
                    'direct_url' => $streamUrl,
                    'filename' => $finalFilename,
                    'engine' => 'cli',
                ]);
                exit;
            }
        }
    }

    // ─── METHOD 1B: Local Python Daemon (cPanel Terminal) ───
    $daemonRes = queryDaemon('download', [
        'url' => $canonicalUrl,
        'format' => $format,
        'quality' => $quality,
    ]);
    if ($daemonRes && !empty($daemonRes['stream_url'])) {
        $streamUrl = $daemonRes['stream_url'];
        $proxyUrl = "download.php?u=" . urlencode(base64_encode($streamUrl)) . "&f=" . urlencode(base64_encode($finalFilename));
        echo json_encode([
            'status' => 'success',
            'download_url' => $proxyUrl,
            'direct_url' => $streamUrl,
            'filename' => $finalFilename,
            'engine' => 'daemon',
        ]);
        exit;
    }

    // If daemon stream extraction is offline, return high-speed prefilled download mirrors immediately
    $encodedCanon = urlencode($canonicalUrl);
    $mirrors = [
        [
            'name' => 'Server Mirror 1 (Y2Down Direct HD)',
            'url' => "https://y2down.cc/en/?url={$encodedCanon}",
            'desc' => 'Download instan MP4 / MP3 kualitas tinggi'
        ],
        [
            'name' => 'Server Mirror 2 (SaveFrom Direct)',
            'url' => "https://en.savefrom.net/248/#url={$encodedCanon}",
            'desc' => 'Alternatif unduhan cepat tanpa antri'
        ],
        [
            'name' => 'Server Mirror 3 (SSYouTube Stream)',
            'url' => "https://ssyoutube.com/watch?v={$videoId}",
            'desc' => 'Unduhan web langsung'
        ]
    ];

    echo json_encode([
        'status' => 'fallback',
        'error' => 'Server stream engine lokal cPanel sedang standby.',
        'daemon_debug' => $daemonRes,
        'video_id' => $videoId,
        'title' => $finalFilename,
        'mirrors' => $mirrors,
        'terminal_command' => 'nohup /opt/alt/python311/bin/python3 daemon.py > daemon.log 2>&1 &'
    ]);
}
