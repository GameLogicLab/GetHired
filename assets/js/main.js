/**
 * GetHired — Main JavaScript
 * Vanilla JS only. No jQuery.
 */

'use strict';

/* ── Loading Overlay ─────────────────────────────────────── */
const overlay = document.getElementById('loadingOverlay');
const showLoader = () => overlay?.classList.remove('d-none');
const hideLoader = () => overlay?.classList.add('d-none');

/* ── Toggle Password Visibility ───────────────────────────── */
function togglePassword(btn) {
  const input = btn?.closest('.input-group')?.querySelector('input[type="password"], input[type="text"]');
  const icon = btn?.querySelector('i');
  if (!input || !icon) return;
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'bi bi-eye';
  }
}

/* ── Sidebar Toggle ──────────────────────────────────────── */
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar       = document.getElementById('mainSidebar');

if (sidebarToggle && sidebar) {
  sidebarToggle.addEventListener('click', () => {
    const isMobile = window.innerWidth < 992;
    if (isMobile) {
      sidebar.classList.toggle('show');
    } else {
      document.body.classList.toggle('sidebar-collapsed');
    }
  });

  // Close sidebar on outside click (mobile)
  document.addEventListener('click', (e) => {
    if (window.innerWidth < 992
        && sidebar.classList.contains('show')
        && !sidebar.contains(e.target)
        && e.target !== sidebarToggle
        && !sidebarToggle.contains(e.target)) {
      sidebar.classList.remove('show');
    }
  });
}

/* ── Password Strength ───────────────────────────────────── */
const pwInput = document.getElementById('password');
const pwBar   = document.getElementById('pwStrengthBar');
const pwLabel = document.getElementById('pwStrengthLabel');

if (pwInput && pwBar) {
  pwInput.addEventListener('input', () => {
    const val = pwInput.value;
    let score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
      { w: '25%', bg: '#ef4444', label: 'Weak' },
      { w: '50%', bg: '#f59e0b', label: 'Fair' },
      { w: '75%', bg: '#3b82f6', label: 'Good' },
      { w: '100%',bg: '#10b981', label: 'Strong' },
    ];
    const lvl = levels[score - 1] || levels[0];

    pwBar.style.width      = val.length ? lvl.w   : '0';
    pwBar.style.background = val.length ? lvl.bg  : '';
    if (pwLabel) pwLabel.textContent = val.length ? lvl.label : '';
  });
}

/* ── Client-side Form Validation ────────────────────────── */
(function () {
  const forms = document.querySelectorAll('.needs-validation');
  forms.forEach(form => {
    form.addEventListener('submit', (e) => {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      } else {
        // Show loader on valid submit (skip AJAX forms)
        if (!form.dataset.ajax) showLoader();
      }
      form.classList.add('was-validated');
    });
  });
})();

