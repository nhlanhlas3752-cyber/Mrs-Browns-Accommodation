/* =================================================================
   Hilret Trust Dashboard — Client-side JS
   Vanilla JS only — no external dependencies.
   ================================================================= */

/* ── Live search filter for any table ────────────────────────── */
function initTableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;

  input.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
    updateCount(table);
  });
}

function updateCount(table) {
  const total   = table.querySelectorAll('tbody tr').length;
  const visible = [...table.querySelectorAll('tbody tr')].filter(r => r.style.display !== 'none').length;
  const countEl = table.closest('.card')?.querySelector('.filter-count');
  if (countEl) countEl.textContent = `${visible} of ${total} records`;
}

/* ── Status filter select ─────────────────────────────────────── */
function initStatusFilter(selectId, tableId, colIndex) {
  const sel   = document.getElementById(selectId);
  const table = document.getElementById(tableId);
  if (!sel || !table) return;

  sel.addEventListener('change', function () {
    const val = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      const cell = row.cells[colIndex];
      if (!cell) return;
      row.style.display =
        (!val || cell.textContent.trim().toLowerCase().includes(val)) ? '' : 'none';
    });
    updateCount(table);
  });
}

/* ── Modal helpers ────────────────────────────────────────────── */
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}

document.addEventListener('DOMContentLoaded', function () {

  /* Close modals on backdrop click */
  document.querySelectorAll('.modal-backdrop').forEach(function (bd) {
    bd.addEventListener('click', function (e) {
      if (e.target === bd) bd.classList.remove('open');
    });
  });

  /* Close modals on .modal-close click */
  document.querySelectorAll('.modal-close').forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.closest('.modal-backdrop')?.classList.remove('open');
    });
  });

  /* Auto-dismiss alerts after 4 s */
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (a) {
    setTimeout(function () {
      a.style.transition = 'opacity .4s';
      a.style.opacity = '0';
      setTimeout(() => a.remove(), 400);
    }, 4000);
  });

  /* FAQ category filter tabs */
  const catBtns = document.querySelectorAll('[data-cat-filter]');
  catBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      catBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const cat = btn.dataset.catFilter;
      document.querySelectorAll('[data-category]').forEach(function (item) {
        item.style.display =
          (!cat || cat === 'all' || item.dataset.category === cat) ? '' : 'none';
      });
    });
  });

  /* Confirm delete actions */
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  /* Toggle room status dot colour */
  document.querySelectorAll('.status-toggle').forEach(function (sel) {
    sel.addEventListener('change', function () {
      const row = sel.closest('tr');
      const badge = row?.querySelector('.badge');
      if (!badge) return;
      badge.className = 'badge badge--' + sel.value;
      badge.querySelector('.status-dot') && (badge.querySelector('.status-dot').className = 'status-dot');
      badge.innerHTML = '<span class="status-dot"></span> ' + sel.value.charAt(0).toUpperCase() + sel.value.slice(1);
    });
  });

});
