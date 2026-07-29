<?php
$pageTitle = 'Rooms & Tenants';
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo = db();

// Approved/pending applications not yet assigned to any active room — used in "From Application" tab
$unassignedApps = $pdo->query(
    "SELECT a.id, a.first_name, a.last_name, a.email, a.phone,
            a.preferred_residence, a.occupation, a.funding_source, a.status, a.submitted_at
     FROM applications a
     LEFT JOIN residents r ON r.application_id = a.id AND r.status = 'active'
     WHERE r.id IS NULL
       AND a.status IN ('approved','pending','waitlisted')
     ORDER BY FIELD(a.status,'approved','pending','waitlisted'), a.submitted_at DESC"
)->fetchAll();

/* ── Handle quick room status change ─────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'], $_POST['status']) && !isset($_POST['add_room'])) {
    $allowed = ['available','occupied','maintenance'];
    $newStatus = $_POST['status'];
    $rid = (int)$_POST['room_id'];
    if (in_array($newStatus, $allowed)) {
        $pdo->prepare('UPDATE rooms SET status = ? WHERE id = ?')->execute([$newStatus, $rid]);
        $room = $pdo->prepare('SELECT residence_id FROM rooms WHERE id = ?');
        $room->execute([$rid]);
        $resId = (int)$room->fetchColumn();
        if ($resId) {
            $s = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE residence_id=? AND status='available'");
            $s->execute([$resId]); $avail = (int)$s->fetchColumn();
            $s = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE residence_id=?");
            $s->execute([$resId]); $total = (int)$s->fetchColumn();
            $pdo->prepare('UPDATE residences SET available_rooms=?, total_rooms=? WHERE id=?')->execute([$avail,$total,$resId]);
        }
    }
    $resId = $resId ?? 0;
    header("Location: rooms.php?residence_id=$resId&msg=updated");
    exit;
}

/* ── Handle add room ──────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $rId   = (int)$_POST['add_residence_id'];
    $num   = trim($_POST['room_number'] ?? '');
    $type  = $_POST['room_type_add'] ?? 'single';
    $floor = (int)($_POST['floor'] ?? 1);
    $price = (float)($_POST['price'] ?? 0);
    if ($num && $rId) {
        $pdo->prepare('INSERT INTO rooms (residence_id,room_number,type,floor,price,status) VALUES (?,?,?,?,?,"available")')
            ->execute([$rId, $num, $type, $floor, $price]);
        $s = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE residence_id=? AND status='available'");
        $s->execute([$rId]); $avail = (int)$s->fetchColumn();
        $s = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE residence_id=?");
        $s->execute([$rId]); $total = (int)$s->fetchColumn();
        $pdo->prepare('UPDATE residences SET available_rooms=?, total_rooms=? WHERE id=?')->execute([$avail,$total,$rId]);
    }
    header("Location: rooms.php?residence_id=$rId&msg=added");
    exit;
}

/* ── Data ─────────────────────────────────────────────────────── */
$allResidences = $pdo->query('SELECT id, name FROM residences WHERE status="active" ORDER BY name')->fetchAll();
$residenceId   = (int)($_GET['residence_id'] ?? ($allResidences[0]['id'] ?? 0));
$residence     = null;
$rooms         = [];
$blockStats    = [];

if ($residenceId) {
    $s = $pdo->prepare('SELECT * FROM residences WHERE id = ?');
    $s->execute([$residenceId]);
    $residence = $s->fetch();
}

if ($residence) {
    // Rooms with current tenant info joined
    $rooms = $pdo->query(
        "SELECT rm.*,
                res.id        AS resident_id,
                res.lease_start, res.lease_end,
                res.monthly_rent, res.deposit_status, res.status AS res_status,
                a.first_name, a.last_name, a.email, a.phone,
                DATEDIFF(res.lease_end, CURDATE()) AS days_left
         FROM rooms rm
         LEFT JOIN residents res ON res.room_id = rm.id AND res.status = 'active'
         LEFT JOIN applications a ON a.id = res.application_id
         WHERE rm.residence_id = $residenceId
         ORDER BY rm.floor, rm.room_number"
    )->fetchAll();

    // Block-level stats
    $blockStats = [
        'total'       => count($rooms),
        'occupied'    => count(array_filter($rooms, fn($r) => $r['status'] === 'occupied')),
        'available'   => count(array_filter($rooms, fn($r) => $r['status'] === 'available')),
        'maintenance' => count(array_filter($rooms, fn($r) => $r['status'] === 'maintenance')),
        'expiring30'  => count(array_filter($rooms, fn($r) => $r['days_left'] !== null && $r['days_left'] >= 0 && $r['days_left'] <= 30)),
        'expired'     => count(array_filter($rooms, fn($r) => $r['days_left'] !== null && $r['days_left'] < 0)),
        'revenue'     => array_sum(array_column(array_filter($rooms, fn($r) => $r['status'] === 'occupied'), 'price')),
    ];
}

