#!/usr/bin/env python3
"""Build one episode's timeline and soundtrack from its script.json.

  python3 build.py ep1

Writes ep1/timeline.js (dialogue + lip-sync syllables, read by the page) and
ep1/audio/mix.wav (cartoon babble voices, score, sound effects). The picture and
the sound read the same syllable times, so mouths and voices stay in sync.
"""
import json, pathlib, sys, wave

import numpy as np

SR = 48000
ROOT = pathlib.Path(__file__).resolve().parent

# f0 (Hz), formant scale, syllables per second, breathiness, vibrato depth
VOICES = {
    "ananas": dict(f0=108, fs=0.92, rate=6.2, breath=0.05, vib=0.012, gain=1.0),
    "moozi":  dict(f0=175, fs=1.08, rate=7.6, breath=0.04, vib=0.010, gain=0.95),
    "holu":   dict(f0=300, fs=1.30, rate=7.0, breath=0.06, vib=0.018, gain=0.9),
    "kermo":  dict(f0=215, fs=1.18, rate=8.2, breath=0.35, vib=0.030, gain=0.95),
    "tooti":  dict(f0=340, fs=1.35, rate=8.0, breath=0.05, vib=0.015, gain=0.9),
}
VOWELS = [(800, 1200, 2500), (500, 1800, 2500), (320, 2200, 2900), (520, 900, 2400), (360, 800, 2300)]
PUNCT = "،.!?؟…"


def rng(seed):
    return np.random.default_rng(seed)


# ---------------------------------------------------------------- syllables
def phrases(text):
    """Split a line into (chars, end-punctuation) phrases."""
    out, cur = [], ""
    for ch in text:
        if ch in PUNCT:
            if cur.strip():
                out.append([cur.strip(), ch])
            elif out:
                out[-1][1] += ch
            cur = ""
        else:
            cur += ch
    if cur.strip():
        out.append([cur.strip(), ""])
    return out


def syllables(line, seed):
    v = VOICES[line["who"]]
    r = rng(seed)
    ph = phrases(line["text"])
    pause = 0.16
    total = line["to"] - line["from"] - pause * (len(ph) - 1)
    weights = np.array([max(len(p[0].replace(" ", "")), 2) for p in ph], float)
    t = line["from"]
    syl = []
    for (chars, end), w in zip(ph, weights):
        span = total * w / weights.sum()
        n = max(2, int(round(span * v["rate"])))
        step = span / n
        for i in range(n):
            last = i >= n - 2
            bend = 0.0
            if last and ("?" in end or "؟" in end):
                bend = 0.35 if i == n - 1 else 0.15
            elif last and "!" in end:
                bend = 0.12
            elif last and ("." in end or "…" in end):
                bend = -0.12
            syl.append(dict(t=round(t + i * step + r.uniform(0, step * 0.12), 3),
                            d=round(step * r.uniform(0.62, 0.82), 3),
                            v=int(r.integers(0, 5)),
                            a=round(float(r.uniform(0.7, 1.0) * (1.15 if "!" in end and last else 1)), 2),
                            b=round(bend + float(r.uniform(-0.06, 0.06)), 3)))
        t += span + pause
    return syl


# ---------------------------------------------------------------- voice
def lowpass(x, cutoff):
    a = np.exp(-2 * np.pi * cutoff / SR)
    y = np.empty_like(x)
    acc = 0.0
    for i in range(len(x)):
        acc = (1 - a) * x[i] + a * acc
        y[i] = acc
    return y


def voice(line, syl, buf, seed):
    v = VOICES[line["who"]]
    r = rng(seed)
    for s in syl:
        n = int((s["d"] + 0.05) * SR)
        tt = np.arange(n) / SR
        f0 = v["f0"] * (1 + s["b"] * np.clip(tt / s["d"], 0, 1)) * (1 + v["vib"] * np.sin(2 * np.pi * 6.5 * tt))
        phase = 2 * np.pi * np.cumsum(f0) / SR
        F = [f * v["fs"] for f in VOWELS[s["v"]]]
        sig = np.zeros(n)
        mean_f0 = f0.mean()
        for k in range(1, int(5200 / mean_f0)):
            fk = k * mean_f0
            amp = sum(A / (1 + ((fk - Fi) / B) ** 2) for Fi, A, B in zip(F, (1.0, 0.55, 0.22), (90, 120, 180)))
            sig += amp / k ** 0.4 * np.sin(k * phase)
        sig /= np.max(np.abs(sig)) + 1e-9
        noise = r.standard_normal(n)
        noise = np.diff(noise, prepend=0) * 0.5
        sig = sig * (1 - v["breath"]) + noise * v["breath"] * 0.6
        # consonant click at the onset
        cn = int(0.014 * SR)
        sig[:cn] += r.standard_normal(cn) * 0.35 * np.linspace(1, 0, cn)
        env = np.minimum(1, tt / 0.012) * np.clip((s["d"] + 0.04 - tt) / 0.045, 0, 1)
        i0 = int(s["t"] * SR)
        seg = sig * env * s["a"] * v["gain"] * 0.32
        buf[i0:i0 + n] += seg[: max(0, len(buf) - i0)]


