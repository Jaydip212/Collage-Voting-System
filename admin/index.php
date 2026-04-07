<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

autoUpdateElectionStatus($pdo);

$stats = getDashboardStats($pdo);

// Recent activity
$recentLogs = $pdo->query("
    SELECT al.*, 
        CASE al.user_role
            WHEN 'admin' THEN (SELECT name FROM admins WHERE id=al.user_id)
            WHEN 'student' THEN (SELECT full_name FROM students WHERE id=al.user_id)
            WHEN 'teacher' THEN (SELECT full_name FROM teachers WHERE id=al.user_id)
            WHEN 'hod' THEN (SELECT name FROM hods WHERE id=al.user_id)
        END as user_name
    FROM activity_logs al ORDER BY al.created_at DESC LIMIT 10
")->fetchAll();

// Election chart data
$electionData = $pdo->query("
    SELECT e.title, COUNT(v.vote_id) as total_votes 
    FROM elections e 
    LEFT JOIN votes v ON e.id=v.election_id 
    WHERE e.status IN ('active','published','completed') 
    GROUP BY e.id ORDER BY total_votes DESC LIMIT 8
")->fetchAll();

// Dept vote data
$deptData = $pdo->query("
    SELECT d.name, COUNT(v.vote_id) as total_votes
    FROM departments d
    LEFT JOIN elections e ON e.department_id=d.id
    LEFT JOIN votes v ON v.election_id=e.id
    GROUP BY d.id ORDER BY d.name
")->fetchAll();

// Failed login alerts
$suspiciousCount = $pdo->query("
    SELECT COUNT(DISTINCT ip_address) FROM failed_logins 
    WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
    GROUP BY ip_address HAVING COUNT(*) >= 5
")->fetchColumn();

$pageTitle  = 'Admin Dashboard';
$activeMenu = 'dashboard';

$sidebarLinks = [
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/admin/index.php','key'=>'dashboard'],
  ['section'=>'ELECTIONS'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/admin/elections.php','key'=>'elections'],
  ['icon'=>'fas fa-user-tie','label'=>'Candidates','href'=>BASE_URL.'/admin/candidates.php','key'=>'candidates'],
  ['icon'=>'fas fa-chart-bar','label'=>'Results','href'=>BASE_URL.'/admin/results.php','key'=>'results'],
  ['section'=>'MANAGEMENT'],
  ['icon'=>'fas fa-building','label'=>'Departments','href'=>BASE_URL.'/admin/departments.php','key'=>'departments'],
  ['icon'=>'fas fa-users','label'=>'Students','href'=>BASE_URL.'/admin/students.php','key'=>'students'],
  ['icon'=>'fas fa-chalkboard-teacher','label'=>'Teachers','href'=>BASE_URL.'/admin/teachers.php','key'=>'teachers'],
  ['icon'=>'fas fa-user-shield','label'=>'HODs','href'=>BASE_URL.'/admin/hods.php','key'=>'hods'],
  ['section'=>'SYSTEM'],
  ['icon'=>'fas fa-bullhorn','label'=>'Announcements','href'=>BASE_URL.'/admin/announcements.php','key'=>'announcements'],
  ['icon'=>'fas fa-history','label'=>'Audit Logs','href'=>BASE_URL.'/admin/audit_logs.php','key'=>'auditlogs','badge'=>($suspiciousCount?'🚨':'')],
  ['icon'=>'fas fa-database','label'=>'Backup','href'=>BASE_URL.'/admin/backup.php','key'=>'backup'],
  ['icon'=>'fas fa-cog','label'=>'Settings','href'=>BASE_URL.'/admin/settings.php','key'=>'settings'],
];

require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<!-- ── FRAUD ALERT ──────────────────────────────────────── -->
<?php if ($suspiciousCount): ?>
<div class="alert alert-danger" style="margin-bottom:24px">
  <i class="fas fa-shield-alt"></i>
  <strong>Fraud Alert!</strong> Suspicious login activity detected from <?= $suspiciousCount ?> IP(s) in the past hour.
  <a href="<?= BASE_URL ?>/admin/audit_logs.php" style="color:#f87171;margin-left:8px">View Logs →</a>
</div>
<?php endif; ?>

<!-- ── STAT CARDS ───────────────────────────────────────── -->
<div class="stats-grid">
  <?php
  $cards = [
    ['Total Students',  $stats['total_students'],   'fas fa-user-graduate','stat-icon-blue',   ''],
    ['Total Teachers',  $stats['total_teachers'],   'fas fa-chalkboard-teacher','stat-icon-green',''],
    ['Total Elections', $stats['total_elections'],  'fas fa-vote-yea',     'stat-icon-yellow', ''],
    ['Active Elections',$stats['active_elections'], 'fas fa-fire',         'stat-icon-red',    ''],
    ['Total Votes',     $stats['total_votes'],      'fas fa-check-double', 'stat-icon-cyan',   ''],
    ['Departments',     $stats['total_depts'],      'fas fa-building',     'stat-icon-purple',  ''],
    ['Pending Students',$stats['pending_students'], 'fas fa-user-clock',   'stat-icon-yellow', ''],
    ['Candidates',      $stats['total_candidates'], 'fas fa-id-badge',     'stat-icon-blue',   ''],
  ];
  foreach ($cards as [$label,$val,$icon,$iconClass,$change]): ?>
    <div class="stat-card">
      <div class="stat-icon <?= $iconClass ?>"><i class="<?= $icon ?>"></i></div>
      <div class="stat-info">
        <div class="stat-value" data-counter="<?= $val ?>"><?= number_format($val) ?></div>
        <div class="stat-label"><?= $label ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- ── CHARTS ROW ────────────────────────────────────────── -->
<div class="grid-2" style="margin-bottom:28px">
  <!-- Vote distribution by election -->
  <div class="glass-card" style="padding:24px">
    <h4 style="margin-bottom:20px;font-size:1rem"><i class="fas fa-chart-pie" style="color:var(--primary-light)"></i> Votes by Election</h4>
    <div style="height:240px;position:relative">
      <canvas id="electionPieChart"
        data-chart="pie"
        data-labels='<?= json_encode(array_column($electionData,'title')) ?>'
        data-values='<?= json_encode(array_column($electionData,'total_votes')) ?>'>
      </canvas>
    </div>
  </div>

  <!-- Department turnout -->
  <div class="glass-card" style="padding:24px">
    <h4 style="margin-bottom:20px;font-size:1rem"><i class="fas fa-chart-bar" style="color:var(--accent)"></i> Department Turnout</h4>
    <div style="height:240px;position:relative">
      <canvas id="deptBarChart"
        data-chart="hbar"
        data-labels='<?= json_encode(array_column($deptData,'name')) ?>'
        data-values='<?= json_encode(array_column($deptData,'total_votes')) ?>'>
      </canvas>
    </div>
  </div>
</div>

<!-- ── BOTTOM ROW ────────────────────────────────────────── -->
<div class="grid-2">
  <!-- Recent Activity -->
  <div class="glass-card" style="padding:24px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
      <h4 style="font-size:1rem"><i class="fas fa-history" style="color:var(--warning)"></i> Recent Activity</h4>
      <a href="<?= BASE_URL ?>/admin/audit_logs.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <?php foreach ($recentLogs as $log): ?>
      <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--bg-glass);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.8rem;font-weight:700;color:var(--primary-light)">
          <?= strtoupper(substr($log['user_name']??'?',0,1)) ?>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:0.83rem;font-weight:600"><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></div>
          <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($log['action']) ?> · <?= timeAgo($log['created_at']) ?></div>
        </div>
        <span class="badge badge-default" style="font-size:0.65rem"><?= $log['user_role'] ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (empty($recentLogs)): ?>
      <p style="color:var(--text-muted);font-size:0.85rem;text-align:center;padding:20px">No activity yet</p>
    <?php endif; ?>
  </div>

  <!-- Quick Actions -->
  <div class="glass-card" style="padding:24px">
    <h4 style="font-size:1rem;margin-bottom:18px"><i class="fas fa-bolt" style="color:var(--accent)"></i> Quick Actions</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <?php
      $actions = [
        ['fas fa-plus-circle','Create Election','admin/elections.php?action=create','var(--primary)'],
        ['fas fa-user-plus','Add Candidate','admin/candidates.php?action=add','var(--accent)'],
        ['fas fa-bullhorn','Announcement','admin/announcements.php?action=new','var(--success)'],
        ['fas fa-building','Add Department','admin/departments.php?action=add','var(--warning)'],
        ['fas fa-users','Approve Users','admin/students.php?filter=pending','var(--info)'],
        ['fas fa-chart-bar','View Results','admin/results.php','var(--danger)'],
      ];
      foreach ($actions as [$icon,$label,$href,$color]): ?>
        <a href="<?= BASE_URL ?>/<?= $href ?>" style="text-decoration:none">
          <div style="padding:14px;background:var(--bg-glass);border:1px solid var(--border);border-radius:var(--r-md);text-align:center;transition:all 0.2s ease" onmouseover="this.style.borderColor='<?= $color ?>'" onmouseout="this.style.borderColor='var(--border)'">
            <i class="<?= $icon ?>" style="font-size:1.3rem;color:<?= $color ?>;margin-bottom:8px;display:block"></i>
            <div style="font-size:0.78rem;font-weight:600"><?= $label ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Pending approvals -->
    <?php if ($stats['pending_students'] > 0 || $stats['pending_teachers'] > 0): ?>
    <div style="margin-top:20px;padding:14px;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:var(--r-md)">
      <div style="font-size:0.85rem;font-weight:600;color:var(--warning);margin-bottom:8px"><i class="fas fa-clock"></i> Pending Approvals</div>
      <?php if ($stats['pending_students']): ?>
        <div style="font-size:0.8rem;margin-bottom:4px">
          🎓 <strong><?= $stats['pending_students'] ?></strong> student(s)
          <a href="<?= BASE_URL ?>/admin/students.php?filter=pending" style="color:var(--warning);margin-left:8px">Review →</a>
        </div>
      <?php endif; ?>
      <?php if ($stats['pending_teachers']): ?>
        <div style="font-size:0.8rem">
          👩‍🏫 <strong><?= $stats['pending_teachers'] ?></strong> teacher(s)
          <a href="<?= BASE_URL ?>/admin/teachers.php?filter=pending" style="color:var(--warning);margin-left:8px">Review →</a>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