$pageSubtitle = $residence ? 'Managing ' . $residence['name'] . ' — Botanic Grove' : 'Select a property below';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <?php if (!empty($_GET['msg'])): ?>
    <?php $msgs = ['updated'=>'Room updated.','added'=>'Room added.','tenant_added'=>'Tenant assigned successfully.']; ?>
    <div class="alert alert--success" data-auto-dismiss>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <?= htmlspecialchars($msgs[$_GET['msg']] ?? 'Done.') ?>
    </div>
  <?php endif; ?>

  <!-- ══ BLOCK SELECTOR TABS ═══════════════════════════════════ -->
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;align-items:center">
    <?php foreach ($allResidences as $r): ?>
    <a href="?residence_id=<?= $r['id'] ?>"
       class="btn btn--sm <?= $r['id'] == $residenceId ? 'btn--navy' : 'btn--ghost' ?>">
      <?= htmlspecialchars($r['name']) ?>
    </a>
    <?php endforeach; ?>
    <button onclick="openModal('add-room-modal')"
            class="btn btn--amber btn--sm" style="margin-left:auto">
      + Add Room
    </button>
  </div>

  <?php if ($residence && $rooms): ?>

  <!-- ══ BLOCK STATS ════════════════════════════════════════════ -->
  <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:22px">

    <div class="kpi-card kpi-card--navy" style="padding:14px 16px">
      <div class="kpi-card__label">Total Rooms</div>
      <div class="kpi-card__value" style="font-size:1.8rem"><?= $blockStats['total'] ?></div>
    </div>

    <div class="kpi-card kpi-card--green" style="padding:14px 16px">
      <div class="kpi-card__label">Occupied</div>
      <div class="kpi-card__value" style="font-size:1.8rem"><?= $blockStats['occupied'] ?></div>
    </div>

    <div class="kpi-card kpi-card--amber" style="padding:14px 16px">
      <div class="kpi-card__label">Available</div>
      <div class="kpi-card__value" style="font-size:1.8rem"><?= $blockStats['available'] ?></div>
    </div>

    <div class="kpi-card kpi-card--red" style="padding:14px 16px">
      <div class="kpi-card__label">Maintenance</div>
      <div class="kpi-card__value" style="font-size:1.8rem"><?= $blockStats['maintenance'] ?></div>
    </div>

    <div class="kpi-card kpi-card--orange" style="padding:14px 16px">
      <div class="kpi-card__label">Expiring (30d)</div>
      <div class="kpi-card__value" style="font-size:1.8rem"><?= $blockStats['expiring30'] ?></div>
    </div>

    <div class="kpi-card kpi-card--navy" style="padding:14px 16px">
      <div class="kpi-card__label">Monthly Revenue</div>
      <div class="kpi-card__value" style="font-size:1.2rem;letter-spacing:-1px">
        R&thinsp;<?= number_format($blockStats['revenue'], 0) ?>
      </div>
    </div>

  </div>

  <!-- ══ ROOMS TABLE WITH TENANTS ══════════════════════════════ -->
  <div class="card">
    <div class="card-header">
      <h3><?= htmlspecialchars($residence['name']) ?> — All Rooms</h3>
      <span class="text-muted text-sm">
        <?= $blockStats['occupied'] ?>/<?= $blockStats['total'] ?> occupied
        · R <?= number_format($residence['price_from'], 0) ?>/mo from
      </span>
    </div>

    <div class="table-wrap">
      <table class="data-table" id="rooms-table">
        <thead>
          <tr>
            <th>Room</th>
            <th>Floor</th>
            <th>Type</th>
            <th>Rent</th>
            <th>Status</th>
            <th>Tenant</th>
            <th>Lease Start</th>
            <th>Lease Expires</th>
            <th>Days Left</th>
            <th>Deposit</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rooms as $room):
            $daysLeft  = $room['days_left'];
            $leaseColor = '';
            $leaseLabel = '';
            if ($daysLeft !== null && $room['status'] === 'occupied') {
              if ($daysLeft < 0)       { $leaseColor = 'color:var(--red);font-weight:800';   $leaseLabel = 'EXPIRED'; }
              elseif ($daysLeft <= 14)  { $leaseColor = 'color:var(--red);font-weight:700';   $leaseLabel = $daysLeft . 'd'; }
              elseif ($daysLeft <= 30)  { $leaseColor = 'color:var(--amber);font-weight:700'; $leaseLabel = $daysLeft . 'd'; }
              else                      { $leaseColor = 'color:var(--green)';                  $leaseLabel = $daysLeft . 'd'; }
            }
          ?>
          <tr <?= $daysLeft !== null && $daysLeft < 0 ? 'style="background:#fff5f5"' : ($daysLeft !== null && $daysLeft <= 30 && $daysLeft >= 0 ? 'style="background:#fffbf0"' : '') ?>>

            <!-- Room number -->
            <td>
              <?php if ($room['status'] === 'occupied' && $room['resident_id']): ?>
                <a href="lease-view.php?id=<?= $room['resident_id'] ?>"
                   style="font-family:var(--font-head);font-weight:800;font-size:.88rem;color:var(--navy);text-decoration:none;border-bottom:2px solid var(--orange)"
                   title="View lease for Room <?= htmlspecialchars($room['room_number']) ?>">
                  <?= htmlspecialchars($room['room_number']) ?>
                </a>
              <?php else: ?>
                <span style="font-family:var(--font-head);font-weight:800;font-size:.88rem">
                  <?= htmlspecialchars($room['room_number']) ?>
                </span>
              <?php endif; ?>
            </td>

            <!-- Floor -->
            <td class="text-sm text-muted">Floor <?= $room['floor'] ?></td>

            <!-- Type -->
            <td class="text-sm"><?= ucfirst($room['type']) ?></td>

            <!-- Rent -->
            <td class="text-sm font-medium">
              R <?= number_format($room['price'] ?? 0, 0) ?>
            </td>

            <!-- Status -->
            <td>
              <span class="badge badge--<?= $room['status'] ?>">
                <span class="status-dot"></span>
                <?= ucfirst($room['status']) ?>
              </span>
            </td>

            <!-- Tenant -->
            <td>
              <?php if ($room['first_name']): ?>
                <div style="font-weight:700;font-size:.82rem;color:var(--ink)">
                  <?= htmlspecialchars($room['first_name'] . ' ' . $room['last_name']) ?>
                </div>
                <div class="text-muted text-xs"><?= htmlspecialchars($room['email'] ?? '') ?></div>
              <?php elseif ($room['status'] === 'available'): ?>
                <span class="text-muted text-xs">— vacant —</span>
              <?php else: ?>
                <span class="text-muted text-xs">—</span>
              <?php endif; ?>
            </td>

            <!-- Lease start -->
            <td class="text-sm text-muted">
              <?= $room['lease_start'] ? date('d M Y', strtotime($room['lease_start'])) : '—' ?>
            </td>

            <!-- Lease end -->
            <td class="text-sm">
              <?= $room['lease_end'] ? date('d M Y', strtotime($room['lease_end'])) : '—' ?>
            </td>

            <!-- Days left -->
            <td>
              <?php if ($leaseLabel): ?>
                <span style="font-family:var(--font-head);font-size:.78rem;<?= $leaseColor ?>">
                  <?= $leaseLabel ?>
                </span>
              <?php else: ?>
                <span class="text-muted text-xs">—</span>
              <?php endif; ?>
            </td>

            <!-- Deposit -->
            <td>
              <?php if ($room['resident_id']): ?>
                <span class="badge badge--<?= $room['deposit_status'] === 'paid' ? 'approved' : ($room['deposit_status'] === 'refunded' ? 'read' : 'pending') ?>">
                  <?= ucfirst($room['deposit_status'] ?? 'unpaid') ?>
                </span>
              <?php else: ?>
                <span class="text-muted text-xs">—</span>
              <?php endif; ?>
            </td>

            <!-- Actions -->
            <td>
              <div style="display:flex;gap:6px;align-items:center">

                <?php if ($room['status'] === 'available'): ?>
                  <!-- Assign Tenant -->
                  <button class="btn btn--primary btn--sm"
                    onclick="openAssignModal(<?= $room['id'] ?>, '<?= htmlspecialchars($room['room_number']) ?>', <?= $room['price'] ?? 0 ?>)">
                    + Assign Tenant
                  </button>

                <?php elseif ($room['status'] === 'occupied' && $room['resident_id']): ?>
                  <!-- View lease -->
                  <a href="lease-view.php?id=<?= $room['resident_id'] ?>"
                     class="btn btn--primary btn--sm">View Lease</a>
                  <!-- View tenant application -->
                  <?php if ($room['application_id'] ?? null): ?>
                  <a href="application-view.php?id=<?= $room['application_id'] ?? '' ?>"
                     class="btn btn--ghost btn--sm">Application</a>
                  <?php endif; ?>
                  <!-- Move out -->
                  <button class="btn btn--danger btn--sm"
                    onclick="openMoveOutModal(<?= $room['resident_id'] ?>, '<?= htmlspecialchars(addslashes($room['first_name'] . ' ' . $room['last_name'])) ?>', '<?= htmlspecialchars($room['room_number']) ?>')">
                    Move Out
                  </button>

                <?php else: ?>
                  <!-- Change status (maintenance etc) -->
                  <form method="POST" style="display:inline-flex;gap:5px">
                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                    <select name="status" style="padding:4px 8px;border:1px solid var(--border);border-radius:var(--r-sm);font-size:.74rem;background:var(--surface)">
                      <option value="available"   <?= $room['status']==='available'   ? 'selected':'' ?>>Available</option>
                      <option value="occupied"    <?= $room['status']==='occupied'    ? 'selected':'' ?>>Occupied</option>
                      <option value="maintenance" <?= $room['status']==='maintenance' ? 'selected':'' ?>>Maintenance</option>
                    </select>
                    <button type="submit" class="btn btn--navy btn--sm">Save</button>
                  </form>
                <?php endif; ?>

              </div>
            </td>

          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php elseif ($residence): ?>
    <div class="card">
      <div class="empty-state">
        <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        <h4>No rooms added yet</h4>
        <p>Click "+ Add Room" to add the first room to <?= htmlspecialchars($residence['name']) ?>.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="empty-state">
        <h4>Select a property above to manage its rooms</h4>
      </div>
    </div>
  <?php endif; ?>

