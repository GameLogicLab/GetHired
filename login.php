<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . (current_role() === 'freelancer' ? '/freelancer/dashboard.php' : '/client/dashboard.php'));
    exit;
}

$errors   = [];
$formData = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $remember = !empty($_POST['remember']);
    $formData['email'] = $email;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    }

    if (empty($errors)) {
        $pdo  = db();
        $stmt = $pdo->prepare('SELECT id, full_name, password, role, avatar FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors['general'] = 'Invalid email or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['avatar']    = $user['avatar'];

            if ($remember) {
                setcookie('remember_email', $email, time() + 86400 * 30, '/', '', false, true);
            }

            flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            header('Location: ' . BASE_URL . ($user['role'] === 'freelancer' ? '/freelancer/dashboard.php' : '/client/dashboard.php'));
            exit;
        }
    }
}

if (empty($formData['email']) && !empty($_COOKIE['remember_email'])) {
    $formData['email'] = $_COOKIE['remember_email'];
}

$pageTitle = 'Sign In';
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

  <!-- Left Visual Panel -->
  <div class="auth-visual">
    <div class="auth-visual-orb auth-visual-orb-1"></div>
    <div class="auth-visual-orb auth-visual-orb-2"></div>

    <div class="auth-visual-content">
      <div class="auth-visual-logo">
        <span class="b-dot"></span><?= APP_NAME ?>
      </div>

      <h2>The <em>smartest</em> way<br>to hire freelancers</h2>
      <p>Connect with top talent and grow your business — backed by secure payments and AI matching.</p>

      <!-- Testimonial -->
      <div class="auth-testimonial">
        <div class="auth-testimonial-text">
          "Posted my job at 9am. Had 3 great proposals by noon. Hired by 2pm. Truly unbelievable turnaround."
        </div>
        <div class="auth-testimonial-author">
          <div class="auth-testimonial-avatar">S</div>
          <div>
            <div class="auth-testimonial-name">Sara M.</div>
            <div class="auth-testimonial-title">Founder, Bloom Agency</div>
          </div>
        </div>
      </div>

      <!-- Stats row -->
      <div class="d-flex justify-content-center gap-4 mt-3">
        <div class="text-center">
          <div style="font-size:1.35rem;font-weight:800;color:#fff">5.2K+</div>
          <div style="font-size:.72rem;color:rgba(255,255,255,.45);letter-spacing:.06em;text-transform:uppercase">Freelancers</div>
        </div>
        <div style="width:1px;background:rgba(255,255,255,.1)"></div>
        <div class="text-center">
          <div style="font-size:1.35rem;font-weight:800;color:#fff">98%</div>
          <div style="font-size:.72rem;color:rgba(255,255,255,.45);letter-spacing:.06em;text-transform:uppercase">Satisfaction</div>
        </div>
        <div style="width:1px;background:rgba(255,255,255,.1)"></div>
        <div class="text-center">
          <div style="font-size:1.35rem;font-weight:800;color:#fff">≤4h</div>
          <div style="font-size:.72rem;color:rgba(255,255,255,.45);letter-spacing:.06em;text-transform:uppercase">First Proposal</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Form Panel -->
  <div class="auth-form-side">
    <div class="auth-card">

      <div class="mb-4">
        <div class="auth-logo">
          <i class="bi bi-lightning-charge-fill" style="font-size:1.35rem"></i><?= APP_NAME ?>
        </div>
        <h1 style="font-size:1.35rem;font-weight:700;color:var(--ink);margin:.5rem 0 .2rem;letter-spacing:-.02em">Welcome back</h1>
        <p class="auth-subtitle">Sign in to your <?= APP_NAME ?> account</p>
      </div>

      <?= render_flash() ?>

      <?php if (isset($errors['general'])): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
          <?= e($errors['general']) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label" for="email">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" id="email" name="email"
                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                   value="<?= e($formData['email']) ?>" placeholder="you@example.com" required autofocus>
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
                   placeholder="Your password" required>
            <button type="button" class="btn btn-icon input-group-text" onclick="togglePassword(this)" aria-label="Toggle password">
              <i class="bi bi-eye"></i>
            </button>
            <?php if (isset($errors['password'])): ?>
              <div class="invalid-feedback"><?= e($errors['password']) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="mb-4 d-flex align-items-center justify-content-between">
          <div class="form-check mb-0">
            <input type="checkbox" class="form-check-input" id="remember" name="remember"
                   <?= ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['remember'])) ? 'checked' : '' ?>>
            <label class="form-check-label small fw-500" for="remember">Remember me</label>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3" style="font-size:.95rem">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
      </form>

      <div class="divider-label my-3"><center>or</center></div>

      <p class="text-center mb-0" style="font-size:.875rem;color:var(--ink-3)">
        New to <?= APP_NAME ?>?
        <a href="<?= BASE_URL ?>/register.php" class="fw-700" style="color:var(--accent)">Create a free account →</a>
      </p>

    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
