<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title    = sanitize($_POST['title'] ?? '');
        $content  = sanitize($_POST['content'] ?? '');
        $deptId   = (isset($_POST['department_id']) && $_POST['department_id'] !== '') ? (int)$_POST['department_id'] : null;
        $stmt = $pdo->prepare("INSERT INTO announcements (title,content,posted_by_role,posted_by_id,department_id) VALUES (?,?,'admin',?,?)");
        $stmt->execute([$title,$content,$_SESSION['user_id'],$deptId]);
        logActivity($pdo,'admin',$_SESSION['user_id'],'ANNOUNCEMENT_POSTED',"Posted: $title");
        setFlash('success','Announcement posted.');
    }
    if ($action === 'toggle') {
        $id = (int)$_POST['ann_id'];
        $pdo->prepare("UPDATE announcements SET is_active=1-is_active WHERE id=?")->execute([$id]);
        setFlash('success','Announcement status toggled.');
    }
    if ($action === 'delete') {
        $id = (int)$_POST['ann_id'];
        $pdo->prepare("DELETE FROM announcements WHERE id=?")->execute([$id]);
        setFlash('success','Announcement deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/announcements.php');
    exit;
}

$announcements = $pdo->query("
    SELECT a.*, d.name as dept_name FROM announcements a
    LEFT JOIN departments d ON a.department_id=d.id
    ORDER BY a.created_at DESC
")->fetchAll();

$departments = getDepartments($pdo);

$pageTitle='Announcements'; $activeMenu='announcements';
$sidebarLinks=[
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/admin/index.php','key'=>'dashboard'],
  ['section'=>'ELECTIONS'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/admin/elections.php','key'=>'elections'],
  ['section'=>'SYSTEM'],
  ['icon'=>'fas fa-bullhorn','label'=>'Announcements','href'=>BASE_URL.'/admin/announcements.php','key'=>'announcements'],
  ['icon'=>'fas fa-history','label'=>'Audit Logs','href'=>BASE_URL.'/admin/audit_logs.php','key'=>'auditlogs'],
  ['icon'=>'fas fa-database','label'=>'Backup','href'=>BASE_URL.'/admin/backup.php','key'=>'backup'],
];
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
  <div>
    <h2 style="font-size:1.4rem;margin-bottom:4px">Announcements</h2>
    <p style="color:var(--text-muted);font-size:0.85rem">Post notices and announcements for students and teachers</p>
  </div>
  <button class="btn btn-primary" data-modal-open="annModal"><i class="fas fa-plus"></i> New Announcement</button>
</div>

<div style="display:flex;flex-direction:column;gap:14px">
  <?php foreach ($announcements as $a): ?>
    <div class="glass-card" style="padding:22px;display:flex;align-items:flex-start;gap:16px;<?= !$a['is_active'] ? 'opacity:0.6' : '' ?>">
      <div style="width:46px;height:46px;border-radius:12px;background:rgba(79,70,229,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.2rem">📢</div>
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap">
          <div style="font-weight:700;font-size:0.95rem"><?= htmlspecialchars($a['title']) ?></div>
          <span class="badge <?= $a['is_active'] ? 'badge-success' : 'badge-default' ?>"><?= $a['is_active'] ? 'Active' : 'Hidden' ?></span>
          <?php if ($a['dept_name']): ?>
            <span class="badge badge-info" style="font-size:0.68rem"><?= htmlspecialchars($a['dept_name']) ?></span>
          <?php else: ?>
            <span class="badge badge-purple" style="font-size:0.68rem">College-wide</span>
          <?php endif; ?>
        </div>
        <p style="font-size:0.83rem;color:var(--text-muted);line-height:1.5;margin-bottom:8px"><?= htmlspecialchars($a['content']) ?></p>
        <div style="font-size:0.72rem;color:var(--text-muted)"><?= timeAgo($a['created_at']) ?> · by <?= $a['posted_by_role'] ?></div>
      </div>
      <div style="display:flex;gap:8px;flex-shrink:0">
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="ann_id" value="<?= $a['id'] ?>">
          <button type="submit" class="btn btn-sm <?= $a['is_active'] ? 'btn-warning' : 'btn-success' ?>" title="Toggle visibility"><i class="fas <?= $a['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i></button>
        </form>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="ann_id" value="<?= $a['id'] ?>">
          <button type="submit" class="btn btn-sm btn-danger" data-confirm="Delete this announcement?" title="Delete"><i class="fas fa-trash"></i></button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (empty($announcements)): ?>
    <div class="glass-card" style="padding:40px;text-align:center">
      <div style="font-size:3rem;margin-bottom:12px">📢</div>
      <h3>No Announcements</h3>
      <p style="color:var(--text-muted)">Post your first announcement to inform students and teachers.</p>
    </div>
  <?php endif; ?>
</div>

<!-- Modal -->
<div class="modal-overlay" id="annModal" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">New Announcement</h3>
      <button class="modal-close" data-modal-close="annModal">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <input type="hidden" name="action" value="create">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" required placeholder="Announcement title">
        </div>
        <div class="form-group">
          <label class="form-label">Content *</label>
          <textarea name="content" class="form-control" rows="4" required placeholder="Announcement content..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Target Department</label>
          <select name="department_id" class="form-control">
            <option value="">All College (College-wide)</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
          <button type="button" class="btn btn-outline" data-modal-close="annModal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post Announcement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
