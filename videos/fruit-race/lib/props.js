/* Miveh-Star: Tooti, Holu's kitchen, phone/laptop screens, ClickPop UI and logo, and small props. */

// ---------------------------------------------------------------- TOOTI (strawberry)
function tooti(t, o) {
  const c = charState('tooti', t, o, 5);
  const handL = c.handL || [-150, -120], handR = c.handR || [150, -120];
  let s = legs([-42, 42], -70, '#18a058', '#3a2416');
  s += `<path d="M0,-52 C-92,-62 -162,-200 -152,-292 C-142,-362 -62,-372 0,-346 C62,-372 142,-362 152,-292 C162,-200 92,-62 0,-52 Z" fill="url(#gBerry)" stroke="#8e1424" stroke-width="5"/>`;
  for (let r = 0; r < 6; r++) for (let k = -3; k <= 3; k++) {
    const x = k * 44 + (r % 2) * 22, y = -300 + r * 42;
    if (Math.abs(x) > 150 - r * 14 || (r < 2 && Math.abs(x) < 40) || (r >= 2 && r <= 3 && Math.abs(x) < 110 && y > -280 && y < -170)) continue;
    s += `<ellipse cx="${x}" cy="${y}" rx="5" ry="8" fill="#ffe27a" stroke="#c99a1e" stroke-width="1.5"/>`;
  }
  s += `<ellipse cx="-72" cy="-300" rx="30" ry="16" fill="#fff" opacity="0.35" transform="rotate(-30 -72 -300)"/>`;
  for (let i = 0; i < 6; i++) {
    const a = -150 + i * 24;
    s += `<path transform="translate(0,-348) rotate(${a + 90})" d="M0,0 C18,10 30,40 0,72 C-30,40 -18,10 0,0 Z" fill="${i % 2 ? '#3fa34d' : '#5cc46a'}" stroke="#23702f" stroke-width="4"/>`;
  }
  s += `<path d="M0,-350 q-4,-26 6,-40" stroke="#2f8a43" stroke-width="10" fill="none" stroke-linecap="round"/>`;
  s += face({ x: 0, y: -248, gap: 50, r: 25, my: 70, mw: 28, m: c.m, open: c.open, look: c.look, blink: c.blink, lidColor: '#ff5468', outline: '#4a0f18', blush: '#ffb0c0' });
  for (const sx of [-1, 1]) s += `<circle cx="${sx * 50}" cy="-248" r="36" fill="none" stroke="#1b1430" stroke-width="7"/>`;
  s += `<path d="M-14,-252 Q0,-262 14,-252" stroke="#1b1430" stroke-width="6" fill="none"/>`;
  if (c.prop) s += c.prop;
  s += arm(-140, -190, handL[0], handL[1], -30, '#ff5468', '#fff', '#3a2416');
  s += arm(140, -190, handR[0], handR[1], 30, '#ff5468', '#fff', '#3a2416');
  if (c.propFront) s += c.propFront;
  return place(c, s);
}

