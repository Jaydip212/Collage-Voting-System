<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('hod');
autoUpdateElectionStatus($pdo);

$hodId  = $_SESSION['user_id'];
$hod    = $pdo->prepare("SELECT h.*, d.name as dept_name FROM hods h LEFT JOIN departments d ON h.department_id=d.id WHERE h.id=?");
$hod->execute([$hodId]);
$hod    = $hod->fetch();
$deptId = $hod['department_id'];

// Fetch elections for this dept
$elections = $pdo->prepare("
    SELECT e.*, COUNT(DISTINCT v.vote_id) as vote_count, COUNT(DISTINCT c.id) as candidate_count
    FROM elections e
    LEFT JOIN votes v ON v.election_id=e.id
    LEFT JOIN candidates c ON c.election_id=e.id AND c.status='approved'
    WHERE (e.department_id=? OR e.department_id IS NULL)
    GROUP BY e.id ORDER BY e.start_datetime DESC
");
$elections->execute([$deptId]);
$elections = $elections->fetchAll();

$pageTitle  = 'Elections – HOD';
$activeMenu = 'elections';
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
  <h2 style="font-size:1.4rem;margin-bottom:4px">🗳️ Department Elections</h2>
  <p style="color:var(--text-muted)"><?= htmlspecialchars($hod['dept_name']) ?> · All elections visible to your department</p>
</div>

<div class="table-wrapper">
  <table class="table">
    <thead>
      <tr>
        <th>Election Title</th>
        <th>Type</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Candidates</th>
        <th>Votes</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($elections as $e):
        $badge = ['active'=>'badge-success','upcoming'=>'badge-info','completed'=>'badge-default','published'=>'badge-purple','frozen'=>'badge-warning'][$e['status']] ?? 'badge-default';
      ?>
      <tr class="searchable-row">
        <td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
        <td><span class="badge badge-info" style="font-size:0.7rem"><?= ucfirst($e['election_type']) ?></span></td>
        <td style="font-size:0.82rem;color:var(--text-muted)"><?= date('d M Y, h:i A', strtotime($e['start_datetime'])) ?></td>
        <td style="font-size:0.82rem;color:var(--text-muted)"><?= date('d M Y, h:i A', strtotime($e['end_datetime'])) ?></td>
        <td style="text-align:center"><?= $e['candidate_count'] ?></td>
        <td style="text-align:center;font-weight:700;color:var(--primary-light)"><?= $e['vote_count'] ?></td>
        <td><span class="badge <?= $badge ?>"><?= ucfirst($e['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($elections)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">No elections found for your department.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
