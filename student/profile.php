<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$student = getStudentById($pdo, $_SESSION['user_id']);
$departments = getDepartments($pdo);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName  = sanitize($_POST['full_name'] ?? '');
    $mobile    = sanitize($_POST['mobile'] ?? '');
    $deptId    = (int)($_POST['department_id'] ?? $student['department_id']);
    $year      = (int)($_POST['year'] ?? 1);
    $division  = sanitize($_POST['division'] ?? '');
    $gender    = sanitize($_POST['gender'] ?? '');

    $photoName = $student['profile_photo'];
    if (!empty($_FILES['profile_photo']['name'])) {
        $up = uploadFile($_FILES['profile_photo'], UPLOAD_PROFILES);
        if ($up['success']) {
            $photoName = $up['filename'];
            $_SESSION['user_photo'] = $photoName;
        }
    }

    // Password change
    if (!empty($_POST['new_password'])) {
        if (!password_verify($_POST['current_password'] ?? '', $student['password'])) {
            setFlash('danger','Current password is incorrect.');
        } elseif (strlen($_POST['new_password']) < 8) {
            setFlash('danger','New password must be at least 8 characters.');
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            setFlash('danger','Passwords do not match.');
        } else {
            $hashed = hashPassword($_POST['new_password']);
            $pdo->prepare("UPDATE students SET password=? WHERE id=?")->execute([$hashed,$_SESSION['user_id']]);
            logActivity($pdo,'student',$_SESSION['user_id'],'PASSWORD_CHANGED','Student changed password');
        }
    }

    $pdo->prepare("UPDATE students SET full_name=?,mobile=?,department_id=?,year=?,division=?,gender=?,profile_photo=? WHERE id=?")
        ->execute([$fullName,$mobile,$deptId,$year,$division,$gender,$photoName,$_SESSION['user_id']]);

    $_SESSION['user_name'] = $fullName;
    logActivity($pdo,'student',$_SESSION['user_id'],'PROFILE_UPDATED','Updated profile');
    setFlash('success','Profile updated successfully!');
    header('Location: ' . BASE_URL . '/student/profile.php');
    exit;
}

$voteHistory = $pdo->prepare("
    SELECT v.*, e.title as election_title, c.name as candidate_name, c.photo as candidate_photo
    FROM votes v
    LEFT JOIN elections e ON v.election_id=e.id
    LEFT JOIN candidates c ON v.candidate_id=c.id
    WHERE v.voter_type='student' AND v.voter_id=?
    ORDER BY v.voted_at DESC
");
$voteHistory->execute([$_SESSION['user_id']]);
$voteHistory = $voteHistory->fetchAll();

$pageTitle='My Profile'; $activeMenu='profile';
$sidebarLinks=[
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/student/index.php','key'=>'dashboard'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/student/elections.php','key'=>'elections'],
  ['icon'=>'fas fa-poll-h','label'=>'Results','href'=>BASE_URL.'/student/results.php','key'=>'results'],
  ['icon'=>'fas fa-user-circle','label'=>'My Profile','href'=>BASE_URL.'/student/profile.php','key'=>'profile'],
  ['icon'=>'fas fa-certificate','label'=>'Certificate','href'=>BASE_URL.'/student/certificate.php','key'=>'certificate'],
];
$roleLabel='Student';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div style="margin-bottom:24px"><h2 style="font-size:1.4rem;margin-bottom:4px">My Profile</h2><p style="color:var(--text-muted);font-size:0.85rem">Update your personal information and settings</p></div>

