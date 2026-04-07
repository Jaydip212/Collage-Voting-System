<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $id   = (int)($_POST['dept_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $code = strtoupper(sanitize($_POST['code'] ?? ''));
        $desc = sanitize($_POST['description'] ?? '');
        if ($action === 'add') {
            $pdo->prepare("INSERT INTO departments (name,code,description) VALUES (?,?,?)")->execute([$name,$code,$desc]);
            logActivity($pdo,'admin',$_SESSION['user_id'],'DEPT_CREATED',"Dept: $name");
            setFlash('success',"Department '$name' created.");
        } else {
            $pdo->prepare("UPDATE departments SET name=?,code=?,description=? WHERE id=?")->execute([$name,$code,$desc,$id]);
            setFlash('success',"Department updated.");
        }
    }
    if ($action === 'toggle') {
        $id = (int)$_POST['dept_id'];
        $pdo->prepare("UPDATE departments SET is_active=1-is_active WHERE id=?")->execute([$id]);
        setFlash('success','Department status toggled.');
    }
    if ($action === 'delete') {
        $id = (int)$_POST['dept_id'];
        try {
            $pdo->prepare("DELETE FROM departments WHERE id=?")->execute([$id]);
            setFlash('success','Department deleted.');
        } catch (Exception $e) {
            setFlash('danger','Cannot delete: department has linked students or elections.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/departments.php');
    exit;
}

$depts = $pdo->query("
    SELECT d.*, COUNT(DISTINCT s.id) as student_count, COUNT(DISTINCT e.id) as election_count
    FROM departments d
    LEFT JOIN students s ON s.department_id=d.id
    LEFT JOIN elections e ON e.department_id=d.id
    GROUP BY d.id ORDER BY d.name
")->fetchAll();

$editDept = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM departments WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editDept = $s->fetch();
}

$pageTitle='Departments'; $activeMenu='departments';
$sidebarLinks = [
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/admin/index.php','key'=>'dashboard'],
  ['section'=>'ELECTIONS'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/admin/elections.php','key'=>'elections'],
  ['icon'=>'fas fa-user-tie','label'=>'Candidates','href'=>BASE_URL.'/admin/candidates.php','key'=>'candidates'],
  ['section'=>'MANAGEMENT'],
  ['icon'=>'fas fa-building','label'=>'Departments','href'=>BASE_URL.'/admin/departments.php','key'=>'departments'],
  ['icon'=>'fas fa-users','label'=>'Students','href'=>BASE_URL.'/admin/students.php','key'=>'students'],
  ['icon'=>'fas fa-chalkboard-teacher','label'=>'Teachers','href'=>BASE_URL.'/admin/teachers.php','key'=>'teachers'],
];
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
  <div>
    <h2 style="font-size:1.4rem;margin-bottom:4px">Departments</h2>
    <p style="color:var(--text-muted);font-size:0.85rem">Manage college departments</p>
  </div>
  <button class="btn btn-primary" data-modal-open="deptModal"><i class="fas fa-plus"></i> Add Department</button>
</div>

<div class="grid-auto">
  <?php
  $deptIcons=['CS'=>'💻','IT'=>'🖧','CE'=>'🏗️','ME'=>'⚙️','ET'=>'📡','MBA'=>'📊','BCA'=>'🖥️','MCA'=>'🎓'];
  foreach ($depts as $d): ?>
    <div class="glass-card" style="padding:24px;<?= !$d['is_active'] ? 'opacity:0.6' : '' ?>">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:12px">
          <div style="font-size:1.8rem"><?= $deptIcons[$d['code']] ?? '🏫' ?></div>
          <div>
            <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($d['name']) ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($d['code']) ?></div>
          </div>
        </div>
        <span class="badge <?= $d['is_active'] ? 'badge-success' : 'badge-default' ?>"><?= $d['is_active'] ? 'Active' : 'Inactive' ?></span>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px">
        <div style="padding:8px;background:var(--bg-glass);border-radius:8px;text-align:center">
          <div style="font-size:1.1rem;font-weight:700;color:var(--primary-light)"><?= $d['student_count'] ?></div>
          <div style="font-size:0.7rem;color:var(--text-muted)">Students</div>
        </div>
        <div style="padding:8px;background:var(--bg-glass);border-radius:8px;text-align:center">
          <div style="font-size:1.1rem;font-weight:700;color:var(--accent)"><?= $d['election_count'] ?></div>
          <div style="font-size:0.7rem;color:var(--text-muted)">Elections</div>
        </div>
      </div>

      <?php if ($d['description']): ?>
        <p style="font-size:0.78rem;color:var(--text-muted);line-height:1.5;margin-bottom:14px"><?= htmlspecialchars(substr($d['description'],0,80)) ?></p>
      <?php endif; ?>

      <div style="display:flex;gap:8px">
        <a href="?edit=<?= $d['id'] ?>" class="btn btn-sm btn-outline" style="flex:1;justify-content:center"><i class="fas fa-edit"></i> Edit</a>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
          <button type="submit" class="btn btn-sm <?= $d['is_active'] ? 'btn-warning' : 'btn-success' ?>"
                  data-confirm="Toggle department status?" title="Toggle">
            <i class="fas <?= $d['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
          </button>
        </form>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
          <button type="submit" class="btn btn-sm btn-danger" data-confirm="Delete this department? This will fail if students are assigned to it.">
            <i class="fas fa-trash"></i>
          </button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="deptModal" style="display:<?= $editDept ? 'none' : 'none' ?>">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Add Department</h3>
      <button class="modal-close" data-modal-close="deptModal">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
          <label class="form-label">Department Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. Computer Science">
        </div>
        <div class="form-group">
          <label class="form-label">Department Code *</label>
          <input type="text" name="code" class="form-control" required placeholder="e.g. CS" maxlength="10">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2" placeholder="Brief description..."></textarea>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end">
          <button type="button" class="btn btn-outline" data-modal-close="deptModal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Department</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($editDept): ?>
<div class="modal-overlay" id="editDeptModal" style="display:flex">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Edit Department</h3>
      <a href="<?= BASE_URL ?>/admin/departments.php" class="modal-close">✕</a>
    </div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="dept_id" value="<?= $editDept['id'] ?>">
        <div class="form-group">
          <label class="form-label">Department Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editDept['name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Code</label>
          <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($editDept['code']) ?>" required maxlength="10">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($editDept['description']) ?></textarea>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end">
          <a href="<?= BASE_URL ?>/admin/departments.php" class="btn btn-outline">Cancel</a>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
