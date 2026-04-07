<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

autoUpdateElectionStatus($pdo);

$departments = getDepartments($pdo);
$message = '';

// ── HANDLE ACTIONS ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $id          = (int)($_POST['election_id'] ?? 0);
        $title       = sanitize($_POST['title'] ?? '');
        $eType       = sanitize($_POST['election_type'] ?? 'student');
        $deptId      = ($_POST['department_id'] ?? '') !== '' ? (int)$_POST['department_id'] : null;
        $desc        = sanitize($_POST['description'] ?? '');
        $startDt     = sanitize($_POST['start_datetime'] ?? '');
        $endDt       = sanitize($_POST['end_datetime'] ?? '');
        $status      = sanitize($_POST['status'] ?? 'upcoming');

        // Banner upload
        $bannerName = null;
        if (!empty($_FILES['banner_image']['name'])) {
            $up = uploadFile($_FILES['banner_image'], UPLOAD_BANNERS, ['jpg','jpeg','png','gif','webp']);
            if ($up['success']) $bannerName = $up['filename'];
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO elections (title,election_type,department_id,description,start_datetime,end_datetime,status,banner_image,created_by) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$title,$eType,$deptId,$desc,$startDt,$endDt,$status,$bannerName,$_SESSION['user_id']]);
            $eid = $pdo->lastInsertId();
            logActivity($pdo,'admin',$_SESSION['user_id'],'ELECTION_CREATED',"Created: $title (ID: $eid)");
            addNotification($pdo,'all',null,'New Election Created',"$title is now available!",'info');
            setFlash('success',"Election '$title' created successfully!");
        } else {
            $sets = "title=?,election_type=?,department_id=?,description=?,start_datetime=?,end_datetime=?,status=?";
            $vals = [$title,$eType,$deptId,$desc,$startDt,$endDt,$status];
            if ($bannerName) { $sets .= ",banner_image=?"; $vals[] = $bannerName; }
            $vals[] = $id;
            $pdo->prepare("UPDATE elections SET $sets WHERE id=?")->execute($vals);
            logActivity($pdo,'admin',$_SESSION['user_id'],'ELECTION_UPDATED',"Updated: $title (ID: $id)");
            setFlash('success',"Election updated successfully!");
        }
    }

    if ($action === 'freeze') {
        $id = (int)$_POST['election_id'];
        $pdo->prepare("UPDATE elections SET status='frozen' WHERE id=?")->execute([$id]);
        setFlash('success','Election frozen.');
    }

    if ($action === 'activate') {
        $id = (int)$_POST['election_id'];
        $pdo->prepare("UPDATE elections SET status='active' WHERE id=?")->execute([$id]);
        setFlash('success','Election activated.');
    }

    if ($action === 'publish') {
        $id = (int)$_POST['election_id'];
        publishResults($pdo, $id);
        logActivity($pdo,'admin',$_SESSION['user_id'],'RESULTS_PUBLISHED',"Published results for Election ID: $id");
        setFlash('success','Results published!');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['election_id'];
        $pdo->prepare("DELETE FROM elections WHERE id=?")->execute([$id]);
        logActivity($pdo,'admin',$_SESSION['user_id'],'ELECTION_DELETED',"Deleted Election ID: $id");
        setFlash('success','Election deleted.');
    }

    header('Location: ' . BASE_URL . '/admin/elections.php');
    exit;
}

// ── FILTER ─────────────────────────────────────────────────
$filter   = $_GET['status'] ?? 'all';
$deptFilt = $_GET['dept'] ?? '';
$search   = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filter !== 'all') { $where[] = "e.status=?"; $params[] = $filter; }
if ($deptFilt)         { $where[] = "e.department_id=?"; $params[] = $deptFilt; }
if ($search)           { $where[] = "e.title LIKE ?"; $params[] = "%$search%"; }

