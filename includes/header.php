<?php
// includes/header.php – Reusable page header (non-dashboard)
if (!isset($pageTitle)) $pageTitle = SITE_NAME;
if (!isset($pageDesc))  $pageDesc  = SITE_TAGLINE;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<title><?= htmlspecialchars($pageTitle) ?> – <?= SITE_NAME ?></title>
<link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.ico" type="image/x-icon">
<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- Main CSS -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/premium.css">
</head>
<body>
<!-- Premium Particle Background -->
<canvas id="particles-canvas"></canvas>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">
    <img src="<?= BASE_URL ?>/assets/images/college-logo.png" alt="Logo" class="navbar-logo" onerror="this.style.display='none'">
    <div class="navbar-brand-text">
      <span class="navbar-brand-name"><?= SITE_NAME ?></span>
      <span class="navbar-brand-tagline"><?= SITE_TAGLINE ?></span>
    </div>
  </a>

  <div class="navbar-links">
    <a href="<?= BASE_URL ?>/index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a>
    <a href="<?= BASE_URL ?>/about.php" class="<?= basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : '' ?>"><i class="fas fa-info-circle"></i> About</a>
    <a href="<?= BASE_URL ?>/contact.php" class="<?= basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Contact</a>
  </div>

  <div class="navbar-actions">
    <button class="theme-toggle" id="themeToggle" title="Toggle dark/light mode" aria-label="Toggle theme"></button>
    <button class="btn btn-sm" id="langToggle" style="padding:7px 14px; font-size:0.78rem;">मराठी</button>
    <?php if (isLoggedIn()): ?>
      <?php $r = getCurrentUser()['role']; $dashUrl = BASE_URL . "/{$r}/index.php"; ?>
      <a href="<?= $dashUrl ?>" class="btn btn-outline btn-sm"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= BASE_URL ?>/logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline btn-sm"><i class="fas fa-sign-in-alt"></i> Login</a>
      <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
  </div>
</nav>
<!-- END NAVBAR -->
