<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$student = getStudentById($pdo, $_SESSION['user_id']);

// Check if student has voted in at least one election
$votedCount = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE voter_type='student' AND voter_id=?");
$votedCount->execute([$_SESSION['user_id']]);
$votedCount = $votedCount->fetchColumn();

$pageTitle  = 'Participation Certificate';
$activeMenu = 'certificate';
$sidebarLinks = [
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/student/index.php','key'=>'dashboard'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/student/elections.php','key'=>'elections'],
  ['icon'=>'fas fa-poll-h','label'=>'Results','href'=>BASE_URL.'/student/results.php','key'=>'results'],
  ['icon'=>'fas fa-user-circle','label'=>'My Profile','href'=>BASE_URL.'/student/profile.php','key'=>'profile'],
  ['icon'=>'fas fa-certificate','label'=>'Certificate','href'=>BASE_URL.'/student/certificate.php','key'=>'certificate'],
];
$roleLabel = 'Student';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div style="margin-bottom:24px">
  <h2 style="font-size:1.4rem;margin-bottom:4px">Participation Certificate</h2>
  <p style="color:var(--text-muted);font-size:0.85rem">Download your election participation certificate</p>
</div>

<?php if ($votedCount === 0): ?>
  <div class="glass-card" style="padding:40px;text-align:center">
    <div style="font-size:3rem;margin-bottom:14px">📋</div>
    <h3>No Certificate Available</h3>
    <p style="color:var(--text-muted);margin-top:8px">You haven't participated in any election yet. Cast your vote to earn a participation certificate!</p>
    <a href="<?= BASE_URL ?>/student/elections.php" class="btn btn-primary" style="margin-top:20px"><i class="fas fa-vote-yea"></i> View Elections</a>
  </div>
<?php else: ?>
  <div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Certificate</button>
    <button onclick="downloadCert()" class="btn btn-outline"><i class="fas fa-download"></i> Save as PDF</button>
  </div>

  <!-- Certificate -->
  <div id="certificate" style="
    max-width:800px;margin:0 auto;
    background:linear-gradient(135deg,#0f0f1a 0%,#1a1a3a 50%,#0f0f1a 100%);
    border:3px solid var(--primary);
    border-radius:20px;
    padding:60px 50px;
    text-align:center;
    position:relative;
    overflow:hidden;
    box-shadow:0 0 60px rgba(79,70,229,0.3);
  ">
    <!-- Corner decorations -->
    <div style="position:absolute;top:15px;left:15px;width:50px;height:50px;border-left:3px solid var(--accent);border-top:3px solid var(--accent);border-radius:4px 0 0 0"></div>
    <div style="position:absolute;top:15px;right:15px;width:50px;height:50px;border-right:3px solid var(--accent);border-top:3px solid var(--accent);border-radius:0 4px 0 0"></div>
    <div style="position:absolute;bottom:15px;left:15px;width:50px;height:50px;border-left:3px solid var(--accent);border-bottom:3px solid var(--accent);border-radius:0 0 0 4px"></div>
    <div style="position:absolute;bottom:15px;right:15px;width:50px;height:50px;border-right:3px solid var(--accent);border-bottom:3px solid var(--accent);border-radius:0 0 4px 0"></div>

    <!-- Background glow -->
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:400px;height:400px;background:radial-gradient(circle,rgba(79,70,229,0.08),transparent 70%);pointer-events:none"></div>

    <!-- Header -->
    <div style="font-size:2rem;margin-bottom:6px">🗳️</div>
    <div style="font-size:0.75rem;font-weight:700;color:var(--accent);letter-spacing:0.2em;text-transform:uppercase;margin-bottom:6px"><?= SITE_NAME ?></div>
    <div style="width:80px;height:2px;background:linear-gradient(90deg,transparent,var(--primary),transparent);margin:10px auto 20px"></div>

    <h1 style="font-size:2rem;font-weight:800;margin-bottom:6px;letter-spacing:0.05em">Certificate of</h1>
    <h1 style="font-size:2.5rem;font-weight:800;background:linear-gradient(135deg,#6c63ff,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:30px">Participation</h1>

    <p style="font-size:1rem;color:#94a3b8;margin-bottom:20px">This is to certify that</p>

    <div style="font-size:2rem;font-weight:800;font-family:'Poppins',sans-serif;color:#f1f5f9;margin-bottom:8px;border-bottom:2px solid rgba(79,70,229,0.4);padding-bottom:10px;display:inline-block;min-width:300px">
      <?= htmlspecialchars($student['full_name']) ?>
    </div>

    <div style="display:flex;justify-content:center;gap:32px;margin:24px 0;flex-wrap:wrap">
      <div style="text-align:center">
        <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px">Roll Number</div>
        <div style="font-weight:700;color:#94a3b8"><?= htmlspecialchars($student['roll_number']) ?></div>
      </div>
      <div style="text-align:center">
        <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px">Department</div>
        <div style="font-weight:700;color:#94a3b8"><?= htmlspecialchars($student['dept_name']) ?></div>
      </div>
      <div style="text-align:center">
        <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px">Elections Participated</div>
        <div style="font-weight:700;color:#94a3b8"><?= $votedCount ?></div>
      </div>
    </div>

    <p style="font-size:1rem;color:#94a3b8;line-height:1.8;max-width:480px;margin:0 auto 32px">
      has successfully participated in the digital college elections organized by <strong style="color:#f1f5f9"><?= SITE_NAME ?></strong> and exercised their democratic right responsibly.
    </p>

    <div style="width:80px;height:2px;background:linear-gradient(90deg,transparent,var(--primary),transparent);margin:0 auto 24px"></div>

    <div style="display:flex;justify-content:space-around;flex-wrap:wrap;gap:20px">
      <div style="text-align:center">
        <div style="font-size:1.5rem;font-weight:800;color:var(--primary-light)"><?= date('d') ?></div>
        <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;letter-spacing:0.08em"><?= date('M Y') ?></div>
        <div style="font-size:0.7rem;color:#64748b;margin-top:4px;border-top:1px solid rgba(255,255,255,0.1);padding-top:6px">Date Issued</div>
      </div>
      <div style="text-align:center">
        <div style="font-size:1rem;color:var(--accent)">⚡ Digital</div>
        <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;letter-spacing:0.08em">Verified</div>
        <div style="font-size:0.7rem;color:#64748b;margin-top:4px;border-top:1px solid rgba(255,255,255,0.1);padding-top:6px">Certificate Authority</div>
      </div>
    </div>

    <div style="margin-top:24px;font-size:0.65rem;color:#475569;letter-spacing:0.05em">
      Certificate ID: CERT-<?= strtoupper(substr(md5($student['id'].date('Y')),0,12)) ?>
    </div>
  </div>
<?php endif; ?>

<style>
@media print {
  .main-content { margin-left: 0 !important; }
  .topbar, .sidebar, .no-print, .btn { display: none !important; }
  body { background: #fff; }
  #certificate {
    background: linear-gradient(135deg,#e8e8f8,#f0f0ff,#e8e8f8) !important;
    border: 3px solid #4f46e5 !important;
    color: #000 !important;
    box-shadow: none !important;
  }
  h1, div { -webkit-text-fill-color: unset !important; }
}
</style>

<script>
function downloadCert() {
  alert('To save as PDF: Click Print → Change destination to "Save as PDF" → Click Save');
  window.print();
}
</script>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