// ---------------------------------------------------------------- Holu's kitchen
function kitchenBG(t, night = true) {
  let s = `<rect width="1080" height="1920" fill="url(#${night ? 'gKitN' : 'gKitD'})"/>`;
  // window
  s += `<rect x="610" y="380" width="340" height="380" rx="20" fill="${night ? '#0c1236' : '#9fd8ff'}" stroke="#7a4a2a" stroke-width="18"/>`;
  if (night) {
    s += `<circle cx="860" cy="470" r="46" fill="#f3f0dc"/>`;
    for (let i = 0; i < 12; i++) s += `<circle cx="${f1(630 + rnd(i + 50) * 300)}" cy="${f1(400 + rnd(i + 70) * 340)}" r="2.5" fill="#fff" opacity="${f1(0.4 + 0.6 * Math.abs(Math.sin(t * 2 + i)))}"/>`;
  } else s += `<circle cx="860" cy="470" r="50" fill="#fff4b0"/>`;
  s += `<path d="M780,380 V760 M610,570 H950" stroke="#7a4a2a" stroke-width="12"/>`;
  // shelf with jars
  s += `<rect x="90" y="560" width="400" height="18" fill="#7a4a2a"/>`;
  [['#ff9a3d', 120], ['#ffd84a', 200], ['#6cc96c', 280], ['#ff5d8f', 360], ['#9a7bff', 430]].forEach(([c, x], i) => {
    s += `<rect x="${x}" y="${470 - (i % 2) * 20}" width="56" height="${90 + (i % 2) * 20}" rx="12" fill="${c}" opacity="0.85" stroke="#5a3418" stroke-width="3"/><rect x="${x - 4}" y="${458 - (i % 2) * 20}" width="64" height="16" rx="5" fill="#5a3418"/>`;
  });
  // fairy lights
  for (let i = 0; i < 14; i++) {
    const x = 60 + i * 72, y = 250 + Math.sin(i * 0.9) * 18;
    s += `<circle cx="${x}" cy="${f1(y)}" r="9" fill="#ffe7a3" opacity="${f1(0.6 + 0.4 * Math.sin(t * 3 + i))}"/><circle cx="${x}" cy="${f1(y)}" r="22" fill="#ffd36b" opacity="0.2"/>`;
  }
  s += `<path d="M60,250 ${Array.from({ length: 13 }, (_, i) => `L${60 + (i + 1) * 72},${f1(250 + Math.sin((i + 1) * 0.9) * 18)}`).join(' ')}" stroke="#3a2a20" stroke-width="2" fill="none"/>`;
  // counter
  s += `<rect x="0" y="1100" width="1080" height="40" fill="#f4efe6"/><rect x="0" y="1140" width="1080" height="330" fill="url(#gCab)"/>`;
  for (let i = 0; i < 5; i++) s += `<rect x="${20 + i * 214}" y="1170" width="190" height="270" rx="12" fill="none" stroke="#8e4a2a" stroke-width="5"/><circle cx="${190 + i * 214}" cy="1300" r="9" fill="#ffd36b"/>`;
  s += `<rect y="1470" width="1080" height="1200" fill="url(#gTiles)"/>`;
  for (let i = 0; i < 12; i++) s += `<path d="M${i * 100},1470 L${i * 100 - 120},1920" stroke="#000" stroke-width="2" opacity="0.15"/>`;
  return s;
}

function pan(x, y, t, steam = 1) {
  let s = `<g transform="translate(${x},${y})"><rect x="-150" y="-20" width="300" height="20" fill="#2a2a33"/><ellipse cx="0" cy="-20" rx="150" ry="26" fill="#3a3a46"/><ellipse cx="0" cy="-22" rx="128" ry="18" fill="#ff9a3d"/><ellipse cx="-40" cy="-24" rx="22" ry="8" fill="#ffd84a"/><ellipse cx="34" cy="-22" rx="20" ry="8" fill="#6cc96c"/><rect x="150" y="-16" width="140" height="14" rx="7" fill="#2a2a33"/>`;
  for (let i = 0; i < 4 * steam; i++) {
    const k = ((t * 0.6 + i / 4) % 1), xx = -60 + i * 40 + Math.sin(t * 3 + i) * 10;
    s += `<circle cx="${f1(xx)}" cy="${f1(-40 - k * 220)}" r="${f1(18 + k * 30)}" fill="#fff" opacity="${f1(0.35 * (1 - k))}"/>`;
  }
  return s + `</g>`;
}

function ringLight(x, y, screen) {
  return `<g transform="translate(${x},${y})"><path d="M0,0 L-60,420 M0,0 L60,420 M0,0 L0,420" stroke="#2a2a33" stroke-width="10"/>` +
    `<circle cx="0" cy="-60" r="120" fill="none" stroke="#fff6e0" stroke-width="26"/><circle cx="0" cy="-60" r="150" fill="none" stroke="#fff6e0" stroke-width="2" opacity="0.4"/>` +
    phone(0, -60, 0.24, 0, screen) + `</g>`;
}

// ---------------------------------------------------------------- laptop
function laptop(x, y, scale, screen) {
  return `<g transform="translate(${f1(x)},${f1(y)}) scale(${scale})">` +
    `<rect x="-340" y="-440" width="680" height="440" rx="26" fill="#1b1b24" stroke="#000" stroke-width="5"/>` +
    `<g transform="translate(-320,-420)"><rect width="640" height="400" rx="8" fill="#f6f8ff"/><svg width="640" height="400" viewBox="0 0 640 400" overflow="hidden">${screen}</svg></g>` +
    `<path d="M-400,0 L400,0 L370,40 L-370,40 Z" fill="#c9ccd8" stroke="#6b6e7a" stroke-width="4"/><rect x="-70" y="2" width="140" height="10" rx="5" fill="#9a9dab"/></g>`;
}

