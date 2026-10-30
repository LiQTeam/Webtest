/* Episode 3 · سقوط */
const END = 36.4, STAMP = 16.3, SNATCH = 29.6;

function shotFeed(t) {
  const n = lerp(120000, 8200, ease.in(prog(t, 0.4, 3.2)) * 0.6 + prog(t, 0.4, 3.2) * 0.4);
  const jit = t < 0.4 ? (rnd(Math.floor(t * 30)) - 0.5) * 50 : 0;
  let s = phoneShot(t, screenFeed(prog(t, 0.5, 3.2), fmtK(n)), lerp(2.15, 2.3, prog(t, 0, 3.5)), -3, '#1a0610', '#8a1a2a');
  s += `<g opacity="${f1(0.5 + 0.5 * Math.sin(t * 10))}"><path d="M540,180 l70,120 h-140 Z" fill="#ff3d5a"/><rect x="534" y="214" width="12" height="50" fill="#fff"/><circle cx="540" cy="282" r="7" fill="#fff"/></g>`;
  return { body: s, cam: `translate(${f1(jit)},0)`, vig: 0.7 };
}

function shotTicket(t) {
  const ph = phone(150, -350, 0.32, 8 + (t > 5.8 && t < 6.6 ? Math.sin(t * 60) * 2 : 0), screenTicket(prog(t, 6.6, 6.8), t), 0.8);
  const s = alleySet(t, '') + kermoApple(t, { x: 770, y: 1180, s: 0.78, emerge: 0 }) +
    moozi(t, { x: 330, y: 1350, s: 0.95, m: mood('angry', { bi: -10 }), look: [0.7, -0.6], glasses: 1, handR: [130, -270], handL: [-120, -300 - Math.abs(Math.sin(t * 7)) * 30], propFront: ph });
  return { body: s, cam: cam(400, 1020, lerp(1.75, 1.9, ease.io(prog(t, 5.5, 9.2)))), vig: 0.9 };
}

function shotEscape(t) {
  // Kermo pops up with a suitcase, then the apple hops away off the crate
  const k = prog(t, 10.4, 12.4);
  const x = lerp(770, 1400, k);
  const base = x < 940 ? 1180 : 1360;
  const hop = Math.abs(Math.sin(Math.PI * k * 3)) * 140;
  const o = { x, y: base - hop, s: 0.78, emerge: spring(t, 9.2, 2.4, 0.3), m: mood('sly', { smile: 0.95 }), lean: 8, look: [-0.8, 0] };
  const [hx, hy] = kermoHead(t, o);
  let s = alleySet(t, '');
  s += moozi(t, { x: 330, y: 1350, s: 0.95, m: mood(t < 10.4 ? 'shocked' : 'angry'), look: [0.9, -0.2], glasses: 1, handL: [-110, -140], handR: t > 10.4 ? [190, -300] : [130, -240] });
  s += kermoApple(t, o) + suitcase(hx + 70, hy + 40, 0.55);
  return { body: s, cam: cam(640, 1020, 1.15), vig: 0.9 };
}

function shotVerdict(t) {
  const st = prog(t, STAMP, STAMP + 0.12);
  const [sx, sy] = shake(t, STAMP, 24);
  const body = stageSet(t, {
    ananas: { m: mood('stern', { bi: 12 }), look: [0.6, 0.1], handL: [-165, -150], handR: t > 15.6 ? [230, -260] : [130, -110] },
    moozi: null,
    holu: { x: 215, m: mood('worried'), look: [0.7, -0.1] },
    extra: poster(850, 1350, 0.78, t > STAMP ? 1 : st, t), crowd: 2,
  });
  return { body, cam: cam(640, 1050, lerp(1.2, 1.28, prog(t, 12.4, 17.6)), sx, sy) };
}

function kitchenNight(t, extra) {
  return kitchenBG(t, true) + pan(200, 1100, t, 0) + extra;
}

function shotWorry(t) {
  const ph = phone(150, -300, 0.3, 8, screenProfile('holu.kitchen', '۱٫۲ هزار', 'فالوور', '#ff5d8f'));
  const s = kitchenNight(t, holu(t, { x: 540, y: 1460, s: 1.0, m: mood('sad'), look: [0.75, 0.3], handR: [150, -230], handL: [-150, -110], propFront: ph }));
  return { body: s + sweat(540 - 120, 1460 - 330, 1.0), cam: cam(560, 1200, lerp(1.7, 1.85, ease.io(prog(t, 17.6, 22.2)))) };
}

