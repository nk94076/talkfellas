(function () {
  var root = document.documentElement, $ = function (s) { return document.querySelector(s); };
  // Dark mode
  var tt = $('#theme-toggle');
  if (tt) tt.addEventListener('click', function () {
    var dark = root.dataset.theme ? root.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
    root.dataset.theme = dark ? 'light' : 'dark';
    try { localStorage.setItem('tf-theme', root.dataset.theme); } catch (e) {}
  });
  // Search panel
  var st = $('#search-toggle'), sp = $('#search-panel');
  if (st && sp) st.addEventListener('click', function () { sp.hidden = !sp.hidden; st.setAttribute('aria-expanded', !sp.hidden); if (!sp.hidden) sp.querySelector('input').focus(); });
  // Mobile menu
  var mt = $('#menu-toggle');
  if (mt) mt.addEventListener('click', function () { var o = document.body.classList.toggle('nav-open'); mt.setAttribute('aria-expanded', o); });
  // Hero slider
  document.querySelectorAll('[data-slider]').forEach(function (s) {
    var slides = s.querySelectorAll('.slide'), dots = s.querySelectorAll('[data-dot]'), i = 0, timer;
    if (slides.length < 2) return;
    function go(n) { slides[i].classList.remove('is-active'); dots[i] && dots[i].classList.remove('on'); i = (n + slides.length) % slides.length; slides[i].classList.add('is-active'); dots[i] && dots[i].classList.add('on'); }
    function auto() { clearInterval(timer); if (!matchMedia('(prefers-reduced-motion: reduce)').matches) timer = setInterval(function () { go(i + 1); }, 6000); }
    s.querySelector('[data-prev]').addEventListener('click', function () { go(i - 1); auto(); });
    s.querySelector('[data-next]').addEventListener('click', function () { go(i + 1); auto(); });
    dots.forEach(function (d) { d.addEventListener('click', function () { go(+d.dataset.dot); auto(); }); });
    s.addEventListener('mouseenter', function () { clearInterval(timer); }); s.addEventListener('mouseleave', auto);
    auto();
  });
})();
