<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('hod');

autoUpdateElectionStatus($pdo);

$hod    = $pdo->prepare("SELECT h.*, d.name as dept_name FROM hods h LEFT JOIN departments d ON h.department_id=d.id WHERE h.id=?");
$hod->execute([$_SESSION['user_id']]);
$hod    = $hod->fetch();
$deptId = $hod['department_id'];

// Dept elections
$elections = $pdo->prepare("
    SELECT e.*, COUNT(v.vote_id) as vote_count, COUNT(c.id) as candidate_count
    FROM elections e
    LEFT JOIN votes v ON v.election_id=e.id
    LEFT JOIN candidates c ON c.election_id=e.id AND c.status='approved'
    WHERE (e.department_id=? OR e.department_id IS NULL)
    GROUP BY e.id ORDER BY e.status='active' DESC, e.start_datetime DESC
");
$elections->execute([$deptId]);
$elections = $elections->fetchAll();

// Pending candidates to approve
$pendingCandidates = $pdo->prepare("SELECT c.*, e.title as election_title FROM candidates c LEFT JOIN elections e ON c.election_id=e.id WHERE e.department_id=? AND c.status='pending' ORDER BY c.created_at DESC");
$pendingCandidates->execute([$deptId]);
$pendingCandidates = $pendingCandidates->fetchAll();

// Dept vote percentage
$deptStudents = $pdo->prepare("SELECT COUNT(*) FROM students WHERE department_id=? AND is_approved=1")->execute([$deptId]);
$totalEligible = $pdo->prepare("SELECT COUNT(*) FROM students WHERE department_id=? AND is_approved=1");
$totalEligible->execute([$deptId]);
$totalEligible = $totalEligible->fetchColumn();

$totalVotesCast = $pdo->prepare("SELECT COUNT(DISTINCT v.voter_id) FROM votes v JOIN elections e ON v.election_id=e.id WHERE e.department_id=? AND v.voter_type='student'");
$totalVotesCast->execute([$deptId]);
$totalVotesCast = $totalVotesCast->fetchColumn();
$turnout = $totalEligible > 0 ? round(($totalVotesCast/$totalEligible)*100,1) : 0;

// Announcements for dept
$announcements = $pdo->prepare("SELECT * FROM announcements WHERE department_id=? OR department_id IS NULL ORDER BY created_at DESC LIMIT 5");
$announcements->execute([$deptId]);
$announcements = $announcements->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'approve_candidate') {
        $cid = (int)$_POST['candidate_id'];
        $pdo->prepare("UPDATE candidates SET status='approved' WHERE id=?")->execute([$cid]);
        logActivity($pdo,'hod',$_SESSION['user_id'],'HOD_CANDIDATE_APPROVED',"Approved candidate ID: $cid");
        setFlash('success','Candidate approved.');
    }
    if ($action === 'reject_candidate') {
        $cid = (int)$_POST['candidate_id'];
        $pdo->prepare("UPDATE candidates SET status='rejected' WHERE id=?")->execute([$cid]);
        setFlash('success','Candidate rejected.');
    }
    if ($action === 'post_announcement') {
        $title   = sanitize($_POST['ann_title'] ?? '');
        $content = sanitize($_POST['ann_content'] ?? '');
        $pdo->prepare("INSERT INTO announcements (title,content,posted_by_role,posted_by_id,department_id) VALUES (?,?,'hod',?,?)")->execute([$title,$content,$_SESSION['user_id'],$deptId]);
        logActivity($pdo,'hod',$_SESSION['user_id'],'ANNOUNCEMENT_POSTED',"Posted: $title");
        setFlash('success','Announcement posted.');
    }
    header('Location: ' . BASE_URL . '/hod/index.php');
    exit;
}

$pageTitle  = 'HOD Dashboard';
$activeMenu = 'dashboard';
$sidebarLinks = [
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/hod/index.php','key'=>'dashboard'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/hod/elections.php','key'=>'elections'],
  ['icon'=>'fas fa-user-tie','label'=>'Candidates','href'=>BASE_URL.'/hod/candidates.php','key'=>'candidates'],
  ['icon'=>'fas fa-chart-bar','label'=>'Results','href'=>BASE_URL.'/hod/results.php','key'=>'results'],
  ['icon'=>'fas fa-bullhorn','label'=>'Announcements','href'=>BASE_URL.'/hod/announcements.php','key'=>'announcements'],
];
$roleLabel = 'HOD / Principal';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div style="margin-bottom:28px">
  <h2 style="font-size:1.4rem;margin-bottom:4px">🏛️ HOD Dashboard</h2>
  <p style="color:var(--text-muted)"><?= htmlspecialchars($hod['dept_name']) ?> Department · <?= htmlspecialchars($hod['designation']??'Head of Department') ?></p>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:28px">
  <?php
  $activeElections = count(array_filter($elections,fn($e)=>$e['status']==='active'));
  $totalVoters     = array_sum(array_column($elections,'vote_count'));
  $totalCands      = array_sum(array_column($elections,'candidate_count'));
  ?>
  <div class="stat-card"><div class="stat-icon stat-icon-red"><i class="fas fa-fire"></i></div><div class="stat-info"><div class="stat-value"><?= $activeElections ?></div><div class="stat-label">Active Elections</div></div></div>
  <div class="stat-card"><div class="stat-icon stat-icon-green"><i class="fas fa-check-double"></i></div><div class="stat-info"><div class="stat-value"><?= $totalVoters ?></div><div class="stat-label">Total Votes Cast</div></div></div>
  <div class="stat-card"><div class="stat-icon stat-icon-blue"><i class="fas fa-users"></i></div><div class="stat-info"><div class="stat-value"><?= $totalEligible ?></div><div class="stat-label">Eligible Voters</div></div></div>
  <div class="stat-card"><div class="stat-icon stat-icon-cyan"><i class="fas fa-percent"></i></div><div class="stat-info"><div class="stat-value"><?= $turnout ?>%</div><div class="stat-label">Voter Turnout</div></div></div>