function shotWindow(t) {
  const o = { x: 750, y: 765, s: 0.66, emerge: spring(t, 22.25, 2.2, 0.35) * 0.9, m: mood('sly'), lean: -10, look: [-0.9, 0.3] };
  const [hx, hy] = kermoHead(t, o);
  let s = kitchenNight(t, '');
  s += kermoApple(t, o);
  if (o.emerge > 0.3) s += disguise(hx, hy, o.s);
  const turn = t > 22.3;
  s += holu(t, { x: 430, y: 1460, s: 1.0, m: turn ? mood('worried') : mood('sad'), look: turn ? [0.9, -0.6] : [0.7, 0.3], handR: [150, -230], handL: [-150, -110],
    propFront: phone(150, -300, 0.3, 8, screenPassword(0, 'holu.kitchen', 'پنل سوپرارزون')) });
  return { body: s, cam: cam(620, 1000, lerp(1.25, 1.32, prog(t, 22.2, 26.2))) };
}

function shotTyping(t) {
  const taps = [0, 1, 2, 3, 4, 5].map(k => 26.5 + k * 0.32);
  const dots = taps.filter(a => t >= a).length;
  const yank = ease.in(prog(t, SNATCH, SNATCH + 0.3));
  let s = `<rect width="1080" height="1920" fill="#160c22"/><circle cx="540" cy="860" r="700" fill="#5a2a6a" opacity="0.35"/>`;
  s += `<g transform="translate(${f1(yank * 900)},${f1(-yank * 1500)}) rotate(${f1(yank * 40)} 540 900)">` + phone(540, 900, 2.2, -3, screenPassword(dots, 'holu.kitchen', 'پنل سوپرارزون')) + `</g>`;
  // peach-coloured hand: typing, then hovering over "send"
  const hover = ease.io(prog(t, 28.3, 29.3));
  const keyX = 540 + (rnd(dots * 3 + 1) - 0.5) * 260;
  const down = Math.max(...taps.map(a => Math.exp(-Math.abs(t - a) * 14))) * (1 - hover);
  const gx = lerp(keyX, 540, hover), gy = lerp(1560 - down * 110, 1300, hover) + (t > SNATCH ? 300 * yank : 0) + Math.sin(t * 9) * 6 * hover;
  s += `<path d="M${f1(gx + 40)},${f1(gy + 60)} Q${f1(gx + 260)},${f1(gy + 260)} ${f1(gx + 330)},2000" stroke="#6b2a35" stroke-width="120" fill="none" stroke-linecap="round"/><path d="M${f1(gx + 40)},${f1(gy + 60)} Q${f1(gx + 260)},${f1(gy + 260)} ${f1(gx + 330)},2000" stroke="#ffb48c" stroke-width="96" fill="none" stroke-linecap="round"/>`;
  s += `<g transform="translate(${f1(gx)},${f1(gy)}) rotate(-25)"><ellipse rx="95" ry="120" fill="#fff" stroke="#6b2a35" stroke-width="8"/><ellipse cx="-10" cy="-70" rx="42" ry="60" fill="#fff" stroke="#6b2a35" stroke-width="7"/></g>`;
  if (t > SNATCH) for (let i = 0; i < 6; i++) s += `<path d="M${200 + i * 120},${1400 - i * 90} l${f1(300 * yank)},${f1(-500 * yank)}" stroke="#fff" stroke-width="8" opacity="${f1(0.6 * (1 - yank))}"/>`;
  return { body: s, vig: 0.6 };
}

function shotTooti(t) {
  const enter = ease.back(prog(t, 29.9, 30.3));
  const ph = phone(60, -470, 0.3, 14, screenPassword(6, 'holu.kitchen', 'پنل سوپرارزون'));
  let tm = mood('angry'), hL = [-170, -180 - Math.abs(Math.sin(t * 7)) * 30 * (speaking('tooti', t) ? 1 : 0)];
  if (t > 34.0) { tm = mood('happy'); hL = [-200, -240 + Math.sin(t * 8) * 30]; }
  let hm = mood('shocked');
  if (t > 33.9) hm = mood('worried', { smile: 0.2 });
  let s = kitchenNight(t, '');
  s += holu(t, { x: 320, y: 1460, s: 0.95, m: hm, look: [0.9, -0.2], handL: [-150, -110], handR: [150, -110] });
  s += tooti(t, { x: lerp(1300, 770, enter), y: 1460, s: 0.95, m: tm, look: [-0.9, 0], handR: [70, -400], handL: hL, propFront: ph });
  let r = { body: s, cam: cam(545, 1150, lerp(1.25, 1.36, ease.io(prog(t, 30.0, END)))) };
  if (t >= END) { r.filter = 'saturate(0.35) brightness(0.55) blur(5px)'; r.top = `<rect width="1080" height="1920" fill="#c0102a" opacity="${f1(0.35 * ease.out(prog(t, END, END + 0.3)))}"/>`; r.body = shotTooti(END - 0.01).body; }
  return r;
}

const SHOTS = [[0, shotFeed], [5.5, shotTicket], [9.2, shotEscape], [12.4, shotVerdict], [17.6, shotWorry], [22.2, shotWindow], [26.2, shotTyping], [29.95, shotTooti]];
window.SHOT = t => shotAt(t, SHOTS)[1](t);
