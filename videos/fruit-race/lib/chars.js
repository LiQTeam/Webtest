/* Miveh-Star shared library: easing, lip-sync, and the cast drawn as SVG strings.
   Every function is a pure function of its arguments (and of t), so any frame renders alone. */

const clamp = (x, a = 0, b = 1) => Math.min(b, Math.max(a, x));
const lerp = (a, b, k) => a + (b - a) * k;
const prog = (t, a, b) => clamp((t - a) / (b - a));
const ease = {
  io: k => (k < 0.5 ? 4 * k * k * k : 1 - Math.pow(-2 * k + 2, 3) / 2),
  out: k => 1 - Math.pow(1 - k, 3),
  in: k => k * k * k,
  back: k => { const c = 1.9; return 1 + (c + 1) * Math.pow(k - 1, 3) + c * Math.pow(k - 1, 2); },
};
// damped spring from 0 to 1 starting at time a (closed form, no state)
const spring = (t, a, f = 3.2, z = 0.32) => {
  if (t <= a) return 0;
  const x = t - a, w = 2 * Math.PI * f;
  return 1 - Math.exp(-z * w * x) * Math.cos(w * Math.sqrt(1 - z * z) * x);
};
const rnd = s => { const x = Math.sin(s * 127.1 + 311.7) * 43758.5453; return x - Math.floor(x); };
const f1 = n => n.toFixed(1);

// ---------------------------------------------------------------- lip-sync and blinks
function talk(who, t) {
  let open = 0, v = 0;
  for (const L of TL.lines) {
    if (L.who !== who || t < L.from - 0.05 || t > L.to + 0.1) continue;
    for (const s of L.syl) {
      if (t >= s.t && t <= s.t + s.d) {
        const e = Math.pow(Math.sin(Math.PI * (t - s.t) / s.d), 0.7) * s.a;
        if (e > open) { open = e; v = s.v; }
      }
    }
  }
  return { open: clamp(open), v };
}
const speaking = (who, t) => TL.lines.some(L => L.who === who && t >= L.from && t <= L.to);
function blink(t, seed) {
  const period = 2.8 + rnd(seed) * 1.6;
  const ph = ((t + rnd(seed + 9) * period) % period) / period;
  return ph < 0.05 ? Math.sin(Math.PI * ph / 0.05) : 0;
}

// ---------------------------------------------------------------- moods
const MOODS = {
  neutral: { bi: 0, bo: 0, smile: 0.3, lid: 0 },
  happy: { bi: -4, bo: 4, smile: 0.9, lid: 0 },
  proud: { bi: 6, bo: -6, smile: 0.8, lid: 0.3 },
  worried: { bi: -16, bo: 6, smile: -0.4, lid: 0 },
  sad: { bi: -18, bo: 8, smile: -0.7, lid: 0.25 },
  angry: { bi: 14, bo: -4, smile: -0.4, lid: 0.15 },
  sly: { bi: 10, bo: -10, smile: 0.7, lid: 0.5 },
  shocked: { bi: -22, bo: -14, smile: -0.1, lid: 0, gasp: 0.55 },
  stern: { bi: 10, bo: -2, smile: 0.05, lid: 0.1 },
};
const mood = (name, extra = {}) => Object.assign({}, MOODS[name] || MOODS.neutral, extra);

// ---------------------------------------------------------------- face parts
function eye(x, y, r, look, b, lid, lidColor, outline, small) {
  const ry = r * 1.12;
  let s = '';
  if (b > 0.45) {
    s += `<path d="M${f1(x - r)},${f1(y)} Q${f1(x)},${f1(y + r * 0.45)} ${f1(x + r)},${f1(y)}" stroke="${outline}" stroke-width="6" fill="none" stroke-linecap="round"/>`;
    return s;
  }
  const sy = ry * (1 - b * 1.6);
  s += `<ellipse cx="${f1(x)}" cy="${f1(y)}" rx="${f1(r)}" ry="${f1(sy)}" fill="#fff" stroke="${outline}" stroke-width="4"/>`;
  const pr = r * (small ? 0.36 : 0.56);
  const px = x + look[0] * r * 0.36, py = y + look[1] * r * 0.32;
  s += `<circle cx="${f1(px)}" cy="${f1(py)}" r="${f1(pr)}" fill="#1c0f22"/>`;
  s += `<circle cx="${f1(px - pr * 0.35)}" cy="${f1(py - pr * 0.4)}" r="${f1(pr * 0.34)}" fill="#fff"/>`;
  if (lid > 0.02) {
    const y0 = y - sy + lid * 2 * sy;
    const k = clamp((y0 - y) / sy, -0.999, 0.999);
    const hx = r * Math.sqrt(1 - k * k);
    s += `<path d="M${f1(x - hx)},${f1(y0)} A${f1(r)},${f1(sy)} 0 ${k > 0 ? 1 : 0} 1 ${f1(x + hx)},${f1(y0)} Z" fill="${lidColor}" stroke="${outline}" stroke-width="4"/>`;
  }
  return s;
}