</main>


<!-- ══ ASSIGN TENANT MODAL (two-tab) ════════════════════════════ -->
<div class="modal-backdrop" id="assign-modal">
  <div class="modal" style="max-width:620px">

    <div class="modal-header" style="flex-direction:column;align-items:flex-start;gap:10px">
      <div style="display:flex;align-items:center;justify-content:space-between;width:100%">
        <div>
          <h3>Assign Tenant</h3>
          <p class="text-muted text-xs" id="assign-room-label" style="margin-top:2px"></p>
        </div>
        <button class="modal-close">&times;</button>
      </div>
      <!-- Tab switcher -->
      <div style="display:flex;gap:0;border:1px solid var(--border);border-radius:var(--r-sm);overflow:hidden;width:100%">
        <button id="tab-manual-btn" onclick="switchAssignTab('manual')"
          style="flex:1;padding:8px 16px;font-size:.78rem;font-weight:700;font-family:var(--font-body);border:none;cursor:pointer;background:var(--navy);color:#fff;transition:background .15s">
          ✍ Enter Details Manually
        </button>
        <button id="tab-app-btn" onclick="switchAssignTab('app')"
          style="flex:1;padding:8px 16px;font-size:.78rem;font-weight:700;font-family:var(--font-body);border:none;cursor:pointer;background:var(--surface-2);color:var(--stone);transition:background .15s">
          📋 From Website Application
          <?php if (count($unassignedApps) > 0): ?>
            <span style="background:var(--orange);color:#fff;font-size:.6rem;padding:1px 6px;border-radius:99px;margin-left:4px"><?= count($unassignedApps) ?></span>
          <?php endif; ?>
        </button>
      </div>
    </div>

    <!-- ── TAB 1: Manual entry ─────────────────────────────── -->
    <div id="tab-manual">
      <form id="assign-form">
        <input type="hidden" name="room_id" id="assign-room-id">
        <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">

          <div class="field">
            <label>First Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="first_name" required placeholder="e.g. Thabo">
          </div>
          <div class="field">
            <label>Last Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="last_name" required placeholder="e.g. Mokoena">
          </div>
          <div class="field">
            <label>Email Address <span style="color:var(--red)">*</span></label>
            <input type="email" name="email" required placeholder="tenant@email.com">
          </div>
          <div class="field">
            <label>Phone Number <span style="color:var(--red)">*</span></label>
            <input type="tel" name="phone" required placeholder="082 000 0000">
          </div>
          <div class="field">
            <label>Date of Birth</label>
            <input type="date" name="dob">
          </div>
          <div class="field">
            <label>Gender</label>
            <select name="gender">
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div style="grid-column:1/-1;border-top:1px solid var(--border-lt);padding-top:10px">
            <p style="font-family:var(--font-head);font-size:.68rem;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--stone)">Lease Details</p>
          </div>
          <div class="field">
            <label>Lease Start <span style="color:var(--red)">*</span></label>
            <input type="date" name="lease_start" id="assign-lease-start" required>
          </div>
          <div class="field">
            <label>Lease End <span style="color:var(--red)">*</span></label>
            <input type="date" name="lease_end" id="assign-lease-end" required>
          </div>
          <div class="field">
            <label>Monthly Rent (R)</label>
            <input type="number" name="monthly_rent" id="assign-rent" step="50" min="0">
          </div>
          <div class="field">
            <label>Deposit Amount (R)</label>
            <input type="number" name="deposit_amount" id="assign-deposit" step="50" min="0">
          </div>
          <div class="field">
            <label>Deposit Status</label>
            <select name="deposit_status">
              <option value="unpaid">Unpaid</option>
              <option value="paid">Paid</option>
            </select>
          </div>
          <div class="field" style="grid-column:1/-1">
            <label>Notes <span class="text-muted">(optional)</span></label>
            <textarea name="notes" rows="2" placeholder="Any special requirements or notes…"></textarea>
          </div>
        </div>

        <div id="assign-error" style="display:none;margin:0 22px 4px;padding:10px 14px;background:var(--red-dim);color:var(--red);border-radius:var(--r-sm);font-size:.8rem;border:1px solid #fca5a5"></div>

        <div class="modal-footer">
          <button type="button" class="btn btn--ghost modal-close">Cancel</button>
          <button type="submit" class="btn btn--primary" id="assign-submit">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Assign Tenant
          </button>
        </div>
      </form>
    </div>

    <!-- ── TAB 2: From Application ─────────────────────────── -->
    <div id="tab-app" style="display:none">
      <form id="app-assign-form">
        <input type="hidden" name="room_id"        id="app-room-id">
        <input type="hidden" name="application_id" id="app-application-id">

        <div class="modal-body" style="padding-bottom:8px">

          <!-- Search box -->
          <div style="position:relative;margin-bottom:12px">
            <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);opacity:.4;pointer-events:none" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="search" id="app-search" placeholder="Search by name or email…"
              style="width:100%;padding:9px 12px 9px 34px;border:1px solid var(--border);border-radius:var(--r-sm);font-size:.84rem;font-family:var(--font-body);outline:none;background:var(--surface)"
              oninput="filterApps(this.value)">
          </div>

          <!-- Application list -->
          <div id="app-list" style="max-height:240px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--r-sm);background:var(--surface)">
            <?php if (!$unassignedApps): ?>
              <div style="padding:28px;text-align:center;color:var(--stone);font-size:.82rem">
                <div style="font-size:2rem;margin-bottom:8px">📭</div>
                No pending applications without a room assignment.
              </div>
            <?php endif; ?>
            <?php foreach ($unassignedApps as $a): ?>
            <div class="app-row"
                 data-id="<?= $a['id'] ?>"
                 data-name="<?= htmlspecialchars(strtolower($a['first_name'] . ' ' . $a['last_name'] . ' ' . $a['email'])) ?>"
                 data-firstname="<?= htmlspecialchars($a['first_name']) ?>"
                 data-lastname="<?= htmlspecialchars($a['last_name']) ?>"
                 data-email="<?= htmlspecialchars($a['email']) ?>"
                 data-phone="<?= htmlspecialchars($a['phone']) ?>"
                 data-residence="<?= htmlspecialchars($a['preferred_residence']) ?>"
                 onclick="selectApplication(this)"
                 style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-bottom:1px solid var(--border-lt);cursor:pointer;transition:background .12s">
              <!-- Avatar initial -->
              <div style="width:34px;height:34px;border-radius:50%;background:var(--navy);color:#fff;font-family:var(--font-head);font-weight:800;font-size:.84rem;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <?= strtoupper(substr($a['first_name'],0,1)) ?>
              </div>
              <div style="flex:1;min-width:0">
                <div style="font-weight:700;font-size:.83rem;color:var(--ink)"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></div>
                <div style="font-size:.72rem;color:var(--stone)"><?= htmlspecialchars($a['email']) ?> · <?= htmlspecialchars($a['phone']) ?></div>
              </div>
              <div style="text-align:right;flex-shrink:0">
                <span class="badge badge--<?= $a['status'] ?>" style="font-size:.6rem">
                  <span class="status-dot"></span><?= ucfirst($a['status']) ?>
                </span>
                <div style="font-size:.67rem;color:var(--stone-lt);margin-top:3px">
                  Wants: <?= htmlspecialchars(ucwords(str_replace(['house','house-'],['House ','House '],$a['preferred_residence']))) ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Selected applicant confirmation card -->
          <div id="app-selected-card" style="display:none;margin-top:14px;padding:14px;background:var(--green-dim);border:1px solid #86efac;border-radius:var(--r-md)">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--green-dk)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <span style="font-weight:700;font-size:.83rem;color:var(--green-dk)">Selected Applicant</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:.79rem;color:var(--ink-2)">
              <div><span style="color:var(--stone);font-weight:600">Name:</span> <span id="app-sel-name"></span></div>
              <div><span style="color:var(--stone);font-weight:600">Email:</span> <span id="app-sel-email"></span></div>
              <div><span style="color:var(--stone);font-weight:600">Phone:</span> <span id="app-sel-phone"></span></div>
              <div><span style="color:var(--stone);font-weight:600">Preferred:</span> <span id="app-sel-res"></span></div>
            </div>
            <button type="button" onclick="clearAppSelection()" style="margin-top:8px;background:none;border:none;font-size:.72rem;color:var(--stone);text-decoration:underline;cursor:pointer">
              Clear selection
            </button>
          </div>

          <!-- Lease details (shown after selecting applicant) -->
          <div id="app-lease-section" style="display:none;margin-top:14px">
            <p style="font-family:var(--font-head);font-size:.68rem;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--stone);margin-bottom:12px">Lease Details</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
              <div class="field">
                <label>Lease Start <span style="color:var(--red)">*</span></label>
                <input type="date" name="lease_start" id="app-lease-start" required>
              </div>
              <div class="field">
                <label>Lease End <span style="color:var(--red)">*</span></label>
                <input type="date" name="lease_end" id="app-lease-end" required>
              </div>
              <div class="field">
                <label>Monthly Rent (R)</label>
                <input type="number" name="monthly_rent" id="app-rent" step="50" min="0">
              </div>
              <div class="field">
                <label>Deposit Amount (R)</label>
                <input type="number" name="deposit_amount" id="app-deposit" step="50" min="0">
              </div>
              <div class="field" style="grid-column:1/-1">
                <label>Deposit Status</label>
                <select name="deposit_status">
                  <option value="unpaid">Unpaid — to be collected</option>
                  <option value="paid">Paid — already received</option>
                </select>
              </div>
            </div>
          </div>

        </div><!-- /modal-body -->

        <div id="app-assign-error" style="display:none;margin:0 22px 4px;padding:10px 14px;background:var(--red-dim);color:var(--red);border-radius:var(--r-sm);font-size:.8rem;border:1px solid #fca5a5"></div>

        <div class="modal-footer">
          <button type="button" class="btn btn--ghost modal-close">Cancel</button>
          <button type="submit" class="btn btn--primary" id="app-assign-submit" disabled style="opacity:.5">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Assign to Room
          </button>
        </div>
      </form>
    </div>

  </div>
