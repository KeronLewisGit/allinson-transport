// Records the home page motion as video for the pitch: node scripts/proposal/record.mjs out.webm, then
// convert with ffmpeg (H.264, CRF 24) to <Name>-Home-Motion.mp4. Needs the dev server on 8115.
import puppeteer from 'puppeteer-core';
const out = process.argv[2];
const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new', args: ['--hide-scrollbars'] });
const p = await b.newPage();
await p.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }, { name: 'prefers-reduced-motion', value: 'no-preference' }]);
await p.setViewport({ width: 1280, height: 720, deviceScaleFactor: 1 });
// Warm the cache so the first frame is not a blank load.
await p.goto('http://127.0.0.1:8115/', { waitUntil: 'networkidle0' });
await p.goto('about:blank');
const rec = await p.screencast({ path: out, ffmpegPath: '/opt/homebrew/bin/ffmpeg', speed: 1 });
const sleep = (ms) => new Promise(r => setTimeout(r, ms));
const glide = (sel, offset, ms) => p.evaluate((sel, offset, ms) => new Promise((done) => {
  const el = sel ? document.querySelector(sel) : null;
  const from = scrollY; const to = el ? el.getBoundingClientRect().top + scrollY - offset : offset;
  const t0 = performance.now();
  const step = (now) => { const t = Math.min(1, (now - t0) / ms); const e = t < .5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2; scrollTo(0, from + (to - from) * e); t < 1 ? requestAnimationFrame(step) : done(); };
  requestAnimationFrame(step);
}), sel, offset, ms);
await p.goto('http://127.0.0.1:8115/', { waitUntil: 'domcontentloaded' });
await sleep(4200);
await glide('#figures-title', 120, 1400); await sleep(2400);
await glide('.tab-label', 160, 1300); await sleep(1500);
await glide('.depot-map', 140, 1500); await sleep(3200);
await glide('[data-strike]', 300, 1300); await sleep(2200);
await glide(null, 0, 1600); await sleep(900);
await p.click('[data-brandbar] [data-theme-toggle]'); await sleep(1600);
await rec.stop();
await b.close();
