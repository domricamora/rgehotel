/* RGE Hotel — front-end interactions (self-hosted, no dependencies) */
(function () {
  'use strict';

  // Mobile nav toggle
  var toggle = document.querySelector('.nav-toggle');
  var links = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      links.classList.toggle('open');
    });
    links.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { links.classList.remove('open'); });
    });
  }

  // Scroll reveal — with failsafes so content never stays hidden
  var reveals = document.querySelectorAll('.reveal');
  function reveal(el) { el.classList.add('in'); }
  function inView(el) {
    var r = el.getBoundingClientRect();
    return r.top < (window.innerHeight || document.documentElement.clientHeight) + 80;
  }
  if ('IntersectionObserver' in window && reveals.length) {
    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { reveal(en.target); obs.unobserve(en.target); } });
    }, { threshold: 0.08 });
    reveals.forEach(function (el) {
      if (inView(el)) reveal(el);      // anything on screen at load shows immediately
      else obs.observe(el);
    });
    // Failsafe: never leave content hidden if the observer doesn't fire
    window.addEventListener('load', function () { setTimeout(function () { reveals.forEach(reveal); }, 1500); });
  } else {
    reveals.forEach(reveal);
  }

  // Lightweight lightbox for [data-lightbox] anchors
  var lbLinks = document.querySelectorAll('[data-lightbox]');
  if (lbLinks.length) {
    var box = document.createElement('div');
    box.className = 'lightbox';
    box.innerHTML = '<button class="lightbox__close" aria-label="Close">&times;</button><img alt="">';
    Object.assign(box.style, {
      position: 'fixed', inset: '0', background: 'rgba(7,34,41,.92)', display: 'none',
      alignItems: 'center', justifyContent: 'center', zIndex: '1000', padding: '24px', cursor: 'zoom-out'
    });
    var img = box.querySelector('img');
    Object.assign(img.style, { maxWidth: '92vw', maxHeight: '88vh', borderRadius: '14px', boxShadow: '0 30px 80px rgba(0,0,0,.5)' });
    var closeBtn = box.querySelector('.lightbox__close');
    Object.assign(closeBtn.style, { position: 'absolute', top: '18px', right: '24px', fontSize: '40px', color: '#fff', background: 'none', border: '0', lineHeight: '1' });
    document.body.appendChild(box);
    function open(src) { img.src = src; box.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    function close() { box.style.display = 'none'; document.body.style.overflow = ''; }
    lbLinks.forEach(function (a) {
      a.addEventListener('click', function (e) { e.preventDefault(); open(a.getAttribute('href')); });
    });
    box.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  }

  // Eased scrolling (custom easing) + scroll-to-top
  function easeInOutCubic(t) { return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; }
  function easedScrollTo(targetY) {
    var startY = window.pageYOffset || document.documentElement.scrollTop;
    var diff = targetY - startY;
    if (Math.abs(diff) < 2) return;
    var duration = Math.min(900, Math.max(350, Math.abs(diff) * 0.45));
    var start = null;
    function step(ts) {
      if (start === null) start = ts;
      var p = Math.min(1, (ts - start) / duration);
      // behavior:auto so our easing drives it (CSS scroll-behavior:smooth would fight a per-frame set)
      window.scrollTo({ top: startY + diff * easeInOutCubic(p), behavior: 'auto' });
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  // In-page anchor links: eased scroll with sticky-header offset
  var HEADER_OFFSET = 96;
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    var hash = a.getAttribute('href');
    if (!hash || hash.length < 2) return; // ignore bare "#"
    a.addEventListener('click', function (e) {
      var target = document.getElementById(hash.slice(1));
      if (!target) return;
      e.preventDefault();
      var y = target.getBoundingClientRect().top + window.pageYOffset - HEADER_OFFSET;
      easedScrollTo(y);
      if (history.replaceState) history.replaceState(null, '', hash);
    });
  });

  // Scroll-to-top button (created here so no template edits needed)
  var toTop = document.createElement('button');
  toTop.type = 'button';
  toTop.className = 'to-top';
  toTop.setAttribute('aria-label', 'Back to top');
  toTop.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>';
  document.body.appendChild(toTop);
  toTop.addEventListener('click', function () { easedScrollTo(0); });
  var ticking = false;
  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () {
      toTop.classList.toggle('show', (window.pageYOffset || document.documentElement.scrollTop) > 420);
      ticking = false;
    });
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Booking bar: keep checkout >= checkin
  var ci = document.querySelector('[name="check_in"]');
  var co = document.querySelector('[name="check_out"]');
  if (ci && co) {
    var today = new Date().toISOString().split('T')[0];
    ci.min = today;
    function sync() {
      if (ci.value) { co.min = ci.value; if (co.value && co.value <= ci.value) { var d = new Date(ci.value); d.setDate(d.getDate() + 1); co.value = d.toISOString().split('T')[0]; } }
    }
    ci.addEventListener('change', sync); sync();
  }
})();
