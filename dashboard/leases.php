<?php
$pageTitle = 'Lease Management';
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo = db();

$filterRes    = (int)($_GET['residence_id'] ?? 0);
$filterStatus = $_GET['lease_status'] ?? 'all';

$allResidences = $pdo->query('SELECT id, name FROM residences WHERE status="active" ORDER BY name')->fetchAll();

$where   = ['res.status = "active"'];
$params  = [];

if ($filterRes) {
    $where[]  = 'rm.residence_id = ?';
    $params[] = $filterRes;
}
if ($filterStatus === 'expiring') {
    $where[] = 'DATEDIFF(res.lease_end, CURDATE()) BETWEEN 0 AND 30';
} elseif ($filterStatus === 'expired') {
    $where[] = 'DATEDIFF(res.lease_end, CURDATE()) < 0';
} elseif ($filterStatus === 'active') {
    $where[] = 'DATEDIFF(res.lease_end, CURDATE()) > 30';
}

$sql = "SELECT res.*,
               a.first_name, a.last_name, a.email, a.phone,
               rm.room_number, rm.floor, rm.type AS room_type,
               rv.name AS residence_name,
               DATEDIFF(res.lease_end, CURDATE()) AS days_left
        FROM residents res
        JOIN rooms rm ON rm.id = res.room_id
        JOIN residences rv ON rv.id = rm.residence_id
        LEFT JOIN applications a ON a.id = res.application_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY res.lease_end ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leases = $stmt->fetchAll();

$stats = [
    'total'    => count($leases),
    'expiring' => count(array_filter($leases, fn($l) => $l['days_left'] >= 0 && $l['days_left'] <= 30)),
    'expired'  => count(array_filter($leases, fn($l) => $l['days_left'] < 0)),
    'revenue'  => array_sum(array_column($leases, 'monthly_rent')),
];

$pageSubtitle = 'All active leases across Hilret Trust properties';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <!-- KPI row -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:22px">
    <div class="kpi-card kpi-card--navy" style="padding:14px 16px">
      <div class="kpi-card__label">Active Leases</div>
      <div class="kpi-card__value" style="font-size:1.9rem"><?= $stats['total'] ?></div>
    </div>
    <div class="kpi-card kpi-card--amber" style="padding:14px 16px">
      <div class="kpi-card__label">Expiring (30 days)</div>
      <div class="kpi-card__value" style="font-size:1.9rem"><?= $stats['expiring'] ?></div>
    </div>
    <div class="kpi-card kpi-card--red" style="padding:14px 16px">
      <div class="kpi-card__label">Expired</div>
      <div class="kpi-card__value" style="font-size:1.9rem"><?= $stats['expired'] ?></div>
    </div>
    <div class="kpi-card kpi-card--green" style="padding:14px 16px">
      <div class="kpi-card__label">Monthly Revenue</div>
      <div class="kpi-card__value" style="font-size:1.15rem;letter-spacing:-1px">R&thinsp;<?= number_format($stats['revenue'], 0) ?></div>
    </div>
  </div>

  <!-- Filters -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;align-items:center">

    <!-- Residence filter -->
    <div style="display:flex;gap:4px;flex-wrap:wrap">
      <a href="?lease_status=<?= $filterStatus ?>"
         class="btn btn--sm <?= !$filterRes ? 'btn--navy' : 'btn--ghost' ?>">All Properties</a>
      <?php foreach ($allResidences as $r): ?>
      <a href="?residence_id=<?= $r['id'] ?>&lease_status=<?= $filterStatus ?>"
         class="btn btn--sm <?= $r['id'] == $filterRes ? 'btn--navy' : 'btn--ghost' ?>">
        <?= htmlspecialchars($r['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>

    <div style="margin-left:auto;display:flex;gap:4px">
      <a href="?residence_id=<?= $filterRes ?>&lease_status=all"
         class="btn btn--sm <?= $filterStatus==='all'      ? 'btn--primary' : 'btn--ghost' ?>">All</a>
      <a href="?residence_id=<?= $filterRes ?>&lease_status=active"
         class="btn btn--sm <?= $filterStatus==='active'   ? 'btn--primary' : 'btn--ghost' ?>">Healthy</a>
      <a href="?residence_id=<?= $filterRes ?>&lease_status=expiring"
         class="btn btn--sm <?= $filterStatus==='expiring' ? 'btn--amber'   : 'btn--ghost' ?>">Expiring</a>
      <a href="?residence_id=<?= $filterRes ?>&lease_status=expired"
         class="btn btn--sm <?= $filterStatus==='expired'  ? 'btn--danger'  : 'btn--ghost' ?>">Expired</a>
    </div>
  </div>

  <!-- Lease table -->
  <div class="card">
    <div class="card-header">
      <h3>Leases</h3>
      <span class="text-muted text-sm"><?= count($leases) ?> record<?= count($leases) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if ($leases): ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tenant</th>
            <th>Property</th>
            <th>Room</th>
            <th>Rent</th>
            <th>Deposit</th>
            <th>Lease Start</th>
            <th>Lease End</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leases as $l):
            $days = (int)$l['days_left'];
            if ($days < 0)       { $statusBadge = 'badge--pending'; $statusText = 'Expired'; $rowStyle = 'background:#fff5f5'; }
            elseif ($days <= 14) { $statusBadge = 'badge--pending'; $statusText = $days.'d left'; $rowStyle = 'background:#fff5f5'; }
            elseif ($days <= 30) { $statusBadge = 'badge--waitlisted'; $statusText = $days.'d left'; $rowStyle = 'background:#fffbf0'; }
            else                 { $statusBadge = 'badge--approved'; $statusText = $days.'d left'; $rowStyle = ''; }
          ?>
          <tr style="<?= $rowStyle ?>">
            <td>
              <div style="font-weight:700;font-size:.83rem"><?= htmlspecialchars($l['first_name'] . ' ' . $l['last_name']) ?></div>
              <div class="text-muted text-xs"><?= htmlspecialchars($l['email'] ?? '') ?></div>
            </td>
            <td class="text-sm"><?= htmlspecialchars($l['residence_name']) ?></td>
            <td>
              <span style="font-family:var(--font-head);font-weight:800;font-size:.85rem">
                <?= htmlspecialchars($l['room_number']) ?>
              </span>
              <span class="text-muted text-xs"> · F<?= $l['floor'] ?></span>
            </td>
            <td class="text-sm font-medium">R <?= number_format($l['monthly_rent'], 0) ?></td>
            <td>
              <span class="badge badge--<?= $l['deposit_status'] === 'paid' ? 'approved' : ($l['deposit_status'] === 'refunded' ? 'read' : 'pending') ?>">
                <?= ucfirst($l['deposit_status']) ?>
              </span>
            </td>
            <td class="text-sm text-muted"><?= $l['lease_start'] ? date('d M Y', strtotime($l['lease_start'])) : '—' ?></td>
            <td class="text-sm"><?= $l['lease_end'] ? date('d M Y', strtotime($l['lease_end'])) : '—' ?></td>
            <td>
              <span class="badge <?= $statusBadge ?>">
                <span class="status-dot"></span><?= $statusText ?>
              </span>
            </td>
            <td>
              <a href="lease-view.php?id=<?= $l['id'] ?>" class="btn btn--ghost btn--sm">View Lease</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
      </svg>
      <h4>No leases found</h4>
      <p>No active leases match the current filter.</p>
    </div>
    <?php endif; ?>
  </div>

</main>

<?php include __DIR__ . '/partials/foot.php'; ?>
