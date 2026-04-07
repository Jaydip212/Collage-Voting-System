<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// Handle actions - same as students.php but for teachers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)$_POST['teacher_id'];
    if ($action === 'approve') {
        $pdo->prepare("UPDATE teachers SET is_approved=1 WHERE id=?")->execute([$id]);
        logActivity($pdo,'admin',$_SESSION['user_id'],'TEACHER_APPROVED',"Approved teacher ID: $id");
        setFlash('success','Teacher approved.');
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE teachers SET is_active=0, is_approved=0 WHERE id=?")->execute([$id]);
        setFlash('danger','Teacher rejected.');
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM teachers WHERE id=?")->execute([$id]);
        setFlash('success','Teacher deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/teachers.php');
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$where  = ['1=1'];
$params = [];
if ($filter === 'pending')  { $where[] = 't.is_approved=0 AND t.email_verified=1'; }
if ($filter === 'approved') { $where[] = 't.is_approved=1'; }
if ($search) {
    $like = "%{$search}%";
    $where[] = '(t.full_name LIKE ? OR t.teacher_id LIKE ? OR t.email LIKE ?)';
    $params = array_merge($params, [$like, $like, $like]);
}

$stmt = $pdo->prepare("SELECT t.*, d.name as dept_name FROM teachers t LEFT JOIN departments d ON t.department_id=d.id WHERE ".implode(' AND ',$where)." ORDER BY t.created_at DESC");
$stmt->execute($params);
$teachers = $stmt->fetchAll();

$pageTitle='Teachers'; $activeMenu='teachers';
$sidebarLinks=[
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/admin/index.php','key'=>'dashboard'],
  ['section'=>'ELECTIONS'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/admin/elections.php','key'=>'elections'],
  ['section'=>'MANAGEMENT'],
  ['icon'=>'fas fa-building','label'=>'Departments','href'=>BASE_URL.'/admin/departments.php','key'=>'departments'],
  ['icon'=>'fas fa-users','label'=>'Students','href'=>BASE_URL.'/admin/students.php','key'=>'students'],
  ['icon'=>'fas fa-chalkboard-teacher','label'=>'Teachers','href'=>BASE_URL.'/admin/teachers.php','key'=>'teachers'],
];
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
  <div><h2 style="font-size:1.4rem;margin-bottom:4px">Manage Teachers</h2><p style="color:var(--text-muted);font-size:0.85rem">Approve and manage faculty accounts</p></div>
  <span class="badge badge-info"><?= count($teachers) ?> records</span>
</div>

<div class="filter-row">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <div style="position:relative">
      <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
      <input type="text" name="q" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" style="padding-left:44px;max-width:240px">
    </div>
    <?php foreach (['all'=>'All','pending'=>'Pending','approved'=>'Approved'] as $k=>$v): ?>
      <a href="?filter=<?= $k ?>" class="btn btn-sm <?= $filter===$k?'btn-primary':'btn-outline' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </form>
</div>

<div class="glass-card" style="padding:0;overflow:hidden;margin-top:20px">
  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr><th>#</th><th>Teacher</th><th>Teacher ID</th><th>Department</th><th>Designation</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($teachers as $t): ?>
          <tr class="searchable-row">
            <td style="color:var(--text-muted);font-size:0.8rem"><?= $t['id'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($t['profile_photo']) ?>"
                     style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--border)"
                     onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
                <div>
                  <div style="font-weight:600;font-size:0.9rem"><?= htmlspecialchars($t['full_name']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($t['email']) ?></div>
                </div>
              </div>
            </td>
            <td><code style="font-size:0.8rem"><?= htmlspecialchars($t['teacher_id']) ?></code></td>
            <td><?= htmlspecialchars($t['dept_name']??'—') ?></td>
            <td style="font-size:0.85rem"><?= htmlspecialchars($t['designation']??'—') ?></td>
            <td>
              <?php if (!$t['email_verified']): ?><span class="badge badge-warning">Email Unverified</span>
              <?php elseif (!$t['is_approved']): ?><span class="badge badge-warning">Pending</span>
              <?php else: ?><span class="badge badge-success">Approved</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:6px">
                <?php if (!$t['is_approved'] && $t['email_verified']): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="approve"><input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="fas fa-check"></i></button>
                  </form>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="reject"><input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-warning" data-confirm="Reject?" title="Reject"><i class="fas fa-ban"></i></button>
                  </form>
                <?php endif; ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger" data-confirm="Delete?" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($teachers)): ?>
          <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">No teachers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
