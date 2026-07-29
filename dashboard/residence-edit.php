<?php
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$pdo = db();
$id  = (int)($_GET['id'] ?? 0);
$res = null;

if ($id) {
    $s = $pdo->prepare('SELECT * FROM residences WHERE id=?');
    $s->execute([$id]);
    $res = $s->fetch();
    if (!$res) { header('Location: residences.php'); exit; }
}

$pageTitle = $id ? 'Edit ' . ($res['name'] ?? 'Residence') : 'Add Residence';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = trim($_POST['name']        ?? '');
    $address        = trim($_POST['address']      ?? '');
    $region         = trim($_POST['region']       ?? 'Botanic Grove');
    $price_from     = (float)($_POST['price_from'] ?? 0);
    $email          = trim($_POST['email']        ?? '');
    $phone          = trim($_POST['phone']        ?? '');
    $description    = trim($_POST['description']  ?? '');
    $status         = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';
    $slug           = $id ? ($res['slug'] ?? '') : strtolower(str_replace(' ','-',$name));

    if (!$name) $errors[] = 'Name is required.';

    if (!$errors) {
        if ($id) {
            $pdo->prepare(
              'UPDATE residences SET name=?,address=?,region=?,price_from=?,email=?,phone=?,description=?,status=? WHERE id=?'
            )->execute([$name,$address,$region,$price_from,$email,$phone,$description,$status,$id]);
        } else {
            $pdo->prepare(
              'INSERT INTO residences (name,slug,address,region,price_from,email,phone,description,status) VALUES (?,?,?,?,?,?,?,?,?)'
            )->execute([$name,$slug,$address,$region,$price_from,$email,$phone,$description,$status]);
        }
        header('Location: residences.php?msg=updated');
        exit;
    }
}
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/partials/topbar.php'; ?>

<main class="dash-content">
  <a href="residences.php" class="btn btn--ghost btn--sm" style="margin-bottom:16px">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Residences
  </a>

  <?php foreach ($errors as $e): ?>
    <div class="alert alert--error"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div class="card" style="max-width:700px">
    <div class="card-header"><h3><?= $pageTitle ?></h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-grid form-grid--2" style="gap:16px">
          <div class="field">
            <label>Residence Name *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $res['name'] ?? '') ?>" required placeholder="e.g. House 29">
          </div>
          <div class="field">
            <label>Status</label>
            <select name="status">
              <option value="active"   <?= ($res['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= ($res['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
          <div class="field field--full">
            <label>Street Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($_POST['address'] ?? $res['address'] ?? '') ?>" placeholder="e.g. 29 Botanic Grove, Berea, Durban">
          </div>
          <div class="field">
            <label>Region</label>
            <input type="text" name="region" value="<?= htmlspecialchars($_POST['region'] ?? $res['region'] ?? 'Botanic Grove') ?>">
          </div>
          <div class="field">
            <label>Monthly Price From (R)</label>
            <input type="number" name="price_from" step="50" min="0" value="<?= htmlspecialchars($_POST['price_from'] ?? $res['price_from'] ?? '') ?>" placeholder="3500">
          </div>
          <div class="field">
            <label>Contact Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $res['email'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Contact Phone</label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? $res['phone'] ?? '') ?>">
          </div>
          <div class="field field--full">
            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Brief description of the residence…"><?= htmlspecialchars($_POST['description'] ?? $res['description'] ?? '') ?></textarea>
          </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:10px">
          <button type="submit" class="btn btn--primary"><?= $id ? 'Save Changes' : 'Create Residence' ?></button>
          <a href="residences.php" class="btn btn--ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</main>
<?php include __DIR__ . '/partials/foot.php'; ?>
