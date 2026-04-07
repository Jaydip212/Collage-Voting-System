<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('teacher');

$teacher    = getTeacherById($pdo, $_SESSION['user_id']);
$electionId = (int)($_GET['election_id'] ?? 0);
$published  = $pdo->query("SELECT e.*, d.name as dept_name FROM elections e LEFT JOIN departments d ON e.department_id=d.id WHERE e.status='published' AND e.election_type='teacher' ORDER BY e.end_datetime DESC")->fetchAll();
if (!$electionId && !empty($published)) $electionId = $published[0]['id'];

$selectedElec = null; $candidates = []; $totalVotes = 0;
if ($electionId) {
    $s = $pdo->prepare("SELECT * FROM elections WHERE id=? AND status='published'");
    $s->execute([$electionId]); $selectedElec = $s->fetch();
    if ($selectedElec) {
        $cs = $pdo->prepare("SELECT c.*, COALESCE(r.total_votes,0) as total_votes FROM candidates c LEFT JOIN results r ON r.candidate_id=c.id AND r.election_id=c.election_id WHERE c.election_id=? AND c.status='approved' ORDER BY total_votes DESC");
        $cs->execute([$electionId]); $candidates = $cs->fetchAll();
        $totalVotes = array_sum(array_column($candidates,'total_votes'));
    }
}

$pageTitle='Results'; $activeMenu='results';
$sidebarLinks=[
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/teacher/index.php','key'=>'dashboard'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/teacher/elections.php','key'=>'elections'],
  ['icon'=>'fas fa-poll-h','label'=>'Results','href'=>BASE_URL.'/teacher/results.php','key'=>'results'],
  ['icon'=>'fas fa-user-circle','label'=>'My Profile','href'=>BASE_URL.'/teacher/profile.php','key'=>'profile'],
];
$roleLabel='Teacher';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>
<div style="margin-bottom:24px"><h2 style="font-size:1.4rem;margin-bottom:4px">Teacher Election Results</h2></div>
<div class="filter-row" style="margin-bottom:20px">
  <form method="GET">
    <select name="election_id" class="form-control" style="max-width:360px" onchange="this.form.submit()">
      <option value="">Select Election</option>
      <?php foreach ($published as $e): ?><option value="<?= $e['id'] ?>" <?= $electionId==$e['id']?'selected':'' ?>><?= htmlspecialchars($e['title']) ?></option><?php endforeach; ?>
    </select>
  </form>
</div>
<?php if ($selectedElec && !empty($candidates)): ?>
  <div class="glass-card" style="padding:24px">
    <h4 style="margin-bottom:16px"><i class="fas fa-trophy" style="color:var(--warning)"></i> Leaderboard</h4>
    <div class="leaderboard">
      <?php foreach ($candidates as $idx=>$c): $pct=$totalVotes>0?round(($c['total_votes']/$totalVotes)*100,1):0; $rankCls=$idx===0?'rank-1':($idx===1?'rank-2':($idx===2?'rank-3':'rank-n')); ?>
        <div class="leaderboard-item">
          <div class="leaderboard-rank <?= $rankCls ?>"><?= $idx+1 ?></div>
          <img class="leaderboard-photo" src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($c['photo']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
          <div class="leaderboard-info">
            <div class="leaderboard-name"><?= htmlspecialchars($c['name']) ?></div>
            <div class="leaderboard-dept"><?= htmlspecialchars($c['position']??'') ?></div>
            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $pct ?>%"></div></div>
          </div>
          <div class="leaderboard-votes"><div class="leaderboard-count"><?= number_format($c['total_votes']) ?></div><div class="leaderboard-label"><?= $pct ?>%</div></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php else: ?>
  <div class="glass-card" style="padding:60px;text-align:center"><div style="font-size:4rem;margin-bottom:16px">📊</div><h3>No Results Available</h3><p style="color:var(--text-muted)">No published teacher election results.</p></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
