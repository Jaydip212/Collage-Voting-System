<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

autoUpdateElectionStatus($pdo);

// Fetch completed / published elections with results
$elections = $pdo->query("
    SELECT e.*, d.name as dept_name, COUNT(v.vote_id) as total_votes
    FROM elections e
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN votes v ON v.election_id=e.id
    WHERE e.status IN ('published','completed','frozen')
    GROUP BY e.id
    ORDER BY e.end_datetime DESC
")->fetchAll();

$selectedId    = isset($_GET['election_id']) ? (int)$_GET['election_id'] : ($elections[0]['id'] ?? 0);
$selectedElec  = null;
$candidates    = [];

if ($selectedId) {
    $s = $pdo->prepare("SELECT * FROM elections WHERE id=?");
    $s->execute([$selectedId]);
    $selectedElec = $s->fetch();

    // Get candidates with votes
    $stmt = $pdo->prepare("
        SELECT c.*, 
            COALESCE(r.total_votes, 0) as total_votes,
            COALESCE(r.rank, 999) as result_rank,
            COALESCE(r.is_winner, 0) as is_winner,
            d.name as dept_name
        FROM candidates c
        LEFT JOIN results r ON r.candidate_id=c.id AND r.election_id=c.election_id
        LEFT JOIN departments d ON c.department_id=d.id
        WHERE c.election_id=? AND c.status='approved'
        ORDER BY total_votes DESC
    ");
    $stmt->execute([$selectedId]);
    $candidates = $stmt->fetchAll();
}

$totalVotes = array_sum(array_column($candidates, 'total_votes'));

$pageTitle  = 'Results';
$activeMenu = 'results';
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
];
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
  <div>
    <h2 style="font-size:1.4rem;margin-bottom:4px">Election Results</h2>
    <p style="color:var(--text-muted);font-size:0.85rem">Live and published election results with leaderboard</p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    <?php if ($selectedElec && !$selectedElec['is_result_published']): ?>
      <form method="POST" action="<?= BASE_URL ?>/admin/elections.php" style="display:inline">
        <input type="hidden" name="action" value="publish">
        <input type="hidden" name="election_id" value="<?= $selectedId ?>">
        <button type="submit" class="btn btn-accent" data-confirm="Publish results for this election?">
          <i class="fas fa-bullhorn"></i> Publish Results
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<!-- Election Selector -->
<div class="filter-row" style="margin-bottom:28px">
  <form method="GET">
    <select name="election_id" class="form-control" style="max-width:360px" onchange="this.form.submit()">
      <option value="">Select Election...</option>
      <?php foreach ($elections as $e): ?>
        <option value="<?= $e['id'] ?>" <?= $selectedId==$e['id']?'selected':'' ?>>
          <?= htmlspecialchars($e['title']) ?> (<?= $e['total_votes'] ?> votes)
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if ($selectedElec && !empty($candidates)): ?>

<!-- Election Info -->
<div class="glass-card" style="padding:20px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em">Election</div>
    <div style="font-weight:700;font-size:1.1rem"><?= htmlspecialchars($selectedElec['title']) ?></div>
  </div>
  <div style="display:flex;gap:28px;flex-wrap:wrap">
    <div style="text-align:center">
      <div style="font-size:1.5rem;font-weight:800;color:var(--primary-light)"><?= number_format($totalVotes) ?></div>
      <div style="font-size:0.72rem;color:var(--text-muted)">Total Votes</div>
    </div>
    <div style="text-align:center">
      <div style="font-size:1.5rem;font-weight:800;color:var(--accent)"><?= count($candidates) ?></div>
      <div style="font-size:0.72rem;color:var(--text-muted)">Candidates</div>
    </div>
    <div style="text-align:center">
      <span class="badge <?= $selectedElec['is_result_published'] ? 'badge-success' : 'badge-warning' ?>">
        <?= $selectedElec['is_result_published'] ? '✅ Published' : '⏳ Not Published' ?>
      </span>
    </div>
  </div>
</div>

<div id="liveResults">

<!-- Winner Card (if published) -->
<?php $winner = $candidates[0] ?? null; if ($winner && $winner['total_votes'] > 0): ?>
<div class="winner-card" style="margin-bottom:24px">
  <span class="winner-crown">👑</span>
  <img src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($winner['photo']) ?>"
       alt="<?= htmlspecialchars($winner['name']) ?>"
       class="winner-photo"
       onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
  <div class="winner-name"><?= htmlspecialchars($winner['name']) ?></div>
  <div style="color:var(--text-muted);font-size:0.85rem;margin:4px 0"><?= htmlspecialchars($winner['position'] ?? '') ?></div>
  <div class="winner-votes">🏆 <?= number_format($winner['total_votes']) ?> votes
    (<?= $totalVotes > 0 ? round(($winner['total_votes']/$totalVotes)*100,1) : 0 ?>%)
  </div>
</div>
<?php endif; ?>

<!-- Leaderboard -->
<div class="glass-card" style="padding:24px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <h4 style="font-size:1rem"><i class="fas fa-trophy" style="color:var(--warning)"></i> Leaderboard</h4>
    <div style="font-size:0.8rem;color:var(--text-muted)">Auto-refreshes every 5 seconds</div>
  </div>

  <div class="leaderboard">
  <?php foreach ($candidates as $idx => $c):
    $pct = $totalVotes > 0 ? round(($c['total_votes']/$totalVotes)*100,1) : 0;
    $rankCls = $idx===0?'rank-1':($idx===1?'rank-2':($idx===2?'rank-3':'rank-n'));
  ?>
    <div class="leaderboard-item">
      <div class="leaderboard-rank <?= $rankCls ?>"><?= $idx+1 ?></div>
      <img class="leaderboard-photo"
           src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($c['photo']) ?>"
           alt="<?= htmlspecialchars($c['name']) ?>"
           onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
      <div class="leaderboard-info">
        <div class="leaderboard-name"><?= htmlspecialchars($c['name']) ?></div>
        <div class="leaderboard-dept"><?= htmlspecialchars($c['position'] ?? '') ?> · <?= htmlspecialchars($c['dept_name'] ?? '') ?></div>
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

  <!-- Doughnut Chart -->
  <div style="max-width:320px;margin:28px auto 0">
    <canvas id="resultPieChart"
      data-chart="pie"
      data-labels='<?= json_encode(array_column($candidates,'name')) ?>'
      data-values='<?= json_encode(array_column($candidates,'total_votes')) ?>'>
    </canvas>
  </div>
</div>
</div><!-- end #liveResults -->

<script>
// Auto-refresh results every 5 seconds for live elections
<?php if ($selectedElec && in_array($selectedElec['status'],['active','frozen'])): ?>
startResultRefresh(<?= $selectedId ?>);
<?php endif; ?>
</script>

<?php elseif ($selectedId && empty($candidates)): ?>
  <div class="glass-card" style="padding:40px;text-align:center">
    <div style="font-size:3rem;margin-bottom:16px">📊</div>
    <h3>No Results Yet</h3>
    <p style="color:var(--text-muted)">No approved candidates or votes recorded for this election.</p>
  </div>
<?php else: ?>
  <div class="glass-card" style="padding:40px;text-align:center">
    <div style="font-size:3rem;margin-bottom:16px">🏆</div>
    <h3>Select an Election</h3>
    <p style="color:var(--text-muted)">Choose an election above to view its results and leaderboard.</p>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