</div>


<!-- ══ MOVE OUT MODAL ═══════════════════════════════════════════ -->
<div class="modal-backdrop" id="move-out-modal">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h3>Move Out Tenant</h3>
      <button class="modal-close">&times;</button>
    </div>
    <form method="POST" action="/backend/move-out.php">
      <input type="hidden" name="resident_id" id="move-out-resident-id">
      <div class="modal-body">
        <p style="margin-bottom:14px;font-size:.85rem">
          You are moving out <strong id="move-out-name"></strong> from Room <strong id="move-out-room"></strong>.
        </p>
        <div class="field" style="margin-bottom:12px">
          <label>Move-out Date</label>
          <input type="date" name="move_out_date" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="field" style="margin-bottom:12px">
          <label>Reason</label>
          <input type="text" name="reason" placeholder="e.g. Lease ended, voluntary departure…" value="Lease ended">
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


<!-- ══ ADD ROOM MODAL ═══════════════════════════════════════════ -->
<div class="modal-backdrop" id="add-room-modal">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <h3>Add Room</h3>
      <button class="modal-close">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="add_room" value="1">
      <input type="hidden" name="add_residence_id" value="<?= $residenceId ?>">
      <div class="modal-body">
        <div class="form-grid form-grid--2">
          <div class="field">
            <label>Room Number *</label>
            <input type="text" name="room_number" placeholder="e.g. 101" required>
          </div>
          <div class="field">
            <label>Floor</label>
            <input type="number" name="floor" min="1" value="1">
          </div>
          <div class="field">
            <label>Room Type</label>
            <select name="room_type_add">
              <option value="single">Single</option>
              <option value="double">Double</option>
              <option value="shared">Shared</option>
            </select>
          </div>
          <div class="field">
            <label>Monthly Price (R)</label>
            <input type="number" name="price" step="50" min="0" placeholder="3500">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn--ghost modal-close">Cancel</button>
        <button type="submit" class="btn btn--primary">Add Room</button>
      </div>
    </form>
  </div>
