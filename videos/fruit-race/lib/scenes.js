/* Miveh-Star sets, drawn in a 1080x1920 world. */

// ---------------------------------------------------------------- talent-show stage (day)
function stageBG(t) {
  let s = `<rect width="1080" height="1920" fill="url(#gWall)"/>`;
  // light beams
  for (const [x, a, d] of [[150, 18, 0], [930, -18, 1.3], [540, 0, 2.1]]) {
    const sw = Math.sin(t * 0.7 + d) * 6;
    s += `<g transform="translate(${x},-40) rotate(${f1(a + sw)})"><path d="M-30,0 L30,0 L260,1500 L-260,1500 Z" fill="url(#gBeam)" opacity="0.5"/></g>`;
  }
  // marquee sign
  s += `<g transform="translate(540,400)">`;
  s += `<rect x="-380" y="-130" width="760" height="260" rx="40" fill="#2b0f4a" stroke="url(#gGold)" stroke-width="16"/>`;
  for (let i = 0; i < 26; i++) {
    const k = i / 26, per = 2 * (760 + 260);
    let d = k * per, x, y;
    if (d < 760) { x = -380 + d; y = -130; } else if ((d -= 760) < 260) { x = 380; y = -130 + d; }
    else if ((d -= 260) < 760) { x = 380 - d; y = 130; } else { d -= 760; x = -380; y = 130 - d; }
    const on = (Math.floor(t * 6) + i) % 3 !== 0;
    s += `<circle cx="${f1(x)}" cy="${f1(y)}" r="11" fill="${on ? '#fff3b0' : '#8a6a2a'}"/>`;
    if (on) s += `<circle cx="${f1(x)}" cy="${f1(y)}" r="22" fill="#ffe27a" opacity="0.25"/>`;
  }
  s += `<text x="0" y="-12" font-size="104" font-weight="900" text-anchor="middle" fill="url(#gGold)" font-family="Vazirmatn" stroke="#7a4a08" stroke-width="3">آبمیوه طلایی</text>`;
  s += `<text x="0" y="78" font-size="44" font-weight="800" text-anchor="middle" fill="#ffd9f0" font-family="Vazirmatn">جستجوی سفیر برند</text>`;
  s += `</g>`;
  // floor
  s += `<rect y="1240" width="1080" height="680" fill="url(#gFloor)"/>`;
  for (let i = -8; i <= 8; i++) s += `<path d="M${540 + i * 40},1240 L${540 + i * 170},1920" stroke="#5a2a12" stroke-width="3" opacity="0.5"/>`;
  s += `<rect y="1236" width="1080" height="10" fill="#ffcf6b" opacity="0.5"/>`;
  // curtains
  for (const side of [0, 1]) {
    const x0 = side ? 950 : 0;
    s += `<rect x="${x0}" y="0" width="130" height="1300" fill="#b3122e"/>`;
    for (let i = 0; i < 4; i++) s += `<rect x="${x0 + i * 34 + 6}" y="0" width="16" height="1300" fill="#7e0a1f" opacity="0.6"/>`;
  }
  s += `<path d="M0,0 H1080 V150 ${Array.from({ length: 9 }, (_, i) => `Q${1080 - i * 120 - 60},${230} ${1080 - (i + 1) * 120},150`).join(' ')} Z" fill="#c8173a"/>`;
  s += `<rect width="1080" height="40" fill="url(#gGold)"/>`;
  return s;
}

function spotPool(x, y, w) {
  return `<ellipse cx="${x}" cy="${y}" rx="${w}" ry="${w * 0.22}" fill="url(#gSpot)"/>`;
}

function podium(x, y) {
  return `<g transform="translate(${x},${y})">` +
    `<path d="M-170,0 L170,0 L145,250 L-145,250 Z" fill="url(#gPodium)" stroke="#5a0e1e" stroke-width="5"/>` +
    `<rect x="-185" y="-22" width="370" height="30" rx="10" fill="url(#gGold)" stroke="#8a5a10" stroke-width="4"/>` +
    `<circle cx="0" cy="120" r="64" fill="url(#gGold)" stroke="#8a5a10" stroke-width="5"/>` +
    `<path d="M-24,86 L24,86 L18,156 L-18,156 Z" fill="#ff9a2a" stroke="#7a4a08" stroke-width="4"/><path d="M8,86 L26,62" stroke="#d6283c" stroke-width="6" stroke-linecap="round"/>` +
    `</g>`;
}

function crowd(t, y0 = 1600) {
  let s = '';
  const kinds = ['grape', 'orange', 'cherry', 'kiwi'];
  for (let i = 0; i < 11; i++) {
    const x = 40 + i * 100 + rnd(i) * 30, bob = Math.abs(Math.sin(t * 4 + i * 1.7)) * 16;
    const y = y0 + 40 + rnd(i + 30) * 60 - bob, kind = kinds[i % 4];
    const col = '#1a0c2e';
    if (kind === 'grape') for (const [dx, dy] of [[-28, -10], [0, -14], [28, -10], [-14, 18], [14, 18], [0, 44]]) s += `<circle cx="${x + dx}" cy="${y + dy}" r="26" fill="${col}"/>`;
    else if (kind === 'orange') s += `<circle cx="${x}" cy="${y + 10}" r="62" fill="${col}"/>`;
    else if (kind === 'cherry') s += `<circle cx="${x - 26}" cy="${y + 20}" r="38" fill="${col}"/><circle cx="${x + 26}" cy="${y + 26}" r="38" fill="${col}"/><path d="M${x - 26},${y - 14} Q${x},${y - 80} ${x + 26},${y - 8}" stroke="${col}" stroke-width="8" fill="none"/>`;
    else s += `<ellipse cx="${x}" cy="${y + 10}" rx="56" ry="66" fill="${col}"/>`;
  }
  return s + `<rect y="${y0 + 120}" width="1080" height="400" fill="#1a0c2e"/>`;
}

