<?php
$pageTitle    = 'Overview';
$pageSubtitle = '';
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo = db();

$totalApps      = (int) $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$pendingApps    = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$approvedApps   = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status='approved'")->fetchColumn();
$rejectedApps   = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status='rejected'")->fetchColumn();
$waitlistedApps = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status='waitlisted'")->fetchColumn();
$totalResidents = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE status='active'")->fetchColumn();
$availableRooms = (int) $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='available'")->fetchColumn();
$occupiedRooms  = (int) $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='occupied'")->fetchColumn();
$totalRooms     = (int) $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$newEnquiries   = (int) $pdo->query("SELECT COUNT(*) FROM enquiries WHERE status='new'")->fetchColumn();
$monthlyRevenue = (float)($pdo->query("SELECT COALESCE(SUM(monthly_rent),0) FROM residents WHERE status='active'")->fetchColumn());
$occupancyRate  = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

$recentApps = $pdo->query(
  "SELECT id, first_name, last_name, preferred_residence, status, occupation, submitted_at
   FROM applications ORDER BY submitted_at DESC LIMIT 7"
)->fetchAll();

$recentEnqs = $pdo->query(
  "SELECT id, name, email, message, status, submitted_at
   FROM enquiries ORDER BY submitted_at DESC LIMIT 5"
)->fetchAll();

$residences = $pdo->query(
  "SELECT r.id, r.name, r.available_rooms, r.total_rooms, r.hero_image, r.price_from,
          (r.total_rooms - r.available_rooms) AS occupied
   FROM residences r WHERE r.status='active' ORDER BY r.name"
)->fetchAll();

