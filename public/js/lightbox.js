// Lightbox dla galerii Elementora (linki z data-elementor-open-lightbox="yes").
(function () {
  var box = document.querySelector('.tk-lightbox');
  var links = Array.prototype.slice.call(document.querySelectorAll('a[data-elementor-open-lightbox="yes"]'));
  if (!box || !links.length) return;
  var img = box.querySelector('.tk-lightbox__img');
  var counter = box.querySelector('.tk-lightbox__counter');
  var title = box.querySelector('.tk-lightbox__title');
  var prev = box.querySelector('.tk-lightbox__prev');
  var next = box.querySelector('.tk-lightbox__next');
  var items = [], index = 0, lastFocus = null;

  function show(i) {
    index = (i + items.length) % items.length;
    var a = items[index];
    img.src = a.href;
    img.alt = a.dataset.elementorLightboxTitle || '';
    title.textContent = a.dataset.elementorLightboxTitle || '';
    counter.textContent = items.length > 1 ? (index + 1) + ' / ' + items.length : '';
    prev.hidden = next.hidden = items.length < 2;
  }
  function open(a) {
    var group = a.dataset.elementorLightboxSlideshow;
    items = group ? links.filter(function (l) { return l.dataset.elementorLightboxSlideshow === group; }) : [a];
    lastFocus = document.activeElement;
    show(items.indexOf(a));
    box.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    box.querySelector('.tk-lightbox__close').focus();
  }
  function close() {
    box.hidden = true;
    img.removeAttribute('src');
    document.documentElement.style.overflow = '';
    if (lastFocus) lastFocus.focus();
  }

  links.forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
      e.preventDefault();
      open(a);
    });
  });
  box.querySelector('.tk-lightbox__close').addEventListener('click', close);
  prev.addEventListener('click', function () { show(index - 1); });
  next.addEventListener('click', function () { show(index + 1); });
  box.addEventListener('click', function (e) {
    if (e.target === box || e.target.classList.contains('tk-lightbox__stage')) close();
  });
  document.addEventListener('keydown', function (e) {
    if (box.hidden) return;
    if (e.key === 'Escape') close();
    else if (e.key === 'ArrowLeft' && items.length > 1) show(index - 1);
    else if (e.key === 'ArrowRight' && items.length > 1) show(index + 1);
  });
  var x0 = null;
  box.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
  box.addEventListener('touchend', function (e) {
    if (x0 === null || items.length < 2) return;
    var dx = e.changedTouches[0].clientX - x0;
    if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1));
    x0 = null;
  });
})();
