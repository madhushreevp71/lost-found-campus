(function () {
  'use strict';

  // Mobile sidebar toggle
  var btn = document.querySelector('.menu-btn');
  var side = document.getElementById('sidebar');
  if (btn && side) {
    btn.addEventListener('click', function () {
      var open = side.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && side.classList.contains('open')) { btn.click(); btn.focus(); }
    });
  }

  // Confirm dialogs for destructive buttons
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!window.confirm(el.getAttribute('data-confirm'))) { e.preventDefault(); }
    });
  });

  // Character counters
  document.querySelectorAll('textarea[data-counter]').forEach(function (ta) {
    var max = parseInt(ta.getAttribute('data-counter'), 10);
    var out = ta.parentNode.querySelector('.counter');
    if (!out) { return; }
    function update() { out.textContent = ta.value.length + ' / ' + max + ' characters'; }
    ta.addEventListener('input', update);
    update();
  });

  // Client-side validation (the server validates again)
  function message(field) {
    var v = field.validity;
    var label = (field.id && document.querySelector('label[for="' + field.id + '"]'));
    var name = label ? label.textContent.trim() : 'This field';
    if (v.valueMissing) { return name + ' is required.'; }
    if (v.typeMismatch) { return 'Enter a valid ' + (field.type === 'email' ? 'email address' : 'value') + '.'; }
    if (v.tooShort) { return name + ' must be at least ' + field.minLength + ' characters.'; }
    if (v.tooLong) { return name + ' must be at most ' + field.maxLength + ' characters.'; }
    if (v.rangeOverflow) { return 'The date cannot be in the future.'; }
    return 'Check this field.';
  }
  function setError(field, text) {
    var holder = field.closest('.field') || field.parentNode;
    var err = holder.querySelector('.field-error.js');
    var server = holder.querySelector('.field-error:not(.js)');
    if (server) { server.remove(); }
    if (!text) {
      field.classList.remove('invalid'); field.removeAttribute('aria-invalid');
      if (err) { err.remove(); }
      return;
    }
    field.classList.add('invalid'); field.setAttribute('aria-invalid', 'true');
    if (!err) { err = document.createElement('span'); err.className = 'field-error js'; holder.appendChild(err); }
    err.textContent = text;
  }

  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    var fields = form.querySelectorAll('input:not([type=hidden]), select, textarea');
    var pw = form.querySelector('#password'), cf = form.querySelector('#confirm_password');

    function check(field) {
      var text = field.checkValidity() ? '' : message(field);
      if (!text && field === pw && form.querySelector('#confirm_password') && field.hasAttribute('minlength')) {
        if (!/[A-Za-z]/.test(field.value) || !/\d/.test(field.value)) { text = 'Use a letter and a number in your password.'; }
      }
      if (!text && field === cf && pw && cf.value !== pw.value) { text = 'Passwords do not match.'; }
      if (!text && field.type === 'file' && field.files && field.files[0]) {
        var f = field.files[0];
        if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1) { text = 'Only JPG, PNG or WEBP images are allowed.'; }
        else if (f.size > 2 * 1024 * 1024) { text = 'The image is larger than 2 MB.'; }
      }
      setError(field, text);
      return text === '';
    }
    fields.forEach(function (f) {
      f.addEventListener('blur', function () { if (f.value !== '' || f.classList.contains('invalid')) { check(f); } });
      f.addEventListener('input', function () { if (f.classList.contains('invalid')) { check(f); } });
      f.addEventListener('change', function () { check(f); });
    });
    form.addEventListener('submit', function (e) {
      var firstBad = null;
      fields.forEach(function (f) { if (!check(f) && !firstBad) { firstBad = f; } });
      if (firstBad) { e.preventDefault(); firstBad.focus(); }
    });
  });
})();