# ---------------------------------------------------------------- instruments
def note_env(n, a=0.005, d=0.3, s=0.0, rel=None):
    t = np.arange(n) / SR
    e = np.minimum(1, t / a) * (s + (1 - s) * np.exp(-t / d))
    if rel:
        e *= np.clip((n / SR - t) / rel, 0, 1)
    return e


def additive(f, dur, partials, bright=3000):
    n = int(dur * SR)
    t = np.arange(n) / SR
    sig = np.zeros(n)
    for k, amp in partials:
        if f * k < 12000:
            sig += amp / (1 + (f * k / bright) ** 2) * np.sin(2 * np.pi * f * k * t)
    return sig


def saw(f, dur, bright=1800, detune=(0.997, 1.003)):
    out = 0
    for dt in detune:
        out = out + additive(f * dt, dur, [(k, 1 / k) for k in range(1, 40)], bright)
    return out / len(detune)


def midi(m):
    return 440 * 2 ** ((m - 69) / 12)


def add(buf, t, sig, gain=1.0):
    i0 = int(t * SR)
    if i0 >= len(buf):
        return
    seg = sig[: len(buf) - i0] * gain
    buf[i0:i0 + len(seg)] += seg


def kick(gain=1.0):
    n = int(0.35 * SR); t = np.arange(n) / SR
    f = 45 + 110 * np.exp(-t / 0.04)
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t / 0.12) * gain


def snare(r):
    n = int(0.22 * SR); t = np.arange(n) / SR
    nz = np.diff(r.standard_normal(n), prepend=0)
    return (nz * 0.5 + np.sin(2 * np.pi * 190 * t) * 0.5) * np.exp(-t / 0.06)


def hat(r, d=0.04):
    n = int(0.08 * SR); t = np.arange(n) / SR
    nz = np.diff(np.diff(r.standard_normal(n), prepend=0), prepend=0)
    return nz * np.exp(-t / d) * 0.25


CHORDS_BOUNCY = [[48, 52, 55, 60, 64], [45, 52, 57, 60, 64], [41, 53, 57, 60, 65], [43, 55, 59, 62, 67]]
CHORDS_TENDER = [[45, 52, 57, 60, 64], [41, 53, 57, 60, 65], [48, 52, 55, 60, 64], [43, 55, 59, 62, 67]]
CHORDS_NIGHT = [[38, 50, 53, 57], [34, 50, 53, 58], [31, 50, 55, 58], [33, 49, 52, 57]]


