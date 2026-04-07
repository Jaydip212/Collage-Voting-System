<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$message = '';
$departments = getDepartments($pdo);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id          = (int)($_POST['candidate_id'] ?? 0);
        $electionId  = (int)$_POST['election_id'];
        $userType    = sanitize($_POST['user_type'] ?? 'student');
        $userId      = (int)$_POST['user_id'];
        $name        = sanitize($_POST['name'] ?? '');
        $deptId      = $_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null;
        $position    = sanitize($_POST['position'] ?? '');
        $candNo      = (int)($_POST['candidate_number'] ?? 0);
        $manifesto   = sanitize($_POST['manifesto'] ?? '');
        $bio         = sanitize($_POST['bio'] ?? '');
        $status      = sanitize($_POST['status'] ?? 'pending');

        $photoName = 'default.png';
        if (!empty($_FILES['photo']['name'])) {
            $up = uploadFile($_FILES['photo'], UPLOAD_CANDIDATES);
            if ($up['success']) $photoName = $up['filename'];
        }
        $symbolName = null;
        if (!empty($_FILES['symbol']['name'])) {
            $up = uploadFile($_FILES['symbol'], UPLOAD_CANDIDATES);
            if ($up['success']) $symbolName = $up['filename'];
        }

        if ($action === 'add') {
            try {
                $stmt = $pdo->prepare("INSERT INTO candidates (election_id,user_type,user_id,name,department_id,position,candidate_number,photo,symbol,manifesto,bio,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$electionId,$userType,$userId,$name,$deptId,$position,$candNo,$photoName,$symbolName,$manifesto,$bio,$status]);
                logActivity($pdo,'admin',$_SESSION['user_id'],'CANDIDATE_ADDED',"Added: $name");
                setFlash('success',"Candidate '$name' added successfully!");
            } catch (Exception $e) {
                setFlash('danger','This candidate is already registered in this election.');
            }
        } else {
            $sets = "name=?,department_id=?,position=?,candidate_number=?,manifesto=?,bio=?,status=?";
            $vals = [$name,$deptId,$position,$candNo,$manifesto,$bio,$status];
            if ($photoName !== 'default.png') { $sets .= ",photo=?"; $vals[] = $photoName; }
            if ($symbolName) { $sets .= ",symbol=?"; $vals[] = $symbolName; }
            $vals[] = $id;
            $pdo->prepare("UPDATE candidates SET $sets WHERE id=?")->execute($vals);
            setFlash('success','Candidate updated.');
        }
    }

    if ($action === 'approve') {
        $id = (int)$_POST['candidate_id'];
        $pdo->prepare("UPDATE candidates SET status='approved' WHERE id=?")->execute([$id]);
        setFlash('success','Candidate approved.');
    }

    if ($action === 'reject') {
        $id = (int)$_POST['candidate_id'];
        $pdo->prepare("UPDATE candidates SET status='rejected' WHERE id=?")->execute([$id]);
        setFlash('success','Candidate rejected.');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['candidate_id'];
        $pdo->prepare("DELETE FROM candidates WHERE id=?")->execute([$id]);
        setFlash('success','Candidate removed.');
    }

    header('Location: ' . BASE_URL . '/admin/candidates.php');
    exit;
}

// Filters
$electionFilt = (int)($_GET['election_id'] ?? 0);
$statusFilt   = $_GET['status'] ?? 'all';
$search       = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];
if ($electionFilt) { $where[] = "c.election_id=?"; $params[] = $electionFilt; }
if ($statusFilt !== 'all') { $where[] = "c.status=?"; $params[] = $statusFilt; }
if ($search) { $where[] = "c.name LIKE ?"; $params[] = "%$search%"; }

$stmt = $pdo->prepare("SELECT c.*, e.title as election_title, d.name as dept_name FROM candidates c LEFT JOIN elections e ON c.election_id=e.id LEFT JOIN departments d ON c.department_id=d.id WHERE " . implode(' AND ',$where) . " ORDER BY c.created_at DESC");
$stmt->execute($params);
$candidates = $stmt->fetchAll();

$elections = $pdo->query("SELECT id,title FROM elections ORDER BY created_at DESC")->fetchAll();
$students  = $pdo->query("SELECT id,full_name,roll_number FROM students WHERE is_approved=1 AND is_active=1 ORDER BY full_name")->fetchAll();
$teachers  = $pdo->query("SELECT id,full_name,teacher_id FROM teachers WHERE is_approved=1 AND is_active=1 ORDER BY full_name")->fetchAll();

$statusBadges = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'];

$pageTitle  = 'Candidates';
$activeMenu = 'candidates';
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
    <h2 style="font-size:1.4rem;margin-bottom:4px">Manage Candidates</h2>
    <p style="color:var(--text-muted);font-size:0.85rem">Add, approve, or reject candidates for elections</p>
  </div>
  <button class="btn btn-primary" data-modal-open="addCandModal"><i class="fas fa-plus"></i> Add Candidate</button>
</div>

