<?php
$pageTitle    = 'Residences';
$pageSubtitle = 'Manage your properties, pricing, and availability';
$topbarCTA    = ['href' => 'residence-edit.php', 'label' => '+ Add Residence'];
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo = db();
$residences = $pdo->query(
  "SELECT r.*,
     (SELECT COUNT(*) FROM rooms WHERE residence_id=r.id) as total_rooms_db,
     (SELECT COUNT(*) FROM rooms WHERE residence_id=r.id AND status='available') as avail_db,
     (SELECT COUNT(*) FROM rooms WHERE residence_id=r.id AND status='occupied') as occ_db
   FROM residences r ORDER BY r.name"
)->fetchAll();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <?php if (!empty($_GET['msg'])): ?>
    <div class="alert alert--success" data-auto-dismiss>Residence updated successfully.</div>
  <?php endif; ?>

  <div class="res-dash-grid">
    <?php foreach ($residences as $r):
      $pct = $r['total_rooms_db'] > 0 ? round(($r['occ_db'] / $r['total_rooms_db']) * 100) : 0;
      $fillClass = $pct >= 90 ? 'occupancy-fill--full' : ($pct >= 60 ? 'occupancy-fill--mid' : '');
    ?>
    <div class="res-dash-card">
      <img class="res-dash-card__img"
           src="/<?= rawurlencode($r['hero_image'] ?? '') ?>"
           alt="<?= htmlspecialchars($r['name']) ?>">
      <div class="res-dash-card__body">
        <div class="flex-center justify-between" style="margin-bottom:4px">
          <div class="res-dash-card__name"><?= htmlspecialchars($r['name']) ?></div>
          <span class="badge badge--<?= $r['status'] === 'active' ? 'available' : 'maintenance' ?>">
            <?= ucfirst($r['status']) ?>
          </span>
        </div>
        <div class="res-dash-card__meta">
          <?= htmlspecialchars($r['address'] ?? $r['region']) ?><br>
          From <strong>R <?= number_format($r['price_from'], 0) ?>/mo</strong>
        </div>
        <div class="res-dash-card__occupancy">
          <span class="text-muted text-xs"><?= $r['occ_db'] ?>/<?= $r['total_rooms_db'] ?> occupied</span>
          <div class="occupancy-bar">
            <div class="occupancy-fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></div>
          </div>
          <span class="text-xs font-medium"><?= $pct ?>%</span>
        </div>
        <div style="font-size:.75rem;color:var(--clr-muted);margin-bottom:12px">
          <?= htmlspecialchars($r['email'] ?? '') ?> &mdash; <?= htmlspecialchars($r['phone'] ?? '') ?>
        </div>
        <div class="res-dash-card__actions">
          <a href="residence-edit.php?id=<?= $r['id'] ?>" class="btn btn--navy btn--sm" style="flex:1">Edit</a>
          <a href="rooms.php?residence_id=<?= $r['id'] ?>" class="btn btn--ghost btn--sm" style="flex:1">Rooms (<?= $r['total_rooms_db'] ?>)</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

</main>
<?php include __DIR__ . '/partials/foot.php'; ?>
