<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/otp.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/' . $_SESSION['user_role'] . '/index.php');
    exit;
}

$regRole  = $_GET['role'] ?? 'student';
$message  = '';
$msgType  = 'danger';
$step     = 'form'; // 'form' | 'otp'
$departments = getDepartments($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postRole = sanitize($_POST['reg_role'] ?? 'student');
    $postStep = sanitize($_POST['step'] ?? 'form');

    if ($postStep === 'form') {
        // CSRF check
        if (!verifyCaptcha($_POST['captcha'] ?? '')) {
            $message = 'Wrong CAPTCHA.';
        } else {
            // Validate & register
            $email    = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Please enter a valid email address.';
            } elseif (strlen($password) < 8) {
                $message = 'Password must be at least 8 characters.';
            } elseif ($password !== $confirm) {
                $message = 'Passwords do not match.';
            } else {
                $hashed = hashPassword($password);
                $success = false;

                if ($postRole === 'student') {
                    $fullName   = sanitize($_POST['full_name'] ?? '');
                    $rollNo     = sanitize($_POST['roll_number'] ?? '');
                    $mobile     = sanitize($_POST['mobile'] ?? '');
                    $deptId     = (int)($_POST['department_id'] ?? 0);
                    $year       = (int)($_POST['year'] ?? 1);
                    $division   = sanitize($_POST['division'] ?? '');
                    $gender     = sanitize($_POST['gender'] ?? '');

                    // Check duplicates
                    $chk = $pdo->prepare("SELECT id FROM students WHERE email=? OR roll_number=?");
                    $chk->execute([$email, $rollNo]);
                    if ($chk->fetch()) {
                        $message = 'Email or Roll Number already registered.';
                    } else {
                        // Handle photo
                        $photoName = 'default.png';
                        if (!empty($_FILES['profile_photo']['name'])) {
                            $up = uploadFile($_FILES['profile_photo'], UPLOAD_PROFILES);
                            if ($up['success']) $photoName = $up['filename'];
                        }

                        $stmt = $pdo->prepare("INSERT INTO students (full_name,roll_number,email,password,mobile,department_id,year,division,gender,profile_photo) VALUES (?,?,?,?,?,?,?,?,?,?)");
                        $stmt->execute([$fullName,$rollNo,$email,$hashed,$mobile,$deptId,$year,$division,$gender,$photoName]);
                        $newId   = $pdo->lastInsertId();
                        $success = true;
                        $otp     = sendOtp($pdo, $email, 'register');
                        logActivity($pdo,'student',$newId,'REGISTER','New student registration');
                        $_SESSION['reg_user_id']   = $newId;
                        $_SESSION['reg_user_role'] = 'student';
                        $_SESSION['reg_email']     = $email;
                    }
                } elseif ($postRole === 'teacher') {
                    $fullName    = sanitize($_POST['full_name'] ?? '');
                    $teacherId   = sanitize($_POST['teacher_id'] ?? '');
                    $mobile      = sanitize($_POST['mobile'] ?? '');
                    $deptId      = (int)($_POST['department_id'] ?? 0);
                    $designation = sanitize($_POST['designation'] ?? '');

                    $chk = $pdo->prepare("SELECT id FROM teachers WHERE email=? OR teacher_id=?");
                    $chk->execute([$email,$teacherId]);
                    if ($chk->fetch()) {
                        $message = 'Email or Teacher ID already registered.';
                    } else {
                        $photoName = 'default.png';
                        if (!empty($_FILES['profile_photo']['name'])) {
                            $up = uploadFile($_FILES['profile_photo'], UPLOAD_PROFILES);
                            if ($up['success']) $photoName = $up['filename'];
                        }
                        $stmt = $pdo->prepare("INSERT INTO teachers (full_name,teacher_id,email,password,mobile,department_id,designation,profile_photo) VALUES (?,?,?,?,?,?,?,?)");
                        $stmt->execute([$fullName,$teacherId,$email,$hashed,$mobile,$deptId,$designation,$photoName]);
                        $newId   = $pdo->lastInsertId();
                        $success = true;
                        $otp     = sendOtp($pdo, $email, 'register');
                        logActivity($pdo,'teacher',$newId,'REGISTER','New teacher registration');
                        $_SESSION['reg_user_id']   = $newId;
                        $_SESSION['reg_user_role'] = 'teacher';
                        $_SESSION['reg_email']     = $email;
                    }
                }

                if ($success) $step = 'otp';
            }
        }
    } elseif ($postStep === 'otp') {
        $otpInput = trim($_POST['otp_code'] ?? '');
        $regEmail = $_SESSION['reg_email'] ?? '';
        $regUId   = $_SESSION['reg_user_id'] ?? 0;
        $rRole    = $_SESSION['reg_user_role'] ?? 'student';

        if (verifyOtp($pdo, $regEmail, $otpInput, 'register')) {
            $table = $rRole === 'student' ? 'students' : 'teachers';
            $pdo->prepare("UPDATE $table SET email_verified=1 WHERE id=?")->execute([$regUId]);
            logActivity($pdo,$rRole,$regUId,'EMAIL_VERIFIED','Email verified successfully');
            setFlash('success','✅ Registration successful! Your account is pending admin approval. Please wait for approval before logging in.');
            header('Location: ' . BASE_URL . '/login.php?role=' . $rRole);
            exit;
        } else {
            $message = 'Invalid or expired OTP.';
            $step    = 'otp';
            $postRole = $_SESSION['reg_user_role'] ?? 'student';
        }
    }

    if ($step !== 'otp') $regRole = $postRole;
}