/* ── Confirm Delete Modal ────────────────────────────────── */
document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-confirm]');
  if (!btn) return;

  e.preventDefault();
  const msg  = btn.dataset.confirm || 'Are you sure?';
  const href = btn.dataset.href;
  const form = btn.closest('form');

  // Simple inline modal
  const existing = document.getElementById('confirmModal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.innerHTML = `
    <div class="modal fade" id="confirmModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Confirm Action</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">${msg}</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmOk">Yes, proceed</button>
          </div>
        </div>
      </div>
    </div>`;
  document.body.appendChild(modal.firstElementChild);

  const bsModal = new bootstrap.Modal(document.getElementById('confirmModal'));
  bsModal.show();

  // Handle the confirmation
  document.getElementById('confirmOk').addEventListener('click', () => {
    if (form) {
      // If it's a form button, submit the form
      form.submit();
    } else if (href && href !== '#' && href !== 'undefined') {
      // If it's a link, navigate to the href
      window.location.href = href;
    }
    bsModal.hide();
  });
});

/* ── AJAX: Submit Proposal ───────────────────────────────── */
const proposalForm = document.getElementById('proposalForm');
const proposalFeedback = document.getElementById('proposalFeedback');

if (proposalForm) {
  proposalForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    proposalForm.classList.add('was-validated');
    if (!proposalForm.checkValidity()) return;

    showLoader();
    const fd = new FormData(proposalForm);

    try {
      const res  = await fetch(proposalForm.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();

      hideLoader();

      if (proposalFeedback) {
        proposalFeedback.className = `alert ${data.success ? 'alert-success' : 'alert-danger'} mt-3`;
        proposalFeedback.textContent = data.message;
        proposalFeedback.classList.remove('d-none');
      }

      if (data.success) {
        proposalForm.reset();
        proposalForm.classList.remove('was-validated');
        // Disable submit button to prevent duplicates
        const btn = proposalForm.querySelector('[type="submit"]');
        if (btn) {
          btn.disabled = true;
          btn.textContent = 'Proposal Submitted';
        }
      }
    } catch (err) {
      hideLoader();
      if (proposalFeedback) {
        proposalFeedback.className = 'alert alert-danger mt-3';
        proposalFeedback.textContent = 'Network error. Please try again.';
        proposalFeedback.classList.remove('d-none');
      }
    }
  });
}

/* ── AJAX: Accept / Reject Proposal ─────────────────────── */
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-action]');
  if (!btn || !['accept', 'reject'].includes(btn.dataset.action)) return;

  const action      = btn.dataset.action;
  const proposalId  = btn.dataset.id;
  const csrfToken   = document.querySelector('meta[name="csrf-token"]')?.content || '';

  showLoader();

  try {
    const fd = new FormData();
    fd.append('action',      action);
    fd.append('proposal_id', proposalId);
    fd.append('csrf_token',  csrfToken);

    const res  = await fetch(window.proposalActionUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();

    hideLoader();

    if (data.success) {
      // Re-render the row/card without full reload
      const row = btn.closest('[data-proposal-row]');
      if (row) {
        row.querySelector('[data-status-badge]').textContent = action === 'accept' ? 'Accepted' : 'Rejected';
        row.querySelector('[data-status-badge]').className   = `badge status-${action === 'accept' ? 'accepted' : 'rejected'} data-status-badge`;
        row.querySelectorAll('[data-action]').forEach(b => b.remove());
      }
      showToast(data.message, 'success');
    } else {
      showToast(data.message, 'danger');
    }
  } catch {
    hideLoader();
    showToast('Network error. Please try again.', 'danger');
  }
});

/* ── Toast Notification ──────────────────────────────────── */
function showToast(message, type = 'success') {
  const container = document.getElementById('toastContainer') || (() => {
    const c = document.createElement('div');
    c.id = 'toastContainer';
    c.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    c.style.zIndex = 9999;
    document.body.appendChild(c);
    return c;
  })();

  const t = document.createElement('div');
  t.className = `toast align-items-center text-bg-${type} border-0 show`;
  t.setAttribute('role', 'alert');
  t.innerHTML = `
    <div class="d-flex">
      <div class="toast-body fw-semibold">${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
  container.appendChild(t);

  const bsToast = new bootstrap.Toast(t, { delay: 4000 });
  bsToast.show();
  t.addEventListener('hidden.bs.toast', () => t.remove());
}

/* ── Search: live job filter on browse page ─────────────── */
const jobSearch = document.getElementById('jobSearch');
if (jobSearch) {
  jobSearch.addEventListener('input', () => {
    const q    = jobSearch.value.toLowerCase().trim();
    const rows = document.querySelectorAll('[data-job-card]');
    let visible = 0;

    rows.forEach(card => {
      const txt = card.textContent.toLowerCase();
      const show = !q || txt.includes(q);
      card.closest('[data-job-col]').style.display = show ? '' : 'none';
      if (show) visible++;
    });

    const noResults = document.getElementById('noJobsMsg');
    if (noResults) noResults.classList.toggle('d-none', visible > 0);
  });
}
