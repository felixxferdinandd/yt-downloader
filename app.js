/**
 * YouTubeDown — Modern Client Controller
 * Apple Liquid Dark Glass UX with Multi-Engine Fallback Pipeline
 * Exclusively engineered for Felix Ferdinand | portofelix.my.id
 */

let currentVideo = null;
let currentFormat = 'mp4';
let currentQuality = 720;
let currentFormatId = '';

document.addEventListener('DOMContentLoaded', () => {
    initInputEvents();
    renderHistory();
});

// ─── TOAST NOTIFICATION SYSTEM (NO BROWSER ALERTS) ───
function showToast(message, type = 'info', title = '') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `glass-toast ${type}`;

    let iconHtml = '<i class="fas fa-circle-info" style="color: #38bdf8; font-size: 16px;"></i>';
    if (type === 'success') {
        iconHtml = '<i class="fas fa-circle-check" style="color: #22c55e; font-size: 16px;"></i>';
    } else if (type === 'error') {
        iconHtml = '<i class="fas fa-circle-exclamation" style="color: #ef4444; font-size: 16px;"></i>';
    }

    toast.innerHTML = `
        ${iconHtml}
        <div style="flex: 1; min-width: 0;">
            ${title ? `<div style="font-weight: 600; font-size: 0.82rem; color: #fff; margin-bottom: 2px;">${title}</div>` : ''}
            <div style="font-size: 0.78rem; color: #cbd5e1; line-height: 1.4;">${escapeHtml(message)}</div>
        </div>
        <button type="button" style="background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;" onclick="this.parentElement.remove()">
            <i class="fas fa-times" style="font-size: 11px;"></i>
        </button>
    `;

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.add('show');
    });

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

// ─── EXTRACT YOUTUBE ID (SUPPORTS ALL FORMATS) ───
function extractVideoId(url) {
    if (!url) return null;
    url = url.trim();
    if (/^[a-zA-Z0-9_-]{11}$/.test(url)) return url;

    const patterns = [
        /(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?(?:.*&)?v=|shorts\/|live\/))([a-zA-Z0-9_-]{11})/i,
        /[?&]v=([a-zA-Z0-9_-]{11})/i
    ];
    for (const p of patterns) {
        const m = url.match(p);
        if (m && m[1]) return m[1];
    }
    return null;
}

// ─── INPUT HELPERS & EVENTS ───
function initInputEvents() {
    const input = document.getElementById('urlInput');
    const clearBtn = document.getElementById('clearBtn');
    const fetchBtn = document.getElementById('fetchBtn');
    const pasteBtn = document.getElementById('pasteBtn');

    if (input) {
        input.addEventListener('input', () => {
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', input.value.trim() === '');
            }
            hideInlineError();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                fetchVideo();
            }
        });
    }

    if (fetchBtn) {
        fetchBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fetchVideo();
        });
    }

    if (pasteBtn) {
        pasteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            pasteFromClipboard();
        });
    }
}

function clearInput() {
    const input = document.getElementById('urlInput');
    if (input) {
        input.value = '';
        input.focus();
    }
    const clearBtn = document.getElementById('clearBtn');
    if (clearBtn) clearBtn.classList.add('hidden');
    hideInlineError();
}

async function pasteFromClipboard() {
    const input = document.getElementById('urlInput');
    if (!input) return;
    input.focus();

    // 1. Try modern async Clipboard API
    if (navigator.clipboard && navigator.clipboard.readText) {
        try {
            const text = await navigator.clipboard.readText();
            if (text && text.trim()) {
                input.value = text.trim();
                const clearBtn = document.getElementById('clearBtn');
                if (clearBtn) clearBtn.classList.remove('hidden');
                hideInlineError();
                showToast('Link berhasil ditempel!', 'success');
                fetchVideo();
                return;
            }
        } catch (err) {
            console.warn('Clipboard readText permission denied/unsupported:', err);
        }
    }

    // 2. Clean fallback if browser clipboard permissions are denied
    input.select();
    showToast('Silakan tekan Ctrl+V atau tahan kolom input & pilih Tempel (Paste).', 'info');
}

