<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('hod');

$hodId  = $_SESSION['user_id'];
$hod    = $pdo->prepare("SELECT h.*, d.name as dept_name FROM hods h LEFT JOIN departments d ON h.department_id=d.id WHERE h.id=?");
$hod->execute([$hodId]);
$hod    = $hod->fetch();
$deptId = $hod['department_id'];

// Elections with published results for this dept
$elections = $pdo->prepare("
    SELECT e.* FROM elections e
    WHERE (e.department_id=? OR e.department_id IS NULL)
    AND e.is_result_published=1
    ORDER BY e.updated_at DESC
");
$elections->execute([$deptId]);
$elections = $elections->fetchAll();

$pageTitle  = 'Results – HOD';
$activeMenu = 'results';
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
  <h2 style="font-size:1.4rem;margin-bottom:4px">📊 Election Results</h2>
  <p style="color:var(--text-muted)"><?= htmlspecialchars($hod['dept_name']) ?> · Published results for your department</p>
</div>

<?php if (empty($elections)): ?>
<div class="glass-card" style="padding:60px;text-align:center">
  <div style="font-size:3rem;margin-bottom:16px">📊</div>
  <h3 style="margin-bottom:8px">No Results Published Yet</h3>
  <p style="color:var(--text-muted)">Results will appear here once the Admin publishes them after an election ends.</p>
</div>
<?php endif; ?>

<?php foreach ($elections as $e):
  $results = $pdo->prepare("
    SELECT r.*, c.name, c.photo, c.position, d.name as dept_name
    FROM results r
    LEFT JOIN candidates c ON r.candidate_id=c.id
    LEFT JOIN departments d ON c.department_id=d.id
    WHERE r.election_id=?
    ORDER BY r.rank ASC
  ");
  $results->execute([$e['id']]);
  $results = $results->fetchAll();
  $totalVotes = array_sum(array_column($results,'total_votes'));
  $winner = $results[0] ?? null;
?>
<div class="glass-card" style="padding:28px;margin-bottom:28px">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <div>
      <h3 style="font-size:1.1rem;margin-bottom:4px"><?= htmlspecialchars($e['title']) ?></h3>
      <span style="font-size:0.75rem;color:var(--text-muted)"><?= $totalVotes ?> total votes cast</span>
    </div>
    <span class="badge badge-purple">Results Published</span>
  </div>

  <?php if ($winner): ?>
  <!-- Winner -->
  <div style="background:linear-gradient(135deg,rgba(79,70,229,0.15),rgba(6,182,212,0.15));border:1px solid rgba(255,215,0,0.3);border-radius:12px;padding:20px;text-align:center;margin-bottom:20px">
    <div style="font-size:2rem;margin-bottom:8px">👑</div>
    <img src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($winner['photo'] ?? 'default.png') ?>"
         onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'"
         style="width:70px;height:70px;border-radius:50%;border:3px solid gold;margin:0 auto 10px;display:block;object-fit:cover">
    <div style="font-size:1.1rem;font-weight:800"><?= htmlspecialchars($winner['name']) ?></div>
    <div style="color:var(--accent);font-weight:600;margin-top:4px"><?= $winner['total_votes'] ?> votes
      <?php if ($totalVotes > 0): ?>
        (<?= round($winner['total_votes']/$totalVotes*100,1) ?>%)
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Leaderboard -->
  <div class="leaderboard">
    <?php foreach ($results as $i => $r):
      $rankClass = ['rank-1','rank-2','rank-3'][$i] ?? 'rank-n';
      $pct = $totalVotes > 0 ? round($r['total_votes']/$totalVotes*100,1) : 0;
    ?>
    <div class="leaderboard-item">
      <div class="leaderboard-rank <?= $rankClass ?>"><?= $i+1 ?></div>
      <img class="leaderboard-photo" src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($r['photo']??'default.png') ?>"
           onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
      <div class="leaderboard-info">
        <div class="leaderboard-name"><?= htmlspecialchars($r['name']) ?></div>
        <div class="leaderboard-dept"><?= htmlspecialchars($r['position']??'') ?></div>
        <div class="progress-bar-wrap"><div class="progress-bar-fill" data-pct="<?= $pct ?>" style="width:<?= $pct ?>%"></div></div>
      </div>
      <div class="leaderboard-votes">
        <div class="leaderboard-count"><?= $r['total_votes'] ?></div>
        <div class="leaderboard-label"><?= $pct ?>%</div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
