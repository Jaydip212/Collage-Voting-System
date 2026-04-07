<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$student     = getStudentById($pdo, $_SESSION['user_id']);
$deptId      = $student['department_id'];
$electionId  = (int)($_GET['election_id'] ?? 0);

// Published elections for student dept
$publishedElections = $pdo->prepare("
    SELECT e.*, d.name as dept_name
    FROM elections e
    LEFT JOIN departments d ON e.department_id=d.id
    WHERE e.status='published' AND (e.department_id IS NULL OR e.department_id=?)
    ORDER BY e.end_datetime DESC
");
$publishedElections->execute([$deptId]);
$publishedElections = $publishedElections->fetchAll();

if (!$electionId && !empty($publishedElections)) {
    $electionId = $publishedElections[0]['id'];
}

$selectedElec  = null;
$candidates    = [];
$totalVotes    = 0;

if ($electionId) {
    $s = $pdo->prepare("SELECT e.*, d.name as dept_name FROM elections e LEFT JOIN departments d ON e.department_id=d.id WHERE e.id=? AND e.status='published'");
    $s->execute([$electionId]);
    $selectedElec = $s->fetch();

    if ($selectedElec) {
        $cs = $pdo->prepare("
            SELECT c.*, r.total_votes, r.rank as result_rank, r.is_winner, d.name as dept_name
            FROM candidates c
            LEFT JOIN results r ON r.candidate_id=c.id AND r.election_id=c.election_id
            LEFT JOIN departments d ON c.department_id=d.id
            WHERE c.election_id=? AND c.status='approved'
            ORDER BY COALESCE(r.total_votes,0) DESC
        ");
        $cs->execute([$electionId]);
        $candidates = $cs->fetchAll();
        $totalVotes = array_sum(array_column($candidates,'total_votes'));
    }
}

$myVote = $electionId ? hasVoted($pdo,$electionId,'student',$_SESSION['user_id']) : false;

$pageTitle  = 'Election Results';
$activeMenu = 'results';
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
  <h2 style="font-size:1.4rem;margin-bottom:4px">Election Results</h2>
  <p style="color:var(--text-muted);font-size:0.85rem">View published election results and leaderboards</p>
</div>

<!-- Election selector -->
<div class="filter-row" style="margin-bottom:24px">
  <form method="GET">
    <select name="election_id" class="form-control" style="max-width:360px" onchange="this.form.submit()">
      <option value="">Select Election</option>
      <?php foreach ($publishedElections as $e): ?>
        <option value="<?= $e['id'] ?>" <?= $electionId==$e['id']?'selected':'' ?>><?= htmlspecialchars($e['title']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if ($selectedElec && !empty($candidates)): ?>

  <!-- Winner -->
  <?php $winner = $candidates[0]; if ($winner && $winner['total_votes'] > 0): ?>
    <div class="winner-card" style="max-width:500px;margin:0 auto 28px">
      <span class="winner-crown">👑</span>
      <img src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($winner['photo']) ?>"
           class="winner-photo"
           onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
      <div class="winner-name"><?= htmlspecialchars($winner['name']) ?></div>
      <div style="color:var(--text-muted);font-size:0.85rem"><?= htmlspecialchars($winner['position']??'') ?></div>
      <div class="winner-votes" style="margin-top:8px">
        🏆 <?= number_format($winner['total_votes']) ?> votes
        (<?= $totalVotes > 0 ? round(($winner['total_votes']/$totalVotes)*100,1) : 0 ?>%)
      </div>
    </div>
  <?php endif; ?>

  <!-- Summary -->
  <div class="glass-card" style="padding:20px;margin-bottom:24px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($selectedElec['title']) ?></div>
        <div style="font-size:0.8rem;color:var(--text-muted)"><?= htmlspecialchars($selectedElec['dept_name']??'All Departments') ?> · Ended <?= date('d M Y',strtotime($selectedElec['end_datetime'])) ?></div>
      </div>
      <div style="display:flex;gap:28px">
        <div style="text-align:center">
          <div style="font-size:1.4rem;font-weight:800;color:var(--primary-light)"><?= number_format($totalVotes) ?></div>
          <div style="font-size:0.7rem;color:var(--text-muted)">Total Votes</div>
        </div>
        <div style="text-align:center">
          <div style="font-size:1.4rem;font-weight:800;color:var(--accent)"><?= count($candidates) ?></div>
          <div style="font-size:0.7rem;color:var(--text-muted)">Candidates</div>
        </div>
        <?php if ($myVote): ?>
          <div style="text-align:center">
            <div style="font-size:1.4rem;font-weight:800;color:var(--success)">✅</div>
            <div style="font-size:0.7rem;color:var(--text-muted)">You Voted</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="grid-2" style="align-items:start">
    <!-- Leaderboard -->
    <div class="glass-card" style="padding:24px">
      <h4 style="font-size:1rem;margin-bottom:16px"><i class="fas fa-trophy" style="color:var(--warning)"></i> Leaderboard</h4>
      <div class="leaderboard">
        <?php foreach ($candidates as $idx => $c):
          $pct = $totalVotes > 0 ? round(($c['total_votes']/$totalVotes)*100,1) : 0;
          $rankCls = $idx===0?'rank-1':($idx===1?'rank-2':($idx===2?'rank-3':'rank-n'));
        ?>
          <div class="leaderboard-item">
            <div class="leaderboard-rank <?= $rankCls ?>"><?= $idx+1 ?></div>
            <img class="leaderboard-photo"
                 src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($c['photo']) ?>"
                 onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
            <div class="leaderboard-info">
              <div class="leaderboard-name"><?= htmlspecialchars($c['name']) ?></div>
              <div class="leaderboard-dept"><?= htmlspecialchars($c['position']??'') ?></div>
              <div class="progress-bar-wrap">
                <div class="progress-bar-fill" data-pct="<?= $pct ?>" style="width:<?= $pct ?>%"></div>
              </div>
            </div>
            <div class="leaderboard-votes">
              <div class="leaderboard-count"><?= number_format($c['total_votes']) ?></div>
              <div class="leaderboard-label"><?= $pct ?>%</div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Doughnut Chart -->
    <div class="glass-card" style="padding:24px">
      <h4 style="font-size:1rem;margin-bottom:16px"><i class="fas fa-chart-pie" style="color:var(--accent)"></i> Vote Distribution</h4>
      <div style="height:280px;position:relative">
        <canvas id="resultPie"
          data-chart="pie"
          data-labels='<?= json_encode(array_column($candidates,'name')) ?>'
          data-values='<?= json_encode(array_column($candidates,'total_votes')) ?>'>
        </canvas>
      </div>
    </div>
  </div>

<?php elseif ($electionId && !$selectedElec): ?>
  <div class="glass-card" style="padding:40px;text-align:center">
    <div style="font-size:3rem;margin-bottom:12px">🔒</div>
    <h3>Results Not Yet Published</h3>
    <p style="color:var(--text-muted)">Results for this election haven't been published yet.</p>
  </div>
<?php elseif (empty($publishedElections)): ?>
  <div class="glass-card" style="padding:60px;text-align:center">
    <div style="font-size:4rem;margin-bottom:16px">📊</div>
    <h3>No Results Available</h3>
    <p style="color:var(--text-muted)">No election results have been published for your department yet.</p>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