function face(o) {
  // o: x,y (eye-line centre), gap, r, my (mouth offset), mw (mouth width), m (mood), open, look, blink, lidColor, outline, blush
  const m = o.m, g = o.gap, r = o.r;
  let s = '';
  if (o.blush) for (const sx of [-1, 1]) s += `<ellipse cx="${f1(o.x + sx * (g + r * 0.9))}" cy="${f1(o.y + r * 1.5)}" rx="${f1(r * 0.9)}" ry="${f1(r * 0.5)}" fill="${o.blush}" opacity="0.5"/>`;
  for (const sx of [-1, 1]) s += eye(o.x + sx * g, o.y, r, o.look || [0, 0], Math.max(o.blink || 0, sx < 0 ? o.wink || 0 : 0), m.lid, o.lidColor, o.outline, m.gasp);
  // brows: inner end moves by bi (positive = down/angry), outer by bo
  for (const sx of [-1, 1]) {
    const bx = o.x + sx * g, by = o.y - r * 1.62 - (o.browLift || 0);
    const ix = bx - sx * r * 0.85, ox = bx + sx * r * 0.85;
    s += `<path d="M${f1(ix)},${f1(by + m.bi)} Q${f1(bx)},${f1(by - 7 + (m.bi + m.bo) / 2)} ${f1(ox)},${f1(by + m.bo)}" stroke="${o.outline}" stroke-width="8" fill="none" stroke-linecap="round"/>`;
  }
  s += mouth(o.x, o.y + o.my, o.mw, Math.max(o.open || 0, m.gasp || 0), m.smile, o.outline);
  return s;
}

function mouth(x, y, w, open, smile, outline) {
  const h = 46;
  if (open < 0.06) {
    return `<path d="M${f1(x - w)},${f1(y)} Q${f1(x)},${f1(y + smile * w * 0.9)} ${f1(x + w)},${f1(y)}" stroke="${outline}" stroke-width="7" fill="none" stroke-linecap="round"/>`;
  }
  const top = y + smile * w * 0.25 - open * 4;
  const bot = y + Math.max(smile, -0.2) * w * 0.7 + open * h;
  const ww = w * (0.85 + open * 0.2);
  let s = `<path d="M${f1(x - ww)},${f1(y)} Q${f1(x)},${f1(top)} ${f1(x + ww)},${f1(y)} Q${f1(x)},${f1(bot)} ${f1(x - ww)},${f1(y)} Z" fill="#4a1018" stroke="${outline}" stroke-width="5" stroke-linejoin="round"/>`;
  s += `<ellipse cx="${f1(x)}" cy="${f1(lerp(y, bot, 0.62))}" rx="${f1(ww * 0.48)}" ry="${f1(Math.max(2, (bot - y) * 0.22))}" fill="#ff6f86"/>`;
  return s;
}

function arm(sx, sy, hx, hy, bend, col, glove = '#fff', outline = '#3a2416') {
  const cx = (sx + hx) / 2 + bend, cy = (sy + hy) / 2 + Math.abs(bend) * 0.4;
  return `<path d="M${f1(sx)},${f1(sy)} Q${f1(cx)},${f1(cy)} ${f1(hx)},${f1(hy)}" stroke="${outline}" stroke-width="18" fill="none" stroke-linecap="round"/>` +
    `<path d="M${f1(sx)},${f1(sy)} Q${f1(cx)},${f1(cy)} ${f1(hx)},${f1(hy)}" stroke="${col}" stroke-width="10" fill="none" stroke-linecap="round"/>` +
    `<circle cx="${f1(hx)}" cy="${f1(hy)}" r="19" fill="${glove}" stroke="${outline}" stroke-width="4"/>`;
}

