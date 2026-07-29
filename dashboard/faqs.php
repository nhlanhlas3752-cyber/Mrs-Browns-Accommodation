<?php
$pageTitle    = 'FAQs';
$pageSubtitle = 'Manage questions and answers shown on the public website';
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo  = db();
$cats = ['applications','funding','documentation','amenities','house-rules','general'];

/* ── Actions ──────────────────────────────────────────────────── */

// DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM faqs WHERE id=?')->execute([(int)$_GET['delete']]);
    header('Location: faqs.php?msg=deleted');
    exit;
}

// TOGGLE visibility
if (isset($_GET['toggle'])) {
    $pdo->prepare('UPDATE faqs SET active = 1 - active WHERE id=?')
        ->execute([(int)$_GET['toggle']]);
    header('Location: faqs.php?msg=toggled');
    exit;
}

// ADD new FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $cat      = in_array($_POST['category'] ?? '', $cats) ? $_POST['category'] : 'general';
    $question = trim($_POST['question'] ?? '');
    $answer   = trim($_POST['answer']   ?? '');
    $sortOrd  = (int)($_POST['sort_order'] ?? 0);
    if ($question && $answer) {
        $pdo->prepare('INSERT INTO faqs (category,question,answer,sort_order) VALUES (?,?,?,?)')
            ->execute([$cat, $question, $answer, $sortOrd]);
    }
    header('Location: faqs.php?msg=added');
    exit;
}

// EDIT existing FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $faqId    = (int)($_POST['faq_id'] ?? 0);
    $cat      = in_array($_POST['category'] ?? '', $cats) ? $_POST['category'] : 'general';
    $question = trim($_POST['question'] ?? '');
    $answer   = trim($_POST['answer']   ?? '');
    $sortOrd  = (int)($_POST['sort_order'] ?? 0);
    if ($faqId && $question && $answer) {
        $pdo->prepare(
            'UPDATE faqs SET category=?, question=?, answer=?, sort_order=?, updated_at=NOW() WHERE id=?'
        )->execute([$cat, $question, $answer, $sortOrd, $faqId]);
    }
    header('Location: faqs.php?msg=updated');
    exit;
}

