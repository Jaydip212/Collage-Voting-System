<?php
// includes/dashboard_header.php – Reusable sidebar layout header
// Variables expected: $pageTitle (string), $activeMenu (string), $roleLabel (string), $sidebarLinks (array)
// Each sidebarLink: ['icon'=>'...', 'label'=>'...', 'href'=>'...', 'key'=>'...', 'badge'=>null]

if (!isset($pageTitle)) $pageTitle = 'Dashboard';
if (!isset($roleLabel)) $roleLabel = ucfirst($_SESSION['user_role'] ?? 'User');
$userName  = $_SESSION['user_name']  ?? 'User';
$userPhoto = $_SESSION['user_photo'] ?? 'default.png';
$userRole  = $_SESSION['user_role']  ?? 'student';
$deptName  = $_SESSION['dept_name']  ?? '';
$photoUrl  = BASE_URL . '/uploads/profiles/' . $userPhoto;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> – <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/premium.css">
<style>
/* Sidebar overlay for mobile */
#sidebarOverlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.5);
  z-index: 499;
  backdrop-filter: blur(2px);
}
.notif-dropdown {
  position: absolute; top: 54px; right: 0;
  width: 320px;
  background: var(--bg-dark);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-lg);
  z-index: 1000;
  overflow: hidden;
}
.notif-item {
  padding: 14px 16px;
  border-bottom: 1px solid var(--border);
  display: flex; gap: 12px; align-items: flex-start;
  transition: background var(--t-fast);
}
.notif-item:hover { background: var(--bg-glass); }
.notif-item:last-child { border-bottom: none; }
.notif-dot { width:10px;height:10px;border-radius:50%;margin-top:5px;flex-shrink:0; }
</style>
</head>
<body>

<!-- SIDEBAR OVERLAY (mobile) -->
<div id="sidebarOverlay" class="d-none"></div>

<div class="layout-wrapper">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo" title="<?= SITE_NAME ?>">🗳️</div>
    <div>
      <div class="sidebar-title"><?= SITE_SHORT_NAME ?></div>
      <div class="sidebar-subtitle">Voting System</div>
    </div>
  </div>

  <div class="sidebar-user">
    <img src="<?= $photoUrl ?>" alt="<?= htmlspecialchars($userName) ?>" class="sidebar-user-avatar"
         onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
    <div style="overflow:hidden">
      <div class="sidebar-user-name"><?= htmlspecialchars($userName) ?></div>
      <div class="sidebar-user-role"><?= $roleLabel ?></div>
      <?php if ($deptName): ?>
        <div style="font-size:0.68rem;color:var(--text-muted)"><?= htmlspecialchars($deptName) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php foreach ($sidebarLinks as $group): ?>
      <?php if (isset($group['section'])): ?>
        <div class="sidebar-section-label"><?= $group['section'] ?></div>
      <?php else: ?>
        <a href="<?= $group['href'] ?>"
           class="sidebar-link <?= ($activeMenu ?? '') === $group['key'] ? 'active' : '' ?>">
          <span class="icon"><i class="<?= $group['icon'] ?>"></i></span>
          <span><?= $group['label'] ?></span>
          <?php if (!empty($group['badge'])): ?>
            <span class="badge"><?= $group['badge'] ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>/logout.php" class="sidebar-link" data-confirm="Are you sure you want to logout?">
      <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
      <span>Logout</span>
    </a>
  </div>
</aside>
<!-- END SIDEBAR -->

<!-- MAIN CONTENT -->
<main class="main-content">

  <!-- TOPBAR -->
  <header class="topbar">
    <button id="sidebarToggle" class="btn btn-icon btn-outline" style="display:none" aria-label="Toggle sidebar">
      <i class="fas fa-bars"></i>
    </button>
    <h1 class="topbar-title" style="font-size:1rem"><?= htmlspecialchars($pageTitle) ?></h1>

    <div class="topbar-actions">
      <!-- Language -->
      <button id="langToggle" class="btn btn-sm btn-outline" style="font-size:0.76rem;padding:6px 12px">मराठी</button>

      <!-- Dark Mode -->
      <button class="theme-toggle" id="themeToggle" title="Toggle theme" aria-label="Toggle theme"></button>

      <!-- Notifications -->
      <div class="notif-bell" style="position:relative" id="notifContainer">
        <button class="btn btn-icon btn-outline" id="notifBtn" aria-label="Notifications">
          <i class="fas fa-bell"></i>
          <?php
          $notifs = getUnreadNotifications($pdo, $userRole, $_SESSION['user_id']);
          if (count($notifs)): ?>
            <span class="dot" style="position:absolute;top:-3px;right:-3px;width:10px;height:10px;background:#ef4444;border-radius:50%;border:2px solid var(--bg-dark)"></span>
          <?php endif; ?>
        </button>
        <div id="notifDropdown" class="notif-dropdown d-none">
          <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-weight:700;font-size:0.9rem">Notifications</div>
          <?php if (empty($notifs)): ?>
            <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:0.85rem">No new notifications</div>
          <?php else: ?>
            <?php foreach ($notifs as $n): ?>
              <div class="notif-item">
                <div class="notif-dot" style="background:<?= ['info'=>'#3b82f6','success'=>'#10b981','warning'=>'#f59e0b','danger'=>'#ef4444'][$n['type']] ?? '#3b82f6' ?>"></div>
                <div>
                  <div style="font-weight:600;font-size:0.85rem"><?= htmlspecialchars($n['title']) ?></div>
                  <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($n['message']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Profile -->
      <a href="<?= BASE_URL ?>/<?= $userRole ?>/profile.php" style="display:flex;align-items:center;gap:8px;text-decoration:none">
        <img src="<?= $photoUrl ?>" alt="Profile" class="avatar-sm"
             onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
      </a>
    </div>
  </header>
  <!-- END TOPBAR -->

  <!-- FLASH MESSAGES -->
  <div style="padding: 0 28px">
    <?= showFlashes() ?>
  </div>

  <!-- PAGE CONTENT WRAPPER -->
  <div class="page-content">