function legs(xs, top, shoe = '#2a1d3a', outline = '#3a2416', lift = [0, 0]) {
  let s = '';
  xs.forEach((x, i) => {
    const fy = -lift[i];
    s += `<path d="M${f1(x)},${f1(top)} L${f1(x)},${f1(fy - 10)}" stroke="${outline}" stroke-width="16" stroke-linecap="round"/>`;
    s += `<path d="M${f1(x)},${f1(top)} L${f1(x)},${f1(fy - 10)}" stroke="#f3e9dc" stroke-width="8" stroke-linecap="round"/>`;
    s += `<ellipse cx="${f1(x + (x < 0 ? -12 : 12))}" cy="${f1(fy - 8)}" rx="34" ry="16" fill="${shoe}" stroke="${outline}" stroke-width="4"/>`;
  });
  return s;
}

// character wrapper: position, scale, flip, squash about the feet
function place(o, inner) {
  const sq = o.squash || 0, fl = o.flip ? -1 : 1, s = o.s || 1;
  return `<g transform="translate(${f1(o.x)},${f1(o.y)}) rotate(${f1(o.rot || 0)}) scale(${(s * fl * (1 + sq)).toFixed(4)},${(s * (1 - sq)).toFixed(4)})">${inner}</g>`;
}

// common per-frame state for a character
function charState(who, t, o, seed) {
  const tk = talk(who, t);
  const breathe = Math.sin(t * 2.2 + seed) * 0.012;
  return Object.assign({ open: tk.open, blink: blink(t, seed), look: [0, 0], m: mood('neutral') }, o, {
    squash: (o.squash || 0) + breathe + tk.open * 0.012,
  });
}

// ---------------------------------------------------------------- HOLU (peach)
function holu(t, o) {
  const c = charState('holu', t, o, 1);
  const out = '#b8463f';
  const handL = c.handL || [-150, -110], handR = c.handR || [150, -110];
  let s = legs([-50, 50], -60, '#ff5d8f', '#6b2a35');
  s += `<path d="M0,-355 C95,-385 172,-310 166,-200 C160,-88 86,-34 0,-34 C-86,-34 -160,-88 -166,-200 C-172,-310 -95,-385 0,-355 Z" fill="url(#gPeach)" stroke="${out}" stroke-width="5"/>`;
  s += `<path d="M2,-350 C-30,-300 -36,-180 -12,-60" stroke="#e0625a" stroke-width="6" fill="none" opacity="0.5" stroke-linecap="round"/>`;
  s += `<ellipse cx="-70" cy="-290" rx="34" ry="20" fill="#fff" opacity="0.35" transform="rotate(-30 -70 -290)"/>`;
  s += `<path d="M0,-356 q2,-24 10,-34" stroke="#6b4226" stroke-width="10" fill="none" stroke-linecap="round"/>`;
  s += `<path d="M10,-386 C40,-432 102,-426 122,-396 C92,-370 40,-366 10,-386 Z" fill="url(#gLeaf)" stroke="#2f8a43" stroke-width="4"/>`;
  s += `<path d="M16,-386 C50,-398 84,-400 112,-396" stroke="#2f8a43" stroke-width="3" fill="none"/>`;
  s += face({ x: 0, y: -222, gap: 52, r: 27, my: 70, mw: 30, m: c.m, open: c.open, look: c.look, blink: c.blink, lidColor: '#ffb48c', outline: '#4a1f1c', blush: '#ff5d7a' });
  if (c.prop) s += c.prop;
  s += arm(-140, -170, handL[0], handL[1], -30, '#ffb48c', '#fff', '#6b2a35');
  s += arm(140, -170, handR[0], handR[1], 30, '#ffb48c', '#fff', '#6b2a35');
  if (c.propFront) s += c.propFront;
  return place(c, s);
}

