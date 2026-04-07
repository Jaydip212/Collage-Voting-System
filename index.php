<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

autoUpdateElectionStatus($pdo);

// Fetch active elections
$activeElections = $pdo->query("
    SELECT e.*, d.name as dept_name 
    FROM elections e 
    LEFT JOIN departments d ON e.department_id = d.id 
    WHERE e.status = 'active' 
    ORDER BY e.end_datetime ASC 
    LIMIT 6
")->fetchAll();

// Fetch upcoming elections
$upcomingElections = $pdo->query("
    SELECT e.*, d.name as dept_name 
    FROM elections e 
    LEFT JOIN departments d ON e.department_id = d.id 
    WHERE e.status = 'upcoming' 
    ORDER BY e.start_datetime ASC 
    LIMIT 3
")->fetchAll();

// Announcements
$announcements = $pdo->query("
    SELECT a.*, d.name as dept_name 
    FROM announcements a 
    LEFT JOIN departments d ON a.department_id = d.id 
    WHERE a.is_active = 1 
    ORDER BY a.created_at DESC 
    LIMIT 5
")->fetchAll();

// Stats
$totalStudents  = $pdo->query("SELECT COUNT(*) FROM students WHERE is_active=1")->fetchColumn();
$totalElections = $pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn();
$totalVotes     = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$departments    = getDepartments($pdo);

$pageTitle = 'Home';
$pageDesc  = SITE_TAGLINE;
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── HERO SECTION ──────────────────────────────────────── -->
<section class="hero" style="padding-top:90px">
  <div class="container" style="display:flex;align-items:center;gap:60px;flex-wrap:wrap">
    <div class="hero-content" style="flex:1;min-width:280px">
      <div class="hero-badge animate-fadeIn">
        <span style="width:8px;height:8px;background:#10b981;border-radius:50%;animation:pulse 1.5s infinite;display:inline-block"></span>
        <span data-i18n="welcome">Elections are LIVE now!</span>
      </div>
      <h1 style="margin-bottom:20px">
        <span class="text-gradient">Digital Voting</span><br>
        for a Brighter<br>Democracy 🗳️
      </h1>
      <p style="font-size:1.05rem;color:var(--text-secondary);margin-bottom:32px">
        Participate in fair, secure and transparent elections. Your vote is your voice — exercise it with confidence in our protected digital platform.
      </p>
      <div class="hero-actions">
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-lg">
          <i class="fas fa-user-plus"></i> <span data-i18n="register">Register Now</span>
        </a>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline btn-lg">
          <i class="fas fa-sign-in-alt"></i> <span data-i18n="login">Login</span>
        </a>
      </div>

      <!-- Quick stats row -->
      <div style="display:flex;gap:28px;margin-top:40px;flex-wrap:wrap">
        <?php foreach ([
          ['🎓', number_format($totalStudents), 'Students'],
          ['🗳️', number_format($totalElections), 'Elections'],
          ['✅', number_format($totalVotes), 'Votes Cast'],
        ] as [$icon, $val, $label]): ?>
          <div class="animate-fadeInUp">
            <div style="font-size:1.5rem;font-weight:800;font-family:'Poppins',sans-serif" data-counter="<?= preg_replace('/\D/','',$val) ?>"><?= $val ?></div>
            <div style="font-size:0.78rem;color:var(--text-muted)"><?= $icon ?> <?= $label ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Visual card -->
    <div class="hero-visual" style="flex:1;min-width:260px">
      <div class="floating-card" style="max-width:340px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
          <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#4f46e5,#06b6d4);display:flex;align-items:center;justify-content:center;font-size:1.4rem">🗳️</div>
          <div>
            <div style="font-weight:700;font-size:1rem"><?= SITE_NAME ?></div>
            <div style="font-size:0.75rem;color:var(--success);display:flex;align-items:center;gap:5px">
              <span style="width:7px;height:7px;background:var(--success);border-radius:50%;animation:pulse 1.5s infinite;display:inline-block"></span>
              System Active
            </div>
          </div>
        </div>

        <?php foreach ($activeElections as $i => $e): if ($i >= 3) break; ?>
          <div style="padding:12px;background:var(--bg-glass);border-radius:10px;margin-bottom:8px;border:1px solid var(--border)">
            <div style="font-size:0.82rem;font-weight:600;margin-bottom:4px"><?= htmlspecialchars($e['title']) ?></div>
            <div style="font-size:0.72rem;color:var(--text-muted)">
              <span class="badge badge-success" style="font-size:0.65rem">LIVE</span>
              Ends: <?= date('d M, h:i A', strtotime($e['end_datetime'])) ?>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (empty($activeElections)): ?>
          <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:0.85rem">No active elections at the moment</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ── HOW IT WORKS ───────────────────────────────────────── -->
<section style="padding:80px 5%;position:relative;z-index:1">
  <div class="container">
    <div style="text-align:center;margin-bottom:48px">
      <h2>How It <span class="text-gradient">Works</span></h2>
      <p style="color:var(--text-secondary);font-size:1rem;margin-top:10px">Simple, secure, and transparent in just a few steps</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:24px">
      <?php
      $steps = [
        ['01','fas fa-user-plus','Register','Sign up with your roll number / teacher ID and verify your email.','var(--primary)'],
        ['02','fas fa-search','Browse Elections','View all active elections from your department and college.','var(--accent)'],
        ['03','fas fa-vote-yea','Cast Your Vote','Review candidates, read manifestos, and vote securely.','var(--success)'],
        ['04','fas fa-chart-bar','View Results','Results are published live after the election ends.','var(--warning)'],
      ];
      foreach ($steps as [$num,$icon,$title,$desc,$color]): ?>
        <div class="glass-card" style="padding:28px;text-align:center">
          <div style="width:56px;height:56px;border-radius:50%;background:<?= $color ?>33;border:2px solid <?= $color ?>;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin:0 auto 16px;color:<?= $color ?>">
            <i class="<?= $icon ?>"></i>
          </div>
          <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);letter-spacing:0.1em;margin-bottom:8px">STEP <?= $num ?></div>
          <h4 style="font-size:1rem;margin-bottom:8px"><?= $title ?></h4>
          <p style="font-size:0.82rem;color:var(--text-muted);line-height:1.6"><?= $desc ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── ACTIVE ELECTIONS ───────────────────────────────────── -->
<?php if (!empty($activeElections)): ?>
<section style="padding:0 5% 80px;position:relative;z-index:1">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px">
      <div>
        <h2>Active <span class="text-gradient">Elections</span></h2>
        <p style="color:var(--text-muted);font-size:0.9rem;margin-top:4px">Elections happening right now — don't miss your chance to vote!</p>
      </div>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">
        <i class="fas fa-sign-in-alt"></i> Login to Vote
      </a>
    </div>

    <div class="grid-auto">
      <?php foreach ($activeElections as $election): ?>
        <div class="election-card">
          <div class="election-banner" style="background:linear-gradient(135deg,#4f46e593,#06b6d493)">
            <?php if ($election['banner_image']): ?>
              <img src="<?= BASE_URL ?>/uploads/election_banners/<?= htmlspecialchars($election['banner_image']) ?>" alt="Banner" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <span style="font-size:3rem">🗳️</span>
            <?php endif; ?>
          </div>
          <div class="election-body">
            <span class="badge badge-success election-type-badge">
              <span style="width:6px;height:6px;background:var(--success);border-radius:50%;animation:pulse 1.5s infinite"></span>
              LIVE
            </span>
            <div class="election-title"><?= htmlspecialchars($election['title']) ?></div>
            <div class="election-meta">
              <?php if ($election['dept_name']): ?>
                <span><i class="fas fa-building"></i> <?= htmlspecialchars($election['dept_name']) ?></span>
              <?php else: ?>
                <span><i class="fas fa-globe"></i> All Departments</span>
              <?php endif; ?>
              <span><i class="fas fa-clock"></i> Ends: <?= formatDate($election['end_datetime']) ?></span>
            </div>
            <div data-countdown="<?= $election['end_datetime'] ?>"></div>
          </div>
          <div class="election-footer">
            <a href="<?= BASE_URL ?>/login.php?role=student" class="btn btn-primary btn-sm" style="flex:1;justify-content:center">
              <i class="fas fa-vote-yea"></i> Vote Now
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── ANNOUNCEMENTS ───────────────────────────────────────── -->
<?php if (!empty($announcements)): ?>
<section style="padding:0 5% 80px;position:relative;z-index:1">
  <div class="container">
    <h2 style="margin-bottom:24px">📢 Announcements</h2>
    <div style="display:flex;flex-direction:column;gap:12px">
      <?php foreach ($announcements as $a): ?>
        <div class="glass-card" style="padding:20px;display:flex;align-items:flex-start;gap:16px">
          <div style="width:44px;height:44px;border-radius:10px;background:rgba(79,70,229,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem">📢</div>
          <div>
            <div style="font-weight:700;font-size:0.95rem;margin-bottom:4px"><?= htmlspecialchars($a['title']) ?></div>
            <div style="font-size:0.83rem;color:var(--text-muted);line-height:1.5"><?= htmlspecialchars(substr($a['content'],0,180)) ?><?= strlen($a['content'])>180 ? '...' : '' ?></div>
            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:8px"><i class="fas fa-clock"></i> <?= timeAgo($a['created_at']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── DEPARTMENTS GRID ────────────────────────────────────── -->
<section style="padding:0 5% 80px;position:relative;z-index:1">
  <div class="container">
    <h2 style="margin-bottom:8px">Departments <span class="text-gradient">Participating</span></h2>
    <p style="color:var(--text-muted);margin-bottom:28px">Elections run for all college departments</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px">
      <?php
      $deptIcons = ['CS'=>'💻','IT'=>'🖧','CE'=>'🏗️','ME'=>'⚙️','ET'=>'📡','MBA'=>'📊','BCA'=>'🖥️','MCA'=>'🎓'];
      foreach ($departments as $dept): ?>
        <div class="glass-card" style="padding:20px;text-align:center;cursor:default">
          <div style="font-size:2rem;margin-bottom:10px"><?= $deptIcons[$dept['code']] ?? '🏫' ?></div>
          <div style="font-size:0.82rem;font-weight:700"><?= htmlspecialchars($dept['name']) ?></div>
          <div style="font-size:0.7rem;color:var(--text-muted);margin-top:4px"><?= htmlspecialchars($dept['code']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── LOGIN PORTAL CARDS ─────────────────────────────────── -->
<section style="padding:0 5% 80px;position:relative;z-index:1">
  <div class="container">
    <h2 style="text-align:center;margin-bottom:8px">Access Your <span class="text-gradient">Portal</span></h2>
    <p style="color:var(--text-muted);text-align:center;margin-bottom:36px">Click on your role to login or register</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px">
      <?php
      $portals = [
        ['🎓','Student Portal','For students to vote and participate in campus elections','login.php?role=student','var(--primary)'],
        ['👩‍🏫','Teacher Portal','For faculty members to participate in teacher elections','login.php?role=teacher','var(--accent)'],
        ['🏛️','HOD / Principal','Department heads to manage and monitor department elections','login.php?role=hod','var(--success)'],
        ['⚙️','Admin Panel','Super administrator to manage the entire system','login.php?role=admin','var(--warning)'],
      ];
      foreach ($portals as [$icon,$title,$desc,$href,$color]): ?>
        <a href="<?= BASE_URL ?>/<?= $href ?>" style="text-decoration:none">
          <div class="glass-card" style="padding:28px;text-align:center;border-top:3px solid <?= $color ?>">
            <div style="font-size:2.5rem;margin-bottom:14px"><?= $icon ?></div>
            <h4 style="margin-bottom:8px;font-size:1rem"><?= $title ?></h4>
            <p style="font-size:0.8rem;color:var(--text-muted);line-height:1.5"><?= $desc ?></p>
            <div style="margin-top:16px;font-size:0.83rem;font-weight:600;color:<?= $color ?>">Login / Register →</div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
