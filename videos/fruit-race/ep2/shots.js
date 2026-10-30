/* Episode 2 · رمز */
const END = 37.0;

function shotCounter(t) {
  const k = ease.out(prog(t, 0.1, 3.0));
  const n = lerp(12400, 120000, k);
  let s = `<rect width="1080" height="1920" fill="#2a0f4a"/>` + rays(t, 540, 860, '#ffe14d', 18, 0.12 + 0.1 * prog(t, 2.8, 3.1));
  const pz = 2.0 + (t > 3.0 ? 0.12 * Math.exp(-(t - 3.0) * 6) : 0);
  s += phone(540, 900, pz, -3, screenProfile('moozi.luxury', fmtK(n), 'فالوور', '#f7b500'), 0);
  for (let i = 0; i < 14; i++) {
    const a = 0.2 + i * 0.2;
    if (t < a) continue;
    const k2 = (t - a) / 1.6;
    if (k2 > 1) continue;
    s += heart(540 + (i % 2 ? 1 : -1) * (330 + rnd(i) * 160), 1500 - k2 * 1200, 2 + rnd(i + 4) * 1.5, 1 - k2);
  }
  if (t > 3.0) for (let i = 0; i < 8; i++) {
    const p = prog(t, 3.0, 3.6), a = i / 8 * Math.PI * 2;
    s += sparkleStar(540 + Math.cos(a) * (200 + p * 380), 900 + Math.sin(a) * (300 + p * 500), 40 * (1 - p), p * 90);
  }
  return { body: s, vig: 0.4 };
}

function shotStage(t) {
  // crowd cheers Moozi; Ananas raises an eyebrow
  const glint = t > 8.85 && t < 9.3 ? Math.sin(Math.PI * prog(t, 8.85, 9.3)) : 0;
  const am = t > 6.2 ? mood('stern', { bi: -4, bo: 10 }) : mood('stern');
  const extra = glint ? sparkleStar(540 + 54 * 0.95 - 10, 1140 - 255 * 0.95, 44 * glint, glint * 90) : '';
  const raise = ease.back(prog(t, 5.6, 6.0));
  const body = stageSet(t, {
    ananas: { m: am, look: [-0.6, 0.1] },
    moozi: { m: mood('proud'), handR: [lerp(44, 150, raise), lerp(-165, -320, raise)], handL: [-140, -280 - Math.abs(Math.sin(t * 6)) * 30], look: [0.3, -0.4],
      propFront: phone(150 + 20, -320 - 90, 0.32, 10, screenProfile('moozi.luxury', '۱۲۰ هزار', 'فالوور', '#f7b500'), 0.5) },
    holu: { m: mood('worried'), look: [-0.9, -0.1] },
    extra, crowd: 9,
  });
  return { body, cam: cam(400, 1060, lerp(1.25, 1.32, prog(t, 5.5, 10.1))) };
}

function shotMoozi(t) {
  const gd = ease.out(prog(t, 11.8, 12.1));
  const wink = t > 12.1 && t < 12.6 ? Math.sin(Math.PI * prog(t, 12.1, 12.6)) : 0;
  let extra = '';
  if (t > 12.1 && t < 12.9) { const p = prog(t, 12.1, 12.9); extra = sparkleStar(215 - 0.8 * 52, 1345 - 0.8 * 300, Math.sin(Math.PI * p) * 34, p * 90); }
  const body = stageSet(t, {
    moozi: { m: mood('proud', { lid: t > 11.8 ? 0 : 0.3 }), glasses: gd, wink, handL: [-120, -150], handR: [140, -230], look: [0.4, 0],
      propFront: phone(160, -320, 0.32, 10, screenProfile('moozi.luxury', '۱۲۰ هزار', 'فالوور', '#f7b500'), 0.5) },
    holu: { m: mood('worried') }, extra, crowd: 8,
  });
  return { body, cam: cam(215, 1060, lerp(2.0, 2.15, ease.io(prog(t, 10.1, 14.4)))) };
}

function kitchenScene(t, holuOpt, camStr) {
  let s = kitchenBG(t, true);
  s += pan(200, 1100, t, 1);
  const rec = Math.floor(t * 2) % 2 ? '#ff2d2d' : '#5a1a1a';
  s += ringLight(890, 1180, `<rect width="300" height="560" rx="34" fill="#2a1f3a"/><circle cx="150" cy="260" r="70" fill="#ffb48c"/><circle cx="40" cy="40" r="16" fill="${rec}"/>`);
  s += holu(t, Object.assign({ x: 540, y: 1460, s: 1.0 }, holuOpt));
  return { body: s, cam: camStr };
}

