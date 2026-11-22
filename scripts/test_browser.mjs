import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';
const TOKEN = process.env.TEST_TOKEN;
const OUT_DIR = process.env.SCREENSHOT_DIR || path.resolve('test-results');

if (!TOKEN) {
  console.error('TEST_TOKEN env diperlukan');
  process.exit(1);
}

fs.mkdirSync(OUT_DIR, { recursive: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 1366, height: 820 } });
const page = await context.newPage();

// Simulasi 100% OFFLINE: blokir semua request di luar localhost/127.0.0.1
const externalRequests = [];
await page.route('**/*', (route) => {
  const url = route.request().url();
  if (url.startsWith('http://127.0.0.1') || url.startsWith('http://localhost') || url.startsWith('data:') || url.startsWith('about:')) {
    route.continue();
  } else {
    externalRequests.push(url);
    route.abort('internetdisconnected');
  }
});

const failedLocalRequests = [];
page.on('requestfailed', (req) => {
  if (req.url().startsWith('http://127.0.0.1') || req.url().startsWith('http://localhost')) {
    failedLocalRequests.push(`${req.url()} (${req.failure()?.errorText})`);
  }
});

page.on('dialog', async (dialog) => {
  await dialog.accept();
});

function assert(cond, msg) {
  if (!cond) {
    console.error('  [FAIL]', msg);
    process.exit(1);
  }
  console.log('  [PASS]', msg);
}

console.log('[Tes 10] Membuka Editor Day 1 (murni PHP CRUD) di headless Chromium (mode offline)...');
await page.goto(`${BASE_URL}/p/${TOKEN}/editor`, { waitUntil: 'networkidle' });

const cmVisible = await page.locator('.CodeMirror').isVisible();
assert(cmVisible, 'Editor CodeMirror 5 termuat di layar');
assert(externalRequests.length === 0, `Tidak ada request ke internet/CDN (external=${externalRequests.length})`);

// Tab pertama = koneksi.php versi awal berlubang
const konText = await page.evaluate(() => document.querySelector('.CodeMirror').CodeMirror.getValue());
assert(konText.includes('$database = "____"'), 'Tab 1. koneksi.php termuat dengan versi awal berlubang (~14 baris murni PHP)');

// Screenshot 1: Day 1 versi awal (koneksi.php + pesan koneksi ramah di kanan)
await page.screenshot({ path: path.join(OUT_DIR, 'day1-crud-awal.png') });

// Kunci Jawaban koneksi.php -> belum otomatis tersimpan -> Simpan & Jalankan
await page.locator('#btn-key').click();
await page.waitForTimeout(350);
const dirtyText = await page.locator('#status-pill').textContent();
assert(dirtyText.includes('Belum disimpan'), 'Kunci Jawaban memuat ke editor tanpa langsung menyimpan');

await page.locator('#btn-save').click();
await page.waitForTimeout(800);

// Pindah ke tab tampil.php -> Kunci Jawaban -> Simpan & Jalankan
await page.locator('.tab-btn[data-file="tampil.php"]').click();
await page.waitForTimeout(400);
const tampilText = await page.evaluate(() => document.querySelector('.CodeMirror').CodeMirror.getValue());
assert(tampilText.includes('SELECT * FROM ____') && !tampilText.includes('<!DOCTYPE'), 'Tab 2. tampil.php murni PHP singkat tanpa HTML');

await page.locator('#btn-key').click();
await page.waitForTimeout(350);
await page.locator('#btn-save').click();
await page.waitForTimeout(1000);

const frame1 = page.frameLocator('#preview-frame');
const previewBody1 = await frame1.locator('body').textContent();
assert(previewBody1.includes('Database Tersambung') && previewBody1.includes('Makan siang di kantin') && previewBody1.includes('70.000'), 'Preview iframe Day 1 langsung menampilkan tabel CRUD & total Rp 70.000');

// Screenshot 2: Day 1 setelah koneksi.php & tampil.php lengkap
await page.screenshot({ path: path.join(OUT_DIR, 'day1-crud-jalan.png') });

// Reset File tampil.php -> Simpan -> preview kembali ke pesan ramah READ
await page.locator('#btn-reset').click();
await page.waitForTimeout(350);
await page.locator('#btn-save').click();
await page.waitForTimeout(800);
const previewAfterReset = await frame1.locator('body').textContent();
assert(previewAfterReset.includes('Daftar pengeluaran belum bisa ditampilkan (READ)'), 'Reset File mengembalikan tampil.php ke versi awal');

assert(failedLocalRequests.length === 0, `Tidak ada asset lokal yang gagal dimuat (failed=${failedLocalRequests.length})`);

await browser.close();
console.log('[Tes 10] Semua pengecekan browser headless PASS!');
