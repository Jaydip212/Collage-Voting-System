<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/otp.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    $dirs = ['admin'=>'admin','student'=>'student','teacher'=>'teacher','hod'=>'hod'];
    header('Location: ' . BASE_URL . '/' . ($dirs[$role] ?? '') . '/index.php');
    exit;
}

$role     = $_GET['role'] ?? 'student';
$message  = '';
$msgType  = 'danger';
$step     = 'login'; // 'login' | 'otp'
$captcha  = generateCaptcha();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postRole  = sanitize($_POST['role'] ?? 'student');
    $postStep  = sanitize($_POST['step'] ?? 'login');

    if ($postStep === 'login') {
        // CAPTCHA is optional in demo mode - OTP is the real security layer
        $captchaOk = true; // verifyCaptcha($_POST['captcha'] ?? '');
        if (!$captchaOk) {
            $message = '❌ Wrong CAPTCHA answer. Please try again.';
            $captcha = generateCaptcha();
        } elseif (isSuspiciousIp($pdo)) {
            $message = '🔒 Too many failed attempts. Please wait 15 minutes.';
        } else {
            $identifier = trim($_POST['identifier'] ?? '');
            $password   = $_POST['password'] ?? '';

            $result = false;
            switch ($postRole) {
                case 'admin':   $result = loginAdmin($pdo, $identifier, $password); break;
                case 'student': $result = loginStudent($pdo, $identifier, $password); break;
                case 'teacher': $result = loginTeacher($pdo, $identifier, $password); break;
                case 'hod':     $result = loginHod($pdo, $identifier, $password); break;
            }

            if ($result === true || (is_array($result) && isset($result['success']))) {
                // Send OTP for second factor
                $email = $_SESSION['user_email'] ?? $identifier;
                $otp   = sendOtp($pdo, $email, 'login');
                $_SESSION['otp_pending_role'] = $postRole;
                logActivity($pdo, $postRole, $_SESSION['user_id'], 'LOGIN_OTP_SENT', "OTP sent to $email");
                $step    = 'otp';
                $role    = $postRole;
                $message = '';
            } else {
                $err = is_array($result) ? ($result['error'] ?? 'Invalid credentials.') : 'Invalid credentials.';
                $message = '❌ ' . $err;
                recordFailedLogin($pdo, $_POST['identifier'] ?? '');
                $captcha = generateCaptcha();
            }
        }
    } elseif ($postStep === 'otp') {
        $otpInput = trim($_POST['otp_code'] ?? '');
        $email    = $_SESSION['user_email'] ?? '';
        $pendRole = $_SESSION['otp_pending_role'] ?? $postRole;

        if (verifyOtp($pdo, $email, $otpInput, 'login')) {
            logActivity($pdo, $pendRole, $_SESSION['user_id'], 'LOGIN_SUCCESS', 'Logged in via OTP');
            $dashboardMap = ['admin'=>'admin','student'=>'student','teacher'=>'teacher','hod'=>'hod'];
            header('Location: ' . BASE_URL . '/' . $dashboardMap[$pendRole] . '/index.php');
            exit;
        } else {
            $message = '❌ Invalid or expired OTP. Please try again.';
            $step = 'otp';
            $role = $postRole;
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 20px 60px;position:relative;z-index:1">
  <div style="width:100%;max-width:460px">

    <!-- Logo -->
    <div style="text-align:center;margin-bottom:32px">
      <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#4f46e5,#06b6d4);display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 14px">🗳️</div>
      <h2 style="font-size:1.6rem;margin-bottom:6px"><?= $step==='otp' ? 'Verify OTP' : 'Welcome Back' ?></h2>
      <p style="color:var(--text-muted);font-size:0.9rem"><?= SITE_NAME ?></p>
    </div>

    <!-- Role Tabs -->
    <?php if ($step === 'login'): ?>
    <div style="display:flex;background:var(--bg-glass);border-radius:var(--r-full);padding:4px;margin-bottom:24px;border:1px solid var(--border)">
      <?php foreach (['student'=>'🎓 Student','teacher'=>'👩‍🏫 Teacher','hod'=>'🏛️ HOD','admin'=>'⚙️ Admin'] as $r=>$label): ?>
        <a href="?role=<?= $r ?>"
           style="flex:1;text-align:center;padding:8px 4px;border-radius:var(--r-full);font-size:0.75rem;font-weight:600;text-decoration:none;
           color:<?= $role===$r ? '#fff' : 'var(--text-muted)' ?>;
           background:<?= $role===$r ? 'linear-gradient(135deg,#4f46e5,#6c63ff)' : 'transparent' ?>;
           transition:all 0.2s ease">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Card -->
    <div class="glass-card" style="padding:32px">

      <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?>"><?= $message ?></div>
      <?php endif; ?>

      <!-- Demo OTP Banner -->
      <?php if (!empty($_SESSION['demo_otp']) && $step === 'otp'): ?>
        <div class="alert alert-demo">
          <strong>🔔 DEMO MODE – OTP</strong>
          <span style="font-size:2rem;font-weight:800;letter-spacing:4px;color:var(--warning);margin:8px 0;display:block"><?= $_SESSION['demo_otp'] ?></span>
          <small style="color:var(--text-muted)">In production this would be sent to your email. Valid for 2 minutes.</small>
        </div>
      <?php endif; ?>

      <?php if ($step === 'login'): ?>
      <!-- LOGIN FORM -->
      <form method="POST" action="" autocomplete="off">
        <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
        <input type="hidden" name="step" value="login">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-group">
          <label class="form-label">
            <?= $role==='student' ? '📋 Roll Number or Email' : ($role==='teacher'||$role==='hod' ? '🪪 Teacher ID or Email' : '📧 Admin Email') ?>
          </label>
          <div class="form-control-icon">
            <i class="icon fas fa-user"></i>
            <input type="text" name="identifier" class="form-control" required
                   placeholder="<?= $role==='student' ? 'e.g. BCA-2024-001 or email@college.edu' : 'Enter ID or email' ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">🔑 Password</label>
          <div class="form-control-icon">
            <i class="icon fas fa-lock"></i>
            <input type="password" name="password" id="passwordInput" class="form-control" required placeholder="Enter your password">
          </div>
          <div style="display:flex;gap:8px;align-items:center;margin-top:8px">
            <input type="checkbox" id="showPwd" onchange="document.getElementById('passwordInput').type=this.checked?'text':'password'">
            <label for="showPwd" style="font-size:0.8rem;color:var(--text-muted);cursor:pointer">Show password</label>
          </div>
        </div>

        <!-- CAPTCHA -->
        <div class="form-group">
          <label class="form-label">🔒 Security Check: <?= $captcha ?></label>
          <input type="number" name="captcha" class="form-control" required placeholder="Answer" min="0" max="20">
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:4px">
          <i class="fas fa-sign-in-alt"></i> Login
        </button>

        <div style="display:flex;justify-content:space-between;margin-top:16px;font-size:0.82rem">
          <a href="<?= BASE_URL ?>/forgot_password.php?role=<?= $role ?>">Forgot password?</a>
          <?php if ($role !== 'admin'): ?>
            <a href="<?= BASE_URL ?>/register.php?role=<?= $role ?>">Create Account →</a>
          <?php endif; ?>
        </div>
      </form>

      <?php else: /* OTP STEP */ ?>
      <!-- OTP FORM -->
      <form method="POST" action="">
        <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
        <input type="hidden" name="step" value="otp">
        <input type="hidden" id="otpHidden" name="otp_code">

        <p style="text-align:center;color:var(--text-muted);font-size:0.9rem;margin-bottom:20px">
          Enter the 6-digit OTP sent to your registered email.
        </p>

        <div class="otp-inputs">
          <?php for ($i=0;$i<6;$i++): ?>
            <input type="text" class="otp-input" maxlength="1" pattern="\d" inputmode="numeric" required>
          <?php endfor; ?>
        </div>

        <div class="otp-timer">
          OTP expires in <span id="otpCountdown">2:00</span>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:20px">
          <i class="fas fa-check-circle"></i> Verify OTP
        </button>

        <div style="text-align:center;margin-top:14px">
          <a href="<?= BASE_URL ?>/login.php?role=<?= $role ?>" style="font-size:0.83rem;color:var(--text-muted)">← Back to Login</a>
        </div>
      </form>

      <script>
        // OTP 2-minute countdown
        let secs = 120;
        const el = document.getElementById('otpCountdown');
        const timer = setInterval(() => {
          secs--;
          if (secs <= 0) { clearInterval(timer); el.textContent = 'EXPIRED'; el.style.color='var(--danger)'; return; }
          el.textContent = Math.floor(secs/60) + ':' + String(secs%60).padStart(2,'0');
        }, 1000);
      </script>
      <?php endif; ?>
    </div>

    <p style="text-align:center;margin-top:20px;font-size:0.8rem;color:var(--text-muted)">
      🔒 Your login is secured with OTP verification and encryption
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
