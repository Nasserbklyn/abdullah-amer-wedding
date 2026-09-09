"""Original Yemeni-style أهزوجة (zamil) beds for the September reels — pure numpy.

Three synthesized layers, no sampled or third-party recording, so the reels stay
eligible for recommendation and never trip Facebook's Rights Manager:

  * zamil percussion — doum/tak march at 100 BPM (0.6 s beat), the pulse a zamil
    is chanted over;
  * crowd hand-claps on the backbeat, several time-jittered copies per hit so
    they read as a group rather than one pair of hands;
  * a wordless male unison chant. Each voice is additive: a glottal harmonic
    series scaled by the magnitude response of the /a/ formants, with seven to
    nine voices detuned and offset against each other for the ragged edge of men
    chanting together. Phrasing follows the zamil call and response — three short
    cries answered by one sustained shout.

F2/F3 sit above a neutral vowel because phone speakers roll off the low end, and
the mix puts the voices ahead of the drums so the chant leads.

Usage: python3 zamil.py [30|60] [out.wav]
"""
import sys, os, wave, numpy as np

SR = 44100
BEAT = 0.6                      # 100 BPM march — the zamil pulse
FORMANTS = [(730, 90, 1.00), (1090, 110, 0.78), (2440, 170, 0.40), (3400, 250, 0.18)]
rng = np.random.default_rng(2609)


def formant_gain(f):
    g = 0.0
    for F, B, a in FORMANTS:
        g += a / np.sqrt(1.0 + ((f*f - F*F) / np.maximum(f*B, 1e-6))**2)
    return g


def voice(f0, dur, attack=.035, release=.10, drift=.012, n_harm=44):
    """One chanted /a/, built additively from formant-shaped harmonics."""
    n = int(dur*SR); tt = np.arange(n)/SR
    scoop = -0.055*np.exp(-tt/0.045)                       # pitch scoops up into the note
    f = f0*(1 + scoop + drift*np.sin(2*np.pi*0.9*tt + rng.random()*6.28)
              + .006*np.sin(2*np.pi*5.2*tt)*np.clip((tt-.15)/.3, 0, 1))
    ph = 2*np.pi*np.cumsum(f)/SR
    sig = np.zeros(n)
    for k in range(1, n_harm+1):
        fk = f0*k
        if fk > SR/2 - 500: break
        sig += (formant_gain(fk)/k**1.05)*np.sin(k*ph + rng.random()*6.28)
    br = np.convolve(rng.standard_normal(n), np.ones(12)/12, 'same')
    sig = sig/(np.max(np.abs(sig))+1e-9) + .075*br         # breath keeps it off an organ tone
    return sig*np.clip(tt/attack, 0, 1)*np.clip((dur-tt)/release, 0, 1)*(0.82+0.18*np.clip(tt/.09, 0, 1))


def crowd(f0, dur, n_voices=7, spread=.016, jitter=.030):
    pad = int((jitter*4+.05)*SR)
    out = np.zeros(int(dur*SR)+pad)
    for _ in range(n_voices):
        v = voice(f0*(1+rng.normal(0, spread)), dur)
        off = min(int(abs(rng.normal(0, jitter))*SR), pad)
        n = min(len(v), len(out)-off)
        out[off:off+n] += v[:n]
    return out/n_voices**0.62


def doum(dur=.55):
    tt = np.arange(int(dur*SR))/SR
    f = 62 + 78*np.exp(-tt*26)
    body = np.sin(2*np.pi*np.cumsum(f)/SR)*np.exp(-tt*7.5)
    skin = np.convolve(rng.standard_normal(len(tt)), np.ones(7)/7, 'same')*np.exp(-tt*60)*.42
    return body + skin


def tak(dur=.17):
    tt = np.arange(int(dur*SR))/SR
    n = rng.standard_normal(len(tt))
    n = n - np.convolve(n, np.ones(9)/9, 'same')
    return (n*.85 + np.sin(2*np.pi*430*tt)*.3)*np.exp(-tt*36)


def clap(dur=.32, hands=9):
    n = int(dur*SR); out = np.zeros(n)
    for _ in range(hands):
        off = int(abs(rng.normal(0, .012))*SR); m = n-off
        if m <= 0: continue
        tt = np.arange(m)/SR
        w = rng.standard_normal(m); w = w - np.convolve(w, np.ones(5)/5, 'same')
        out[off:] += w*np.exp(-tt*52)
    return out/hands**0.5


def boom(dur=1.9, dec=3.8):
    tt = np.arange(int(dur*SR))/SR
    return np.convolve(rng.standard_normal(len(tt)), np.ones(26)/26, 'same')*np.exp(-tt*dec)


