<?php
$pageTitle    = 'Tenants & Residents';
$pageSubtitle = 'All active tenants across every block';
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo = db();

/* ── Filters ──────────────────────────────────────────────────── */
$filterBlock  = (int)($_GET['block']   ?? 0);
$filterStatus = $_GET['status'] ?? 'active';
$filterLease  = $_GET['lease']  ?? '';   // expiring30 | expired | all

$where  = [];
$params = [];

if ($filterBlock) { $where[] = 'prop.id = ?'; $params[] = $filterBlock; }

if ($filterStatus && $filterStatus !== 'all') {
    $where[] = 'res.status = ?';
    $params[] = $filterStatus;
}

if ($filterLease === 'expiring30') {
    $where[] = 'DATEDIFF(res.lease_end, CURDATE()) BETWEEN 0 AND 30';
} elseif ($filterLease === 'expired') {
    $where[] = 'res.lease_end < CURDATE()';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$residents = $pdo->prepare(
  "SELECT res.id AS resident_id,
          res.lease_start, res.lease_end, res.monthly_rent,
          res.deposit_amount, res.deposit_status, res.status AS tenant_status,
          res.application_id,
          a.first_name, a.last_name, a.email, a.phone, a.gender, a.occupation,
          rm.room_number, rm.floor, rm.type AS room_type,
          prop.id AS block_id, prop.name AS block_name,
          DATEDIFF(res.lease_end, CURDATE()) AS days_left
   FROM residents res
   JOIN rooms rm ON rm.id = res.room_id
   JOIN residences prop ON prop.id = rm.residence_id
   JOIN applications a ON a.id = res.application_id
   $whereSQL
   ORDER BY prop.name, rm.floor, rm.room_number"
);
$residents->execute($params);
$residents = $residents->fetchAll();

/* ── Block list for tabs ──────────────────────────────────────── */
$blocks = $pdo->query('SELECT id, name FROM residences ORDER BY name')->fetchAll();

/* ── Summary counts ───────────────────────────────────────────── */
$totalActive   = (int)$pdo->query("SELECT COUNT(*) FROM residents WHERE status='active'")->fetchColumn();
$expiring30    = (int)$pdo->query("SELECT COUNT(*) FROM residents WHERE status='active' AND DATEDIFF(lease_end, CURDATE()) BETWEEN 0 AND 30")->fetchColumn();
$expired       = (int)$pdo->query("SELECT COUNT(*) FROM residents WHERE status='active' AND lease_end < CURDATE()")->fetchColumn();
$movedOut      = (int)$pdo->query("SELECT COUNT(*) FROM residents WHERE status='moved-out'")->fetchColumn();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <?php if (!empty($_GET['msg'])): ?>
    <div class="alert alert--success" data-auto-dismiss>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <?= $_GET['msg'] === 'moved_out' ? 'Tenant moved out and room marked available.' : 'Done.' ?>
    </div>
  <?php endif; ?>

  <!-- ══ SUMMARY STATS ════════════════════════════════════════ -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px">

    <div class="kpi-card kpi-card--green" style="padding:16px 18px">
      <div class="kpi-card__label">Active Tenants</div>
      <div class="kpi-card__value" style="font-size:2rem"><?= $totalActive ?></div>
      <div class="kpi-card__rule"></div>
      <div class="kpi-card__sub">Across all blocks</div>
    </div>

    <div class="kpi-card kpi-card--amber" style="padding:16px 18px">
      <div class="kpi-card__label">Expiring in 30 days</div>
      <div class="kpi-card__value" style="font-size:2rem"><?= $expiring30 ?></div>
      <div class="kpi-card__rule"></div>
      <div class="kpi-card__sub">Need renewal action</div>
    </div>

    <div class="kpi-card kpi-card--red" style="padding:16px 18px">
      <div class="kpi-card__label">Leases Expired</div>
      <div class="kpi-card__value" style="font-size:2rem"><?= $expired ?></div>
      <div class="kpi-card__rule"></div>
      <div class="kpi-card__sub">Past end date</div>
    </div>

    <div class="kpi-card kpi-card--navy" style="padding:16px 18px">
      <div class="kpi-card__label">Moved Out</div>
      <div class="kpi-card__value" style="font-size:2rem"><?= $movedOut ?></div>
      <div class="kpi-card__rule"></div>
      <div class="kpi-card__sub">Historical records</div>
    </div>

  </div>

  <!-- ══ FILTERS ══════════════════════════════════════════════ -->
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;align-items:center">

    <!-- Block filter -->
    <a href="?status=<?= urlencode($filterStatus) ?>&lease=<?= urlencode($filterLease) ?>"
       class="btn btn--sm <?= !$filterBlock ? 'btn--navy' : 'btn--ghost' ?>">All Blocks</a>
    <?php foreach ($blocks as $b): ?>
    <a href="?block=<?= $b['id'] ?>&status=<?= urlencode($filterStatus) ?>&lease=<?= urlencode($filterLease) ?>"
       class="btn btn--sm <?= $filterBlock == $b['id'] ? 'btn--navy' : 'btn--ghost' ?>">
      <?= htmlspecialchars($b['name']) ?>
    </a>
    <?php endforeach; ?>

    <span style="width:1px;height:24px;background:var(--border);margin:0 4px"></span>

    <!-- Lease expiry filter -->
    <a href="?block=<?= $filterBlock ?>&status=<?= urlencode($filterStatus) ?>&lease="
       class="btn btn--sm <?= !$filterLease ? 'btn--amber' : 'btn--ghost' ?>">All Leases</a>
    <a href="?block=<?= $filterBlock ?>&status=<?= urlencode($filterStatus) ?>&lease=expiring30"
       class="btn btn--sm <?= $filterLease==='expiring30' ? 'btn--amber' : 'btn--ghost' ?>">
      Expiring Soon <?= $expiring30 > 0 ? "($expiring30)" : '' ?>
    </a>
    <a href="?block=<?= $filterBlock ?>&status=<?= urlencode($filterStatus) ?>&lease=expired"
       class="btn btn--sm <?= $filterLease==='expired' ? 'btn--danger' : 'btn--ghost' ?>">
      Expired <?= $expired > 0 ? "($expired)" : '' ?>
    </a>

    <span style="width:1px;height:24px;background:var(--border);margin:0 4px"></span>

    <!-- Tenant status -->
    <a href="?block=<?= $filterBlock ?>&status=active&lease=<?= urlencode($filterLease) ?>"
       class="btn btn--sm <?= $filterStatus==='active' ? 'btn--primary' : 'btn--ghost' ?>">Active</a>
    <a href="?block=<?= $filterBlock ?>&status=moved-out&lease=<?= urlencode($filterLease) ?>"
       class="btn btn--sm <?= $filterStatus==='moved-out' ? 'btn--ghost' : 'btn--ghost' ?>"
       style="<?= $filterStatus==='moved-out' ? 'border-color:var(--navy);color:var(--navy)' : '' ?>">Moved Out</a>
    <a href="?block=<?= $filterBlock ?>&status=all&lease=<?= urlencode($filterLease) ?>"
       class="btn btn--sm btn--ghost" style="<?= $filterStatus==='all' ? 'border-color:var(--navy);color:var(--navy)' : '' ?>">All</a>

    <span class="filter-count" style="margin-left:auto"><?= count($residents) ?> tenant<?= count($residents) != 1 ? 's' : '' ?></span>
  </div>

  <!-- ══ TENANTS TABLE ═════════════════════════════════════════ -->
  <div class="card">
    <div class="table-wrap">
      <table class="data-table" id="res-table">
        <thead>
          <tr>
            <th>Tenant</th>
            <th>Contact</th>
            <th>Block</th>
            <th>Room</th>
            <th>Rent (R/mo)</th>
            <th>Lease Start</th>
            <th>Lease Expires</th>
            <th>Days Left</th>
            <th>Deposit</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($residents as $r):
            $dl = $r['days_left'];
            if ($r['tenant_status'] === 'active') {
              if ($dl < 0)        { $daysBg = 'background:#fff5f5'; $daysStyle = 'color:var(--red);font-weight:800';   $daysText = 'EXPIRED'; }
              elseif ($dl <= 14)  { $daysBg = 'background:#fff8f0'; $daysStyle = 'color:var(--red);font-weight:700';   $daysText = $dl . ' days'; }
              elseif ($dl <= 30)  { $daysBg = 'background:#fffbf0'; $daysStyle = 'color:var(--amber);font-weight:700'; $daysText = $dl . ' days'; }
              else                { $daysBg = '';                   $daysStyle = 'color:var(--green)';                  $daysText = $dl . ' days'; }
            } else {
              $daysBg = ''; $daysStyle = 'color:var(--stone)'; $daysText = $dl !== null ? $dl . 'd' : '—';
            }
          ?>
          <tr style="<?= $daysBg ?>">

            <td>
              <div style="font-weight:700;font-size:.83rem;color:var(--ink)">
                <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>
              </div>
              <div class="text-muted text-xs"><?= ucfirst($r['gender'] ?? '') ?></div>
            </td>

            <td>
              <div class="text-sm"><?= htmlspecialchars($r['email'] ?? '') ?></div>
              <div class="text-muted text-xs"><?= htmlspecialchars($r['phone'] ?? '') ?></div>
            </td>

            <td>
              <span style="font-family:var(--font-head);font-size:.78rem;font-weight:700;color:var(--navy)">
                <?= htmlspecialchars($r['block_name']) ?>
              </span>
            </td>

            <td>
              <span style="font-family:var(--font-head);font-weight:800;font-size:.85rem">
                <?= htmlspecialchars($r['room_number']) ?>
              </span>
              <div class="text-muted text-xs">Floor <?= $r['floor'] ?> · <?= ucfirst($r['room_type']) ?></div>
            </td>

            <td class="font-medium">
              R <?= number_format($r['monthly_rent'] ?? 0, 0) ?>
            </td>

            <td class="text-sm text-muted">
              <?= $r['lease_start'] ? date('d M Y', strtotime($r['lease_start'])) : '—' ?>
            </td>

            <td class="text-sm">
              <?= $r['lease_end'] ? date('d M Y', strtotime($r['lease_end'])) : '—' ?>
            </td>

            <td>
              <span style="font-family:var(--font-head);font-size:.8rem;<?= $daysStyle ?>">
                <?= $daysText ?>
              </span>
            </td>

            <td>
              <div>
                <span class="badge badge--<?= $r['deposit_status'] === 'paid' ? 'approved' : ($r['deposit_status'] === 'refunded' ? 'read' : 'pending') ?>">
                  <?= ucfirst($r['deposit_status'] ?? 'unpaid') ?>
                </span>
                <div class="text-muted text-xs" style="margin-top:2px">
                  R <?= number_format($r['deposit_amount'] ?? 0, 0) ?>
                </div>
              </div>
            </td>

            <td>
              <span class="badge badge--<?= $r['tenant_status'] ?>">
                <span class="status-dot"></span>
                <?= ucfirst(str_replace('-', ' ', $r['tenant_status'])) ?>
              </span>
            </td>

            <td>
              <div style="display:flex;gap:5px;align-items:center">
                <?php if ($r['application_id']): ?>
                <a href="application-view.php?id=<?= $r['application_id'] ?>"
                   class="btn btn--ghost btn--sm">Profile</a>
                <?php endif; ?>

                <?php if ($r['tenant_status'] === 'active'): ?>
                <button class="btn btn--danger btn--sm"
                  onclick="openMoveOutModalResidents(<?= $r['resident_id'] ?>, '<?= htmlspecialchars(addslashes($r['first_name'] . ' ' . $r['last_name'])) ?>', '<?= htmlspecialchars($r['room_number']) ?>')">
                  Move Out
                </button>
                <?php endif; ?>
              </div>
            </td>

          </tr>
          <?php endforeach; ?>

          <?php if (!$residents): ?>
          <tr><td colspan="11">
            <div class="empty-state" style="padding:40px">
              <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
              </svg>
              <h4>No tenants found</h4>
              <p>Add tenants via the <a href="rooms.php" style="color:var(--orange)">Rooms</a> page by clicking "Assign Tenant" on any available room.</p>
            </div>
          </td></tr>
          <?php endif; ?>

        </tbody>
      </table>
    </div>
  </div>

</main>


<!-- ══ MOVE OUT MODAL ═══════════════════════════════════════════ -->
<div class="modal-backdrop" id="move-out-modal-r">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h3>Move Out Tenant</h3>
      <button class="modal-close">&times;</button>
    </div>
    <form method="POST" action="/backend/move-out.php">
      <input type="hidden" name="resident_id" id="mor-resident-id">
      <div class="modal-body">
        <p style="margin-bottom:14px;font-size:.85rem">
          Moving out <strong id="mor-name"></strong> from Room <strong id="mor-room"></strong>.
          This will free the room and mark it as available.
        </p>
        <div class="field" style="margin-bottom:12px">
          <label>Move-out Date</label>
          <input type="date" name="move_out_date" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="field" style="margin-bottom:12px">
          <label>Reason</label>
          <input type="text" name="reason" value="Lease ended" placeholder="e.g. Lease ended, voluntary departure">
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

<script>
function openMoveOutModalResidents(id, name, room) {
  document.getElementById('mor-resident-id').value = id;
  document.getElementById('mor-name').textContent  = name;
  document.getElementById('mor-room').textContent  = room;
  openModal('move-out-modal-r');
}
</script>

<?php include __DIR__ . '/partials/foot.php'; ?>
