<?php
require_once __DIR__ . '/includes/config.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>404 — Page Not Found · <?= APP_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="error-page">
  <div class="error-code">404</div>
  <h1>Page Not Found</h1>
  <p class="mb-4" style="color:rgba(255,255,255,.7)">Oops! The page you're looking for doesn't exist or has been moved.</p>
  <div class="d-flex gap-3 justify-content-center flex-wrap">
    <a href="<?= BASE_URL ?>/"            class="btn btn-light fw-bold text-primary"><i class="bi bi-house me-2"></i>Go Home</a>
    <a href="javascript:history.back()"  class="btn btn-outline-light fw-bold"><i class="bi bi-arrow-left me-2"></i>Go Back</a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