$hour      = (int) date('H');
$greeting  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', $user['name'])[0];
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <!-- ══ WELCOME BANNER ═══════════════════════════════════════ -->
  <div class="welcome-banner">
    <div class="welcome-banner__text">
      <div class="welcome-banner__greeting">
        <?= $greeting ?>, <span><?= htmlspecialchars($firstName) ?></span>
      </div>
      <div class="welcome-banner__sub">
        <?php if ($pendingApps > 0): ?>
          <strong style="color:rgba(255,255,255,.85)"><?= $pendingApps ?> application<?= $pendingApps > 1 ? 's' : '' ?></strong> awaiting review
          <?= $newEnquiries > 0 ? ' · <strong style="color:rgba(255,255,255,.85)">' . $newEnquiries . ' new message' . ($newEnquiries > 1 ? 's' : '') . '</strong>' : '' ?>
        <?php else: ?>
          All applications reviewed · <?= $newEnquiries > 0 ? $newEnquiries . ' new message' . ($newEnquiries > 1 ? 's' : '') : 'Everything up to date' ?>
        <?php endif; ?>
        &nbsp;·&nbsp; <?= date('l, d F Y') ?>
      </div>
    </div>
    <div class="welcome-banner__actions">
      <?php if ($pendingApps > 0): ?>
      <a href="applications.php?status=pending" class="welcome-banner__btn welcome-banner__btn--primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Review Applications
      </a>
      <?php endif; ?>
      <a href="availability.php" class="welcome-banner__btn welcome-banner__btn--ghost">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        Availability
      </a>
    </div>
  </div>

  <!-- ══ KPI CARDS ════════════════════════════════════════════ -->
  <div class="kpi-grid">

    <div class="kpi-card kpi-card--amber">
      <div class="kpi-card__icon-wrap">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <div class="kpi-card__label">Pending</div>
      <div class="kpi-card__value"><?= $pendingApps ?></div>
      <div class="kpi-card__rule"></div>
      <div class="kpi-card__sub"><?= $totalApps ?> total applications</div>
    </div>

    <div class="kpi-card kpi-card--green">
      <div class="kpi-card__icon-wrap">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      </div>
      <div class="kpi-card__label">Residents</div>
      <div class="kpi-card__value"><?= $totalResidents ?></div>
      <div class="kpi-card__rule"></div>
      <div class="kpi-card__sub"><?= $approvedApps ?> approved</div>
    </div>

    <div class="kpi-card kpi-card--navy">
      <div class="kpi-card__icon-wrap">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <div class="kpi-card__label">Available Rooms</div>
      <div class="kpi-card__value"><?= $availableRooms ?></div>
      <div class="kpi-card__rule"></div>
      <div class="kpi-card__sub"><?= $occupancyRate ?>% occupied</div>
    </div>

    <div class="kpi-card kpi-card--red">
      <div class="kpi-card__icon-wrap">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </div>
      <div class="kpi-card__label">New Messages</div>
      <div class="kpi-card__value"><?= $newEnquiries ?></div>
      <div class="kpi-card__rule"></div>
      <div class="kpi-card__sub">Awaiting response</div>
    </div>

    <div class="kpi-card kpi-card--orange">
      <div class="kpi-card__icon-wrap">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      </div>
      <div class="kpi-card__label">Monthly Revenue</div>
      <div class="kpi-card__value" style="font-size:1.6rem;letter-spacing:-1px">R&thinsp;<?= number_format($monthlyRevenue, 0, '.', ' ') ?></div>
      <div class="kpi-card__rule"></div>
      <div class="kpi-card__sub">From active leases</div>
    </div>

  </div>

  <!-- ══ MAIN CONTENT GRID ════════════════════════════════════ -->
  <div class="overview-grid">

    <!-- LEFT: Recent Applications -->
    <div class="card">
      <div class="card-header">
        <h3>Recent Applications</h3>
        <div style="display:flex;gap:8px;align-items:center">
          <?php if ($pendingApps > 0): ?>
          <span class="badge badge--pending"><span class="status-dot"></span> <?= $pendingApps ?> pending</span>
          <?php endif; ?>
          <a href="applications.php" class="btn btn--ghost btn--sm">All applications</a>
        </div>
      </div>

      <!-- Status distribution bar -->
      <?php if ($totalApps > 0):
        $pPct = round($pendingApps  / $totalApps * 100);
        $aPct = round($approvedApps / $totalApps * 100);
        $rPct = round($rejectedApps / $totalApps * 100);
        $wPct = max(0, 100 - $pPct - $aPct - $rPct);
      ?>
      <div style="padding:11px 16px;border-bottom:1px solid var(--border-lt);background:var(--surface-2)">
        <div style="display:flex;height:6px;border-radius:2px;overflow:hidden;gap:1px">
          <?php if ($pPct): ?><div style="width:<?= $pPct ?>%;background:var(--amber)"></div><?php endif; ?>
          <?php if ($aPct): ?><div style="width:<?= $aPct ?>%;background:var(--green)"></div><?php endif; ?>
          <?php if ($rPct): ?><div style="width:<?= $rPct ?>%;background:var(--red)"></div><?php endif; ?>
          <?php if ($wPct): ?><div style="width:<?= $wPct ?>%;background:var(--purple)"></div><?php endif; ?>
        </div>
        <div style="display:flex;gap:14px;margin-top:7px;flex-wrap:wrap">
          <?php foreach ([
            ['Pending',    $pendingApps,    'var(--amber)'],
            ['Approved',   $approvedApps,   'var(--green)'],
            ['Rejected',   $rejectedApps,   'var(--red)'],
            ['Waitlisted', $waitlistedApps, 'var(--purple)'],
          ] as [$lbl,$cnt,$clr]): ?>
          <span style="display:flex;align-items:center;gap:5px;font-size:.66rem;color:var(--stone)">
            <span style="width:7px;height:7px;border-radius:1px;background:<?= $clr ?>;flex-shrink:0"></span>
            <?= $lbl ?>&nbsp;<strong style="color:var(--ink-2)"><?= $cnt ?></strong>
          </span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Applicant</th>
              <th>Residence</th>
              <th>Occupation</th>
              <th>Status</th>
              <th>Date</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentApps as $a): ?>
            <tr>
              <td style="font-weight:700;color:var(--ink)"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
              <td class="text-sm"><?= htmlspecialchars(ucwords(str_replace(['house29','house35','house39','house25'],['House 29','House 35','House 39','House 25'],$a['preferred_residence']))) ?></td>
              <td class="text-sm text-muted"><?= ucwords(str_replace('-',' ',$a['occupation'])) ?></td>
              <td><span class="badge badge--<?= $a['status'] ?>"><span class="status-dot"></span> <?= ucfirst($a['status']) ?></span></td>
              <td class="text-muted text-sm"><?= date('d M Y', strtotime($a['submitted_at'])) ?></td>
              <td><a href="application-view.php?id=<?= $a['id'] ?>" class="btn btn--ghost btn--sm">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$recentApps): ?>
            <tr><td colspan="6">
              <div class="empty-state" style="padding:28px">
                <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                <h4>No applications yet</h4>
                <p>Applications from the website will appear here.</p>
              </div>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- RIGHT column -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <!-- Properties -->
      <div class="card">
        <div class="card-header">
          <h3>Properties</h3>
          <a href="availability.php" class="btn btn--ghost btn--sm">Edit</a>
        </div>
        <div class="prop-mini">
          <?php foreach ($residences as $r):
            $pct       = $r['total_rooms'] > 0 ? round(($r['occupied'] / $r['total_rooms']) * 100) : 0;
            $fillClass = $pct >= 90 ? 'prop-row__fill--full' : ($pct >= 65 ? 'prop-row__fill--mid' : '');
            $dotColor  = $r['available_rooms'] > 0 ? '#BECC66' : '#e05252';
          ?>
          <div class="prop-row">
            <img
              class="prop-row__thumb"
              src="/<?= rawurlencode($r['hero_image'] ?? '') ?>"
              alt="<?= htmlspecialchars($r['name']) ?>"
              onerror="this.style.display='none'"
            >
            <div class="prop-row__info">
              <div class="prop-row__name">
                <span class="prop-row__avail-dot" style="background:<?= $dotColor ?>;box-shadow:0 0 4px <?= $dotColor ?>"></span>
                <?= htmlspecialchars($r['name']) ?>
              </div>
              <div class="prop-row__bar-wrap">
                <div class="prop-row__bar">
                  <div class="prop-row__fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="prop-row__pct"><?= $pct ?>%</span>
              </div>
              <div class="prop-row__rooms"><?= $r['available_rooms'] ?> available · R&thinsp;<?= number_format($r['price_from'],0) ?>/mo</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="padding:10px 14px;border-top:1px solid var(--border-lt);display:flex;gap:8px">
          <a href="rooms.php" class="btn btn--ghost btn--sm" style="flex:1;justify-content:center">Rooms</a>
          <a href="residences.php" class="btn btn--navy btn--sm" style="flex:1;justify-content:center">Properties</a>
        </div>
      </div>

      <!-- Enquiries -->
      <div class="card">
        <div class="card-header">
          <h3>Messages</h3>
          <?php if ($newEnquiries > 0): ?>
          <span class="badge badge--new"><span class="status-dot"></span><?= $newEnquiries ?> new</span>
          <?php endif; ?>
        </div>
        <ul class="activity-list">
          <?php foreach ($recentEnqs as $e): ?>
          <li class="activity-item">
            <div class="activity-dot <?= $e['status'] === 'new' ? 'activity-dot--orange' : '' ?>"></div>
            <div class="activity-text">
              <div style="font-weight:700;font-size:.8rem"><?= htmlspecialchars($e['name']) ?></div>
              <div class="text-muted text-xs truncate" style="max-width:160px">
                <?= htmlspecialchars(mb_strimwidth($e['message'], 0, 52, '…')) ?>
              </div>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <div class="activity-time"><?= date('d M', strtotime($e['submitted_at'])) ?></div>
            </div>
          </li>
          <?php endforeach; ?>
          <?php if (!$recentEnqs): ?>
          <li class="activity-item" style="justify-content:center">
            <span class="text-muted text-sm">No messages yet.</span>
          </li>
          <?php endif; ?>
        </ul>
        <div style="padding:10px 14px;border-top:1px solid var(--border-lt)">
          <a href="enquiries.php" class="btn btn--ghost btn--sm w-full">View all messages</a>
        </div>
      </div>

    </div>
  </div>

</main>

<?php include __DIR__ . '/partials/foot.php'; ?>