def music(sec, buf, seed):
    r = rng(seed)
    a, b, style = sec["from"], sec["to"], sec["style"]
    if style == "bouncy":
        beat = 60 / 120
        bar = beat * 4
        t = a
        i = 0
        while t < b - 0.05:
            ch = CHORDS_BOUNCY[(i // 4) % 4]
            pos = i % 4
            # bass: root on 1, fifth on 3
            if pos in (0, 2):
                f = midi(ch[0] - 12 + (7 if pos == 2 else 0))
                add(buf, t, saw(f, beat * 0.9, 900, (1,)) * note_env(int(beat * 0.9 * SR), d=0.18), 0.30)
                add(buf, t, kick(), 0.55)
            else:
                add(buf, t, snare(r), 0.18)
            # marimba: two 8ths per beat from chord tones
            for h in range(2):
                if r.random() < 0.8:
                    m = ch[1 + int(r.integers(0, len(ch) - 1))] + 12
                    sig = additive(midi(m), 0.35, [(1, 1), (4, 0.25), (10, 0.05)], 6000) * note_env(int(0.35 * SR), d=0.09)
                    add(buf, t + h * beat / 2, sig, 0.11)
                add(buf, t + h * beat / 2, hat(r), 0.5)
            t += beat
            i += 1
    elif style == "tender":
        beat = 60 / 120
        t = a
        i = 0
        while t < b - 0.05:
            ch = CHORDS_TENDER[(i // 4) % 4]
            if i % 4 == 0:
                d = min(beat * 4, b - t) + 0.6
                for m in ch[1:4]:
                    add(buf, t, saw(midi(m), d, 1100) * note_env(int(d * SR), a=0.4, d=9, s=0.7, rel=0.6), 0.05)
                add(buf, t, additive(midi(ch[0] - 12), d, [(1, 1), (2, 0.3)], 600) * note_env(int(d * SR), a=0.1, d=2.5), 0.22)
            m = ch[1 + int(r.integers(0, len(ch) - 1))] + 12
            add(buf, t, additive(midi(m), 0.9, [(1, 1), (3, 0.15)], 5000) * note_env(int(0.9 * SR), d=0.35), 0.08)
            t += beat
            i += 1
    elif style == "night":
        beat = 60 / 84
        t = a
        i = 0
        walk = [0, 3, 5, 6, 7, 6, 5, 3]
        while t < b - 0.05:
            ch = CHORDS_NIGHT[(i // 8) % 4]
            if i % 8 == 0:
                d = min(beat * 8, b - t) + 0.8
                for m in ch[1:]:
                    add(buf, t, saw(midi(m), d, 700) * note_env(int(d * SR), a=0.8, d=20, s=0.8, rel=0.8), 0.045)
            # sneaky pizzicato walk
            m = ch[0] + walk[i % 8]
            add(buf, t, additive(midi(m), 0.3, [(1, 1), (2, 0.5), (3, 0.3), (5, 0.1)], 1500) * note_env(int(0.3 * SR), d=0.07), 0.32)
            if i % 2 == 1:
                add(buf, t, hat(r, 0.02), 0.35)
            t += beat
            i += 1
        # crickets
        t = a + 0.3
        while t < b:
            n = int(0.18 * SR); tt = np.arange(n) / SR
            chirp = np.sin(2 * np.pi * 4600 * tt) * (0.5 + 0.5 * np.sign(np.sin(2 * np.pi * 32 * tt))) * np.exp(-tt / 0.08)
            add(buf, t, chirp, 0.025)
            t += r.uniform(0.6, 1.4)
    elif style == "end":
        d = b - a + 0.5
        add(buf, a, saw(midi(26), d, 400) * note_env(int(d * SR), a=0.05, d=1.4, s=0.2, rel=1.0), 0.25)


# ---------------------------------------------------------------- effects
def sfx(name, r):
    if name == "impact":
        n = int(1.2 * SR); t = np.arange(n) / SR
        f = 30 + 120 * np.exp(-t / 0.08)
        boom = np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t / 0.35)
        crack = np.diff(r.standard_normal(n), prepend=0) * np.exp(-t / 0.05) * 0.4
        return (boom + crack) * 0.9
    if name == "whoosh":
        n = int(0.6 * SR); t = np.arange(n) / SR
        nz = r.standard_normal(n)
        env = np.sin(np.pi * t / t[-1]) ** 2
        return (nz - lowpass(nz, 600)) * env * 0.25
    if name == "pop":
        n = int(0.14 * SR); t = np.arange(n) / SR
        f = 380 + 900 * t / t[-1]
        return np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t / 0.04) * 0.6
    if name == "tick":
        n = int(0.05 * SR); t = np.arange(n) / SR
        return np.sin(2 * np.pi * 2400 * t) * np.exp(-t / 0.01) * 0.4
    if name == "tap":
        n = int(0.04 * SR); t = np.arange(n) / SR
        return np.diff(r.standard_normal(n), prepend=0) * np.exp(-t / 0.006) * 0.4
    if name == "ding":
        n = int(1.0 * SR); t = np.arange(n) / SR
        return (np.sin(2 * np.pi * 1568 * t) + 0.5 * np.sin(2 * np.pi * 2352 * t)) * np.exp(-t / 0.3) * 0.3
    if name == "sparkle":
        out = np.zeros(int(0.9 * SR))
        for k, m in enumerate([84, 88, 91, 96]):
            sig = additive(midi(m), 0.5, [(1, 1), (2.76, 0.3)], 9000) * note_env(int(0.5 * SR), d=0.15)
            i0 = int(k * 0.07 * SR)
            out[i0:i0 + len(sig)] += sig[: len(out) - i0]
        return out * 0.22
    if name == "rattle":
        out = np.zeros(int(0.7 * SR))
        for k in range(6):
            n = int(0.05 * SR); t = np.arange(n) / SR
            knock = np.sin(2 * np.pi * r.uniform(180, 260) * t) * np.exp(-t / 0.015)
            i0 = int((k * 0.1 + r.uniform(0, 0.03)) * SR)
            out[i0:i0 + n] += knock
        return out * 0.5
    if name == "typing":
        out = np.zeros(int(2.0 * SR))
        for k in range(6):
            n = int(0.03 * SR); t = np.arange(n) / SR
            click = np.diff(r.standard_normal(n), prepend=0) * np.exp(-t / 0.004) + np.sin(2 * np.pi * 1800 * t) * np.exp(-t / 0.008) * 0.4
            i0 = int((k * 0.32 + r.uniform(0, 0.04)) * SR)
            out[i0:i0 + n] += click
        return out * 0.45
    if name == "sting":
        d = 2.8
        n = int(d * SR); t = np.arange(n) / SR
        chord = sum(saw(midi(m), d, 2200) for m in (38, 50, 53, 56, 62))
        hit = np.sin(2 * np.pi * np.cumsum(55 + 60 * np.exp(-t / 0.05)) / SR) * np.exp(-t / 0.5)
        return chord * note_env(n, a=0.01, d=0.9, s=0.15, rel=0.8) * 0.18 + hit * 0.7
    raise ValueError(name)


def reverb(x, seconds=1.2, mix=0.18, seed=7):
    r = rng(seed)
    n = int(seconds * SR)
    ir = r.standard_normal(n) * np.exp(-np.arange(n) / SR / (seconds / 5))
    ir = lowpass(ir, 5000)
    ir /= np.sqrt(np.sum(ir ** 2))
    L = len(x) + n
    nfft = 1 << (L - 1).bit_length()
    wet = np.fft.irfft(np.fft.rfft(x, nfft) * np.fft.rfft(ir, nfft), nfft)[: len(x)]
    return x + wet * mix


def write_wav(path, x):
    x = np.clip(x, -1, 1)
    with wave.open(str(path), "wb") as w:
        w.setnchannels(1); w.setsampwidth(2); w.setframerate(SR)
        w.writeframes((x * 32767).astype("<i2").tobytes())


def main(ep):
    d = ROOT / ep
    S = json.loads((d / "script.json").read_text())
    n = int((S["duration"] + 0.5) * SR)
    for i, line in enumerate(S["lines"]):
        line["syl"] = syllables(line, 100 + i)
    (d / "timeline.js").write_text("window.TL = " + json.dumps(S, ensure_ascii=False) + ";\n")

    vox = np.zeros(n)
    for i, line in enumerate(S["lines"]):
        voice(line, line["syl"], vox, 200 + i)
    vox = reverb(vox, 0.6, 0.10)

    mus = np.zeros(n)
    for i, sec in enumerate(S["music"]):
        music(sec, mus, 300 + i)
    # duck the score under dialogue
    duck = np.ones(n)
    for line in S["lines"]:
        a, b = int((line["from"] - 0.1) * SR), int((line["to"] + 0.15) * SR)
        duck[a:b] = 0.5
    duck = lowpass(duck, 8)
    mus = reverb(mus * duck, 1.4, 0.22, 11)

    fx = np.zeros(n)
    r = rng(400)
    for e in S["sfx"]:
        add(fx, e["t"], sfx(e["sound"], r), e.get("gain", 1.0))
    fx = reverb(fx, 0.9, 0.12, 13)

    mix = vox * 1.0 + mus * 0.75 + fx * 0.8
    mix = np.tanh(mix * 1.2) / 1.2
    (d / "audio").mkdir(exist_ok=True)
    write_wav(d / "audio" / "mix.wav", mix[: int(S["duration"] * SR)])
    print(f"{ep}: {len(S['lines'])} lines, {sum(len(l['syl']) for l in S['lines'])} syllables -> timeline.js, audio/mix.wav")


if __name__ == "__main__":
    main(sys.argv[1] if len(sys.argv) > 1 else "ep1")
