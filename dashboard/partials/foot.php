</div><!-- /.dash-layout -->
<script src="/dashboard/assets/dashboard.js"></script>
<?php if (!empty($extraJs)): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>
<script>
/* ── CSRF: auto-inject token into all forms and AJAX requests ── */
(function () {
  var TOKEN = '<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>';

  // Inject hidden field into every HTML form
  document.querySelectorAll('form').forEach(function (f) {
    if (!f.querySelector('[name="_csrf"]')) {
      var i = document.createElement('input');
      i.type = 'hidden'; i.name = '_csrf'; i.value = TOKEN;
      f.appendChild(i);
    }
  });

  // Patch fetch() to include CSRF header on same-origin requests
  var _fetch = window.fetch;
  window.fetch = function (url, opts) {
    opts = opts || {};
    if (opts.method && opts.method.toUpperCase() !== 'GET') {
      opts.headers = Object.assign({ 'X-CSRF-Token': TOKEN }, opts.headers || {});
    }
    return _fetch.call(this, url, opts);
  };

  // Patch XMLHttpRequest for legacy AJAX
  var _open = XMLHttpRequest.prototype.open;
  var _send = XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.open = function (method) {
    this._method = method;
    return _open.apply(this, arguments);
  };
  XMLHttpRequest.prototype.send = function () {
    if (this._method && this._method.toUpperCase() !== 'GET') {
      this.setRequestHeader('X-CSRF-Token', TOKEN);
    }
    return _send.apply(this, arguments);
  };
})();
</script>
</body>
</html>
