/* Episode 5 · سفیر — finale and ClickPop end card */
const WIN = 6.9, KICK = 28.6, BRAND = 30.0;

function shotEnvelope(t) {
  const open = ease.io(prog(t, 1.2, 2.2));
  const body = stageSet(t, {
    ananas: { m: mood('stern', { smile: 0.3 }), look: [0, 0.5], handL: [-175, -170], handR: [190, -250], propFront: envelope(250, -290, 0.7, open) },
    moozi: null,
    holu: { m: mood('worried'), look: [-0.8, -0.2], handL: [-60, -130], handR: [60, -130] },
    extra: tooti(t, { x: 215, y: 1345, s: 0.8, m: mood('worried', { smile: 0.2 }), look: [0.8, -0.2] }), crowd: 1,
  });
  return { body, cam: cam(540, 1000, lerp(1.55, 1.7, ease.io(prog(t, 0, 3.6)))), filter: 'brightness(0.85)', vig: 0.8 };
}

function shotWin(t) {
  const won = t >= WIN;
  const jump = won ? Math.abs(Math.sin(Math.PI * prog(t, WIN + 0.05, WIN + 0.5) * 2)) * (t < WIN + 1.0 ? 1 : 0) : 0;
  let s = stageSet(t, {
    holu: { m: won ? mood('happy', { gasp: t < WIN + 0.4 ? 0.5 : 0 }) : mood('worried'), y: 1345 - jump * 90, look: [-0.5, -0.4],
      handL: won ? [-170, -360] : [-60, -130], handR: won ? [170, -360] : [60, -130] },
    moozi: null, ananas: { m: mood('happy') }, crowd: won ? 10 : 2,
  });
  if (!won) s += sweat(860 + 110, 1345 - 290, 1);
  s += confetti(t, WIN, 860, 1100, 90) + confetti(t, WIN + 0.1, 300, 1200, 60);
  return { body: s, cam: cam(860, 1070, lerp(1.9, 2.05, prog(t, 5.4, 9.4)) + (won ? 0.1 * Math.exp(-(t - WIN) * 5) : 0)) };
}

function shotAward(t) {
  const give = ease.io(prog(t, 10.2, 11.0));
  const card = goldCard(lerp(300, 330, give), -150, 0.55, -6, prog(t, 10.8, 12));
  let s = stageSet(t, {
    ananas: { m: mood('happy'), handR: [lerp(130, 300, give), lerp(-110, -150, give)], propFront: card, look: [0.8, 0] },
    holu: { x: 830, m: mood('happy'), look: [-0.8, -0.1], handL: [lerp(-150, -190, give), lerp(-110, -170, give)] },
    moozi: null, crowd: 8,
    extra: tooti(t, { x: 215, y: 1345, s: 0.8, m: mood('happy'), look: [0.8, -0.2], handL: [-170, -330 + Math.sin(t * 9) * 20], handR: [170, -330 - Math.sin(t * 9) * 20] }),
  });
  s += confetti(t, WIN, 860, 1100, 90) + confetti(t, WIN + 0.1, 300, 1200, 60);
  return { body: s, cam: cam(660, 1060, lerp(1.25, 1.32, prog(t, 9.4, 14))) };
}

function shotMakeUp(t) {
  // stage side, after the show: Holu walks over to Moozi
  const walk = ease.io(prog(t, 14.0, 15.0));
  const smile = t > 24.0;
  const dm = t > 20.7;
  let s = stageBG(t) + spotPool(300, 1345, 260);
  s += moozi(t, { x: 190, y: 1345, s: 0.9, m: smile ? mood('happy') : mood('sad'), glasses: 1, look: [0.8, dm && !smile ? 0.6 : 0], handL: [-110, -140],
    handR: dm ? [120, -250] : [80, -140], propFront: dm ? phone(130, -330, 0.28, 8, screenDM(prog(t, 20.7, 21.0)), 0.4) : '' });
  s += holu(t, { x: lerp(560, 450, walk), y: 1345, s: 0.9, m: t < 17.3 ? mood('worried', { smile: 0.2 }) : mood('happy'), look: [-0.9, 0],
    handL: t > 19.6 && t < 20.8 ? [-190, -230] : [-150, -110], handR: [150, -110] });
  s += crowd(t * 0.2, 1660);
  return { body: s, cam: cam(330, 1060, lerp(1.6, 1.7, prog(t, 14.0, 26.0))), filter: 'brightness(0.82)', vig: 0.8 };
}

function shotDM(t) {
  return { body: phoneShot(t, screenDM(1), lerp(2.15, 2.25, prog(t, 20.8, 23.6)), -3, '#0e1a3a', '#1668FF'), vig: 0.5 };
}

