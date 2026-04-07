<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// Filters
$roleFilter   = $_GET['role'] ?? 'all';
$actionFilter = $_GET['action_filter'] ?? '';
$search       = trim($_GET['q'] ?? '');
$dateFrom     = $_GET['from'] ?? '';
$dateTo       = $_GET['to'] ?? '';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($roleFilter !== 'all') { $where[] = 'al.user_role=?'; $params[] = $roleFilter; }
if ($actionFilter)         { $where[] = 'al.action LIKE ?'; $params[] = "%$actionFilter%"; }
if ($search)               { $where[] = '(al.action LIKE ? OR al.description LIKE ? OR al.ip_address LIKE ?)'; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }
if ($dateFrom)             { $where[] = 'DATE(al.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)               { $where[] = 'DATE(al.created_at) <= ?'; $params[] = $dateTo; }

$whereStr = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al WHERE $whereStr");
$total->execute($params);
$total = $total->fetchColumn();
$pages = ceil($total / $perPage);

$stmt = $pdo->prepare("
    SELECT al.*,
        CASE al.user_role
            WHEN 'admin' THEN (SELECT name FROM admins WHERE id=al.user_id)
            WHEN 'student' THEN (SELECT full_name FROM students WHERE id=al.user_id)
            WHEN 'teacher' THEN (SELECT full_name FROM teachers WHERE id=al.user_id)
            WHEN 'hod' THEN (SELECT name FROM hods WHERE id=al.user_id)
        END as user_name
    FROM activity_logs al WHERE $whereStr
    ORDER BY al.created_at DESC LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Suspicious IPs (5+ failed logins)
$suspIPs = $pdo->query("
    SELECT ip_address, COUNT(*) as cnt
    FROM failed_logins
    WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY ip_address
    HAVING cnt >= 5
    ORDER BY cnt DESC
")->fetchAll();

$actionColors = [
    'LOGIN_SUCCESS' => 'success', 'LOGIN_OTP_SENT' => 'info', 'LOGOUT' => 'default',
    'VOTE_CAST' => 'success', 'REGISTER' => 'info', 'EMAIL_VERIFIED' => 'success',
    'ELECTION_CREATED' => 'purple', 'ELECTION_UPDATED' => 'info', 'ELECTION_DELETED' => 'danger',
    'CANDIDATE_ADDED' => 'info', 'RESULTS_PUBLISHED' => 'success',
    'STUDENT_APPROVED' => 'success', 'DATABASE_BACKUP' => 'warning',
    'DEVICE_DUPLICATE_ATTEMPT' => 'danger',
];

$pageTitle='Audit Logs'; $activeMenu='auditlogs';
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
    <h2 style="font-size:1.4rem;margin-bottom:4px">Audit Logs</h2>
    <p style="color:var(--text-muted);font-size:0.85rem">Track all system activity including logins, votes, and admin actions</p>
  </div>
  <span class="badge badge-info"><?= number_format($total) ?> total records</span>
</div>

<!-- Suspicious IPs Alert -->
<?php if (!empty($suspIPs)): ?>
<div class="alert alert-danger" style="margin-bottom:20px">
  <i class="fas fa-shield-alt"></i>
  <div>
    <strong>🚨 Suspicious Activity Detected!</strong>
    <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:8px">
      <?php foreach ($suspIPs as $ip): ?>
        <span class="badge badge-danger"><?= htmlspecialchars($ip['ip_address']) ?> (<?= $ip['cnt'] ?> attempts)</span>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Filters -->
<form method="GET">
<div class="filter-row" style="flex-wrap:wrap">
  <div style="position:relative">
    <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
    <input type="text" name="q" class="form-control" placeholder="Search action or IP..." value="<?= htmlspecialchars($search) ?>" style="padding-left:44px;max-width:240px">
  </div>
  <select name="role" class="form-control" style="max-width:140px" onchange="this.form.submit()">
    <option value="all" <?= $roleFilter==='all'?'selected':'' ?>>All Roles</option>
    <?php foreach (['admin','student','teacher','hod'] as $r): ?>
      <option value="<?= $r ?>" <?= $roleFilter===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="action_filter" class="form-control" style="max-width:175px">
    <option value="">All Actions</option>
    <?php foreach (array_keys($actionColors) as $a): ?>
      <option value="<?= $a ?>" <?= $actionFilter===$a?'selected':'' ?>><?= $a ?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="from" class="form-control" style="max-width:150px" value="<?= htmlspecialchars($dateFrom) ?>" placeholder="From">
  <input type="date" name="to" class="form-control" style="max-width:150px" value="<?= htmlspecialchars($dateTo) ?>" placeholder="To">
  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
  <a href="<?= BASE_URL ?>/admin/audit_logs.php" class="btn btn-outline btn-sm">Reset</a>
</div>
</form>

<div class="glass-card" style="padding:0;overflow:hidden;margin-top:20px">
  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr><th>#</th><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>IP Address</th><th>Time</th></tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td style="color:var(--text-muted);font-size:0.78rem"><?= $log['id'] ?></td>
            <td style="font-weight:600;font-size:0.88rem"><?= htmlspecialchars($log['user_name'] ?? '—') ?></td>
            <td><span class="badge badge-default" style="font-size:0.7rem;text-transform:capitalize"><?= $log['user_role'] ?></span></td>
            <td>
              <?php $bc = $actionColors[$log['action']] ?? 'default'; ?>
              <span class="badge badge-<?= $bc ?>" style="font-size:0.7rem"><?= str_replace('_',' ',$log['action']) ?></span>
            </td>
            <td style="font-size:0.78rem;color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($log['description']) ?></td>
            <td style="font-size:0.78rem"><code><?= htmlspecialchars($log['ip_address'] ?? '—') ?></code></td>
            <td style="font-size:0.75rem;color:var(--text-muted);white-space:nowrap"><?= date('d M Y h:i A',strtotime($log['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?>
          <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">No logs found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination">
  <?php for ($p=1;$p<=$pages;$p++): ?>
    <a href="?page=<?= $p ?>&role=<?= $roleFilter ?>&q=<?= urlencode($search) ?>&from=<?= $dateFrom ?>&to=<?= $dateTo ?>"
       class="page-btn <?= $p===$page ? 'active' : '' ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
