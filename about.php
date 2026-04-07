<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Stats for about page
$totalStudents  = $pdo->query("SELECT COUNT(*) FROM students WHERE is_active=1")->fetchColumn();
$totalElections = $pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn();
$totalVotes     = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$totalDepts     = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

$pageTitle = 'About Us';
$pageDesc  = 'Learn about the Haribhai V Desai college pune-02 Engineering Digital Voting System — secure, transparent and modern.';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── PAGE HERO ────────────────────────────────────────────── -->
<section style="padding:120px 5% 60px;position:relative;z-index:1;text-align:center">
  <div style="max-width:720px;margin:0 auto">
    <div class="hero-badge animate-fadeIn" style="display:inline-flex;margin-bottom:20px">
      <span style="width:8px;height:8px;background:#10b981;border-radius:50%;animation:pulse 1.5s infinite;display:inline-block"></span>
      <span>Empowering Student Democracy Since 2024</span>
    </div>
    <h1 class="text-gradient animate-fadeInUp" style="font-size:clamp(2rem,5vw,3.2rem);margin-bottom:20px">
      About Our Voting Platform
    </h1>
    <p style="font-size:1.05rem;color:var(--text-secondary);line-height:1.8" class="animate-fadeInUp">
      A secure, transparent, and modern digital election management system built for
      <strong style="color:var(--text-primary)"><?= SITE_NAME ?></strong> — allowing students,
      teachers, and departments to participate in fair campus elections from anywhere.
    </p>
  </div>
</section>

<!-- ── STATS STRIP ───────────────────────────────────────────── -->
<section style="padding:0 5% 60px;position:relative;z-index:1">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px">
      <?php
      $stats = [
        ['🎓', number_format($totalStudents),  'Registered Students', 'var(--primary)'],
        ['🗳️', number_format($totalElections), 'Elections Held',      'var(--accent)'],
        ['✅', number_format($totalVotes),      'Votes Cast',          'var(--success)'],
        ['🏛️', number_format($totalDepts),      'Departments',         'var(--warning)'],
      ];
      foreach ($stats as [$icon, $val, $label, $color]): ?>
        <div class="glass-card animate-fadeInUp" style="padding:28px;text-align:center;border-top:3px solid <?= $color ?>">
          <div style="font-size:2rem;margin-bottom:10px"><?= $icon ?></div>
          <div style="font-size:2rem;font-weight:800;font-family:'Poppins',sans-serif;color:<?= $color ?>"
               data-counter="<?= preg_replace('/\D/','',$val) ?>"><?= $val ?></div>
          <div style="font-size:0.82rem;color:var(--text-muted);margin-top:6px"><?= $label ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── MISSION & VISION ──────────────────────────────────────── -->
<section style="padding:0 5% 80px;position:relative;z-index:1">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:28px">

      <!-- Mission -->
      <div class="glass-card" style="padding:36px;border-left:4px solid var(--primary)">
        <div style="width:56px;height:56px;border-radius:14px;background:rgba(79,70,229,0.15);display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin-bottom:20px">🎯</div>
        <h3 style="margin-bottom:14px">Our <span class="text-gradient">Mission</span></h3>
        <p style="color:var(--text-secondary);line-height:1.8;font-size:0.92rem">
          To provide every student and faculty member of <?= SITE_NAME ?> with a reliable, 
          accessible, and corruption-free digital platform to exercise their democratic rights — 
          making campus governance transparent, fair, and participatory.
        </p>
      </div>

      <!-- Vision -->
      <div class="glass-card" style="padding:36px;border-left:4px solid var(--accent)">
        <div style="width:56px;height:56px;border-radius:14px;background:rgba(6,182,212,0.15);display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin-bottom:20px">🔭</div>
        <h3 style="margin-bottom:14px">Our <span class="text-gradient">Vision</span></h3>
        <p style="color:var(--text-secondary);line-height:1.8;font-size:0.92rem">
          To be the gold standard for college-level digital democracy — extending this platform 
          to encompass multi-college elections, alumni participation, and real-time analytics that 
          inspire broader civic engagement beyond the campus walls.
        </p>
      </div>

    </div>
  </div>
</section>

