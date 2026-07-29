<?php
$pageTitle    = 'Applications';
$pageSubtitle = 'Review and manage all accommodation applications';
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo    = db();
$status = $_GET['status'] ?? '';
$search = trim($_GET['q']     ?? '');

$where  = [];
$params = [];
if ($status) { $where[] = 'status = ?'; $params[] = $status; }
if ($search) {
    $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}

$sql = 'SELECT * FROM applications' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY submitted_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$apps = $stmt->fetchAll();

$counts = $pdo->query(
  "SELECT status, COUNT(*) as n FROM applications GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <?php if (!empty($_GET['msg'])): ?>
    <div class="alert alert--<?= $_GET['msg'] === 'updated' ? 'success' : 'error' ?>" data-auto-dismiss>
      <?= $_GET['msg'] === 'updated' ? 'Application status updated successfully.' : 'An error occurred.' ?>
    </div>
  <?php endif; ?>

  <!-- Status pills -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
    <?php
    $allStatuses = ['pending','approved','rejected','waitlisted'];
    $total = count($apps) + 0;
    ?>
    <a href="applications.php" class="btn btn--sm <?= !$status ? 'btn--navy' : 'btn--ghost' ?>">
      All (<?= array_sum($counts) ?>)
    </a>
    <?php foreach ($allStatuses as $s): ?>
    <a href="?status=<?= $s ?><?= $search ? '&q='.urlencode($search) : '' ?>"
       class="btn btn--sm <?= $status === $s ? 'btn--navy' : 'btn--ghost' ?>">
      <?= ucfirst($s) ?> (<?= $counts[$s] ?? 0 ?>)
    </a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="filter-bar">
      <form method="GET" style="display:contents">
        <?php if ($status): ?><input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>"><?php endif; ?>
        <input type="search" name="q" placeholder="Search by name or email…" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn--navy btn--sm">Search</button>
        <?php if ($search): ?>
          <a href="applications.php<?= $status ? '?status='.$status : '' ?>" class="btn btn--ghost btn--sm">Clear</a>
        <?php endif; ?>
      </form>
      <span class="filter-count"><?= count($apps) ?> records</span>
    </div>

    <div class="table-wrap">
      <table class="data-table" id="apps-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Applicant</th>
            <th>Contact</th>
            <th>Occupation</th>
            <th>Residence</th>
            <th>Move-in</th>
            <th>Status</th>
            <th>Submitted</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($apps as $a): ?>
          <tr>
            <td class="text-muted text-sm"><?= $a['id'] ?></td>
            <td>
              <span class="font-medium"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></span>
              <div class="text-muted text-xs"><?= htmlspecialchars($a['gender']) ?></div>
            </td>
            <td>
              <div class="text-sm"><?= htmlspecialchars($a['email']) ?></div>
              <div class="text-muted text-xs"><?= htmlspecialchars($a['phone']) ?></div>
            </td>
            <td class="text-sm"><?= htmlspecialchars(ucwords(str_replace('-',' ',$a['occupation']))) ?></td>
            <td class="text-sm"><?= htmlspecialchars(str_replace(['house','house-'],['House ','House '],$a['preferred_residence'])) ?></td>
            <td class="text-sm"><?= $a['move_in_date'] ? date('d M Y', strtotime($a['move_in_date'])) : '—' ?></td>
            <td><span class="badge badge--<?= $a['status'] ?>"><span class="status-dot"></span> <?= ucfirst($a['status']) ?></span></td>
            <td class="text-muted text-sm"><?= date('d M Y', strtotime($a['submitted_at'])) ?></td>
            <td>
              <a href="application-view.php?id=<?= $a['id'] ?>" class="btn btn--ghost btn--sm">View</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$apps): ?>
          <tr>
            <td colspan="9">
              <div class="empty-state">
                <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <h4>No applications found</h4>
                <p>Try adjusting your search or filter.</p>
              </div>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>
<?php include __DIR__ . '/partials/foot.php'; ?>
