<?php
$pageTitle    = 'Enquiries';
$pageSubtitle = 'Contact messages from the website';
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo = db();

// Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $pdo->prepare('UPDATE enquiries SET status="read" WHERE id=?')
        ->execute([(int)$_POST['mark_read']]);
    header('Location: enquiries.php?msg=read');
    exit;
}
// Mark replied
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_replied'])) {
    $pdo->prepare('UPDATE enquiries SET status="replied" WHERE id=?')
        ->execute([(int)$_POST['mark_replied']]);
    header('Location: enquiries.php?msg=replied');
    exit;
}

$filterStatus = $_GET['status'] ?? '';
$sql  = 'SELECT * FROM enquiries' . ($filterStatus ? ' WHERE status=?' : '') . ' ORDER BY submitted_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($filterStatus ? [$filterStatus] : []);
$enquiries = $stmt->fetchAll();

$counts = $pdo->query("SELECT status, COUNT(*) FROM enquiries GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <?php if (!empty($_GET['msg'])): ?>
    <div class="alert alert--success" data-auto-dismiss>
      <?= $_GET['msg'] === 'read' ? 'Marked as read.' : 'Marked as replied.' ?>
    </div>
  <?php endif; ?>

  <!-- Filter pills -->
  <div style="display:flex;gap:8px;margin-bottom:16px">
    <a href="enquiries.php" class="btn btn--sm <?= !$filterStatus ? 'btn--navy' : 'btn--ghost' ?>">
      All (<?= array_sum($counts) ?>)
    </a>
    <?php foreach (['new','read','replied'] as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn--sm <?= $filterStatus===$s ? 'btn--navy' : 'btn--ghost' ?>">
      <?= ucfirst($s) ?> (<?= $counts[$s] ?? 0 ?>)
    </a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>From</th>
            <th>Email</th>
            <th>Message</th>
            <th>Status</th>
            <th>Received</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($enquiries as $e): ?>
          <tr <?= $e['status'] === 'new' ? 'style="background:#FFFBF0"' : '' ?>>
            <td class="text-muted text-sm"><?= $e['id'] ?></td>
            <td class="font-medium"><?= htmlspecialchars($e['name']) ?></td>
            <td class="text-sm"><?= htmlspecialchars($e['email']) ?></td>
            <td class="text-sm" style="max-width:320px">
              <span title="<?= htmlspecialchars($e['message']) ?>" class="truncate" style="display:block;max-width:280px">
                <?= htmlspecialchars(mb_strimwidth($e['message'], 0, 80, '…')) ?>
              </span>
            </td>
            <td><span class="badge badge--<?= $e['status'] ?>"><span class="status-dot"></span> <?= ucfirst($e['status']) ?></span></td>
            <td class="text-muted text-sm"><?= date('d M Y, H:i', strtotime($e['submitted_at'])) ?></td>
            <td>
              <div style="display:flex;gap:6px;align-items:center">
                <a href="mailto:<?= htmlspecialchars($e['email']) ?>?subject=Re:+Your+Enquiry+to+Hilret+Trust&body=Dear+<?= urlencode($e['name']) ?>," class="btn btn--navy btn--sm">
                  Reply
                </a>
                <?php if ($e['status'] === 'new'): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="mark_read" value="<?= $e['id'] ?>">
                  <button type="submit" class="btn btn--ghost btn--sm">Mark Read</button>
                </form>
                <?php elseif ($e['status'] === 'read'): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="mark_replied" value="<?= $e['id'] ?>">
                  <button type="submit" class="btn btn--primary btn--sm">Mark Replied</button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$enquiries): ?>
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <h4>No enquiries yet</h4>
                <p>Messages submitted via the website contact form will appear here.</p>
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
