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

// Handle post new announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $scope   = $_POST['scope'] ?? 'dept';
    $did     = $scope === 'college' ? null : $deptId;
    if ($title && $content) {
        $pdo->prepare("INSERT INTO announcements (title,content,posted_by_role,posted_by_id,department_id) VALUES (?,'hod',?,?)")
            ->execute([$title, $content, $hodId, $did]);
        // Wrong param count fix:
        $pdo->prepare("INSERT INTO announcements (title,content,posted_by_role,posted_by_id,department_id) VALUES (?,?,?,?,?)")
            ->execute([$title, $content, 'hod', $hodId, $did]);
        logActivity($pdo,'hod',$hodId,'ANNOUNCEMENT_POSTED',"Posted: $title");
        setFlash('success','Announcement posted successfully!');
    } else {
        setFlash('danger','Title and content are required.');
    }
    header('Location: ' . BASE_URL . '/hod/announcements.php');
    exit;
}

// Fetch announcements
$announcements = $pdo->prepare("
    SELECT a.*, 
           CASE WHEN a.department_id IS NULL THEN 'College-wide' ELSE ? END as scope_label
    FROM announcements a
    WHERE a.department_id=? OR a.department_id IS NULL
    ORDER BY a.created_at DESC
");
$announcements->execute([$hod['dept_name'], $deptId]);
$announcements = $announcements->fetchAll();

$pageTitle  = 'Announcements – HOD';
$activeMenu = 'announcements';
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

<div style="margin-bottom:28px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
  <div>
    <h2 style="font-size:1.4rem;margin-bottom:4px">📢 Announcements</h2>
    <p style="color:var(--text-muted)"><?= htmlspecialchars($hod['dept_name']) ?> · Post and manage announcements</p>
  </div>
  <button class="btn btn-primary" onclick="document.getElementById('postForm').scrollIntoView({behavior:'smooth'})">
    <i class="fas fa-plus"></i> Post New
  </button>
</div>

<div class="grid-2" style="align-items:start">

  <!-- Announcements List -->
  <div>
    <?php if (empty($announcements)): ?>
    <div class="glass-card" style="padding:40px;text-align:center">
      <div style="font-size:2.5rem;margin-bottom:12px">📭</div>
      <p style="color:var(--text-muted)">No announcements posted yet.</p>
    </div>
    <?php endif; ?>

    <?php foreach ($announcements as $a): ?>
    <div class="glass-card" style="padding:20px;margin-bottom:14px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:8px">
        <div style="font-weight:700;font-size:0.95rem"><?= htmlspecialchars($a['title']) ?></div>
        <span class="badge <?= $a['department_id'] ? 'badge-info' : 'badge-success' ?>" style="font-size:0.68rem;white-space:nowrap">
          <?= htmlspecialchars($a['scope_label']) ?>
        </span>
      </div>
      <p style="font-size:0.82rem;color:var(--text-secondary);line-height:1.6;margin-bottom:10px">
        <?= nl2br(htmlspecialchars($a['content'])) ?>
      </p>
      <div style="font-size:0.72rem;color:var(--text-muted)">
        <i class="fas fa-clock"></i> <?= timeAgo($a['created_at']) ?>
        &nbsp;·&nbsp;
        <i class="fas fa-user"></i> <?= ucfirst($a['posted_by_role']) ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Post Form -->
  <div id="postForm">
    <div class="glass-card" style="padding:28px;position:sticky;top:90px">
      <h3 style="font-size:1rem;margin-bottom:20px"><i class="fas fa-bullhorn" style="color:var(--accent)"></i> Post New Announcement</h3>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" placeholder="Announcement title" required>
        </div>
        <div class="form-group">
          <label class="form-label">Content *</label>
          <textarea name="content" class="form-control" rows="5" placeholder="Write your announcement here..." required></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Scope</label>
          <select name="scope" class="form-control">
            <option value="dept">Department Only (<?= htmlspecialchars($hod['dept_name']) ?>)</option>
            <option value="college">College-wide</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-full">
          <i class="fas fa-paper-plane"></i> Post Announcement
        </button>
      </form>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
