<?php
/**
 * Client Sidebar Navigation — Enhanced
 */
$activePage = $activePage ?? '';
$base = BASE_URL . '/client';
$userName = $_SESSION['full_name'] ?? 'User';
$avatar = $_SESSION['avatar'] ?? null;
?>
<aside class="sidebar" id="mainSidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="sidebar-brand-name">
      <span class="b-dot"></span><?= APP_NAME ?>
    </div>
  </div>

  <!-- User -->
  <div class="sidebar-user">
    <div class="sidebar-user-avatar">
      <?php if ($avatar && file_exists(UPLOAD_DIR . $avatar)): ?>
        <img src="<?= UPLOAD_URL . e($avatar) ?>" alt="Avatar">
      <?php else: ?>
        <?= strtoupper(substr($userName, 0, 1)) ?>
      <?php endif; ?>
    </div>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?= e($userName) ?></div>
      <div class="sidebar-user-role">Client</div>
    </div>
  </div>

  <!-- Nav -->
  <div class="sidebar-scroll">
    <nav>
      <span class="sidebar-section-label">Main</span>

      <a href="<?= $base ?>/dashboard.php"
         class="sidebar-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
      </a>

      <a href="<?= $base ?>/post_job.php"
         class="sidebar-link <?= $activePage === 'post_job' ? 'active' : '' ?>">
        <i class="bi bi-plus-circle"></i>
        <span>Post a Job</span>
      </a>

      <a href="<?= $base ?>/my_jobs.php"
         class="sidebar-link <?= $activePage === 'my_jobs' ? 'active' : '' ?>">
        <i class="bi bi-collection"></i>
        <span>My Jobs</span>
      </a>

      <a href="<?= $base ?>/profile.php"
         class="sidebar-link <?= $activePage === 'profile' ? 'active' : '' ?>">
        <i class="bi bi-person-circle"></i>
        <span>Profile</span>
      </a>

      <a href="<?= $base ?>/messages.php"
         class="sidebar-link <?= $activePage === 'messages' ? 'active' : '' ?>">
        <i class="bi bi-chat-dots"></i>
        <span>Messages</span>
      </a>

      <div class="sidebar-divider"></div>
      <span class="sidebar-section-label">Account</span>

      <a href="<?= BASE_URL ?>/logout.php" class="sidebar-link text-danger">
        <i class="bi bi-box-arrow-right"></i>
        <span>Sign Out</span>
      </a>
    </nav>
  </div>

  <div class="sidebar-footer">
    <small><?= APP_NAME ?> &copy; <?= date('Y') ?></small>
  </div>
</aside>
