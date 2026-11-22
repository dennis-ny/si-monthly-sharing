<?php

declare(strict_types=1);

$token = $participant['token'];
$label = $participant['label'];
$isLaravel = ($editorMode === 'day2');
$whitelist = get_whitelist($editorMode);
$previewUrl = $isLaravel ? "/p/{$token}/laravel/" : "/p/{$token}/app/index.php";
$titleMode = $isLaravel ? 'Editor Laravel (Day 2)' : 'Editor PHP (Day 1)';
$firstFile = array_key_first($whitelist);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titleMode) ?> — <?= e($label) ?></title>
    <link rel="stylesheet" href="/assets/codemirror/codemirror.min.css">
    <link rel="stylesheet" href="/assets/codemirror/theme/dracula.min.css">
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #09090b; color: #f4f4f5; overflow: hidden; }
        .editor-shell { display: flex; flex-direction: column; height: 100vh; }
        .toolbar {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            padding: 8px 14px; background: #111113; border-bottom: 1px solid #27272a; flex-wrap: wrap;
        }
        .toolbar-left, .toolbar-right { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
        .back-link { color: #d4d4d8; text-decoration: none; font-size: 12.5px; font-weight: 500; padding: 5px 10px; border-radius: 4px; background: #18181b; border: 1px solid #27272a; }
        .back-link:hover { background: #27272a; color: #fff; }
        .title-chip { font-weight: 600; font-size: 13.5px; color: #fafafa; letter-spacing: -0.01em; }
        .status-pill { font-family: ui-monospace, Consolas, monospace; font-size: 11px; padding: 3px 7px; border-radius: 3px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; border: 1px solid transparent; }
        .status-saved { background: #052e16; border-color: #166534; color: #86efac; }
        .status-dirty { background: #451a03; border-color: #92400e; color: #fde68a; }
        .status-readonly { background: #18181b; border-color: #3f3f46; color: #a1a1aa; }
        .tbtn {
            border: 1px solid #3f3f46; border-radius: 4px; padding: 6px 12px; font-size: 12.5px; font-weight: 600;
            cursor: pointer; color: #f4f4f5; background: #18181b; display: inline-flex; align-items: center; gap: 6px;
            text-decoration: none;
        }
        .tbtn:hover:not(:disabled) { background: #27272a; border-color: #52525b; }
        .tbtn:disabled { opacity: 0.4; cursor: not-allowed; }
        .tbtn-save { background: #15803d; border-color: #16a34a; color: #ffffff; }
        .tbtn-save:hover:not(:disabled) { background: #166534; border-color: #22c55e; }
        .tbtn-reset { color: #d4d4d8; }
        .tbtn-open { color: #f4f4f5; }

        .workspace { display: flex; flex: 1; min-height: 0; }
        .pane-explorer {
            width: 225px; background: #09090b; border-right: 1px solid #27272a;
            display: flex; flex-direction: column; flex-shrink: 0; overflow: hidden;
        }
        .pane-explorer.collapsed { display: none; }
        .exp-header {
            padding: 9px 12px; font-family: ui-monospace, Consolas, monospace; font-size: 10.5px; font-weight: 700; letter-spacing: 0.07em;
            text-transform: uppercase; color: #71717a; border-bottom: 1px solid #27272a;
            display: flex; align-items: center; justify-content: space-between;
        }
        .exp-tree { flex: 1; overflow-y: auto; padding: 6px 0 14px; font-size: 12.5px; }
        .exp-folder {
            display: flex; align-items: center; gap: 5px; padding: 4px 10px;
            color: #d4d4d8; font-weight: 600; cursor: pointer; user-select: none;
        }
        .exp-folder:hover { background: #18181b; }
        .exp-folder .chev { font-size: 10px; width: 12px; color: #71717a; display: inline-block; }
        .exp-children { padding-left: 14px; }
        .exp-children.closed { display: none; }
        .exp-file {
            display: flex; align-items: center; justify-content: space-between; gap: 4px;
            width: 100%; background: transparent; border: none; color: #a1a1aa;
            padding: 4px 8px 4px 10px; font-size: 12.5px; text-align: left; cursor: pointer;
            font-family: inherit; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .exp-file:hover { background: #18181b; color: #fafafa; }
        .exp-file.editable { color: #e4e4e7; font-weight: 500; }
        .exp-file.active { background: #18181b; color: #ffffff; border-left: 2px solid #dc2626; padding-left: 8px; }
        .exp-file .fname { overflow: hidden; text-overflow: ellipsis; flex: 1; }
        .exp-tag { font-size: 10px; font-weight: 700; padding: 1px 5px; border-radius: 3px; flex-shrink: 0; }
        .exp-tag-ro { color: #71717a; }
        .exp-note { padding: 3px 10px; font-size: 11px; color: #52525b; font-style: italic; }
        .exp-legend {
            padding: 8px 10px; border-top: 1px solid #27272a; font-size: 11px; color: #71717a; line-height: 1.45;
        }
        .ro-banner {
            display: none; background: #18181b; color: #d4d4d8; border-bottom: 1px solid #27272a;
            padding: 6px 12px; font-size: 12px; align-items: center; justify-content: space-between; gap: 8px;
        }
        .ro-banner.show { display: flex; }

        .pane-left { flex: 1.25; display: flex; flex-direction: column; border-right: 1px solid #27272a; min-width: 280px; }
        .pane-right { flex: 1; display: flex; flex-direction: column; background: #fafaf9; min-width: 260px; }

        .tabs { display: flex; background: #09090b; border-bottom: 1px solid #27272a; overflow-x: auto; }
        .tab-btn {
            background: transparent; color: #71717a; border: none; border-bottom: 2px solid transparent;
            padding: 9px 14px; font-family: ui-monospace, Consolas, monospace; font-size: 12px; font-weight: 500; cursor: pointer; white-space: nowrap;
        }
        .tab-btn:hover { color: #e4e4e7; background: #18181b; }
        .tab-btn.active { color: #fafafa; background: #18181b; border-bottom-color: <?= $isLaravel ? '#dc2626' : '#fafafa' ?>; }

        .cm-wrap { flex: 1; min-height: 0; position: relative; }
        .CodeMirror {
            position: absolute; inset: 0; height: 100% !important;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 13.5px; line-height: 1.55;
        }
        .cm-s-dracula span.cm-comment { color: #6272a4 !important; font-style: italic; }

        .preview-bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 7px 12px; background: #ffffff; border-bottom: 1px solid #e4e4e7; color: #52525b; font-size: 12.5px;
        }
        .preview-frame { flex: 1; width: 100%; border: none; background: #fafaf9; }
        .toast {
            position: fixed; bottom: 16px; left: 16px; padding: 9px 14px; border-radius: 4px;
            background: #18181b; color: #fafafa; border: 1px solid #3f3f46; font-size: 12.5px;
            display: none; z-index: 999;
        }
        @media (max-width: 860px) {
            .workspace { flex-direction: column; }
            .pane-left { width: 100%; height: 55%; border-right: none; border-bottom: 1px solid #27272a; }
            .pane-right { height: 45%; }
        }
    </style>
</head>
<body>
<div class="editor-shell">
    <div class="toolbar">
        <div class="toolbar-left">
            <a class="back-link" href="/p/<?= e($token) ?>/">&larr; Portal</a>
            <button id="btn-toggle-exp" class="back-link" type="button" style="cursor:pointer;" title="Tampilkan/sembunyikan File Explorer">&#128193; Explorer</button>
            <span class="title-chip"><?= e($titleMode) ?> • <?= e($label) ?></span>
            <span id="status-pill" class="status-pill status-saved">Tersimpan</span>
        </div>
        <div class="toolbar-right">
            <button id="btn-save" class="tbtn tbtn-save" type="button" title="Simpan &amp; Jalankan (Ctrl+S)">Simpan &amp; Jalankan (Ctrl+S)</button>
            <button id="btn-reset" class="tbtn tbtn-reset" type="button">Reset File</button>
            <a class="tbtn tbtn-open" href="/p/<?= e($token) ?>/db" target="_blank">Buka Database</a>
            <a class="tbtn tbtn-open" href="<?= e($previewUrl) ?>" target="_blank">Buka di Tab Baru</a>
        </div>
    </div>

    <div class="workspace">
        <?php if ($isLaravel): ?>
        <aside class="pane-explorer collapsed" id="pane-explorer">
            <div class="exp-header">
                <span>Struktur Proyek Laravel</span>
            </div>
            <div class="exp-tree" id="exp-tree">
                <div class="exp-folder" data-toggle><span class="chev">&#9662;</span>&#128193; app</div>
                <div class="exp-children">
                    <div class="exp-folder" data-toggle><span class="chev">&#9662;</span>&#128193; Http / Controllers</div>
                    <div class="exp-children">
                        <button type="button" class="exp-file editable" data-edit-file="app/Http/Controllers/PengeluaranController.php">
                            <span class="fname">&#128196; PengeluaranController.php</span>
                        </button>
                    </div>
                    <div class="exp-folder" data-toggle><span class="chev">&#9662;</span>&#128193; Models</div>
                    <div class="exp-children">
                        <button type="button" class="exp-file editable" data-edit-file="app/Models/Pengeluaran.php">
                            <span class="fname">&#128196; Pengeluaran.php</span>
                        </button>
                    </div>
                </div>

                <div class="exp-folder" data-toggle><span class="chev">&#9656;</span>&#128193; bootstrap</div>
                <div class="exp-children closed">
                    <button type="button" class="exp-file" data-ro-file="bootstrap/app.php">
                        <span class="fname">&#128196; app.php</span>
                        <span class="exp-tag exp-tag-ro">&#128274;</span>
                    </button>
                </div>

                <div class="exp-folder" data-toggle><span class="chev">&#9656;</span>&#128193; config</div>
                <div class="exp-children closed">
                    <button type="button" class="exp-file" data-ro-file="config/database.php">
                        <span class="fname">&#128196; database.php</span>
                        <span class="exp-tag exp-tag-ro">&#128274;</span>
                    </button>
                    <button type="button" class="exp-file" data-ro-file="config/view.php">
                        <span class="fname">&#128196; view.php</span>
                        <span class="exp-tag exp-tag-ro">&#128274;</span>
                    </button>
                </div>

                <div class="exp-folder" data-toggle><span class="chev">&#9662;</span>&#128193; database / migrations</div>
                <div class="exp-children">
                    <button type="button" class="exp-file editable" data-edit-file="database/migrations/create_pengeluaran_table.php">
                        <span class="fname">&#128196; create_pengeluaran_table.php</span>
                    </button>
                </div>

                <div class="exp-folder" data-toggle><span class="chev">&#9656;</span>&#128193; public</div>
                <div class="exp-children closed">
                    <button type="button" class="exp-file" data-ro-file="public/index.php">
                        <span class="fname">&#128196; index.php</span>
                        <span class="exp-tag exp-tag-ro">&#128274;</span>
                    </button>
                </div>

                <div class="exp-folder" data-toggle><span class="chev">&#9662;</span>&#128193; resources / views</div>
                <div class="exp-children">
                    <div class="exp-folder" data-toggle><span class="chev">&#9656;</span>&#128193; layouts</div>
                    <div class="exp-children closed">
                        <button type="button" class="exp-file" data-ro-file="resources/views/layouts/app.blade.php">
                            <span class="fname">&#128196; app.blade.php</span>
                            <span class="exp-tag exp-tag-ro">&#128274;</span>
                        </button>
                    </div>
                    <div class="exp-folder" data-toggle><span class="chev">&#9656;</span>&#128193; partials</div>
                    <div class="exp-children closed">
                        <button type="button" class="exp-file" data-ro-file="resources/views/partials/form.blade.php">
                            <span class="fname">&#128196; form.blade.php</span>
                            <span class="exp-tag exp-tag-ro">&#128274;</span>
                        </button>
                    </div>
                    <div class="exp-folder" data-toggle><span class="chev">&#9662;</span>&#128193; pengeluaran</div>
                    <div class="exp-children">
                        <button type="button" class="exp-file editable" data-edit-file="resources/views/pengeluaran/index.blade.php">
                            <span class="fname">&#128196; index.blade.php</span>
                        </button>
                    </div>
                </div>

                <div class="exp-folder" data-toggle><span class="chev">&#9662;</span>&#128193; routes</div>
                <div class="exp-children">
                    <button type="button" class="exp-file editable" data-edit-file="routes/web.php">
                        <span class="fname">&#128196; web.php</span>
                    </button>
                </div>

                <div class="exp-folder" data-toggle><span class="chev">&#9656;</span>&#128193; storage</div>
                <div class="exp-children closed">
                    <div class="exp-note">cache, logs, session (otomatis)</div>
                </div>

                <div class="exp-folder" data-toggle><span class="chev">&#9656;</span>&#128193; vendor</div>
                <div class="exp-children closed">
                    <div class="exp-note">library Laravel &amp; Composer</div>
                </div>

                <button type="button" class="exp-file" data-ro-file=".env" style="margin-top:4px;">
                    <span class="fname">&#9881;&#65039; .env</span>
                    <span class="exp-tag exp-tag-ro">&#128274;</span>
                </button>
                <button type="button" class="exp-file" data-ro-file="artisan">
                    <span class="fname">&#128196; artisan</span>
                    <span class="exp-tag exp-tag-ro">&#128274;</span>
                </button>
                <button type="button" class="exp-file" data-ro-file="composer.json">
                    <span class="fname">&#128196; composer.json</span>
                    <span class="exp-tag exp-tag-ro">&#128274;</span>
                </button>
            </div>
            <div class="exp-legend">
                &#128196; File latihan (bisa diedit)<br>
                &#128274; File bawaan Laravel (hanya lihat)
            </div>
        </aside>
        <?php else: ?>
        <aside class="pane-explorer collapsed" id="pane-explorer">
            <div class="exp-header">
                <span>Struktur Proyek PHP</span>
            </div>
            <div class="exp-tree" id="exp-tree">
                <div class="exp-folder" data-toggle><span class="chev">&#9662;</span>&#128193; catatan-pengeluaran</div>
                <div class="exp-children">
                    <button type="button" class="exp-file editable" data-edit-file="buat_db.php">
                        <span class="fname">&#128196; buat_db.php</span>
                    </button>
                    <button type="button" class="exp-file editable" data-edit-file="koneksi.php">
                        <span class="fname">&#128196; koneksi.php</span>
                    </button>
                    <button type="button" class="exp-file editable" data-edit-file="tampil.php">
                        <span class="fname">&#128196; tampil.php</span>
                    </button>
                    <button type="button" class="exp-file editable" data-edit-file="tambah.php">
                        <span class="fname">&#128196; tambah.php</span>
                    </button>
                    <button type="button" class="exp-file editable" data-edit-file="ubah.php">
                        <span class="fname">&#128196; ubah.php</span>
                    </button>
                    <button type="button" class="exp-file editable" data-edit-file="hapus.php">
                        <span class="fname">&#128196; hapus.php</span>
                    </button>
                    <button type="button" class="exp-file" data-ro-file="index.php">
                        <span class="fname">&#128196; index.php</span>
                        <span class="exp-tag exp-tag-ro">&#128274;</span>
                    </button>
                    <button type="button" class="exp-file" data-ro-file="style.css">
                        <span class="fname">&#128196; style.css</span>
                        <span class="exp-tag exp-tag-ro">&#128274;</span>
                    </button>
                </div>
            </div>
            <div class="exp-legend">
                &#128196; File latihan (bisa diedit)<br>
                &#128274; File tampilan (hanya lihat)
            </div>
        </aside>
        <?php endif; ?>

        <div class="pane-left">
            <div class="tabs" id="file-tabs">
                <?php foreach ($whitelist as $fileKey => $spec): ?>
                    <button
                        type="button"
                        class="tab-btn<?= $fileKey === $firstFile ? ' active' : '' ?>"
                        data-file="<?= e($fileKey) ?>"
                    ><?= e($spec['label']) ?></button>
                <?php endforeach; ?>
            </div>
            <div id="ro-banner" class="ro-banner">
                <span>&#128274; Sedang melihat file bawaan: <strong id="ro-filename"></strong> (Hanya Baca — tidak bisa diubah)</span>
            </div>
            <div class="cm-wrap">
                <textarea id="code-area"></textarea>
            </div>
        </div>
        <div class="pane-right">
            <div class="preview-bar">
                <span><strong>Preview Hasil</strong> — otomatis segar setelah disimpan</span>
                <button type="button" id="btn-reload-preview" style="border:1px solid #cbd5e1;background:#fff;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:12px;">Muat Ulang Preview</button>
            </div>
            <iframe id="preview-frame" class="preview-frame" src="<?= e($previewUrl) ?>?t=<?= time() ?>"></iframe>
        </div>
    </div>
</div>
<div id="toast" class="toast"></div>

<script src="/assets/codemirror/codemirror.min.js"></script>
<script src="/assets/codemirror/mode/xml/xml.min.js"></script>
<script src="/assets/codemirror/mode/javascript/javascript.min.js"></script>
<script src="/assets/codemirror/mode/css/css.min.js"></script>
<script src="/assets/codemirror/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="/assets/codemirror/mode/clike/clike.min.js"></script>
<script src="/assets/codemirror/mode/php/php.min.js"></script>
<script src="/assets/codemirror/addon/edit/closebrackets.min.js"></script>
<script src="/assets/codemirror/addon/edit/matchbrackets.min.js"></script>
<script>
(function() {
    const API_URL = <?= json_encode("/p/{$token}/api.php") ?>;
    const MODE = <?= json_encode($editorMode) ?>;
    const PREVIEW_BASE = <?= json_encode($previewUrl) ?>;

    let currentFile = <?= json_encode($firstFile) ?>;
    let currentEditable = true;
    let isDirty = false;
    let suppressChange = false;

    const statusPill = document.getElementById('status-pill');
    const btnSave = document.getElementById('btn-save');
    const btnReset = document.getElementById('btn-reset');
    const btnReload = document.getElementById('btn-reload-preview');
    const previewFrame = document.getElementById('preview-frame');
    const toastEl = document.getElementById('toast');

    function showToast(msg) {
        toastEl.textContent = msg;
        toastEl.style.display = 'block';
        clearTimeout(toastEl._timer);
        toastEl._timer = setTimeout(() => { toastEl.style.display = 'none'; }, 2800);
    }

    function updateStatus() {
        if (!currentEditable) {
            statusPill.textContent = 'Hanya Baca';
            statusPill.className = 'status-pill status-readonly';
            btnSave.disabled = true;
            btnReset.disabled = true;
            return;
        }
        btnSave.disabled = false;
        btnReset.disabled = false;
        if (isDirty) {
            statusPill.textContent = 'Belum disimpan *';
            statusPill.className = 'status-pill status-dirty';
        } else {
            statusPill.textContent = 'Tersimpan';
            statusPill.className = 'status-pill status-saved';
        }
    }

    const cm = CodeMirror.fromTextArea(document.getElementById('code-area'), {
        mode: 'application/x-httpd-php',
        theme: 'dracula',
        lineNumbers: true,
        lineWrapping: false,
        indentUnit: 4,
        tabSize: 4,
        indentWithTabs: false,
        autoCloseBrackets: true,
        matchBrackets: true,
        extraKeys: {
            'Tab': function(editor) {
                if (editor.somethingSelected()) {
                    editor.indentSelection('add');
                } else {
                    editor.replaceSelection('    ', 'end');
                }
            },
            'Ctrl-S': function() { saveFile(); },
            'Cmd-S': function() { saveFile(); }
        }
    });

    // Highlight khusus Blade: komentar {{-- ... --}} (termasuk multi-baris) & direktif @foreach/@if/@csrf
    const bladeDirectiveOverlay = {
        token: function(stream) {
            if (stream.match(/^@(extends|section|endsection|yield|include|if|elseif|else|endif|foreach|endforeach|empty|endempty|csrf|method)\b/)) {
                return 'keyword';
            }
            stream.next();
            return null;
        }
    };
    let bladeOverlayActive = false;
    let bladeCommentMarks = [];

    function markBladeComments() {
        bladeCommentMarks.forEach(m => m.clear());
        bladeCommentMarks = [];
        if (!bladeOverlayActive) return;
        const text = cm.getValue();
        const re = /\{\{--[\s\S]*?--\}\}/g;
        let match;
        while ((match = re.exec(text)) !== null) {
            const from = cm.posFromIndex(match.index);
            const to = cm.posFromIndex(match.index + match[0].length);
            bladeCommentMarks.push(cm.markText(from, to, { className: 'cm-comment' }));
        }
    }

    function updateBladeHighlight(fileName) {
        const isBlade = typeof fileName === 'string' && fileName.endsWith('.blade.php');
        if (isBlade && !bladeOverlayActive) {
            cm.addOverlay(bladeDirectiveOverlay);
            bladeOverlayActive = true;
        } else if (!isBlade && bladeOverlayActive) {
            cm.removeOverlay(bladeDirectiveOverlay);
            bladeOverlayActive = false;
            bladeCommentMarks.forEach(m => m.clear());
            bladeCommentMarks = [];
        }
    }

    cm.on('change', function() {
        if (bladeOverlayActive) {
            markBladeComments();
        }
        if (suppressChange) return;
        if (currentEditable && !isDirty) {
            isDirty = true;
            updateStatus();
        }
    });

    function refreshPreview() {
        previewFrame.src = PREVIEW_BASE + '?t=' + Date.now();
    }

    const roBanner = document.getElementById('ro-banner');
    const roFilename = document.getElementById('ro-filename');

    function syncExplorerActive(fileName, isRo) {
        document.querySelectorAll('.exp-file').forEach(el => {
            const match = isRo ? (el.dataset.roFile === fileName) : (el.dataset.editFile === fileName);
            el.classList.toggle('active', match);
        });
    }

    async function loadFile(fileName) {
        if (isDirty && !confirm('Ada perubahan yang belum disimpan di ' + currentFile + '. Yakin pindah file tanpa menyimpan?')) {
            return;
        }
        const url = API_URL + '?mode=' + encodeURIComponent(MODE) + '&aksi=baca&file=' + encodeURIComponent(fileName);
        const res = await fetch(url, { cache: 'no-store' });
        const data = await res.json();
        if (!data.ok) {
            showToast('Gagal membuka file: ' + (data.error || 'unknown'));
            return;
        }
        currentFile = fileName;
        currentEditable = !!data.editable;
        suppressChange = true;
        updateBladeHighlight(fileName);
        cm.setValue(data.isi);
        cm.setOption('readOnly', !currentEditable);
        cm.clearHistory();
        suppressChange = false;
        isDirty = false;
        updateStatus();
        if (roBanner) roBanner.classList.remove('show');

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.file === fileName);
        });
        syncExplorerActive(fileName, false);
    }

    async function loadReadOnlySystemFile(fileName) {
        if (isDirty && !confirm('Ada perubahan yang belum disimpan di ' + currentFile + '. Yakin melihat file lain tanpa menyimpan?')) {
            return;
        }
        const url = API_URL + '?mode=' + encodeURIComponent(MODE) + '&aksi=intip&file=' + encodeURIComponent(fileName);
        const res = await fetch(url, { cache: 'no-store' });
        const data = await res.json();
        if (!data.ok) {
            showToast('Gagal melihat file: ' + (data.error || 'unknown'));
            return;
        }
        currentFile = fileName;
        currentEditable = false;
        suppressChange = true;
        updateBladeHighlight(fileName);
        cm.setValue(data.isi);
        cm.setOption('readOnly', true);
        cm.clearHistory();
        suppressChange = false;
        isDirty = false;
        updateStatus();
        if (roBanner && roFilename) {
            roFilename.textContent = fileName;
            roBanner.classList.add('show');
        }
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        syncExplorerActive(fileName, true);
    }

    const expTree = document.getElementById('exp-tree');
    if (expTree) {
        expTree.addEventListener('click', function(ev) {
            const folder = ev.target.closest('.exp-folder[data-toggle]');
            if (folder) {
                const kids = folder.nextElementSibling;
                const chev = folder.querySelector('.chev');
                if (kids && kids.classList.contains('exp-children')) {
                    const closed = kids.classList.toggle('closed');
                    if (chev) chev.innerHTML = closed ? '&#9656;' : '&#9662;';
                }
                return;
            }
            const editBtn = ev.target.closest('.exp-file[data-edit-file]');
            if (editBtn) {
                loadFile(editBtn.dataset.editFile);
                return;
            }
            const roBtn = ev.target.closest('.exp-file[data-ro-file]');
            if (roBtn) {
                loadReadOnlySystemFile(roBtn.dataset.roFile);
            }
        });
    }

    const btnToggleExp = document.getElementById('btn-toggle-exp');
    const paneExplorer = document.getElementById('pane-explorer');
    if (btnToggleExp && paneExplorer) {
        btnToggleExp.addEventListener('click', function() {
            paneExplorer.classList.toggle('collapsed');
            setTimeout(() => cm.refresh(), 50);
        });
    }

    async function saveFile() {
        if (!currentEditable) return;
        btnSave.disabled = true;
        const url = API_URL + '?mode=' + encodeURIComponent(MODE) + '&aksi=simpan&file=' + encodeURIComponent(currentFile);
        const body = new URLSearchParams();
        body.set('isi', cm.getValue());
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            const data = await res.json();
            if (!data.ok) {
                showToast('Gagal menyimpan: ' + (data.error || 'unknown'));
                btnSave.disabled = false;
                return;
            }
            isDirty = false;
            updateStatus();
            showToast('Tersimpan (' + data.saved_at + ') — Preview diperbarui!');
            refreshPreview();
        } catch (err) {
            showToast('Koneksi ke server terputus. Coba klik Simpan lagi.');
            btnSave.disabled = false;
        }
    }

    async function loadTemplate(aksi, labelAksi) {
        if (!currentEditable) return;
        if (!confirm('Muat ' + labelAksi + ' untuk ' + currentFile + ' ke editor? (Belum otomatis tersimpan — klik Simpan & Jalankan setelah melihatnya.)')) {
            return;
        }
        const url = API_URL + '?mode=' + encodeURIComponent(MODE) + '&aksi=' + encodeURIComponent(aksi) + '&file=' + encodeURIComponent(currentFile);
        const res = await fetch(url, { cache: 'no-store' });
        const data = await res.json();
        if (!data.ok) {
            showToast('Gagal memuat: ' + (data.error || 'unknown'));
            return;
        }
        suppressChange = true;
        updateBladeHighlight(currentFile);
        cm.setValue(data.isi);
        suppressChange = false;
        isDirty = true;
        updateStatus();
        showToast(labelAksi + ' dimuat ke editor. Klik "Simpan & Jalankan" untuk menerapkannya.');
    }

    document.getElementById('file-tabs').addEventListener('click', function(ev) {
        const btn = ev.target.closest('.tab-btn');
        if (!btn || btn.dataset.file === currentFile) return;
        loadFile(btn.dataset.file);
    });

    btnSave.addEventListener('click', saveFile);
    btnReset.addEventListener('click', () => loadTemplate('awal', 'Versi Awal (Reset)'));
    btnReload.addEventListener('click', refreshPreview);

    window.addEventListener('keydown', function(ev) {
        if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 's') {
            ev.preventDefault();
            saveFile();
        }
    });

    window.addEventListener('beforeunload', function(ev) {
        if (isDirty) {
            ev.preventDefault();
            ev.returnValue = '';
        }
    });

    loadFile(currentFile);
})();
</script>
</body>
</html>