$captcha = generateCaptcha();
$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 20px 60px;position:relative;z-index:1">
  <div style="width:100%;max-width:560px">

    <div style="text-align:center;margin-bottom:28px">
      <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#4f46e5,#06b6d4);display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 14px">📝</div>
      <h2 style="font-size:1.6rem;margin-bottom:6px"><?= $step==='otp' ? 'Verify Your Email' : 'Create Account' ?></h2>
      <p style="color:var(--text-muted);font-size:0.9rem"><?= SITE_NAME ?></p>
    </div>

    <!-- Role Toggle (form only) -->
    <?php if ($step === 'form'): ?>
    <div style="display:flex;background:var(--bg-glass);border-radius:var(--r-full);padding:4px;margin-bottom:24px;border:1px solid var(--border)">
      <?php foreach(['student'=>'🎓 Student','teacher'=>'👩‍🏫 Teacher'] as $r=>$lbl): ?>
        <a href="?role=<?= $r ?>"
           style="flex:1;text-align:center;padding:9px 4px;border-radius:var(--r-full);font-size:0.82rem;font-weight:600;text-decoration:none;
           color:<?= $regRole===$r ? '#fff' : 'var(--text-muted)' ?>;
           background:<?= $regRole===$r ? 'linear-gradient(135deg,#4f46e5,#6c63ff)' : 'transparent' ?>">
          <?= $lbl ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="glass-card" style="padding:32px">
      <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <?php if ($step === 'form'): ?>
      <form method="POST" action="" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="reg_role" value="<?= $regRole ?>">
        <input type="hidden" name="step" value="form">

        <!-- COMMON FIELDS -->
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" required placeholder="Enter your full name">
        </div>

        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required placeholder="you@example.com">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <?php if ($regRole === 'student'): ?>
            <div class="form-group">
              <label class="form-label">Roll Number *</label>
              <input type="text" name="roll_number" class="form-control" required placeholder="e.g. BCA-2024-001">
            </div>
            <div class="form-group">
              <label class="form-label">Mobile Number</label>
              <input type="tel" name="mobile" class="form-control" placeholder="+91 XXXXX XXXXX" maxlength="15">
            </div>
          <?php else: ?>
            <div class="form-group">
              <label class="form-label">Teacher ID *</label>
              <input type="text" name="teacher_id" class="form-control" required placeholder="e.g. TCH-CS-001">
            </div>
            <div class="form-group">
              <label class="form-label">Designation</label>
              <input type="text" name="designation" class="form-control" placeholder="e.g. Assistant Professor">
            </div>
          <?php endif; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr <?= $regRole==='student' ? '1fr' : '' ?>;gap:16px">
          <div class="form-group">
            <label class="form-label">Department *</label>
            <select name="department_id" class="form-control" required>
              <option value="">Select Department</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($regRole === 'student'): ?>
          <div class="form-group">
            <label class="form-label">Year *</label>
            <select name="year" class="form-control" required>
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
            </select>
          </div>
          <?php endif; ?>
        </div>

        <?php if ($regRole === 'student'): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Division</label>
            <input type="text" name="division" class="form-control" placeholder="e.g. A, B, C">
          </div>
          <div class="form-group">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-control">
              <option value="">Select</option>
              <option>Male</option><option>Female</option><option>Other</option>
            </select>
          </div>
        </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-control" required placeholder="Min 8 characters" minlength="8">
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password *</label>
            <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
          </div>
        </div>

        <!-- Profile Photo -->
        <div class="form-group">
          <label class="form-label">Profile Photo</label>
          <div style="display:flex;align-items:center;gap:16px">
            <img id="photoPreview" src="<?= BASE_URL ?>/assets/images/default-avatar.png" alt="Preview"
                 style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--border)">
            <input type="file" name="profile_photo" accept="image/*" class="form-control"
                   data-preview="#photoPreview" class="file-preview-input">
          </div>
        </div>

        <!-- CAPTCHA -->
        <div class="form-group">
          <label class="form-label">🔒 Security Check: <?= $captcha ?></label>
          <input type="number" name="captcha" class="form-control" required placeholder="Answer" min="0" max="20">
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">
          <i class="fas fa-user-plus"></i> Register &amp; Send OTP
        </button>

        <p style="text-align:center;margin-top:16px;font-size:0.83rem;color:var(--text-muted)">
          Already registered? <a href="<?= BASE_URL ?>/login.php?role=<?= $regRole ?>">Login here</a>
        </p>
      </form>

      <?php else: /* OTP step */ ?>
      <!-- Demo OTP -->
      <?php if (!empty($_SESSION['demo_otp'])): ?>
        <div class="alert alert-demo">
          <strong>🔔 DEMO MODE – Email OTP</strong>
          <span style="font-size:2rem;font-weight:800;letter-spacing:4px;color:var(--warning);margin:8px 0;display:block"><?= $_SESSION['demo_otp'] ?></span>
          <small style="color:var(--text-muted)">In production this would be sent via email.</small>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="reg_role" value="<?= htmlspecialchars($_SESSION['reg_user_role'] ?? 'student') ?>">
        <input type="hidden" name="step" value="otp">
        <input type="hidden" id="otpHidden" name="otp_code">

        <p style="text-align:center;color:var(--text-muted);margin-bottom:24px">
          We've sent a 6-digit OTP to <strong><?= htmlspecialchars($_SESSION['reg_email'] ?? '') ?></strong>
        </p>

        <div class="otp-inputs">
          <?php for ($i=0;$i<6;$i++): ?>
            <input type="text" class="otp-input" maxlength="1" pattern="\d" inputmode="numeric">
          <?php endfor; ?>
        </div>

        <div class="otp-timer">Expires in <span id="otpCountdown">2:00</span></div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:20px">
          <i class="fas fa-check-circle"></i> Verify & Complete Registration
        </button>
      </form>
      <script>
        let secs = 120;
        const el = document.getElementById('otpCountdown');
        const t = setInterval(() => {
          secs--;
          if (secs <= 0) { clearInterval(t); el.textContent='EXPIRED'; el.style.color='var(--danger)'; return; }
          el.textContent = Math.floor(secs/60)+':'+String(secs%60).padStart(2,'0');
        }, 1000);
      </script>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Photo preview
document.querySelector('input[name="profile_photo"]')?.addEventListener('change', function() {
  const preview = document.getElementById('photoPreview');
  if (!this.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => preview.src = e.target.result;
  reader.readAsDataURL(this.files[0]);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