// ---------------------------------------------------------------- MOOZI (banana)
function moozi(t, o) {
  const c = charState('moozi', t, o, 2);
  const out = '#9c6f12';
  const handL = c.handL || [-130, -120], handR = c.handR || [120, -120];
  const gd = c.glasses === undefined ? 0 : c.glasses; // 0 = on, 1 = pushed down the nose
  let s = legs([-30, 42], -50, '#3b2a8f', '#3a2416');
  s += `<path d="M-8,-470 L-2,-512 L28,-514 L32,-458 Z" fill="#7a5a2b" stroke="#4a3416" stroke-width="4"/>`;
  s += `<path d="M-10,-475 C-112,-420 -122,-140 -40,-22 Q12,12 72,-22 C122,-150 92,-382 32,-456 Z" fill="url(#gBanana)" stroke="${out}" stroke-width="5"/>`;
  s += `<path d="M40,-438 C86,-360 96,-160 46,-44" stroke="#fff3a6" stroke-width="10" fill="none" opacity="0.5" stroke-linecap="round"/>`;
  s += `<ellipse cx="16" cy="-14" rx="16" ry="9" fill="#6b4a22"/>`;
  s += `<ellipse cx="-70" cy="-140" rx="7" ry="5" fill="#c8961e" opacity="0.6"/><ellipse cx="-55" cy="-110" rx="5" ry="4" fill="#c8961e" opacity="0.6"/>`;
  const fx = -8, fy = -322;
  s += face({ x: fx, y: fy, gap: 44, r: 25, my: 72, mw: 30, m: c.m, open: c.open, look: c.look, blink: c.blink, wink: c.wink, lidColor: '#ffe066', outline: '#3d2a0a', browLift: gd * 4 });
  // sunglasses slide down the nose by gd
  const gy = fy - 22 + gd * 40;
  s += `<g transform="translate(${fx},${f1(gy)})">` +
    `<rect x="-86" y="0" width="74" height="46" rx="16" fill="#121218" stroke="#000" stroke-width="3"/>` +
    `<rect x="12" y="0" width="74" height="46" rx="16" fill="#121218" stroke="#000" stroke-width="3"/>` +
    `<path d="M-12,12 Q0,4 12,12" stroke="#000" stroke-width="6" fill="none"/>` +
    `<path d="M-74,10 L-46,10 L-70,30 Z M24,10 L52,10 L28,30 Z" fill="#fff" opacity="0.28"/></g>`;
  // gold chain
  let chain = '';
  for (let i = 0; i <= 12; i++) {
    const k = i / 12, x = lerp(-78, 78, k), y = -218 + Math.sin(Math.PI * k) * 52;
    chain += `<circle cx="${f1(x)}" cy="${f1(y)}" r="7" fill="#f5c542" stroke="#a87a12" stroke-width="2"/>`;
  }
  s += chain + `<circle cx="0" cy="-152" r="20" fill="url(#gGold)" stroke="#a87a12" stroke-width="3"/><text x="0" y="-144" font-size="22" font-weight="900" text-anchor="middle" fill="#7a5410" font-family="Vazirmatn">$</text>`;
  if (c.prop) s += c.prop;
  s += arm(-90, -210, handL[0], handL[1], -26, '#ffd84a', '#fff', '#3a2416');
  s += arm(78, -210, handR[0], handR[1], 26, '#ffd84a', '#fff', '#3a2416');
  if (c.propFront) s += c.propFront;
  return place(c, s);
}

