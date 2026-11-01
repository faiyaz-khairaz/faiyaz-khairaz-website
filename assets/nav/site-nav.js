/* Site menu for faiyazkhairaz.com.
   Edit MENU and BAR below to change the menu on every page at once. */
(function () {
  if (window.__siteNav) return;
  window.__siteNav = true;

  /* ---------- Settings ---------- */
  var BAR = {
    id: 'copilot-licences-2026-11',             // change the id to show a new message to everyone again
    text: 'Copilot Basic or Premium? See what your team already has.',
    link: '/copilot-basic-vs-premium/#video',
    linkText: 'Watch the 90-second video →',
    hideOn: '/copilot-basic-vs-premium/'          // no bar on the page it points to
  };
  var IMG = { copilot: '/assets/img/copilot-logo.png', featured: '/assets/img/copilot-basic-vs-premium.jpg' };
  var PHONE = '+91 98190 06132', TEL = 'tel:+919819006132';
  var WA = 'https://wa.me/919819006132?text=' + encodeURIComponent('Hi Faiyaz, I would like to discuss a training program for my team.');
  var REVIEWS = 'https://www.google.com/maps/place/IT+Corporate+Training+%E2%80%93+Advanced+Excel,+Copilot,+AI+%26+Power+BI+Corporate+Trainings+in+Mumbai/@19.0496683,72.8247554,17z/data=!3m1!4b1!4m6!3m5!1s0x3be7c946e0e1d905:0xac031917d67a620!8m2!3d19.0496683!4d72.8247554!16s%2Fg%2F11b6tr8rzf';

  var PROGRAMMES = [
    { t: 'Microsoft Copilot', s: 'Basic and Premium licences', h: '/#programs', ic: '<img src="' + IMG.copilot + '" alt="">', bg: 'var(--sn-tb)', hl: true },
    { t: 'Claude AI', s: 'Role-based for HR and Finance', h: '/#programs', ic: 'Cl', bg: '#FBEDE6', fg: '#B4532A' },
    { t: 'Advanced Excel & Dashboards', s: 'Formulas to dashboards, VBA add-on', h: '/#programs', ic: 'X', bg: 'var(--sn-tg)', fg: 'var(--sn-green)' },
    { t: 'Power BI in five levels', s: 'From Power Query to Power BI Service', h: '/#programs', ic: 'BI', bg: 'var(--sn-tgold)', fg: '#7A5B00' }
  ];
  var GUIDES = [
    { t: 'Copilot Basic vs Premium', s: 'What your team already has', h: '/copilot-basic-vs-premium/', tag: 'NEW' },
    { t: 'Ways to train', s: 'Corporate workshops or classroom in Mumbai', h: '/#ways' },
    { t: 'Free demo for HR & L&D', s: '30 minutes, up to 3 people', h: '/copilot-basic-vs-premium/#demo' },
    { t: 'Check training dates', s: 'Live availability calendar', h: '/availability/' }
  ];
  var RESULTS = [
    { t: 'Case studies', s: 'Banking, manufacturing, shipping', h: '/case-studies/' },
    { t: 'Clients', s: '160+ organisations, 20 industries', h: '/#clients' },
    { t: 'Videos', s: 'Workshops and short lessons', h: '/#videos' },
    { t: 'Certifications', s: 'Microsoft and Anthropic', h: '/#certified' },
    { t: 'Google reviews', s: '1,600+ reviews', h: REVIEWS, ext: true }
  ];
  var FINDER = [
    { t: 'I need Copilot training', s: 'Basic, Premium or a mix', h: '/copilot-basic-vs-premium/', ic: '<img src="' + IMG.copilot + '" alt="">', bg: 'var(--sn-tb)' },
    { t: 'I need Excel or Power BI', s: 'From formulas to dashboards', h: '/#programs', ic: 'X', bg: 'var(--sn-tg)', fg: 'var(--sn-green)' },
    { t: 'I want to see results', s: 'Case studies and clients', h: '/case-studies/', ic: '★', bg: 'var(--sn-tgold)', fg: '#7A5B00' },
    { t: 'I want to check dates', s: 'Live training calendar', h: '/availability/', ic: '31', bg: 'var(--sn-tb)', fg: 'var(--sn-blue)' }
  ];

  /* ---------- Helpers ---------- */
  function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
  function ext(o) { return o.ext ? ' target="_blank" rel="noopener"' : ''; }
  function icon(o) { return '<span class="sn-ic" aria-hidden="true" style="background:' + (o.bg || 'var(--sn-ground)') + ';color:' + (o.fg || 'var(--sn-ink)') + '">' + o.ic + '</span>'; }
  var path = location.pathname.replace(/index\.html$/, '');
  var header = document.querySelector('header.top');
  if (!header) return;
  var wrap = header.querySelector('.wrap') || header;
  var nav = header.querySelector('nav.nav');
  if (!nav) { nav = document.createElement('nav'); nav.className = 'nav'; wrap.insertBefore(nav, wrap.children[1] || null); }

  /* ---------- Announcement bar ---------- */
  var barKey = 'sn-bar-' + BAR.id, dismissed = false;
  try { dismissed = localStorage.getItem(barKey) === '1'; } catch (e) {}
  if (!dismissed && path.indexOf(BAR.hideOn) !== 0) {
    var bar = document.createElement('div');
    bar.className = 'sn-bar';
    bar.setAttribute('role', 'region');
    bar.setAttribute('aria-label', 'Announcement');
    bar.innerHTML = '<div class="sn-bar-in"><span class="sn-new">NEW</span><span>' + esc(BAR.text) + '</span><a href="' + BAR.link + '">' + esc(BAR.linkText) + '</a></div>' +
      '<button type="button" class="sn-x" aria-label="Close announcement">×</button>';
    header.parentNode.insertBefore(bar, header);
    bar.querySelector('.sn-x').addEventListener('click', function () {
      bar.remove();
      try { localStorage.setItem(barKey, '1'); } catch (e) {}
    });
  }

  /* ---------- Menu row ---------- */
  var cur = path.indexOf('/copilot-basic-vs-premium') === 0 ? 'training' : path.indexOf('/case-studies') === 0 ? 'results' : path.indexOf('/availability') === 0 ? 'availability' : '';
  nav.className = 'nav sn-nav';
  nav.setAttribute('aria-label', 'Main menu');
  nav.innerHTML =
    '<button type="button" class="sn-item' + (cur === 'training' ? ' sn-cur' : '') + '" id="sn-b-training" aria-expanded="false" aria-controls="sn-p-training">Training <span class="sn-caret" aria-hidden="true">▾</span></button>' +
    '<button type="button" class="sn-item' + (cur === 'results' ? ' sn-cur' : '') + '" id="sn-b-results" aria-expanded="false" aria-controls="sn-p-results">Results <span class="sn-caret" aria-hidden="true">▾</span></button>' +
    '<a class="sn-link" href="/#about">About</a>' +
    '<a class="sn-link' + (cur === 'availability' ? ' sn-cur' : '') + '" href="/availability/"' + (cur === 'availability' ? ' aria-current="page"' : '') + '>Availability</a>' +
    '<button type="button" class="sn-item sn-find" id="sn-b-finder" aria-haspopup="dialog"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg><span class="sn-find-t">What do you need?</span></button>';

  /* Header call to action: Book a demo */
  var cta = header.querySelector('.top-cta');
  if (cta) {
    var btn = cta.querySelector('.btn');
    var demo = document.createElement('a');
    demo.className = 'btn btn-green';
    demo.href = '/copilot-basic-vs-premium/#demo';
    demo.textContent = 'Book a demo';
    if (btn) btn.replaceWith(demo); else cta.appendChild(demo);
  }

  /* ---------- Panels ---------- */
  var mega = document.createElement('div');
  mega.className = 'sn-panel sn-mega';
  mega.id = 'sn-p-training';
  mega.hidden = true;
  mega.innerHTML =
    '<div class="sn-col"><p class="sn-h">Programmes</p>' + PROGRAMMES.map(function (o) {
      return '<a class="sn-row' + (o.hl ? ' sn-hl' : '') + '" href="' + o.h + '">' + icon(o) + '<span><b>' + esc(o.t) + '</b><small>' + esc(o.s) + '</small></span></a>';
    }).join('') + '</div>' +
    '<div class="sn-col"><p class="sn-h">Guides and formats</p>' + GUIDES.map(function (o) {
      return '<a class="sn-row" href="' + o.h + '"><span><b>' + esc(o.t) + (o.tag ? ' <span class="sn-tag">' + o.tag + '</span>' : '') + '</b><small>' + esc(o.s) + '</small></span></a>';
    }).join('') + '</div>' +
    '<a class="sn-feat" href="/copilot-basic-vs-premium/#video"><span class="sn-thumb"><img src="' + IMG.featured + '" alt="" loading="lazy" width="1600" height="900"><span class="sn-play" aria-hidden="true"></span></span>' +
    '<span class="sn-k">FEATURED VIDEO</span><b>Copilot Basic or Premium? Watch the 90-second explainer</b><span class="sn-go">See the difference →</span></a>';

  var drop = document.createElement('div');
  drop.className = 'sn-panel sn-drop';
  drop.id = 'sn-p-results';
  drop.hidden = true;
  drop.innerHTML = RESULTS.map(function (o) {
    return '<a class="sn-row" href="' + o.h + '"' + ext(o) + '><span><b>' + esc(o.t) + '</b><small>' + esc(o.s) + '</small></span><span class="sn-arrow" aria-hidden="true">→</span></a>';
  }).join('');
  header.appendChild(mega);
  header.appendChild(drop);

  var dim = document.createElement('div');
  dim.className = 'sn-dim';
  dim.hidden = true;
  document.body.appendChild(dim);

  var finder = document.createElement('div');
  finder.className = 'sn-finder';
  finder.setAttribute('role', 'dialog');
  finder.setAttribute('aria-modal', 'true');
  finder.setAttribute('aria-labelledby', 'sn-f-title');
  finder.hidden = true;
  finder.innerHTML = '<div class="sn-fh"><h2 id="sn-f-title">What do you need?</h2><button type="button" class="sn-close" aria-label="Close">×</button></div>' +
    '<div class="sn-grid">' + FINDER.map(function (o) {
      return '<a class="sn-opt" href="' + o.h + '">' + icon(o) + '<span><b>' + esc(o.t) + '</b><small>' + esc(o.s) + '</small></span></a>';
    }).join('') + '</div>' +
    '<a class="sn-wa" href="' + WA + '" target="_blank" rel="noopener">Not sure? Talk to Faiyaz on WhatsApp</a>' +
    '<p class="sn-note">Or call ' + PHONE + '</p>';
  document.body.appendChild(finder);

  /* ---------- Phone menu ---------- */
  var burger = document.createElement('button');
  burger.type = 'button';
  burger.className = 'sn-burger';
  burger.setAttribute('aria-label', 'Open menu');
  burger.setAttribute('aria-expanded', 'false');
  burger.setAttribute('aria-controls', 'sn-mobile');
  burger.textContent = '☰';
  (cta || wrap).appendChild(burger);

  var mob = document.createElement('div');
  mob.className = 'sn-mobile';
  mob.id = 'sn-mobile';
  mob.hidden = true;
  function sub(list, withIcons) {
    return list.map(function (o) {
      return '<a href="' + o.h + '"' + ext(o) + '>' + (withIcons && o.ic && o.ic.indexOf('<img') === 0 ? o.ic : '') + esc(o.t) + (o.tag ? ' <span class="sn-tag">' + o.tag + '</span>' : '') + '</a>';
    }).join('');
  }
  mob.innerHTML =
    '<button type="button" class="sn-acc" aria-expanded="true" aria-controls="sn-m-training">Training <span class="sn-caret" aria-hidden="true">▾</span></button>' +
    '<div class="sn-sub" id="sn-m-training">' + sub(PROGRAMMES.slice(0, 1), true) + sub(GUIDES.slice(0, 1)) + sub(PROGRAMMES.slice(1)) + sub(GUIDES.slice(1)) + '</div>' +
    '<button type="button" class="sn-acc" aria-expanded="false" aria-controls="sn-m-results">Results <span class="sn-caret" aria-hidden="true">▾</span></button>' +
    '<div class="sn-sub" id="sn-m-results" hidden>' + sub(RESULTS) + '</div>' +
    '<a class="sn-mlink" href="/#about">About</a>' +
    '<a class="sn-mlink" href="/availability/">Availability</a>' +
    '<button type="button" class="sn-acc sn-m-find">What do you need? <span aria-hidden="true">→</span></button>' +
    '<div class="sn-mcta"><a class="sn-p" href="/copilot-basic-vs-premium/#demo">Book a free demo (HR and L&amp;D)</a><a class="sn-s" href="' + WA + '" target="_blank" rel="noopener">WhatsApp ' + PHONE + '</a></div>';
  document.body.appendChild(mob);
  mob.querySelectorAll('.sn-acc[aria-controls]').forEach(function (b) {
    b.addEventListener('click', function () {
      var open = b.getAttribute('aria-expanded') !== 'true';
      b.setAttribute('aria-expanded', open);
      document.getElementById(b.getAttribute('aria-controls')).hidden = !open;
    });
  });

  /* ---------- Open and close ---------- */
  var bT = document.getElementById('sn-b-training'), bR = document.getElementById('sn-b-results'), bF = document.getElementById('sn-b-finder');
  var openName = null, lastFocus = null;
  function closeAll(focusBack) {
    mega.hidden = drop.hidden = finder.hidden = dim.hidden = true;
    bT.setAttribute('aria-expanded', 'false');
    bR.setAttribute('aria-expanded', 'false');
    if (openName === 'finder' && focusBack && lastFocus) lastFocus.focus();
    openName = null;
  }
  function placeDrop() {
    var hb = header.getBoundingClientRect(), bb = bR.getBoundingClientRect();
    var left = Math.min(Math.max(16, bb.left - hb.left - 12), hb.width - 416);
    drop.style.left = Math.max(16, left) + 'px';
  }
  function open(name) {
    closeMobile();
    if (openName === name) { closeAll(false); return; }
    closeAll(false);
    openName = name;
    dim.hidden = false;
    if (name === 'training') { mega.hidden = false; bT.setAttribute('aria-expanded', 'true'); }
    if (name === 'results') { placeDrop(); drop.hidden = false; bR.setAttribute('aria-expanded', 'true'); }
    if (name === 'finder') {
      lastFocus = document.activeElement;
      finder.hidden = false;
      var first = finder.querySelector('.sn-opt');
      if (first) first.focus();
    }
  }
  bT.addEventListener('click', function () { open('training'); });
  bR.addEventListener('click', function () { open('results'); });
  bF.addEventListener('click', function () { open('finder'); });
  finder.querySelector('.sn-close').addEventListener('click', function () { closeAll(true); });
  dim.addEventListener('click', function () { closeAll(false); });
  [mega, drop, finder].forEach(function (p) {
    p.addEventListener('click', function (e) { if (e.target.closest('a')) closeAll(false); });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (openName) { var b = openName === 'training' ? bT : openName === 'results' ? bR : null; closeAll(true); if (b) b.focus(); }
      if (!mob.hidden) { closeMobile(); burger.focus(); }
    }
    if (e.key === 'Tab' && openName === 'finder') {
      var f = finder.querySelectorAll('a,button'), a = f[0], z = f[f.length - 1];
      if (e.shiftKey && document.activeElement === a) { e.preventDefault(); z.focus(); }
      else if (!e.shiftKey && document.activeElement === z) { e.preventDefault(); a.focus(); }
    }
  });

  /* Desktop: open dropdowns on hover too */
  if (window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    var timer;
    [[bT, mega, 'training'], [bR, drop, 'results']].forEach(function (x) {
      var b = x[0], p = x[1], n = x[2];
      function enter() { clearTimeout(timer); if (openName !== n && openName !== 'finder') { timer = setTimeout(function () { if (openName !== n) open(n); }, 120); } }
      function leave() { clearTimeout(timer); timer = setTimeout(function () { if (openName === n) closeAll(false); }, 260); }
      b.addEventListener('mouseenter', enter); b.addEventListener('mouseleave', leave);
      p.addEventListener('mouseenter', function () { clearTimeout(timer); }); p.addEventListener('mouseleave', leave);
    });
  }

  function closeMobile() {
    if (mob.hidden) return;
    mob.hidden = true;
    burger.textContent = '☰';
    burger.setAttribute('aria-expanded', 'false');
    burger.setAttribute('aria-label', 'Open menu');
    document.documentElement.style.overflow = '';
  }
  burger.addEventListener('click', function () {
    if (!mob.hidden) { closeMobile(); return; }
    closeAll(false);
    mob.style.top = Math.max(0, header.getBoundingClientRect().bottom) + 'px';
    mob.hidden = false;
    burger.textContent = '×';
    burger.setAttribute('aria-expanded', 'true');
    burger.setAttribute('aria-label', 'Close menu');
    document.documentElement.style.overflow = 'hidden';
  });
  mob.addEventListener('click', function (e) { if (e.target.closest('a')) closeMobile(); });
  mob.querySelector('.sn-m-find').addEventListener('click', function () { closeMobile(); open('finder'); });
  window.addEventListener('resize', function () {
    if (window.innerWidth > 900) closeMobile();
    if (openName === 'results') placeDrop();
  });
})();
