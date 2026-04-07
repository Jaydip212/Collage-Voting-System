<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

autoUpdateElectionStatus($pdo);

$student     = getStudentById($pdo, $_SESSION['user_id']);
$deptId      = $student['department_id'];
$typeFilter  = $_GET['type'] ?? 'all';
$statusFilt  = $_GET['status'] ?? 'all';
$search      = trim($_GET['q'] ?? '');

$where  = ["(e.department_id IS NULL OR e.department_id=?) AND e.election_type IN ('student','cr','cultural','sports','general')"];
$params = [$deptId];

if ($typeFilter !== 'all')  { $where[] = "e.election_type=?"; $params[] = $typeFilter; }
if ($statusFilt !== 'all')  { $where[] = "e.status=?"; $params[] = $statusFilt; }
if ($search)                { $where[] = "e.title LIKE ?"; $params[] = "%$search%"; }

$stmt = $pdo->prepare("
    SELECT e.*, d.name as dept_name,
        COUNT(DISTINCT c.id) as candidate_count,
        COUNT(DISTINCT v.vote_id) as total_votes,
        (SELECT COUNT(*) FROM votes WHERE election_id=e.id AND voter_type='student' AND voter_id=?) as my_vote
    FROM elections e
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN candidates c ON c.election_id=e.id AND c.status='approved'
    LEFT JOIN votes v ON v.election_id=e.id
    WHERE ".implode(' AND ',$where)."
    GROUP BY e.id ORDER BY FIELD(e.status,'active','upcoming','published','completed'), e.start_datetime
");
$stmt->execute(array_merge([$_SESSION['user_id']],$params));
$elections = $stmt->fetchAll();

$statusBadges = ['active'=>'badge-success','upcoming'=>'badge-info','completed'=>'badge-default','published'=>'badge-purple','frozen'=>'badge-warning'];
$typeLabels   = ['student'=>'Student','cr'=>'Class CR','cultural'=>'Cultural','sports'=>'Sports','general'=>'General'];

$pageTitle  = 'My Elections';
$activeMenu = 'elections';
$sidebarLinks = [
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/student/index.php','key'=>'dashboard'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/student/elections.php','key'=>'elections'],
  ['icon'=>'fas fa-poll-h','label'=>'Results','href'=>BASE_URL.'/student/results.php','key'=>'results'],
  ['icon'=>'fas fa-user-circle','label'=>'My Profile','href'=>BASE_URL.'/student/profile.php','key'=>'profile'],
  ['icon'=>'fas fa-certificate','label'=>'Certificate','href'=>BASE_URL.'/student/certificate.php','key'=>'certificate'],
];
$roleLabel = 'Student';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div style="margin-bottom:24px">
  <h2 style="font-size:1.4rem;margin-bottom:4px">Elections</h2>
  <p style="color:var(--text-muted);font-size:0.85rem">Browse and participate in available elections for your department</p>
</div>

<!-- Filters -->
<div class="filter-row" style="margin-bottom:24px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <div style="position:relative">
      <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
      <input type="text" name="q" class="form-control" placeholder="Search elections..." value="<?= htmlspecialchars($search) ?>" style="padding-left:44px;max-width:240px">
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <?php foreach (['all'=>'🗳️ All','active'=>'🔴 Active','upcoming'=>'📅 Upcoming','published'=>'📊 Results'] as $k=>$v): ?>
        <a href="?status=<?= $k ?>&type=<?= $typeFilter ?>" class="btn btn-sm <?= $statusFilt===$k?'btn-primary':'btn-outline' ?>"><?= $v ?></a>
      <?php endforeach; ?>
    </div>
    <select name="type" class="form-control" style="max-width:150px" onchange="this.form.submit()">
      <option value="all">All Types</option>
      <?php foreach ($typeLabels as $k=>$v): ?>
        <option value="<?= $k ?>" <?= $typeFilter===$k?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<!-- Elections Grid -->
<?php if (!empty($elections)): ?>
<div class="grid-auto">
  <?php foreach ($elections as $e):
    $hasVoted  = $e['my_vote'] > 0;
    $isActive  = $e['status'] === 'active';
    $isPubl    = $e['status'] === 'published';
  ?>
    <div class="election-card">
      <div class="election-banner" style="background:linear-gradient(135deg,rgba(79,70,229,0.6),rgba(6,182,212,0.6));position:relative">
        <?php if ($e['banner_image']): ?>
          <img src="<?= BASE_URL ?>/uploads/election_banners/<?= htmlspecialchars($e['banner_image']) ?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
        <?php else: ?>
          <span style="font-size:3rem;position:relative;z-index:1">🗳️</span>
        <?php endif; ?>
      </div>

      <div class="election-body">
        <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap">
          <span class="badge <?= $statusBadges[$e['status']] ?? 'badge-default' ?>" style="font-size:0.68rem">
            <?= $isActive ? '🔴' : '' ?> <?= ucfirst($e['status']) ?>
          </span>
          <span class="badge badge-default" style="font-size:0.68rem"><?= $typeLabels[$e['election_type']] ?? $e['election_type'] ?></span>
          <?php if ($hasVoted): ?>
            <span class="badge badge-success" style="font-size:0.68rem">✅ Voted</span>
          <?php endif; ?>
        </div>

        <div class="election-title"><?= htmlspecialchars($e['title']) ?></div>
        <div class="election-meta">
          <span><i class="fas fa-building"></i> <?= htmlspecialchars($e['dept_name'] ?? 'All Departments') ?></span>
          <span><i class="fas fa-users"></i> <?= $e['candidate_count'] ?> candidates · <?= $e['total_votes'] ?> votes cast</span>
          <span><i class="fas fa-calendar"></i> <?= date('d M Y',strtotime($e['start_datetime'])) ?> → <?= date('d M Y',strtotime($e['end_datetime'])) ?></span>
        </div>

        <?php if ($isActive): ?>
          <div data-countdown="<?= $e['end_datetime'] ?>"></div>
        <?php elseif ($e['status']==='upcoming'): ?>
          <div style="font-size:0.78rem;color:var(--text-muted)"><i class="fas fa-clock"></i> Starts: <?= formatDate($e['start_datetime']) ?></div>
        <?php endif; ?>
      </div>

      <div class="election-footer">
        <?php if ($isActive && !$hasVoted): ?>
          <a href="<?= BASE_URL ?>/student/vote.php?election_id=<?= $e['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;justify-content:center">
            <i class="fas fa-vote-yea"></i> Vote Now
          </a>
        <?php elseif ($isActive && $hasVoted): ?>
          <div class="btn btn-sm" style="flex:1;justify-content:center;background:rgba(16,185,129,0.15);color:var(--success);cursor:default;border:1px solid rgba(16,185,129,0.3)">
            <i class="fas fa-check-circle"></i> Already Voted
          </div>
        <?php elseif ($isPubl): ?>
          <a href="<?= BASE_URL ?>/student/results.php?election_id=<?= $e['id'] ?>" class="btn btn-accent btn-sm" style="flex:1;justify-content:center">
            <i class="fas fa-poll-h"></i> View Results
          </a>
        <?php else: ?>
          <div class="btn btn-sm btn-outline" style="flex:1;justify-content:center;cursor:default">
            <i class="fas fa-clock"></i> <?= ucfirst($e['status']) ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
  <div class="glass-card" style="padding:60px;text-align:center">
    <div style="font-size:4rem;margin-bottom:16px">🗳️</div>
    <h3>No Elections Found</h3>
    <p style="color:var(--text-muted);margin-top:8px">There are no elections matching your filters. Check back later!</p>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