function showInlineError(msg) {
    const box = document.getElementById('inlineError');
    const text = document.getElementById('inlineErrorText');
    if (box && text) {
        text.textContent = msg;
        box.classList.remove('hidden');
    }
}

function hideInlineError() {
    const box = document.getElementById('inlineError');
    if (box) box.classList.add('hidden');
}

// ─── FETCH VIDEO METADATA ───
async function fetchVideo() {
    hideInlineError();
    const input = document.getElementById('urlInput');
    const rawVal = input ? input.value.trim() : '';

    if (!rawVal) {
        showInlineError('Tempelkan URL video atau Shorts YouTube terlebih dahulu.');
        showToast('URL YouTube tidak boleh kosong.', 'error');
        return;
    }

    const videoId = extractVideoId(rawVal);
    if (!videoId) {
        showInlineError('Format URL tidak dikenali. Masukkan URL video, Shorts, atau ID video YouTube yang valid.');
        showToast('URL YouTube tidak valid.', 'error');
        return;
    }

    const canonicalUrl = `https://www.youtube.com/watch?v=${videoId}`;
    const fetchBtn = document.getElementById('fetchBtn');
    const origHtml = fetchBtn ? fetchBtn.innerHTML : '';
    if (fetchBtn) {
        fetchBtn.innerHTML = '<span class="spinner"></span> <span>Mencari...</span>';
        fetchBtn.disabled = true;
    }

    try {
        const response = await fetch(`api.php?action=info&url=${encodeURIComponent(canonicalUrl)}`);
        const data = await response.json();

        if (data.error) {
            showInlineError(data.error);
            showToast(data.error, 'error');
            return;
        }

        currentVideo = {
            id: data.id || videoId,
            url: canonicalUrl,
            title: data.title || 'YouTube Video',
            thumbnail: data.thumbnail || `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`,
            channel: data.channel || 'YouTube Creator',
            duration: data.duration || 0,
            videoFormats: data.video_formats || [],
            audioFormats: data.audio_formats || [],
            engine: data.engine || 'native'
        };

        renderVideoPreview();
        showToast('Video berhasil dimuat! Pilih format unduhan.', 'success');

    } catch (err) {
        console.error(err);
        showInlineError('Gagal menghubungi backend API. Periksa koneksi internet Anda.');
        showToast('Gagal memproses data video.', 'error');
    } finally {
        if (fetchBtn) {
            fetchBtn.innerHTML = origHtml;
            fetchBtn.disabled = false;
        }
    }
}

// Alias for backward compatibility
function fetchVideoInfo() {
    return fetchVideo();
}

