<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('freelancer');

$pdo = db();
$uid = current_user_id();

// ── Fetch Reviews Data ────────────────────────────────────
// Get average rating
$avgRatingStmt = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE freelancer_id = ?');
$avgRatingStmt->execute([$uid]);
$ratingData = $avgRatingStmt->fetch();
$averageRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 0;
$totalReviews = $ratingData['total_reviews'];

// Get rating distribution
$ratingDistStmt = $pdo->prepare('
    SELECT rating, COUNT(*) as count 
    FROM reviews 
    WHERE freelancer_id = ? 
    GROUP BY rating 
    ORDER BY rating DESC
');
$ratingDistStmt->execute([$uid]);
$ratingDistribution = $ratingDistStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fill missing ratings with 0
for ($i = 5; $i >= 1; $i--) {
    if (!isset($ratingDistribution[$i])) {
        $ratingDistribution[$i] = 0;
    }
}
krsort($ratingDistribution); // Ensure 5→1 order regardless of DB return order

// Get recent reviews (last 10)
$recentReviewsStmt = $pdo->prepare('
    SELECT r.*, j.title as job_title, u.full_name as client_name, u.avatar as client_avatar
    FROM reviews r
    JOIN proposals p ON r.proposal_id = p.id
    JOIN jobs j ON p.job_id = j.id
    JOIN users u ON r.client_id = u.id
    WHERE r.freelancer_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
');
$recentReviewsStmt->execute([$uid]);
$recentReviews = $recentReviewsStmt->fetchAll();

// Get all reviews for history (pagination)
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$totalReviewsCount = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE freelancer_id = ?');
$totalReviewsCount->execute([$uid]);
$totalCount = $totalReviewsCount->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

$allReviewsStmt = $pdo->prepare('
    SELECT r.*, j.title as job_title, u.full_name as client_name, u.avatar as client_avatar
    FROM reviews r
    JOIN proposals p ON r.proposal_id = p.id
    JOIN jobs j ON p.job_id = j.id
    JOIN users u ON r.client_id = u.id
    WHERE r.freelancer_id = ?
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
');
$allReviewsStmt->execute([$uid, $perPage, $offset]);
$allReviews = $allReviewsStmt->fetchAll();

$pageTitle = 'Reviews';
$activePage = 'reviews';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_freelancer.php'; ?>

<div class="page-wrapper">
<div class="page-content">
  <div class="container-fluid p-4">
    
    <!-- Header Stats -->
    <div class="row mb-4">
      <div class="col-12">
        <h2 class="mb-3">My Reviews</h2>
        
        <div class="row g-3 align-items-stretch">
          <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body text-center">
                <div class="display-4 fw-bold text-primary mb-1"><?= $averageRating ?></div>
                <div class="text-muted small mb-2">Average Rating</div>
                <div class="justify-content-center">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?= $i <= round($averageRating) ? '-fill' : '' ?> text-warning"></i>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body text-center">
                <div class="display-4 fw-bold text-success mb-1"><?= $totalReviews ?></div>
                <div class="text-muted small">Total Reviews</div>
              </div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body text-center">
                <div class="display-4 fw-bold text-info mb-1"><?= $ratingDistribution[5] + $ratingDistribution[4] ?></div>
                <div class="text-muted small">4-5 Star Reviews</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rating Distribution -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white">
            <h6 class="mb-0">Rating Distribution</h6>
          </div>
          <div class="card-body">
            <?php foreach ($ratingDistribution as $stars => $count): ?>
              <div class="d-flex align-items-center mb-2">
                <div class="me-2" style="width: 80px;">
                  <?= $stars ?> 
                  <i class="bi bi-star-fill text-warning"></i>
                </div>
                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                  <div class="progress-bar bg-warning" style="width: <?= $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0 ?>%"></div>
                </div>
                <div class="text-muted small" style="width: 40px; text-align: right;"><?= $count ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white">
            <h6 class="mb-0">Quick Stats</h6>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
              <span>5 Star Reviews</span>
              <span class="badge bg-success"><?= $ratingDistribution[5] ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>4 Star Reviews</span>
              <span class="badge bg-info"><?= $ratingDistribution[4] ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>3 Star Reviews</span>
              <span class="badge bg-warning"><?= $ratingDistribution[3] ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>2 Star Reviews</span>
              <span class="badge bg-danger"><?= $ratingDistribution[2] ?></span>
            </div>
            <div class="d-flex justify-content-between">
              <span>1 Star Reviews</span>
              <span class="badge bg-dark"><?= $ratingDistribution[1] ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Reviews -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Recent Reviews</h6>
            <small class="text-muted">Last 10 reviews</small>
          </div>
          <div class="card-body">
            <?php if (empty($recentReviews)): ?>
              <div class="text-center text-muted py-4">
                <i class="bi bi-star" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="mt-2">No reviews yet. Complete projects to receive reviews!</p>
              </div>
            <?php else: ?>
              <div class="row">
                <?php foreach ($recentReviews as $review): ?>
                  <div class="col-md-6 mb-3">
                    <div class="card border h-100">
                      <div class="card-body">
                        <div class="d-flex align-items-start mb-2">
                          <?php if (!empty($review['client_avatar']) && file_exists(UPLOAD_DIR . $review['client_avatar'])): ?>
                            <img src="<?= UPLOAD_URL . e($review['client_avatar']) ?>" class="avatar-sm rounded-circle me-2" alt="">
                          <?php else: ?>
                            <div class="avatar-sm avatar-placeholder rounded-circle me-2"><?= strtoupper(substr($review['client_name'], 0, 1)) ?></div>
                          <?php endif; ?>
                          <div class="flex-grow-1">
                            <div class="fw-500"><?= e($review['client_name']) ?></div>
                            <div class="small text-muted"><?= date('M j, Y', strtotime($review['created_at'])) ?></div>
                          </div>
                          <div class="text-end">
                            <div class="text-warning">
                              <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>"></i>
                              <?php endfor; ?>
                            </div>
                          </div>
                        </div>
                        <div class="small text-muted mb-2">Project: <?= e($review['job_title']) ?></div>
                        <?php if (!empty($review['review_text'])): ?>
                          <p class="mb-0 small"><?= e($review['review_text']) ?></p>
                        <?php else: ?>
                          <p class="mb-0 small text-muted fst-italic">No comment provided</p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Full Review History -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white">
            <h6 class="mb-0">Review History</h6>
          </div>
          <div class="card-body">
            <?php if (empty($allReviews)): ?>
              <div class="text-center text-muted py-4">
                <i class="bi bi-clock-history" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="mt-2">No review history available</p>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Client</th>
                      <th>Project</th>
                      <th>Rating</th>
                      <th>Review</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($allReviews as $review): ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <?php if (!empty($review['client_avatar']) && file_exists(UPLOAD_DIR . $review['client_avatar'])): ?>
                              <img src="<?= UPLOAD_URL . e($review['client_avatar']) ?>" class="avatar-sm rounded-circle me-2" alt="">
                            <?php else: ?>
                              <div class="avatar-sm avatar-placeholder rounded-circle me-2"><?= strtoupper(substr($review['client_name'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <span><?= e($review['client_name']) ?></span>
                          </div>
                        </td>
                        <td><?= e($review['job_title']) ?></td>
                        <td>
                          <div class="text-warning">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                              <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>"></i>
                            <?php endfor; ?>
                            <span class="text-muted ms-1">(<?= $review['rating'] ?>/5)</span>
                          </div>
                        </td>
                        <td>
                          <?php if (!empty($review['review_text'])): ?>
                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= e($review['review_text']) ?>">
                              <?= e(mb_strlen($review['review_text']) > 50 ? mb_substr($review['review_text'], 0, 50) . '…' : $review['review_text']) ?>
                            </span>
                          <?php else: ?>
                            <span class="text-muted fst-italic">No comment</span>
                          <?php endif; ?>
                        </td>
                        <td><?= date('M j, Y', strtotime($review['created_at'])) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              
              <!-- Pagination -->
              <?php if ($totalPages > 1): ?>
                <nav aria-label="Review pagination">
                  <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                      <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                      </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                      </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                      <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                      </li>
                    <?php endif; ?>
                  </ul>
                </nav>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
