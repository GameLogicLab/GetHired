<?php
require_once __DIR__ . '/auth_check.php';
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="<?= is_logged_in() ? 'has-sidebar' : '' ?> <?= strpos($_SERVER['PHP_SELF'], 'messages.php') !== false ? 'messages-page' : '' ?>">

<?php if (is_logged_in()): ?>
<nav class="navbar navbar-expand-lg top-navbar fixed-top">
  <div class="container-fluid px-4">

    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-icon sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-list fs-5"></i>
      </button>
      <a class="navbar-brand d-none d-lg-block" href="<?= BASE_URL ?>">
        <?= APP_NAME ?>
      </a>
    </div>

    <!-- Page title on mobile -->
    <span class="fw-600 d-lg-none" style="font-size:.95rem;color:var(--ink)">
      <?= e($pageTitle) ?>
    </span>

    <div class="d-flex align-items-center gap-2 ms-auto">
      <!-- Role badge -->
      <span class="badge role-badge <?= current_role() === 'freelancer' ? 'bg-success' : 'bg-primary' ?> d-none d-sm-inline-flex">
        <?= ucfirst(current_role()) ?>
      </span>

      <!-- User dropdown -->
      <div class="dropdown">
        <button class="btn btn-icon dropdown-toggle no-caret d-flex align-items-center gap-2" data-bs-toggle="dropdown">
          <?php
          $avatar = $_SESSION['avatar'] ?? null;
          if ($avatar && file_exists(UPLOAD_DIR . $avatar)): ?>
            <img src="<?= UPLOAD_URL . e($avatar) ?>" class="avatar-sm" alt="Avatar">
          <?php else: ?>
            <div class="avatar-placeholder">
              <?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?>
            </div>
          <?php endif; ?>
          <span class="fw-600 d-none d-md-inline" style="font-size:.85rem;color:var(--ink-2)">
            <?= e(explode(' ', $_SESSION['full_name'] ?? 'User')[0]) ?>
          </span>
          <i class="bi bi-chevron-down" style="font-size:.65rem;color:var(--ink-4)"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li class="dropdown-header">
            <div class="fw-700" style="color:var(--ink)"><?= e($_SESSION['full_name'] ?? '') ?></div>
            <div style="font-size:.77rem;color:var(--ink-3)"><?= ucfirst(current_role()) ?> account</div>
          </li>
          <li><hr class="dropdown-divider"></li>
          <?php if (current_role() === 'freelancer'): ?>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/freelancer/profile.php">
              <i class="bi bi-person me-2 text-primary"></i>My Profile</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/freelancer/dashboard.php">
              <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</a></li>
          <?php else: ?>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/client/dashboard.php">
              <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</a></li>
          <?php endif; ?>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
            <i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
        </ul>
      </div>
    </div>

  </div>
</nav>
<?php endif; ?>

<div id="loadingOverlay" class="loading-overlay d-none">
  <div class="spinner-border text-primary" role="status">
    <span class="visually-hidden">Loading…</span>
  </div>
</div>