</div>


<script>
var currentRoomId    = 0;
var currentRoomNum   = '';
var currentRoomPrice = 0;
var selectedAppId    = null;

/* ── Open Assign Tenant Modal ─────────────────────────────── */
function openAssignModal(roomId, roomNum, roomPrice) {
  currentRoomId    = roomId;
  currentRoomNum   = roomNum;
  currentRoomPrice = roomPrice;

  document.getElementById('assign-room-label').textContent = 'Room ' + roomNum;

  // Manual tab: pre-fill room & lease dates
  document.getElementById('assign-room-id').value  = roomId;
  document.getElementById('assign-rent').value     = roomPrice;
  document.getElementById('assign-deposit').value  = roomPrice;
  var today = new Date().toISOString().slice(0,10);
  var end   = new Date(new Date().setFullYear(new Date().getFullYear()+1)).toISOString().slice(0,10);
  document.getElementById('assign-lease-start').value = today;
  document.getElementById('assign-lease-end').value   = end;
  document.getElementById('assign-error').style.display = 'none';

  // App tab: pre-fill room & lease dates
  document.getElementById('app-room-id').value    = roomId;
  document.getElementById('app-rent').value       = roomPrice;
  document.getElementById('app-deposit').value    = roomPrice;
  document.getElementById('app-lease-start').value = today;
  document.getElementById('app-lease-end').value   = end;

  clearAppSelection();
  switchAssignTab('manual');
  openModal('assign-modal');
}

