<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/otp.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/' . $_SESSION['user_role'] . '/index.php');
    exit;
}

$role    = sanitize($_GET['role'] ?? 'student');
$message = '';
$step    = 'request'; // 'request' | 'otp' | 'reset'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postStep = $_POST['step'] ?? 'request';
    $postRole = sanitize($_POST['role'] ?? 'student');

    if ($postStep === 'request') {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $tableMap = ['student'=>'students','teacher'=>'teachers','hod'=>'hods','admin'=>'admins'];
        $nameCol  = ['student'=>'full_name','teacher'=>'full_name','hod'=>'name','admin'=>'name'];
        $table    = $tableMap[$postRole] ?? 'students';
        $col      = $nameCol[$postRole] ?? 'full_name';

        $stmt = $pdo->prepare("SELECT id, email, $col as full_name FROM $table WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $otp = sendOtp($pdo, $email, 'forgot_password');
            $_SESSION['fp_email'] = $email;
            $_SESSION['fp_role']  = $postRole;
            $_SESSION['fp_id']    = $user['id'];
            $step = 'otp';
        } else {
            $message = 'No account found with this email address.';
        }
    } elseif ($postStep === 'otp') {
        $otpInput = trim($_POST['otp_code'] ?? '');
        $fpEmail  = $_SESSION['fp_email'] ?? '';
        if (verifyOtp($pdo, $fpEmail, $otpInput, 'forgot_password')) {
            $_SESSION['fp_verified'] = true;
            $step = 'reset';
        } else {
            $message = 'Invalid or expired OTP.';
            $step = 'otp';
        }
    } elseif ($postStep === 'reset') {
        if (!isset($_SESSION['fp_verified']) || !$_SESSION['fp_verified']) {
            $step = 'request'; $message = 'Session expired. Please restart.';
        } else {
            $newPass  = $_POST['new_password'] ?? '';
            $confPass = $_POST['confirm_password'] ?? '';
            if (strlen($newPass) < 8) {
                $message = 'Password must be at least 8 characters.'; $step = 'reset';
            } elseif ($newPass !== $confPass) {
                $message = 'Passwords do not match.'; $step = 'reset';
            } else {
                $hashed   = hashPassword($newPass);
                $fpRole   = $_SESSION['fp_role'] ?? 'student';
                $fpId     = $_SESSION['fp_id'] ?? 0;
                $tableMap = ['student'=>'students','teacher'=>'teachers','hod'=>'hods','admin'=>'admins'];
                $table    = $tableMap[$fpRole] ?? 'students';
                $pdo->prepare("UPDATE $table SET password=? WHERE id=?")->execute([$hashed,$fpId]);
                logActivity($pdo,$fpRole,$fpId,'PASSWORD_RESET','Password reset via OTP');
                unset($_SESSION['fp_email'],$_SESSION['fp_role'],$_SESSION['fp_id'],$_SESSION['fp_verified'],$_SESSION['demo_otp']);
                setFlash('success','✅ Password reset successfully! Please login with your new password.');
                header('Location: ' . BASE_URL . '/login.php?role=' . $fpRole);
                exit;
            }
        }
    }
}

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';
?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 20px 60px;position:relative;z-index:1">
  <div style="width:100%;max-width:440px">
    <div style="text-align:center;margin-bottom:28px">
      <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#f59e0b,#ef4444);display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 14px">🔑</div>
      <h2 style="font-size:1.6rem;margin-bottom:6px">
        <?= $step==='request' ? 'Forgot Password' : ($step==='otp' ? 'Enter OTP' : 'Set New Password') ?>
      </h2>
      <p style="color:var(--text-muted);font-size:0.9rem"><?= SITE_NAME ?></p>
    </div>

    <div class="glass-card" style="padding:32px">
      <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <!-- Demo OTP Banner -->
      <?php if (!empty($_SESSION['demo_otp']) && $step === 'otp'): ?>
        <div class="alert alert-demo">
          <strong>🔔 DEMO – Reset OTP</strong>
          <span style="font-size:2rem;font-weight:800;letter-spacing:4px;color:var(--warning);display:block;margin:8px 0"><?= $_SESSION['demo_otp'] ?></span>
        </div>
      <?php endif; ?>

      <?php if ($step === 'request'): ?>
        <form method="POST">
          <input type="hidden" name="step" value="request">
          <input type="hidden" name="role" value="<?= $role ?>">

          <div style="display:flex;background:var(--bg-glass);border-radius:var(--r-full);padding:4px;margin-bottom:20px;border:1px solid var(--border)">
            <?php foreach (['student'=>'🎓 Student','teacher'=>'👩‍🏫 Teacher','hod'=>'🏛️ HOD','admin'=>'⚙️ Admin'] as $r=>$lbl): ?>
              <a href="?role=<?= $r ?>"
                 style="flex:1;text-align:center;padding:7px 4px;border-radius:var(--r-full);font-size:0.72rem;font-weight:600;text-decoration:none;
                 color:<?= $role===$r ? '#fff' : 'var(--text-muted)' ?>;
                 background:<?= $role===$r ? 'linear-gradient(135deg,#4f46e5,#6c63ff)' : 'transparent' ?>">
                <?= $lbl ?>
              </a>
            <?php endforeach; ?>
          </div>

          <div class="form-group">
            <label class="form-label">📧 Registered Email</label>
            <div class="form-control-icon">
              <i class="icon fas fa-envelope"></i>
              <input type="email" name="email" class="form-control" required placeholder="your@email.com">
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg">
            <i class="fas fa-paper-plane"></i> Send OTP
          </button>
          <div style="text-align:center;margin-top:16px">
            <a href="<?= BASE_URL ?>/login.php?role=<?= $role ?>" style="font-size:0.82rem;color:var(--text-muted)">← Back to Login</a>
          </div>
        </form>

      <?php elseif ($step === 'otp'): ?>
        <form method="POST">
          <input type="hidden" name="step" value="otp">
          <input type="hidden" name="role" value="<?= $_SESSION['fp_role'] ?? $role ?>">
          <input type="hidden" id="otpHidden" name="otp_code">

          <p style="text-align:center;color:var(--text-muted);margin-bottom:20px;font-size:0.88rem">
            OTP sent to <strong><?= htmlspecialchars($_SESSION['fp_email'] ?? '') ?></strong>
          </p>

          <div class="otp-inputs">
            <?php for ($i=0;$i<6;$i++): ?>
              <input type="text" class="otp-input" maxlength="1" pattern="\d" inputmode="numeric">
            <?php endfor; ?>
          </div>
          <div class="otp-timer">Expires in <span id="otpCountdown">2:00</span></div>

          <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:20px"><i class="fas fa-arrow-right"></i> Verify OTP</button>
        </form>
        <script>
          let s=120;const e=document.getElementById('otpCountdown');
          setInterval(()=>{s--;if(s<=0){e.textContent='EXPIRED';e.style.color='var(--danger)';return;}e.textContent=Math.floor(s/60)+':'+String(s%60).padStart(2,'0');},1000);
        </script>

      <?php elseif ($step === 'reset'): ?>
        <form method="POST">
          <input type="hidden" name="step" value="reset">
          <div class="form-group">
            <label class="form-label">New Password *</label>
            <input type="password" name="new_password" class="form-control" required placeholder="Min 8 characters" minlength="8">
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password *</label>
            <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg"><i class="fas fa-lock"></i> Reset Password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
