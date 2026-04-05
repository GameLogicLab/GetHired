<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('freelancer');

$pdo = db();
$uid = current_user_id();

$stats = $pdo->prepare('
    SELECT
        (SELECT COUNT(*) FROM proposals WHERE freelancer_id = ?) AS total_proposals,
        (SELECT COUNT(*) FROM proposals WHERE freelancer_id = ? AND status = "accepted") AS accepted,
        (SELECT COUNT(*) FROM proposals WHERE freelancer_id = ? AND status = "pending")  AS pending,
        (SELECT COALESCE(SUM(bid_amount),0) FROM proposals WHERE freelancer_id = ? AND status = "accepted") AS earnings
');
$stats->execute([$uid, $uid, $uid, $uid]);
$s = $stats->fetch();

$profStmt = $pdo->prepare('SELECT * FROM freelancer_profiles WHERE user_id = ?');
$profStmt->execute([$uid]);
$profile = $profStmt->fetch();

$fields    = ['title', 'skills', 'experience_level', 'hourly_rate', 'bio'];
$filled    = count(array_filter($fields, fn($f) => !empty($profile[$f])));
$completion = $profile ? (int)(($filled / count($fields)) * 100) : 0;

$jobs = $pdo->query('
    SELECT j.*, u.full_name AS client_name,
           (SELECT COUNT(*) FROM proposals WHERE job_id = j.id) AS proposal_count
    FROM   jobs j
    JOIN   users u ON u.id = j.client_id
    WHERE  j.status = "open"
    ORDER  BY j.created_at DESC
    LIMIT  6
')->fetchAll();

$myProposals = $pdo->prepare('
    SELECT p.*, j.title AS job_title, j.budget
    FROM   proposals p
    JOIN   jobs j ON j.id = p.job_id
    WHERE  p.freelancer_id = ?
    ORDER  BY p.created_at DESC
    LIMIT  5
');
$myProposals->execute([$uid]);
$proposals = $myProposals->fetchAll();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_freelancer.php'; ?>

<div class="page-wrapper">
<div class="page-content">
  <?= render_flash() ?>

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <h1>Good <?= (date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening')) ?>, <?= e(explode(' ', $_SESSION['full_name'])[0]) ?> 👋</h1>
      <p>Here's your freelance activity at a glance.</p>
    </div>
    <a href="<?= BASE_URL ?>/freelancer/browse_jobs.php" class="btn btn-primary">
      <i class="bi bi-search me-1"></i> Browse Jobs
    </a>
  </div>

  <!-- Profile Completion -->
  <?php if ($completion < 100): ?>
  <div class="card mb-4" style="border-color:rgba(26,107,255,.2);background:var(--accent-2)">
    <div class="card-body p-3 d-flex align-items-center gap-3">
      <div style="width:44px;height:44px;border-radius:12px;background:var(--accent);display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="bi bi-person-lines-fill" style="color:#fff;font-size:1.1rem"></i>
      </div>
      <div class="flex-grow-1">
        <div class="fw-700" style="font-size:.9rem;color:var(--ink);margin-bottom:.3rem">
          Complete your profile — <?= $completion ?>% done
        </div>
        <div class="progress" style="height:6px">
          <div class="progress-bar" style="width:<?= $completion ?>%"></div>
        </div>
        <div style="font-size:.77rem;color:var(--ink-3);margin-top:.3rem">Clients are 3× more likely to hire fully set-up freelancers.</div>
      </div>
      <a href="<?= BASE_URL ?>/freelancer/profile.php" class="btn btn-sm btn-primary flex-shrink-0">Complete</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon primary"><i class="bi bi-send-fill"></i></div>
        <div>
          <div class="stat-label">Proposals Sent</div>
          <div class="stat-value"><?= (int)$s['total_proposals'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
        <div>
          <div class="stat-label">Won</div>
          <div class="stat-value"><?= (int)$s['accepted'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
        <div>
          <div class="stat-label">Pending</div>
          <div class="stat-value"><?= (int)$s['pending'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon info"><i class="bi bi-currency-dollar"></i></div>
        <div>
          <div class="stat-label">Earnings</div>
          <div class="stat-value" style="font-size:1.3rem"><?= money((float)$s['earnings']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Available Jobs -->
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header justify-content-between">
          <span><i class="bi bi-briefcase me-2"></i>Available Jobs</span>
          <a href="<?= BASE_URL ?>/freelancer/browse_jobs.php" class="btn btn-sm btn-outline-secondary">Browse All</a>
        </div>
        <div class="card-body p-3">
          <?php if (empty($jobs)): ?>
            <div class="empty-state py-4">
              <i class="bi bi-briefcase-fill"></i>
              <h5>No jobs available yet</h5>
              <p>Check back soon — new jobs are posted every day.</p>
            </div>
          <?php else: ?>
            <div class="d-flex flex-column gap-2">
              <?php foreach ($jobs as $job): ?>
              <a href="<?= BASE_URL ?>/freelancer/job_details.php?id=<?= $job['id'] ?>"
                 class="text-decoration-none d-flex align-items-center gap-3 p-3 rounded-2"
                 style="border:1px solid var(--border);transition:all .18s;background:var(--surface)"
                 onmouseover="this.style.borderColor='var(--accent)';this.style.background='var(--accent-2)'"
                 onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--surface)'">
                <div style="width:42px;height:42px;border-radius:11px;background:var(--accent-2);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <i class="bi bi-briefcase" style="color:var(--accent);font-size:1rem"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                  <div class="fw-700" style="font-size:.875rem;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?= e($job['title']) ?>
                  </div>
                  <div style="font-size:.77rem;color:var(--ink-3);margin-top:.15rem">
                    <span class="me-2"><i class="bi bi-wallet2 me-1"></i><?= money((float)$job['budget']) ?></span>
                    <span><i class="bi bi-people me-1"></i><?= (int)$job['proposal_count'] ?> proposals</span>
                  </div>
                </div>
                <span class="badge status-open flex-shrink-0">Open</span>
              </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- My Proposals -->
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header justify-content-between">
          <span><i class="bi bi-send me-2"></i>My Proposals</span>
          <a href="<?= BASE_URL ?>/freelancer/my_proposals.php" class="btn btn-sm btn-outline-secondary">View All</a>
        </div>
        <div class="card-body p-3">
          <?php if (empty($proposals)): ?>
            <div class="empty-state py-4">
              <i class="bi bi-send-fill"></i>
              <h5>No proposals yet</h5>
              <p>Browse jobs and send your first proposal today!</p>
              <a href="<?= BASE_URL ?>/freelancer/browse_jobs.php" class="btn btn-sm btn-outline-primary mt-1">Browse Jobs</a>
            </div>
          <?php else: ?>
            <div class="d-flex flex-column gap-2">
              <?php foreach ($proposals as $p): ?>
              <div class="d-flex align-items-start gap-3 p-2 rounded-2" style="border:1px solid var(--border)">
                <div style="width:8px;height:8px;border-radius:50%;background:<?= $p['status']==='accepted'?'var(--green)':($p['status']==='rejected'?'var(--red)':'var(--gold)') ?>;flex-shrink:0;margin-top:.45rem"></div>
                <div class="flex-grow-1 min-w-0">
                  <div class="fw-600" style="font-size:.84rem;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?= e(truncate($p['job_title'], 40)) ?>
                  </div>
                  <div style="font-size:.76rem;color:var(--ink-3);margin-top:.1rem">
                    Bid: <?= money((float)$p['bid_amount']) ?> &middot; <?= time_ago($p['created_at']) ?>
                  </div>
                </div>
                <span class="badge status-<?= $p['status'] ?> flex-shrink-0"><?= ucfirst($p['status']) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Profile Tip Card -->
      <?php if ($completion < 100): ?>
      <div class="cta-card mt-4" style="background:linear-gradient(135deg,#00A878,#00c49f)">
        <i class="bi bi-person-badge display-icon"></i>
        <h5>Boost your visibility</h5>
        <p>A complete profile gets 3× more invitations from clients.</p>
        <a href="<?= BASE_URL ?>/freelancer/profile.php" class="btn btn-light fw-700" style="color:var(--green);font-size:.875rem">
          <i class="bi bi-pencil me-1"></i>Edit Profile
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