function shotCooking(t) {
  const stir = Math.sin(t * 7) * 30;
  const spat = `<path d="M${f1(-170 + stir)},-150 L${f1(-250 + stir)},-260" stroke="#7a4a2a" stroke-width="12" stroke-linecap="round"/><ellipse cx="${f1(-262 + stir)}" cy="-276" rx="22" ry="34" fill="#9a9dab" transform="rotate(-30 ${f1(-262 + stir)} -276)"/>`;
  const r = kitchenScene(t, { m: mood('worried', { smile: 0.1 }), look: [-0.7, 0.4], handL: [-170 + stir, -150], handR: [150, -110], prop: spat }, cam(470, 1180, lerp(1.3, 1.38, prog(t, 16.1, 21.0))));
  r.body += sweat(540 + 120, 1460 - 330, 1.1 + Math.sin(t * 3) * 0.05);
  return r;
}

function shotCheck(t) {
  const n = lerp(800, 940, ease.out(prog(t, 21.0, 21.8)));
  const droop = t > 24.4 ? ease.out(prog(t, 24.4, 24.9)) * 0.05 : 0;
  const ph = phone(150, -300, 0.3, 8, screenProfile('holu.kitchen', fa(Math.round(n)), 'فالوور', '#ff5d8f'));
  return kitchenScene(t, { m: t < 22 ? mood('worried') : mood('sad'), look: [0.75, 0.3], handR: [150, -230], handL: [-150, -110], propFront: ph, squash: droop },
    cam(600, 1210, lerp(2.0, 2.15, ease.io(prog(t, 21.0, 25.2)))));
}

function shotKermo(t) {
  // Kermo counts cash on the crate and tosses bills into a suitcase
  let extra = suitcase(600, 1170, 1.0);
  for (let i = 0; i < 10; i++) {
    const a = 25.3 + i * 0.42;
    if (t < a || t > a + 0.7) continue;
    const p = (t - a) / 0.7;
    extra += bills(lerp(780, 600, p), lerp(880, 1100, p) - Math.sin(Math.PI * p) * 160, p * 360, 0.8);
  }
  const s = alleySet(t, extra) + kermoApple(t, { x: 770, y: 1180, s: 0.78, emerge: 1, m: mood('sly', { smile: 0.95 }), lean: -12, hand: [-58, 30 + Math.sin(t * 8) * 10], look: [-0.6, 0.4] });
  const laugh = t > 29.7 ? Math.abs(Math.sin((t - 29.7) * 16)) * 0.03 : 0;
  return { body: s, cam: cam(720, 980, lerp(1.6, 1.75, ease.io(prog(t, 25.2, 30.8))) + laugh), vig: 0.9 };
}

function shotBuzz(t) {
  const buzz = [31.3, 31.9].some(a => t > a && t < a + 0.45) ? Math.sin(t * 90) * 3 : 0;
  let m = mood('proud'), look = [0.5, -0.5], gd = 0;
  if (t > 31.3) { m = mood('neutral'); look = [0.7, -0.6]; }
  if (t > 32.8) { m = mood('shocked'); gd = ease.out(prog(t, 32.8, 33.1)); }
  const ph = phone(150, -350, 0.32, 8 + buzz, screenNotif('اینستاگرام', ['رمز عبور حساب شما', 'تغییر کرد.']), 0.8);
  const s = alleySet(t, '') + moozi(t, { x: 330, y: 1350, s: 0.95, m, look, glasses: gd, handR: [130, -270], handL: [-110, -140], propFront: ph });
  return { body: s, cam: cam(380, 1020, lerp(1.8, 2.0, ease.io(prog(t, 30.8, 34.6)))), vig: 0.9 };
}

function shotLocked(t) {
  const tf = Math.min(t, END);
  const scr = tf < 35.6 ? screenNotif('اینستاگرام', ['رمز عبور حساب شما', 'تغییر کرد.']) : screenLocked(prog(tf, 35.9, 36.4));
  const jit = tf > 35.6 && tf < 36.0 ? (rnd(Math.floor(tf * 30)) - 0.5) * 40 : 0;
  const r = { body: phoneShot(tf, scr, lerp(2.15, 2.3, prog(tf, 34.6, END))), cam: `translate(${f1(jit)},0)`, vig: 0.6 };
  if (tf > 35.6 && tf < 36.0) r.top = `<rect y="${f1(rnd(Math.floor(tf * 30) + 1) * 1800)}" width="1080" height="60" fill="#ff3d5a" opacity="0.5"/>`;
  if (t >= END) { r.filter = 'saturate(0.35) brightness(0.55) blur(5px)'; r.top = `<rect width="1080" height="1920" fill="#c0102a" opacity="${f1(0.35 * ease.out(prog(t, END, END + 0.3)))}"/>`; }
  return r;
}

const SHOTS = [[0, shotCounter], [5.5, shotStage], [10.1, shotMoozi], [16.1, shotCooking], [21.0, shotCheck], [25.2, shotKermo], [30.8, shotBuzz], [34.6, shotLocked]];
window.SHOT = t => shotAt(t, SHOTS)[1](t);