<div class="grid-2" style="align-items:start">
  <!-- Profile Form -->
  <div class="glass-card" style="padding:28px">
    <form method="POST" enctype="multipart/form-data">
      <!-- Photo -->
      <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px">
        <div style="position:relative">
          <img id="photoPreview" src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($student['profile_photo']) ?>"
               style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--primary)"
               onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
          <label for="photoInput" style="position:absolute;bottom:-4px;right:-4px;width:26px;height:26px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:0.7rem;color:#fff">
            <i class="fas fa-camera"></i>
          </label>
          <input type="file" id="photoInput" name="profile_photo" accept="image/*" style="display:none" onchange="previewPhoto(this)">
        </div>
        <div>
          <div style="font-weight:700;font-size:1.1rem"><?= htmlspecialchars($student['full_name']) ?></div>
          <div style="font-size:0.8rem;color:var(--accent)"><?= htmlspecialchars($student['roll_number']) ?></div>
          <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px">Click camera icon to change photo</div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($student['full_name']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Mobile</label>
          <input type="tel" name="mobile" class="form-control" value="<?= htmlspecialchars($student['mobile']??'') ?>" placeholder="+91 XXXXX XXXXX">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label class="form-label">Department</label>
          <select name="department_id" class="form-control">
            <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>" <?= $student['department_id']==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Year</label>
          <select name="year" class="form-control">
            <?php for ($y=1;$y<=4;$y++): ?>
              <option value="<?= $y ?>" <?= $student['year']==$y?'selected':'' ?>>Year <?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label class="form-label">Division</label>
          <input type="text" name="division" class="form-control" value="<?= htmlspecialchars($student['division']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-control">
            <option value="">Select</option>
            <?php foreach (['Male','Female','Other'] as $g): ?>
              <option value="<?= $g ?>" <?= ($student['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <hr class="divider">
      <h4 style="font-size:0.9rem;margin-bottom:16px">🔑 Change Password (optional)</h4>

      <div class="form-group">
        <label class="form-label">Current Password</label>
        <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" placeholder="Min 8 chars" minlength="8">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-save"></i> Save Changes</button>
    </form>
  </div>

  <!-- Info + Vote History -->
  <div>
    <!-- Account Info -->
    <div class="glass-card" style="padding:20px;margin-bottom:18px">
      <h4 style="font-size:0.95rem;margin-bottom:14px"><i class="fas fa-id-card" style="color:var(--accent)"></i> Account Details</h4>
      <?php foreach ([['Email',$student['email'],'fas fa-envelope'],['Roll No',$student['roll_number'],'fas fa-id-badge'],['Department',$student['dept_name'],'fas fa-building'],['Status',$student['is_approved']?'✅ Approved':'⏳ Pending','fas fa-check-circle']] as [$label,$val,$icon]): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
          <i class="<?= $icon ?>" style="color:var(--primary-light);width:16px"></i>
          <span style="font-size:0.8rem;color:var(--text-muted);min-width:80px"><?= $label ?></span>
          <span style="font-size:0.88rem;font-weight:600"><?= htmlspecialchars($val??'—') ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Vote History -->
    <div class="glass-card" style="padding:20px">
      <h4 style="font-size:0.95rem;margin-bottom:14px"><i class="fas fa-history" style="color:var(--warning)"></i> Vote History (<?= count($voteHistory) ?>)</h4>
      <?php if (!empty($voteHistory)): ?>
        <?php foreach ($voteHistory as $v): ?>
          <div style="padding:12px;background:var(--bg-glass);border-radius:var(--r-md);margin-bottom:8px">
            <div style="font-weight:600;font-size:0.88rem;margin-bottom:4px"><?= htmlspecialchars($v['election_title']??'—') ?></div>
            <div style="font-size:0.78rem;color:var(--text-muted)">Voted for: <strong><?= htmlspecialchars($v['candidate_name']??'—') ?></strong></div>
            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:4px"><i class="fas fa-clock"></i> <?= formatDate($v['voted_at']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color:var(--text-muted);font-size:0.85rem;text-align:center;padding:16px">No votes cast yet.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function previewPhoto(input) {
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => document.getElementById('photoPreview').src = e.target.result;
  reader.readAsDataURL(input.files[0]);
}
</script>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
