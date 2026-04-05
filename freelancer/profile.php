<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('freelancer');

$pdo = db();
$uid = current_user_id();
$errors = [];

// Fetch user + profile
$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->execute([$uid]);
$user = $userStmt->fetch();

$profStmt = $pdo->prepare('SELECT * FROM freelancer_profiles WHERE user_id = ?');
$profStmt->execute([$uid]);
$profile = $profStmt->fetch();

if (!$profile) {
    $pdo->prepare('INSERT INTO freelancer_profiles (user_id) VALUES (?)')->execute([$uid]);
    $profStmt->execute([$uid]);
    $profile = $profStmt->fetch();
}

// ── Handle POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $title    = trim($_POST['title']            ?? '');
    $skills   = trim($_POST['skills']           ?? '');
    $level    = $_POST['experience_level']      ?? 'entry';
    $rate     = (float)($_POST['hourly_rate']   ?? 0);
    $bio      = trim($_POST['bio']              ?? '');
    $fullName = trim($_POST['full_name']        ?? '');

    // Validate
    if (empty($fullName)) $errors['full_name'] = 'Full name is required.';
    if (!in_array($level, ['entry','intermediate','expert'])) $errors['level'] = 'Invalid experience level.';
    if ($rate < 0) $errors['rate'] = 'Hourly rate cannot be negative.';

    // Avatar upload
    $avatarName = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $file = $_FILES['avatar'];
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors['avatar'] = 'File too large (max 2 MB).';
        } elseif (!in_array(mime_content_type($file['tmp_name']), ALLOWED_IMG_TYPES)) {
            $errors['avatar'] = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
        } else {
            // Create upload directory if it doesn't exist
            $uploadDir = UPLOAD_DIR;
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $errors['avatar'] = 'Upload failed. Cannot create upload directory.';
                    $avatarName = $user['avatar'];
                }
            }
            
            if (empty($errors['avatar'])) {
                $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $avatarName = 'avatar_' . $uid . '_' . time() . '.' . $ext;
                if (!move_uploaded_file($file['tmp_name'], $uploadDir . $avatarName)) {
                    $errors['avatar'] = 'Upload failed. Check folder permissions.';
                    $avatarName = $user['avatar'];
                }
            }
        }
    }

    if (empty($errors)) {
        $pdo->prepare('UPDATE users SET full_name = ?, avatar = ? WHERE id = ?')
            ->execute([$fullName, $avatarName, $uid]);

        $pdo->prepare('UPDATE freelancer_profiles SET title=?, skills=?, experience_level=?, hourly_rate=?, bio=? WHERE user_id=?')
            ->execute([$title, $skills, $level, $rate, $bio, $uid]);

        $_SESSION['full_name'] = $fullName;
        $_SESSION['avatar']    = $avatarName;

        flash('success', 'Profile updated successfully!');
        header('Location: ' . BASE_URL . '/freelancer/profile.php');
        exit;
    }
}

// ── Profile completeness ──────────────────────────────────
$fields     = ['title', 'skills', 'experience_level', 'hourly_rate', 'bio'];
$filled     = count(array_filter($fields, fn($f) => !empty($profile[$f])));
$completion = (int)(($filled / count($fields)) * 100);

$pageTitle  = 'My Profile';
$activePage = 'profile';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_freelancer.php'; ?>

<div class="page-wrapper">
<div class="page-content">
  <?= render_flash() ?>

  <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h1>My Profile</h1>
      <p>How clients see you.</p>
    </div>
    <span class="badge bg-primary"><?= $completion ?>% Complete</span>
  </div>

  <div class="row g-4">
    <!-- Avatar + Completion -->
    <div class="col-lg-3">
      <div class="card text-center p-4">
        <?php if (!empty($user['avatar']) && file_exists(UPLOAD_DIR . $user['avatar'])): ?>
          <img src="<?= UPLOAD_URL . e($user['avatar']) ?>" class="avatar-lg mx-auto mb-3" alt="Avatar">
        <?php else: ?>
          <div class="avatar-lg-placeholder mx-auto mb-3">
            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
          </div>
        <?php endif; ?>
        <h5 class="fw-700 mb-1"><?= e($user['full_name']) ?></h5>
        <p class="text-muted small mb-2"><?= e($profile['title'] ?: 'No title set') ?></p>
        <span class="badge <?= $profile['experience_level'] === 'expert' ? 'bg-success' : 'bg-secondary' ?>">
          <?= ucfirst($profile['experience_level'] ?: 'entry') ?>
        </span>
        <hr>
        <div class="text-start">
          <small class="text-muted d-block mb-1">Profile Completion</small>
          <div class="progress mb-1"><div class="progress-bar" style="width:<?= $completion ?>%"></div></div>
          <small class="text-muted"><?= $completion ?>%</small>
        </div>
      </div>
    </div>

    <!-- Edit Form -->
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
              <!-- Full Name -->
              <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name"
                       class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                       value="<?= e($_POST['full_name'] ?? $user['full_name']) ?>" required>
                <?php if (isset($errors['full_name'])): ?><div class="invalid-feedback"><?= e($errors['full_name']) ?></div><?php endif; ?>
              </div>

              <!-- Professional Title -->
              <div class="col-md-6">
                <label class="form-label">Professional Title</label>
                <input type="text" name="title" class="form-control"
                       value="<?= e($_POST['title'] ?? $profile['title'] ?? '') ?>"
                       placeholder="e.g. Full-Stack Developer">
              </div>

              <!-- Skills -->
              <div class="col-12">
                <label class="form-label">Skills <span class="text-muted fw-normal">(comma-separated)</span></label>
                <input type="text" name="skills" class="form-control"
                       value="<?= e($_POST['skills'] ?? $profile['skills'] ?? '') ?>"
                       placeholder="e.g. PHP, MySQL, JavaScript, Bootstrap">
              </div>

              <!-- Experience Level -->
              <div class="col-md-6">
                <label class="form-label">Experience Level</label>
                <select name="experience_level" class="form-select">
                  <?php foreach (['entry' => 'Entry Level', 'intermediate' => 'Intermediate', 'expert' => 'Expert'] as $val => $label): ?>
                    <option value="<?= $val ?>"
                      <?= ($_POST['experience_level'] ?? $profile['experience_level']) === $val ? 'selected' : '' ?>>
                      <?= $label ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Hourly Rate -->
              <div class="col-md-6">
                <label class="form-label">Hourly Rate (USD)</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" name="hourly_rate" class="form-control"
                         value="<?= e($_POST['hourly_rate'] ?? $profile['hourly_rate'] ?? '0') ?>"
                         min="0" step="0.01" placeholder="25.00">
                </div>
              </div>

              <!-- Bio -->
              <div class="col-12">
                <label class="form-label">Bio</label>
                <textarea name="bio" class="form-control" rows="4"
                          placeholder="Tell clients about yourself…"><?= e($_POST['bio'] ?? $profile['bio'] ?? '') ?></textarea>
              </div>

              <!-- Avatar Upload -->
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
