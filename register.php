<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';

// Redirect if already logged in
if (is_logged_in()) {
  header('Location: ' . BASE_URL . (current_role() === 'freelancer' ? '/freelancer/dashboard.php' : '/client/dashboard.php'));
  exit;
}

$errors   = [];
$formData = ['full_name' => '', 'email' => '', 'role' => $_GET['role'] ?? 'freelancer'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $fullName  = trim($_POST['full_name']  ?? '');
  $email     = trim($_POST['email']      ?? '');
  $password  = $_POST['password']        ?? '';
  $confirm   = $_POST['confirm_password'] ?? '';
  $role      = $_POST['role']            ?? '';

  $formData = ['full_name' => $fullName, 'email' => $email, 'role' => $role];

  // Validation
  if (empty($fullName) || mb_strlen($fullName) < 2) {
    $errors['full_name'] = 'Full name must be at least 2 characters.';
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
  }
  if (mb_strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
  } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    $errors['password'] = 'Password must contain at least one uppercase letter and one number.';
  }
  if ($password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
  }
  if (!in_array($role, ['freelancer', 'client'])) {
    $errors['role'] = 'Please select a valid role.';
  }

  // Check email uniqueness
  if (empty($errors)) {
    $pdo  = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      $errors['email'] = 'An account with this email already exists.';
    }
  }

  // Create user
  if (empty($errors)) {
    $pdo  = db();
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$fullName, $email, $hash, $role]);
    $userId = (int)$pdo->lastInsertId();

    // Create empty freelancer profile
    if ($role === 'freelancer') {
      $pdo->prepare('INSERT INTO freelancer_profiles (user_id) VALUES (?)')
        ->execute([$userId]);
    }

    // Auto-login
    session_regenerate_id(true);
    $_SESSION['user_id']   = $userId;
    $_SESSION['role']      = $role;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['avatar']    = null;

    flash('success', 'Welcome to ' . APP_NAME . ', ' . $fullName . '! 🎉');
    header('Location: ' . BASE_URL . ($role === 'freelancer' ? '/freelancer/dashboard.php' : '/client/dashboard.php'));
    exit;
  }
}

$pageTitle = 'Create Account';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $pageTitle ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>

<body>

  <div class="auth-page">

    <!-- Left Visual -->
    <div class="auth-visual">
      <div class="auth-visual-orb auth-visual-orb-1"></div>
      <div class="auth-visual-orb auth-visual-orb-2"></div>
      <div class="auth-visual-content">
        <div class="auth-visual-logo"><span class="b-dot"></span><?= APP_NAME ?></div>
        <h2>Start earning or<br>building <em>today</em></h2>
        <p>Join thousands of clients and freelancers already using <?= APP_NAME ?> to do their best work.</p>

        <!-- Feature list -->
        <div style="display:flex;flex-direction:column;gap:.75rem;text-align:left;max-width:300px;margin:0 auto 2rem">
          <?php foreach (
            [
              ['bi-shield-check', 'Secure escrow payments'],
              ['bi-lightning-charge', 'AI-powered job matching'],
              ['bi-chat-dots', 'Built-in messaging & files'],
              ['bi-star', 'Verified reviews system'],
            ] as [$icon, $text]
          ): ?>
            <div style="display:flex;align-items:center;gap:.75rem">
              <div style="width:36px;height:36px;border-radius:10px;background:rgba(26,107,255,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi <?= $icon ?>" style="color:#60a5fa;font-size:.95rem"></i>
              </div>
              <span style="color:rgba(255,255,255,.8);font-size:.875rem;font-weight:500"><?= $text ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="auth-testimonial">
          <div class="auth-testimonial-text">"Within a week I had two clients and was earning more than my old 9–5. The platform makes everything seamless."</div>
          <div class="auth-testimonial-author">
            <div class="auth-testimonial-avatar">R</div>
            <div>
              <div class="auth-testimonial-name">Raj P.</div>
              <div class="auth-testimonial-title">Full-Stack Developer</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Form -->
    <div class="auth-form-side">
      <div class="auth-card">
        <div class="mb-4">
          <div class="auth-logo"><i class="bi bi-person-plus-fill" style="font-size:1.3rem"></i><?= APP_NAME ?></div>
          <h1 style="font-size:1.3rem;font-weight:700;color:var(--ink);margin:.5rem 0 .2rem;letter-spacing:-.02em">Create your account</h1>
          <p class="auth-subtitle">Free forever. No credit card needed.</p>
        </div>

        <?= render_flash() ?>

        <form method="POST" class="needs-validation" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label" for="full_name">Full Name</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" id="full_name" name="full_name"
                class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                value="<?= e($formData['full_name']) ?>" placeholder="Jane Smith" required autofocus>
              <?php if (isset($errors['full_name'])): ?>
                <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="email">Email Address</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" id="email" name="email"
                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                value="<?= e($formData['email']) ?>" placeholder="jane@example.com" required>
              <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" id="password" name="password"
                class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                placeholder="Min 8 chars, 1 uppercase, 1 number" required>
              <button type="button" class="btn btn-icon input-group-text" onclick="togglePassword(this)" aria-label="Toggle">
                <i class="bi bi-eye"></i>
              </button>
              <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
              <?php endif; ?>
            </div>
            <div class="progress mt-2">
              <div id="pwStrengthBar" style="width:0;height:4px;border-radius:99px"></div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
              <input type="password" id="confirm_password" name="confirm_password"
                class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                placeholder="Repeat your password" required>
              <?php if (isset($errors['confirm_password'])): ?>
                <div class="invalid-feedback"><?= e($errors['confirm_password']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">I want to…</label>
            <div class="d-flex gap-2">
              <div class="role-option flex-fill">
                <input type="radio" id="role_freelancer" name="role" value="freelancer"
                  <?= $formData['role'] === 'freelancer' ? 'checked' : '' ?> required>
                <label for="role_freelancer" class="justify-content-center">
                  <i class="bi bi-laptop"></i> Find Work
                </label>
              </div>
              <div class="role-option flex-fill">
                <input type="radio" id="role_client" name="role" value="client"
                  <?= $formData['role'] === 'client' ? 'checked' : '' ?>>
                <label for="role_client" class="justify-content-center">
                  <i class="bi bi-briefcase"></i> Hire Talent
                </label>
              </div>
            </div>
            <?php if (isset($errors['role'])): ?>
              <div class="text-danger small mt-1"><?= e($errors['role']) ?></div>
            <?php endif; ?>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3" style="font-size:.95rem">
            <i class="bi bi-rocket-takeoff me-2"></i>Create Account — It's Free
          </button>
        </form>

        <p class="text-center mb-0" style="font-size:.875rem;color:var(--ink-3)">
          Already have an account?
          <a href="<?= BASE_URL ?>/login.php" class="fw-700" style="color:var(--accent)">Sign in →</a>
        </p>

      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>

</html>