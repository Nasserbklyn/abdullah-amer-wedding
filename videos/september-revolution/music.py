"""Procedural cinematic soundtrack (20 s) for the September Revolution video.
Pure numpy synthesis: minor drone pad, sub-kicks, taiko hits, riser and impacts.
Usage: python3 music.py [music.wav]
"""
import sys, wave, numpy as np

SR = 44100
DUR = 20.0
N = int(SR * DUR)
t = np.arange(N) / SR
rng = np.random.default_rng(26)

def env_pts(points):
    """Piecewise-linear envelope from (time, value) points."""
    xs, ys = zip(*points)
    return np.interp(t, xs, ys)

def place(buf, sig, at, amp=1.0):
    i = int(at * SR)
    n = min(len(sig), N - i)
    if n > 0:
        buf[i:i + n] += sig[:n] * amp

def kick(f0=150, f1=42, dur=0.6, decay=9):
    tt = np.arange(int(dur * SR)) / SR
    f = f1 + (f0 - f1) * np.exp(-tt * 22)
    ph = 2 * np.pi * np.cumsum(f) / SR
    return np.sin(ph) * np.exp(-tt * decay)

def taiko(dur=0.9):
    tt = np.arange(int(dur * SR)) / SR
    f = 62 + 40 * np.exp(-tt * 18)
    ph = 2 * np.pi * np.cumsum(f) / SR
    body = np.sin(ph) * np.exp(-tt * 5.5)
    click = rng.standard_normal(len(tt)) * np.exp(-tt * 90) * 0.5
    return body + click

def noise_hit(dur=1.4, decay=6):
    tt = np.arange(int(dur * SR)) / SR
    n = rng.standard_normal(len(tt))
    # crude low-pass by moving average for a darker boom
    n = np.convolve(n, np.ones(24) / 24, mode='same')
    return n * np.exp(-tt * decay)

def tick(dur=0.05):
    tt = np.arange(int(dur * SR)) / SR
    return rng.standard_normal(len(tt)) * np.exp(-tt * 140)

# ---------------- pad / drone ----------------
pad_env = env_pts([(0, 0), (0.6, 0.35), (4, 0.8), (8, 0.6), (8.6, 0.95), (15.4, 1.0), (15.6, 0.7), (18.5, 0.9), (19.9, 0.0), (20, 0)])
def voice(freq, detune, amp):
    lfo = 1 + 0.004 * np.sin(2 * np.pi * 0.13 * t + freq)
    f = freq * (1 + detune) * lfo
    return amp * (np.sin(2 * np.pi * f * t) + 0.35 * np.sin(2 * np.pi * 2 * f * t + 0.3) + 0.12 * np.sin(2 * np.pi * 3 * f * t))
notes = [(55, 0.9), (110, 0.7), (164.81, 0.35), (220, 0.32), (261.63, 0.22), (329.63, 0.18)]
padL = sum(voice(f, +0.0025, a) for f, a in notes)
padR = sum(voice(f, -0.0025, a) for f, a in notes)
padL *= pad_env * 0.11
padR *= pad_env * 0.11
# a high shimmer that only enters for the finale
shimmer = (np.sin(2 * np.pi * 659.26 * t) + np.sin(2 * np.pi * 880 * t * 1.001)) * env_pts([(0, 0), (15.5, 0), (16.5, 0.05), (19, 0.05), (19.9, 0)])
padL += shimmer; padR += shimmer

# ---------------- percussion ----------------
perc = np.zeros(N)
place(perc, kick(), 0.05, 0.9); place(perc, noise_hit(), 0.05, 0.35)
# scene 1-2: slow heartbeat
for b in [3.5, 4.7, 5.9, 7.1]:
    place(perc, kick(), b, 0.75)
for b in [4.4, 5.6, 6.8]:
    place(perc, taiko(), b, 0.45)
# impact into scene 3
place(perc, kick(), 8.15, 1.0); place(perc, noise_hit(1.6, 5), 8.15, 0.5); place(perc, taiko(1.2), 8.15, 0.8)
# scene 3: driving war-drums, growing
beat = 0.6
b = 8.75
i = 0
while b < 15.3:
    grow = 0.65 + 0.35 * (b - 8.75) / 6.5
    place(perc, kick(), b, 0.8 * grow)
    if i % 2 == 1:
        place(perc, taiko(), b - 0.3, 0.5 * grow)
    if b > 11.0:
        place(perc, taiko(0.5), b + 0.3, 0.45 * grow)
    if b > 13.0:
        place(perc, kick(120, 45, 0.3, 14), b + 0.15, 0.4 * grow)
        place(perc, kick(120, 45, 0.3, 14), b + 0.45, 0.4 * grow)
    b += beat; i += 1
for k in np.arange(8.75, 15.3, 0.3):
    place(perc, tick(), k, 0.12)
# riser 13 -> 15.5
riser_env = env_pts([(0, 0), (13.0, 0), (15.45, 1), (15.5, 0), (20, 0)])
rn = np.convolve(rng.standard_normal(N), np.ones(6) / 6, mode='same')
riser = rn * riser_env ** 2 * 0.35
sweep_f = 90 * (2 ** (env_pts([(0, 0), (13, 0), (15.5, 3.2), (20, 3.2)])))
riser += np.sin(2 * np.pi * np.cumsum(sweep_f) / SR) * riser_env ** 1.5 * 0.18
# finale impact
place(perc, kick(170, 38, 1.2, 5), 15.5, 1.0); place(perc, noise_hit(2.2, 3.2), 15.5, 0.7); place(perc, taiko(1.6), 15.5, 0.9)
for b in [16.7, 17.9, 19.1]:
    place(perc, kick(), b, 0.55)

# simple echo on percussion for space
def echo(x, delay, gain):
    d = int(delay * SR); y = x.copy(); y[d:] += gain * x[:-d]; return y
perc = echo(echo(perc, 0.18, 0.28), 0.36, 0.14)

L = padL + perc + riser
R = padR + perc * 0.98 + riser
mix = np.stack([L, R], axis=1)
mix = np.tanh(mix * 1.15)  # soft clip
mix *= 0.89 / np.max(np.abs(mix))
# global fade out
mix *= env_pts([(0, 1), (19.0, 1), (19.95, 0), (20, 0)])[:, None]

out = sys.argv[1] if len(sys.argv) > 1 else __import__('os').path.join(__import__('os').path.dirname(__file__), 'music.wav')
with wave.open(out, 'wb') as w:
    w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
    w.writeframes((mix * 32767).astype('<i2').tobytes())
print('wrote', out)
