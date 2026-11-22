<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catatan Pengeluaran (Laravel)</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #fafaf9; color: #18181b; margin: 0; padding: 24px 16px; line-height: 1.5; -webkit-font-smoothing: antialiased; }
        .container { max-width: 740px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e4e4e7; border-radius: 6px; padding: 24px; margin-bottom: 18px; }
        .badge { display: inline-block; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-weight: 600; font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase; padding: 2px 7px; border-radius: 3px; margin-bottom: 8px; }
        h1 { margin: 0 0 4px; font-size: 24px; font-weight: 700; letter-spacing: -0.02em; color: #18181b; }
        .subtitle { margin: 0 0 18px; color: #71717a; font-size: 13.5px; }
        h2 { margin: 0 0 12px; font-size: 15px; font-weight: 700; }
        .form-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px; }
        @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }
        label { display: block; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #52525b; margin-bottom: 5px; }
        input { width: 100%; padding: 9px 11px; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 14px; font-family: inherit; color: #18181b; }
        input:focus { outline: none; border-color: #b91c1c; }
        .btn { display: inline-flex; align-items: center; justify-content: center; margin-top: 14px; padding: 9px 16px; background: #b91c1c; color: #fff; border: 1px solid #b91c1c; border-radius: 4px; font-family: inherit; font-size: 13.5px; line-height: 1.2; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #991b1b; }
        .btn-cancel { background: #fff; color: #18181b; border-color: #d4d4d8; margin-left: 6px; }
        .btn-cancel:hover { background: #f4f4f5; }
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        th, td { text-align: left; padding: 9px 10px; border-bottom: 1px solid #e4e4e7; }
        th { background: #f4f4f5; color: #52525b; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; border-top: 1px solid #e4e4e7; }
        .text-right { text-align: right; font-variant-numeric: tabular-nums; }
        .total-row td { font-weight: 700; background: #f4f4f5; color: #18181b; border-top: 1.5px solid #18181b; border-bottom: none; }
        .row-actions { display: flex; gap: 6px; justify-content: flex-end; align-items: center; }
        .row-actions form { display: inline-flex; margin: 0; }
        .btn-sm { display: inline-flex; align-items: center; justify-content: center; height: 28px; padding: 0 10px; font-family: inherit; font-size: 12px; line-height: 1; border-radius: 3px; border: 1px solid #d4d4d8; background: #fff; cursor: pointer; text-decoration: none; font-weight: 600; color: #18181b; white-space: nowrap; }
        .btn-edit:hover { background: #f4f4f5; border-color: #18181b; }
        .btn-del { color: #b91c1c; border-color: #fecaca; }
        .btn-del:hover { background: #fef2f2; border-color: #b91c1c; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 3px solid #15803d; border-radius: 4px; padding: 10px 13px; color: #166534; margin-bottom: 16px; font-weight: 600; font-size: 13.5px; }
        .empty-state { text-align: center; padding: 24px; color: #71717a; }
    </style>
</head>
<body>
<div class="container">
    @yield('content')
</div>
</body>
</html>
