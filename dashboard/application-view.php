<?php
$pageTitle = 'Application Detail';
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';
require_once dirname(__DIR__) . '/backend/email.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: applications.php'); exit; }

$pdo  = db();
$app  = $pdo->prepare('SELECT * FROM applications WHERE id = ?');
$app->execute([$id]);
$app  = $app->fetch();
if (!$app) { header('Location: applications.php'); exit; }

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $newStatus   = $_POST['new_status'];
    $reviewNotes = trim($_POST['review_notes'] ?? '');
    $allowed = ['pending','approved','rejected','waitlisted'];
    if (in_array($newStatus, $allowed)) {
        $upd = $pdo->prepare(
          'UPDATE applications SET status=?, review_notes=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?'
        );
        $upd->execute([$newStatus, $reviewNotes ?: null, $_SESSION['admin_id'], $id]);

        $emailApp = array_merge($app, ['review_notes' => $reviewNotes]);
        if ($newStatus === 'approved') {
            send_approval_email($emailApp);
        } elseif ($newStatus === 'rejected') {
            send_rejection_email($emailApp);
        }

        header("Location: application-view.php?id=$id&msg=updated");
        exit;
    }
}

// Check if this applicant already has an active resident record
$existingResident = $pdo->prepare(
    "SELECT res.id, rm.room_number, prop.name AS block_name, res.lease_end
     FROM residents res
     JOIN rooms rm ON rm.id = res.room_id
     JOIN residences prop ON prop.id = rm.residence_id
     WHERE res.application_id = ? AND res.status = 'active'"
);
$existingResident->execute([$id]);
$existingResident = $existingResident->fetch();

// Available rooms for assignment (grouped by residence)
$availableRooms = $pdo->query(
    "SELECT rm.id, rm.room_number, rm.floor, rm.type, rm.price,
            prop.id AS res_id, prop.name AS res_name
     FROM rooms rm
     JOIN residences prop ON prop.id = rm.residence_id
     WHERE rm.status = 'available' AND prop.status = 'active'
     ORDER BY prop.name, rm.floor, rm.room_number"
)->fetchAll();

// Group by residence for the select dropdown
$roomsByResidence = [];
foreach ($availableRooms as $r) {
    $roomsByResidence[$r['res_name']][] = $r;
}

