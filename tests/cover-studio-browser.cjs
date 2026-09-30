// Standalone real-browser canvas verification. No WordPress requests or DB writes.
// Optional KN_COVER_BASELINE=/path/to/previous/cover-studio.js checks legacy pixels.
const { chromium } = require('../local/browser-tools/node_modules/playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'theme/assets/notebook/cover-studio.js'), 'utf8');
const php = fs.readFileSync(path.join(root, 'theme/includes/notebook/cover-studio.php'), 'utf8');
const names = ['orbit', 'pages', 'path', 'botanical', 'weave', 'horizon', 'windows', 'constellation', 'folded', 'terraces', 'mosaic', 'signal', 'sundial'];
const palettes = ['forest', 'sage', 'night'];
const legacy = names.slice(0, 7);
const output = path.join(root, 'reports/cover-studio');
fs.mkdirSync(output, { recursive: true });

function fixture() {
  return `<form id="knt-cover-form"><select id="knt-cover-post"><option value="42" data-title="Making space for a good idea">Fixture</option></select>
    <input id="knt-cover-title"><input id="knt-cover-tags">
    <select id="knt-cover-palette">${palettes.map(x => `<option>${x}</option>`).join('')}</select>
    <select id="knt-cover-motif">${names.map(x => `<option>${x}</option>`).join('')}</select>
    <button type="button" id="knt-cover-variation">Variation</button><button id="knt-cover-save">Save</button><p id="knt-cover-status"></p></form>
    <canvas id="knt-cover-canvas" width="1200" height="900"></canvas>`;
}

(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROME_BIN || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
  const errors = [];
  const page = await browser.newPage({ viewport: { width: 1200, height: 900 } });
  page.on('pageerror', error => errors.push(error.message));
  async function boot(script) {
    await page.setContent(fixture());
    await page.evaluate(() => { window.kntCoverStudio = { siteName: 'Kamal Notebook', endpoint: '/fixture-only', nonce: 'fixture' }; });
    await page.addScriptTag({ content: script });
  }
  async function render(motif, palette, seed = 13741) {
    return page.evaluate(({ motif, palette, seed }) => {
      const post = document.querySelector('#knt-cover-post');
      post.selectedOptions[0].dataset.recipe = JSON.stringify({ title: 'Making space for a good idea', tags: 'practice, curiosity', motif, palette, seed, version: 1 });
      post.dispatchEvent(new Event('change', { bubbles: true }));
      const canvas = document.querySelector('canvas');
      const region = document.createElement('canvas'); region.width = 646; region.height = 900;
      region.getContext('2d').drawImage(canvas, 0, 0);
      return { png: canvas.toDataURL(), safe: region.toDataURL() };
    }, { motif, palette, seed });
  }
  try {
    const baselines = {};
    if (process.env.KN_COVER_BASELINE) {
      await boot(fs.readFileSync(process.env.KN_COVER_BASELINE, 'utf8'));
      for (const palette of palettes) for (const name of legacy) baselines[`${name}-${palette}`] = (await render(name, palette)).png;
    }
    await boot(source);
    const gallery = [];
    for (const palette of palettes) {
      let safe;
      const unique = new Set();
      for (const name of names) {
        assert.ok(php.includes(`value="${name}"`), `${name} must be selectable`);
        assert.ok(php.includes(`'${name}'`), `${name} must be allowed in saved recipes`);
        const first = await render(name, palette);
        await render('path', 'night', 82323);
        const restored = await render(name, palette);
        assert.equal(restored.png, first.png, `${name}/${palette}: restoring a recipe after another design must reproduce every pixel`);
        assert.notEqual((await render(name, palette, 92571)).png, first.png, `${name}/${palette}: seed must produce a variation`);
        if (names.indexOf(name) >= 7) {
          safe ||= first.safe;
          assert.equal(first.safe, safe, `${name}/${palette}: illustration must not overlap the title panel`);
        }
        if (baselines[`${name}-${palette}`]) assert.equal(first.png, baselines[`${name}-${palette}`], `${name}/${palette}: legacy recipe pixels must stay unchanged`);
        unique.add(first.png);
        gallery.push({ name, palette, png: first.png });
      }
      assert.equal(unique.size, names.length, `${palette}: every motif must produce a distinct image`);
    }
    await render('mosaic', 'sage');
    const before = await page.locator('canvas').evaluate(canvas => canvas.toDataURL());
    await page.locator('#knt-cover-variation').click();
    assert.notEqual(await page.locator('canvas').evaluate(canvas => canvas.toDataURL()), before, 'Variation button must redraw');
    await page.evaluate(() => {
      window.fetch = async (_url, options) => {
        const payload = options.body;
        window.savedFixture = Object.fromEntries([...payload.entries()].filter(([key]) => key !== 'cover'));
        const image = await createImageBitmap(payload.get('cover'));
        window.savedFixture.dimensions = [image.width, image.height];
        return { ok: true, json: async () => ({ success: true, data: { editUrl: '#fixture-editor' } }) };
      };
    });
    await page.locator('#knt-cover-save').click();
    await page.waitForFunction(() => document.querySelector('#knt-cover-status').textContent.includes('Saved as'));
    const saved = await page.evaluate(() => window.savedFixture);
    assert.equal(saved.motif, 'mosaic'); assert.equal(saved.palette, 'sage');
    assert.deepEqual(saved.dimensions, [1200, 900]); assert.ok(Number.isSafeInteger(Number(saved.seed)));
    const result = { capturedAt: new Date().toISOString(), browser: await browser.version(), session: 'Fresh isolated headless Chromium fixture; no network or WordPress writes', viewport: '1200 × 900; 1200 × 900 canvas', combinations: 39, legacyPixelComparisons: Object.keys(baselines).length, checks: ['distinct designs', 'repeat rendering and recipe restoration', 'deterministic seed variations', 'new design title safe area', 'variation button', 'mocked save PNG dimensions and recipe payload'], errors };
    assert.deepEqual(errors, []);
    const galleryHtml = cards => `<!doctype html><meta charset="utf-8"><title>Cover Studio canvas verification</title><style>body{margin:24px;background:#e9ede5;color:#173a31;font:16px Georgia}main{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}figure{margin:0}img{width:100%;display:block}figcaption{padding:10px 0}h1{font-weight:400}</style><h1>Cover Studio · ${result.capturedAt}</h1><main>${cards.map(x => `<figure><img src="${x.png}"><figcaption>${x.name} / ${x.palette}</figcaption></figure>`).join('')}</main>`;
    fs.writeFileSync(path.join(output, 'gallery.html'), galleryHtml(gallery));
    fs.writeFileSync(path.join(output, 'report.json'), JSON.stringify(result, null, 2));
    for (const palette of palettes) {
      await page.setContent(galleryHtml(gallery.filter(x => !legacy.includes(x.name) && x.palette === palette)));
      await page.screenshot({ path: path.join(output, `new-designs-${palette}.png`), fullPage: true });
    }
    console.log(JSON.stringify(result, null, 2));
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