$sql = "SELECT e.*, d.name as dept_name, COUNT(v.vote_id) as vote_count
        FROM elections e
        LEFT JOIN departments d ON e.department_id=d.id
        LEFT JOIN votes v ON v.election_id=e.id
        WHERE " . implode(' AND ',$where) . "
        GROUP BY e.id ORDER BY e.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$elections = $stmt->fetchAll();

// Edit mode
$editElection = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM elections WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editElection = $s->fetch();
}

$pageTitle  = 'Elections';
$activeMenu = 'elections';
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
  ['section'=>'SYSTEM'],
  ['icon'=>'fas fa-bullhorn','label'=>'Announcements','href'=>BASE_URL.'/admin/announcements.php','key'=>'announcements'],
  ['icon'=>'fas fa-history','label'=>'Audit Logs','href'=>BASE_URL.'/admin/audit_logs.php','key'=>'auditlogs'],
  ['icon'=>'fas fa-database','label'=>'Backup','href'=>BASE_URL.'/admin/backup.php','key'=>'backup'],
];

require_once __DIR__ . '/../includes/dashboard_header.php';

$electionTypes = ['student'=>'Student','teacher'=>'Teacher','hod'=>'HOD','cr'=>'Class CR','cultural'=>'Cultural','sports'=>'Sports','general'=>'General'];
$statusBadges  = ['upcoming'=>'badge-info','active'=>'badge-success','frozen'=>'badge-warning','completed'=>'badge-default','published'=>'badge-purple'];
?>

<!-- ── HEADER ROW ───────────────────────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
  <div>
    <h2 style="font-size:1.4rem;margin-bottom:4px">Manage Elections</h2>
    <p style="color:var(--text-muted);font-size:0.85rem">Create, edit, and manage all college elections</p>
  </div>
  <button class="btn btn-primary" data-modal-open="createElectionModal">
    <i class="fas fa-plus"></i> Create Election
  </button>
</div>

<!-- ── FILTERS ──────────────────────────────────────────── -->
<div class="filter-row">
  <div class="search-bar" style="max-width:280px">
    <form method="GET" style="position:relative">
      <i class="fas fa-search search-icon" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
      <input type="text" name="q" class="form-control" placeholder="Search elections..." value="<?= htmlspecialchars($search) ?>" style="padding-left:44px">
    </form>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php foreach (['all'=>'All','upcoming'=>'Upcoming','active'=>'Active','frozen'=>'Frozen','completed'=>'Completed','published'=>'Published'] as $k=>$v): ?>
      <a href="?status=<?= $k ?>" class="btn btn-sm <?= $filter===$k ? 'btn-primary' : 'btn-outline' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── ELECTIONS TABLE ──────────────────────────────────── -->
