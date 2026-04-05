<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('client');

$pdo = db();
$uid = current_user_id();

// Get proposal details
$proposalId = (int)($_GET['proposal'] ?? 0);
if ($proposalId <= 0) {
    header('Location: ' . BASE_URL . '/client/my_jobs.php');
    exit;
}

$proposalStmt = $pdo->prepare('
    SELECT p.*, j.title as job_title, u.full_name as freelancer_name, u.id as freelancer_id
    FROM proposals p
    JOIN jobs j ON p.job_id = j.id
    JOIN users u ON p.freelancer_id = u.id
    WHERE p.id = ? AND j.client_id = ? AND p.status = "accepted"
');
$proposalStmt->execute([$proposalId, $uid]);
$proposal = $proposalStmt->fetch();

if (!$proposal) {
    header('Location: ' . BASE_URL . '/client/my_jobs.php');
    exit;
}

// Check if review already exists
$existingReviewStmt = $pdo->prepare('SELECT id FROM reviews WHERE proposal_id = ?');
$existingReviewStmt->execute([$proposalId]);
if ($existingReviewStmt->fetch()) {
    flash('info', 'A review has already been submitted for this project.');
    header('Location: ' . BASE_URL . '/client/my_jobs.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        $error = 'Invalid security token.';
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $reviewText = trim($_POST['review_text'] ?? '');
        
        if ($rating < 1 || $rating > 5) {
            $error = 'Please select a rating between 1 and 5 stars.';
        } else {
            // Insert review
            $insertStmt = $pdo->prepare('
                INSERT INTO reviews (proposal_id, client_id, freelancer_id, rating, review_text)
                VALUES (?, ?, ?, ?, ?)
            ');
            $insertStmt->execute([$proposalId, $uid, $proposal['freelancer_id'], $rating, $reviewText]);
            
            // Mark proposal as completed/closed
            $updateStmt = $pdo->prepare('UPDATE proposals SET status = "completed" WHERE id = ?');
            $updateStmt->execute([$proposalId]);
            
            flash('success', 'Review submitted! Thank you for your feedback.');
            header('Location: ' . BASE_URL . '/client/my_jobs.php');
            exit;
        }
    }
}

$pageTitle = 'Leave a Review';
$activePage = 'my_jobs';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_client.php'; ?>

<div class="page-wrapper">
<div class="page-content">
  <div class="container-fluid p-4">
    
    <div class="row justify-content-center">
      <div class="col-lg-8">
        
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white">
            <h4 class="mb-0">Leave a Review</h4>
            <p class="text-muted mb-0">Review the freelancer's work on: <?= e($proposal['job_title']) ?></p>
          </div>
          <div class="card-body">
            
            <!-- Freelancer Info -->
            <div class="d-flex align-items-center mb-4 p-3 bg-light rounded">
              <div class="avatar-placeholder rounded-circle me-3"><?= strtoupper(substr($proposal['freelancer_name'], 0, 1)) ?></div>
              <div>
                <h6 class="mb-0"><?= e($proposal['freelancer_name']) ?></h6>
                <small class="text-muted">Freelancer</small>
              </div>
            </div>
            
            <?php if (isset($error)): ?>
              <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
              <?= csrf_field() ?>
              
              <!-- Rating -->
              <div class="mb-4">
                <label class="form-label fw-500">Rating <span class="text-danger">*</span></label>
                <div class="d-flex gap-2 mb-2">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" class="btn <?= $i <= 3 ? 'btn-warning' : 'btn-outline-warning' ?> rating-btn" data-rating="<?= $i ?>">
                      <i class="bi bi-star<?= $i <= 3 ? '-fill' : '' ?>"></i>
                    </button>
                  <?php endfor; ?>
                </div>
                <small class="text-muted">Click to rate from 1 to 5 stars</small>
                <input type="hidden" name="rating" id="ratingValue" value="3" required>
              </div>
              
              <!-- Review Text -->
              <div class="mb-4">
                <label for="review_text" class="form-label fw-500">Review (Optional)</label>
                <textarea class="form-control" id="review_text" name="review_text" rows="4" 
                          placeholder="Share your experience working with this freelancer..."></textarea>
                <small class="text-muted">Tell others about your experience (optional but appreciated)</small>
              </div>
              
              <!-- Guidelines -->
              <div class="alert alert-info mb-4">
                <h6 class="alert-heading">Review Guidelines:</h6>
                <ul class="mb-0 small">
                  <li>Be honest and constructive in your feedback</li>
                  <li>Focus on the work quality and communication</li>
                  <li>Help other clients make informed decisions</li>
                  <li>Reviews cannot be modified after submission</li>
                </ul>
              </div>
              
              <!-- Submit Buttons -->
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-check-circle me-2"></i>Submit Review
                </button>
                <a href="<?= BASE_URL ?>/client/my_jobs.php" class="btn btn-secondary">
                  <i class="bi bi-x-circle me-2"></i>Cancel
                </a>
              </div>
            </form>
            
          </div>
        </div>
        
      </div>
    </div>
    
  </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const ratingBtns = document.querySelectorAll('.rating-btn');
  const ratingValue = document.getElementById('ratingValue');
  
  ratingBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const rating = parseInt(this.dataset.rating);
      ratingValue.value = rating;
      
      // Update button states
      ratingBtns.forEach((b, index) => {
        const icon = b.querySelector('i');
        if (index < rating) {
          b.classList.remove('btn-outline-warning');
          b.classList.add('btn-warning');
          icon.className = 'bi bi-star-fill';
        } else {
          b.classList.remove('btn-warning');
          b.classList.add('btn-outline-warning');
          icon.className = 'bi bi-star';
        }
      });
    });
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