// ─── RENDER PREVIEW ───
function renderVideoPreview() {
    const card = document.getElementById('previewCard');
    if (!card || !currentVideo) return;

    document.getElementById('vThumb').src = currentVideo.thumbnail;
    document.getElementById('vDuration').textContent = formatDuration(currentVideo.duration);
    document.getElementById('vTitle').textContent = currentVideo.title;
    document.getElementById('vChannel').textContent = currentVideo.channel;
    document.getElementById('vExternalLink').href = currentVideo.url;

    // Render Quality Grids
    renderVideoQualityGrid();
    renderAudioQualityGrid();

    // Default to MP4 720p or highest available
    switchFormat('mp4');

    card.classList.remove('hidden');
    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function renderVideoQualityGrid() {
    const grid = document.getElementById('videoQualityGrid');
    if (!grid) return;

    const availableHeights = currentVideo.videoFormats.map(f => f.height);
    const standardHeights = [
        { h: 2160, label: '4K', sub: 'UHD' },
        { h: 1440, label: '2K', sub: 'QHD' },
        { h: 1080, label: '1080p', sub: 'FHD' },
        { h: 720,  label: '720p',  sub: 'HD' },
        { h: 480,  label: '480p',  sub: 'SD' },
        { h: 360,  label: '360p',  sub: 'Mobile' },
        { h: 240,  label: '240p',  sub: 'Light' },
        { h: 144,  label: '144p',  sub: 'Eco' }
    ];

    let heightsToRender = standardHeights;
    if (availableHeights.length > 0) {
        heightsToRender = standardHeights.filter(sh => availableHeights.includes(sh.h));
        if (heightsToRender.length === 0) heightsToRender = standardHeights.slice(2, 6);
    }

    grid.innerHTML = heightsToRender.map((item, idx) => {
        const isDefault = item.h === 720 || (idx === 0 && !heightsToRender.some(x => x.h === 720));
        return `
            <div class="quality-card ${isDefault ? 'active' : ''}" data-quality="${item.h}" onclick="selectQuality(${item.h}, this)">
                <div class="q-title">${item.label}</div>
                <div class="q-sub">${item.sub}</div>
            </div>
        `;
    }).join('');

    const activeEl = grid.querySelector('.quality-card.active');
    if (activeEl) {
        currentQuality = parseInt(activeEl.dataset.quality);
    }
}

function renderAudioQualityGrid() {
    const grid = document.getElementById('audioQualityGrid');
    if (!grid) return;

    const bitrates = [
        { b: 320, label: '320 kbps', sub: 'Studio HQ' },
        { b: 192, label: '192 kbps', sub: 'Standard' },
        { b: 128, label: '128 kbps', sub: 'Compact' }
    ];

    grid.innerHTML = bitrates.map((item, idx) => `
        <div class="quality-card ${idx === 0 ? 'active' : ''}" data-quality="${item.b}" onclick="selectQuality(${item.b}, this)">
            <div class="q-title">${item.label}</div>
            <div class="q-sub">${item.sub}</div>
        </div>
    `).join('');
}

function switchFormat(format) {
    currentFormat = format;

    const btnMp4 = document.getElementById('btnFormatMp4');
    const btnMp3 = document.getElementById('btnFormatMp3');
    const vGrid = document.getElementById('videoQualityGrid');
    const aGrid = document.getElementById('audioQualityGrid');

    if (format === 'mp4') {
        btnMp4.classList.add('active');
        btnMp3.classList.remove('active');
        vGrid.classList.remove('hidden');
        aGrid.classList.add('hidden');

        const activeCard = vGrid.querySelector('.quality-card.active');
        currentQuality = activeCard ? parseInt(activeCard.dataset.quality) : 720;
    } else {
        btnMp3.classList.add('active');
        btnMp4.classList.remove('active');
        aGrid.classList.remove('hidden');
        vGrid.classList.add('hidden');

        const activeCard = aGrid.querySelector('.quality-card.active');
        currentQuality = activeCard ? parseInt(activeCard.dataset.quality) : 320;
    }

    updateDownloadBtnLabel();
}

function selectQuality(val, element) {
    const parent = element.parentElement;
    parent.querySelectorAll('.quality-card').forEach(c => c.classList.remove('active'));
    element.classList.add('active');
    currentQuality = val;
    updateDownloadBtnLabel();
}

function updateDownloadBtnLabel() {
    const label = document.getElementById('downloadBtnText');
    if (!label) return;

    if (currentFormat === 'mp4') {
        label.textContent = `Unduh Sekarang (MP4 • ${currentQuality}p)`;
    } else {
        label.textContent = `Unduh Sekarang (MP3 • ${currentQuality} kbps)`;
    }
}

// ─── DOWNLOAD FLOW & MODAL ───
function triggerDownloadFlow() {
    if (!currentVideo) {
        showToast('Pilih video terlebih dahulu.', 'error');
        return;
    }

    const modal = document.getElementById('downloadModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const modalProgressBar = document.getElementById('modalProgressBar');
    const modalProgressText = document.getElementById('modalProgressText');
    const modalProgressPct = document.getElementById('modalProgressPct');
    const modalStatusIcon = document.getElementById('modalStatusIcon');
    const modalStatusBox = document.getElementById('modalStatusBox');
    const modalDirectLink = document.getElementById('modalDirectLink');
    const progressBarWrap = document.getElementById('modalProgressBarWrap');
    const fallbackWrap = document.getElementById('modalFallbackWrap');
    const mirrorButtons = document.getElementById('modalMirrorButtons');

    modalTitle.textContent = currentFormat === 'mp4' ? 'Menyiapkan Video' : 'Menyiapkan Audio';
    modalSubtitle.textContent = `${currentVideo.title} (${currentFormat.toUpperCase()} ${currentQuality}${currentFormat === 'mp4' ? 'p' : 'kbps'})`;

    // Reset UI states
    progressBarWrap.classList.remove('hidden');
    modalStatusBox.classList.add('hidden');
    modalDirectLink.classList.add('hidden');
    if (fallbackWrap) fallbackWrap.classList.add('hidden');
    if (mirrorButtons) mirrorButtons.innerHTML = '';

    modalProgressBar.style.width = '20%';
    modalProgressPct.textContent = '20%';
    modalProgressText.textContent = 'Menghubungkan ke stream engine...';
    modalStatusIcon.innerHTML = '<i class="fas fa-spinner fa-spin" style="color: #ef4444; font-size: 20px;"></i>';
    modalStatusIcon.style.background = 'rgba(239, 68, 68, 0.1)';
    modalStatusIcon.style.borderColor = 'rgba(239, 68, 68, 0.2)';

    modal.classList.add('active');

    let progress = 20;
    const interval = setInterval(() => {
        if (progress < 85) {
            progress += 10;
            modalProgressBar.style.width = `${progress}%`;
            modalProgressPct.textContent = `${progress}%`;
            if (progress > 50) modalProgressText.textContent = 'Mengekstrak stream bitrate terbaik...';
        }
    }, 200);

    const targetUrl = `api.php?action=download&url=${encodeURIComponent(currentVideo.url)}&format=${currentFormat}&quality=${currentQuality}&format_id=${currentFormatId}`;

    fetch(targetUrl)
        .then(res => res.json())
        .then(data => {
            clearInterval(interval);

            // CASE 1: Direct Stream Success
            if (data.status === 'success' && data.download_url) {
                modalProgressBar.style.width = '100%';
                modalProgressPct.textContent = '100%';
                modalProgressText.textContent = 'Stream siap!';
                modalStatusIcon.innerHTML = '<i class="fas fa-circle-check" style="color: #22c55e; font-size: 20px;"></i>';
                modalStatusIcon.style.background = 'rgba(34, 197, 94, 0.1)';
                modalStatusIcon.style.borderColor = 'rgba(34, 197, 94, 0.2)';

                modalStatusBox.className = 'glass-card';
                modalStatusBox.style.background = 'rgba(34, 197, 94, 0.1)';
                modalStatusBox.style.borderColor = 'rgba(34, 197, 94, 0.25)';
                modalStatusBox.style.color = '#86efac';
                modalStatusBox.innerHTML = `
                    <div style="font-weight: 600; margin-bottom: 2px;">Stream Berhasil Dihasilkan!</div>
                    <div style="font-size: 0.76rem; color: #bbf7d0;">Download dimulai otomatis. Jika belum berjalan, klik tombol Simpan File di bawah.</div>
                `;
                modalStatusBox.classList.remove('hidden');

                modalDirectLink.href = data.download_url;
                modalDirectLink.download = data.filename || 'download.mp4';
                modalDirectLink.classList.remove('hidden');

                // Trigger browser instant download
                const triggerLink = document.createElement('a');
                triggerLink.href = data.download_url;
                triggerLink.download = data.filename || 'download.mp4';
                document.body.appendChild(triggerLink);
                triggerLink.click();
                setTimeout(() => triggerLink.remove(), 1000);

                saveToHistory({
                    id: currentVideo.id + '_' + Date.now(),
                    title: currentVideo.title,
                    thumbnail: currentVideo.thumbnail,
                    url: currentVideo.url,
                    format: currentFormat,
                    quality: currentQuality,
                    date: new Date().toISOString()
                });

                showToast('Download berhasil dimulai!', 'success');
                return;
            }

            // CASE 2: Fallback Mirrors Available
            if (data.status === 'fallback' && data.mirrors && data.mirrors.length > 0) {
                modalProgressBar.style.width = '100%';
                modalProgressPct.textContent = '100%';
                modalProgressText.textContent = 'Link Download Siap!';
                modalStatusIcon.innerHTML = '<i class="fas fa-circle-check" style="color: #38bdf8; font-size: 20px;"></i>';
                modalStatusIcon.style.background = 'rgba(56, 189, 248, 0.1)';
                modalStatusIcon.style.borderColor = 'rgba(56, 189, 248, 0.2)';

                modalStatusBox.className = 'glass-card';
                modalStatusBox.style.background = 'rgba(56, 189, 248, 0.08)';
                modalStatusBox.style.borderColor = 'rgba(56, 189, 248, 0.2)';
                modalStatusBox.style.color = '#bae6fd';
                modalStatusBox.innerHTML = `
                    <div style="font-weight: 600; margin-bottom: 3px;">Pilih Server Unduhan Berkecepatan Tinggi</div>
                    <div style="font-size: 0.76rem; color: #94a3b8;">Klik salah satu server mirror di bawah untuk download langsung format ${currentFormat.toUpperCase()}.</div>
                `;
                modalStatusBox.classList.remove('hidden');

                if (fallbackWrap && mirrorButtons) {
                    mirrorButtons.innerHTML = data.mirrors.map((m, idx) => `
                        <a href="${m.url}" target="_blank" rel="noopener noreferrer" class="btn-primary" style="text-decoration: none; padding: 12px 16px; font-size: 0.85rem; justify-content: space-between; ${idx === 0 ? 'background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 4px 15px rgba(239,68,68,0.3);' : ''}">
                            <span style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-download"></i>
                                <span>${escapeHtml(m.name)}</span>
                            </span>
                            <i class="fas fa-arrow-up-right-from-square" style="font-size: 11px; opacity: 0.8;"></i>
                        </a>
                    `).join('');
                    fallbackWrap.classList.remove('hidden');
                }

                saveToHistory({
                    id: currentVideo.id + '_' + Date.now(),
                    title: currentVideo.title,
                    thumbnail: currentVideo.thumbnail,
                    url: currentVideo.url,
                    format: currentFormat,
                    quality: currentQuality,
                    date: new Date().toISOString()
                });

                showToast('Link download siap! Pilih server mirror.', 'success');
                return;
            }

            // CASE 3: Error
            modalProgressBar.style.width = '100%';
            modalProgressPct.textContent = 'Failed';
            modalStatusIcon.innerHTML = '<i class="fas fa-triangle-exclamation" style="color: #ef4444; font-size: 20px;"></i>';
            modalStatusBox.className = 'glass-card';
            modalStatusBox.style.background = 'rgba(239, 68, 68, 0.12)';
            modalStatusBox.style.borderColor = 'rgba(239, 68, 68, 0.3)';
            modalStatusBox.style.color = '#fca5a5';
            modalStatusBox.textContent = data.error || 'Gagal menghasilkan link stream unduhan.';
            modalStatusBox.classList.remove('hidden');
            progressBarWrap.classList.add('hidden');
            showToast('Gagal menyiapkan download.', 'error');
        })
        .catch(err => {
            clearInterval(interval);
            console.error(err);
            modalStatusIcon.innerHTML = '<i class="fas fa-triangle-exclamation" style="color: #ef4444; font-size: 20px;"></i>';
            modalStatusBox.className = 'glass-card';
            modalStatusBox.style.background = 'rgba(239, 68, 68, 0.12)';
            modalStatusBox.style.borderColor = 'rgba(239, 68, 68, 0.3)';
            modalStatusBox.style.color = '#fca5a5';
            modalStatusBox.textContent = 'Terjadi gangguan jaringan saat mengambil stream unduhan.';
            modalStatusBox.classList.remove('hidden');
            progressBarWrap.classList.add('hidden');
            showToast('Gagal terhubung ke server unduhan.', 'error');
        });
}

function closeDownloadModal() {
    const modal = document.getElementById('downloadModal');
    if (modal) modal.classList.remove('active');
}

// ─── HISTORY MANAGEMENT ───
function getHistory() {
    try {
        return JSON.parse(localStorage.getItem('ytdown_history') || '[]');
    } catch {
        return [];
    }
}

function saveToHistory(item) {
    const list = getHistory();
    list.unshift(item);
    if (list.length > 25) list.pop();
    localStorage.setItem('ytdown_history', JSON.stringify(list));
    renderHistory();
}

function clearAllHistory() {
    localStorage.removeItem('ytdown_history');
    renderHistory();
    showToast('Riwayat unduhan berhasil dibersihkan.', 'success');
}

function renderHistory() {
    const listEl = document.getElementById('historyList');
    const emptyEl = document.getElementById('emptyHistory');
    if (!listEl || !emptyEl) return;

    const history = getHistory();
    if (history.length === 0) {
        listEl.innerHTML = '';
        emptyEl.classList.remove('hidden');
        return;
    }

    emptyEl.classList.add('hidden');
    listEl.innerHTML = history.map(item => `
        <div class="history-item">
            <img src="${item.thumbnail}" alt="" style="width: 56px; height: 36px; object-fit: cover; border-radius: 8px; flex-shrink: 0;">
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 0.82rem; font-weight: 600; color: #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${escapeHtml(item.title)}
                </div>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 3px;">
                    <span class="font-mono" style="font-size: 0.68rem; padding: 2px 6px; border-radius: 4px; background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.25);">
                        ${item.format.toUpperCase()} ${item.quality}${item.format === 'mp4' ? 'p' : 'k'}
                    </span>
                    <span style="font-size: 0.7rem; color: #64748b;">
                        ${formatDate(item.date)}
                    </span>
                </div>
            </div>
            <a href="${item.url}" target="_blank" class="btn-secondary" style="text-decoration: none; padding: 6px 10px; font-size: 0.72rem; border-radius: 8px;" title="Buka di YouTube">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </div>
    `).join('');
}

// ─── BUG REPORT MODAL ───
function openBugModal() {
    const modal = document.getElementById('bugModal');
    if (modal) modal.classList.add('active');
}

function closeBugModal() {
    const modal = document.getElementById('bugModal');
    if (modal) modal.classList.remove('active');
}

function submitBugReport() {
    const textEl = document.getElementById('bugText');
    const msg = textEl ? textEl.value.trim() : '';

    if (!msg) {
        showToast('Tuliskan kendala yang dialami terlebih dahulu.', 'error');
        return;
    }

    const bugs = JSON.parse(localStorage.getItem('ytdown_bugs') || '[]');
    bugs.unshift({
        message: msg,
        url: currentVideo ? currentVideo.url : 'No video selected',
        timestamp: new Date().toISOString()
    });
    localStorage.setItem('ytdown_bugs', JSON.stringify(bugs));

    textEl.value = '';
    closeBugModal();
    showToast('Laporan kamu tersimpan. Terima kasih telah membantu pengembangan!', 'success');
}

// ─── UTILITIES ───
function copyVideoUrl() {
    if (!currentVideo) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(currentVideo.url).then(() => {
            showToast('Link video berhasil disalin!', 'success');
        });
    } else {
        const dummy = document.createElement('input');
        dummy.value = currentVideo.url;
        document.body.appendChild(dummy);
        dummy.select();
        document.execCommand('copy');
        dummy.remove();
        showToast('Link video berhasil disalin!', 'success');
    }
}

function formatDuration(sec) {
    if (!sec || isNaN(sec)) return '0:00';
    const s = parseInt(sec);
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const remSec = s % 60;
    if (h > 0) {
        return `${h}:${String(m).padStart(2, '0')}:${String(remSec).padStart(2, '0')}`;
    }
    return `${m}:${String(remSec).padStart(2, '0')}`;
}

function formatDate(isoStr) {
    try {
        const d = new Date(isoStr);
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    } catch {
        return '';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ─── GLOBAL EXPORTS FOR HTML ONCLICK COMPATIBILITY ───
window.pasteFromClipboard = pasteFromClipboard;
window.fetchVideo = fetchVideo;
window.fetchVideoInfo = fetchVideo;
window.clearInput = clearInput;
window.switchFormat = switchFormat;
window.setFormat = switchFormat;
window.selectQuality = selectQuality;
window.pickRes = selectQuality;
window.pickAudio = selectQuality;
window.triggerDownloadFlow = triggerDownloadFlow;
window.showDownloadModal = triggerDownloadFlow;
window.closeDownloadModal = closeDownloadModal;
window.copyVideoUrl = copyVideoUrl;
window.clearAllHistory = clearAllHistory;
window.openBugModal = openBugModal;
window.closeBugModal = closeBugModal;
window.submitBugReport = submitBugReport;