// ---------------------------------------------------------------- MR. ANANAS (pineapple)
function ananas(t, o) {
  const c = charState('ananas', t, o, 3);
  const handL = c.handL || [-160, -110], handR = c.handR || [160, -110];
  let s = legs([-55, 55], -40, '#1d1d2b', '#3a2416');
  const leaves = [[-58, 120], [-36, 165], [-16, 195], [0, 215], [16, 195], [36, 165], [58, 120]];
  leaves.forEach(([a, h], i) => {
    const w = 30;
    s += `<path transform="translate(0,-350) rotate(${a + Math.sin(t * 2 + i) * 2})" d="M0,0 C${w},${-h * 0.4} ${w * 0.3},${-h * 0.9} 0,${-h} C${-w * 0.3},${-h * 0.9} ${-w},${-h * 0.4} 0,0 Z" fill="${i % 2 ? '#3fa34d' : '#5cc46a'}" stroke="#23702f" stroke-width="4"/>`;
  });
  s += `<ellipse cx="0" cy="-190" rx="152" ry="182" fill="url(#gAnanas)" stroke="#a5581a" stroke-width="5"/>`;
  s += `<g clip-path="url(#cAnanas)" opacity="0.55">`;
  for (let k = -8; k <= 8; k++) {
    s += `<path d="M${k * 46 - 200},-380 l400,400" stroke="#c4741f" stroke-width="5"/><path d="M${k * 46 + 200},-380 l-400,400" stroke="#c4741f" stroke-width="5"/>`;
  }
  s += `</g><ellipse cx="-62" cy="-290" rx="40" ry="22" fill="#fff" opacity="0.3" transform="rotate(-28 -62 -290)"/>`;
  s += face({ x: 0, y: -235, gap: 54, r: 25, my: 86, mw: 32, m: c.m, open: c.open, look: c.look, blink: c.blink, lidColor: '#ffc23d', outline: '#3d2008', blush: '#ff7a4a' });
  // monocle + mustache
  s += `<circle cx="54" cy="-235" r="36" fill="#fff" fill-opacity="0.12" stroke="#e8b43a" stroke-width="7"/><path d="M88,-222 Q110,-170 92,-120" stroke="#e8b43a" stroke-width="3" fill="none"/>`;
  const mo = c.open * 6;
  s += `<path transform="translate(0,${f1(-176 - mo)})" d="M0,-2 C-20,-22 -66,-24 -92,6 C-74,-2 -52,10 -30,12 C-14,13 -4,6 0,2 C4,6 14,13 30,12 C52,10 74,-2 92,6 C66,-24 20,-22 0,-2 Z" fill="#5a3a1e" stroke="#2e1c0c" stroke-width="3"/>`;
  if (c.prop) s += c.prop;
  s += arm(-140, -175, handL[0], handL[1], -30, '#ffc23d', '#fff', '#3a2416');
  s += arm(140, -175, handR[0], handR[1], 30, '#ffc23d', '#fff', '#3a2416');
  if (c.propFront) s += c.propFront;
  return place(c, s);
}

// ---------------------------------------------------------------- KERMO (worm in an apple)
function kermoApple(t, o) {
  // o.emerge 0..1, o.shake (px), o.lean (deg)
  const c = charState('kermo', t, o, 4);
  const e = clamp(o.emerge || 0, 0, 1.2);
  const hx = 42, hy = -172;
  const sh = (o.shake || 0) * Math.sin(t * 60);
  let s = `<g transform="translate(${f1(sh)},0)">`;
  s += `<path d="M0,-250 C60,-292 152,-262 152,-150 C152,-48 80,2 0,-20 C-80,2 -152,-48 -152,-150 C-152,-262 -60,-292 0,-250 Z" fill="url(#gApple)" stroke="#7e1424" stroke-width="5"/>`;
  s += `<ellipse cx="-70" cy="-200" rx="30" ry="18" fill="#fff" opacity="0.35" transform="rotate(-35 -70 -200)"/>`;
  s += `<path d="M0,-250 q-4,-30 6,-48" stroke="#6b4226" stroke-width="9" fill="none" stroke-linecap="round"/>`;
  s += `<path d="M6,-290 C30,-322 74,-318 88,-298 C66,-282 30,-280 6,-290 Z" fill="url(#gLeaf)" stroke="#2f8a43" stroke-width="4"/>`;
  s += `<ellipse cx="${hx}" cy="${hy}" rx="36" ry="27" fill="#3b0d14"/>`;
  if (e > 0.01) {
    const H = e * 230, lean = o.lean || 0;
    const n = 7;
    const pts = [];
    for (let i = 0; i <= n; i++) {
      const k = i / n;
      pts.push([hx + Math.sin(k * 2.4 + t * 2) * 10 * k + lean * k * k * 1.2, hy - H * k]);
    }
    for (let i = 0; i < n; i++) {
      const [x, y] = pts[i];
      s += `<circle cx="${f1(x)}" cy="${f1(y)}" r="${f1(31 - i)}" fill="${i % 2 ? '#86d35a' : '#9be36b'}" stroke="#4e8f2e" stroke-width="4"/>`;
    }
    const [x, y] = pts[n];
    s += `<g transform="translate(${f1(x)},${f1(y)}) rotate(${f1(lean * 0.4)})">`;
    s += `<circle cx="0" cy="0" r="56" fill="url(#gWorm)" stroke="#4e8f2e" stroke-width="5"/>`;
    s += face({ x: 0, y: -6, gap: 22, r: 16, my: 30, mw: 18, m: c.m, open: c.open, look: c.look, blink: c.blink, lidColor: '#9be36b', outline: '#20401a' });
    s += `<path d="M-16,16 C-24,10 -34,12 -40,20 M16,16 C24,10 34,12 40,20" stroke="#20401a" stroke-width="4" fill="none" stroke-linecap="round"/>`;
    s += `<g transform="translate(0,-46) rotate(-8)"><ellipse cx="0" cy="6" rx="52" ry="12" fill="#1b1b22"/><path d="M-32,6 C-32,-38 32,-38 32,6 Z" fill="#1b1b22"/><rect x="-32" y="-8" width="64" height="9" fill="#c0283a"/></g>`;
    if (o.hand) s += `<circle cx="${o.hand[0]}" cy="${o.hand[1]}" r="15" fill="#9be36b" stroke="#4e8f2e" stroke-width="4"/>`;
    s += `</g>`;
  }
  // lower lip of the hole hides the worm's base
  s += `<path d="M${hx - 36},${hy} A36,27 0 0 0 ${hx + 36},${hy} Z" fill="#d42a3c"/>`;
  s += `</g>`;
  return place(c, s);
}

