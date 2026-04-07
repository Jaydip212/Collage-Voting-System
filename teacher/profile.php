<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('teacher');

$teacher     = getTeacherById($pdo, $_SESSION['user_id']);
$departments = getDepartments($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName    = sanitize($_POST['full_name'] ?? '');
    $mobile      = sanitize($_POST['mobile'] ?? '');
    $deptId      = (int)($_POST['department_id'] ?? $teacher['department_id']);
    $designation = sanitize($_POST['designation'] ?? '');

    $photoName = $teacher['profile_photo'];
    if (!empty($_FILES['profile_photo']['name'])) {
        $up = uploadFile($_FILES['profile_photo'], UPLOAD_PROFILES);
        if ($up['success']) { $photoName = $up['filename']; $_SESSION['user_photo'] = $photoName; }
    }

    if (!empty($_POST['new_password'])) {
        if (!password_verify($_POST['current_password']??'', $teacher['password'])) {
            setFlash('danger','Current password incorrect.');
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            setFlash('danger','Passwords do not match.');
        } else {
            $pdo->prepare("UPDATE teachers SET password=? WHERE id=?")->execute([hashPassword($_POST['new_password']),$_SESSION['user_id']]);
        }
    }

    $pdo->prepare("UPDATE teachers SET full_name=?,mobile=?,department_id=?,designation=?,profile_photo=? WHERE id=?")
        ->execute([$fullName,$mobile,$deptId,$designation,$photoName,$_SESSION['user_id']]);
    $_SESSION['user_name'] = $fullName;
    setFlash('success','Profile updated!');
    header('Location: ' . BASE_URL . '/teacher/profile.php'); exit;
}

$voteHistory = $pdo->prepare("SELECT v.*, e.title as election_title, c.name as candidate_name FROM votes v LEFT JOIN elections e ON v.election_id=e.id LEFT JOIN candidates c ON v.candidate_id=c.id WHERE v.voter_type='teacher' AND v.voter_id=? ORDER BY v.voted_at DESC");
$voteHistory->execute([$_SESSION['user_id']]);
$voteHistory = $voteHistory->fetchAll();

$pageTitle='Profile'; $activeMenu='profile';
$sidebarLinks=[
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/teacher/index.php','key'=>'dashboard'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/teacher/elections.php','key'=>'elections'],
  ['icon'=>'fas fa-poll-h','label'=>'Results','href'=>BASE_URL.'/teacher/results.php','key'=>'results'],
  ['icon'=>'fas fa-user-circle','label'=>'My Profile','href'=>BASE_URL.'/teacher/profile.php','key'=>'profile'],
];
$roleLabel='Teacher';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>
<div style="margin-bottom:24px"><h2 style="font-size:1.4rem;margin-bottom:4px">My Profile</h2></div>
<div class="grid-2" style="align-items:start">
  <div class="glass-card" style="padding:28px">
    <form method="POST" enctype="multipart/form-data">
      <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px">
        <div style="position:relative">
          <img id="photoPreview" src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($teacher['profile_photo']) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--primary)" onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
          <label for="photoInput" style="position:absolute;bottom:-4px;right:-4px;width:26px;height:26px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:0.7rem;color:#fff"><i class="fas fa-camera"></i></label>
          <input type="file" id="photoInput" name="profile_photo" accept="image/*" style="display:none" onchange="const r=new FileReader();r.onload=e=>document.getElementById('photoPreview').src=e.target.result;r.readAsDataURL(this.files[0]);">
        </div>
        <div><div style="font-weight:700;font-size:1.1rem"><?= htmlspecialchars($teacher['full_name']) ?></div><div style="font-size:0.8rem;color:var(--accent)"><?= htmlspecialchars($teacher['teacher_id']) ?></div></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($teacher['full_name']) ?>"></div>
        <div class="form-group"><label class="form-label">Mobile</label><input type="tel" name="mobile" class="form-control" value="<?= htmlspecialchars($teacher['mobile']??'') ?>"></div>
        <div class="form-group"><label class="form-label">Department</label><select name="department_id" class="form-control"><?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>" <?= $teacher['department_id']==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Designation</label><input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($teacher['designation']??'') ?>"></div>
      </div>
      <hr class="divider"><h4 style="font-size:0.9rem;margin-bottom:16px">🔑 Change Password</h4>
      <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" placeholder="Current password"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" minlength="8" placeholder="Min 8 chars"></div>
        <div class="form-group"><label class="form-label">Confirm New</label><input type="password" name="confirm_password" class="form-control" placeholder="Repeat"></div>
      </div>
      <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-save"></i> Save Changes</button>
    </form>
  </div>
  <div>
    <div class="glass-card" style="padding:20px;margin-bottom:18px">
      <h4 style="font-size:0.95rem;margin-bottom:14px"><i class="fas fa-id-card" style="color:var(--accent)"></i> Account Details</h4>
      <?php foreach (['Email'=>$teacher['email'],'Teacher ID'=>$teacher['teacher_id'],'Department'=>$teacher['dept_name'],'Designation'=>$teacher['designation']??'—'] as $l=>$v): ?>
        <div style="padding:10px 0;border-bottom:1px solid var(--border);display:flex;gap:12px;align-items:center;font-size:0.85rem">
          <span style="color:var(--text-muted);min-width:90px"><?= $l ?></span><span style="font-weight:600"><?= htmlspecialchars($v) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="glass-card" style="padding:20px">
      <h4 style="font-size:0.95rem;margin-bottom:14px"><i class="fas fa-history" style="color:var(--warning)"></i> Vote History</h4>
      <?php if (!empty($voteHistory)): ?>
        <?php foreach ($voteHistory as $v): ?>
          <div style="padding:12px;background:var(--bg-glass);border-radius:var(--r-md);margin-bottom:8px">
            <div style="font-weight:600;font-size:0.88rem"><?= htmlspecialchars($v['election_title']??'—') ?></div>
            <div style="font-size:0.78rem;color:var(--text-muted)">Voted: <strong><?= htmlspecialchars($v['candidate_name']??'—') ?></strong> · <?= formatDate($v['voted_at']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?><p style="color:var(--text-muted);font-size:0.85rem;text-align:center;padding:16px">No votes cast yet.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
