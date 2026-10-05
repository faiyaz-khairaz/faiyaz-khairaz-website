/* Videos play inside the page: the cover image shows first, the YouTube player loads only on click. */
document.querySelectorAll('.video[data-id]').forEach(function (box) {
  var btn = box.querySelector('button');
  btn.addEventListener('click', function () {
    var f = document.createElement('iframe');
    f.src = 'https://www.youtube-nocookie.com/embed/' + box.getAttribute('data-id') + '?autoplay=1&rel=0&playsinline=1';
    f.title = btn.getAttribute('aria-label') || 'Video';
    f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
    f.allowFullscreen = true;
    f.referrerPolicy = 'strict-origin-when-cross-origin';
    box.replaceChildren(f);
  });
});

/* Case study listing: filter cards by topic and industry. */
(function () {
  var cards = document.querySelectorAll('.cards[data-filterable] .card');
  if (!cards.length) return;
  var state = { topic: 'all', industry: 'all' };
  var empty = document.getElementById('empty');
  function apply() {
    var shown = 0;
    cards.forEach(function (c) {
      var ok = (state.topic === 'all' || c.dataset.topic === state.topic) &&
               (state.industry === 'all' || c.dataset.industry === state.industry);
      c.hidden = !ok; if (ok) shown++;
    });
    if (empty) empty.hidden = shown > 0;
  }
  document.querySelectorAll('.filters[data-group]').forEach(function (group) {
    var key = group.dataset.group;
    group.querySelectorAll('button').forEach(function (b) {
      b.addEventListener('click', function () {
        group.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
        b.setAttribute('aria-pressed', 'true');
        state[key] = b.dataset.v;
        apply();
      });
    });
  });
  apply();
})();
