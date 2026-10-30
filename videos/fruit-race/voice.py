"""Cartoon babble voices with a Persian feel.

Each line's Persian text is turned into gibberish syllables built from its own letters
(خ ش ر س ...), Persian vowels (آ ا ای او اِ اُ) and Persian word stress (last syllable).
Every character has its own pitch, timbre, tempo and melody, so they sound like who they are.
"""
import numpy as np

SR = 48000

# ---------------------------------------------------------------- characters
# f0 Hz, formant scale, syllables/s, breath, vibrato (depth, rate), melody style, giggle at "!"
CAST = {
    # sweet, bubbly little girl: high, sing-song scoops, breathy, tiny giggles
    "holu":   dict(f0=410, fs=1.52, rate=4.4, breath=0.10, vib=(0.022, 6.2), style="singsong", giggle=True,  gain=0.95, nasal=0.0),
    # hyper, squeaky best friend: highest, big pitch leaps, staccato, giggles
    "tooti":  dict(f0=470, fs=1.60, rate=5.0, breath=0.06, vib=(0.015, 7.0), style="bouncy",   giggle=True,  gain=0.9,  nasal=0.1),
    # cool show-off: mid-low, lazy drawl, long vowels sliding down, a bit of vocal fry
    "moozi":  dict(f0=165, fs=1.06, rate=3.8, breath=0.05, vib=(0.010, 5.0), style="drawl",    giggle=False, gain=1.0,  nasal=0.15),
    # pompous boss: deep, slow, grand vibrato, heavy stress
    "ananas": dict(f0=96,  fs=0.86, rate=3.5, breath=0.04, vib=(0.035, 4.8), style="pompous",  giggle=False, gain=1.05, nasal=0.0),
    # sneaky worm: nasal, hissy, sliding up at the ends, whispery
    "kermo":  dict(f0=235, fs=1.25, rate=4.3, breath=0.35, vib=(0.030, 8.0), style="sneaky",   giggle=False, gain=0.95, nasal=0.6),
}

# vowel formants (adult baseline, scaled per character)
VOWELS = {
    "a": (720, 1550, 2600), "â": (640, 980, 2550), "e": (520, 1850, 2600),
    "i": (300, 2300, 3000), "o": (470, 870, 2500), "u": (330, 800, 2300),
}
MOUTH = {"a": 1.0, "â": 1.0, "e": 0.75, "o": 0.7, "i": 0.5, "u": 0.45}

LETTERS = {
    "ب": "b", "پ": "p", "ت": "t", "ث": "s", "ج": "j", "چ": "ch", "ح": "h", "خ": "kh", "د": "d", "ذ": "z", "ر": "r",
    "ز": "z", "ژ": "zh", "س": "s", "ش": "sh", "ص": "s", "ض": "z", "ط": "t", "ظ": "z", "غ": "gh", "ف": "f", "ق": "gh",
    "ک": "k", "گ": "g", "ل": "l", "م": "m", "ن": "n", "ه": "h", "ك": "k",
}
DIGITS = "۰۱۲۳۴۵۶۷۸۹"
ONES = ["", "یک", "دو", "سه", "چهار", "پنج", "شش", "هفت", "هشت", "نه"]
TENS = ["", "ده", "بیست", "سی", "چهل", "پنجاه", "شصت", "هفتاد", "هشتاد", "نود"]
HUNDREDS = ["", "صد", "دویست", "سیصد", "چهارصد", "پانصد", "ششصد", "هفتصد", "هشتصد", "نهصد"]


def number_words(n):
    if n == 0:
        return "صفر"
    h, t, o = n // 100 % 10, n // 10 % 10, n % 10
    parts = [HUNDREDS[h], TENS[t], ONES[o]]
    return " و ".join(p for p in parts if p)


def normalise(text):
    out, num = "", ""
    for ch in text + " ":
        if ch in DIGITS or ch.isdigit():
            num += str(DIGITS.index(ch)) if ch in DIGITS else ch
            continue
        if num:
            out += number_words(int(num) % 1000)
            num = ""
        out += ch
    return out.replace("‌", "")


