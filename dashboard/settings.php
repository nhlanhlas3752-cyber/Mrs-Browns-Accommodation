<?php
$pageTitle    = 'Settings';
$pageSubtitle = 'Account and system configuration';
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo     = db();
$success = '';
$errors  = [];

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $row = $pdo->prepare('SELECT password_hash FROM users WHERE id=?');
    $row->execute([$user['id']]);
    $row = $row->fetch();

    if (!password_verify($current, $row['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $user['id']]);
        $success = 'Password changed successfully.';
    }
}

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Name and valid email are required.';
    } else {
        $pdo->prepare('UPDATE users SET name=?, email=? WHERE id=?')
            ->execute([$name, $email, $user['id']]);
        $success = 'Profile updated.';
        // Refresh user data
        $user['name']  = $name;
        $user['email'] = $email;
    }
}
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">

  <?php if ($success): ?>
    <div class="alert alert--success" data-auto-dismiss><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php foreach ($errors as $e): ?>
    <div class="alert alert--error"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:900px">

    <!-- Profile Settings -->
    <div class="card">
      <div class="card-header"><h3>Profile</h3></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="update_profile" value="1">
          <div class="field" style="margin-bottom:12px">
            <label>Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
          </div>
          <div class="field" style="margin-bottom:12px">
            <label>Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
          </div>
          <div class="field" style="margin-bottom:16px">
            <label>Role</label>
            <input type="text" value="<?= ucfirst(htmlspecialchars($user['role'])) ?>" disabled style="opacity:.6">
          </div>
          <button type="submit" class="btn btn--primary">Save Profile</button>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card">
      <div class="card-header"><h3>Change Password</h3></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="change_password" value="1">
          <div class="field" style="margin-bottom:12px">
            <label>Current Password</label>
            <input type="password" name="current_password" required autocomplete="current-password">
          </div>
          <div class="field" style="margin-bottom:12px">
            <label>New Password</label>
            <input type="password" name="new_password" required autocomplete="new-password" placeholder="Min. 8 characters">
          </div>
          <div class="field" style="margin-bottom:16px">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn--navy">Update Password</button>
        </form>
      </div>
    </div>

    <!-- System Info -->
    <div class="card">
      <div class="card-header"><h3>System Information</h3></div>
      <div class="card-body" style="font-size:.83rem">
        <div class="detail-row" style="padding:8px 0"><span class="detail-row__key">PHP Version</span><span class="detail-row__val"><?= PHP_VERSION ?></span></div>
        <div class="detail-row" style="padding:8px 0"><span class="detail-row__key">Database</span><span class="detail-row__val">MySQL (hilret_trust)</span></div>
        <div class="detail-row" style="padding:8px 0"><span class="detail-row__key">Server</span><span class="detail-row__val"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache/XAMPP') ?></span></div>
        <div class="detail-row" style="padding:8px 0"><span class="detail-row__key">Logged in as</span><span class="detail-row__val"><?= htmlspecialchars($user['email']) ?></span></div>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="card">
      <div class="card-header"><h3>Quick Links</h3></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <a href="/index.html" target="_blank" class="btn btn--ghost">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          View Public Website
        </a>
        <a href="/apply.html" target="_blank" class="btn btn--ghost">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          View Apply Form
        </a>
        <a href="/phpmyadmin" target="_blank" class="btn btn--ghost">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
          Open phpMyAdmin
        </a>
        <a href="logout.php" class="btn btn--danger">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Sign Out
        </a>
      </div>
    </div>

  </div>
</main>
<?php include __DIR__ . '/partials/foot.php'; ?>