<div class="glass-card" style="padding:0;overflow:hidden">
  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Election</th>
          <th>Type</th>
          <th>Department</th>
          <th>Schedule</th>
          <th>Votes</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($elections as $i => $e): ?>
          <tr class="searchable-row">
            <td style="color:var(--text-muted);font-size:0.8rem">#<?= $e['id'] ?></td>
            <td>
              <div style="font-weight:600;font-size:0.9rem"><?= htmlspecialchars($e['title']) ?></div>
              <?php if ($e['description']): ?>
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars(substr($e['description'],0,60)) ?>...</div>
              <?php endif; ?>
            </td>
            <td><span class="badge badge-info"><?= $electionTypes[$e['election_type']] ?? $e['election_type'] ?></span></td>
            <td><?= $e['dept_name'] ? htmlspecialchars($e['dept_name']) : '<span style="color:var(--text-muted)">All Depts</span>' ?></td>
            <td style="font-size:0.78rem">
              <div>📅 <?= date('d M Y', strtotime($e['start_datetime'])) ?></div>
              <div style="color:var(--text-muted)">→ <?= date('d M Y', strtotime($e['end_datetime'])) ?></div>
            </td>
            <td><strong><?= number_format($e['vote_count']) ?></strong></td>
            <td><span class="badge <?= $statusBadges[$e['status']] ?? 'badge-default' ?>"><?= ucfirst($e['status']) ?></span></td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="?edit=<?= $e['id'] ?>" class="btn btn-sm btn-outline" title="Edit"><i class="fas fa-edit"></i></a>

                <?php if ($e['status'] === 'active'): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="freeze">
                    <input type="hidden" name="election_id" value="<?= $e['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-warning" title="Freeze" data-confirm="Freeze this election?"><i class="fas fa-pause"></i></button>
                  </form>
                <?php elseif ($e['status'] === 'frozen'): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="election_id" value="<?= $e['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success" title="Activate" data-confirm="Activate this election?"><i class="fas fa-play"></i></button>
                  </form>
                <?php endif; ?>

                <?php if (in_array($e['status'],['completed','frozen']) && !$e['is_result_published']): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="publish">
                    <input type="hidden" name="election_id" value="<?= $e['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-accent" title="Publish Results" data-confirm="Publish results for this election?"><i class="fas fa-bullhorn"></i></button>
                  </form>
                <?php endif; ?>

                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="election_id" value="<?= $e['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger" title="Delete" data-confirm="Permanently delete this election?"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($elections)): ?>
          <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">
            No elections found. <a href="javascript:void(0)" data-modal-open="createElectionModal">Create one now →</a>
          </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── CREATE ELECTION MODAL ────────────────────────────── -->
<div class="modal-overlay" id="createElectionModal" style="display:none">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <h3 class="modal-title">Create New Election</h3>
      <button class="modal-close" data-modal-close="createElectionModal">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">

        <div class="form-group">
          <label class="form-label">Election Title *</label>
          <input type="text" name="title" class="form-control" required placeholder="e.g. BCA CR Election 2026">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Election Type *</label>
            <select name="election_type" class="form-control" required>
              <?php foreach ($electionTypes as $k=>$v): ?>
                <option value="<?= $k ?>"><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-control">
              <option value="">All Departments</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Start Date & Time *</label>
            <input type="datetime-local" name="start_datetime" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Date & Time *</label>
            <input type="datetime-local" name="end_datetime" class="form-control" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" placeholder="Brief description..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Election Banner</label>
          <input type="file" name="banner_image" class="form-control" accept="image/*">
        </div>

        <div class="form-group">
          <label class="form-label">Initial Status</label>
          <select name="status" class="form-control">
            <option value="upcoming">Upcoming</option>
            <option value="active">Active Now</option>
          </select>
        </div>

        <div class="modal-footer" style="padding:0;margin-top:10px">
          <button type="button" class="btn btn-outline" data-modal-close="createElectionModal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Election</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($editElection): ?>
<!-- ── EDIT ELECTION MODAL (auto-open) ──────────────────── -->
<div class="modal-overlay" id="editElectionModal" style="display:flex">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <h3 class="modal-title">Edit Election</h3>
      <button class="modal-close" onclick="document.getElementById('editElectionModal').style.display='none'">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="election_id" value="<?= $editElection['id'] ?>">
        <div class="form-group">
          <label class="form-label">Election Title *</label>
          <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($editElection['title']) ?>">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="election_type" class="form-control">
              <?php foreach ($electionTypes as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $editElection['election_type']===$k?'selected':'' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-control">
              <option value="">All Departments</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $editElection['department_id']==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Start Date & Time</label>
            <input type="datetime-local" name="start_datetime" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($editElection['start_datetime'])) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">End Date & Time</label>
            <input type="datetime-local" name="end_datetime" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($editElection['end_datetime'])) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <?php foreach (['upcoming','active','frozen','completed'] as $s): ?>
              <option value="<?= $s ?>" <?= $editElection['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control"><?= htmlspecialchars($editElection['description']) ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">New Banner (optional)</label>
          <input type="file" name="banner_image" class="form-control" accept="image/*">
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px">
          <a href="<?= BASE_URL ?>/admin/elections.php" class="btn btn-outline">Cancel</a>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Election</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