$pageTitle = 'Application — ' . $app['first_name'] . ' ' . $app['last_name'];
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <?php if (!empty($_GET['msg'])): ?>
    <div class="alert alert--success" data-auto-dismiss>Status updated successfully.</div>
  <?php endif; ?>

  <!-- Back link -->
  <a href="applications.php" class="btn btn--ghost btn--sm" style="margin-bottom:16px">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Applications
  </a>

  <div class="detail-grid">

    <!-- Left: Application data -->
    <div>
      <!-- Status bar -->
      <div class="card" style="margin-bottom:16px">
        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div>
            <span class="badge badge--<?= $app['status'] ?>" style="font-size:.8rem;padding:5px 12px">
              <span class="status-dot"></span> <?= ucfirst($app['status']) ?>
            </span>
            <span class="text-muted text-sm" style="margin-left:10px">
              Application #<?= $app['id'] ?> &mdash; Submitted <?= date('d M Y, H:i', strtotime($app['submitted_at'])) ?>
            </span>
          </div>
          <button onclick="openModal('status-modal')" class="btn btn--amber">Change Status</button>
        </div>
      </div>

      <!-- Personal Details -->
      <div class="detail-section">
        <div class="detail-section__head">Personal Details</div>
        <div class="detail-row"><span class="detail-row__key">Full Name</span><span class="detail-row__val"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></span></div>
        <div class="detail-row"><span class="detail-row__key">Email</span><span class="detail-row__val"><a href="mailto:<?= htmlspecialchars($app['email']) ?>" style="color:var(--clr-navy)"><?= htmlspecialchars($app['email']) ?></a></span></div>
        <div class="detail-row"><span class="detail-row__key">Phone</span><span class="detail-row__val"><?= htmlspecialchars($app['phone']) ?></span></div>
        <div class="detail-row"><span class="detail-row__key">Date of Birth</span><span class="detail-row__val"><?= $app['dob'] ? date('d M Y', strtotime($app['dob'])) : '—' ?></span></div>
        <div class="detail-row"><span class="detail-row__key">Gender</span><span class="detail-row__val"><?= ucfirst(htmlspecialchars($app['gender'])) ?></span></div>
      </div>

      <!-- Background Details -->
      <div class="detail-section">
        <div class="detail-section__head">Background Details</div>
        <div class="detail-row"><span class="detail-row__key">Occupation</span><span class="detail-row__val"><?= ucwords(str_replace('-',' ', htmlspecialchars($app['occupation']))) ?></span></div>
        <div class="detail-row"><span class="detail-row__key">Employer / Institution</span><span class="detail-row__val"><?= htmlspecialchars($app['employer'] ?: '—') ?></span></div>
        <div class="detail-row"><span class="detail-row__key">Funding Source</span><span class="detail-row__val"><?= ucwords(str_replace('-',' ', htmlspecialchars($app['funding_source']))) ?></span></div>
      </div>

      <!-- Accommodation Preferences -->
      <div class="detail-section">
        <div class="detail-section__head">Accommodation Preferences</div>
        <div class="detail-row"><span class="detail-row__key">Preferred Residence</span><span class="detail-row__val"><?= htmlspecialchars(str_replace(['house','house-'],['House ','House '],$app['preferred_residence'])) ?></span></div>
        <div class="detail-row"><span class="detail-row__key">Room Type</span><span class="detail-row__val"><?= ucfirst(htmlspecialchars($app['room_type'])) ?></span></div>
        <div class="detail-row"><span class="detail-row__key">Preferred Move-in</span><span class="detail-row__val"><?= $app['move_in_date'] ? date('d M Y', strtotime($app['move_in_date'])) : '—' ?></span></div>
        <div class="detail-row"><span class="detail-row__key">Notes</span><span class="detail-row__val"><?= htmlspecialchars($app['notes'] ?: 'None') ?></span></div>
      </div>

      <!-- Supporting Documents -->
      <?php
        $docs = [
          ['label' => 'SA ID / Passport',        'path' => $app['doc_id_path'],          'always' => true],
          ['label' => 'Proof of Enrollment',      'path' => $app['doc_enrollment_path'],  'always' => $app['occupation'] === 'student'],
          ['label' => 'Bursary Award Letter',     'path' => $app['doc_bursary_path'],     'always' => $app['funding_source'] === 'bursary'],
          ['label' => 'Sponsorship Letter',       'path' => $app['doc_sponsorship_path'], 'always' => $app['funding_source'] === 'employer'],
          ['label' => 'Bank Statement / Payslip', 'path' => $app['doc_income_path'],      'always' => !in_array($app['funding_source'], ['bursary','employer'])],
        ];
        $relevantDocs = array_filter($docs, fn($d) => $d['always']);
        $anyDoc = array_filter($relevantDocs, fn($d) => !empty($d['path']));
      ?>
      <div class="detail-section" style="margin-top:0">
        <div class="detail-section__head" style="display:flex;align-items:center;justify-content:space-between">
          Supporting Documents
          <?php if (!$anyDoc): ?>
            <span style="font-size:.72rem;font-weight:400;color:var(--text-muted);text-transform:none;letter-spacing:0">No documents uploaded</span>
          <?php endif; ?>
        </div>

        <?php foreach ($relevantDocs as $doc): ?>
        <div class="detail-row" style="align-items:center">
          <span class="detail-row__key" style="flex-shrink:0"><?= htmlspecialchars($doc['label']) ?></span>
          <span class="detail-row__val">
            <?php if (!empty($doc['path'])): ?>
              <?php
                $ext      = strtolower(pathinfo($doc['path'], PATHINFO_EXTENSION));
                $isPdf    = $ext === 'pdf';
                $fileUrl  = '/dashboard/document.php?file=' . urlencode(str_replace('\\', '/', $doc['path']));
                $filename = basename($doc['path']);
              ?>
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:.78rem;color:var(--text-muted)">
                  <?php if ($isPdf): ?>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    PDF
                  <?php else: ?>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    Image
                  <?php endif; ?>
                </span>
                <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" rel="noopener"
                   class="btn btn--ghost btn--sm" style="font-size:.72rem;padding:4px 10px;gap:4px">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  View
                </a>
                <a href="<?= htmlspecialchars($fileUrl) ?>" download="<?= htmlspecialchars($filename) ?>"
                   class="btn btn--ghost btn--sm" style="font-size:.72rem;padding:4px 10px;gap:4px">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  Download
                </a>
              </div>
            <?php else: ?>
              <span style="display:inline-flex;align-items:center;gap:5px;font-size:.78rem;color:#9CA3AF">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Not submitted
              </span>
            <?php endif; ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Right: Actions & review -->
    <div>
      <!-- Quick Actions -->
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Quick Actions</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="new_status" value="approved">
            <button type="submit" class="btn btn--primary w-full" <?= $app['status']==='approved' ? 'disabled style="opacity:.5"' : '' ?>>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
              Approve Application
            </button>
          </form>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="new_status" value="waitlisted">
            <button type="submit" class="btn btn--amber w-full" <?= $app['status']==='waitlisted' ? 'disabled style="opacity:.5"' : '' ?>>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Waitlist
            </button>
          </form>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="new_status" value="rejected">
            <button type="submit" class="btn btn--danger w-full" <?= $app['status']==='rejected' ? 'disabled style="opacity:.5"' : '' ?>>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              Reject Application
            </button>
          </form>
        </div>
      </div>

      <!-- Review Notes -->
      <?php if ($app['review_notes']): ?>
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Review Notes</h3></div>
        <div class="card-body">
          <p class="text-sm"><?= nl2br(htmlspecialchars($app['review_notes'])) ?></p>
          <?php if ($app['reviewed_at']): ?>
          <p class="text-muted text-xs mt-2">Reviewed on <?= date('d M Y', strtotime($app['reviewed_at'])) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── ASSIGN TO ROOM CARD ──────────────────────────── -->
      <?php if (!$existingResident && $app['status'] !== 'rejected'): ?>
      <div class="card" style="margin-bottom:16px;border:2px solid var(--orange);border-radius:var(--r-md)">
        <div class="card-header" style="background:linear-gradient(90deg,rgba(254,102,0,.08),transparent)">
          <h3 style="color:var(--orange)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:5px"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Assign to Room
          </h3>
        </div>
        <div class="card-body">
          <?php if (!$availableRooms): ?>
            <p class="text-muted text-sm">No rooms available at the moment. <a href="rooms.php" style="color:var(--orange)">View rooms →</a></p>
          <?php else: ?>
          <form id="assign-app-form">
            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

            <div class="field" style="margin-bottom:12px">
              <label>Select Room <span style="color:var(--red)">*</span></label>
              <select name="room_id" id="assign-room-select" required
                style="padding:9px 12px;border:1px solid var(--border);border-radius:var(--r-sm);font-family:var(--font-body);font-size:.84rem;background:var(--surface);width:100%;outline:none"
                onchange="prefillRentFromRoom(this)">
                <option value="">— Choose a room —</option>
                <?php foreach ($roomsByResidence as $resName => $resRooms): ?>
                <optgroup label="<?= htmlspecialchars($resName) ?>">
                  <?php foreach ($resRooms as $rm): ?>
                  <option value="<?= $rm['id'] ?>"
                          data-price="<?= $rm['price'] ?>"
                          <?= strpos(strtolower($app['preferred_residence']), strtolower(preg_replace('/[^0-9]/','',$resName))) !== false ? 'style="font-weight:700"' : '' ?>>
                    Room <?= htmlspecialchars($rm['room_number']) ?> · Floor <?= $rm['floor'] ?>
                    · <?= ucfirst($rm['type']) ?> · R<?= number_format($rm['price'],0) ?>/mo
                    <?= strpos(strtolower($app['preferred_residence']), strtolower(preg_replace('/[^0-9]/','',$resName))) !== false ? ' ⭐ Preferred' : '' ?>
                  </option>
                  <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
              </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
              <div class="field">
                <label>Lease Start <span style="color:var(--red)">*</span></label>
                <input type="date" name="lease_start" id="av-lease-start" required
                  value="<?= $app['move_in_date'] ?? date('Y-m-d') ?>"
                  style="padding:8px 11px;border:1px solid var(--border);border-radius:var(--r-sm);font-family:var(--font-body);font-size:.84rem;background:var(--surface);width:100%;outline:none">
              </div>
              <div class="field">
                <label>Lease End <span style="color:var(--red)">*</span></label>
                <input type="date" name="lease_end" id="av-lease-end" required
                  value="<?= date('Y-m-d', strtotime(($app['move_in_date'] ?? date('Y-m-d')) . ' +12 months')) ?>"
                  style="padding:8px 11px;border:1px solid var(--border);border-radius:var(--r-sm);font-family:var(--font-body);font-size:.84rem;background:var(--surface);width:100%;outline:none">
              </div>
              <div class="field">
                <label>Monthly Rent (R)</label>
                <input type="number" name="monthly_rent" id="av-rent" step="50" min="0"
                  style="padding:8px 11px;border:1px solid var(--border);border-radius:var(--r-sm);font-family:var(--font-body);font-size:.84rem;background:var(--surface);width:100%;outline:none">
              </div>
              <div class="field">
                <label>Deposit Status</label>
                <select name="deposit_status"
                  style="padding:8px 11px;border:1px solid var(--border);border-radius:var(--r-sm);font-family:var(--font-body);font-size:.84rem;background:var(--surface);width:100%;outline:none">
                  <option value="unpaid">Unpaid</option>
                  <option value="paid">Paid</option>
                </select>
              </div>
            </div>

            <div id="av-error" style="display:none;padding:9px 12px;background:var(--red-dim);color:var(--red);border-radius:var(--r-sm);font-size:.78rem;margin-bottom:10px;border:1px solid #fca5a5"></div>

            <button type="submit" class="btn btn--primary w-full" id="av-submit">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
              Assign <?= htmlspecialchars($app['first_name']) ?> to Room
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <?php elseif ($existingResident): ?>
      <!-- Already assigned -->
      <div class="card" style="margin-bottom:16px;border:1px solid #86efac">
        <div class="card-body" style="background:#f0fdf4;border-radius:var(--r-md)">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--green-dk)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span style="font-weight:700;font-size:.84rem;color:var(--green-dk)">Currently Assigned</span>
          </div>
          <p class="text-sm">
            Room <strong><?= htmlspecialchars($existingResident['room_number']) ?></strong>
            at <strong><?= htmlspecialchars($existingResident['block_name']) ?></strong>.
            Lease ends <strong><?= $existingResident['lease_end'] ? date('d M Y', strtotime($existingResident['lease_end'])) : '—' ?></strong>.
          </p>
          <a href="residents.php" class="btn btn--ghost btn--sm" style="margin-top:10px">View All Tenants</a>
        </div>
      </div>
      <?php endif; ?>

      <!-- Email applicant shortcut -->
      <div class="card">
        <div class="card-header"><h3>Contact Applicant</h3></div>
        <div class="card-body">
          <p class="text-muted text-sm" style="margin-bottom:12px">Send an email directly to the applicant regarding their application.</p>
          <a href="mailto:<?= htmlspecialchars($app['email']) ?>?subject=Your+Hilret+Trust+Application+%23<?= $app['id'] ?>" class="btn btn--navy w-full">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Email <?= htmlspecialchars($app['first_name']) ?>
          </a>
        </div>
      </div>
    </div>
  </div>

