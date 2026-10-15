// Konfiguracja motywu Astra (czyta ją style.min.js) — musi być załadowana przed nim.
var astra = {"break_point":"921","isRtl":"","is_scroll_to_id":"","is_scroll_to_top":"","is_header_footer_builder_active":""};

// Elementor: sekcje "rozciągnięte" na całą szerokość okna (zastępuje frontend.min.js)
(function () {
  var stretch = function () {
    document.querySelectorAll('.elementor-section-stretched').forEach(function (s) {
      s.style.width = 'auto'; s.style.left = '0px';
      var left = s.getBoundingClientRect().left;
      s.style.width = document.documentElement.clientWidth + 'px';
      s.style.left = -left + 'px';
    });
  };
  stretch();
  window.addEventListener('resize', stretch);
})();
