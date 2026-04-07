<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        $id = (int)$_POST['student_id'];
        $pdo->prepare("UPDATE students SET is_approved=1 WHERE id=?")->execute([$id]);
        logActivity($pdo,'admin',$_SESSION['user_id'],'STUDENT_APPROVED',"Approved student ID: $id");
        setFlash('success','Student approved.');
    } elseif ($action === 'reject') {
        $id = (int)$_POST['student_id'];
        $pdo->prepare("UPDATE students SET is_active=0, is_approved=0 WHERE id=?")->execute([$id]);
        setFlash('danger','Student rejected.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['student_id'];
        $pdo->prepare("DELETE FROM students WHERE id=?")->execute([$id]);
        setFlash('success','Student deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/students.php');
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$where  = ['1=1'];
$params = [];
if ($filter === 'pending')  { $where[] = 's.is_approved=0 AND s.email_verified=1'; }
if ($filter === 'approved') { $where[] = 's.is_approved=1'; }
if ($filter === 'unverified') { $where[] = 's.email_verified=0'; }
if ($search) {
    $like = "%{$search}%";
    $where[] = '(s.full_name LIKE ? OR s.roll_number LIKE ? OR s.email LIKE ?)';
    $params = array_merge($params, [$like, $like, $like]);
}

$stmt = $pdo->prepare("SELECT s.*, d.name as dept_name FROM students s LEFT JOIN departments d ON s.department_id=d.id WHERE ".implode(' AND ',$where)." ORDER BY s.created_at DESC");
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle='Students'; $activeMenu='students';
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
    <h2 style="font-size:1.4rem;margin-bottom:4px">Manage Students</h2>
    <p style="color:var(--text-muted);font-size:0.85rem">Approve registrations and manage student accounts</p>
  </div>
  <span class="badge badge-info"><?= count($students) ?> records</span>
</div>

<div class="filter-row">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <div style="position:relative">
      <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
      <input type="text" name="q" class="form-control" placeholder="Search by name, roll no..." value="<?= htmlspecialchars($search) ?>" style="padding-left:44px;max-width:260px">
    </div>
    <?php foreach (['all'=>'All','pending'=>'Pending','approved'=>'Approved','unverified'=>'Unverified'] as $k=>$v): ?>
      <a href="?filter=<?= $k ?>" class="btn btn-sm <?= $filter===$k ? 'btn-primary' : 'btn-outline' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </form>
</div>

<div class="glass-card" style="padding:0;overflow:hidden">
  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr><th>#</th><th>Student</th><th>Roll No</th><th>Department</th><th>Year</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
          <tr class="searchable-row">
            <td style="color:var(--text-muted);font-size:0.8rem"><?= $s['id'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($s['profile_photo']) ?>"
                     style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--border)"
                     onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
                <div>
                  <div style="font-weight:600;font-size:0.9rem"><?= htmlspecialchars($s['full_name']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($s['email']) ?></div>
                </div>
              </div>
            </td>
            <td><code style="font-size:0.8rem"><?= htmlspecialchars($s['roll_number']) ?></code></td>
            <td><?= htmlspecialchars($s['dept_name'] ?? '—') ?></td>
            <td><?= $s['year'] ?>st Year</td>
            <td>
              <?php if (!$s['email_verified']): ?>
                <span class="badge badge-warning">Email Unverified</span>
              <?php elseif (!$s['is_approved']): ?>
                <span class="badge badge-warning">Pending</span>
              <?php else: ?>
                <span class="badge badge-success">Approved</span>
              <?php endif; ?>
            </td>
            <td style="font-size:0.78rem;color:var(--text-muted)"><?= date('d M Y',strtotime($s['created_at'])) ?></td>
            <td>
              <div style="display:flex;gap:6px">
                <?php if (!$s['is_approved'] && $s['email_verified']): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="fas fa-check"></i></button>
                  </form>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-warning" title="Reject" data-confirm="Reject this student?"><i class="fas fa-ban"></i></button>
                  </form>
                <?php endif; ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger" title="Delete" data-confirm="Delete this student permanently?"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($students)): ?>
          <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">No students found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
