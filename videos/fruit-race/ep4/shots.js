/* Episode 4 · راه درست — ClickPop enters the story */
const END = 41.2, SLAM = 0.35;

// laptop on the counter between the two friends
function kitchenDuo(t, o) {
  let s = kitchenBG(t, o.night === undefined ? false : o.night);
  s += pan(150, 1100, t, o.steam || 0);
  const lid = o.lid === undefined ? 1 : o.lid;
  s += `<g transform="translate(540,1100) scale(1,${f1(Math.max(0.02, lid))}) translate(-540,-1100)">` + laptop(540, 1100, 0.72, o.screen) + `</g>`;
  if (o.holu) s += holu(t, Object.assign({ x: 210, y: 1470, s: 0.92, m: mood('neutral'), look: [0.9, -0.1] }, o.holu));
  if (o.tooti) s += tooti(t, Object.assign({ x: 875, y: 1470, s: 0.92, m: mood('happy'), look: [-0.9, -0.1] }, o.tooti));
  return s;
}

function shotSlam(t) {
  const lid = t < SLAM ? 0.05 : spring(t, SLAM, 3, 0.4);
  const [sx, sy] = shake(t, SLAM, 18);
  const body = kitchenDuo(t, {
    lid, screen: cpOrderScreen(0, 0),
    holu: { m: t < SLAM ? mood('sad') : mood('shocked'), look: [0.9, 0] },
    tooti: { m: mood('stern', { smile: 0.4 }), handL: t < SLAM ? [-160, -300] : [-120, -60], handR: [150, -420 + Math.sin(t * 6) * 10], look: [-0.6, 0.2] },
  });
  return { body, cam: cam(545, 1050, lerp(1.04, 1.1, prog(t, 0, 3.5)), sx, sy) };
}

function shotTalk(t) {
  const body = kitchenDuo(t, {
    screen: cpOrderScreen(0, 0),
    holu: { m: t < 8.2 ? mood('worried') : mood('neutral', { smile: 0.5 }), handL: [-150, -110], handR: t < 8 ? [120, -240] : [150, -110] },
    tooti: { m: mood('happy'), handL: [-170, -170 - Math.abs(Math.sin(t * 6)) * 30 * (speaking('tooti', t) ? 1 : 0)], handR: [150, -120] },
  });
  return { body, cam: cam(540, 1050, lerp(1.04, 1.08, prog(t, 5.5, 11))) };
}

// screen-space (640x400) to world for the big laptop
const LX = sx => 60 + sx * 1.5, LY = sy => 620 + sy * 1.5;
const CURSOR_KEYS = [[11.0, 470, 330], [11.6, 568, 163], [12.5, 567, 248], [13.4, 320, 291], [14.2, 395, 353], [16.6, 108, 353], [17.6, 300, 330], [21.5, 300, 330]];

function cursorAt(t) {
  let a = CURSOR_KEYS[0], b = CURSOR_KEYS[CURSOR_KEYS.length - 1];
  for (let i = 0; i < CURSOR_KEYS.length - 1; i++) if (t >= CURSOR_KEYS[i][0] && t < CURSOR_KEYS[i + 1][0]) { a = CURSOR_KEYS[i]; b = CURSOR_KEYS[i + 1]; }
  const k = ease.io(prog(t, a[0], Math.min(b[0], a[0] + 0.55)));
  return [lerp(a[1], b[1], k), lerp(a[2], b[2], k)];
}

function shotSite(t) {
  const clicks = [11.8, 12.8, 14.4, 17.0];
  const press = Math.max(0, ...clicks.map(c => t >= c && t < c + 0.35 ? 1 - (t - c) / 0.35 : 0));
  let step = 0;
  if (t >= 11.8) step = 1; if (t >= 12.8) step = 2; if (t >= 13.4) step = 3; if (t >= 14.4) step = 4;
  const chars = Math.round(26 * prog(t, 14.6, 16.2));
  let scr;
  if (t < 17.6) scr = cpOrderScreen(step, chars, t >= 17.0 && t < 17.3 ? 1 : 0);
  else scr = cpProgressScreen(0.92 * ease.io(prog(t, 18.0, 21.4)), prog(t, 21.8, 22.2));
  let s = `<rect x="-500" y="-500" width="2080" height="2920" fill="#e9eefb"/><circle cx="540" cy="900" r="760" fill="#1668FF" opacity="0.08"/>`;
  s += laptop(540, 1250, 1.5, scr);
  if (t < 21.6) { const [cx, cy] = cursorAt(t); s += cursorArrow(LX(cx), LY(cy), 1.4, press); }
  // camera follows the cursor up close on the form, then frames the whole order panel
  const [fx, fy] = cursorAt(Math.min(t, 17.4));
  const near = 1 - ease.io(prog(t, 17.3, 17.9));
  const cx = lerp(540, clamp(LX(fx) * 0.6 + 540 * 0.4, 380, 700), near), cy = lerp(930, LY(fy) * 0.7 + 900 * 0.3, near);
  const z = lerp(1.26, 1.75, near * ease.io(prog(t, 11.0, 11.6)));
  return { body: s, cam: cam(cx, cy, z), vig: 0.25 };
}