/* ── Tab switching ────────────────────────────────────────── */
function switchAssignTab(tab) {
  var isManual = tab === 'manual';
  document.getElementById('tab-manual').style.display     = isManual ? '' : 'none';
  document.getElementById('tab-app').style.display        = isManual ? 'none' : '';
  document.getElementById('tab-manual-btn').style.background = isManual ? 'var(--navy)' : 'var(--surface-2)';
  document.getElementById('tab-manual-btn').style.color      = isManual ? '#fff' : 'var(--stone)';
  document.getElementById('tab-app-btn').style.background    = isManual ? 'var(--surface-2)' : 'var(--navy)';
  document.getElementById('tab-app-btn').style.color         = isManual ? 'var(--stone)' : '#fff';
  if (!isManual) {
    setTimeout(function(){ document.getElementById('app-search').focus(); }, 150);
  }
}

/* ── Filter applications list ─────────────────────────────── */
function filterApps(q) {
  q = q.toLowerCase().trim();
  document.querySelectorAll('.app-row').forEach(function(row) {
    row.style.display = (!q || row.dataset.name.includes(q)) ? '' : 'none';
  });
}

/* ── Select an application ────────────────────────────────── */
function selectApplication(el) {
  // Deselect all
  document.querySelectorAll('.app-row').forEach(function(r) {
    r.style.background = '';
  });
  // Highlight selected
  el.style.background = '#f0fdf4';
  selectedAppId = el.dataset.id;
  document.getElementById('app-application-id').value = selectedAppId;

  // Fill confirmation card
  document.getElementById('app-sel-name').textContent  = el.dataset.firstname + ' ' + el.dataset.lastname;
  document.getElementById('app-sel-email').textContent = el.dataset.email;
  document.getElementById('app-sel-phone').textContent = el.dataset.phone;
  document.getElementById('app-sel-res').textContent   = el.dataset.residence
    ? el.dataset.residence.replace('house','House ').replace('house-','House ')
    : '—';

  document.getElementById('app-selected-card').style.display  = '';
  document.getElementById('app-lease-section').style.display  = '';
  document.getElementById('app-assign-submit').disabled       = false;
  document.getElementById('app-assign-submit').style.opacity  = '1';
  document.getElementById('app-assign-error').style.display   = 'none';
}

