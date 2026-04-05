<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('client');

$pdo = db();
$uid = current_user_id();

$stats = $pdo->prepare('
    SELECT
        (SELECT COUNT(*) FROM jobs WHERE client_id = ?)                                  AS total_jobs,
        (SELECT COUNT(*) FROM jobs WHERE client_id = ? AND status = "open")              AS open_jobs,
        (SELECT COUNT(*) FROM proposals p JOIN jobs j ON j.id = p.job_id WHERE j.client_id = ?) AS total_proposals,
        (SELECT COUNT(*) FROM proposals p JOIN jobs j ON j.id = p.job_id WHERE j.client_id = ? AND p.status = "accepted") AS accepted_proposals
');
$stats->execute([$uid, $uid, $uid, $uid]);
$s = $stats->fetch();

$jobStmt = $pdo->prepare('
    SELECT j.*,
           (SELECT COUNT(*) FROM proposals WHERE job_id = j.id) AS proposal_count,
           (SELECT COUNT(*) FROM proposals WHERE job_id = j.id AND status = "pending") AS pending_count
    FROM jobs j
    WHERE j.client_id = ?
    ORDER BY j.created_at DESC
    LIMIT 5
');
$jobStmt->execute([$uid]);
$jobs = $jobStmt->fetchAll();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_client.php'; ?>

<div class="page-wrapper">
<div class="page-content">
  <?= render_flash() ?>

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <h1>Good <?= (date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening')) ?>, <?= e(explode(' ', $_SESSION['full_name'])[0]) ?> 👋</h1>
      <p>Here's what's happening with your projects today.</p>
    </div>
    <a href="<?= BASE_URL ?>/client/post_job.php" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> Post a Job
    </a>
  </div>

  <!-- Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon primary"><i class="bi bi-collection"></i></div>
        <div>
          <div class="stat-label">Total Jobs</div>
          <div class="stat-value"><?= (int)$s['total_jobs'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon success"><i class="bi bi-briefcase-fill"></i></div>
        <div>
          <div class="stat-label">Open Jobs</div>
          <div class="stat-value"><?= (int)$s['open_jobs'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon warning"><i class="bi bi-inbox-fill"></i></div>
        <div>
          <div class="stat-label">Proposals</div>
          <div class="stat-value"><?= (int)$s['total_proposals'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon info"><i class="bi bi-person-check-fill"></i></div>
        <div>
          <div class="stat-label">Hired</div>
          <div class="stat-value"><?= (int)$s['accepted_proposals'] ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Recent Jobs Table -->
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header justify-content-between">
          <span><i class="bi bi-collection me-2"></i>My Recent Jobs</span>
          <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/client/my_jobs.php" class="btn btn-sm btn-outline-secondary">View All</a>
            <a href="<?= BASE_URL ?>/client/post_job.php" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Post Job</a>
          </div>
        </div>
        <div class="card-body p-0">
          <?php if (empty($jobs)): ?>
            <div class="empty-state py-5">
              <i class="bi bi-plus-circle-fill"></i>
              <h5>No jobs posted yet</h5>
              <p>Post your first job and start receiving proposals from top freelancers.</p>
              <a href="<?= BASE_URL ?>/client/post_job.php" class="btn btn-primary mt-1">
                <i class="bi bi-plus-lg me-1"></i>Post a Job
              </a>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th style="padding-left:1.25rem">Job Title</th>
                    <th>Budget</th>
                    <th>Proposals</th>
                    <th>Status</th>
                    <th style="padding-right:1.25rem"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($jobs as $job): ?>
                  <tr>
                    <td style="padding-left:1.25rem">
                      <a href="<?= BASE_URL ?>/client/job_proposals.php?id=<?= $job['id'] ?>"
                         class="fw-600 text-decoration-none" style="color:var(--ink)">
                        <?= e(truncate($job['title'], 42)) ?>
                      </a>
                      <?php if ($job['pending_count'] > 0): ?>
                        <span class="badge ms-1" style="background:var(--gold-2);color:#8A5C00"><?= $job['pending_count'] ?> new</span>
                      <?php endif; ?>
                    </td>
                    <td class="fw-600"><?= money((float)$job['budget']) ?></td>
                    <td>
                      <span class="d-flex align-items-center gap-1">
                        <i class="bi bi-people text-muted" style="font-size:.85rem"></i>
                        <?= (int)$job['proposal_count'] ?>
                      </span>
                    </td>
                    <td><span class="badge status-<?= $job['status'] ?>"><?= ucfirst($job['status']) ?></span></td>
                    <td style="padding-right:1.25rem">
                      <a href="<?= BASE_URL ?>/client/job_proposals.php?id=<?= $job['id'] ?>"
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

    <!-- Right Column -->
    <div class="col-lg-4 d-flex flex-column gap-4">

      <!-- CTA Card -->
      <div class="cta-card">
        <i class="bi bi-rocket-takeoff display-icon"></i>
        <h5>Post a New Job</h5>
        <p>Reach thousands of skilled freelancers ready to work on your project right now.</p>
        <a href="<?= BASE_URL ?>/client/post_job.php" class="btn btn-light fw-700" style="color:var(--accent);font-size:.875rem">
          <i class="bi bi-plus-circle me-1"></i>Post a Job — Free
        </a>
      </div>

      <!-- Quick Links -->
      <div class="card">
        <div class="card-header">
          <i class="bi bi-grid me-2"></i>Quick Actions
        </div>
        <div class="card-body d-flex flex-column gap-2 p-3">
          <?php foreach ([
            [BASE_URL.'/client/my_jobs.php',      'bi-collection',      'Manage My Jobs',      'View and manage all your posted jobs'],
            [BASE_URL.'/client/messages.php',     'bi-chat-dots',       'Messages',            'Chat with your hired freelancers'],
          ] as [$url, $icon, $title, $desc]): ?>
          <a href="<?= $url ?>" class="d-flex align-items-center gap-3 p-2 rounded-2 text-decoration-none"
             style="border:1px solid var(--border);transition:all .18s"
             onmouseover="this.style.borderColor='var(--accent)';this.style.background='var(--accent-2)'"
             onmouseout="this.style.borderColor='var(--border)';this.style.background=''">
            <div style="width:38px;height:38px;border-radius:10px;background:var(--accent-2);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="bi <?= $icon ?>" style="color:var(--accent)"></i>
            </div>
            <div class="min-w-0">
              <div class="fw-600" style="font-size:.875rem;color:var(--ink)"><?= $title ?></div>
              <div style="font-size:.77rem;color:var(--ink-3)"><?= $desc ?></div>
            </div>
            <i class="bi bi-chevron-right ms-auto" style="color:var(--ink-4);font-size:.75rem"></i>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>

</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