<!-- Filters -->
<div class="filter-row">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <select name="election_id" class="form-control" style="max-width:220px" onchange="this.form.submit()">
      <option value="">All Elections</option>
      <?php foreach ($elections as $e): ?>
        <option value="<?= $e['id'] ?>" <?= $electionFilt==$e['id']?'selected':'' ?>><?= htmlspecialchars(substr($e['title'],0,40)) ?></option>
      <?php endforeach; ?>
    </select>
    <?php foreach (['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $k=>$v): ?>
      <a href="?status=<?= $k ?>&election_id=<?= $electionFilt ?>" class="btn btn-sm <?= $statusFilt===$k?'btn-primary':'btn-outline' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </form>
</div>

<!-- Candidate Cards -->
<div class="grid-auto">
  <?php foreach ($candidates as $c): ?>
    <div class="glass-card" style="padding:24px;text-align:center;position:relative">
      <div style="position:absolute;top:12px;left:12px">
        <span class="badge <?= $statusBadges[$c['status']] ?? 'badge-default' ?>"><?= ucfirst($c['status']) ?></span>
      </div>
      <?php if ($c['candidate_number']): ?>
        <div style="position:absolute;top:12px;right:12px;width:28px;height:28px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:#fff">
          <?= $c['candidate_number'] ?>
        </div>
      <?php endif; ?>

      <img src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($c['photo']) ?>"
           alt="<?= htmlspecialchars($c['name']) ?>"
           style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--border);margin:12px auto 14px"
           onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">

      <div style="font-weight:700;font-size:1rem;margin-bottom:4px"><?= htmlspecialchars($c['name']) ?></div>
      <div style="font-size:0.78rem;color:var(--accent);margin-bottom:4px"><?= htmlspecialchars($c['position'] ?? '') ?></div>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:12px"><?= htmlspecialchars($c['election_title'] ?? '') ?></div>

      <?php if ($c['bio']): ?>
        <p style="font-size:0.78rem;color:var(--text-muted);line-height:1.5;margin-bottom:12px"><?= htmlspecialchars(substr($c['bio'],0,100)) ?>...</p>
      <?php endif; ?>

      <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
        <?php if ($c['status'] === 'pending'): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approve</button>
          </form>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger" data-confirm="Reject this candidate?"><i class="fas fa-times"></i> Reject</button>
          </form>
        <?php endif; ?>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
          <button type="submit" class="btn btn-sm btn-outline" data-confirm="Delete this candidate?"><i class="fas fa-trash"></i></button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (empty($candidates)): ?>
    <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted)">No candidates found.</div>
  <?php endif; ?>
</div>

<!-- Add Candidate Modal -->
<div class="modal-overlay" id="addCandModal" style="display:none">
  <div class="modal" style="max-width:580px">
    <div class="modal-header">
      <h3 class="modal-title">Add Candidate</h3>
      <button class="modal-close" data-modal-close="addCandModal">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <div class="form-group">
          <label class="form-label">Election *</label>
          <select name="election_id" class="form-control" required>
            <option value="">Select Election</option>
            <?php foreach ($elections as $e): ?>
              <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Candidate Type *</label>
            <select name="user_type" class="form-control" id="userTypeSelect" onchange="toggleUserSelect(this.value)">
              <option value="student">Student</option>
              <option value="teacher">Teacher</option>
            </select>
          </div>
          <div class="form-group" id="studentSelectWrap">
            <label class="form-label">Select Student</label>
            <select name="user_id" class="form-control" id="studentSelect">
              <option value="0">Select...</option>
              <?php foreach ($students as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['roll_number']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" id="teacherSelectWrap" style="display:none">
            <label class="form-label">Select Teacher</label>
            <select name="user_id_teacher" class="form-control" id="teacherSelect">
              <option value="0">Select...</option>
              <?php foreach ($teachers as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?> (<?= htmlspecialchars($t['teacher_id']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Candidate Name *</label>
            <input type="text" name="name" class="form-control" required placeholder="Full name">
          </div>
          <div class="form-group">
            <label class="form-label">Position</label>
            <input type="text" name="position" class="form-control" placeholder="e.g. President, CR">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-control">
              <option value="">General</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Candidate No.</label>
            <input type="number" name="candidate_number" class="form-control" placeholder="1,2,3..." min="1">
          </div>
          <div class="form-group">
            <label class="form-label">Initial Status</label>
            <select name="status" class="form-control">
              <option value="approved">Approved</option>
              <option value="pending">Pending Review</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Short Bio</label>
          <textarea name="bio" class="form-control" rows="2" placeholder="Brief candidate biography..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Manifesto</label>
          <textarea name="manifesto" class="form-control" rows="3" placeholder="Candidate's promises and manifesto..."></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Photo</label>
            <input type="file" name="photo" class="form-control" accept="image/*">
          </div>
          <div class="form-group">
            <label class="form-label">Symbol / Logo</label>
            <input type="file" name="symbol" class="form-control" accept="image/*">
          </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
          <button type="button" class="btn btn-outline" data-modal-close="addCandModal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Candidate</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleUserSelect(type) {
  document.getElementById('studentSelectWrap').style.display = type === 'student' ? '' : 'none';
  document.getElementById('teacherSelectWrap').style.display = type === 'teacher' ? '' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