// ---------------------------------------------------------------- props
// phone: (x,y) centre, w,h, rotation, screen content drawn in a 300x560 box
function phone(x, y, scale, rot, screen, glow = 0) {
  let s = `<g transform="translate(${f1(x)},${f1(y)}) rotate(${f1(rot)}) scale(${scale})">`;
  if (glow) s += `<ellipse cx="0" cy="0" rx="260" ry="320" fill="url(#gGlow)" opacity="${glow}"/>`;
  s += `<rect x="-160" y="-300" width="320" height="600" rx="44" fill="#16161e" stroke="#000" stroke-width="5"/>`;
  s += `<g transform="translate(-150,-280)"><rect width="300" height="560" rx="34" fill="#fbfaff"/>${screen}</g>`;
  s += `<rect x="-38" y="-288" width="76" height="16" rx="8" fill="#16161e"/>`;
  return s + `</g>`;
}

const fa = n => String(n).replace(/[0-9]/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);

function screenProfile(name, count, label = 'فالوور', accent = '#ff3d7f') {
  return `<rect width="300" height="90" rx="34" fill="${accent}" opacity="0.12"/>` +
    `<circle cx="150" cy="140" r="58" fill="url(#gStory)"/><circle cx="150" cy="140" r="50" fill="#fff"/><circle cx="150" cy="140" r="44" fill="${accent}" opacity="0.25"/>` +
    `<text x="150" y="240" font-size="30" font-weight="800" text-anchor="middle" fill="#1b1430" font-family="Vazirmatn">${name}</text>` +
    `<text x="150" y="350" font-size="${String(count).length > 5 ? 54 : 76}" font-weight="900" text-anchor="middle" fill="#1b1430" font-family="Vazirmatn" direction="rtl">${count}</text>` +
    `<text x="150" y="400" font-size="30" font-weight="500" text-anchor="middle" fill="#6b6380" font-family="Vazirmatn">${label}</text>` +
    `<rect x="40" y="440" width="220" height="64" rx="20" fill="${accent}"/><text x="150" y="483" font-size="28" font-weight="800" text-anchor="middle" fill="#fff" font-family="Vazirmatn">دنبال کردن</text>`;
}

function screenPassword(dots, user = 'moozi.luxury', panel = 'پنل ارزون‌کده') {
  let d = '';
  for (let i = 0; i < dots; i++) d += `<circle cx="${f1(232 - i * 30)}" cy="300" r="10" fill="#1b1430"/>`;
  return `<rect width="300" height="560" rx="34" fill="#12101c"/>` +
    `<text x="150" y="110" font-size="30" font-weight="800" text-anchor="middle" fill="#9be36b" font-family="Vazirmatn">${panel}</text>` +
    `<text x="150" y="150" font-size="20" font-weight="500" text-anchor="middle" fill="#8a8399" font-family="Vazirmatn">فالوور نصف قیمت</text>` +
    `<rect x="30" y="180" width="240" height="56" rx="14" fill="#fff"/><text x="250" y="216" font-size="22" font-weight="500" text-anchor="end" fill="#1b1430" font-family="Vazirmatn" direction="ltr">${user}</text>` +
    `<text x="270" y="262" font-size="20" font-weight="800" text-anchor="end" fill="#ff5d5d" font-family="Vazirmatn">رمز عبور پیج</text>` +
    `<rect x="30" y="272" width="240" height="56" rx="14" fill="#fff" stroke="#ff5d5d" stroke-width="4"/>${d}` +
    `<rect x="30" y="370" width="240" height="64" rx="18" fill="#9be36b"/><text x="150" y="412" font-size="28" font-weight="900" text-anchor="middle" fill="#12101c" font-family="Vazirmatn">ارسال</text>`;
}

