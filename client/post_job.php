<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('client');

$pdo    = db();
$uid    = current_user_id();
$errors = [];

// Optional edit mode
$editId = (int)($_GET['edit'] ?? 0);
$job    = null;

if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ? AND client_id = ?');
    $stmt->execute([$editId, $uid]);
    $job = $stmt->fetch();
    if (!$job) {
        flash('error', 'Job not found or access denied.');
        header('Location: ' . BASE_URL . '/client/my_jobs.php');
        exit;
    }
    // Cannot edit if accepted proposal exists
    $hasAccepted = $pdo->prepare('SELECT id FROM proposals WHERE job_id = ? AND status = "accepted"');
    $hasAccepted->execute([$editId]);
    if ($hasAccepted->fetch()) {
        flash('error', 'Cannot edit a job that already has an accepted proposal.');
        header('Location: ' . BASE_URL . '/client/my_jobs.php');
        exit;
    }
}

// Default form data
$formData = [
    'title'       => $job['title']       ?? '',
    'description' => $job['description'] ?? '',
    'budget'      => $job['budget']      ?? '',
    'deadline'    => $job['deadline']    ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget      = (float)($_POST['budget']   ?? 0);
    $deadline    = trim($_POST['deadline']    ?? '');

    $formData = compact('title', 'description', 'budget', 'deadline');

    // Validate
    if (empty($title) || mb_strlen($title) < 5)     $errors['title']       = 'Title must be at least 5 characters.';
    if (empty($description) || mb_strlen($description) < 20) $errors['description'] = 'Description must be at least 20 characters.';
    if ($budget <= 0)                                $errors['budget']      = 'Budget must be greater than zero.';
    if ($deadline && strtotime($deadline) < strtotime('today')) $errors['deadline'] = 'Deadline cannot be in the past.';

    if (empty($errors)) {
        if ($editId) {
            $pdo->prepare('UPDATE jobs SET title=?, description=?, budget=?, deadline=? WHERE id=? AND client_id=?')
                ->execute([$title, $description, $budget, $deadline ?: null, $editId, $uid]);
            flash('success', 'Job updated successfully!');
        } else {
            $pdo->prepare('INSERT INTO jobs (client_id, title, description, budget, deadline) VALUES (?,?,?,?,?)')
                ->execute([$uid, $title, $description, $budget, $deadline ?: null]);
            flash('success', 'Job posted successfully! Freelancers can now apply.');
        }
        header('Location: ' . BASE_URL . '/client/my_jobs.php');
        exit;
    }
}

$pageTitle  = $editId ? 'Edit Job' : 'Post a Job';
$activePage = 'post_job';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_client.php'; ?>

<div class="page-wrapper">
<div class="page-content">
  <?= render_flash() ?>

  <div class="page-header">
    <h1><?= $editId ? 'Edit Job' : 'Post a New Job' ?></h1>
    <p><?= $editId ? 'Update your job posting.' : 'Describe your project to attract the right freelancers.' ?></p>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body p-4">

          <?php if ($errors): ?>
            <div class="alert alert-danger">Please fix the errors below.</div>
          <?php endif; ?>

          <form method="POST" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
              <label class="form-label">Job Title <span class="text-danger">*</span></label>
              <input type="text" name="title"
                     class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                     value="<?= e($formData['title']) ?>"
                     placeholder="e.g. Build a Responsive E-Commerce Website" required>
              <?php if (isset($errors['title'])): ?><div class="invalid-feedback"><?= e($errors['title']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
              <label class="form-label">Project Description <span class="text-danger">*</span></label>
              <textarea name="description" rows="8"
                        class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                        placeholder="Describe the project in detail: goals, deliverables, tech stack requirements, milestones, etc."
                        required><?= e($formData['description']) ?></textarea>
              <div class="form-text">Be as specific as possible to attract qualified freelancers.</div>
              <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= e($errors['description']) ?></div><?php endif; ?>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Budget (USD) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" name="budget"
                         class="form-control <?= isset($errors['budget']) ? 'is-invalid' : '' ?>"
                         value="<?= e($formData['budget']) ?>" min="1" step="0.01" required placeholder="500">
                </div>
                <?php if (isset($errors['budget'])): ?><div class="text-danger small mt-1"><?= e($errors['budget']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label">Deadline <span class="text-muted fw-normal">(optional)</span></label>
                <input type="date" name="deadline"
                       class="form-control <?= isset($errors['deadline']) ? 'is-invalid' : '' ?>"
                       value="<?= e($formData['deadline']) ?>"
                       min="<?= date('Y-m-d') ?>">
                <?php if (isset($errors['deadline'])): ?><div class="invalid-feedback"><?= e($errors['deadline']) ?></div><?php endif; ?>
              </div>
            </div>

            <div class="d-flex gap-3 mt-4">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-<?= $editId ? 'save' : 'send' ?> me-2"></i>
                <?= $editId ? 'Update Job' : 'Post Job' ?>
              </button>
              <a href="<?= BASE_URL ?>/client/my_jobs.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </form>

        </div>
      </div>

      <!-- Tips Card -->
      <div class="card mt-3" style="background:#f8fafc">
        <div class="card-body p-3">
          <h6 class="fw-700 mb-2"><i class="bi bi-lightbulb text-warning me-2"></i>Tips for a Great Job Post</h6>
          <ul class="small text-muted mb-0 ps-3">
            <li>Be specific about deliverables and expected outcomes.</li>
            <li>Set a realistic budget based on the scope of work.</li>
            <li>Mention required skills or technologies.</li>
            <li>Include a reasonable deadline — not too rushed!</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
