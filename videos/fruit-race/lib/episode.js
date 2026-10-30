/* Episode shell: camera helpers, overlays (title, cards, end card, subtitles, flash) and the render loop.
   An episode defines window.SHOT = t => ({ body, cam, filter, vig, tint }) in shots.js. */

const $ = id => document.getElementById(id);
const NAMES = { ananas: ['آقای آناناس', '#ffc23d'], moozi: ['موزی', '#fff07a'], holu: ['هلو', '#ffb48c'], kermo: ['کرمو', '#9be36b'], tooti: ['توتی', '#ff8a95'] };
const cam = (cx, cy, z, sx = 0, sy = 0) => `translate(${f1(540 + sx)},${f1(860 + sy)}) scale(${z.toFixed(4)}) translate(${f1(-cx)},${f1(-cy)})`;
const shake = (t, a, amp) => t < a ? [0, 0] : [amp * Math.exp(-(t - a) * 7) * Math.sin((t - a) * 71), amp * Math.exp(-(t - a) * 7) * Math.cos((t - a) * 53)];
// pick the shot whose start time is the latest one <= t
const shotAt = (t, list) => { let cur = list[0]; for (const s of list) if (t >= s[0]) cur = s; return cur; };

function overlays(t) {
  // flash
  const F = (TL.flashes || []).find(a => t >= a && t < a + 0.25);
  $('flash').style.opacity = F !== undefined ? f1(0.5 * (1 - prog(t, F, F + 0.25))) : 0;

  // title card
  const tt = TL.title, T = $('title');
  if (tt && t > tt.from - 0.05 && t < tt.to + 0.05) {
    const ti = ease.out(prog(t, tt.from, tt.from + 0.3)), to = ease.in(prog(t, tt.to - 0.3, tt.to));
    T.style.display = 'block';
    T.querySelector('.band').style.transform = `translateX(${f1((1 - ti) * 1400 - to * 1400)}px) rotate(-8deg)`;
    T.querySelector('.name').style.transform = `translateX(${f1(-to * 1400)}px) scale(${f1(spring(t, tt.from + 0.25, 2.2, 0.35))})`;
    const e = T.querySelector('.ep');
    e.textContent = tt.text;
    e.style.opacity = f1(prog(t, tt.from + 0.55, tt.from + 0.85));
    e.style.transform = `translateX(${f1(-to * 1400)}px) translateY(${f1((1 - ease.out(prog(t, tt.from + 0.55, tt.from + 0.85))) * 60)}px)`;
  } else T.style.display = 'none';

  // black text cards ("that night...")
  const C = $('card'), cd = (TL.cards || []).find(c => t >= c.from && t <= c.to);
  if (cd) {
    const cIn = prog(t, cd.from, cd.from + 0.25), cOut = prog(t, cd.to - 0.3, cd.to);
    C.style.display = 'block';
    C.style.opacity = f1(Math.min(cIn, 1 - cOut));
    const x = C.querySelector('.t1');
    x.textContent = cd.text;
    x.style.opacity = f1(prog(t, cd.from + 0.2, cd.from + 0.5));
    x.style.letterSpacing = f1(lerp(8, 0, ease.out(prog(t, cd.from + 0.2, cd.to - 0.3)))) + 'px';
  } else C.style.display = 'none';

  // end card
  const E = $('end'), en = TL.end;
  if (en && t >= en.from) {
    E.style.display = 'block';
    E.querySelector('.t1').textContent = en.t1;
    E.querySelector('.t2').textContent = en.t2;
    E.querySelector('.t3').textContent = en.t3;
    E.querySelector('.t1').style.transform = `scale(${f1(spring(t, en.from + 0.1, 2.4, 0.4))})`;
    E.querySelector('.t2').style.opacity = f1(prog(t, en.from + 0.6, en.from + 0.9));
    E.querySelector('.t2').style.transform = `translateY(${f1((1 - ease.out(prog(t, en.from + 0.6, en.from + 0.9))) * 40)}px)`;
    E.querySelector('.t3').style.opacity = f1(prog(t, en.from + 1.1, en.from + 1.4));
  } else E.style.display = 'none';

  // subtitles
  const L = TL.lines.find(l => t >= l.from - 0.08 && t <= l.to + 0.35), S = $('sub');
  if (L && !(TL.noSubsAfter && t > TL.noSubsAfter)) {
    const p = spring(t, L.from - 0.08, 3, 0.5), out = prog(t, L.to + 0.2, L.to + 0.35);
    S.style.display = 'flex';
    S.style.opacity = f1(1 - out);
    S.querySelector('.box').style.transform = `scale(${f1(0.85 + 0.15 * p)})`;
    S.querySelector('.who').textContent = NAMES[L.who][0];
    S.querySelector('.who').style.color = NAMES[L.who][1];
    S.querySelector('.txt').textContent = L.text;
  } else S.style.display = 'none';
}

window.render = function (t) {
  t = Math.min(t, TL.duration - 1e-3);
  const r = window.SHOT(t);
  const W = $('world');
  W.setAttribute('transform', r.cam || '');
  W.innerHTML = r.body;
  $('v').style.filter = r.filter || 'none';
  $('fx').innerHTML = `<rect width="1080" height="1920" fill="url(#gVig)" opacity="${r.vig === undefined ? 0.55 : r.vig}"/>` + (r.top || '');
  overlays(t);
};