function shotRefund(t) {
  const jump = t > 24.0 ? Math.sin(Math.PI * prog(t, 24.0, 24.45)) : 0;
  const body = kitchenDuo(t, {
    screen: cpProgressScreen(0.92, 1),
    holu: { m: t < 23.8 ? mood('shocked', { gasp: 0.3, smile: 0.4 }) : mood('happy'), y: 1470 - jump * 100, squash: -jump * 0.05, look: [0.8, 0.1],
      handL: t > 24 ? [-170, -330] : [-150, -110], handR: t > 24 ? [170, -330] : [150, -110] },
    tooti: { m: mood('proud', { lid: 0 }), handL: [-80, -190], handR: [80, -190], look: [-0.9, -0.2] },
  });
  return { body: body + (t > 24 ? confetti(t, 24.0, 210, 1150, 30) : ''), cam: cam(540, 1050, lerp(1.04, 1.1, prog(t, 22.6, 29.2))) };
}

function shotMontage(t) {
  const day = t < 31.8 ? 5 : t < 34.3 ? 6 : 7;
  const flip = [29.3, 31.8, 34.3].map(a => prog(t, a, a + 0.3)).reduce((m, v) => (v > 0 && v < 1 ? v : m), 1);
  const n = lerp(1200, 6400, ease.io(prog(t, 29.5, 36.8)));
  const stir = Math.sin(t * 7) * 30;
  let s = kitchenBG(t, false) + pan(200, 1100, t, 1);
  s += ringLight(890, 1180, screenProfile('holu.kitchen', fmtK(n), 'فالوور', '#ff5d8f'));
  const spat = `<path d="M${f1(-170 + stir)},-150 L${f1(-250 + stir)},-260" stroke="#7a4a2a" stroke-width="12" stroke-linecap="round"/><ellipse cx="${f1(-262 + stir)}" cy="-276" rx="22" ry="34" fill="#9a9dab" transform="rotate(-30 ${f1(-262 + stir)} -276)"/>`;
  s += holu(t, { x: 520, y: 1460, s: 1.0, m: mood('happy'), look: [0.8, -0.2], handL: [-170 + stir, -150], handR: [170, -260 + Math.sin(t * 5) * 20], prop: spat, squash: Math.sin(t * 8) * 0.02 });
  for (let i = 0; i < 18; i++) {
    const a = 29.4 + i * 0.4;
    if (t < a || t > a + 2) continue;
    const k = (t - a) / 2;
    s += heart(890 + (rnd(i) - 0.5) * 240, 1060 - k * 700, 1.6 + rnd(i + 5), 1 - k);
  }
  s += `<g transform="translate(820,560) scale(1,${f1(Math.max(0.05, Math.abs(1 - 2 * Math.min(1, flip * 2) + (flip > 0.5 ? 0 : 0))))})">` + dayCard(0, 0, 1, day) + `</g>`;
  return { body: s, cam: cam(560, 1080, lerp(1.2, 1.3, prog(t, 29.2, 37.2))) };
}

function shotTomorrow(t) {
  const tf = Math.min(t, END);
  const body = stageSet(tf, {
    ananas: { m: mood('stern', { smile: 0.35 }), look: [0.3, 0.4], handR: [130, -110], handL: [-120, -260],
      propFront: phone(-130, -350, 0.3, -10, screenProfile('holu.kitchen', '۶٫۴ هزار', 'فالوور', '#ff5d8f'), 0.5) },
    moozi: null, holu: null, crowd: 1,
  });
  const r = { body, cam: cam(520, 990, lerp(1.7, 1.85, ease.io(prog(tf, 37.2, END)))), filter: 'brightness(0.72) saturate(0.85)', vig: 0.85 };
  if (t >= END) { r.filter = 'saturate(0.35) brightness(0.5) blur(5px)'; r.top = `<rect width="1080" height="1920" fill="#c0102a" opacity="${f1(0.3 * ease.out(prog(t, END, END + 0.3)))}"/>`; }
  return r;
}

const SHOTS = [[0, shotSlam], [5.5, shotTalk], [11.0, shotSite], [22.6, shotRefund], [29.2, shotMontage], [37.2, shotTomorrow]];
window.SHOT = t => shotAt(t, SHOTS)[1](t);
