<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

autoUpdateElectionStatus($pdo);

$student = getStudentById($pdo, $_SESSION['user_id']);
$deptId  = $student['department_id'];

// Elections available to this student (dept-specific or college-wide)
$elections = $pdo->prepare("
    SELECT e.*, d.name as dept_name,
        COUNT(c.id) as candidate_count,
        (SELECT COUNT(*) FROM votes WHERE election_id=e.id AND voter_type='student' AND voter_id=?) as my_vote
    FROM elections e
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN candidates c ON c.election_id=e.id AND c.status='approved'
    WHERE e.status IN ('active','upcoming','published')
        AND (e.department_id IS NULL OR e.department_id=?)
        AND e.election_type IN ('student','cr','cultural','sports','general')
    GROUP BY e.id
    ORDER BY FIELD(e.status,'active','upcoming','published'), e.start_datetime
");
$elections->execute([$_SESSION['user_id'], $deptId]);
$elections = $elections->fetchAll();

$activeCount   = count(array_filter($elections, fn($e)=>$e['status']==='active'));
$votedCount    = count(array_filter($elections, fn($e)=>$e['my_vote']>0));
$upcomingCount = count(array_filter($elections, fn($e)=>$e['status']==='upcoming'));

// Notifications
$notifs = getUnreadNotifications($pdo,'student',$_SESSION['user_id']);

// Announcements
$announcements = $pdo->query("
    SELECT a.* FROM announcements a 
    WHERE a.is_active=1 AND (a.department_id IS NULL OR a.department_id=$deptId)
    ORDER BY a.created_at DESC LIMIT 4
")->fetchAll();

$pageTitle  = 'Student Dashboard';
$activeMenu = 'dashboard';
$sidebarLinks = [
  ['icon'=>'fas fa-tachometer-alt', 'label'=>'Dashboard',   'href'=>BASE_URL.'/student/index.php',    'key'=>'dashboard'],
  ['icon'=>'fas fa-vote-yea',       'label'=>'Elections',   'href'=>BASE_URL.'/student/elections.php','key'=>'elections'],
  ['icon'=>'fas fa-poll-h',         'label'=>'Results',     'href'=>BASE_URL.'/student/results.php',  'key'=>'results'],
  ['icon'=>'fas fa-user-circle',    'label'=>'My Profile',  'href'=>BASE_URL.'/student/profile.php',  'key'=>'profile'],
  ['icon'=>'fas fa-certificate',    'label'=>'Certificate', 'href'=>BASE_URL.'/student/certificate.php','key'=>'certificate'],
  ['icon'=>'fas fa-bell',           'label'=>'Notifications','href'=>BASE_URL.'/student/notifications.php','key'=>'notifs','badge'=>count($notifs)?count($notifs):null],
];
$roleLabel = 'Student';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<!-- Greeting -->
<div style="margin-bottom:28px">
  <h2 style="font-size:1.4rem;margin-bottom:4px">
    👋 Welcome, <?= htmlspecialchars(explode(' ',$student['full_name'])[0]) ?>!
  </h2>
  <p style="color:var(--text-muted)">
    <?= htmlspecialchars($student['dept_name']) ?> · Year <?= $student['year'] ?> · <?= htmlspecialchars($student['division']?:'') ?>
  </p>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:28px">
  <div class="stat-card">
    <div class="stat-icon stat-icon-red"><i class="fas fa-fire"></i></div>
    <div class="stat-info">
      <div class="stat-value" data-counter="<?= $activeCount ?>"><?= $activeCount ?></div>
      <div class="stat-label">Active Elections</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon stat-icon-green"><i class="fas fa-check-double"></i></div>
    <div class="stat-info">
      <div class="stat-value" data-counter="<?= $votedCount ?>"><?= $votedCount ?></div>
      <div class="stat-label">Votes Cast</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon stat-icon-blue"><i class="fas fa-calendar-alt"></i></div>
    <div class="stat-info">
      <div class="stat-value" data-counter="<?= $upcomingCount ?>"><?= $upcomingCount ?></div>
      <div class="stat-label">Upcoming</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon stat-icon-cyan"><i class="fas fa-bell"></i></div>
    <div class="stat-info">
      <div class="stat-value" data-counter="<?= count($notifs) ?>"><?= count($notifs) ?></div>
      <div class="stat-label">New Notifications</div>
    </div>
  </div>
</div>

<!-- Main Content Grid -->
<div class="grid-2" style="align-items:start">

  <!-- Elections -->
  <div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <h3 style="font-size:1rem"><i class="fas fa-vote-yea" style="color:var(--primary-light)"></i> Your Elections</h3>
      <a href="<?= BASE_URL ?>/student/elections.php" class="btn btn-sm btn-outline">View All</a>
    </div>

    <?php foreach ($elections as $e):
      $isActive  = $e['status'] === 'active';
      $hasVoted  = $e['my_vote'] > 0;
      $isPublished = $e['status'] === 'published';
    ?>
      <div class="glass-card" style="padding:20px;margin-bottom:14px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px">
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:0.95rem;margin-bottom:4px"><?= htmlspecialchars($e['title']) ?></div>
            <div style="font-size:0.78rem;color:var(--text-muted)">
              <?= $e['dept_name'] ?? 'All Departments' ?> · <?= $e['candidate_count'] ?> candidates
            </div>
          </div>
          <?php if ($hasVoted): ?>
            <span class="badge badge-success">✅ Voted</span>
          <?php elseif ($isActive): ?>
            <span class="badge badge-danger" style="animation:pulse 1.5s infinite">🔴 LIVE</span>
          <?php elseif ($e['status']==='upcoming'): ?>
            <span class="badge badge-info">📅 Upcoming</span>
          <?php else: ?>
            <span class="badge badge-purple">Results Out</span>
          <?php endif; ?>
        </div>

        <?php if ($isActive && !$hasVoted): ?>
          <div data-countdown="<?= $e['end_datetime'] ?>"></div>
          <a href="<?= BASE_URL ?>/student/vote.php?election_id=<?= $e['id'] ?>" class="btn btn-primary btn-sm btn-full" style="margin-top:12px">
            <i class="fas fa-vote-yea"></i> Cast Your Vote
          </a>
        <?php elseif ($isActive && $hasVoted): ?>
          <div style="font-size:0.8rem;color:var(--success);margin-top:8px"><i class="fas fa-check-circle"></i> You have voted in this election.</div>
        <?php elseif ($isPublished): ?>
          <a href="<?= BASE_URL ?>/student/results.php?election_id=<?= $e['id'] ?>" class="btn btn-accent btn-sm btn-full" style="margin-top:12px">
            <i class="fas fa-poll-h"></i> View Results
          </a>
        <?php elseif ($e['status']==='upcoming'): ?>
          <div data-countdown="<?= $e['start_datetime'] ?>"></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <?php if (empty($elections)): ?>
      <div class="glass-card" style="padding:40px;text-align:center">
        <div style="font-size:3rem;margin-bottom:12px">🗳️</div>
        <h4>No Elections Available</h4>
        <p style="color:var(--text-muted);font-size:0.85rem">Check back later for upcoming elections in your department.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right Column -->
  <div>
    <!-- Profile Card -->
    <div class="glass-card" style="padding:24px;margin-bottom:20px;text-align:center">
      <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($student['profile_photo']) ?>"
           style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);margin:0 auto 14px"
           onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
      <div style="font-weight:700;font-size:1.1rem;margin-bottom:4px"><?= htmlspecialchars($student['full_name']) ?></div>
      <div style="font-size:0.8rem;color:var(--accent);margin-bottom:12px"><?= htmlspecialchars($student['roll_number']) ?></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;font-size:0.78rem">
        <div style="background:var(--bg-glass);padding:8px;border-radius:8px">
          <div style="color:var(--text-muted)">Department</div>
          <div style="font-weight:600"><?= htmlspecialchars($student['dept_name']) ?></div>
        </div>
        <div style="background:var(--bg-glass);padding:8px;border-radius:8px">
          <div style="color:var(--text-muted)">Year</div>
          <div style="font-weight:600">Year <?= $student['year'] ?></div>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/student/profile.php" class="btn btn-outline btn-sm btn-full"><i class="fas fa-edit"></i> Edit Profile</a>
    </div>

    <!-- Announcements -->
    <?php if (!empty($announcements)): ?>
    <div class="glass-card" style="padding:20px">
      <h4 style="font-size:0.95rem;margin-bottom:16px"><i class="fas fa-bullhorn" style="color:var(--warning)"></i> Announcements</h4>
      <?php foreach ($announcements as $a): ?>
        <div style="padding:12px 0;border-bottom:1px solid var(--border)">
          <div style="font-weight:600;font-size:0.85rem;margin-bottom:4px"><?= htmlspecialchars($a['title']) ?></div>
          <div style="font-size:0.78rem;color:var(--text-muted);line-height:1.5"><?= htmlspecialchars(substr($a['content'],0,100)) ?>...</div>
          <div style="font-size:0.7rem;color:var(--text-muted);margin-top:6px"><?= timeAgo($a['created_at']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