function shotKick(t) {
  const fly = prog(t, KICK, KICK + 0.9);
  const ax = lerp(960, 1500, fly), ay = 1370 - Math.sin(Math.PI * Math.min(1, fly * 1.2)) * 900 * (fly > 0 ? 1 : 0);
  const kickLeg = t > KICK - 0.15 && t < KICK + 0.25;
  let s = stageSet(t, {
    ananas: { m: mood(t > KICK ? 'happy' : 'stern'), look: [0.8, 0.2] },
    moozi: { x: 170, m: mood(t > KICK ? 'happy' : 'shocked'), glasses: 1, look: [0.9, 0] },
    holu: { x: 400, m: mood(t > KICK ? 'happy' : 'shocked'), look: [0.9, 0] },
    crowd: t > KICK ? 9 : 3,
  });
  s += tooti(t, { x: 760, y: 1345, s: 0.82, m: mood(t < 27.9 ? 'stern' : 'angry'), look: [0.9, 0.2], rot: kickLeg ? -10 : 0, handL: [-160, -200], handR: [170, -260] });
  s += `<g transform="rotate(${f1(fly * 720)} ${f1(ax)} ${f1(ay - 120)})">` + kermoApple(t, { x: ax, y: ay, s: 0.6, emerge: spring(t, 26.1, 2.4, 0.35) * 0.8, m: t > KICK ? mood('shocked') : mood('sly'), lean: -16, look: [-0.9, 0] }) + `</g>`;
  if (t > KICK && t < KICK + 0.4) s += sparkleStar(900, 1250, 80 * (1 - prog(t, KICK, KICK + 0.4)), 30, '#ffe14d');
  return { body: s, cam: cam(560, 1060, 1.02) };
}

function brandCard(t) {
  const k = t - BRAND;
  let s = `<rect x="-200" y="-200" width="1480" height="2320" fill="url(#gInk)"/>`;
  s += rays(t, 540, 620, '#3D89FF', 14, 0.05);
  for (let i = 0; i < 26; i++) s += `<circle cx="${f1(rnd(i) * 1080)}" cy="${f1((rnd(i + 40) * 1920 - k * 40 * (1 + rnd(i + 8))) % 1920 + (rnd(i + 40) * 1920 - k * 40 < 0 ? 1920 : 0))}" r="${f1(2 + rnd(i + 3) * 4)}" fill="#fff" opacity="0.25"/>`;
  const draw = ease.io(prog(t, BRAND + 0.3, BRAND + 1.0)), pop = spring(t, BRAND + 1.0, 2.6, 0.35);
  s += cpIcon(540, 560, 300, draw, pop, '#fff');
  const wm = ease.out(prog(t, BRAND + 1.2, BRAND + 1.6));
  s += `<g opacity="${f1(wm)}" transform="translate(0,${f1((1 - wm) * 30)})">` + cpWordmark(540 - 760 * 0.62 / 2, 860, 0.62, '#fff') + `</g>`;
  const tg = ease.out(prog(t, BRAND + 1.8, BRAND + 2.2));
  s += `<text x="540" y="${f1(1000 + (1 - tg) * 30)}" font-size="54" font-weight="900" text-anchor="middle" fill="#fff" opacity="${f1(tg)}" font-family="Vazirmatn" direction="rtl">بدون رمز عبور. با رسید برای هر سفارش.</text>`;
  ['فقط لینک عمومی پیج', 'پیشرفت زندهٔ سفارش', 'بازگشت خودکار وجهِ تحویل‌نشده'].forEach((f, i) => {
    const a = spring(t, BRAND + 2.6 + i * 0.5, 2.6, 0.45);
    if (a <= 0) return;
    const y = 1090 + i * 100;
    s += `<g transform="translate(540,${y}) scale(${f1(Math.max(0, a))})"><rect x="-330" y="-38" width="660" height="76" rx="38" fill="#fff" fill-opacity="0.08" stroke="#3D89FF" stroke-width="3"/>` +
      `<circle cx="285" cy="0" r="22" fill="#FF7A1A"/><path d="M274,0 l8,8 l14,-16" stroke="#fff" stroke-width="5" fill="none" stroke-linecap="round"/>` +
      `<text x="245" y="13" font-size="36" font-weight="800" text-anchor="end" fill="#fff" font-family="Vazirmatn">${f}</text></g>`;
  });
  const u = spring(t, BRAND + 4.5, 2.4, 0.4);
  if (u > 0) s += `<text x="540" y="1440" font-size="104" font-weight="900" text-anchor="middle" fill="url(#icO)" font-family="Vazirmatn" direction="ltr" transform="translate(540,1405) scale(${f1(u)}) translate(-540,-1405)">clickpop.ir</text>`;
  const c = spring(t, BRAND + 5.2, 2.4, 0.4), pulse = 1 + 0.04 * Math.sin(Math.max(0, t - BRAND - 6) * 5);
  if (c > 0) s += `<g transform="translate(540,1540) scale(${f1(c * pulse)})"><rect x="-260" y="-56" width="520" height="112" rx="56" fill="#1668FF"/><text y="18" font-size="50" font-weight="900" text-anchor="middle" fill="#fff" font-family="Vazirmatn">ساخت حساب رایگان</text></g>`;
  // Holu and Tooti peek in from the bottom and wave
  const peek = ease.back(prog(t, BRAND + 5.8, BRAND + 6.4));
  s += holu(t, { x: 150, y: 2260 - peek * 390, s: 0.7, m: mood('happy'), look: [0.3, -0.6], handL: [-160, -330 + Math.sin(t * 8) * 30], handR: [150, -110] });
  s += tooti(t, { x: 930, y: 2260 - peek * 390, s: 0.7, m: mood('happy'), look: [-0.3, -0.6], handR: [160, -340 + Math.sin(t * 8 + 1) * 30], handL: [-150, -110] });
  return { body: s, vig: 0.4 };
}

const SHOTS = [[0, shotEnvelope], [5.4, shotWin], [9.4, shotAward], [14.0, shotMakeUp], [20.8, shotDM], [23.6, shotMakeUp], [26.0, shotKick], [BRAND, brandCard]];
window.SHOT = t => shotAt(t, SHOTS)[1](t);