# ---------------------------------------------------------------- text -> syllables
def word_syllables(word, r):
    """Persian word -> list of (onset, vowel, coda); short vowels are guessed like a reader would."""
    toks = []
    for i, ch in enumerate(word):
        if ch in "اآ":
            toks.append(("V", "â" if (i > 0 or ch == "آ") else "a"))
        elif ch == "و":
            toks.append(("V", "u") if 0 < i and toks and toks[-1][0] == "C" else ("C", "v"))
        elif ch == "ی":
            toks.append(("V", "i") if i > 0 and toks and toks[-1][0] == "C" else ("C", "y"))
        elif ch in LETTERS:
            toks.append(("C", LETTERS[ch]))
    if not toks:
        return []
    # word-final ه after a consonant reads as "e"
    if word.endswith("ه") and len(toks) > 1 and toks[-2][0] == "C":
        toks[-1] = ("V", "e")
    sylls, i = [], 0
    while i < len(toks):
        onset = ""
        if toks[i][0] == "C":
            onset = toks[i][1]; i += 1
        if i < len(toks) and toks[i][0] == "V":
            vowel = toks[i][1]; i += 1
        else:
            vowel = r.choice(["a", "e", "a", "e", "o"])
        coda = ""
        # keep one consonant as coda if the next one is followed by another consonant or ends the word
        if i < len(toks) and toks[i][0] == "C" and (i + 1 >= len(toks) or toks[i + 1][0] == "C"):
            coda = toks[i][1]; i += 1
        sylls.append((onset, vowel, coda))
    return sylls


def phrase_split(text):
    """-> list of (words, end punctuation)"""
    out, cur = [], ""
    for ch in text:
        if ch in "،.!?؟…:":
            if cur.strip():
                out.append([cur.split(), ch])
            elif out:
                out[-1][1] += ch
            cur = ""
        else:
            cur += ch
    if cur.strip():
        out.append([cur.split(), ""])
    return out


def plan_line(line, seed):
    """Lay the line's syllables out in time. Returns [{t,d,on,v,coda,f,a,stress,end}]"""
    c = CAST[line["who"]]
    r = np.random.default_rng(seed)
    text = normalise(line["text"])
    phrases = phrase_split(text)
    words = []
    for pi, (ws, end) in enumerate(phrases):
        for wi, w in enumerate(ws):
            sy = word_syllables(w, r)
            if sy:
                words.append(dict(s=sy, end=end if wi == len(ws) - 1 else "", last=wi == len(ws) - 1))
    if not words:
        return []
    giggle = c["giggle"] and "!" in line["text"]
    dur = line["to"] - line["from"] - (0.42 if giggle else 0)
    gaps = sum(0.30 if "…" in w["end"] or w["end"].count(".") > 1 else 0.20 if w["end"] else 0.06 for w in words[:-1])
    budget = max(1, int((dur - gaps) * c["rate"]))
    # too many syllables for a calm tempo: keep each word's first and stressed (last) syllable
    total = sum(len(w["s"]) for w in words)
    if total > budget:
        for w in words:
            if len(w["s"]) > 2:
                w["s"] = [w["s"][0], w["s"][-1]]
        total = sum(len(w["s"]) for w in words)
    if total > budget:
        for w in words:
            if len(w["s"]) > 1 and total > budget:
                w["s"] = [w["s"][-1]]; total -= 1
    unit = (dur - gaps) / sum(len(w["s"]) + 0.6 for w in words)   # stressed syllables get 1.6 units
    t = line["from"]
    out = []
    n_all = sum(len(w["s"]) for w in words)
    k = 0
    for wi, w in enumerate(words):
        for si, (on, v, coda) in enumerate(w["s"]):
            stress = si == len(w["s"]) - 1
            d = unit * (1.6 if stress else 1.0)
            if c["style"] == "drawl" and stress:
                d *= 1.15
            out.append(dict(t=t, d=d, on=on, v=v, coda=coda, stress=stress, end=w["end"] if stress else "",
                            pos=k / max(1, n_all - 1), first=k == 0, a=1.0 if stress else 0.8))
            t += d; k += 1
        if wi < len(words) - 1:
            e = w["end"]
            t += 0.30 if ("…" in e or e.count(".") > 1) else 0.20 if e else 0.06
    if giggle:
        for g in range(2):
            out.append(dict(t=t + 0.06 + g * 0.17, d=0.13, on="h", v="i", coda="", stress=False, end="giggle", pos=1, first=False, a=0.7))
    # pitch melody per character
    for i, s in enumerate(out):
        s["f"] = melody(c, s, i, r, "?" in line["text"] or "؟" in line["text"], "!" in line["text"])
    return out