// ---------------------------------------------------------------- ClickPop logo (brand kit v6)
// Symbol built on the v6 grid: 64-unit squares, 24 gaps, a 1:2 blade, radii 8/16/32/12, frame 392x336.
const CP_BODY = 'M17.69,187.38 L384.84,3.58 Q392,0 392,8 L392,72 L168,184 L392,184 L392,336 L264,336 L264,288 A16,16 0 0 1 280,272 L316,272 A12,12 0 0 0 316,248 L32,248 A32,32 0 0 1 17.69,187.38 Z';
const cpSquare = x => `M${x},336 L${x},288 A16,16 0 0 1 ${x + 16},272 L${x + 48},272 A16,16 0 0 1 ${x + 64},288 L${x + 64},320 A16,16 0 0 1 ${x + 48},336 Z`;
const CP_BLUE = '#0D3AB8', CP_ORANGE = '#FF7B1A', CP_NAVY = '#081C3E';
// (x,y) centre, size = width. draw 0..1 sweeps the blade in, pop 0..1 pops the squares in (orange first).
function cpIcon(x, y, size, draw = 1, pop = 1, col = CP_BLUE) {
  const k = size / 392;
  let s = `<g transform="translate(${f1(x)},${f1(y)}) scale(${k.toFixed(4)}) translate(-196,-168)">`;
  if (draw > 0.001) {
    const w = 392 * clamp(draw);
    s += `<clipPath id="cpc${Math.round(x)}${Math.round(y)}"><rect x="0" y="-10" width="${f1(w)}" height="360"/></clipPath><path d="${CP_BODY}" fill="${col}" clip-path="url(#cpc${Math.round(x)}${Math.round(y)})"/>`;
  }
  [[0, CP_ORANGE], [88, col], [176, col]].forEach(([sx, c], i) => {
    const p = clamp(pop * 3 - i);
    if (p <= 0) return;
    const sc = i === 0 ? ease.back(p) : ease.out(p);
    s += `<g transform="translate(${sx + 32},304) scale(${f1(sc)}) translate(${-sx - 32},-304)"><path d="${cpSquare(sx)}" fill="${c}"/></g>`;
  });
  return s + `</g>`;
}
// Persian wordmark from the brand kit (raster, transparent); h = height in px, x,y = centre
function cpWordmark(x, y, h, variant = 'white') {
  const w = h * 672 / 150;
  return `<image href="../lib/assets/cp-wordmark-${variant}.png" x="${f1(x - w / 2)}" y="${f1(y - h / 2)}" width="${f1(w)}" height="${f1(h)}"/>`;
}

