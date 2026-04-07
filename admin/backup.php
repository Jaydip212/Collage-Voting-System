<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// Handle backup download
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $tables = ['admins','departments','students','teachers','hods','elections','candidates','votes','otp_verification','announcements','activity_logs','notifications','results'];
    $sql = "-- College Voting System Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        // Table create
        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $createStmt[1] . ";\n\n";

        // Data
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_NUM);
        if (!empty($rows)) {
            $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll();
            $colNames = array_map(fn($c)=>"`{$c['Field']}`",$cols);
            $sql .= "INSERT INTO `$table` (" . implode(',',$colNames) . ") VALUES\n";
            $vals = [];
            foreach ($rows as $row) {
                $escaped = array_map(fn($v)=> $v===null ? 'NULL' : "'" . addslashes($v) . "'", $row);
                $vals[] = "  (" . implode(',',$escaped) . ")";
            }
            $sql .= implode(",\n", $vals) . ";\n\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $filename = 'voting_backup_' . date('Y-m-d_His') . '.sql';
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Content-Length: ' . strlen($sql));
    echo $sql;
    logActivity($pdo,'admin',$_SESSION['user_id'],'DATABASE_BACKUP','Full backup downloaded');
    exit;
}

$pageTitle  = 'Backup & Restore';
$activeMenu = 'backup';
$sidebarLinks = [
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

<div style="margin-bottom:28px">
  <h2 style="font-size:1.4rem;margin-bottom:4px">Backup & Restore</h2>
  <p style="color:var(--text-muted);font-size:0.85rem">Export database backup or restore from a SQL file</p>
</div>

<div class="grid-2">
  <!-- Download Backup -->
  <div class="glass-card" style="padding:32px;text-align:center">
    <div style="font-size:3rem;margin-bottom:16px">💾</div>
    <h3 style="margin-bottom:10px">Download Backup</h3>
    <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:24px">
      Export a full SQL backup of all database tables and data. Download and store it safely.
    </p>
    <a href="?action=download" class="btn btn-primary btn-lg">
      <i class="fas fa-download"></i> Download SQL Backup
    </a>
    <p style="font-size:0.75rem;color:var(--text-muted);margin-top:16px">
      Includes all tables: students, elections, votes, logs, etc.
    </p>
  </div>

  <!-- Restore -->
  <div class="glass-card" style="padding:32px">
    <div style="text-align:center;margin-bottom:20px">
      <div style="font-size:3rem;margin-bottom:16px">📂</div>
      <h3 style="margin-bottom:10px">Restore Database</h3>
      <p style="color:var(--text-muted);font-size:0.85rem">Upload a .sql backup file to restore the database.</p>
    </div>

    <div class="alert alert-warning">
      <i class="fas fa-exclamation-triangle"></i>
      <strong>Warning:</strong> Restoring will overwrite existing data. This cannot be undone!
    </div>

    <div style="background:var(--bg-glass);border-radius:var(--r-md);padding:20px;text-align:center;border:2px dashed var(--border)">
      <i class="fas fa-database" style="font-size:2rem;color:var(--text-muted);margin-bottom:10px;display:block"></i>
      <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:12px">
        For restore, please use <strong>phpMyAdmin</strong>:<br>
        1. Open phpMyAdmin<br>
        2. Select <code>college_voting_system</code><br>
        3. Go to Import tab<br>
        4. Upload your .sql backup file
      </p>
    </div>
  </div>
</div>

<!-- DB Stats -->
<div class="glass-card" style="padding:24px;margin-top:24px">
  <h4 style="font-size:1rem;margin-bottom:16px"><i class="fas fa-info-circle" style="color:var(--accent)"></i> Database Information</h4>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px">
    <?php
    $tableStats = ['students','teachers','elections','votes','candidates','activity_logs','announcements'];
    foreach ($tableStats as $tbl): ?>
      <div style="padding:14px;background:var(--bg-glass);border-radius:var(--r-md);text-align:center">
        <div style="font-size:1.3rem;font-weight:800;color:var(--primary-light)"><?= $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn() ?></div>
        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:capitalize"><?= str_replace('_',' ',$tbl) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