<!-- ── KEY FEATURES ──────────────────────────────────────────── -->
<section style="padding:0 5% 80px;position:relative;z-index:1">
  <div class="container">
    <div style="text-align:center;margin-bottom:48px">
      <h2>Why Choose <span class="text-gradient">Our System?</span></h2>
      <p style="color:var(--text-secondary);margin-top:10px">Built with industry-grade security and a student-first UX</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:22px">
      <?php
      $features = [
        ['🔐','OTP 2-Factor Auth',        'Every login and vote is protected by a time-limited one-time password.',   'var(--primary)'],
        ['🛡️','CSRF & CAPTCHA Protection','All forms use CSRF tokens and math CAPTCHA to block bots & attacks.',      'var(--danger)'],
        ['📱','Device Fingerprinting',     'Prevents duplicate votes from the same device, even in incognito mode.',   'var(--accent)'],
        ['📊','Real-Time Results',         'Live vote counts update every 5 seconds using our JSON API endpoint.',     'var(--success)'],
        ['🏆','Winner Animations',         'Beautiful winner cards with crown animations for result announcements.',   'var(--warning)'],
        ['🎓','Participation Certificate', 'Students receive a downloadable certificate for voting participation.',    'var(--primary)'],
        ['🌙','Dark / Light Mode',         'Comfortable viewing in any lighting condition with a single click toggle.','var(--accent)'],
        ['🔄','Auto Status Updates',       'Elections automatically move Upcoming → Active → Completed on schedule.', 'var(--success)'],
      ];
      foreach ($features as [$icon, $title, $desc, $color]): ?>
        <div class="glass-card animate-fadeInUp" style="padding:24px">
          <div style="width:48px;height:48px;border-radius:12px;background:<?= $color ?>22;border:1.5px solid <?= $color ?>55;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:16px"><?= $icon ?></div>
          <h4 style="font-size:0.95rem;margin-bottom:8px;color:<?= $color ?>"><?= $title ?></h4>
          <p style="font-size:0.82rem;color:var(--text-muted);line-height:1.6"><?= $desc ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── HOW IT WORKS (TIMELINE) ──────────────────────────────── -->
<section style="padding:0 5% 80px;position:relative;z-index:1">
  <div class="container">
    <div style="text-align:center;margin-bottom:48px">
      <h2>Election <span class="text-gradient">Lifecycle</span></h2>
      <p style="color:var(--text-secondary);margin-top:10px">From creation to result — fully automated</p>
    </div>
    <div style="max-width:700px;margin:0 auto;position:relative">
      <!-- Vertical line -->
      <div style="position:absolute;left:28px;top:0;bottom:0;width:2px;background:linear-gradient(to bottom,var(--primary),var(--accent),var(--success));border-radius:4px"></div>
      <?php
      $timeline = [
        ['⚙️', 'Admin Creates Election',   'Admin sets title, type, department, start/end dates and uploads a banner.',              'var(--primary)'],
        ['👤', 'Candidates Nominated',     'HOD or Admin approves candidate nominations with photo and manifesto.',                   'var(--accent)'],
        ['🔔', 'Voting Opens Automatically','System auto-transitions election to Active status at the configured start time.',        'var(--warning)'],
        ['🗳️', 'Students & Teachers Vote', 'Eligible users receive OTP verification, then cast their secure, encrypted votes.',      'var(--success)'],
        ['📊', 'Results Published',        'Admin publishes results — live leaderboard, pie chart & winner card become visible.',     'var(--primary)'],
        ['🎓', 'Certificates Issued',      'Participating voters can instantly download a personalised participation certificate.',  'var(--accent)'],
      ];
      foreach ($timeline as $i => [$icon, $title, $desc, $color]): ?>
        <div style="display:flex;gap:24px;margin-bottom:32px;align-items:flex-start">
          <div style="width:56px;height:56px;border-radius:50%;background:<?= $color ?>22;border:2px solid <?= $color ?>;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;position:relative;z-index:1;background:var(--bg-card)"><?= $icon ?></div>
          <div class="glass-card" style="flex:1;padding:20px">
            <div style="font-size:0.72rem;font-weight:700;color:<?= $color ?>;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px">Step <?= $i+1 ?></div>
            <h4 style="font-size:0.95rem;margin-bottom:6px"><?= $title ?></h4>
            <p style="font-size:0.82rem;color:var(--text-muted);line-height:1.6;margin:0"><?= $desc ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── TEAM / DEVELOPER ──────────────────────────────────────── -->