// ---------------------------------------------------------------- ClickPop order screen (640x400)
// step: 0 none, 1 Instagram, 2 followers, 3 price row, 4 link typed (chars), 5 submit pressed
function cpOrderScreen(step, chars, press = 0) {
  const link = 'instagram.com/holu.kitchen'.slice(0, chars);
  let s = `<rect width="640" height="400" fill="#f4f6fb"/>`;
  s += `<rect width="640" height="50" fill="#fff"/><rect y="49" width="640" height="1" fill="#e3e7f0"/>`;
  s += cpWordmark(556, 26, 28, 'navy');
  s += `<rect x="20" y="13" width="86" height="26" rx="8" fill="#081C3E"/><text x="63" y="32" font-size="13" font-weight="800" text-anchor="middle" fill="#fff" font-family="Vazirmatn">پنل من</text>`;
  s += `<rect x="20" y="62" width="600" height="326" rx="16" fill="#fff" stroke="#e3e7f0" stroke-width="2"/>`;
  s += `<text x="600" y="92" font-size="19" font-weight="900" text-anchor="end" fill="#081C3E" font-family="Vazirmatn">سفارش سریع</text>`;
  s += `<text x="600" y="120" font-size="13" font-weight="700" text-anchor="end" fill="#6b7590" font-family="Vazirmatn">۱. کدام برنامه؟</text>`;
  const apps = [['اینستاگرام', 'url(#gStory)'], ['تلگرام', '#2aa3e0'], ['یوتیوب', '#ff2d2d'], ['تیک‌تاک', '#111']];
  apps.forEach(([n, c], i) => {
    const x = 520 - i * 120, sel = i === 0 && step >= 1;
    s += `<rect x="${x}" y="130" width="96" height="66" rx="12" fill="${sel ? '#eaf1ff' : '#fff'}" stroke="${sel ? '#0D3AB8' : '#e3e7f0'}" stroke-width="${sel ? 3 : 2}"/>`;
    s += `<rect x="${x + 34}" y="138" width="28" height="28" rx="8" fill="${c}"/>`;
    s += `<text x="${x + 48}" y="188" font-size="12" font-weight="700" text-anchor="middle" fill="#081C3E" font-family="Vazirmatn">${n}</text>`;
  });
  s += `<text x="600" y="222" font-size="13" font-weight="700" text-anchor="end" fill="#6b7590" font-family="Vazirmatn">۲. چه چیزی لازم دارید؟</text>`;
  ['فالوور', 'لایک', 'بازدید', 'کامنت'].forEach((n, i) => {
    const x = 530 - i * 86, sel = i === 0 && step >= 2;
    s += `<rect x="${x}" y="232" width="74" height="32" rx="10" fill="${sel ? '#0D3AB8' : '#fff'}" stroke="${sel ? '#0D3AB8' : '#e3e7f0'}" stroke-width="2"/><text x="${x + 37}" y="254" font-size="14" font-weight="800" text-anchor="middle" fill="${sel ? '#fff' : '#081C3E'}" font-family="Vazirmatn">${n}</text>`;
  });
  s += `<rect x="40" y="276" width="560" height="30" rx="8" fill="${step >= 3 ? '#fff1e6' : '#f4f6fb'}" stroke="${step >= 3 ? '#E86A00' : 'none'}" stroke-width="2"/>`;
  s += `<text x="588" y="296" font-size="13" font-weight="800" text-anchor="end" fill="#081C3E" font-family="Vazirmatn">قیمت هر ۱۰۰۰ · حداقل و حداکثر · سرعت</text><text x="52" y="296" font-size="13" font-weight="800" fill="#BF5200" font-family="Vazirmatn">پیش از پرداخت</text>`;
  s += `<text x="600" y="326" font-size="13" font-weight="700" text-anchor="end" fill="#6b7590" font-family="Vazirmatn">۳. لینک عمومی پیج</text>`;
  s += `<rect x="190" y="334" width="410" height="38" rx="10" fill="#fff" stroke="${step >= 4 ? '#0D3AB8' : '#d5dbe8'}" stroke-width="2"/>`;
  s += `<text x="200" y="359" font-size="16" font-weight="600" fill="#081C3E" font-family="Vazirmatn" direction="ltr">${link}${step === 4 && chars < 26 ? '|' : ''}</text>`;
  const pr = press ? 2 : 0;
  s += `<rect x="40" y="${334 + pr}" width="136" height="38" rx="10" fill="${press ? '#0A2E94' : '#0D3AB8'}"/><text x="108" y="${359 + pr}" font-size="16" font-weight="900" text-anchor="middle" fill="#fff" font-family="Vazirmatn">ثبت سفارش</text>`;
  if (step >= 4) s += `<text x="395" y="392" font-size="13" font-weight="800" text-anchor="middle" fill="#18a058" font-family="Vazirmatn">✓ رمز عبور لازم نیست</text>`;
  return s;
}

