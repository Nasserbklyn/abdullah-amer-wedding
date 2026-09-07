// Renders composition.html frame-by-frame with Playwright and pipes PNG frames into ffmpeg.
// Usage: node render.js [out.mp4] [--portrait]   (--portrait renders 1080x1920 for stories)
// Env: FFMPEG (path to an ffmpeg with libx264), PLAYWRIGHT_MODULE (path to playwright), MUSIC (wav path)
const path = require('path');
const fs = require('fs');
const { spawn } = require('child_process');

const pwPath = process.env.PLAYWRIGHT_MODULE || 'playwright';
const { chromium } = require(pwPath);

const PORTRAIT = process.argv.includes('--portrait');
const FPS = 30, W = PORTRAIT ? 1080 : 1920, H = PORTRAIT ? 1920 : 1080;
const outArg = process.argv.slice(2).find(a => !a.startsWith('--'));
const out = outArg || path.join(__dirname, PORTRAIT ? 'yemen-september-revolution-9x16.mp4' : 'yemen-september-revolution.mp4');
const music = process.env.MUSIC || path.join(__dirname, 'music.wav');
const ffmpeg = process.env.FFMPEG || 'ffmpeg';

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: W, height: H }, deviceScaleFactor: 1 });
  await page.goto('file://' + path.join(__dirname, 'composition.html') + (PORTRAIT ? '?portrait=1' : ''));
  await page.evaluate(() => document.fonts.ready);
  await page.waitForFunction(() => document.fonts.status === 'loaded');
  const duration = await page.evaluate(() => window.DURATION);
  const total = Math.round(duration * FPS);

  const args = ['-y', '-hide_banner', '-loglevel', 'error',
    '-f', 'image2pipe', '-framerate', String(FPS), '-i', '-'];
  if (fs.existsSync(music)) args.push('-i', music);
  args.push('-c:v', 'libx264', '-preset', 'slow', '-crf', '17', '-pix_fmt', 'yuv420p', '-r', String(FPS),
    '-movflags', '+faststart');
  if (fs.existsSync(music)) args.push('-c:a', 'aac', '-b:a', '192k', '-shortest');
  args.push(out);
  const ff = spawn(ffmpeg, args, { stdio: ['pipe', 'inherit', 'inherit'] });

  const write = buf => new Promise(res => { if (!ff.stdin.write(buf)) ff.stdin.once('drain', res); else res(); });

  const t0 = Date.now();
  for (let f = 0; f < total; f++) {
    await page.evaluate(t => window.seek(t), f / FPS);
    const png = await page.screenshot({ type: 'png' });
    await write(png);
    if (f % 60 === 0) console.log(`frame ${f}/${total} (${((Date.now() - t0) / 1000).toFixed(1)}s)`);
  }
  ff.stdin.end();
  await new Promise((res, rej) => ff.on('close', c => c === 0 ? res() : rej(new Error('ffmpeg exit ' + c))));
  await browser.close();
  console.log('done ->', out);
})().catch(e => { console.error(e); process.exit(1); });
