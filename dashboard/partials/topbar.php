<?php
$topbarTitle    = $pageTitle    ?? 'Dashboard';
$topbarSubtitle = $pageSubtitle ?? '';
?>
<header class="dash-topbar">

  <div class="topbar-title">
    <h1><?= htmlspecialchars($topbarTitle) ?></h1>
    <?php if ($topbarSubtitle): ?>
      <small><?= htmlspecialchars($topbarSubtitle) ?></small>
    <?php endif; ?>
  </div>

  <span class="topbar-date"><?= date('D · d M Y') ?></span>

  <div class="topbar-actions">
    <a href="/index.html" class="topbar-view-site" target="_blank" rel="noopener">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
        <polyline points="15 3 21 3 21 9"/>
        <line x1="10" y1="14" x2="21" y2="3"/>
      </svg>
      View Site
    </a>
    <?php if (!empty($topbarCTA)): ?>
      <a href="<?= htmlspecialchars($topbarCTA['href']) ?>" class="topbar-btn topbar-btn--primary">
        <?= htmlspecialchars($topbarCTA['label']) ?>
      </a>
    <?php endif; ?>
  </div>

</header>