<section style="padding:0 5% 80px;position:relative;z-index:1">
  <div class="container">
    <div style="text-align:center;margin-bottom:48px">
      <h2>Meet the <span class="text-gradient">Team</span></h2>
      <p style="color:var(--text-secondary);margin-top:10px">Final Year Project 2026 — <?= SITE_NAME ?></p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:22px;max-width:900px;margin:0 auto">
      <?php
      $team = [
        ['👨‍💻', 'Project Lead / Developer',  'Full-stack development, database design, security implementation',  'var(--primary)'],
        ['👩‍🎨', 'UI/UX Designer',             'Glassmorphism design system, animations, responsive layouts',        'var(--accent)'],
        ['👨‍🏫', 'Faculty Guide',              'Project guidance, requirement analysis, review & validation',        'var(--success)'],
        ['🏛️',  'Department / Institution',   SITE_NAME . ' — Providing the platform and use-case',                'var(--warning)'],
      ];
      foreach ($team as [$avatar, $role, $desc, $color]): ?>
        <div class="glass-card" style="padding:28px;text-align:center">
          <div style="width:72px;height:72px;border-radius:50%;background:<?= $color ?>22;border:2px solid <?= $color ?>;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px"><?= $avatar ?></div>
          <h4 style="font-size:0.9rem;margin-bottom:8px;color:<?= $color ?>"><?= $role ?></h4>
          <p style="font-size:0.78rem;color:var(--text-muted);line-height:1.6"><?= $desc ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── TECH STACK ────────────────────────────────────────────── -->
<section style="padding:0 5% 80px;position:relative;z-index:1">
  <div class="container">
    <div style="text-align:center;margin-bottom:36px">
      <h2>Built With <span class="text-gradient">Modern Tech</span></h2>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:14px;justify-content:center">
      <?php
      $techs = [
        ['⚡','PHP 8.2','Backend'],
        ['🗃️','MySQL / PDO','Database'],
        ['🎨','CSS3 Glassmorphism','Frontend'],
        ['📜','Vanilla JavaScript','Interactivity'],
        ['📊','Chart.js','Data Visualization'],
        ['🔒','bcrypt + CSRF','Security'],
        ['📱','Responsive Design','Mobile Friendly'],
        ['🖋️','Font Awesome 6','Icons'],
        ['🔤','Inter / Poppins','Typography'],
        ['🖥️','Apache / XAMPP','Web Server'],
      ];
      foreach ($techs as [$icon, $name, $category]): ?>
        <div class="glass-card" style="padding:14px 22px;display:flex;align-items:center;gap:10px;cursor:default">
          <span style="font-size:1.2rem"><?= $icon ?></span>
          <div>
            <div style="font-size:0.85rem;font-weight:600"><?= $name ?></div>
            <div style="font-size:0.7rem;color:var(--text-muted)"><?= $category ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── CTA ──────────────────────────────────────────────────── -->
<section style="padding:0 5% 100px;position:relative;z-index:1">
  <div class="container">
    <div class="glass-card" style="padding:56px;text-align:center;background:linear-gradient(135deg,rgba(79,70,229,0.12),rgba(6,182,212,0.12));border:1px solid rgba(79,70,229,0.25)">
      <div style="font-size:3rem;margin-bottom:16px">🗳️</div>
      <h2 style="margin-bottom:14px">Ready to <span class="text-gradient">Participate?</span></h2>
      <p style="color:var(--text-secondary);margin-bottom:32px;max-width:480px;margin-left:auto;margin-right:auto;line-height:1.8">
        Register today and make your voice heard in the upcoming campus elections.
        Your vote shapes the future of <?= SITE_NAME ?>.
      </p>
      <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-lg">
          <i class="fas fa-user-plus"></i> Register Now
        </a>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline btn-lg">
          <i class="fas fa-sign-in-alt"></i> Login
        </a>
        <a href="<?= BASE_URL ?>/contact.php" class="btn btn-outline btn-lg">
          <i class="fas fa-envelope"></i> Contact Us
        </a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
