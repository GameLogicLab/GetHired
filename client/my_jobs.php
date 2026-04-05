<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('client');

$pdo = db();
$uid = current_user_id();

// ── Actions ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $jobId  = (int)($_POST['job_id'] ?? 0);

    // Verify ownership
    $ownerStmt = $pdo->prepare('SELECT id, status FROM jobs WHERE id = ? AND client_id = ?');
    $ownerStmt->execute([$jobId, $uid]);
    $ownedJob = $ownerStmt->fetch();

    if (!$ownedJob) {
        flash('error', 'Job not found or access denied.');
    } elseif ($action === 'close') {
        $pdo->prepare('UPDATE jobs SET status = "closed" WHERE id = ?')->execute([$jobId]);
        flash('success', 'Job closed successfully.');
    } elseif ($action === 'delete') {
        // Only delete if no accepted proposal
        $hasAccepted = $pdo->prepare('SELECT id FROM proposals WHERE job_id = ? AND status = "accepted"');
        $hasAccepted->execute([$jobId]);
        if ($hasAccepted->fetch()) {
            flash('error', 'Cannot delete a job with an accepted proposal.');
        } else {
            $pdo->prepare('DELETE FROM jobs WHERE id = ?')->execute([$jobId]);
            flash('success', 'Job deleted.');
        }
    }

    header('Location: ' . BASE_URL . '/client/my_jobs.php');
    exit;
}

// ── Fetch Jobs ────────────────────────────────────────────
$filter = $_GET['status'] ?? 'all';
$sql    = 'SELECT j.*,
                  (SELECT COUNT(*) FROM proposals WHERE job_id = j.id) AS proposal_count,
                  (SELECT COUNT(*) FROM proposals WHERE job_id = j.id AND status = "pending") AS pending_count,
                  (SELECT COUNT(*) FROM proposals WHERE job_id = j.id AND status = "accepted") AS accepted_count
           FROM jobs j WHERE j.client_id = ?';
$params = [$uid];

if ($filter === 'open')   { $sql .= ' AND j.status = "open"'; }
if ($filter === 'closed') { $sql .= ' AND j.status = "closed"'; }

$sql .= ' ORDER BY j.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$pageTitle  = 'My Jobs';
$activePage = 'my_jobs';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_client.php'; ?>

<div class="page-wrapper">
<div class="page-content">
  <?= render_flash() ?>

  <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h1>My Jobs</h1>
      <p>Manage all your posted projects.</p>
    </div>
    <a href="<?= BASE_URL ?>/client/post_job.php" class="btn btn-primary">
      <i class="bi bi-plus-circle me-2"></i>Post New Job
    </a>
  </div>

  <!-- Filter Tabs -->
  <ul class="nav nav-pills mb-4">
    <li class="nav-item">
      <a class="nav-link <?= $filter === 'all'    ? 'active' : '' ?>" href="?status=all">All</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $filter === 'open'   ? 'active' : '' ?>" href="?status=open">Open</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $filter === 'closed' ? 'active' : '' ?>" href="?status=closed">Closed</a>
    </li>
  </ul>

  <?php if (empty($jobs)): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state py-4">
          <i class="bi bi-collection-fill"></i>
          <h5>No jobs found</h5>
          <p>Post your first job to get started!</p>
          <a href="<?= BASE_URL ?>/client/post_job.php" class="btn btn-primary">Post a Job</a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($jobs as $job): ?>
      <div class="col-12">
        <div class="card">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
              <!-- Job Info -->
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <h5 class="fw-700 mb-0"><?= e($job['title']) ?></h5>
                  <span class="badge status-<?= $job['status'] ?>"><?= ucfirst($job['status']) ?></span>
                  <?php if ($job['pending_count'] > 0): ?>
                    <span class="badge bg-warning text-dark"><?= $job['pending_count'] ?> pending</span>
                  <?php endif; ?>
                  <?php if ($job['accepted_count'] > 0): ?>
                    <span class="badge bg-success"><i class="bi bi-check me-1"></i>Hired</span>
                  <?php endif; ?>
                </div>
                <p class="text-muted small mb-2"><?= e(truncate($job['description'], 120)) ?></p>
                <div class="d-flex gap-3 flex-wrap text-muted small">
                  <span><i class="bi bi-wallet2 me-1"></i><?= money((float)$job['budget']) ?></span>
                  <?php if ($job['deadline']): ?>
                    <span><i class="bi bi-calendar3 me-1"></i><?= date('M j, Y', strtotime($job['deadline'])) ?></span>
                  <?php endif; ?>
                  <span><i class="bi bi-inbox me-1"></i><?= (int)$job['proposal_count'] ?> proposals</span>
                  <span><i class="bi bi-clock me-1"></i><?= time_ago($job['created_at']) ?></span>
                </div>
              </div>

              <!-- Actions -->
              <div class="d-flex gap-2 flex-shrink-0 flex-wrap">
                <a href="<?= BASE_URL ?>/client/job_proposals.php?id=<?= $job['id'] ?>"
                   class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-people me-1"></i>Proposals (<?= (int)$job['proposal_count'] ?>)
                </a>

                <?php if ($job['status'] === 'open'): ?>
                  <a href="<?= BASE_URL ?>/client/post_job.php?edit=<?= $job['id'] ?>"
                     class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil"></i>
                  </a>

                  <form method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action"  value="close">
                    <input type="hidden" name="job_id"  value="<?= $job['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-warning"
                            data-confirm="Close this job? Freelancers will no longer be able to apply.">
                      <i class="bi bi-lock"></i>
                    </button>
                  </form>
                <?php endif; ?>

                <?php if ($job['accepted_count'] == 0): ?>
                  <form method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action"  value="delete">
                    <input type="hidden" name="job_id"  value="<?= $job['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                            data-confirm="Delete this job permanently? This cannot be undone.">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
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