</div>

<!-- Turnout Bar -->
<div class="glass-card" style="padding:20px;margin-bottom:24px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
    <div style="font-weight:600;font-size:0.9rem">Overall Dept. Voter Turnout</div>
    <div style="font-size:1.2rem;font-weight:800;color:var(--accent)"><?= $turnout ?>%</div>
  </div>
  <div class="progress-bar-wrap" style="height:10px">
    <div class="progress-bar-fill" data-pct="<?= $turnout ?>" style="width:<?= $turnout ?>%"></div>
  </div>
  <div style="font-size:0.75rem;color:var(--text-muted);margin-top:6px"><?= $totalVotesCast ?> out of <?= $totalEligible ?> eligible students have voted</div>
</div>

<div class="grid-2" style="align-items:start">
  <!-- Elections -->
  <div>
    <h3 style="font-size:1rem;margin-bottom:14px"><i class="fas fa-vote-yea" style="color:var(--primary-light)"></i> Department Elections</h3>
    <?php foreach ($elections as $e):
      $badge = ['active'=>'badge-success','upcoming'=>'badge-info','completed'=>'badge-default','published'=>'badge-purple','frozen'=>'badge-warning'][$e['status']] ?? 'badge-default';
    ?>
      <div class="glass-card" style="padding:16px;margin-bottom:12px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
          <div>
            <div style="font-weight:700;font-size:0.9rem;margin-bottom:4px"><?= htmlspecialchars($e['title']) ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted)"><?= $e['candidate_count'] ?> candidates · <?= $e['vote_count'] ?> votes</div>
          </div>
          <span class="badge <?= $badge ?>" style="font-size:0.7rem;flex-shrink:0"><?= ucfirst($e['status']) ?></span>
        </div>
        <?php if ($e['status']==='active'): ?>
          <div data-countdown="<?= $e['end_datetime'] ?>"></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (empty($elections)): ?>
      <div class="glass-card" style="padding:32px;text-align:center"><p style="color:var(--text-muted)">No elections for your department yet.</p></div>
    <?php endif; ?>
  </div>

  <div>
    <!-- Pending Candidates -->
    <?php if (!empty($pendingCandidates)): ?>
    <div class="glass-card" style="padding:20px;margin-bottom:18px">
      <h4 style="font-size:0.95rem;margin-bottom:14px"><i class="fas fa-user-clock" style="color:var(--warning)"></i> Pending Candidate Approvals (<?= count($pendingCandidates) ?>)</h4>
      <?php foreach ($pendingCandidates as $c): ?>
        <div style="padding:12px;background:var(--bg-glass);border-radius:var(--r-md);margin-bottom:8px">
          <div style="font-weight:600;font-size:0.85rem;margin-bottom:4px"><?= htmlspecialchars($c['name']) ?></div>
          <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:8px"><?= htmlspecialchars($c['election_title']) ?></div>
          <div style="display:flex;gap:8px">
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="approve_candidate">
              <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
              <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approve</button>
            </form>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="reject_candidate">
              <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger" data-confirm="Reject this candidate?"><i class="fas fa-times"></i> Reject</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Post Announcement -->
    <div class="glass-card" style="padding:20px;margin-bottom:18px">
      <h4 style="font-size:0.95rem;margin-bottom:14px"><i class="fas fa-bullhorn" style="color:var(--accent)"></i> Post Announcement</h4>
      <form method="POST">
        <input type="hidden" name="action" value="post_announcement">
        <div class="form-group">
          <input type="text" name="ann_title" class="form-control" placeholder="Announcement title *" required>
        </div>
        <div class="form-group">
          <textarea name="ann_content" class="form-control" rows="3" placeholder="Announcement content *" required></textarea>
        </div>
        <button type="submit" class="btn btn-accent btn-sm"><i class="fas fa-paper-plane"></i> Post Announcement</button>
      </form>
    </div>

    <!-- Recent Announcements -->
    <?php if (!empty($announcements)): ?>
    <div class="glass-card" style="padding:20px">
      <h4 style="font-size:0.95rem;margin-bottom:14px">Recent Announcements</h4>
      <?php foreach ($announcements as $a): ?>
        <div style="padding:10px 0;border-bottom:1px solid var(--border)">
          <div style="font-weight:600;font-size:0.83rem"><?= htmlspecialchars($a['title']) ?></div>
          <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px"><?= timeAgo($a['created_at']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