// ---------------------------------------------------------------- night alley
function nightBG(t) {
  let s = `<rect width="1080" height="1920" fill="url(#gNight)"/>`;
  for (let i = 0; i < 40; i++) {
    const x = rnd(i) * 1080, y = rnd(i + 99) * 620, tw = 0.4 + 0.6 * Math.abs(Math.sin(t * 2 + i));
    s += `<circle cx="${f1(x)}" cy="${f1(y)}" r="${f1(1.5 + rnd(i + 7) * 2.5)}" fill="#fff" opacity="${f1(tw)}"/>`;
  }
  s += `<circle cx="860" cy="300" r="190" fill="#cfe0ff" opacity="0.12"/><circle cx="860" cy="300" r="92" fill="#f3f0dc"/><circle cx="830" cy="280" r="18" fill="#d9d4bb"/><circle cx="885" cy="330" r="12" fill="#d9d4bb"/>`;
  // brick wall
  s += `<rect y="640" width="1080" height="720" fill="#2a2342"/>`;
  for (let r = 0; r < 14; r++) for (let c = 0; c < 10; c++) {
    const x = c * 120 + (r % 2) * 60 - 60, y = 650 + r * 52;
    s += `<rect x="${x}" y="${y}" width="112" height="44" rx="4" fill="#352c52" opacity="${f1(0.55 + rnd(r * 10 + c) * 0.45)}"/>`;
  }
  // street lamp
  s += `<path d="M140,640 L-140,1360 L420,1360 Z" fill="url(#gLamp)" opacity="${f1(0.55 + Math.sin(t * 13) * 0.02)}"/>`;
  s += `<rect x="126" y="560" width="22" height="800" fill="#1a1626"/><path d="M100,560 L180,560 L160,620 L120,620 Z" fill="#1a1626"/><circle cx="140" cy="630" r="16" fill="#ffe7a3"/><circle cx="140" cy="630" r="50" fill="#ffd36b" opacity="0.25"/>`;
  // ground
  s += `<rect y="1350" width="1080" height="570" fill="url(#gGround)"/>`;
  s += `<ellipse cx="160" cy="1390" rx="260" ry="40" fill="#ffd36b" opacity="0.18"/>`;
  return s;
}

function crate(x, top) {
  let s = `<g transform="translate(${x},${top})">`;
  s += `<rect x="-170" y="0" width="340" height="190" rx="8" fill="#8a5a2e" stroke="#3e2410" stroke-width="5"/>`;
  for (const y of [40, 95, 150]) s += `<rect x="-170" y="${y - 8}" width="340" height="12" fill="#5e3a18"/>`;
  s += `<path d="M-170,0 L170,190 M170,0 L-170,190" stroke="#5e3a18" stroke-width="10" opacity="0.6"/>`;
  s += `<text x="0" y="120" font-size="38" font-weight="900" text-anchor="middle" fill="#3e2410" opacity="0.7" font-family="Vazirmatn">سیب درجه یک</text>`;
  return s + `</g>`;
}

const SCENE_DEFS = `
<linearGradient id="gWall" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#2a1350"/><stop offset="0.55" stop-color="#6a2a8a"/><stop offset="1" stop-color="#3a1a5a"/></linearGradient>
<linearGradient id="gBeam" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#fff6d8" stop-opacity="0.55"/><stop offset="1" stop-color="#fff6d8" stop-opacity="0"/></linearGradient>
<linearGradient id="gFloor" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#b8682f"/><stop offset="1" stop-color="#5e2a12"/></linearGradient>
<linearGradient id="gPodium" x1="0" x2="1"><stop offset="0" stop-color="#8e1430"/><stop offset="0.5" stop-color="#d6283c"/><stop offset="1" stop-color="#8e1430"/></linearGradient>
<linearGradient id="gNight" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#070b1f"/><stop offset="0.6" stop-color="#1a2350"/><stop offset="1" stop-color="#0c0f24"/></linearGradient>
<linearGradient id="gGround" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#1c1836"/><stop offset="1" stop-color="#0a0818"/></linearGradient>
<linearGradient id="gLamp" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#ffd36b" stop-opacity="0.45"/><stop offset="1" stop-color="#ffd36b" stop-opacity="0.02"/></linearGradient>
<radialGradient id="gUnder" cx="0.5" cy="0.9" r="0.6"><stop offset="0" stop-color="#9be36b" stop-opacity="0.35"/><stop offset="1" stop-color="#9be36b" stop-opacity="0"/></radialGradient>
`;
