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

  // Scroll reveal
  var reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && reveals.length) {
    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); obs.unobserve(en.target); }
      });
    }, { threshold: 0.12 });
    reveals.forEach(function (el) { obs.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('in'); });
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