def melody(c, s, i, r, question, excl):
    """Returns (start, end) pitch multipliers for the syllable."""
    decl = 1 - 0.10 * s["pos"]                        # phrases drift down
    up = 1.0 + (0.16 if s["stress"] else 0) + (0.08 if excl and s["first"] else 0)
    st = c["style"]
    if s["end"] == "giggle":
        return (1.45 + 0.1 * (i % 2), 1.25)
    if st == "singsong":
        a = up * decl * (1.08 if i % 2 else 0.97)
        start, end = a * 1.14, a                      # cute downward scoop on every syllable
    elif st == "bouncy":
        a = up * decl * r.choice([0.9, 1.0, 1.15, 1.3])
        start, end = a, a * 1.05
    elif st == "drawl":
        a = up * decl * 0.98
        start, end = a * 1.08, a * (0.86 if s["stress"] else 0.95)   # lazy slide down
    elif st == "pompous":
        a = up * decl * (1.12 if s["first"] else 1.0)
        start, end = a, a * 0.93
    else:  # sneaky
        a = up * decl * (0.95 + 0.05 * (i % 3))
        start, end = a * 0.94, a * 1.12               # creeping up
    if question and s["pos"] > 0.75:
        end *= 1.35
    elif s["end"] and ("." in s["end"]) and st != "sneaky":
        end *= 0.88
    return (float(start), float(end))


# ---------------------------------------------------------------- sound sources
def band_noise(n, lo, hi, r):
    x = r.standard_normal(n)
    X = np.fft.rfft(x)
    f = np.fft.rfftfreq(n, 1 / SR)
    X *= np.exp(-0.5 * ((np.clip(f, lo, hi) - f) / (0.25 * (hi - lo) + 1)) ** 2)
    y = np.fft.irfft(X, n)
    return y / (np.max(np.abs(y)) + 1e-9)


def voiced(n, f_start, f_end, F, c, nasal=0.0, low=False):
    t = np.arange(n) / SR
    ramp = np.linspace(0, 1, n) ** 0.8
    vd, vr = c["vib"]
    f0 = (f_start + (f_end - f_start) * ramp) * (1 + vd * np.sin(2 * np.pi * vr * t))
    phase = 2 * np.pi * np.cumsum(f0) / SR
    mf = f0.mean()
    sig = np.zeros(n)
    peaks = list(zip(F, (1.0, 0.6, 0.25), (90, 130, 190)))
    if nasal:
        peaks.append((260 * c["fs"], 0.9 * nasal, 70))
    for k in range(1, int(6000 / mf)):
        fk = k * mf
        amp = sum(A / (1 + ((fk - Fi) / B) ** 2) for Fi, A, B in peaks)
        if low:
            amp = 1 / (1 + (fk / 400) ** 2)
        sig += amp / k ** 0.3 * np.sin(k * phase)
    return sig / (np.max(np.abs(sig)) + 1e-9)


STOPS = {"p": (500, 1500, False), "b": (300, 1200, True), "t": (3000, 6000, False), "d": (2200, 4500, True),
         "k": (1400, 2800, False), "g": (1100, 2400, True), "ch": (2500, 5500, False), "j": (2000, 4500, True)}
FRIC = {"s": (4500, 9000, False), "sh": (2200, 5000, False), "kh": (900, 2600, False), "f": (1500, 7000, False),
        "h": (500, 4000, False), "z": (4200, 8500, True), "zh": (2200, 4800, True), "gh": (700, 2000, True), "v": (1200, 5000, True)}
SONOR = {"m": "nasal", "n": "nasal", "l": "liquid", "r": "tap", "y": "glide"}


