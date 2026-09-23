<?php
error_reporting(0);
ini_set('display_errors', 0);

$u = isset($_GET['u']) ? $_GET['u'] : '';
$f = isset($_GET['f']) ? $_GET['f'] : 'download.mp4';

if (empty($u)) { http_response_code(400); exit('No URL'); }

$url = base64_decode($u);
if (empty($url) || strpos($url, 'http') !== 0) { http_response_code(400); exit('Invalid URL'); }

$filename = base64_decode($f);
$filename = preg_replace('/[<>:"/\\\\|?*]/', '_', $filename);
if (empty($filename)) $filename = 'download.mp4';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache');
header_remove('Accept-Ranges');

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 600,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_BUFFERSIZE => 65536,
    CURLOPT_HEADER => false,
    CURLOPT_HTTPHEADER => [
        'Referer: https://www.youtube.com/',
        'Origin: https://www.youtube.com',
    ],
]);
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($c, $chunk) {
    echo $chunk;
    if (ob_get_level()) ob_flush();
    flush();
    return strlen($chunk);
});
curl_exec($ch);
curl_close($ch);