// progress p 0..1 of delivered bar (stops at 0.92), refund 0..1 shows the refund panel
function cpProgressScreen(p, refund) {
  let s = `<rect width="640" height="400" fill="#f4f6fb"/><rect width="640" height="50" fill="#fff"/><rect y="49" width="640" height="1" fill="#e3e7f0"/>`;
  s += cpWordmark(556, 26, 28, 'navy');
  s += `<rect x="20" y="62" width="600" height="326" rx="16" fill="#fff" stroke="#e3e7f0" stroke-width="2"/>`;
  s += `<rect x="540" y="80" width="60" height="60" rx="14" fill="url(#gStory)"/>`;
  s += `<text x="526" y="104" font-size="19" font-weight="900" text-anchor="end" fill="#081C3E" font-family="Vazirmatn">فالوور اینستاگرام</text><text x="526" y="130" font-size="13" font-weight="700" text-anchor="end" fill="#6b7590" font-family="Vazirmatn">holu.kitchen · فقط لینک عمومی</text>`;
  const st = refund > 0.5 ? ['تکمیل جزئی', '#E86A00'] : p >= 0.999 ? ['تکمیل', '#18a058'] : ['در حال انجام', '#0D3AB8'];
  s += `<rect x="40" y="84" width="130" height="34" rx="17" fill="${st[1]}" opacity="0.15"/><text x="105" y="107" font-size="15" font-weight="900" text-anchor="middle" fill="${st[1]}" font-family="Vazirmatn">${st[0]}</text>`;
  s += `<text x="600" y="176" font-size="15" font-weight="700" text-anchor="end" fill="#6b7590" font-family="Vazirmatn">تعداد شروع</text><text x="40" y="176" font-size="17" font-weight="900" fill="#081C3E" font-family="Vazirmatn">${fa(940)}</text>`;
  s += `<text x="600" y="212" font-size="15" font-weight="700" text-anchor="end" fill="#6b7590" font-family="Vazirmatn">پیشرفت زنده</text><text x="40" y="212" font-size="17" font-weight="900" fill="#081C3E" font-family="Vazirmatn">${fa(Math.round(p * 100))}٪</text>`;
  s += `<rect x="40" y="226" width="560" height="16" rx="8" fill="#e3e7f0"/><rect x="${f1(40 + 560 * (1 - p))}" y="226" width="${f1(560 * p)}" height="16" rx="8" fill="url(#icB)"/>`;
  if (refund > 0) {
    const a = ease.out(clamp(refund));
    s += `<g opacity="${f1(a)}" transform="translate(0,${f1((1 - a) * 20)})"><rect x="40" y="262" width="560" height="108" rx="14" fill="#fff6ee" stroke="#E86A00" stroke-width="2.5" stroke-dasharray="7 5"/>`;
    s += `<text x="584" y="296" font-size="17" font-weight="900" text-anchor="end" fill="#BF5200" font-family="Vazirmatn">بازگشت خودکار وجه</text>`;
    s += `<text x="584" y="326" font-size="14" font-weight="700" fill="#081C3E" font-family="Vazirmatn" direction="rtl" text-anchor="start">مبلغِ مقدار تحویل‌نشده به کیف پول برگشت.</text>`;
    s += `<text x="584" y="352" font-size="13" font-weight="700" fill="#6b7590" font-family="Vazirmatn" direction="rtl" text-anchor="start">رسید در دفتر کیف پول ثبت شد.</text>`;
    s += `<g transform="translate(110,316) rotate(-10)"><rect x="-62" y="-22" width="124" height="44" rx="8" fill="none" stroke="#BF5200" stroke-width="4"/><text y="8" font-size="17" font-weight="900" text-anchor="middle" fill="#BF5200" font-family="Vazirmatn">برگشت خورد</text></g></g>`;
  }
  return s;
}

function cursorArrow(x, y, s = 1, press = 0) {
  return `<g transform="translate(${f1(x)},${f1(y)}) scale(${(s * (1 - press * 0.15)).toFixed(3)})"><path d="M0,0 L0,46 L12,35 L22,58 L31,54 L21,32 L37,32 Z" fill="#fff" stroke="#111" stroke-width="4" stroke-linejoin="round"/></g>` +
    (press ? `<circle cx="${f1(x)}" cy="${f1(y)}" r="${f1(14 + press * 26)}" fill="none" stroke="#0D3AB8" stroke-width="5" opacity="${f1(1 - press)}"/>` : '');
}

// ---------------------------------------------------------------- phone screens
function screenNotif(title, body, accent = '#ff3d5a') {
  return `<rect width="300" height="560" rx="34" fill="url(#gLock)"/>` +
    `<text x="150" y="120" font-size="64" font-weight="800" text-anchor="middle" fill="#fff" font-family="Vazirmatn">۰۲:۱۴</text>` +
    `<rect x="16" y="200" width="268" height="130" rx="22" fill="#fff" opacity="0.95"/><rect x="236" y="216" width="34" height="34" rx="9" fill="url(#gStory)"/>` +
    `<text x="226" y="240" font-size="18" font-weight="900" text-anchor="end" fill="#1b1430" font-family="Vazirmatn">${title}</text>` +
    `<text x="268" y="284" font-size="17" font-weight="800" fill="${accent}" font-family="Vazirmatn" direction="rtl" text-anchor="start">${body[0]}</text>` +
    `<text x="268" y="312" font-size="15" font-weight="600" fill="#6b6380" font-family="Vazirmatn" direction="rtl" text-anchor="start">${body[1] || ''}</text>`;
}