def consonant(ph, c, f0, F, r, coda=False):
    """Returns the consonant's samples."""
    if not ph:
        return np.zeros(0)
    k = 0.75 if coda else 1.0
    if ph in STOPS:
        lo, hi, vce = STOPS[ph]
        clo = np.zeros(int(0.035 * SR * k))
        if vce:
            clo = voiced(len(clo), f0, f0, F, c, low=True) * 0.25
        bn = int((0.03 if ph in ("ch", "j") else 0.014) * SR)
        burst = band_noise(bn, lo, hi, r) * np.exp(-np.arange(bn) / SR / 0.008) * (0.55 if vce else 0.8)
        return np.concatenate([clo, burst])
    if ph in FRIC:
        lo, hi, vce = FRIC[ph]
        n = int((0.085 if ph in ("s", "sh", "kh") else 0.06) * SR * k * (1.6 if c["style"] == "sneaky" and ph in ("s", "sh", "z") else 1))
        nz = band_noise(n, lo, hi, r) * (0.45 if ph != "h" else 0.25)
        if ph == "kh":   # Persian خ: raspy, rattly
            nz *= 1 + 0.6 * np.sign(np.sin(2 * np.pi * 38 * np.arange(n) / SR))
        if vce:
            nz = nz * 0.5 + voiced(n, f0, f0, F, c, low=True) * 0.35
        env = np.minimum(1, np.arange(n) / (0.012 * SR)) * np.minimum(1, (n - np.arange(n)) / (0.012 * SR))
        return nz * env
    kind = SONOR.get(ph, "liquid")
    n = int(0.05 * SR * k)
    if kind == "nasal":
        return voiced(n, f0, f0, F, c, low=True) * 0.45
    if kind == "tap":   # Persian ر: a quick tap, rolled at word ends
        taps = 2 if coda else 1
        seg = []
        for _ in range(taps):
            m = int(0.022 * SR)
            v = voiced(m, f0, f0, F, c) * 0.5
            v[m // 2 - int(0.004 * SR): m // 2 + int(0.004 * SR)] *= 0.15
            seg.append(v)
        return np.concatenate(seg)
    if kind == "glide":
        return voiced(n, f0, f0, VOW(c, "i"), c) * 0.5
    return voiced(n, f0, f0, (360 * c["fs"], 1300 * c["fs"], 2700 * c["fs"]), c) * 0.55   # l


def VOW(c, v):
    return tuple(x * c["fs"] for x in VOWELS[v])


def fade(x, ms=4):
    m = min(len(x) // 2, int(ms / 1000 * SR))
    if m > 1:
        x = x.copy(); x[:m] *= np.linspace(0, 1, m); x[-m:] *= np.linspace(1, 0, m)
    return x


def render_line(line, plan, buf, seed):
    c = CAST[line["who"]]
    r = np.random.default_rng(seed)
    for s in plan:
        f_a, f_b = c["f0"] * s["f"][0], c["f0"] * s["f"][1]
        F = VOW(c, s["v"])
        on = fade(consonant(s["on"], c, f_a, F, r))
        co = fade(consonant(s["coda"], c, f_b, F, r, coda=True))
        vn = max(int(0.05 * SR), int(s["d"] * SR) - len(on) - len(co))
        vow = voiced(vn, f_a, f_b, F, c, nasal=c["nasal"])
        if c["breath"]:
            vow = vow * (1 - c["breath"]) + band_noise(vn, 400, 6000, r) * c["breath"] * 0.7
        if c["style"] == "drawl" and s["stress"] and s["end"]:
            # vocal fry at the end of Moozi's phrases
            m = vn // 3
            vow[-m:] *= 0.6 + 0.4 * (np.sin(2 * np.pi * 34 * np.arange(m) / SR) > 0)
        a = int(0.008 * SR) if s["on"] else int(0.02 * SR); z = int(0.03 * SR)
        env = np.ones(vn); env[:a] = np.linspace(0, 1, a); env[-z:] *= np.linspace(1, 0, z)
        if s["end"] == "giggle":
            env *= np.exp(-np.arange(vn) / SR / 0.06)
        seg = np.concatenate([on, vow * env * (1.0 if s["stress"] else 0.82), co]) * s["a"] * c["gain"] * 0.30
        i0 = int(s["t"] * SR)
        seg = seg[: max(0, len(buf) - i0)]
        buf[i0:i0 + len(seg)] += seg
