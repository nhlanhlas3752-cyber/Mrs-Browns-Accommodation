/* =================================================================
   Apply Page — Multi-step form controller
   Steps: 1 Personal → 2 Background → 3 Preferences → 4 Documents → 5 Review
   ================================================================= */

(function () {
  'use strict';

  const TOTAL = 5;
  let current = 1;

  const form      = document.getElementById('apply-form');
  const btnNext   = document.getElementById('btn-next');
  const btnBack   = document.getElementById('btn-back');
  const stepDots  = document.querySelectorAll('.progress-step');
  const stepLines = document.querySelectorAll('.progress-step__line');

  if (!form) return;

  /* ── Show a step ───────────────────────────────────────────────── */
  function showStep(n) {
    document.querySelectorAll('.form-step').forEach((el, i) => {
      el.classList.toggle('active', i + 1 === n);
    });

    stepDots.forEach((dot, i) => {
      dot.classList.remove('active', 'complete');
      if (i + 1 === n) dot.classList.add('active');
      if (i + 1  < n) dot.classList.add('complete');
    });

    stepLines.forEach((line, i) => {
      line.classList.toggle('complete', i + 1 < n);
    });

    btnBack.style.visibility = n > 1 ? 'visible' : 'hidden';

    if (n === TOTAL) {
      btnNext.style.display = 'none';
      buildSummary();
    } else {
      btnNext.style.display = '';
    }

    if (n === 4) updateDocGroups();

    document.querySelector('.apply-form-wrap')
      .scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  /* ── Document group visibility ─────────────────────────────────── */
  function updateDocGroups() {
    var occupation = (document.getElementById('occupation') || {}).value || '';
    var funding    = (document.getElementById('funding')    || {}).value || '';

    var isStudent  = occupation === 'student';
    var isBursary  = funding === 'bursary';
    var isEmployer = funding === 'employer';
    // self, family, working, self-employed, job-seeker → show income proof
    var needsIncome = !isBursary && !isEmployer;

    toggle('doc-group-enrollment', isStudent);
    toggle('doc-group-bursary',    isBursary);
    toggle('doc-group-sponsorship', isEmployer);
    toggle('doc-group-income',     needsIncome);

    // Update income label & description to match occupation
    var incomeLabel = document.getElementById('doc-income-label');
    var incomeDesc  = document.getElementById('doc-income-desc');
    if (incomeLabel && incomeDesc) {
      if (occupation === 'working') {
        incomeLabel.textContent = 'Latest Payslip or Bank Statement';
        incomeDesc.textContent  = 'Your most recent payslip or last 3 months’ bank statements.';
      } else if (occupation === 'self-employed') {
        incomeLabel.textContent = 'Bank Statement (Last 3 Months)';
        incomeDesc.textContent  = 'Last 3 months’ business or personal bank statements showing income.';
      } else {
        incomeLabel.textContent = 'Bank Statement (Last 3 Months)';
        incomeDesc.textContent  = 'Last 3 months’ bank statements showing proof of ability to pay rent.';
      }
    }

    // Update notice text
    var notice = document.getElementById('doc-notice-text');
    if (notice) {
      var list = ['South African ID / Passport'];
      if (isStudent)  list.push('Proof of Enrollment');
      if (isBursary)  list.push('Bursary Award Letter');
      if (isEmployer) list.push('Employer Sponsorship Letter');
      if (needsIncome) list.push('Bank Statement / Payslip');
      notice.textContent = 'For your profile we need: ' + list.join(', ') + '. Files must be PDF, JPG or PNG — max 5 MB each.';
    }
  }

  function toggle(id, show) {
    var el = document.getElementById(id);
    if (el) el.style.display = show ? '' : 'none';
  }

  /* ── Per-step validation ───────────────────────────────────────── */
  function validate(n) {
    var step = document.getElementById('step-' + n);
    if (!step) return true;

    var ok = true;

    // Standard required text/select/textarea fields
    step.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (el) {
      // Skip hidden file inputs — handled separately below
      if (el.type === 'file') return;
      var empty = !el.value.trim();
      el.classList.toggle('error', empty);
      if (empty) ok = false;
    });

    // Building radio group (step 3)
    if (n === 3) {
      var radios  = step.querySelectorAll('input[name="building"]');
      var checked = Array.prototype.some.call(radios, function (r) { return r.checked; });
      radios.forEach(function (r) { r.closest('.radio-option').classList.toggle('error', !checked); });
      if (!checked) ok = false;
    }

    // Document file inputs (step 4) — only validate visible groups
    if (n === 4) {
      step.querySelectorAll('.doc-group').forEach(function (group) {
        if (group.style.display === 'none') return;
        var fileInput = group.querySelector('input[type="file"]');
        var zone      = group.querySelector('.doc-drop-zone');
        if (!fileInput) return;
        var hasFile = fileInput.files && fileInput.files.length > 0;
        if (zone) zone.classList.toggle('error', !hasFile);
        if (!hasFile) ok = false;
      });
    }

    return ok;
  }

  /* ── Navigation ────────────────────────────────────────────────── */
  btnNext.addEventListener('click', function () {
    if (!validate(current)) return;
    if (current < TOTAL) { current++; showStep(current); }
  });

  btnBack.addEventListener('click', function () {
    if (current > 1) { current--; showStep(current); }
  });

  /* ── Clear error on input ──────────────────────────────────────── */
  form.addEventListener('input', function (e) {
    if (e.target.required) e.target.classList.remove('error');
  });
  form.addEventListener('change', function (e) {
    if (e.target.type === 'radio') {
      document.querySelectorAll('input[name="' + e.target.name + '"]')
        .forEach(function (r) { r.closest('.radio-option').classList.remove('error'); });
    }
  });

  /* ── Toggle groups (Gender) ────────────────────────────────────── */
  document.querySelectorAll('.toggle-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var group = btn.dataset.group;
      document.querySelectorAll('.toggle-btn[data-group="' + group + '"]')
        .forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      var hidden = document.getElementById(group + '-hidden');
      if (hidden) hidden.value = btn.dataset.value;
    });
  });

  /* ── File upload drag-and-drop ─────────────────────────────────── */
  document.querySelectorAll('.doc-drop-zone').forEach(function (zone) {
    var inputId   = zone.dataset.for;
    var fileInput = document.getElementById(inputId);
    if (!fileInput) return;

    // Keyboard accessibility (click handled natively by file input covering the zone)
    zone.setAttribute('tabindex', '0');
    zone.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
    });

    // Drag events
    zone.addEventListener('dragover', function (e) {
      e.preventDefault();
      zone.classList.add('drag-over');
    });
    zone.addEventListener('dragleave', function () {
      zone.classList.remove('drag-over');
    });
    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      zone.classList.remove('drag-over');
      if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        onFileSelected(fileInput, zone);
      }
    });

    // File selected via picker — pass zone directly from closure (fixes ID mismatch bug)
    fileInput.addEventListener('change', function () { onFileSelected(fileInput, zone); });
  });

  function onFileSelected(input, zone) {
    var nameEl = document.getElementById('fname-' + input.id);

    if (input.files && input.files.length > 0) {
      var file   = input.files[0];
      var sizeMB = (file.size / 1024 / 1024).toFixed(1);
      var fname  = escHtml(file.name);

      // Hide the "Required" badge once a file is chosen
      var badge = zone && zone.closest('.form-row__fields') &&
                  zone.closest('.form-row__fields').querySelector('.doc-badge');
      if (badge) badge.style.display = 'none';

      // Update the zone itself — show big checkmark + filename inside
      if (zone) {
        zone.classList.add('has-file');
        zone.classList.remove('error');
        var icon = zone.querySelector('.doc-drop-icon');
        var text = zone.querySelector('.doc-drop-text');
        var hint = zone.querySelector('.doc-drop-hint');
        if (icon) {
          icon.setAttribute('viewBox', '0 0 24 24');
          icon.innerHTML = '<circle cx="12" cy="12" r="10" stroke="#059669" stroke-width="1.5" fill="rgba(5,150,105,.1)"/><polyline points="7 12 10.5 15.5 17 9" stroke="#059669" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
          icon.style.color = '#059669';
        }
        if (text) {
          text.innerHTML = '<span style="color:#059669;font-weight:700;word-break:break-all">' + fname + '</span>';
        }
        if (hint) {
          hint.textContent = sizeMB + ' MB  ·  Tap to change';
          hint.style.color = '#059669';
        }
      }

      // Also update the label below the zone
      if (nameEl) {
        nameEl.innerHTML =
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">' +
          '<circle cx="12" cy="12" r="10" fill="#059669"/>' +
          '<polyline points="7 12 10.5 15.5 17 9" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/>' +
          '</svg><span>' + fname + ' (' + sizeMB + ' MB)</span>';
      }

    } else {
      // Restore the "Required" badge if file is cleared
      var badge = zone && zone.closest('.form-row__fields') &&
                  zone.closest('.form-row__fields').querySelector('.doc-badge');
      if (badge) badge.style.display = '';

      // Reset zone to default state
      if (zone) {
        zone.classList.remove('has-file');
        var icon = zone.querySelector('.doc-drop-icon');
        var text = zone.querySelector('.doc-drop-text');
        var hint = zone.querySelector('.doc-drop-hint');
        if (icon) {
          icon.innerHTML = '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>';
          icon.style.color = '';
        }
        if (text) { text.innerHTML = 'Drag &amp; drop or <span>click to browse</span>'; }
        if (hint) { hint.textContent = 'PDF, JPG or PNG · Max 5 MB'; hint.style.color = ''; }
      }
      if (nameEl) nameEl.innerHTML = '';
    }
  }

  function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  /* ── Review summary ────────────────────────────────────────────── */
  function getVal(id) {
    var el = document.getElementById(id);
    return el && el.value.trim() ? el.value.trim() : '—';
  }
  function getSelectLabel(id) {
    var el = document.getElementById(id);
    if (!el) return '—';
    var opt = el.options[el.selectedIndex];
    return (opt && opt.value) ? opt.text : '—';
  }
  function getRadio(name) {
    var el = document.querySelector('input[name="' + name + '"]:checked');
    return el ? el.closest('.radio-option').querySelector('span').textContent.trim() : '—';
  }
  function getHidden(id) {
    var el = document.getElementById(id);
    return el ? el.value : '—';
  }
  function row(key, val) {
    return '<div class="review-box__row">' +
      '<span class="review-box__key">' + key + '</span>' +
      '<span class="review-box__val">' + escHtml(String(val)) + '</span>' +
      '</div>';
  }
  function docRow(label, inputId) {
    var input = document.getElementById(inputId);
    var group = document.getElementById('doc-group-' + inputId.replace('doc_', ''));
    if (!group || group.style.display === 'none') return '';
    var name = (input && input.files && input.files.length > 0)
      ? input.files[0].name : 'Not uploaded';
    return row(label, name);
  }

  function buildSummary() {
    var box = document.getElementById('review-summary');
    if (!box) return;

    var firstName = getVal('first-name');
    var lastName  = getVal('last-name');

    box.innerHTML =
      '<div class="review-box__section">' +
        '<p class="review-box__heading">Personal Details</p>' +
        row('Name',   firstName + ' ' + lastName) +
        row('Email',  getVal('email')) +
        row('Phone',  getVal('phone')) +
        row('DOB',    getVal('dob')) +
        row('Gender', getHidden('gender-hidden')) +
      '</div>' +
      '<div class="review-box__section">' +
        '<p class="review-box__heading">Background</p>' +
        row('Occupation', getSelectLabel('occupation')) +
        row('Employer / Institution', getVal('employer') === '—' ? 'Not provided' : getVal('employer')) +
        row('Funding Source', getSelectLabel('funding')) +
      '</div>' +
      '<div class="review-box__section">' +
        '<p class="review-box__heading">Accommodation Preferences</p>' +
        row('Residence',  getRadio('building')) +
        row('Room Type',  getSelectLabel('room-type')) +
        row('Move-in',    getVal('move-in')) +
        row('Notes',      getVal('notes') !== '—' ? getVal('notes') : 'None') +
      '</div>' +
      '<div class="review-box__section">' +
        '<p class="review-box__heading">Documents</p>' +
        docRow('ID / Passport',       'doc_id') +
        docRow('Proof of Enrollment', 'doc_enrollment') +
        docRow('Bursary Letter',      'doc_bursary') +
        docRow('Sponsorship Letter',  'doc_sponsorship') +
        docRow('Bank Stmt / Payslip', 'doc_income') +
      '</div>';
  }

  /* ── Form submit ───────────────────────────────────────────────── */
  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var termsBox = document.getElementById('terms');
    if (!termsBox.checked) {
      termsBox.focus();
      termsBox.classList.add('error');
      return;
    }

    var submitBtn = form.querySelector('.submit-btn');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Submitting…'; }

    fetch('/backend/submit-application.php', {
      method: 'POST',
      body: new FormData(form),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var wrap = document.querySelector('.apply-form-wrap');
        if (data.success) {
          wrap.innerHTML =
            '<div class="success-screen">' +
              '<svg class="success-screen__icon" xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' +
              '<h2 class="success-screen__title">Application Received</h2>' +
              '<p class="success-screen__sub">Thank you for applying. We’ll review your application and be in touch within 3–5 business days.</p>' +
              '<a href="index.html" class="success-screen__btn">Back to Home</a>' +
            '</div>';
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          var msgs = (data.errors || [data.error || 'Something went wrong.']).join('<br>');
          var errBox = wrap.querySelector('.form-submit-error');
          if (!errBox) {
            errBox = document.createElement('div');
            errBox.className = 'form-submit-error';
            errBox.style.cssText = 'background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:.85rem;';
            form.prepend(errBox);
          }
          errBox.innerHTML = msgs;
          if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Submit Application'; }
        }
      })
      .catch(function () {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Submit Application'; }
        alert('A network error occurred. Please check your connection and try again.');
      });
  });

  /* ── Boot ──────────────────────────────────────────────────────── */
  showStep(1);

})();
