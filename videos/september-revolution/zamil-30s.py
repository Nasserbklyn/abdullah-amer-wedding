"""Original Yemeni-style أهزوجة (zamil) bed for the 30 s reel — pure numpy.

Three layers, all synthesized from scratch so the reel carries no third-party
recording and stays clear of Facebook Rights Manager:

  * zamil percussion — doum/tak march at 100 BPM (0.6 s beat), the pulse a
    zamil is chanted over;
  * crowd hand-claps on the backbeat, several jittered copies per hit so they
    read as a group rather than one pair of hands;
  * a wordless male unison chant. Each voice is built additively: a glottal
    harmonic series whose partials are scaled by the magnitude response of the
    /a/ formants (F1 730, F2 1090, F3 2440, F4 3400 Hz). Seven voices are
    detuned and time-jittered against each other to give the ragged edge of
    men chanting together instead of one clean synthetic tone.

Usage: python3 zamil-30s.py [zamil-30s.wav]
"""
import sys, os, wave, numpy as np

SR, DUR = 44100, 30.0
N = int(SR * DUR)
t = np.arange(N) / SR
rng = np.random.default_rng(2609)

BEAT = 0.6                      # 100 BPM march — the zamil pulse
FORMANTS = [(730, 90, 1.00), (1090, 110, 0.78), (2440, 170, 0.40), (3400, 250, 0.18)]

def env(pts):
    xs, ys = zip(*pts)
    return np.interp(t, xs, ys)

def place(buf, sig, at, amp=1.0):
    i = int(at * SR)
    if i < 0 or i >= N: return
    n = min(len(sig), N - i)
    if n > 0: buf[i:i+n] += sig[:n] * amp

def formant_gain(f):
    """Magnitude of the vowel's formant filter at frequency f."""
    g = 0.0
    for F, B, a in FORMANTS:
        g += a / np.sqrt(1.0 + ((f*f - F*F) / np.maximum(f*B, 1e-6))**2)
    return g

def voice(f0, dur, attack=.035, release=.10, drift=.012, n_harm=44):
    """One chanted /a/ note, built additively from formant-shaped harmonics."""
    n = int(dur * SR); tt = np.arange(n) / SR
    # human pitch is never flat: slow drift + a little vibrato late in the note
    scoop = -0.055 * np.exp(-tt/0.045)          # pitch scoops up into the note
    f = f0 * (1 + scoop + drift*np.sin(2*np.pi*0.9*tt + rng.random()*6.28)
                + .006*np.sin(2*np.pi*5.2*tt) * np.clip((tt-.15)/.3, 0, 1))
    ph = 2*np.pi*np.cumsum(f)/SR
    sig = np.zeros(n)
    for k in range(1, n_harm+1):
        fk = f0*k
        if fk > SR/2 - 500: break
        sig += (formant_gain(fk) / k**1.05) * np.sin(k*ph + rng.random()*6.28)
    # breath noise gives the voice body; without it the chant sounds like an organ
    br = np.convolve(rng.standard_normal(n), np.ones(12)/12, 'same')
    sig = sig/ (np.max(np.abs(sig))+1e-9) + .075*br
    a = np.clip(tt/attack, 0, 1)
    r = np.clip((dur-tt)/release, 0, 1)
    return sig * a * r * (0.82 + 0.18*np.clip(tt/.09, 0, 1))

def crowd(f0, dur, n_voices=7, spread=.016, jitter=.030):
    """Several detuned, time-offset voices = men chanting in unison."""
    pad = int((jitter*4 + .05)*SR)
    out = np.zeros(int(dur*SR) + pad)
    for _ in range(n_voices):
        v = voice(f0 * (1 + rng.normal(0, spread)), dur)
        off = min(int(abs(rng.normal(0, jitter))*SR), pad)
        n = min(len(v), len(out) - off)
        out[off:off+n] += v[:n]
    return out / n_voices**0.62

def doum(dur=.55):
    tt = np.arange(int(dur*SR))/SR
    f = 62 + 78*np.exp(-tt*26)
    body = np.sin(2*np.pi*np.cumsum(f)/SR) * np.exp(-tt*7.5)
    skin = np.convolve(rng.standard_normal(len(tt)), np.ones(7)/7, 'same')*np.exp(-tt*60)*.42
    return body + skin

def tak(dur=.17):
    tt = np.arange(int(dur*SR))/SR
    n = rng.standard_normal(len(tt))
    n = n - np.convolve(n, np.ones(9)/9, 'same')          # crude high-pass: brighter slap
    return (n*.85 + np.sin(2*np.pi*430*tt)*.3) * np.exp(-tt*36)

def clap(dur=.32, hands=9):
    n = int(dur*SR); out = np.zeros(n)
    for _ in range(hands):                                 # a crowd, not one pair of hands
        off = int(abs(rng.normal(0, .012))*SR)
        m = n - off
        if m <= 0: continue
        tt = np.arange(m)/SR
        w = rng.standard_normal(m)
        w = w - np.convolve(w, np.ones(5)/5, 'same')
        out[off:] += w * np.exp(-tt*52)
    return out / hands**0.5

