<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function navLink(string $href, string $icon, string $label, string $current, int $badge = 0): string {
    $file   = basename($href);
    $active = ($file === $current) ? ' active' : '';
    $badgeHtml = $badge > 0
        ? '<span class="sidebar-badge">' . $badge . '</span>'
        : '';
    return '<a href="/dashboard/' . $href . '" class="sidebar-nav__link' . $active . '">'
         . '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $icon . '</svg>'
         . $label
         . $badgeHtml
         . '</a>';
}

// Fetch badge counts
use function db as db;
$pendingApps   = 0;
$newEnquiries  = 0;
try {
    require_once dirname(__DIR__, 2) . '/backend/db.php';
    $pendingApps  = (int) db()->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
    $newEnquiries = (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE status='new'")->fetchColumn();
} catch (Exception $e) {}

$initials = strtoupper(substr($user['name'] ?? 'A', 0, 1));
?>
<aside class="dash-sidebar">

  <div class="sidebar-logo">
    <a href="/index.html" class="sidebar-site-logo" aria-label="Hilret Trust — Go to website">
      <div class="sidebar-logo-mark">
        <span class="sidebar-logo-mark__hilret">Hilret</span>
        <span class="sidebar-logo-mark__trust">Trust</span>
        <svg class="sidebar-logo-mark__swoosh" viewBox="0 0 110 10" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M3 7.5 Q27.5 2 55 5.5 Q82.5 9 107 4" stroke="#FE6600" stroke-width="2.5" fill="none" stroke-linecap="round"/>
        </svg>
      </div>
    </a>
    <span class="sidebar-logo__badge">Admin Panel</span>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-nav__label">Main</div>
    <?= navLink('overview.php',
        '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
        'Overview', $currentPage) ?>

    <?= navLink('applications.php',
        '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
        'Applications', $currentPage, $pendingApps) ?>

    <div class="sidebar-nav__label">Properties</div>
    <?= navLink('residences.php',
        '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'Residences', $currentPage) ?>

    <?= navLink('rooms.php',
        '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'Rooms', $currentPage) ?>

    <?= navLink('availability.php',
        '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'Availability', $currentPage) ?>

    <?= navLink('leases.php',
        '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/>',
        'Leases', $currentPage) ?>

    <div class="sidebar-nav__label">People</div>
    <?= navLink('residents.php',
        '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'Tenants', $currentPage) ?>

    <?= navLink('enquiries.php',
        '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'Enquiries', $currentPage, $newEnquiries) ?>

    <div class="sidebar-nav__label">Content</div>
    <?= navLink('faqs.php',
        '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'FAQs', $currentPage) ?>

    <?= navLink('signup.php',
        '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>',
        'Create User', $currentPage) ?>

    <?= navLink('settings.php',
        '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'Settings', $currentPage) ?>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-user__avatar"><?= htmlspecialchars($initials) ?></div>
      <div class="sidebar-user__info">
        <div class="sidebar-user__name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
        <div class="sidebar-user__role"><?= htmlspecialchars($user['role'] ?? '') ?></div>
      </div>
      <a href="/dashboard/logout.php" class="sidebar-logout" title="Log out">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
      </a>
    </div>
  </div>

</aside>
