<?php
$pageTitle    = 'Room Availability';
$pageSubtitle = 'Control the "rooms available" numbers shown on the homepage';
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo = db();

/* ── Save handler ─────────────────────────────────────────────── */
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = $_POST['available'] ?? [];
    $stmt    = $pdo->prepare('UPDATE residences SET available_rooms = ? WHERE id = ?');
    foreach ($updates as $resId => $count) {
        $resId = (int) $resId;
        $count = max(0, (int) $count);
        $stmt->execute([$count, $resId]);
    }
    $successMsg = 'Availability updated. The homepage will now show the new numbers.';
}

/* ── Load residences ──────────────────────────────────────────── */
$residences = $pdo->query(
    "SELECT id, name, address, available_rooms, total_rooms, hero_image, status
     FROM residences ORDER BY name"
)->fetchAll();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <!-- Explainer banner -->
  <div class="alert alert--info" style="margin-bottom:20px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span>
      These numbers appear in the <strong>availability strip on the homepage</strong>.
      Set a house to <strong>0</strong> to show it as full (red dot). Changes take effect immediately.
    </span>
  </div>

  <?php if ($successMsg): ?>
    <div class="alert alert--success" data-auto-dismiss>
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      <?= htmlspecialchars($successMsg) ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,280px),1fr));gap:18px;margin-bottom:24px">

      <?php foreach ($residences as $r):
        $avail    = (int) $r['available_rooms'];
        $total    = (int) $r['total_rooms'];
        $dotColor = $avail > 0 ? '#BECC66' : '#e05252';
      ?>
      <div class="card" style="overflow:hidden">

        <!-- House photo -->
        <div style="position:relative">
          <img
            src="/<?= rawurlencode($r['hero_image'] ?? '') ?>"
            alt="<?= htmlspecialchars($r['name']) ?>"
            style="width:100%;height:130px;object-fit:cover;display:block;background:var(--bg)"
            onerror="this.style.display='none'"
          >
          <span style="
            position:absolute;top:8px;right:8px;
            background:rgba(0,0,0,.55);backdrop-filter:blur(4px);
            color:#fff;font-size:.62rem;font-weight:700;letter-spacing:.8px;
            text-transform:uppercase;padding:3px 9px;border-radius:20px
          "><?= $r['status'] === 'active' ? 'Active' : 'Inactive' ?></span>
        </div>

        <div style="padding:16px 18px">

          <!-- House name + availability dot -->
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
            <div style="
              width:9px;height:9px;border-radius:50%;flex-shrink:0;
              background:<?= $dotColor ?>;box-shadow:0 0 6px <?= $dotColor ?>;
              transition:background .3s,box-shadow .3s
            " class="avail-dot" data-id="<?= $r['id'] ?>"></div>
            <h3 style="font-family:var(--font-head);font-size:.95rem;font-weight:700;flex:1;color:var(--ink)">
              <?= htmlspecialchars($r['name']) ?>
            </h3>
          </div>

          <p style="font-size:.75rem;color:var(--stone);margin-bottom:14px">
            <?= htmlspecialchars($r['address'] ?? 'Botanic Grove, Durban') ?>
          </p>

          <!-- ── Static: total rooms ────────────────────────────── -->
          <div style="
            display:flex;align-items:center;justify-content:space-between;
            background:var(--bg);border:1px solid var(--border);
            border-radius:var(--r-sm);padding:9px 13px;margin-bottom:14px
          ">
            <div>
              <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--stone);margin-bottom:2px">
                Total Rooms in House
              </div>
              <div style="font-size:1.25rem;font-weight:800;color:var(--ink)"><?= $total ?></div>
            </div>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--stone)" stroke-width="1.5" opacity=".5">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </div>

          <!-- ── Editable: available rooms ──────────────────────── -->
          <label style="display:block;margin-bottom:8px;font-size:.8rem;font-weight:700;color:var(--ink)">
            Rooms Available
            <span style="font-weight:400;color:var(--stone);font-size:.73rem">(shown on homepage)</span>
          </label>

          <div style="display:flex;align-items:center;gap:8px">
            <button type="button"
              class="btn btn--ghost"
              style="width:40px;height:40px;padding:0;font-size:1.3rem;flex-shrink:0;justify-content:center;border-radius:var(--r-sm)"
              onclick="stepAvail(<?= $r['id'] ?>, -1)">−</button>

            <input
              type="number"
              name="available[<?= $r['id'] ?>]"
              id="avail-<?= $r['id'] ?>"
              value="<?= $avail ?>"
              min="0"
              max="<?= $total ?>"
              oninput="previewDot(<?= $r['id'] ?>, this.value)"
              onfocus="this.style.borderColor='#FE6600';this.style.boxShadow='0 0 0 3px rgba(254,102,0,.15)'"
              onblur="this.style.borderColor='#DDD6C8';this.style.boxShadow='none'"
              style="
                flex:1;min-width:0;text-align:center;
                font-family:var(--font-head);font-size:1.6rem;font-weight:800;
                color:var(--navy);padding:7px 4px;
                border:2px solid var(--border);border-radius:var(--r-sm);
                outline:none;transition:border-color .2s,box-shadow .2s;
                background:var(--surface)
              "
            >

            <button type="button"
              class="btn btn--ghost"
              style="width:40px;height:40px;padding:0;font-size:1.3rem;flex-shrink:0;justify-content:center;border-radius:var(--r-sm)"
              onclick="stepAvail(<?= $r['id'] ?>, 1, <?= $total ?>)">+</button>
          </div>

          <!-- Live preview -->
          <div style="
            margin-top:10px;background:var(--bg);
            border-radius:var(--r-sm);padding:8px 12px;
            font-size:.76rem;color:var(--stone);border:1px solid var(--border)
          ">
            <span style="font-weight:600;color:var(--ink)">Homepage: </span>
            <span id="preview-<?= $r['id'] ?>">
              <?= $avail === 0 ? 'No rooms available' : '(' . $avail . ') room' . ($avail === 1 ? '' : 's') . ' available' ?>
            </span>
          </div>

        </div>
      </div>
      <?php endforeach; ?>

    </div>

    <!-- Save button -->
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
      <button type="submit" class="btn btn--primary" style="padding:11px 32px;font-size:.95rem">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Save All Changes
      </button>
      <p class="text-muted text-sm">Changes update the homepage immediately.</p>
    </div>

  </form>

  <!-- Homepage strip preview -->
  <div class="card" style="margin-top:28px">
    <div class="card-header">
      <h3>Homepage Strip Preview</h3>
      <span class="text-muted text-xs">How the availability strip looks on your website</span>
    </div>
    <div class="card-body" style="background:var(--navy);border-radius:0 0 var(--r-md) var(--r-md);padding:20px 24px">
      <div style="display:flex;gap:24px;flex-wrap:wrap" id="strip-preview">
        <?php foreach ($residences as $r):
          $avail = (int) $r['available_rooms'];
          $color = $avail > 0 ? '#BECC66' : '#e05252';
        ?>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="
            width:8px;height:8px;border-radius:50%;flex-shrink:0;
            background:<?= $color ?>;box-shadow:0 0 6px <?= $color ?>
          " class="strip-dot" id="strip-dot-<?= $r['id'] ?>"></span>
          <span style="color:rgba(255,255,255,.9);font-size:.85rem;font-weight:600">
            <?= htmlspecialchars($r['name']) ?>
          </span>
          <span style="color:rgba(255,255,255,.55);font-size:.8rem" id="strip-text-<?= $r['id'] ?>">
            <?= $avail === 0 ? 'No rooms available' : '(' . $avail . ') room' . ($avail === 1 ? '' : 's') . ' available' ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</main>

<script>
function stepAvail(id, delta, max) {
  var inp = document.getElementById('avail-' + id);
  if (!inp) return;
  var val = Math.max(0, parseInt(inp.value || 0) + delta);
  if (max !== undefined) val = Math.min(val, max);
  inp.value = val;
  previewDot(id, val);
}

function previewDot(id, rawVal) {
  var val   = Math.max(0, parseInt(rawVal) || 0);
  var color = val > 0 ? '#BECC66' : '#e05252';

  var dot = document.querySelector('.avail-dot[data-id="' + id + '"]');
  if (dot) { dot.style.background = color; dot.style.boxShadow = '0 0 6px ' + color; }

  var txt = val === 0 ? 'No rooms available'
          : '(' + val + ') room' + (val === 1 ? '' : 's') + ' available';

  var prev = document.getElementById('preview-' + id);
  if (prev) prev.textContent = txt;

  var stripDot  = document.getElementById('strip-dot-' + id);
  var stripText = document.getElementById('strip-text-' + id);
  if (stripDot)  { stripDot.style.background = color; stripDot.style.boxShadow = '0 0 6px ' + color; }
  if (stripText) stripText.textContent = txt;
}
</script>

<?php include __DIR__ . '/partials/foot.php'; ?>