/* ── Data ─────────────────────────────────────────────────────── */
$faqs = $pdo->query('SELECT * FROM faqs ORDER BY category, sort_order, id')->fetchAll();
$catCounts = [];
foreach ($faqs as $f) {
    $catCounts[$f['category']] = ($catCounts[$f['category']] ?? 0) + 1;
}
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <?php if (!empty($_GET['msg'])): ?>
    <?php $msgs = [
      'added'   => 'FAQ added successfully.',
      'updated' => 'FAQ answer updated successfully.',
      'deleted' => 'FAQ deleted.',
      'toggled' => 'FAQ visibility toggled.',
    ]; ?>
    <div class="alert alert--success" data-auto-dismiss>
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      <?= htmlspecialchars($msgs[$_GET['msg']] ?? 'Saved.') ?>
    </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">

    <!-- ══ LEFT: FAQ list ══════════════════════════════════════════ -->
    <div>

      <!-- Category filter tabs -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
        <button class="btn btn--sm btn--navy" data-cat-filter="all">
          All (<?= count($faqs) ?>)
        </button>
        <?php foreach ($cats as $c): ?>
        <button class="btn btn--sm btn--ghost" data-cat-filter="<?= $c ?>">
          <?= ucwords(str_replace('-',' ',$c)) ?> (<?= $catCounts[$c] ?? 0 ?>)
        </button>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <?php if (!$faqs): ?>
          <div class="empty-state">
            <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <h4>No FAQs yet</h4>
            <p>Use the "Add New FAQ" form on the right to get started.</p>
          </div>
        <?php endif; ?>

        <ul class="faq-admin-list" id="faq-list">
          <?php foreach ($faqs as $f): ?>
          <li class="faq-admin-item" data-category="<?= htmlspecialchars($f['category']) ?>">

            <!-- Collapse toggle -->
            <div class="faq-admin-item__body">
              <div class="faq-admin-item__q"><?= htmlspecialchars($f['question']) ?></div>

              <!-- Full answer — expandable -->
              <div class="faq-answer-preview" id="preview-<?= $f['id'] ?>">
                <?= nl2br(htmlspecialchars($f['answer'])) ?>
              </div>

              <!-- Meta row -->
              <div style="margin-top:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <span class="badge badge--<?= $f['active'] ? 'approved' : 'read' ?>" style="font-size:.65rem">
                  <span class="status-dot"></span>
                  <?= $f['active'] ? 'Visible on site' : 'Hidden' ?>
                </span>
                <span class="text-muted text-xs" style="background:var(--clr-bg);padding:2px 8px;border-radius:20px;border:1px solid var(--clr-border)">
                  <?= ucwords(str_replace('-',' ',$f['category'])) ?>
                </span>
                <span class="text-muted text-xs">Order: <?= $f['sort_order'] ?></span>
                <?php if ($f['updated_at'] !== $f['created_at']): ?>
                <span class="text-muted text-xs">Edited <?= date('d M Y', strtotime($f['updated_at'])) ?></span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Action buttons -->
            <div class="faq-admin-item__actions">
              <!-- EDIT button — opens modal pre-filled -->
              <button
                class="btn btn--amber btn--sm"
                title="Edit this FAQ"
                onclick="openEditModal(
                  <?= $f['id'] ?>,
                  <?= json_encode($f['category']) ?>,
                  <?= json_encode($f['question']) ?>,
                  <?= json_encode($f['answer']) ?>,
                  <?= (int)$f['sort_order'] ?>
                )"
              >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
              </button>

              <!-- TOGGLE visibility -->
              <a href="?toggle=<?= $f['id'] ?>"
                 class="btn btn--ghost btn--sm"
                 title="<?= $f['active'] ? 'Hide from website' : 'Show on website' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <?php if ($f['active']): ?>
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
                  <?php else: ?>
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                  <?php endif; ?>
                </svg>
                <?= $f['active'] ? 'Hide' : 'Show' ?>
              </a>

              <!-- DELETE -->
              <a href="?delete=<?= $f['id'] ?>"
                 class="btn btn--danger btn--sm"
                 title="Delete this FAQ"
                 data-confirm="Delete this FAQ permanently? This cannot be undone.">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
              </a>
            </div>

          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- ══ RIGHT: Add new FAQ form ════════════════════════════════ -->
    <div class="card" style="position:sticky;top:calc(var(--topbar-h) + 16px)">
      <div class="card-header">
        <h3>Add New FAQ</h3>
        <span class="text-muted text-xs"><?= count($faqs) ?> total</span>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="add">

          <div class="field" style="margin-bottom:12px">
            <label>Category</label>
            <select name="category">
              <?php foreach ($cats as $c): ?>
              <option value="<?= $c ?>"><?= ucwords(str_replace('-',' ',$c)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field" style="margin-bottom:12px">
            <label>Question <span style="color:var(--clr-red)">*</span></label>
            <textarea name="question" rows="3" required
              placeholder="e.g. How do I apply for accommodation?"
              style="resize:vertical"></textarea>
          </div>

          <div class="field" style="margin-bottom:12px">
            <label>Answer <span style="color:var(--clr-red)">*</span></label>
            <textarea name="answer" rows="6" required
              placeholder="Write the full answer here…"
              style="resize:vertical"></textarea>
          </div>

          <div class="field" style="margin-bottom:16px">
            <label>Sort Order <span class="text-muted">(lower = appears first)</span></label>
            <input type="number" name="sort_order" min="0" value="1"
              style="width:100px">
          </div>

          <button type="submit" class="btn btn--primary w-full">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add FAQ
          </button>
        </form>
      </div>
    </div>

  </div>
</main>


<!-- ══ EDIT FAQ MODAL ════════════════════════════════════════════ -->
<div class="modal-backdrop" id="edit-faq-modal">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <div>
        <h3 style="margin-bottom:2px">Edit FAQ</h3>
        <p class="text-muted text-xs" id="edit-modal-cat-label"></p>
      </div>
      <button class="modal-close">&times;</button>
    </div>

    <form method="POST" id="edit-faq-form">
      <input type="hidden" name="action"   value="edit">
      <input type="hidden" name="faq_id"   id="edit-faq-id">

      <div class="modal-body" style="display:flex;flex-direction:column;gap:14px">

        <!-- Category -->
        <div class="field">
          <label>Category</label>
          <select name="category" id="edit-category">
            <?php foreach ($cats as $c): ?>
            <option value="<?= $c ?>"><?= ucwords(str_replace('-',' ',$c)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Question -->
        <div class="field">
          <label>Question <span style="color:var(--clr-red)">*</span></label>
          <textarea name="question" id="edit-question" rows="3" required
            style="resize:vertical;font-size:.9rem"></textarea>
        </div>

        <!-- Answer — the main edit target -->
        <div class="field">
          <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:5px">
            <label style="margin:0">Answer <span style="color:var(--clr-red)">*</span></label>
            <span class="text-muted text-xs" id="edit-char-count"></span>
          </div>
          <textarea
            name="answer"
            id="edit-answer"
            rows="8"
            required
            placeholder="Write the full answer here…"
            style="resize:vertical;font-size:.9rem;line-height:1.6"
            oninput="updateCharCount(this)"
          ></textarea>
          <p class="text-muted text-xs" style="margin-top:4px">
            Tip: Be clear and concise. Answers appear on the public FAQs page.
          </p>
        </div>

        <!-- Sort order -->
        <div class="field">
          <label>Sort Order <span class="text-muted">(lower = appears first in category)</span></label>
          <input type="number" name="sort_order" id="edit-sort-order" min="0" style="width:100px">
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn--ghost modal-close">Cancel</button>
        <button type="submit" class="btn btn--primary">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Save Answer
        </button>
      </div>
    </form>

  </div>
</div>


<style>
/* ── FAQ answer preview in list ─────────────────────────────── */
.faq-answer-preview {
  font-size: .78rem;
  color: var(--clr-muted);
  margin-top: 5px;
  line-height: 1.55;
  max-height: 60px;
  overflow: hidden;
  position: relative;
  transition: max-height .25s ease;
}
.faq-answer-preview.expanded {
  max-height: 600px;
}
.faq-answer-preview:not(.expanded)::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 24px;
  background: linear-gradient(transparent, white);
}
.faq-admin-item {
  cursor: default;
}
.faq-admin-item__q {
  cursor: pointer;
  user-select: none;
}
.faq-admin-item__q:hover { color: var(--clr-navy); }
</style>


<script>
/* ── Populate and open edit modal ───────────────────────────── */
function openEditModal(id, category, question, answer, sortOrder) {
  document.getElementById('edit-faq-id').value      = id;
  document.getElementById('edit-category').value    = category;
  document.getElementById('edit-question').value    = question;
  document.getElementById('edit-answer').value      = answer;
  document.getElementById('edit-sort-order').value  = sortOrder;

  // Category label under heading
  const catLabel = category.replace(/-/g,' ').replace(/\b\w/g, c => c.toUpperCase());
  document.getElementById('edit-modal-cat-label').textContent = 'Category: ' + catLabel;

  // Update char count
  updateCharCount(document.getElementById('edit-answer'));

  // Auto-size the answer textarea to content
  const ta = document.getElementById('edit-answer');
  ta.style.height = 'auto';
  ta.style.height = Math.min(Math.max(ta.scrollHeight, 180), 420) + 'px';

  openModal('edit-faq-modal');
  // Focus the answer field — the main thing being edited
  setTimeout(() => document.getElementById('edit-answer').focus(), 150);
}

/* ── Live character count ───────────────────────────────────── */
function updateCharCount(ta) {
  const count   = ta.value.length;
  const counter = document.getElementById('edit-char-count');
  if (!counter) return;
  counter.textContent = count + ' characters';
  counter.style.color = count < 30 ? 'var(--clr-red)' : 'var(--clr-muted)';
}

/* ── Click question to expand/collapse answer preview ──────── */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.faq-admin-item__q').forEach(function (q) {
    q.addEventListener('click', function () {
      const preview = q.closest('.faq-admin-item__body')
                       .querySelector('.faq-answer-preview');
      if (preview) preview.classList.toggle('expanded');
    });
  });
});
</script>

<?php include __DIR__ . '/partials/foot.php'; ?>
