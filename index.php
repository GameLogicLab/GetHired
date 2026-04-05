<?php
require_once __DIR__ . '/includes/auth_check.php';

// Redirect logged-in users to their dashboard
if (is_logged_in()) {
    $dest = current_role() === 'freelancer'
        ? BASE_URL . '/freelancer/dashboard.php'
        : BASE_URL . '/client/dashboard.php';
    header('Location: ' . $dest);
    exit;
}

$pageTitle = 'Welcome';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?> — Find Top Freelancers</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    :root {
      --ink: #0E0F11;
      --ink-2: #3A3D45;
      --ink-3: #7A7F8E;
      --surface: #FFFFFF;
      --surface-2: #F7F8FA;
      --surface-3: #EFF1F5;
      --border: #E3E6ED;
      --accent: #1A6BFF;
      --accent-2: #EDF3FF;
      --gold: #F0A500;
      --gold-2: #FFF8E6;
      --green: #00A878;
      --green-2: #E6F7F3;
      --font-display: 'DM Serif Display', Georgia, serif;
      --font-body: 'DM Sans', system-ui, sans-serif;
    }

    body { 
      margin: 0; 
      font-family: var(--font-body);
    }

    /* ─── HERO SECTION ─────────────────────────────── */
    .hero {
      min-height: 100vh;
      background: linear-gradient(160deg, #0A0F1E 0%, #0E1A3A 50%, #0F2562 100%);
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
    }

    .hero-bg-orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: .35;
    }

    .orb-1 {
      width: 500px;
      height: 500px;
      background: #1A6BFF;
      top: -200px;
      right: -100px;
    }

    .orb-2 {
      width: 300px;
      height: 300px;
      background: #F0A500;
      bottom: -80px;
      left: 100px;
    }

    /* ─── NAV ─────────────────────────────────────── */
    .hero-nav {
      padding: 1rem 2.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: rgba(255, 255, 255, .04);
      border-bottom: 1px solid rgba(255, 255, 255, .08);
      backdrop-filter: blur(12px);
      position: relative;
      z-index: 10;
    }

    .hero-nav .brand {
      font-size: 1.3rem;
      font-weight: 800;
      color: #fff;
      display: flex;
      align-items: center;
      gap: .5rem;
    }

    .brand-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--accent);
      display: inline-block;
    }

    .hero-nav-links {
      display: flex;
      gap: 2rem;
      list-style: none;
      margin: 0;
    }

    .hero-nav-links a {
      color: rgba(255, 255, 255, .8);
      font-size: .95rem;
      font-weight: 500;
      text-decoration: none;
      transition: color .2s;
    }

    .hero-nav-links a:hover {
      color: #fff;
    }

    .hero-nav-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .btn-hero-ghost {
      background: transparent;
      border: none;
      color: rgba(255, 255, 255, .8);
      font-weight: 600;
      font-size: .9rem;
      padding: .5rem 1rem;
      border-radius: 8px;
      transition: all .2s;
    }

    .btn-hero-ghost:hover {
      color: #fff;
      background: rgba(255, 255, 255, .1);
    }

    .btn-hero-primary {
      background: var(--accent);
      color: #fff;
      border: none;
      font-weight: 600;
      padding: .5rem 1.25rem;
      border-radius: 8px;
      font-size: .9rem;
      text-decoration: none;
      transition: all .2s;
    }

    .btn-hero-primary:hover {
      background: #0F5AE0;
      box-shadow: 0 4px 12px rgba(26, 107, 255, .3);
    }

    /* ─── HERO BODY ────────────────────────────────── */
    .hero-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 4rem 1rem;
      padding-top: 3rem;
      position: relative;
      z-index: 5;
    }

    .hero-badge-new {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      background: rgba(240, 165, 0, .15);
      border: 1px solid rgba(240, 165, 0, .3);
      border-radius: 99px;
      padding: .35rem .9rem .35rem .35rem;
      margin-bottom: 1.5rem;
      font-size: .85rem;
    }

    .hero-badge-pill {
      background: var(--gold);
      color: #0E0F11;
      font-size: .7rem;
      font-weight: 700;
      padding: .2rem .6rem;
      border-radius: 99px;
      letter-spacing: .05em;
    }

    .hero-badge-text {
      font-size: .85rem;
      font-weight: 500;
      color: rgba(255, 255, 255, .8);
    }

    .hero h1 {
      font-family: var(--font-display);
      font-size: clamp(2.25rem, 7vw, 3.5rem);
      font-weight: 400;
      color: #fff;
      line-height: 1.1;
      margin-bottom: 1.25rem;
    }

    .hero h1 em {
      font-style: italic;
      color: transparent;
      -webkit-text-stroke: 1.5px rgba(255, 255, 255, .5);
    }

    .hero h1 .accent-text {
      background: linear-gradient(135deg, #60A5FA, #A78BFA);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .hero-sub {
      font-size: 1rem;
      color: rgba(255, 255, 255, .6);
      line-height: 1.7;
      margin-bottom: 2.25rem;
      max-width: 480px;
    }

    .hero-cta-group {
      display: flex;
      gap: .75rem;
      flex-wrap: wrap;
      justify-content: center;
      margin-bottom: 3.25rem;
    }

    .btn-cta-primary {
      background: var(--accent);
      color: #fff;
      border: none;
      font-weight: 600;
      padding: 1rem 1.75rem;
      border-radius: 8px;
      font-size: 1rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      transition: all .2s;
    }

    .btn-cta-primary:hover {
      background: #0F5AE0;
      box-shadow: 0 8px 20px rgba(26, 107, 255, .3);
      transform: translateY(-2px);
    }

    .btn-cta-secondary {
      background: rgba(255, 255, 255, .1);
      border: 1px solid rgba(255, 255, 255, .2);
      color: #fff;
      font-weight: 600;
      padding: 1rem 1.75rem;
      border-radius: 8px;
      font-size: 1rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      transition: all .2s;
    }

    .btn-cta-secondary:hover {
      background: rgba(255, 255, 255, .15);
      border-color: rgba(255, 255, 255, .3);
    }

    /* ─── FREELANCER CARDS ────────────────────────── */
    .hero-cards-strip {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
      max-width: 900px;
      margin: 0 auto;
    }

    .hero-mini-card {
      background: rgba(255, 255, 255, .05);
      border: 1px solid rgba(255, 255, 255, .1);
      border-radius: 12px;
      padding: 1rem;
      color: #fff;
      text-align: center;
      min-width: 140px;
      backdrop-filter: blur(8px);
    }

    .hero-mini-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1.25rem;
      margin: 0 auto 0.5rem;
      color: #fff;
    }

    .hero-mini-name {
      font-weight: 600;
      font-size: .9rem;
      margin-bottom: .25rem;
    }

    .hero-mini-role {
      font-size: .75rem;
      color: rgba(255, 255, 255, .6);
      margin-bottom: .5rem;
    }

    .hero-mini-rate {
      font-weight: 600;
      font-size: .85rem;
      color: var(--gold);
      margin-bottom: .25rem;
    }

    .hero-mini-stars {
      font-size: .8rem;
      color: var(--gold);
    }

    /* ─── STATS BAR ────────────────────────────────── */
    .stats-bar {
      display: flex;
      align-items: center;
      justify-content: space-around;
      padding: 2rem 2.5rem;
      background: var(--surface);
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      flex-wrap: wrap;
      gap: 2rem;
    }

    .stat-item {
      text-align: center;
    }

    .stat-num {
      font-family: var(--font-display);
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--ink);
    }

    .stat-label {
      font-size: .8rem;
      color: var(--ink-3);
      font-weight: 500;
      margin-top: .25rem;
    }

    /* ─── HOW IT WORKS ────────────────────────────── */
    .hiw-section {
      padding: 4rem 2.5rem;
      background: var(--surface-2);
      text-align: center;
    }

    .hiw-section .section-label {
      display: inline-block;
      font-family: var(--font-mono, 'Courier New');
      font-size: .75rem;
      font-weight: 600;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--accent);
      background: var(--accent-2);
      padding: 0.35rem 0.75rem;
      border-radius: 99px;
      margin-bottom: 1rem;
    }

    .hiw-section h2 {
      font-family: var(--font-display);
      font-size: clamp(1.75rem, 4vw, 2rem);
      color: var(--ink);
      line-height: 1.15;
      margin-bottom: 0.5rem;
    }

    .hiw-steps {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 2rem;
      max-width: 1000px;
      margin: 3rem auto 0;
    }

    .hiw-step {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 2rem 1.5rem;
      text-align: center;
      transition: all .2s;
    }

    .hiw-step:hover {
      box-shadow: 0 4px 16px rgba(14, 15, 17, .08);
      transform: translateY(-2px);
    }

    .hiw-step-num {
      width: 48px;
      height: 48px;
      background: var(--accent-2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      color: var(--accent);
      font-size: 1.25rem;
      margin: 0 auto 1rem;
    }

    .hiw-step-title {
      font-weight: 600;
      color: var(--ink);
      margin-bottom: .5rem;
      font-size: 1rem;
    }

    .hiw-step-desc {
      font-size: .85rem;
      color: var(--ink-3);
      line-height: 1.6;
    }

    /* ─── TRUST BAR ────────────────────────────────── */
    .trust-bar {
      background: var(--surface-2);
      border-top: 1px solid var(--border);
      padding: 1.5rem 2.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 2.5rem;
      flex-wrap: wrap;
    }

    .trust-bar-label {
      font-size: .75rem;
      font-weight: 700;
      color: var(--ink-3);
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .trust-logos {
      display: flex;
      gap: 2rem;
      align-items: center;
      opacity: .4;
      filter: grayscale(1);
    }

    .trust-logo {
      font-size: 1.1rem;
      font-weight: 900;
      color: var(--ink);
    }

    /* ─── FOOTER ───────────────────────────────────── */
    footer {
      background: var(--surface);
      border-top: 1px solid var(--border);
      padding: 2rem 1.5rem;
      text-align: center;
      color: var(--ink-3);
      font-size: .85rem;
    }

    footer a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 500;
    }

    footer a:hover {
      text-decoration: underline;
    }

    /* ─── RESPONSIVE ───────────────────────────────── */
    @media (max-width: 768px) {
      .hero-nav {
        padding: .75rem 1.25rem;
      }

      .hero-nav-links {
        display: none;
      }

      .hero-cta-group {
        flex-direction: column;
        gap: 1rem;
      }

      .btn-cta-primary,
      .btn-cta-secondary {
        width: 100%;
        justify-content: center;
      }

      .stats-bar {
        flex-direction: column;
        gap: 1rem;
      }

      .hero-cards-strip {
        gap: 0.75rem;
      }

      .hero-mini-card {
        min-width: 110px;
        padding: .75rem;
        font-size: .85rem;
      }
    }
  </style>
</head>
<body>

<section class="hero">
  <div class="hero-bg-orb orb-1"></div>
  <div class="hero-bg-orb orb-2"></div>

  <!-- NAV -->
  <nav class="hero-nav">
    <div class="brand">
      <span class="brand-dot"></span><?= APP_NAME ?>
    </div>
    <ul class="hero-nav-links">
      <li><a href="#how-it-works">How it Works</a></li>
      <li><a href="#how-it-works">Features</a></li>
      <li><a href="#how-it-works">Pricing</a></li>
    </ul>
    <div class="hero-nav-actions">
      <a href="<?= BASE_URL ?>/login.php" class="btn-hero-ghost">Sign In</a>
      <a href="<?= BASE_URL ?>/register.php" class="btn-hero-primary">Get Started Free →</a>
    </div>
  </nav>

  <!-- HERO BODY -->
  <div class="hero-body">
    <div class="hero-badge-new">
      <span class="hero-badge-pill">NEW</span>
      <span class="hero-badge-text">AI-powered matching is here — try it free</span>
    </div>

    <h1>The <span class="accent-text">smartest</span> way<br>
        to hire <em>freelancers</em></h1>

    <p class="hero-sub">Post a job in 2 minutes. Get vetted proposals the same day. Pay only when you're 100% satisfied.</p>

    <div class="hero-cta-group">
      <a href="<?= BASE_URL ?>/register.php?role=client" class="btn-cta-primary">
        Post a Job — it's free
      </a>
      <a href="<?= BASE_URL ?>/register.php?role=freelancer" class="btn-cta-secondary">
        Find Work →
      </a>
    </div>

    <!-- Freelancer Cards Proof -->
    <div class="hero-cards-strip">
      <div class="hero-mini-card">
        <div class="hero-mini-avatar" style="background: linear-gradient(135deg, #1A6BFF, #60A5FA)">A</div>
        <div class="hero-mini-name">Aisha K.</div>
        <div class="hero-mini-role">UI/UX Designer</div>
        <div class="hero-mini-rate">$75/hr</div>
        <div class="hero-mini-stars">★★★★★</div>
      </div>
      <div class="hero-mini-card">
        <div class="hero-mini-avatar" style="background: linear-gradient(135deg, #00A878, #34D399)">R</div>
        <div class="hero-mini-name">Raj P.</div>
        <div class="hero-mini-role">Full-Stack Dev</div>
        <div class="hero-mini-rate">$90/hr</div>
        <div class="hero-mini-stars">★★★★★</div>
      </div>
      <div class="hero-mini-card">
        <div class="hero-mini-avatar" style="background: linear-gradient(135deg, #F0A500, #FCD34D)">M</div>
        <div class="hero-mini-name">Maya S.</div>
        <div class="hero-mini-role">Copywriter</div>
        <div class="hero-mini-rate">$55/hr</div>
        <div class="hero-mini-stars">★★★★☆</div>
      </div>
      <div class="hero-mini-card">
        <div class="hero-mini-avatar" style="background: linear-gradient(135deg, #A78BFA, #C4B5FD)">J</div>
        <div class="hero-mini-name">Joel T.</div>
        <div class="hero-mini-role">Data Scientist</div>
        <div class="hero-mini-rate">$110/hr</div>
        <div class="hero-mini-stars">★★★★★</div>
      </div>
    </div>
  </div>

  <!-- STATS BAR -->
  <div class="stats-bar">
    <div class="stat-item">
      <div class="stat-num">5,200+</div>
      <div class="stat-label">Verified Freelancers</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">2,400+</div>
      <div class="stat-label">Projects Completed</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">98%</div>
      <div class="stat-label">Client Satisfaction</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">≤ 4hrs</div>
      <div class="stat-label">Avg. First Proposal</div>
    </div>
  </div>

  <!-- HOW IT WORKS -->
  <div class="hiw-section" id="how-it-works">
    <div class="section-label">⚡ Simple Process</div>
    <h2>From idea to hired in<br>under 24 hours</h2>
    <div class="hiw-steps">
      <div class="hiw-step">
        <div class="hiw-step-num">1</div>
        <div class="hiw-step-title">Post Your Job</div>
        <div class="hiw-step-desc">Describe your project, set your budget, and publish in under 2 minutes.</div>
      </div>
      <div class="hiw-step">
        <div class="hiw-step-num">2</div>
        <div class="hiw-step-title">Review Proposals</div>
        <div class="hiw-step-desc">Compare vetted freelancers by skill, reviews, and hourly rate.</div>
      </div>
      <div class="hiw-step">
        <div class="hiw-step-num">3</div>
        <div class="hiw-step-title">Hire & Collaborate</div>
        <div class="hiw-step-desc">Chat, share files, and track milestones — all inside GetHired.</div>
      </div>
      <div class="hiw-step">
        <div class="hiw-step-num">4</div>
        <div class="hiw-step-title">Pay Securely</div>
        <div class="hiw-step-desc">Funds are held safely and released only when you approve the work.</div>
      </div>
    </div>
  </div>

  <!-- TRUST BAR -->
  <div class="trust-bar">
    <div class="trust-bar-label">Trusted by teams at</div>
    <div class="trust-logos">
      <div class="trust-logo">ACME</div>
      <div class="trust-logo">Nexus</div>
      <div class="trust-logo">Orbit</div>
      <div class="trust-logo">Vanta</div>
      <div class="trust-logo">Loop</div>
    </div>
  </div>
</section>

  <!-- FEATURES SECTION -->
  <div style="padding:4rem 2.5rem;background:#fff;text-align:center">
    <div style="display:inline-block;font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);background:var(--accent-2);padding:.35rem .85rem;border-radius:99px;margin-bottom:1rem">✦ Why GetHired</div>
    <h2 style="font-family:var(--font-display);font-size:clamp(1.6rem,4vw,2rem);color:var(--ink);margin-bottom:.5rem">Everything you need to work smarter</h2>
    <p style="color:var(--ink-3);font-size:.9rem;max-width:480px;margin:0 auto 3rem">Built for speed, trust, and results — so you can focus on what matters most.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem;max-width:1000px;margin:0 auto">
      <?php
      $features = [
        ['bi-robot',          'AI Matching',        'Smart algorithms surface the best freelancers for your exact project scope.'],
        ['bi-shield-check',   'Secure Payments',    'Funds held in escrow. Released only when you sign off on the deliverables.'],
        ['bi-chat-dots',      'Real-time Chat',     'Built-in messaging, file sharing, and milestone tracking — all in one place.'],
        ['bi-star-fill',      'Verified Reviews',   'Every review is tied to a completed project. No fake ratings, ever.'],
        ['bi-lightning-charge','Fast Turnaround',    'Average first proposal arrives in under 4 hours from posting.'],
        ['bi-globe',          'Global Talent Pool', 'Access 5,200+ vetted professionals across 80+ skill categories worldwide.'],
      ];
      foreach ($features as [$icon, $title, $desc]):
      ?>
      <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:1.75rem;text-align:left;transition:all .2s"
           onmouseover="this.style.boxShadow='0 8px 24px rgba(14,15,17,.1)';this.style.borderColor='rgba(26,107,255,.3)';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.boxShadow='';this.style.borderColor='var(--border)';this.style.transform=''">
        <div style="width:48px;height:48px;border-radius:13px;background:var(--accent-2);display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
          <i class="bi <?= $icon ?>" style="color:var(--accent);font-size:1.25rem"></i>
        </div>
        <div style="font-weight:700;color:var(--ink);margin-bottom:.4rem;font-size:.95rem"><?= $title ?></div>
        <div style="font-size:.83rem;color:var(--ink-3);line-height:1.6"><?= $desc ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- FINAL CTA SECTION -->
  <div style="padding:4rem 2rem;background:linear-gradient(160deg,#0A0F1E,#0E1F5C);text-align:center;position:relative;overflow:hidden">
    <div style="position:absolute;width:400px;height:400px;border-radius:50%;background:#1A6BFF;filter:blur(80px);opacity:.15;top:-150px;left:50%;transform:translateX(-50%);pointer-events:none"></div>
    <div style="position:relative;z-index:1">
      <h2 style="font-family:var(--font-display);font-size:clamp(1.75rem,5vw,2.5rem);color:#fff;margin-bottom:.75rem;line-height:1.15">
        Ready to get started?
      </h2>
      <p style="color:rgba(255,255,255,.55);font-size:.95rem;margin-bottom:2rem;max-width:420px;margin-left:auto;margin-right:auto">
        Join thousands of clients and freelancers already doing great work together.
      </p>
      <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/register.php?role=client"
           style="background:var(--accent);color:#fff;font-weight:700;padding:.9rem 1.75rem;border-radius:10px;font-size:.95rem;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;transition:all .2s"
           onmouseover="this.style.background='#0F5AE0';this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(26,107,255,.4)'"
           onmouseout="this.style.background='var(--accent)';this.style.transform='';this.style.boxShadow=''">
          <i class="bi bi-briefcase"></i> Post a Job — Free
        </a>
        <a href="<?= BASE_URL ?>/register.php?role=freelancer"
           style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;font-weight:700;padding:.9rem 1.75rem;border-radius:10px;font-size:.95rem;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;transition:all .2s"
           onmouseover="this.style.background='rgba(255,255,255,.15)';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.background='rgba(255,255,255,.1)';this.style.transform=''">
          <i class="bi bi-laptop"></i> Find Work →
        </a>
      </div>
    </div>
  </div>

  <!-- TRUST BAR -->
  <div class="trust-bar">
    <div class="trust-bar-label">Trusted by teams at</div>
    <div class="trust-logos">
      <div class="trust-logo">ACME</div>
      <div class="trust-logo">Nexus</div>
      <div class="trust-logo">Orbit</div>
      <div class="trust-logo">Vanta</div>
      <div class="trust-logo">Loop</div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer style="background:var(--surface);border-top:1px solid var(--border);padding:1.5rem 2.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;font-size:.82rem;color:var(--ink-3)">
  <span>&copy; <?= date('Y') ?> <strong style="color:var(--ink)"><?= APP_NAME ?></strong>. All rights reserved.</span>
  <div style="display:flex;gap:1.5rem">
    <a href="<?= BASE_URL ?>/login.php" style="color:var(--accent);font-weight:600">Sign In</a>
    <a href="<?= BASE_URL ?>/register.php" style="color:var(--accent);font-weight:600">Get Started</a>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
