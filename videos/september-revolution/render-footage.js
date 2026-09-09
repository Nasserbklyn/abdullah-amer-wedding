// Composites the transparent text layer (overlay-60s.html) over the graded
// footage base and muxes the zamil. The Arabic type is rendered by Chromium
// because ffmpeg's drawtext does no Arabic shaping or bidi.
//
// Usage: FFMPEG=... PLAYWRIGHT_MODULE=... node render-footage.js [out.mp4]
// Env: BASE (default footage/base-60s.mp4), OVERLAY (default overlay-60s.html),
//      MUSIC (default zamil-60s.wav)
const path = require('path'), fs = require('fs'), { spawn } = require('child_process');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const FPS = 30, W = 1080, H = 1920, DIR = __dirname;
const base    = path.join(DIR, process.env.BASE    || 'footage/base-60s.mp4');
const overlay = path.join(DIR, process.env.OVERLAY || 'overlay-60s.html');
const music   = path.join(DIR, process.env.MUSIC   || 'zamil-60s.wav');
const out     = process.argv[2] || path.join(DIR, 'yemen-september-film-60s.mp4');
const ffmpeg  = process.env.FFMPEG || 'ffmpeg';

(async () => {
  for (const f of [base, overlay]) if (!fs.existsSync(f)) throw new Error('missing ' + f);
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: W, height: H }, deviceScaleFactor: 1 });
  const errs = []; page.on('pageerror', e => errs.push(String(e)));
  await page.goto('file://' + overlay);
  await page.waitForFunction(() => document.fonts.status === 'loaded');
  const total = Math.round(await page.evaluate(() => window.DURATION) * FPS);

  const args = ['-y', '-hide_banner', '-loglevel', 'error',
    '-i', base,
    '-f', 'image2pipe', '-framerate', String(FPS), '-c:v', 'png', '-i', '-'];
  if (fs.existsSync(music)) args.push('-i', music);
  args.push('-filter_complex', '[0:v][1:v]overlay=0:0:format=auto,format=yuv420p[v]', '-map', '[v]');
  if (fs.existsSync(music)) args.push('-map', '2:a', '-c:a', 'aac', '-b:a', '192k', '-ar', '44100');
  args.push('-c:v', 'libx264', '-preset', 'slow', '-crf', '18', '-r', String(FPS),
    '-profile:v', 'high', '-level', '4.0', '-movflags', '+faststart', '-shortest', out);

  const ff = spawn(ffmpeg, args, { stdio: ['pipe', 'inherit', 'inherit'] });
  const write = buf => new Promise(r => { if (!ff.stdin.write(buf)) ff.stdin.once('drain', r); else r(); });

  const t0 = Date.now();
  for (let f = 0; f < total; f++) {
    await page.evaluate(t => window.seek(t), f / FPS);
    await write(await page.screenshot({ type: 'png', omitBackground: true }));
    if (f % 120 === 0) console.log(`frame ${f}/${total} (${((Date.now() - t0) / 1000).toFixed(0)}s)`);
  }
  ff.stdin.end();
  await new Promise((res, rej) => ff.on('close', c => c === 0 ? res() : rej(new Error('ffmpeg ' + c))));
  await browser.close();
  console.log(errs.length ? 'PAGE ERRORS: ' + errs.join(' | ') : 'clean render');
  console.log('done ->', out);
})().catch(e => { console.error(e); process.exit(1); });
