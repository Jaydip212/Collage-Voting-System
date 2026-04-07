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

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cid    = (int)($_POST['candidate_id'] ?? 0);
    if ($action === 'approve' && $cid) {
        $pdo->prepare("UPDATE candidates SET status='approved' WHERE id=?")->execute([$cid]);
        logActivity($pdo,'hod',$hodId,'CANDIDATE_APPROVED',"Candidate #$cid approved");
        setFlash('success','Candidate approved successfully!');
    } elseif ($action === 'reject' && $cid) {
        $pdo->prepare("UPDATE candidates SET status='rejected' WHERE id=?")->execute([$cid]);
        setFlash('success','Candidate rejected.');
    }
    header('Location: ' . BASE_URL . '/hod/candidates.php');
    exit;
}

// Filter
$statusFilter = $_GET['status'] ?? 'all';
$whereStatus  = $statusFilter !== 'all' ? "AND c.status='$statusFilter'" : '';

$candidates = $pdo->prepare("
    SELECT c.*, e.title as election_title, e.status as election_status
    FROM candidates c
    LEFT JOIN elections e ON c.election_id=e.id
    WHERE e.department_id=? $whereStatus
    ORDER BY c.created_at DESC
");
$candidates->execute([$deptId]);
$candidates = $candidates->fetchAll();

$pageTitle  = 'Candidates – HOD';
$activeMenu = 'candidates';
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

<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
  <div>
    <h2 style="font-size:1.4rem;margin-bottom:4px">👤 Candidate Management</h2>
    <p style="color:var(--text-muted)"><?= htmlspecialchars($hod['dept_name']) ?> · Approve or reject candidate nominations</p>
  </div>
  <div style="display:flex;gap:8px">
    <?php foreach(['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $k=>$v): ?>
      <a href="?status=<?= $k ?>" class="btn btn-sm <?= $statusFilter===$k?'btn-primary':'btn-outline' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="table-wrapper">
  <table class="table">
    <thead>
      <tr>
        <th>Candidate</th>
        <th>Election</th>
        <th>Position</th>
        <th>Election Status</th>
        <th>Status</th>
        <th>Applied On</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($candidates as $c):
        $statusColor = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'][$c['status']] ?? 'badge-default';
        $elBadge = ['active'=>'badge-success','upcoming'=>'badge-info','completed'=>'badge-default'][$c['election_status']] ?? 'badge-default';
      ?>
      <tr class="searchable-row">
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <img src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($c['photo']) ?>"
                 onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'"
                 style="width:36px;height:36px;border-radius:50%;object-fit:cover">
            <div>
              <div style="font-weight:600;font-size:0.88rem"><?= htmlspecialchars($c['name']) ?></div>
              <?php if ($c['position']): ?>
                <div style="font-size:0.72rem;color:var(--text-muted)"><?= htmlspecialchars($c['position']) ?></div>
              <?php endif; ?>
            </div>
          </div>
        </td>
        <td style="font-size:0.82rem"><?= htmlspecialchars($c['election_title']) ?></td>
        <td style="font-size:0.82rem"><?= htmlspecialchars($c['position'] ?? '—') ?></td>
        <td><span class="badge <?= $elBadge ?>" style="font-size:0.68rem"><?= ucfirst($c['election_status']) ?></span></td>
        <td><span class="badge <?= $statusColor ?>"><?= ucfirst($c['status']) ?></span></td>
        <td style="font-size:0.78rem;color:var(--text-muted)"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
        <td>
          <?php if ($c['status'] === 'pending'): ?>
          <div style="display:flex;gap:6px">
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="approve">
              <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
              <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approve</button>
            </form>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="reject">
              <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
              <button class="btn btn-sm btn-danger" data-confirm="Reject this candidate?"><i class="fas fa-times"></i> Reject</button>
            </form>
          </div>
          <?php else: ?>
            <span style="color:var(--text-muted);font-size:0.8rem">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($candidates)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">No candidates found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