function goldCard(x, y, scale, rot, shine) {
  return `<g transform="translate(${f1(x)},${f1(y)}) rotate(${f1(rot)}) scale(${scale})">` +
    `<rect x="-120" y="-80" width="240" height="160" rx="14" fill="url(#gGold)" stroke="#9a6a10" stroke-width="5"/>` +
    `<rect x="-104" y="-64" width="208" height="128" rx="8" fill="none" stroke="#fff6c8" stroke-width="3" opacity="0.8"/>` +
    `<text x="0" y="-14" font-size="30" font-weight="900" text-anchor="middle" fill="#6b4608" font-family="Vazirmatn">قرارداد</text>` +
    `<text x="0" y="26" font-size="24" font-weight="800" text-anchor="middle" fill="#6b4608" font-family="Vazirmatn">سفیر برند</text>` +
    `<circle cx="78" cy="48" r="22" fill="#d6283c" stroke="#8e1424" stroke-width="3"/><path d="M70,66 l-8,26 l16,-8 l16,8 l-8,-26" fill="#d6283c"/>` +
    `<path d="M${f1(-150 + shine * 300)},-80 l40,0 l-60,160 l-40,0 Z" fill="#fff" opacity="0.35"/>` +
    `</g>`;
}

// shared gradient/clip definitions
const DEFS = `
<radialGradient id="gPeach" cx="0.38" cy="0.3" r="0.8"><stop offset="0" stop-color="#ffe6cc"/><stop offset="0.45" stop-color="#ffb48a"/><stop offset="0.8" stop-color="#ff8a6e"/><stop offset="1" stop-color="#f0605c"/></radialGradient>
<linearGradient id="gBanana" x1="0" x2="1" y1="0" y2="0"><stop offset="0" stop-color="#e9b21e"/><stop offset="0.35" stop-color="#ffd83b"/><stop offset="0.7" stop-color="#fff07a"/><stop offset="1" stop-color="#f2c230"/></linearGradient>
<radialGradient id="gAnanas" cx="0.35" cy="0.3" r="0.85"><stop offset="0" stop-color="#ffe27a"/><stop offset="0.5" stop-color="#ffc23d"/><stop offset="1" stop-color="#e88a24"/></radialGradient>
<radialGradient id="gApple" cx="0.35" cy="0.3" r="0.85"><stop offset="0" stop-color="#ff8f8f"/><stop offset="0.5" stop-color="#ef3b4c"/><stop offset="1" stop-color="#b3172b"/></radialGradient>
<radialGradient id="gWorm" cx="0.35" cy="0.3" r="0.8"><stop offset="0" stop-color="#d8ffb0"/><stop offset="0.6" stop-color="#9be36b"/><stop offset="1" stop-color="#6fbf45"/></radialGradient>
<linearGradient id="gLeaf" x1="0" x2="1"><stop offset="0" stop-color="#7ddc7a"/><stop offset="1" stop-color="#3fa34d"/></linearGradient>
<linearGradient id="gGold" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#fff1a8"/><stop offset="0.45" stop-color="#f7c948"/><stop offset="1" stop-color="#d09420"/></linearGradient>
<linearGradient id="gStory" x1="0" x2="1" y1="1" y2="0"><stop offset="0" stop-color="#feda75"/><stop offset="0.5" stop-color="#d62976"/><stop offset="1" stop-color="#4f5bd5"/></linearGradient>
<radialGradient id="gGlow"><stop offset="0" stop-color="#9fd0ff" stop-opacity="0.7"/><stop offset="1" stop-color="#9fd0ff" stop-opacity="0"/></radialGradient>
<radialGradient id="gSpot"><stop offset="0" stop-color="#fff8e0" stop-opacity="0.55"/><stop offset="1" stop-color="#fff8e0" stop-opacity="0"/></radialGradient>
<radialGradient id="gVig" cx="0.5" cy="0.45" r="0.75"><stop offset="0.55" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity="0.65"/></radialGradient>
<clipPath id="cAnanas"><ellipse cx="0" cy="-190" rx="150" ry="180"/></clipPath>
`;
