/* Composed sets shared by episodes 2-5. */

// follower count in Persian: 940 -> ۹۴۰, 12400 -> ۱۲٫۴ هزار, 120000 -> ۱۲۰ هزار
function fmtK(n) {
  n = Math.round(n);
  if (n < 1000) return fa(n);
  const k = n / 1000;
  const s = k >= 100 ? Math.round(k).toString() : k.toFixed(1).replace(/\.0$/, '');
  return fa(s).replace('.', '٫') + ' هزار';
}

// talent-show stage with Ananas at the podium; c = { ananas:{}, moozi:{}|null, holu:{}|null, extra:'' , crowd: speed }
function stageSet(t, c) {
  let s = stageBG(t);
  s += spotPool(540, 1250, 260) + spotPool(215, 1345, 170) + spotPool(860, 1345, 170);
  if (c.behind) s += c.behind;
  s += ananas(t, Object.assign({ x: 540, y: 1140, s: 0.95, m: mood('stern'), handL: [-165, -150], handR: [130, -110] }, c.ananas || {}));
  s += podium(540, 1092);
  if (c.moozi) s += moozi(t, Object.assign({ x: 215, y: 1345, s: 0.8, m: mood('proud'), look: [0.7, -0.1], handL: [-30, -175], handR: [44, -165] }, c.moozi));
  if (c.holu) s += holu(t, Object.assign({ x: 860, y: 1345, s: 0.82, m: mood('neutral'), look: [-0.7, -0.1] }, c.holu));
  if (c.extra) s += c.extra;
  s += crowdAt(t, 1620, c.crowd || 4);
  return s;
}

function crowdAt(t, y0, speed) {
  // same silhouettes as crowd(), bobbing faster when excited
  return crowd(t * speed / 4, y0);
}

// night alley with the crate
function alleySet(t, extra = '') {
  return nightBG(t) + crate(770, 1170) + extra;
}

// big phone filling the frame
function phoneShot(t, screen, z = 2.2, rot = -3, bg = '#0b0a1c', glow = '#2a3a8a') {
  return `<rect width="1080" height="1920" fill="${bg}"/><circle cx="540" cy="860" r="700" fill="${glow}" opacity="0.35"/>` + phone(540, 900, z, rot, screen, 0);
}

// rotating burst rays behind a hero object
function rays(t, cx, cy, col = '#ffe14d', n = 16, op = 0.25) {
  let s = '';
  for (let i = 0; i < n; i++) {
    const a = (i / n) * 360 + t * 20;
    s += `<path transform="translate(${cx},${cy}) rotate(${f1(a)})" d="M0,0 L-60,-1400 L60,-1400 Z" fill="${col}" opacity="${op}"/>`;
  }
  return s;
}

function sparkleStar(x, y, sz, rot = 0, col = '#fff8c4') {
  return `<path transform="translate(${f1(x)},${f1(y)}) rotate(${f1(rot)})" d="M0,${-sz} L${sz * 0.25},${-sz * 0.25} L${sz},0 L${sz * 0.25},${sz * 0.25} L0,${sz} L${-sz * 0.25},${sz * 0.25} L${-sz},0 L${-sz * 0.25},${-sz * 0.25} Z" fill="${col}"/>`;
}

function sweat(x, y, s = 1) {
  return `<path transform="translate(${f1(x)},${f1(y)}) scale(${s})" d="M0,-24 C10,-6 16,4 16,12 a16,16 0 0 1 -32,0 C-16,4 -10,-6 0,-24 Z" fill="#9fd8ff" stroke="#3a7aa8" stroke-width="3"/>`;
}