def build(DUR, cues):
    """cues: dict of edit marks the arrangement is pinned to (see ARRANGEMENTS)."""
    N = int(SR*DUR); t = np.arange(N)/SR
    perc, claps, chant = np.zeros(N), np.zeros(N), np.zeros(N)

    def env(pts):
        xs, ys = zip(*pts); return np.interp(t, xs, ys)

    def place(buf, sig, at, amp=1.0):
        i = int(at*SR)
        if i < 0 or i >= N: return
        n = min(len(sig), N-i)
        if n > 0: buf[i:i+n] += sig[:n]*amp

    def bar(at, heavy=True):
        """One 2-beat zamil bar: DOUM . tak . DOUM-tak, clap on the backbeat."""
        place(perc, doum(), at, 1.0)
        place(perc, tak(), at+BEAT*.5, .55)
        place(perc, doum(), at+BEAT, .82)
        place(perc, tak(), at+BEAT*1.5, .62)
        if heavy: place(perc, tak(), at+BEAT*1.75, .38)
        place(claps, clap(), at+BEAT, .95)

    place(perc, doum(.8), .03, 1.15); place(perc, boom(), .03, .40)
    place(chant, crowd(110, .95), .02, .95)
    place(perc, doum(), cues['title'], .85); place(claps, clap(), cues['title'], .7)

    for i, at in enumerate(np.arange(cues['s1'], cues['s1_end'], BEAT*2)):
        bar(at, heavy=False)
        if i % 2 == 1: place(chant, crowd(110 if i < 4 else 130.81, .34), at+BEAT, .55)

    for at in np.arange(cues['s2'], cues['s2_end'], BEAT*2):     # tense, no claps
        place(perc, doum(), at, .78); place(perc, tak(), at+BEAT*1.5, .5)
    place(chant, crowd(110, .80), cues['s2']+.05, .62)

    place(perc, doum(.95), cues['drop'], 1.2); place(perc, boom(2.3, 3.0), cues['drop'], .60)
    place(chant, crowd(146.83, 1.15), cues['drop']+.02, 1.0)

    at, i = cues['bed'], 0
    while at < cues['bed_end']:
        g = .70 + .30*(at-cues['bed'])/max(cues['bed_end']-cues['bed'], 1e-6)
        bar(at, heavy=(at > cues['bed']+5))
        if i % 2 == 0:
            for k in range(3): place(chant, crowd(110, .30), at+k*BEAT*.66, .52*g)
        else:
            place(chant, crowd(130.81, .78), at+BEAT*.5, .62*g)
        at += BEAT*2; i += 1
    for f_s in cues['accents']:
        place(perc, doum(.7), f_s, .75); place(claps, clap(), f_s, .85)

    riser = env([(0, 0), (cues['fin']-2.6, 0), (cues['fin']-.05, 1), (cues['fin'], 0), (DUR, 0)])
    rn = np.convolve(rng.standard_normal(N), np.ones(6)/6, 'same')
    sweep = 95*2**env([(0, 0), (cues['fin']-2.6, 0), (cues['fin'], 3.1), (DUR, 3.1)])
    riser_sig = rn*riser**2*.30 + np.sin(2*np.pi*np.cumsum(sweep)/SR)*riser**1.5*.15

    place(perc, doum(1.0), cues['fin'], 1.25); place(perc, boom(2.5, 2.8), cues['fin'], .70)
    place(claps, clap(.5, 14), cues['fin'], 1.0)
    place(chant, crowd(146.83, 1.5, n_voices=9), cues['fin']+.02, 1.15)
    for k, at in enumerate(cues['outro']):
        bar(at, heavy=True)
        place(chant, crowd(146.83 if k < len(cues['outro'])-1 else 110,
                           .55 if k < len(cues['outro'])-1 else .95, n_voices=9), at, .95)

    def echo(x, d, g):
        k = int(d*SR); y = x.copy(); y[k:] += g*x[:-k]; return y
    perc_e, claps_e, chant_e = echo(perc, .19, .22), echo(claps, .14, .30), echo(chant, .21, .26)

    drone = (np.sin(2*np.pi*55*t) + .4*np.sin(2*np.pi*110*t)) * env(
        [(0, 0), (1, .08), (cues['drop']-.3, .07), (cues['drop'], .13),
         (cues['fin']-.1, .13), (cues['fin']+.1, .15), (DUR-.8, .09), (DUR-.05, 0), (DUR, 0)])

    master = env([(0, 1), (DUR-.8, 1), (DUR-.05, 0), (DUR, 0)])
    mid = perc_e*0.80 + claps_e*1.15 + chant_e*1.85 + riser_sig + drone
    mix = np.stack([mid + chant_e*.14 + claps_e*.05,
                    mid - chant_e*.14 - claps_e*.05], axis=1)*master[:, None]
    mix = np.tanh(mix*1.10)
    return mix*(0.90/np.max(np.abs(mix)))


# edit marks for each cut, matching the beats in reel-30s.html / reel-60s.html
ARRANGEMENTS = {
 30: dict(title=.92, s1=3.9, s1_end=8.4, s2=8.85, s2_end=9.6, drop=11.5,
          bed=12.1, bed_end=25.8, accents=(12.55, 15.85, 19.15, 22.45),
          fin=26.0, outro=(27.2, 28.0, 28.8)),
 60: dict(title=.92, s1=4.2, s1_end=8.9, s2=9.4, s2_end=13.4, drop=13.9,
          bed=15.6, bed_end=49.4, accents=(17.6, 22.1, 26.6, 31.1, 35.6, 40.1, 44.6, 50.2),
          fin=55.0, outro=(56.2, 57.0, 57.8, 58.6)),
}

if __name__ == '__main__':
    dur = int(sys.argv[1]) if len(sys.argv) > 1 else 60
    if dur not in ARRANGEMENTS:
        sys.exit(f'no arrangement for {dur}s (have {sorted(ARRANGEMENTS)})')
    mix = build(dur, ARRANGEMENTS[dur])
    out = sys.argv[2] if len(sys.argv) > 2 else os.path.join(os.path.dirname(__file__), f'zamil-{dur}s.wav')
    with wave.open(out, 'wb') as w:
        w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
        w.writeframes((mix*32767).astype('<i2').tobytes())
    print(f'wrote {out}  ({dur}s)')
