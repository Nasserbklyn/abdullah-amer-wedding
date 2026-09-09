// Renders reel-30s.html frame-by-frame (1080x1920) and muxes the soundtrack.
// Usage: FFMPEG=/path/to/ffmpeg node render-reel.js [out.mp4]
// Env: FFMPEG, PLAYWRIGHT_MODULE, COMP (default reel-30s.html), MUSIC (default music-30s.wav)
const path = require('path'), fs = require('fs'), { spawn } = require('child_process');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const FPS = 30, W = 1080, H = 1920, DIR = __dirname;
const comp  = path.join(DIR, process.env.COMP  || 'reel-30s.html');
const music = path.join(DIR, process.env.MUSIC || 'music-30s.wav');
const out   = process.argv[2] || path.join(DIR, 'yemen-september-reel-30s.mp4');
const ffmpeg = process.env.FFMPEG || 'ffmpeg';

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: W, height: H }, deviceScaleFactor: 1 });
  const errs = []; page.on('pageerror', e => errs.push(String(e)));
  await page.goto('file://' + comp);
  await page.waitForFunction(() => document.fonts.status === 'loaded');
  const total = Math.round(await page.evaluate(() => window.DURATION) * FPS);

  // H.264 High@4.0 + AAC 44.1k — Meta's recommended Reels delivery profile.
  const args = ['-y', '-hide_banner', '-loglevel', 'error',
    '-f', 'image2pipe', '-framerate', String(FPS), '-i', '-'];
  if (fs.existsSync(music)) args.push('-i', music);
  args.push('-c:v', 'libx264', '-preset', 'slow', '-crf', '18', '-pix_fmt', 'yuv420p',
    '-r', String(FPS), '-profile:v', 'high', '-level', '4.0', '-movflags', '+faststart');
  if (fs.existsSync(music)) args.push('-c:a', 'aac', '-b:a', '192k', '-ar', '44100', '-shortest');
  args.push(out);
  const ff = spawn(ffmpeg, args, { stdio: ['pipe', 'inherit', 'inherit'] });
  const write = buf => new Promise(r => { if (!ff.stdin.write(buf)) ff.stdin.once('drain', r); else r(); });

  const t0 = Date.now();
  for (let f = 0; f < total; f++) {
    await page.evaluate(t => window.seek(t), f / FPS);
    await write(await page.screenshot({ type: 'png' }));
    if (f % 90 === 0) console.log(`frame ${f}/${total} (${((Date.now() - t0) / 1000).toFixed(0)}s)`);
  }
  ff.stdin.end();
  await new Promise((res, rej) => ff.on('close', c => c === 0 ? res() : rej(new Error('ffmpeg ' + c))));
  await browser.close();
  console.log(errs.length ? 'PAGE ERRORS: ' + errs.join(' | ') : 'clean render');
  console.log('done ->', out);
})().catch(e => { console.error(e); process.exit(1); });
