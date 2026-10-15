// Obsługa formularzy bez WordPressa (zastępuje WPForms i Contact Form 7):
// wysyłka AJAX do form-handler.php, pola "jednokrotnego wyboru" CF7
// i sekcje formularza "Zamów transport" pokazywane po wybraniu gatunku.
(function () {
  // CF7: wpcf7-exclusive-checkbox = można zaznaczyć tylko jedną opcję
  document.querySelectorAll('.wpcf7-exclusive-checkbox input[type=checkbox]').forEach(function (cb) {
    cb.addEventListener('change', function () {
      if (!cb.checked) return;
      cb.closest('.wpcf7-exclusive-checkbox').querySelectorAll('input[type=checkbox]').forEach(function (o) {
        if (o !== cb) o.checked = false;
      });
      updateGroups();
    });
  });

  // "Zamów transport": szczegóły pokazujemy dla wybranego gatunku zwierzęcia
  var groups = document.querySelectorAll('.tk-group[data-group]');
  function updateGroups() {
    groups.forEach(function (g) {
      var form = g.closest('form');
      var on = Array.prototype.some.call(form.querySelectorAll('input[type=checkbox]'), function (cb) {
        return cb.checked && cb.value === g.dataset.group && !g.contains(cb);
      });
      g.hidden = !on;
      g.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = !on; });
    });
  }
  if (groups.length) {
    document.addEventListener('change', updateGroups);
    updateGroups();
  }

  document.querySelectorAll('form[data-ajax]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var out = form.querySelector('.form-response');
      var btn = form.querySelector('[type=submit]');
      if (btn) btn.disabled = true;
      fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form) })
        .then(function (r) { return r.json(); })
        .then(function (res) { show(res.message, res.ok); if (res.ok) { form.reset(); if (groups.length) updateGroups(); } })
        .catch(function () { show('Wystąpił błąd podczas wysyłania wiadomości. Spróbuj ponownie później.', false); })
        .finally(function () { if (btn) btn.disabled = false; });

      function show(msg, ok) {
        if (!out) return alert(msg);
        out.textContent = msg;
        out.hidden = false;
        out.style.display = 'block';
        form.classList.toggle('sent', !!ok);
        form.classList.toggle('failed', !ok);
      }
    });
  });
})();
