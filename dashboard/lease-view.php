<?php
$pageTitle = 'Lease Detail';
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo = db();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: leases.php');
    exit;
}

/* ── Handle lease update ─────────────────────────────────────── */
$updateMsg   = '';
$updateError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lease'])) {
    $leaseStart     = $_POST['lease_start'] ?? '';
    $leaseEnd       = $_POST['lease_end']   ?? '';
    $monthlyRent    = (float)($_POST['monthly_rent']    ?? 0);
    $depositAmount  = (float)($_POST['deposit_amount']  ?? 0);
    $depositStatus  = in_array($_POST['deposit_status'] ?? '', ['unpaid','paid','refunded'])
                      ? $_POST['deposit_status'] : 'unpaid';

    if ($leaseStart && $leaseEnd && $leaseEnd >= $leaseStart) {
        $pdo->prepare(
            'UPDATE residents SET lease_start=?, lease_end=?, monthly_rent=?, deposit_amount=?, deposit_status=? WHERE id=?'
        )->execute([$leaseStart, $leaseEnd, $monthlyRent, $depositAmount, $depositStatus, $id]);
        $updateMsg = 'Lease updated successfully.';
    } else {
        $updateError = 'Lease end date must be after the start date.';
    }
}

/* ── Fetch lease data ────────────────────────────────────────── */
$stmt = $pdo->prepare(
    "SELECT res.*,
            a.first_name, a.last_name, a.email, a.phone, a.gender, a.occupation,
            a.funding_source, a.id AS application_id,
            rm.room_number, rm.floor, rm.type AS room_type, rm.price AS room_price,
            rv.id AS residence_id, rv.name AS residence_name,
            DATEDIFF(res.lease_end, CURDATE()) AS days_left
     FROM residents res
     JOIN rooms rm ON rm.id = res.room_id
     JOIN residences rv ON rv.id = rm.residence_id
     LEFT JOIN applications a ON a.id = res.application_id
     WHERE res.id = ?"
);
$stmt->execute([$id]);
$lease = $stmt->fetch();

if (!$lease) {
    header('Location: leases.php');
    exit;
}

$days = (int)$lease['days_left'];
if ($lease['status'] !== 'active') {
    $statusLabel = ucfirst(str_replace('-', ' ', $lease['status']));
    $statusClass = 'badge--pending';
} elseif ($days < 0) {
    $statusLabel = 'Expired';
    $statusClass = 'badge--pending';
} elseif ($days <= 14) {
    $statusLabel = 'Critical — '.$days.'d left';
    $statusClass = 'badge--pending';
} elseif ($days <= 30) {
    $statusLabel = 'Expiring — '.$days.'d left';
    $statusClass = 'badge--waitlisted';
} else {
    $statusLabel = 'Active — '.$days.'d left';
    $statusClass = 'badge--approved';
}

