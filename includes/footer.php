<!-- ── FOOTER ── -->
<footer class="app-footer <?= is_logged_in() ? 'sidebar-offset' : '' ?>">
  <div class="container-fluid px-4 d-flex align-items-center justify-content-between gap-2">
    <span>&copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong>. All rights reserved.</span>
    <span style="color:var(--ink-4)">Made with <i class="bi bi-heart-fill" style="color:var(--red);font-size:.7rem"></i></span>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>