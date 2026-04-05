<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('freelancer');

$pdo = db();
$uid = current_user_id();
$id  = (int)($_GET['id'] ?? 0);

// Fetch job
$stmt = $pdo->prepare('
    SELECT j.*, u.full_name AS client_name, u.created_at AS client_since,
           (SELECT COUNT(*) FROM proposals WHERE job_id = j.id) AS proposal_count
    FROM   jobs j
    JOIN   users u ON u.id = j.client_id
    WHERE  j.id = ?
');
$stmt->execute([$id]);
$job = $stmt->fetch();

if (!$job) {
    header('Location: ' . BASE_URL . '/freelancer/browse_jobs.php');
    exit;
}

// Check if already applied
$existStmt = $pdo->prepare('SELECT * FROM proposals WHERE job_id = ? AND freelancer_id = ?');
$existStmt->execute([$id, $uid]);
$myProposal = $existStmt->fetch();

// ── AJAX Proposal Submission ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    // Re-check
    $existStmt->execute([$id, $uid]);
    if ($existStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already applied to this job.']);
        exit;
    }
    if ($job['status'] !== 'open') {
        echo json_encode(['success' => false, 'message' => 'This job is no longer open.']);
        exit;
    }

    $text      = trim($_POST['proposal_text'] ?? '');
    $bidAmount = (float)($_POST['bid_amount']  ?? 0);

    if (empty($text) || mb_strlen($text) < 20) {
        echo json_encode(['success' => false, 'message' => 'Proposal must be at least 20 characters.']);
        exit;
    }
    if ($bidAmount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Bid amount must be greater than zero.']);
        exit;
    }

    $ins = $pdo->prepare('INSERT INTO proposals (job_id, freelancer_id, proposal_text, bid_amount) VALUES (?,?,?,?)');
    $ins->execute([$id, $uid, $text, $bidAmount]);

    echo json_encode(['success' => true, 'message' => 'Proposal submitted successfully! 🎉']);
    exit;
}

$pageTitle  = e($job['title']);
$activePage = 'browse';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_freelancer.php'; ?>

<div class="page-wrapper">
<div class="page-content">
  <?= render_flash() ?>

  <div class="mb-3">
    <a href="<?= BASE_URL ?>/freelancer/browse_jobs.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back to Jobs
    </a>
  </div>

  <div class="row g-4">
    <!-- Job Details -->
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
            <h2 class="fw-800 mb-0" style="font-size:1.35rem"><?= e($job['title']) ?></h2>
            <span class="badge status-<?= $job['status'] ?> fs-6"><?= ucfirst($job['status']) ?></span>
          </div>

          <div class="d-flex flex-wrap gap-3 mb-4 text-muted small">
            <span><i class="bi bi-wallet2 me-1"></i> Budget: <strong class="text-dark"><?= money((float)$job['budget']) ?></strong></span>
            <?php if ($job['deadline']): ?>
              <span><i class="bi bi-calendar3 me-1"></i> Deadline: <strong class="text-dark"><?= date('M j, Y', strtotime($job['deadline'])) ?></strong></span>
            <?php endif; ?>
            <span><i class="bi bi-people me-1"></i> <strong class="text-dark"><?= (int)$job['proposal_count'] ?></strong> proposals</span>
            <span><i class="bi bi-clock me-1"></i> Posted <?= time_ago($job['created_at']) ?></span>
          </div>

          <h6 class="fw-700">Project Description</h6>
          <div class="text-muted" style="white-space:pre-wrap;line-height:1.7"><?= e($job['description']) ?></div>
        </div>
      </div>

      <!-- Proposal Form -->
      <?php if ($job['status'] === 'open'): ?>
        <?php if ($myProposal): ?>
          <div class="card mt-4">
            <div class="card-body p-4">
              <h5 class="fw-700 mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Your Proposal</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <span class="text-muted small">Bid Amount</span>
                  <p class="fw-700 fs-5 mb-0"><?= money((float)$myProposal['bid_amount']) ?></p>
                </div>
                <div class="col-md-6">
                  <span class="text-muted small">Status</span>
                  <p class="mb-0"><span class="badge status-<?= $myProposal['status'] ?>"><?= ucfirst($myProposal['status']) ?></span></p>
                </div>
                <div class="col-12">
                  <span class="text-muted small">Proposal Text</span>
                  <p class="mb-0" style="white-space:pre-wrap"><?= e($myProposal['proposal_text']) ?></p>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="card mt-4">
            <div class="card-header">Submit Your Proposal</div>
            <div class="card-body p-4">
              <form id="proposalForm"
                    action="<?= BASE_URL ?>/freelancer/job_details.php?id=<?= $id ?>"
                    method="POST"
                    data-ajax="1"
                    class="needs-validation" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                  <label class="form-label">Bid Amount (USD) <span class="text-danger">*</span></label>
                  <div class="input-group" style="max-width:200px">
                    <span class="input-group-text">$</span>
                    <input type="number" name="bid_amount" class="form-control"
                           min="1" step="0.01" required placeholder="e.g. 450">
                  </div>
                </div>

                <div class="mb-4">
                  <label class="form-label">Cover Letter <span class="text-danger">*</span></label>
                  <textarea name="proposal_text" class="form-control" rows="6"
                            minlength="20" required
                            placeholder="Introduce yourself and explain why you're the right person for this job…"></textarea>
                  <div class="form-text">Minimum 20 characters.</div>
                </div>

                <button type="submit" class="btn btn-primary px-4">
                  <i class="bi bi-send me-2"></i>Submit Proposal
                </button>
              </form>
              <div id="proposalFeedback" class="d-none"></div>
            </div>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="alert alert-warning mt-4"><i class="bi bi-lock me-2"></i>This job is closed and no longer accepting proposals.</div>
      <?php endif; ?>
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">About the Client</div>
        <div class="card-body p-4">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="avatar-placeholder rounded-circle" style="width:42px;height:42px;flex-shrink:0">
              <?= strtoupper(substr($job['client_name'], 0, 1)) ?>
            </div>
            <div>
              <p class="fw-700 mb-0"><?= e($job['client_name']) ?></p>
              <small class="text-muted">Member since <?= date('M Y', strtotime($job['client_since'])) ?></small>
            </div>
          </div>
          <hr>
          <div class="row g-2 text-center">
            <div class="col-6">
              <p class="fw-800 fs-4 mb-0"><?= money((float)$job['budget']) ?></p>
              <small class="text-muted">Budget</small>
            </div>
            <div class="col-6">
              <p class="fw-800 fs-4 mb-0"><?= (int)$job['proposal_count'] ?></p>
              <small class="text-muted">Proposals</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>

<!-- Pass CSRF token to JS -->
<meta name="csrf-token" content="<?= csrf_token() ?>">

<?php include __DIR__ . '/../includes/footer.php'; ?>