def boom(dur=1.9, dec=3.8):
    tt = np.arange(int(dur*SR))/SR
    return np.convolve(rng.standard_normal(len(tt)), np.ones(26)/26, 'same') * np.exp(-tt*dec)

# ----------------------------------------------------------------- layers
perc  = np.zeros(N)
claps = np.zeros(N)
chant = np.zeros(N)

def bar(at, heavy=True):
    """One 2-beat zamil bar: DOUM . tak . DOUM-tak, clap on the backbeat."""
    place(perc, doum(), at, 1.0)
    place(perc, tak(),  at + BEAT*0.5, .55)
    place(perc, doum(), at + BEAT, .82)
    place(perc, tak(),  at + BEAT*1.5, .62)
    if heavy:
        place(perc, tak(), at + BEAT*1.75, .38)
    place(claps, clap(), at + BEAT, .95)

# openings
place(perc, doum(.8), .03, 1.15); place(perc, boom(), .03, .40)
place(chant, crowd(110, .95), .02, .95)                    # opening shout
place(perc, doum(), .92, .85); place(claps, clap(), .92, .7)

# 1962 section — sparse, marching
for i in range(6):
    at = 3.9 + i*BEAT*2
    if at < 8.4: bar(at, heavy=False)
place(chant, crowd(110, .34), 4.5, .55)
place(chant, crowd(110, .34), 5.7, .55)
place(chant, crowd(130.81, .70), 6.9, .65)

# bridge
place(perc, doum(.7), 8.85, .95); place(claps, clap(), 8.85, .8)
place(chant, crowd(110, .80), 8.88, .70)

# the drop into the map section
place(perc, doum(.95), 11.5, 1.2); place(perc, boom(2.3, 3.0), 11.5, .60)
place(chant, crowd(146.83, 1.15), 11.52, 1.0)

# full zamil under the four facts
at, i = 12.1, 0
while at < 25.8:
    g = .70 + .30*(at-12.1)/13.7
    bar(at, heavy=(at > 17))
    perc_g = g
    if i % 2 == 0:                                          # call: three short cries
        for k in range(3):
            place(chant, crowd(110, .30), at + k*BEAT*0.66, .52*g)
    else:                                                   # response: one long shout
        place(chant, crowd(130.81, .78), at + BEAT*0.5, .62*g)
    at += BEAT*2; i += 1

for f_s in (12.55, 15.85, 19.15, 22.45):                    # accent on each fact card
    place(perc, doum(.7), f_s, .75); place(claps, clap(), f_s, .85)

# riser into the finale
riser = env([(0,0),(23.4,0),(25.95,1),(26.0,0),(30,0)])
rn = np.convolve(rng.standard_normal(N), np.ones(6)/6, 'same')
sweep = 95 * 2**env([(0,0),(23.4,0),(26.0,3.1),(30,3.1)])
riser_sig = rn*riser**2*.30 + np.sin(2*np.pi*np.cumsum(sweep)/SR)*riser**1.5*.15

# finale — the whole crowd
place(perc, doum(1.0), 26.0, 1.25); place(perc, boom(2.5, 2.8), 26.0, .70)
place(claps, clap(.5, 14), 26.0, 1.0)
place(chant, crowd(146.83, 1.5, n_voices=9), 26.02, 1.15)
for k, at in enumerate((27.2, 28.0, 28.8)):
    bar(at, heavy=True)
    place(chant, crowd(146.83 if k < 2 else 110, .55 if k < 2 else .95, n_voices=9), at, .95)

# ----------------------------------------------------------------- mix
def echo(x, d, g):
    k = int(d*SR); y = x.copy(); y[k:] += g*x[:-k]; return y

perc  = echo(perc, .19, .22)
claps = echo(claps, .14, .30)
chant = echo(chant, .21, .26)                               # a little hall around the voices

# gentle low-end bed so the chant sits on something
drone = (np.sin(2*np.pi*55*t) + .4*np.sin(2*np.pi*110*t)) * \
        env([(0,0),(1,.08),(11.4,.07),(11.7,.13),(25.9,.13),(26.1,.15),(29.2,.09),(29.95,0),(30,0)])

master = env([(0,1),(29.2,1),(29.95,0),(30,0)])
mid = perc*0.80 + claps*1.15 + chant*1.85 + riser_sig + drone
L = mid + chant*0.14 + claps*0.05
R = mid - chant*0.14 - claps*0.05                           # slight width on the crowd
mix = np.stack([L, R], axis=1) * master[:, None]
mix = np.tanh(mix * 1.10)
mix *= 0.90 / np.max(np.abs(mix))

out = sys.argv[1] if len(sys.argv) > 1 else os.path.join(os.path.dirname(__file__), 'zamil-30s.wav')
with wave.open(out, 'wb') as w:
    w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
    w.writeframes((mix*32767).astype('<i2').tobytes())
print('wrote', out)