$pageSubtitle = 'Lease for ' . $lease['first_name'] . ' ' . $lease['last_name'] . ' · Room ' . $lease['room_number'];
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <?php if ($updateMsg): ?>
  <div class="alert alert--success" data-auto-dismiss>
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <?= htmlspecialchars($updateMsg) ?>
  </div>
  <?php endif; ?>
  <?php if ($updateError): ?>
  <div class="alert alert--danger" style="margin-bottom:16px">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= htmlspecialchars($updateError) ?>
  </div>
  <?php endif; ?>

  <!-- Back breadcrumb -->
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:.82rem;color:var(--stone)">
    <a href="leases.php" style="color:var(--stone);text-decoration:none;display:flex;align-items:center;gap:4px">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Leases
    </a>
    <span>›</span>
    <span style="color:var(--ink)"><?= htmlspecialchars($lease['first_name'] . ' ' . $lease['last_name']) ?></span>
  </div>

  <!-- Header row -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:22px;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:14px">
      <div style="width:52px;height:52px;border-radius:50%;background:var(--navy);color:#fff;font-family:var(--font-head);font-weight:800;font-size:1.3rem;display:flex;align-items:center;justify-content:center">
        <?= strtoupper(substr($lease['first_name'], 0, 1)) ?>
      </div>
      <div>
        <h2 style="font-size:1.25rem;margin:0"><?= htmlspecialchars($lease['first_name'] . ' ' . $lease['last_name']) ?></h2>
        <div style="display:flex;align-items:center;gap:10px;margin-top:4px">
          <span class="badge <?= $statusClass ?>">
            <span class="status-dot"></span><?= $statusLabel ?>
          </span>
          <span class="text-muted text-sm">
            <?= htmlspecialchars($lease['residence_name']) ?> · Room <?= htmlspecialchars($lease['room_number']) ?>
          </span>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:8px">
      <?php if ($lease['application_id']): ?>
      <a href="application-view.php?id=<?= $lease['application_id'] ?>" class="btn btn--ghost btn--sm">View Application</a>
      <?php endif; ?>
      <a href="rooms.php?residence_id=<?= $lease['residence_id'] ?>" class="btn btn--ghost btn--sm">View Room</a>
    </div>
  </div>

  <!-- Info grid: tenant + lease -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">

    <!-- Tenant card -->
    <div class="card" style="padding:20px 22px">
      <h4 style="font-size:.72rem;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--stone);margin-bottom:14px">Tenant Info</h4>
      <dl style="display:grid;grid-template-columns:auto 1fr;gap:6px 16px;font-size:.83rem">
        <dt style="color:var(--stone);font-weight:600">Email</dt>
        <dd><?= htmlspecialchars($lease['email'] ?? '—') ?></dd>
        <dt style="color:var(--stone);font-weight:600">Phone</dt>
        <dd><?= htmlspecialchars($lease['phone'] ?? '—') ?></dd>
        <dt style="color:var(--stone);font-weight:600">Occupation</dt>
        <dd><?= htmlspecialchars(ucfirst($lease['occupation'] ?? '—')) ?></dd>
        <dt style="color:var(--stone);font-weight:600">Funding</dt>
        <dd><?= htmlspecialchars(ucwords($lease['funding_source'] ?? '—')) ?></dd>
        <dt style="color:var(--stone);font-weight:600">Gender</dt>
        <dd><?= ucfirst($lease['gender'] ?? '—') ?></dd>
      </dl>
    </div>

    <!-- Room card -->
    <div class="card" style="padding:20px 22px">
      <h4 style="font-size:.72rem;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--stone);margin-bottom:14px">Room Info</h4>
      <dl style="display:grid;grid-template-columns:auto 1fr;gap:6px 16px;font-size:.83rem">
        <dt style="color:var(--stone);font-weight:600">Property</dt>
        <dd><?= htmlspecialchars($lease['residence_name']) ?></dd>
        <dt style="color:var(--stone);font-weight:600">Room</dt>
        <dd style="font-family:var(--font-head);font-weight:800"><?= htmlspecialchars($lease['room_number']) ?></dd>
        <dt style="color:var(--stone);font-weight:600">Floor</dt>
        <dd>Floor <?= $lease['floor'] ?></dd>
        <dt style="color:var(--stone);font-weight:600">Type</dt>
        <dd><?= ucfirst($lease['room_type']) ?></dd>
        <dt style="color:var(--stone);font-weight:600">Listed Price</dt>
        <dd>R <?= number_format($lease['room_price'], 0) ?>/mo</dd>
      </dl>
    </div>

  </div>

  <!-- Lease edit form -->
  <div class="card">
    <div class="card-header">
      <h3>Lease Details</h3>
      <span class="text-muted text-sm">Update dates, rent or deposit status</span>
    </div>
    <form method="POST" style="padding:20px 22px">
      <input type="hidden" name="update_lease" value="1">

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px">

        <div class="field">
          <label>Lease Start <span style="color:var(--red)">*</span></label>
          <input type="date" name="lease_start" required
            value="<?= htmlspecialchars($lease['lease_start'] ?? '') ?>">
        </div>

        <div class="field">
          <label>Lease End <span style="color:var(--red)">*</span></label>
          <input type="date" name="lease_end" required
            value="<?= htmlspecialchars($lease['lease_end'] ?? '') ?>">
        </div>

        <div class="field">
          <label>Days Remaining</label>
          <div style="padding:10px 12px;background:var(--surface-2);border-radius:var(--r-sm);font-family:var(--font-head);font-weight:800;font-size:.95rem;
            color:<?= $days < 0 ? 'var(--red)' : ($days <= 30 ? 'var(--amber)' : 'var(--green)') ?>">
            <?= $days < 0 ? 'Expired '.abs($days).'d ago' : $days.' days' ?>
          </div>
        </div>

        <div class="field">
          <label>Monthly Rent (R) <span style="color:var(--red)">*</span></label>
          <input type="number" name="monthly_rent" step="50" min="0" required
            value="<?= htmlspecialchars($lease['monthly_rent'] ?? '') ?>">
        </div>

        <div class="field">
          <label>Deposit Amount (R)</label>
          <input type="number" name="deposit_amount" step="50" min="0"
            value="<?= htmlspecialchars($lease['deposit_amount'] ?? '') ?>">
        </div>

        <div class="field">
          <label>Deposit Status</label>
          <select name="deposit_status">
            <option value="unpaid"   <?= ($lease['deposit_status'] ?? '') === 'unpaid'   ? 'selected' : '' ?>>Unpaid</option>
            <option value="paid"     <?= ($lease['deposit_status'] ?? '') === 'paid'     ? 'selected' : '' ?>>Paid</option>
            <option value="refunded" <?= ($lease['deposit_status'] ?? '') === 'refunded' ? 'selected' : '' ?>>Refunded</option>
          </select>
        </div>

      </div>

      <!-- Quick extend buttons -->
      <div style="margin-bottom:18px">
        <p style="font-size:.74rem;color:var(--stone);margin-bottom:8px;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Quick Extend</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <button type="button" class="btn btn--ghost btn--sm" onclick="extendLease(3)">+3 months</button>
          <button type="button" class="btn btn--ghost btn--sm" onclick="extendLease(6)">+6 months</button>
          <button type="button" class="btn btn--ghost btn--sm" onclick="extendLease(12)">+1 year</button>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:10px">
        <button type="submit" class="btn btn--primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Save Changes
        </button>
        <a href="leases.php" class="btn btn--ghost">Cancel</a>

        <?php if ($lease['status'] === 'active'): ?>
        <div style="margin-left:auto">
          <button type="button" class="btn btn--danger btn--sm"
            onclick="openModal('move-out-modal')">
            Move Out Tenant
          </button>
        </div>
        <?php endif; ?>
      </div>

    </form>
  </div>

