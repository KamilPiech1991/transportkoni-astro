// Wysuwany boks Facebooka (dawniej wtyczka Aspexi Facebook Like Box, bez jQuery).
(function () {
  var box = document.querySelector('.aspexifblikebox');
  if (!box) return;
  var width = 245;
  var open = function () { box.style.right = '0px'; };
  var close = function () { box.style.right = -width + 'px'; };
  box.style.transition = 'right .4s';
  var touch = /iPhone|iPod|iPad/i.test(navigator.userAgent);
  box.querySelectorAll('.aspexi_facebook_button, .aspexi_facebook_iframe').forEach(function (el) {
    if (touch) {
      el.addEventListener('click', function () { parseInt(getComputedStyle(box).right, 10) === 0 ? close() : open(); });
    } else {
      el.addEventListener('mouseenter', open);
      el.addEventListener('mouseleave', close);
    }
  });
  // Facebook SDK (renderuje wtyczkę .fb-page) — ładowany jak w oryginale
  var js = document.createElement('script');
  js.id = 'facebook-jssdk';
  js.async = true;
  js.src = 'https://connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v14.0&appId=1548213832159053';
  document.body.appendChild(js);
})();
