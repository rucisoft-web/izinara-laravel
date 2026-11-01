/*
 * Membuat ulang tangkapan layar untuk tombol Bantuan & landing page.
 * Prasyarat: data demo sudah dimuat (php artisan db:seed --class=DemoSeeder) dan aplikasi berjalan.
 *   npm i -D playwright   (atau gunakan Playwright yang sudah terpasang)
 *   BASE=http://127.0.0.1:8000 node tools/screenshot-bantuan.cjs
 * Hasil: public/img/bantuan/*.png
 */
const { chromium } = require('playwright');
const path = require('path');
const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const OUT = path.join(__dirname, '..', 'public', 'img', 'bantuan');

async function login(browser, user) {
  const ctx = await browser.newContext({ viewport: { width: 1366, height: 820 }, locale: 'id-ID' });
  const p = await ctx.newPage();
  await p.goto(BASE + '/login');
  await p.fill('input[name=login]', user);
  await p.fill('input[name=password]', process.env.PASS || 'izinara');
  await p.press('input[name=password]', 'Enter');
  await p.waitForLoadState('networkidle');
  return p;
}

async function go(p, url) {
  await p.goto(BASE + url, { waitUntil: 'networkidle' });
  await p.waitForTimeout(400);
}
const shot = (p, file) => p.screenshot({ path: path.join(OUT, file) }).then(() => console.log('✓', file));
async function card(p, text, file) {
  const c = p.locator('.card', { has: p.locator('h2, h3', { hasText: text }) }).first();
  await c.scrollIntoViewIfNeeded();
  await c.screenshot({ path: path.join(OUT, file) });
  console.log('✓', file);
}
async function hrefOf(p, url, text) {
  await go(p, url);
  return p.locator('a', { hasText: text }).first().getAttribute('href').then(h => h.replace(/^https?:\/\/[^/]+/, ''));
}

(async () => {
  const browser = await chromium.launch();
  const m = await login(browser, process.env.MANAGER || 'sari.manajer');

  const pork = await hrefOf(m, '/proyek', 'PRJ-2026-002');
  const squid = await hrefOf(m, '/proyek', 'PRJ-2026-005');
  const halal = await hrefOf(m, '/proyek', 'PRJ-2026-017');
  const hcTask = await hrefOf(m, squid + '?tab=wbs', 'Pastikan health certificate');
  const rpTask = await hrefOf(m, pork + '?tab=wbs', 'Ajukan RP');

  await go(m, '/dashboard'); await shot(m, 'dashboard.png');
  await go(m, '/proyek'); await shot(m, 'proyek-daftar.png');
  await go(m, '/proyek/baru'); await shot(m, 'proyek-baru.png');
  const src = m.locator('input[name=source]').first().locator('xpath=ancestor::div[contains(@class,"card")][1]');
  await src.scrollIntoViewIfNeeded(); await src.screenshot({ path: path.join(OUT, 'proyek-baru-sumber.png') }); console.log('✓ proyek-baru-sumber.png');

  await go(m, pork); await shot(m, 'proyek-ringkasan.png');
  await go(m, pork + '?tab=wbs'); await shot(m, 'proyek-wbs.png');
  await go(m, pork + '?tab=pic'); await shot(m, 'proyek-pic.png');
  await go(m, pork + '?tab=peraturan'); await shot(m, 'proyek-peraturan.png');
  await go(m, pork + '?tab=gantt'); await m.waitForSelector('.gantt .bar-wrapper', { timeout: 10000 }).catch(() => {}); await m.waitForTimeout(600); await shot(m, 'gantt-proyek.png');
  await go(m, '/gantt'); await m.waitForSelector('.gantt .bar-wrapper', { timeout: 10000 }).catch(() => {}); await m.waitForTimeout(600); await shot(m, 'gantt-portofolio.png');
  await go(m, halal + '?tab=diskusi'); await shot(m, 'diskusi-halal.png');
  await go(m, pork + '?tab=diskusi'); await shot(m, 'diskusi.png');

  await go(m, hcTask); await shot(m, 'kegiatan.png');
  await card(m, 'Dokumen wajib', 'kegiatan-dokumen.png');
  await card(m, 'Lampiran kegiatan', 'lampiran.png');
  await go(m, rpTask); await card(m, 'Referensi peraturan', 'kegiatan-peraturan.png');

  await go(m, '/memo?kotak=semua'); await shot(m, 'memo.png');
  const memo = await hrefOf(m, '/memo?kotak=semua', 'health certificate');
  await go(m, memo); await shot(m, 'memo-detail.png');
  await go(m, '/beban-kerja'); await shot(m, 'beban-kerja.png');
  await go(m, '/proyeksi'); await shot(m, 'proyeksi.png');
  await go(m, '/register-izin'); await shot(m, 'register-izin.png');
  await go(m, '/peraturan'); await shot(m, 'peraturan.png');
  const pt = await hrefOf(m, '/jenis-izin', 'Rekomendasi');
  await go(m, pt); await shot(m, 'jenis-izin.png');
  await go(m, '/kelompok-izin'); await shot(m, 'kelompok-izin.png');
  await go(m, '/template-wbs'); await shot(m, 'template-wbs.png');
  const tpl = (await m.locator('.card-h h3 a').first().getAttribute('href')).replace(/^https?:\/\/[^/]+/, '');
  await go(m, tpl); await shot(m, 'template-wbs-detail.png');
  await go(m, '/item-code'); await shot(m, 'item-code.png');
  await go(m, '/pengguna'); await shot(m, 'pengguna.png');

  const pic = await login(browser, 'budi.purchasing');
  await go(pic, '/kotak-masuk'); await shot(pic, 'kotak-masuk.png');
  const pic2 = await login(browser, 'fajar.izin');
  await go(pic2, '/tugas-saya'); await shot(pic2, 'tugas-saya.png');

  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