function screenLocked(k) {
  return `<rect width="300" height="560" rx="34" fill="#000"/>` +
    `<g opacity="${f1(k)}"><circle cx="150" cy="230" r="54" fill="none" stroke="#ff3d5a" stroke-width="8"/><rect x="122" y="226" width="56" height="44" rx="8" fill="#ff3d5a"/><path d="M132,226 V206 a18,18 0 0 1 36,0 V226" stroke="#ff3d5a" stroke-width="8" fill="none"/>` +
    `<text x="150" y="340" font-size="26" font-weight="900" text-anchor="middle" fill="#fff" font-family="Vazirmatn">از حساب خارج شدید</text>` +
    `<text x="150" y="376" font-size="17" font-weight="600" text-anchor="middle" fill="#9a93ad" font-family="Vazirmatn">رمز عبور اشتباه است</text></g>`;
}

function screenFeed(k, count) {
  let s = `<rect width="300" height="560" rx="34" fill="#fff"/><text x="150" y="44" font-size="20" font-weight="900" text-anchor="middle" fill="#1b1430" font-family="Vazirmatn">moozi.luxury</text>`;
  s += `<text x="150" y="88" font-size="${String(count).length > 5 ? 32 : 40}" font-weight="900" text-anchor="middle" fill="#ff3d5a" font-family="Vazirmatn">${count}</text><text x="150" y="112" font-size="15" font-weight="600" text-anchor="middle" fill="#6b6380" font-family="Vazirmatn">فالوور</text>`;
  for (let i = 0; i < 6; i++) {
    const x = 12 + (i % 3) * 94, y = 128 + Math.floor(i / 3) * 104;
    s += `<rect x="${x}" y="${y}" width="88" height="98" rx="8" fill="${i % 2 ? '#ff4fa3' : '#7a5cff'}"/><text x="${x + 44}" y="${y + 46}" font-size="13" font-weight="900" text-anchor="middle" fill="#fff" font-family="Vazirmatn">قرص لاغری</text><text x="${x + 44}" y="${y + 68}" font-size="13" font-weight="900" text-anchor="middle" fill="#ffe14d" font-family="Vazirmatn">معجزه!!</text>`;
  }
  ['هک شدی؟', 'این چه پستیه؟!', 'آنفالو', 'اسپم!!'].forEach((c, i) => {
    const a = clamp(k * 4 - i);
    if (a <= 0) return;
    const y = 360 + i * 46;
    s += `<g opacity="${f1(a)}" transform="translate(${f1((1 - a) * 40)},0)"><rect x="20" y="${y}" width="260" height="38" rx="19" fill="#f1eef7"/><circle cx="256" cy="${y + 19}" r="13" fill="${['#ffb48c', '#9be36b', '#ffc23d', '#9a7bff'][i]}"/><text x="236" y="${y + 26}" font-size="17" font-weight="800" text-anchor="end" fill="#1b1430" font-family="Vazirmatn">${c}</text></g>`;
  });
  return s;
}

function screenTicket(k, t) {
  let s = `<rect width="300" height="560" rx="34" fill="#12101c"/><text x="150" y="60" font-size="22" font-weight="900" text-anchor="middle" fill="#9be36b" font-family="Vazirmatn">پشتیبانی ارزون‌کده</text>`;
  s += `<rect x="40" y="100" width="240" height="70" rx="20" fill="#f7b500"/><text x="266" y="130" font-size="17" font-weight="900" text-anchor="end" fill="#1b1430" font-family="Vazirmatn">پیجم هک شد!</text><text x="266" y="156" font-size="17" font-weight="900" text-anchor="end" fill="#1b1430" font-family="Vazirmatn">کمک کنید!!!</text>`;
  if (k > 0) s += `<rect x="20" y="196" width="230" height="62" rx="20" fill="#2a2638"/><text x="236" y="234" font-size="16" font-weight="700" text-anchor="end" fill="#cfc8e0" font-family="Vazirmatn">تیکت: در حال بررسی...</text>`;
  for (let i = 0; i < 3; i++) s += `<circle cx="${40 + i * 22}" cy="300" r="7" fill="#8a8399" opacity="${f1(0.3 + 0.7 * Math.abs(Math.sin(t * 4 - i * 0.6)))}"/>`;
  return s;
}

