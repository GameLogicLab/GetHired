<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('freelancer');

$pdo = db();
$uid = current_user_id();

$search  = trim($_GET['q']      ?? '');
$minBudg = (float)($_GET['min'] ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * JOBS_PER_PAGE;

$where  = ['j.status = "open"'];
$params = [];

if ($search !== '') {
    $where[]  = '(j.title LIKE ? OR j.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($minBudg > 0) { $where[] = 'j.budget >= ?'; $params[] = $minBudg; }

$whereSQL = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM jobs j WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = (int)ceil($total / JOBS_PER_PAGE);

$paramsPage   = array_merge($params, [$uid, JOBS_PER_PAGE, $offset]);
$jobsStmt     = $pdo->prepare("
    SELECT j.*, u.full_name AS client_name,
           (SELECT COUNT(*) FROM proposals WHERE job_id = j.id) AS proposal_count,
           (SELECT id FROM proposals WHERE job_id = j.id AND freelancer_id = ?) AS my_proposal_id
    FROM   jobs j
    JOIN   users u ON u.id = j.client_id
    WHERE  $whereSQL
    ORDER  BY j.created_at DESC
    LIMIT  ? OFFSET ?
");
$jobsStmt->execute($paramsPage);
$jobs = $jobsStmt->fetchAll();

$pageTitle  = 'Browse Jobs';
$activePage = 'browse';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_freelancer.php'; ?>

<div class="page-wrapper">
<div class="page-content">
  <?= render_flash() ?>

  <div class="page-header">
    <h1>Browse Jobs</h1>
    <p>Find your next project — <?= $total ?> open job<?= $total !== 1 ? 's' : '' ?> available.</p>
  </div>

  <div class="card mb-4">
    <div class="card-body p-3">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-7">
          <label class="form-label small fw-semibold">Keyword</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="jobSearch" name="q" class="form-control"
                   value="<?= e($search) ?>" placeholder="e.g. PHP, Logo Design…">
          </div>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Min Budget</label>
          <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="min" class="form-control" value="<?= $minBudg ?: '' ?>" min="0" placeholder="0">
          </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">Search</button>
          <?php if ($search || $minBudg): ?>
            <a href="<?= BASE_URL ?>/freelancer/browse_jobs.php" class="btn btn-outline-secondary">Clear</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3" id="jobGrid">
    <?php if (empty($jobs)): ?>
      <div class="col-12">
        <div class="empty-state">
          <i class="bi bi-briefcase-fill"></i>
          <h5>No jobs found</h5>
          <p>Try adjusting your search filters.</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($jobs as $job): ?>
      <div class="col-md-6 col-xl-4" data-job-col>
        <div class="job-card" data-job-card>
          <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
            <a href="<?= BASE_URL ?>/freelancer/job_details.php?id=<?= $job['id'] ?>"
               class="job-card-title text-decoration-none"><?= e($job['title']) ?></a>
            <?php if ($job['my_proposal_id']): ?>
              <span class="badge flex-shrink-0" style="background:var(--accent-2);color:var(--accent)">Applied</span>
            <?php else: ?>
              <span class="badge status-open flex-shrink-0">Open</span>
            <?php endif; ?>
          </div>
          <p class="job-card-desc"><?= e(truncate($job['description'], 115)) ?></p>
          <div class="job-card-meta mt-2">
            <span style="font-weight:700;color:var(--ink)"><i class="bi bi-wallet2"></i> <?= money((float)$job['budget']) ?></span>
            <?php if ($job['deadline']): ?>
              <span><i class="bi bi-calendar3"></i> <?= date('M j', strtotime($job['deadline'])) ?></span>
            <?php endif; ?>
            <span><i class="bi bi-people"></i> <?= (int)$job['proposal_count'] ?></span>
          </div>
          <div class="d-flex align-items-center justify-content-between mt-auto pt-3 gap-2">
            <div class="d-flex align-items-center gap-2" style="min-width:0">
              <div style="width:26px;height:26px;border-radius:8px;background:linear-gradient(135deg,var(--accent),var(--purple));display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <span style="color:#fff;font-size:.68rem;font-weight:700"><?= strtoupper(substr($job['client_name'],0,1)) ?></span>
              </div>
              <span style="font-size:.78rem;color:var(--ink-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($job['client_name']) ?></span>
            </div>
            <a href="<?= BASE_URL ?>/freelancer/job_details.php?id=<?= $job['id'] ?>"
               class="btn btn-outline-primary btn-sm flex-shrink-0">
              <?= $job['my_proposal_id'] ? 'View Proposal' : 'Apply →' ?>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php if ($page > 1): ?>
        <li class="page-item">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
            <i class="bi bi-chevron-left"></i>
          </a>
        </li>
      <?php endif; ?>
      <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
      <?php if ($page < $pages): ?>
        <li class="page-item">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
            <i class="bi bi-chevron-right"></i>
          </a>
        </li>
      <?php endif; ?>
    </ul>
  </nav>
  <?php endif; ?>

</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
