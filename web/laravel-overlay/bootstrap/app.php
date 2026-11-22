<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$routesFile = $_SERVER['PARTICIPANT_ROUTES_FILE'] ?? (__DIR__ . '/../routes/web.php');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: $routesFile,
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, Request $request) {
            $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $file = basename($e->getFile());
            $line = (int) $e->getLine();

            // Sederhanakan pesan umum agar ramah pemula
            $tip = 'Periksa kembali baris yang masih ada tanda <code>____</code>, tanda kutip, koma, atau titik koma (<code>;</code>). Jika bingung, cek komentar petunjuk di atas baris yang kosong atau minta bantuan panitia.';
            if (str_contains($e->getMessage(), '____')) {
                $tip = 'Sepertinya masih ada lubang <code>____</code> yang belum kamu isi! Cek petunjuk komentar di atas baris yang kosong.';
            } elseif (str_contains($e->getMessage(), 'does not exist') || str_contains($e->getMessage(), 'not found')) {
                $tip = 'Nama class, method, atau route belum cocok. Pastikan ejaan huruf besar/kecil sama persis dengan petunjuk.';
            } elseif (str_contains($e->getMessage(), 'Unknown column') || str_contains($e->getMessage(), 'SQLSTATE')) {
                $tip = 'Ada kesalahan pada nama tabel atau kolom database. Cek kembali petunjuk nama kolom: <code>id</code>, <code>keterangan</code>, <code>nominal</code>, <code>tanggal</code>.';
            } elseif (str_contains($e->getMessage(), 'Undefined variable')) {
                $tip = 'Variabel yang dipakai di Blade belum sesuai. Cek nama variabel di Controller (<code>compact(\'data\')</code>) dan di <code>@foreach</code>.';
            }

            $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masih Ada yang Perlu Dilengkapi (Laravel)</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #fafaf9; color: #18181b; margin: 0; padding: 24px 16px; line-height: 1.55; -webkit-font-smoothing: antialiased; }
  .box { max-width: 680px; margin: 20px auto; background: #fff; border: 1px solid #e4e4e7; border-radius: 6px; padding: 24px; }
  .badge { display: inline-block; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-weight: 600; font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase; padding: 2px 7px; border-radius: 3px; margin-bottom: 10px; }
  h1 { margin: 0 0 12px; font-size: 20px; font-weight: 700; letter-spacing: -0.02em; color: #18181b; }
  .tip { background: #fff7ed; border: 1px dashed #fdba74; color: #9a3412; padding: 12px 14px; border-radius: 4px; margin: 14px 0; font-size: 13.5px; line-height: 1.55; }
  .detail-label { margin: 14px 0 6px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #71717a; }
  .detail { background: #18181b; color: #f4f4f5; padding: 12px 14px; border-radius: 4px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12.5px; overflow-x: auto; line-height: 1.5; }
  code { background: #f4f4f5; border: 1px solid #e4e4e7; color: #18181b; padding: 1px 5px; border-radius: 3px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.9em; }
</style>
</head>
<body>
  <div class="box">
    <span class="badge">Laravel • Pesan Bantuan</span>
    <h1>Ups! Kode Laravel masih perlu dilengkapi atau diperbaiki</h1>
    <div class="tip">{$tip}</div>
    <div class="detail-label">Pesan Teknis</div>
    <div class="detail">{$msg}<br><span style="color:#a1a1aa;">File: {$file} (baris {$line})</span></div>
  </div>
</body>
</html>
HTML;

            return response($html, 500)->header('Content-Type', 'text/html; charset=UTF-8');
        });
    })->create();
