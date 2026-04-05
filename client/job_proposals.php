<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('client');

$pdo = db();
$uid = current_user_id();
$id  = (int)($_GET['id'] ?? 0);

// Verify job belongs to this client
$jobStmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ? AND client_id = ?');
$jobStmt->execute([$id, $uid]);
$job = $jobStmt->fetch();

if (!$job) {
    flash('error', 'Job not found.');
    header('Location: ' . BASE_URL . '/client/my_jobs.php');
    exit;
}

// ── AJAX: Accept / Reject ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $action     = $_POST['action']      ?? '';
    $proposalId = (int)($_POST['proposal_id'] ?? 0);

    // Verify proposal belongs to this client's job
    $propStmt = $pdo->prepare('
        SELECT p.* FROM proposals p
        JOIN jobs j ON j.id = p.job_id
        WHERE p.id = ? AND j.client_id = ?
    ');
    $propStmt->execute([$proposalId, $uid]);
    $proposal = $propStmt->fetch();

    if (!$proposal) {
        echo json_encode(['success' => false, 'message' => 'Proposal not found.']);
        exit;
    }
    if ($proposal['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'This proposal has already been reviewed.']);
        exit;
    }

    if ($action === 'accept') {
        // Reject all other proposals for this job
        $pdo->prepare('UPDATE proposals SET status = "rejected" WHERE job_id = ? AND id != ?')
            ->execute([$proposal['job_id'], $proposalId]);
        // Accept this one
        $pdo->prepare('UPDATE proposals SET status = "accepted" WHERE id = ?')->execute([$proposalId]);
        echo json_encode(['success' => true, 'message' => 'Proposal accepted! Other proposals have been rejected.']);
    } elseif ($action === 'reject') {
        $pdo->prepare('UPDATE proposals SET status = "rejected" WHERE id = ?')->execute([$proposalId]);
        echo json_encode(['success' => true, 'message' => 'Proposal rejected.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
    exit;
}

// ── Fetch Proposals ───────────────────────────────────────
$propStmt = $pdo->prepare('
    SELECT p.*, u.full_name, u.avatar,
           fp.title AS freelancer_title, fp.skills, fp.experience_level, fp.hourly_rate
    FROM   proposals p
    JOIN   users u  ON u.id  = p.freelancer_id
    LEFT JOIN freelancer_profiles fp ON fp.user_id = p.freelancer_id
    WHERE  p.job_id = ?
    ORDER  BY
        CASE p.status WHEN "accepted" THEN 0 WHEN "pending" THEN 1 ELSE 2 END,
        p.created_at ASC
');
$propStmt->execute([$id]);
$proposals = $propStmt->fetchAll();

$pageTitle  = 'Job Proposals — ' . e($job['title']);
$activePage = 'my_jobs';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_client.php'; ?>
<meta name="csrf-token" content="<?= csrf_token() ?>">
<script>window.proposalActionUrl = "<?= BASE_URL ?>/client/job_proposals.php?id=<?= $id ?>";</script>

<div class="page-wrapper">
<div class="page-content">
  <?= render_flash() ?>

  <div class="mb-3">
    <a href="<?= BASE_URL ?>/client/my_jobs.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back to My Jobs
    </a>
  </div>

  <!-- Job Summary Banner -->
  <div class="card mb-4" style="background:linear-gradient(135deg,#1e1b4b,#4f46e5);color:#fff;border:none">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h2 class="fw-800 mb-1" style="font-size:1.3rem"><?= e($job['title']) ?></h2>
          <div class="d-flex gap-3 flex-wrap opacity-75 small">
            <span><i class="bi bi-wallet2 me-1"></i><?= money((float)$job['budget']) ?></span>
            <?php if ($job['deadline']): ?>
              <span><i class="bi bi-calendar3 me-1"></i><?= date('M j, Y', strtotime($job['deadline'])) ?></span>
            <?php endif; ?>
            <span><i class="bi bi-inbox me-1"></i><?= count($proposals) ?> proposal<?= count($proposals) !== 1 ? 's' : '' ?></span>
          </div>
        </div>
        <span class="badge status-<?= $job['status'] ?> fs-6"><?= ucfirst($job['status']) ?></span>
      </div>
    </div>
  </div>

  <?php if (empty($proposals)): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state py-4">
          <i class="bi bi-inbox-fill"></i>
          <h5>No proposals yet</h5>
          <p>Your job is live. Freelancers will start applying soon!</p>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($proposals as $p): ?>
      <div class="col-12" data-proposal-row>
        <div class="proposal-item <?= $p['status'] === 'accepted' ? 'border-success' : '' ?>"
             style="<?= $p['status'] === 'accepted' ? 'border-color:var(--success)!important;border-width:2px' : '' ?>">
          <div class="row g-3 align-items-start">

            <!-- Freelancer Info -->
            <div class="col-md-3 col-lg-2 text-center">
              <?php if (!empty($p['avatar']) && file_exists(UPLOAD_DIR . $p['avatar'])): ?>
                <img src="<?= UPLOAD_URL . e($p['avatar']) ?>" class="avatar-lg mx-auto d-block mb-2" alt="">
              <?php else: ?>
                <div class="avatar-lg-placeholder mx-auto mb-2"><?= strtoupper(substr($p['full_name'], 0, 1)) ?></div>
              <?php endif; ?>
              <p class="fw-700 mb-0 small"><?= e($p['full_name']) ?></p>
              <?php if ($p['freelancer_title']): ?>
                <small class="text-muted d-block"><?= e($p['freelancer_title']) ?></small>
              <?php endif; ?>
              <?php if ($p['experience_level']): ?>
                <span class="badge bg-secondary mt-1"><?= ucfirst($p['experience_level']) ?></span>
              <?php endif; ?>
            </div>

            <!-- Proposal Text -->
            <div class="col-md-6 col-lg-7">
              <?php if ($p['skills']): ?>
                <p class="small text-muted mb-2">
                  <i class="bi bi-tools me-1"></i>
                  <?= e($p['skills']) ?>
                </p>
              <?php endif; ?>
              <p class="mb-2" style="white-space:pre-wrap;font-size:.9rem"><?= e($p['proposal_text']) ?></p>
              <small class="text-muted"><i class="bi bi-clock me-1"></i>Submitted <?= time_ago($p['created_at']) ?></small>
            </div>

            <!-- Bid + Actions -->
            <div class="col-md-3 text-md-end">
              <p class="fw-800 fs-4 mb-1 text-primary"><?= money((float)$p['bid_amount']) ?></p>
              <?php if ($p['hourly_rate'] > 0): ?>
                <p class="text-muted small mb-2"><?= money((float)$p['hourly_rate']) ?>/hr</p>
              <?php endif; ?>

              <span class="badge status-<?= $p['status'] ?> d-block mb-2" data-status-badge>
                <?= ucfirst($p['status']) ?>
              </span>

              <?php if ($p['status'] === 'pending' && $job['status'] === 'open'): ?>
                <div class="d-flex flex-md-column gap-2 justify-content-md-end mt-2">
                  <button class="btn btn-sm btn-success"
                          data-action="accept"
                          data-id="<?= $p['id'] ?>">
                    <i class="bi bi-check-lg me-1"></i>Accept
                  </button>
                  <button class="btn btn-sm btn-outline-danger"
                          data-action="reject"
                          data-id="<?= $p['id'] ?>">
                    <i class="bi bi-x-lg me-1"></i>Reject
                  </button>
                </div>
              <?php elseif ($p['status'] === 'accepted'): ?>
                <?php
                // Check if review already exists
                $reviewStmt = $pdo->prepare('SELECT id FROM reviews WHERE proposal_id = ?');
                $reviewStmt->execute([$p['id']]);
                $hasReview = $reviewStmt->fetch();
                ?>
                <?php if (!$hasReview): ?>
                  <a href="<?= BASE_URL ?>/client/review.php?proposal=<?= $p['id'] ?>" 
                     class="btn btn-sm btn-primary mt-2">
                    <i class="bi bi-star me-1"></i>Complete & Review
                  </a>
                <?php else: ?>
                  <span class="badge bg-success mt-2 d-block">
                    <i class="bi bi-check-circle me-1"></i>Reviewed
                  </span>
                <?php endif; ?>
              <?php endif; ?>
            </div>

          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