</main>


<!-- ══ MOVE OUT MODAL ═══════════════════════════════════════════ -->
<div class="modal-backdrop" id="move-out-modal">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h3>Move Out Tenant</h3>
      <button class="modal-close">&times;</button>
    </div>
    <form method="POST" action="/backend/move-out.php">
      <input type="hidden" name="resident_id" value="<?= $id ?>">
      <div class="modal-body">
        <p style="margin-bottom:14px;font-size:.85rem">
          You are moving out <strong><?= htmlspecialchars($lease['first_name'] . ' ' . $lease['last_name']) ?></strong>
          from Room <strong><?= htmlspecialchars($lease['room_number']) ?></strong>.
        </p>
        <div class="field" style="margin-bottom:12px">
          <label>Move-out Date</label>
          <input type="date" name="move_out_date" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="field" style="margin-bottom:12px">
          <label>Reason</label>
          <input type="text" name="reason" value="Lease ended" placeholder="e.g. Lease ended, voluntary departure…">
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;cursor:pointer">
          <input type="checkbox" name="refund_deposit" value="1">
          Mark deposit as refunded
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn--ghost modal-close">Cancel</button>
        <button type="submit" class="btn btn--danger">Confirm Move Out</button>
      </div>
    </form>
  </div>
</div>


<?php
$extraJs = <<<JS
function extendLease(months) {
  var endInput = document.querySelector('input[name="lease_end"]');
  var current  = endInput.value ? new Date(endInput.value) : new Date();
  current.setMonth(current.getMonth() + months);
  endInput.value = current.toISOString().slice(0, 10);
}
JS;
?>
<?php include __DIR__ . '/partials/foot.php'; ?>