function screenDM(k) {
  let s = `<rect width="300" height="560" rx="34" fill="#fff"/><circle cx="252" cy="50" r="22" fill="#ffb48c"/><text x="220" y="58" font-size="20" font-weight="900" text-anchor="end" fill="#1b1430" font-family="Vazirmatn">هلو</text><rect y="86" width="300" height="1" fill="#eee"/>`;
  if (k > 0) {
    const a = ease.back(clamp(k));
    s += `<g transform="translate(150,230) scale(${f1(a)}) translate(-150,-230)"><rect x="24" y="130" width="250" height="200" rx="22" fill="#eaf1ff" stroke="#0D3AB8" stroke-width="3"/>` + cpWordmark(150, 192, 44, 'navy') +
      `<text x="150" y="258" font-size="26" font-weight="900" text-anchor="middle" fill="#0D3AB8" font-family="Vazirmatn" direction="ltr">clickpop.ir</text>` +
      `<text x="150" y="300" font-size="15" font-weight="700" text-anchor="middle" fill="#6b7590" font-family="Vazirmatn">بدون رمز عبور، فقط لینک</text></g>`;
  }
  return s;
}

// ---------------------------------------------------------------- small props
function bills(x, y, rot, s = 1) {
  return `<g transform="translate(${f1(x)},${f1(y)}) rotate(${f1(rot)}) scale(${s})"><rect x="-60" y="-30" width="120" height="60" rx="6" fill="#58b368" stroke="#2e6b3a" stroke-width="3"/><circle r="17" fill="#8fd49a" stroke="#2e6b3a" stroke-width="2"/><text y="7" font-size="18" font-weight="900" text-anchor="middle" fill="#2e6b3a" font-family="Vazirmatn">۱۰۰</text></g>`;
}

function suitcase(x, y, s = 1, open = 0) {
  return `<g transform="translate(${f1(x)},${f1(y)}) scale(${s})"><path d="M-30,-82 v-22 h60 v22" stroke="#3a2416" stroke-width="10" fill="none"/>` +
    bills(-30, -78, -12, 0.8) + bills(26, -84, 14, 0.8) +
    `<rect x="-90" y="-80" width="180" height="120" rx="14" fill="#8a5a2e" stroke="#3a2416" stroke-width="5"/><rect x="-90" y="-36" width="180" height="10" fill="#5e3a18"/><rect x="-14" y="-44" width="28" height="22" rx="4" fill="#ffd36b" stroke="#3a2416" stroke-width="3"/></g>`;
}

function envelope(x, y, s, open) {
  return `<g transform="translate(${f1(x)},${f1(y)}) scale(${s})"><rect x="-110" y="-70" width="220" height="140" rx="10" fill="url(#gGold)" stroke="#9a6a10" stroke-width="5"/>` +
    `<path d="M-110,-70 L0,${f1(lerp(10, -150, open))} L110,-70" fill="#f2c040" stroke="#9a6a10" stroke-width="5" stroke-linejoin="round"/><circle cx="0" cy="0" r="18" fill="#d6283c"/></g>`;
}

function confetti(t, a, cx, cy, n = 70) {
  if (t < a) return '';
  const k = t - a;
  let s = '';
  const cols = ['#ff2e7e', '#ffe14d', '#0D3AB8', '#E86A00', '#5cc46a', '#9a7bff'];
  for (let i = 0; i < n; i++) {
    const ang = -Math.PI / 2 + (rnd(i) - 0.5) * 2.4, sp = 700 + rnd(i + 3) * 900;
    const x = cx + Math.cos(ang) * sp * k * 0.9 + Math.sin(k * 3 + i) * 30;
    const y = cy + Math.sin(ang) * sp * k + 900 * k * k;
    if (y > 2000) continue;
    s += `<rect x="-9" y="-5" width="18" height="10" fill="${cols[i % 6]}" transform="translate(${f1(x)},${f1(y)}) rotate(${f1(k * 400 * (rnd(i + 9) - 0.5) + i * 30)})"/>`;
  }
  return s;
}

function dayCard(x, y, s, day) {
  return `<g transform="translate(${f1(x)},${f1(y)}) scale(${s})"><rect x="-110" y="-120" width="220" height="240" rx="22" fill="#fff" stroke="#1b1430" stroke-width="6"/><rect x="-110" y="-120" width="220" height="70" rx="22" fill="#ff2e7e"/><rect x="-110" y="-70" width="220" height="20" fill="#ff2e7e"/>` +
    `<text y="-72" font-size="34" font-weight="900" text-anchor="middle" fill="#fff" font-family="Vazirmatn">روز</text><text y="80" font-size="130" font-weight="900" text-anchor="middle" fill="#1b1430" font-family="Vazirmatn">${fa(day)}</text></g>`;
}