</main>

<!-- Status Change Modal -->
<div class="modal-backdrop" id="status-modal">
  <div class="modal">
    <div class="modal-header">
      <h3>Change Application Status</h3>
      <button class="modal-close">&times;</button>
    </div>
    <form method="POST">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="field" style="margin-bottom:14px">
          <label>New Status</label>
          <select name="new_status" class="field select">
            <option value="pending"    <?= $app['status']==='pending'    ? 'selected':'' ?>>Pending</option>
            <option value="approved"   <?= $app['status']==='approved'   ? 'selected':'' ?>>Approved</option>
            <option value="waitlisted" <?= $app['status']==='waitlisted' ? 'selected':'' ?>>Waitlisted</option>
            <option value="rejected"   <?= $app['status']==='rejected'   ? 'selected':'' ?>>Rejected</option>
          </select>
        </div>
        <div class="field">
          <label>Review Notes <span class="text-muted">(optional)</span></label>
          <textarea name="review_notes" rows="4" placeholder="Add any notes about this decision…"><?= htmlspecialchars($app['review_notes'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn--ghost modal-close">Cancel</button>
        <button type="submit" class="btn btn--navy">Save Status</button>
      </div>
    </form>
  </div>
</div>

<script>
/* Pre-fill rent when a room is selected */
function prefillRentFromRoom(sel) {
  var price = sel.options[sel.selectedIndex].dataset.price || 0;
  var rentEl = document.getElementById('av-rent');
  if (rentEl && price) rentEl.value = price;
}

/* Submit assign-from-application-view form */
var avForm = document.getElementById('assign-app-form');
if (avForm) {
  avForm.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn    = document.getElementById('av-submit');
    var errBox = document.getElementById('av-error');
    btn.disabled = true; btn.textContent = 'Assigning…';
    errBox.style.display = 'none';

    // Auto-fill deposit amount from rent if empty
    var rentVal = document.getElementById('av-rent').value;
    var depField = avForm.querySelector('[name="deposit_amount"]');
    if (!depField) {
      var depInput = document.createElement('input');
      depInput.type = 'hidden'; depInput.name = 'deposit_amount'; depInput.value = rentVal;
      avForm.appendChild(depInput);
    }

    fetch('/backend/assign-application.php', { method: 'POST', body: new FormData(avForm) })
      .then(r => r.json())
      .then(function(data) {
        if (data.success) {
          window.location.href = data.redirect || '/dashboard/residents.php';
        } else {
          errBox.innerHTML = (data.errors || [data.error || 'Error.']).join('<br>');
          errBox.style.display = 'block';
          btn.disabled = false;
          btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Assign to Room';
        }
      })
      .catch(function() {
        errBox.innerHTML = 'Network error. Please try again.';
        errBox.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Assign to Room';
      });
  });
}
</script>

<?php include __DIR__ . '/partials/foot.php'; ?>
