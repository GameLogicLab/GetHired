<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('freelancer');

$pdo = db();
$uid = current_user_id();

$stmt = $pdo->prepare('
    SELECT p.*, j.title AS job_title, j.budget, j.status AS job_status
    FROM   proposals p
    JOIN   jobs j ON j.id = p.job_id
    WHERE  p.freelancer_id = ?
    ORDER  BY p.created_at DESC
');
$stmt->execute([$uid]);
$proposals = $stmt->fetchAll();

$pageTitle  = 'My Proposals';
$activePage = 'proposals';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_freelancer.php'; ?>

<div class="page-wrapper">
<div class="page-content">
  <?= render_flash() ?>

  <div class="page-header">
    <h1>My Proposals</h1>
    <p>Track the status of all your submitted proposals.</p>
  </div>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <span><i class="bi bi-send me-2"></i>All Proposals (<?= count($proposals) ?>)</span>
    </div>
    <div class="card-body p-3">
      <?php if (empty($proposals)): ?>
        <div class="empty-state py-4">
          <i class="bi bi-send-fill"></i>
          <h5>No proposals yet</h5>
          <p>Start browsing jobs and submit your first proposal!</p>
          <a href="<?= BASE_URL ?>/freelancer/browse_jobs.php" class="btn btn-primary">Browse Jobs</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Job Title</th>
                <th>Bid Amount</th>
                <th>Job Budget</th>
                <th>Submitted</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($proposals as $p): ?>
              <tr>
                <td>
                  <a href="<?= BASE_URL ?>/freelancer/job_details.php?id=<?= $p['job_id'] ?>"
                     class="fw-semibold text-decoration-none">
                    <?= e(truncate($p['job_title'], 50)) ?>
                  </a>
                  <?php if ($p['job_status'] === 'closed'): ?>
                    <span class="badge status-closed ms-1">Closed</span>
                  <?php endif; ?>
                </td>
                <td class="fw-700"><?= money((float)$p['bid_amount']) ?></td>
                <td class="text-muted"><?= money((float)$p['budget']) ?></td>
                <td class="text-muted small"><?= time_ago($p['created_at']) ?></td>
                <td><span class="badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                <td>
                  <a href="<?= BASE_URL ?>/freelancer/job_details.php?id=<?= $p['job_id'] ?>"
                     class="btn btn-sm btn-outline-primary">View</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