function heart(x, y, s, op) {
  return `<path transform="translate(${f1(x)},${f1(y)}) scale(${f1(s)})" d="M0,12 C-20,-6 -34,-20 -20,-34 C-10,-44 0,-36 0,-26 C0,-36 10,-44 20,-34 C34,-20 20,-6 0,12 Z" fill="#ff3d5a" opacity="${f1(op)}"/>`;
}

function poster(x, y, s, stamp, t) {
  let p = `<g transform="translate(${f1(x)},${f1(y)}) scale(${s})"><path d="M-120,0 L-150,420 M120,0 L150,420" stroke="#5a3418" stroke-width="14"/>`;
  p += `<rect x="-170" y="-340" width="340" height="380" rx="12" fill="#fff8e6" stroke="#5a3418" stroke-width="8"/>`;
  p += `<g transform="translate(0,20) scale(0.62)">${moozi(t, { x: 0, y: 0, s: 1, m: mood('proud') })}</g>`;
  p += `<text y="-290" font-size="40" font-weight="900" text-anchor="middle" fill="#5a3418" font-family="Vazirmatn">موزی</text>`;
  if (stamp > 0) {
    const a = clamp(stamp), sc = lerp(2.2, 1, ease.out(a));
    p += `<g transform="translate(0,-120) rotate(-16) scale(${f1(sc)})" opacity="${f1(a)}"><rect x="-160" y="-60" width="320" height="120" rx="14" fill="none" stroke="#d6283c" stroke-width="12"/><text y="34" font-size="92" font-weight="900" text-anchor="middle" fill="#d6283c" font-family="Vazirmatn">حذف شد</text></g>`;
  }
  return p + `</g>`;
}

const PROP_DEFS = `
<radialGradient id="gBerry" cx="0.38" cy="0.3" r="0.85"><stop offset="0" stop-color="#ff8a95"/><stop offset="0.5" stop-color="#ff3d55"/><stop offset="1" stop-color="#c8162f"/></radialGradient>
<linearGradient id="gKitN" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#3b2d55"/><stop offset="1" stop-color="#5a3d6a"/></linearGradient>
<linearGradient id="gKitD" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#ffe8d6"/><stop offset="1" stop-color="#ffcfb8"/></linearGradient>
<linearGradient id="gCab" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#d9825a"/><stop offset="1" stop-color="#a85a36"/></linearGradient>
<linearGradient id="gTiles" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#e8d9c8"/><stop offset="1" stop-color="#b9a48e"/></linearGradient>
<linearGradient id="gLock" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#2a1f5a"/><stop offset="1" stop-color="#8a2a6a"/></linearGradient>
<linearGradient id="icB"><stop offset="0" stop-color="#0D3AB8"/><stop offset="1" stop-color="#0D3AB8"/></linearGradient>
<linearGradient id="icO"><stop offset="0" stop-color="#FF7B1A"/><stop offset="1" stop-color="#FF7B1A"/></linearGradient>
<linearGradient id="gInk" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#0D3AB8"/><stop offset="1" stop-color="#0D3AB8"/></linearGradient>
`;

// world position of Kermo's head for an apple placed with o = {x, y, s, emerge, lean}
function kermoHead(t, o) {
  const e = clamp(o.emerge || 0, 0, 1.2), lean = o.lean || 0;
  return [o.x + (42 + Math.sin(2.4 + t * 2) * 10 + lean * 1.2) * o.s, o.y + (-172 - 230 * e) * o.s];
}
// fake glasses and moustache over Kermo's face
function disguise(x, y, s) {
  return `<g transform="translate(${f1(x)},${f1(y)}) scale(${s})"><circle cx="-22" cy="-6" r="20" fill="#111" stroke="#000" stroke-width="3"/><circle cx="22" cy="-6" r="20" fill="#111" stroke="#000" stroke-width="3"/><path d="M-4,-8 Q0,-12 4,-8" stroke="#000" stroke-width="5" fill="none"/>` +
    `<path d="M0,16 C-12,6 -40,6 -50,22 C-38,18 -24,24 -12,24 C-4,24 0,20 0,18 C0,20 4,24 12,24 C24,24 38,18 50,22 C40,6 12,6 0,16 Z" fill="#3a2416"/></g>`;
}
