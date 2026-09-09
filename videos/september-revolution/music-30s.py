"""Original 30 s soundtrack for the September reel — pure numpy synthesis.

No sampled or copyrighted material, so the reel stays eligible for Facebook
recommendation and never trips Rights Manager. Cues are aligned to the beat
map in reel-30s.html.  Usage: python3 music-30s.py [music-30s.wav]
"""
import sys, os, wave, numpy as np

SR, DUR = 44100, 30.0
N = int(SR * DUR)
t = np.arange(N) / SR
rng = np.random.default_rng(926)

def env(points):
    xs, ys = zip(*points)
    return np.interp(t, xs, ys)

def place(buf, sig, at, amp=1.0):
    i = int(at * SR); n = min(len(sig), N - i)
    if n > 0: buf[i:i+n] += sig[:n] * amp

def kick(f0=155, f1=41, dur=.7, dec=8.5):
    tt = np.arange(int(dur*SR))/SR
    f = f1 + (f0-f1)*np.exp(-tt*21)
    return np.sin(2*np.pi*np.cumsum(f)/SR) * np.exp(-tt*dec)

def taiko(dur=.95):
    tt = np.arange(int(dur*SR))/SR
    f = 64 + 42*np.exp(-tt*17)
    body = np.sin(2*np.pi*np.cumsum(f)/SR) * np.exp(-tt*5.2)
    return body + np.convolve(rng.standard_normal(len(tt)), np.ones(9)/9, 'same')*np.exp(-tt*85)*.55

def boom(dur=1.8, dec=4.0):
    tt = np.arange(int(dur*SR))/SR
    n = np.convolve(rng.standard_normal(len(tt)), np.ones(26)/26, 'same')
    return n * np.exp(-tt*dec)

def tick(dur=.05):
    tt = np.arange(int(dur*SR))/SR
    return rng.standard_normal(len(tt)) * np.exp(-tt*140)

# ---- pad: A minor drone that lifts through the piece ----
pad = env([(0,0),(.5,.30),(3.6,.70),(8.6,.62),(11.4,.55),(11.6,.95),
           (25.8,1.0),(26.0,.75),(27.5,.95),(29.2,.55),(29.95,0),(30,0)])
def voice(f, det, a):
    lfo = 1 + .004*np.sin(2*np.pi*.12*t + f)
    ff = f*(1+det)*lfo
    return a*(np.sin(2*np.pi*ff*t) + .34*np.sin(2*np.pi*2*ff*t+.3) + .11*np.sin(2*np.pi*3*ff*t))
notes = [(55,.92),(110,.70),(164.81,.34),(220,.30),(261.63,.21),(329.63,.17)]
L = sum(voice(f,+.0026,a) for f,a in notes) * pad * .105
R = sum(voice(f,-.0026,a) for f,a in notes) * pad * .105
shimmer = (np.sin(2*np.pi*659.26*t) + np.sin(2*np.pi*880*1.001*t)) * \
          env([(0,0),(26.0,0),(27.0,.052),(29.0,.052),(29.9,0)])
L += shimmer; R += shimmer

# ---- percussion, cued to the edit ----
p = np.zeros(N)
place(p, kick(), .03, .95); place(p, boom(), .03, .38)          # flag slam
place(p, kick(), .92, .80); place(p, taiko(), .92, .55)          # title hit
for b in (2.4, 3.7, 5.2, 6.7, 8.2):                              # 1962 heartbeat
    place(p, kick(), b, .72)
for b in (4.45, 7.45):
    place(p, taiko(), b, .42)
place(p, kick(), 8.85, .78); place(p, taiko(1.1), 8.85, .5)      # bridge
place(p, kick(170,38,1.3,4.6), 11.5, 1.0)                        # into the map
place(p, boom(2.4,3.0), 11.5, .62); place(p, taiko(1.5), 11.5, .85)

b, i = 12.1, 0                                                   # driving bed under the facts
while b < 25.8:
    g = .62 + .38*(b-12.1)/13.7
    place(p, kick(), b, .78*g)
    if i % 2: place(p, taiko(), b-.3, .46*g)
    if b > 16:  place(p, taiko(.5), b+.3, .40*g)
    if b > 20:  place(p, kick(120,45,.3,14), b+.15, .34*g); place(p, kick(120,45,.3,14), b+.45, .34*g)
    b += .6; i += 1
for f_s in (12.55, 15.85, 19.15, 22.45):                         # accent on each fact card
    place(p, taiko(1.0), f_s, .60); place(p, kick(), f_s, .55)
for k in np.arange(12.1, 25.8, .3):
    place(p, tick(), k, .11)

riser = env([(0,0),(23.4,0),(25.95,1),(26.0,0),(30,0)])
rn = np.convolve(rng.standard_normal(N), np.ones(6)/6, 'same')
sweep = 90 * 2**env([(0,0),(23.4,0),(26.0,3.2),(30,3.2)])
p_r = rn*riser**2*.34 + np.sin(2*np.pi*np.cumsum(sweep)/SR)*riser**1.5*.17

place(p, kick(175,37,1.4,4.4), 26.0, 1.0)                        # finale
place(p, boom(2.6,2.9), 26.0, .72); place(p, taiko(1.7), 26.0, .92)
for b in (27.2, 28.4, 29.0):
    place(p, kick(), b, .5)

def echo(x, d, g):
    k = int(d*SR); y = x.copy(); y[k:] += g*x[:-k]; return y
p = echo(echo(p, .18, .27), .36, .13)

mix = np.stack([L + p + p_r, R + p*.98 + p_r], axis=1)
mix = np.tanh(mix * 1.15)
mix *= .89 / np.max(np.abs(mix))
mix *= env([(0,1),(29.2,1),(29.95,0),(30,0)])[:, None]

out = sys.argv[1] if len(sys.argv) > 1 else os.path.join(os.path.dirname(__file__), 'music-30s.wav')
with wave.open(out, 'wb') as w:
    w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
    w.writeframes((mix*32767).astype('<i2').tobytes())
print('wrote', out)