function clearAppSelection() {
  selectedAppId = null;
  document.getElementById('app-application-id').value         = '';
  document.getElementById('app-selected-card').style.display  = 'none';
  document.getElementById('app-lease-section').style.display  = 'none';
  document.getElementById('app-assign-submit').disabled       = true;
  document.getElementById('app-assign-submit').style.opacity  = '.5';
  document.querySelectorAll('.app-row').forEach(function(r) { r.style.background = ''; });
}

/* ── Move-out modal ───────────────────────────────────────── */
function openMoveOutModal(residentId, name, roomNum) {
  document.getElementById('move-out-resident-id').value = residentId;
  document.getElementById('move-out-name').textContent  = name;
  document.getElementById('move-out-room').textContent  = roomNum;
  openModal('move-out-modal');
}

/* ── Submit MANUAL assign ─────────────────────────────────── */
document.getElementById('assign-form').addEventListener('submit', function(e) {
  e.preventDefault();
  var btn    = document.getElementById('assign-submit');
  var errBox = document.getElementById('assign-error');
  btn.disabled = true; btn.innerHTML = 'Assigning…';
  errBox.style.display = 'none';

  fetch('/backend/add-tenant.php', { method:'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(function(data) {
      if (data.success) { window.location.href = data.redirect || location.href; }
      else {
        errBox.innerHTML = (data.errors || [data.error || 'Error.']).join('<br>');
        errBox.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Assign Tenant';
      }
    })
    .catch(function() {
      errBox.innerHTML = 'Network error. Please try again.';
      errBox.style.display = 'block';
      btn.disabled = false;
      btn.innerHTML = 'Assign Tenant';
    });
});

/* ── Submit FROM APPLICATION assign ──────────────────────── */
document.getElementById('app-assign-form').addEventListener('submit', function(e) {
  e.preventDefault();
  if (!selectedAppId) return;
  var btn    = document.getElementById('app-assign-submit');
  var errBox = document.getElementById('app-assign-error');
  btn.disabled = true; btn.innerHTML = 'Assigning…';
  errBox.style.display = 'none';

  fetch('/backend/assign-application.php', { method:'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(function(data) {
      if (data.success) { window.location.href = data.redirect || location.href; }
      else {
        errBox.innerHTML = (data.errors || [data.error || 'Error.']).join('<br>');
        errBox.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Assign to Room';
      }
    })
    .catch(function() {
      errBox.innerHTML = 'Network error.';
      errBox.style.display = 'block';
      btn.disabled = false;
      btn.innerHTML = 'Assign to Room';
    });
});
</script>

<?php include __DIR__ . '/partials/foot.php'; ?>
