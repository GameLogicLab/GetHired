<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('client');

$pdo = db();
$uid = current_user_id();
$errors = [];

// Fetch user
$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->execute([$uid]);
$user = $userStmt->fetch();

if (!$user) {
    flash('error', 'User not found.');
    header('Location: ' . BASE_URL . '/client/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fullName = trim($_POST['full_name'] ?? '');

    if (empty($fullName)) {
        $errors['full_name'] = 'Full name is required.';
    }

    $avatarName = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $file = $_FILES['avatar'];
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors['avatar'] = 'File too large (max 2 MB).';
        } elseif (!in_array(mime_content_type($file['tmp_name']), ALLOWED_IMG_TYPES)) {
            $errors['avatar'] = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
        } else {
            if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
                $errors['avatar'] = 'Upload failed. Cannot create upload directory.';
            }

            if (empty($errors['avatar'])) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $avatarName = 'avatar_' . $uid . '_' . time() . '.' . $ext;
                if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $avatarName)) {
                    $errors['avatar'] = 'Upload failed. Check folder permissions.';
                    $avatarName = $user['avatar'];
                }
            }
        }
    }

    if (empty($errors)) {
        $pdo->prepare('UPDATE users SET full_name = ?, avatar = ? WHERE id = ?')
            ->execute([$fullName, $avatarName, $uid]);

        $_SESSION['full_name'] = $fullName;
        $_SESSION['avatar'] = $avatarName;

        flash('success', 'Profile updated successfully!');
        header('Location: ' . BASE_URL . '/client/profile.php');
        exit;
    }
}

$pageTitle = 'My Profile';
$activePage = 'profile';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_client.php'; ?>

<div class="page-wrapper">
<div class="page-content">
  <?= render_flash() ?>

  <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h1>My Profile</h1>
      <p>Manage your client account details.</p>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-3">
      <div class="card text-center p-4">
        <?php if (!empty($user['avatar']) && file_exists(UPLOAD_DIR . $user['avatar'])): ?>
          <img src="<?= UPLOAD_URL . e($user['avatar']) ?>" class="avatar-lg mx-auto mb-3" alt="Avatar">
        <?php else: ?>
          <div class="avatar-lg-placeholder mx-auto mb-3"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <h5 class="fw-700 mb-1"><?= e($user['full_name']) ?></h5>
        <p class="text-muted small mb-2">Client</p>
      </div>
    </div>

    <div class="col-lg-9">
      <div class="card">
        <div class="card-header">Edit Profile</div>
        <div class="card-body p-4">

          <?php if ($errors): ?>
            <div class="alert alert-danger">Please fix the errors below.</div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name"
                       class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                       value="<?= e($_POST['full_name'] ?? $user['full_name']) ?>" required>
                <?php if (isset($errors['full_name'])): ?><div class="invalid-feedback"><?= e($errors['full_name']) ?></div><?php endif; ?>
              </div>

              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
              </div>

              <div class="col-12">
                <label class="form-label">Profile Photo</label>
                <input type="file" name="avatar"
                       class="form-control <?= isset($errors['avatar']) ? 'is-invalid' : '' ?>"
                       accept="image/jpeg,image/png,image/gif,image/webp">
                <div class="form-text">JPG, PNG, GIF, WEBP — max 2 MB</div>
                <?php if (isset($errors['avatar'])): ?><div class="invalid-feedback"><?= e($errors['avatar']) ?></div><?php endif; ?>
              </div>

              <div class="col-12 pt-2">
                <button type="submit" class="btn btn-primary px-4">
                  <i class="bi bi-save me-2"></i>Save Changes
                </button>
              </div>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>

</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>