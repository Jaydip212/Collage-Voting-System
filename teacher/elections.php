<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('teacher');
autoUpdateElectionStatus($pdo);
$teacher = getTeacherById($pdo, $_SESSION['user_id']);
$statusFilt = $_GET['status'] ?? 'all';
$where = ["e.election_type='teacher'"];
$params = [];
if ($statusFilt !== 'all') { $where[] = "e.status=?"; $params[] = $statusFilt; }
$stmt = $pdo->prepare("SELECT e.*, COUNT(DISTINCT c.id) as candidate_count, COUNT(DISTINCT v.vote_id) as total_votes, (SELECT COUNT(*) FROM votes WHERE election_id=e.id AND voter_type='teacher' AND voter_id=?) as my_vote FROM elections e LEFT JOIN candidates c ON c.election_id=e.id AND c.status='approved' LEFT JOIN votes v ON v.election_id=e.id WHERE ".implode(' AND ',$where)." GROUP BY e.id ORDER BY e.start_datetime DESC");
$stmt->execute(array_merge([$_SESSION['user_id']],$params));
$elections = $stmt->fetchAll();

$pageTitle='Teacher Elections'; $activeMenu='elections';
$sidebarLinks=[
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/teacher/index.php','key'=>'dashboard'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/teacher/elections.php','key'=>'elections'],
  ['icon'=>'fas fa-poll-h','label'=>'Results','href'=>BASE_URL.'/teacher/results.php','key'=>'results'],
  ['icon'=>'fas fa-user-circle','label'=>'My Profile','href'=>BASE_URL.'/teacher/profile.php','key'=>'profile'],
];
$roleLabel='Teacher';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>
<div style="margin-bottom:24px"><h2 style="font-size:1.4rem;margin-bottom:4px">Teacher Elections</h2><p style="color:var(--text-muted);font-size:0.85rem">Teacher-specific elections</p></div>
<div class="filter-row" style="margin-bottom:20px">
  <?php foreach(['all'=>'All','active'=>'🔴 Active','upcoming'=>'📅 Upcoming','published'=>'📊 Results'] as $k=>$v): ?>
    <a href="?status=<?= $k ?>" class="btn btn-sm <?= $statusFilt===$k?'btn-primary':'btn-outline' ?>"><?= $v ?></a>
  <?php endforeach; ?>
</div>
<?php if (!empty($elections)): ?>
  <div class="grid-auto">
  <?php foreach ($elections as $e): $hasVoted=$e['my_vote']>0; $isActive=$e['status']==='active'; ?>
    <div class="election-card">
      <div class="election-banner" style="background:linear-gradient(135deg,rgba(79,70,229,0.6),rgba(6,182,212,0.6))">
        <?php if ($e['banner_image']): ?><img src="<?= BASE_URL ?>/uploads/election_banners/<?= htmlspecialchars($e['banner_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover"><?php else: ?><span style="font-size:3rem">👩‍🏫</span><?php endif; ?>
      </div>
      <div class="election-body">
        <div style="margin-bottom:8px"><span class="badge <?= ['active'=>'badge-success','upcoming'=>'badge-info','published'=>'badge-purple','completed'=>'badge-default'][$e['status']]??'badge-default' ?>" style="font-size:0.68rem"><?= ucfirst($e['status']) ?></span><?php if ($hasVoted): ?> <span class="badge badge-success" style="font-size:0.68rem">✅ Voted</span><?php endif; ?></div>
        <div class="election-title"><?= htmlspecialchars($e['title']) ?></div>
        <div class="election-meta"><span><i class="fas fa-users"></i> <?= $e['candidate_count'] ?> candidates · <?= $e['total_votes'] ?> votes</span><span><i class="fas fa-calendar"></i> <?= date('d M Y',strtotime($e['end_datetime'])) ?></span></div>
        <?php if ($isActive): ?><div data-countdown="<?= $e['end_datetime'] ?>"></div><?php endif; ?>
      </div>
      <div class="election-footer">
        <?php if ($isActive && !$hasVoted): ?>
          <a href="<?= BASE_URL ?>/teacher/vote.php?election_id=<?= $e['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;justify-content:center"><i class="fas fa-vote-yea"></i> Vote Now</a>
        <?php elseif ($e['status']==='published'): ?>
          <a href="<?= BASE_URL ?>/teacher/results.php?election_id=<?= $e['id'] ?>" class="btn btn-accent btn-sm" style="flex:1;justify-content:center"><i class="fas fa-poll-h"></i> Results</a>
        <?php else: ?>
          <div class="btn btn-sm btn-outline" style="flex:1;justify-content:center;cursor:default"><i class="fas fa-clock"></i> <?= ucfirst($e['status']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="glass-card" style="padding:60px;text-align:center"><div style="font-size:4rem;margin-bottom:16px">🗳️</div><h3>No Teacher Elections</h3><p style="color:var(--text-muted)">No teacher elections available.</p></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
