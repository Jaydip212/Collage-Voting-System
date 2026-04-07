<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/otp.php';
requireRole('student');

autoUpdateElectionStatus($pdo);

$electionId = (int)($_GET['election_id'] ?? 0);
if (!$electionId) {
    header('Location: ' . BASE_URL . '/student/elections.php');
    exit;
}

// Fetch election
$stmt = $pdo->prepare("SELECT e.*, d.name as dept_name FROM elections e LEFT JOIN departments d ON e.department_id=d.id WHERE e.id=?");
$stmt->execute([$electionId]);
$election = $stmt->fetch();

if (!$election) {
    setFlash('danger','Election not found.');
    header('Location: ' . BASE_URL . '/student/elections.php');
    exit;
}

$student = getStudentById($pdo, $_SESSION['user_id']);

// Check eligibility
if ($election['department_id'] && $election['department_id'] != $student['department_id']) {
    setFlash('danger','You are not eligible for this election (department mismatch).');
    header('Location: ' . BASE_URL . '/student/elections.php');
    exit;
}

$isActive = isElectionActive($election);
$hasVoted = hasVoted($pdo, $electionId, 'student', $_SESSION['user_id']);
$step     = $_GET['step'] ?? 'vote'; // 'vote' | 'confirm' | 'otp'

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postStep = $_POST['step'] ?? 'vote';

    if ($postStep === 'confirm') {
        if (!$isActive) { setFlash('danger','Election is not active.'); header('Location: ' . BASE_URL . "/student/vote.php?election_id=$electionId"); exit; }
        if ($hasVoted)  { setFlash('danger','You have already voted.'); header('Location: ' . BASE_URL . "/student/vote.php?election_id=$electionId"); exit; }

        $candidateId = (int)$_POST['candidate_id'];
        // Validate candidate belongs to this election
        $cv = $pdo->prepare("SELECT id FROM candidates WHERE id=? AND election_id=? AND status='approved'");
        $cv->execute([$candidateId, $electionId]);
        if (!$cv->fetch()) { setFlash('danger','Invalid candidate selection.'); header('Location: ' . BASE_URL . "/student/vote.php?election_id=$electionId"); exit; }

        $_SESSION['pending_vote'] = ['election_id'=>$electionId,'candidate_id'=>$candidateId];
        $otp = sendOtp($pdo, $student['email'], 'vote_confirm');
        header('Location: ' . BASE_URL . "/student/vote.php?election_id=$electionId&step=otp");
        exit;
    }

    if ($postStep === 'otp') {
        $otpInput = trim($_POST['otp_code'] ?? '');
        if (!isset($_SESSION['pending_vote']) || $_SESSION['pending_vote']['election_id'] != $electionId) {
            setFlash('danger','Session expired. Please try again.'); header('Location: ' . BASE_URL . "/student/vote.php?election_id=$electionId"); exit;
        }

        if (verifyOtp($pdo, $student['email'], $otpInput, 'vote_confirm')) {
            $pv          = $_SESSION['pending_vote'];
            $fingerprint = getDeviceFingerprint();

            // Double-check not voted
            if (hasVoted($pdo, $pv['election_id'], 'student', $_SESSION['user_id'])) {
                setFlash('danger','Duplicate vote attempt blocked.'); header('Location: '.BASE_URL.'/student/index.php'); exit;
            }

            // Device check
            if (hasVotedFromDevice($pdo, $pv['election_id'], $fingerprint)) {
                setFlash('warning','A vote has already been cast from this device for this election.');
                logActivity($pdo,'student',$_SESSION['user_id'],'DEVICE_DUPLICATE_ATTEMPT',"Election: ".$pv['election_id']);
                header('Location: '.BASE_URL.'/student/index.php'); exit;
            }

            // Cast vote
            $voteHash = hash('sha256', $pv['election_id'].'|student|'.$_SESSION['user_id'].'|'.$pv['candidate_id'].'|'.time());
            $vstmt = $pdo->prepare("INSERT INTO votes (election_id,candidate_id,voter_type,voter_id,ip_address,device_info,vote_hash) VALUES (?,?,'student',?,?,?,?)");
            $vstmt->execute([$pv['election_id'],$pv['candidate_id'],$_SESSION['user_id'],getIp(),getDeviceFingerprint(),$voteHash]);

            logActivity($pdo,'student',$_SESSION['user_id'],'VOTE_CAST',"Election: {$pv['election_id']}, Candidate: {$pv['candidate_id']}");
            addNotification($pdo,'student',$_SESSION['user_id'],'Vote Successful!','Your vote has been recorded securely.','success');
            unset($_SESSION['pending_vote'],$_SESSION['demo_otp']);

            setFlash('success','🎉 Your vote has been cast successfully!');
            header('Location: '.BASE_URL.'/student/index.php');
            exit;
        } else {
            setFlash('danger','Invalid or expired OTP.');
            header('Location: '.BASE_URL."/student/vote.php?election_id=$electionId&step=otp");
            exit;
        }
    }
}

// Fetch candidates
$candStmt = $pdo->prepare("SELECT c.*, d.name as dept_name FROM candidates c LEFT JOIN departments d ON c.department_id=d.id WHERE c.election_id=? AND c.status='approved' ORDER BY c.candidate_number");
$candStmt->execute([$electionId]);
$candidates = $candStmt->fetchAll();

$pageTitle  = 'Vote – ' . $election['title'];
$activeMenu = 'elections';
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

<!-- Breadcrumb -->
<div style="display:flex;align-items:center;gap:8px;font-size:0.82rem;color:var(--text-muted);margin-bottom:20px">
  <a href="<?= BASE_URL ?>/student/elections.php">Elections</a>
  <i class="fas fa-chevron-right"></i>
  <span><?= htmlspecialchars($election['title']) ?></span>
</div>

<!-- Election Header -->
<div class="glass-card" style="padding:24px;margin-bottom:24px">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px">
    <div>
      <h2 style="font-size:1.3rem;margin-bottom:6px"><?= htmlspecialchars($election['title']) ?></h2>
      <div style="display:flex;gap:12px;flex-wrap:wrap;font-size:0.82rem;color:var(--text-muted)">
        <span><i class="fas fa-building"></i> <?= htmlspecialchars($election['dept_name'] ?? 'All Departments') ?></span>
        <span><i class="fas fa-users"></i> <?= count($candidates) ?> Candidates</span>
        <span><i class="fas fa-clock"></i> Ends: <?= formatDate($election['end_datetime']) ?></span>
      </div>
    </div>
    <div data-countdown="<?= $election['end_datetime'] ?>"></div>
  </div>
</div>

<?php if ($hasVoted): ?>
  <div class="alert alert-success" style="font-size:1rem">
    <i class="fas fa-check-circle"></i>
    ✅ You have already voted in this election. Thank you for participating!
  </div>
  <div style="text-align:center;margin-top:20px">
    <a href="<?= BASE_URL ?>/student/index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  </div>

<?php elseif (!$isActive): ?>
  <div class="alert alert-warning">
    <i class="fas fa-clock"></i>
    This election is not currently active. Please check back later.
  </div>

<?php elseif ($step === 'otp'): ?>
  <!-- OTP STEP -->
  <div style="max-width:440px;margin:0 auto">
    <div class="glass-card" style="padding:32px;text-align:center">
      <div style="font-size:3rem;margin-bottom:14px">🔐</div>
      <h3 style="margin-bottom:8px">Confirm Your Vote</h3>
      <p style="color:var(--text-muted);font-size:0.88rem;margin-bottom:20px">
        Enter the OTP sent to <strong><?= htmlspecialchars($student['email']) ?></strong> to confirm your vote.
      </p>

      <!-- Demo OTP Banner -->
      <?php if (!empty($_SESSION['demo_otp'])): ?>
        <div class="alert alert-demo">
          <strong>🔔 DEMO – Vote OTP</strong>
          <span style="font-size:1.8rem;font-weight:800;letter-spacing:4px;color:var(--warning);display:block;margin:8px 0"><?= $_SESSION['demo_otp'] ?></span>
        </div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="step" value="otp">
        <input type="hidden" id="otpHidden" name="otp_code">

        <div class="otp-inputs">
          <?php for ($i=0;$i<6;$i++): ?>
            <input type="text" class="otp-input" maxlength="1" pattern="\d" inputmode="numeric">
          <?php endfor; ?>
        </div>
        <div class="otp-timer">Expires in <span id="otpCountdown">2:00</span></div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:20px">
          <i class="fas fa-check-circle"></i> Confirm Vote
        </button>
        <a href="?election_id=<?= $electionId ?>" class="btn btn-outline btn-full" style="margin-top:10px">
          ← Back
        </a>
      </form>
    </div>
  </div>
  <script>
    let secs=120;const el=document.getElementById('otpCountdown');
    const t=setInterval(()=>{secs--;if(secs<=0){clearInterval(t);el.textContent='EXPIRED';el.style.color='var(--danger)';return;}el.textContent=Math.floor(secs/60)+':'+String(secs%60).padStart(2,'0');},1000);
  </script>

<?php else: ?>
  <!-- SELECT CANDIDATE STEP -->
  <?php if (empty($candidates)): ?>
    <div class="glass-card" style="padding:40px;text-align:center">
      <div style="font-size:3rem;margin-bottom:12px">👥</div>
      <h3>No Candidates Yet</h3>
      <p style="color:var(--text-muted)">No approved candidates for this election. Check back later.</p>
    </div>
  <?php else: ?>
    <h3 style="font-size:1rem;margin-bottom:16px"><i class="fas fa-hand-pointer" style="color:var(--accent)"></i> Select Your Candidate</h3>

    <form method="POST" id="voteForm">
      <input type="hidden" name="step" value="confirm">
      <input type="hidden" id="selectedCandidate" name="candidate_id">

      <div class="grid-auto" style="margin-bottom:24px">
        <?php foreach ($candidates as $c): ?>
          <div class="candidate-card" data-id="<?= $c['id'] ?>">
            <?php if ($c['candidate_number']): ?>
              <div class="candidate-number"><?= $c['candidate_number'] ?></div>
            <?php endif; ?>

            <img class="candidate-photo"
                 src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($c['photo']) ?>"
                 alt="<?= htmlspecialchars($c['name']) ?>"
                 onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">

            <div class="candidate-name"><?= htmlspecialchars($c['name']) ?></div>
            <div class="candidate-dept"><?= htmlspecialchars($c['dept_name'] ?? $student['dept_name']) ?></div>

            <?php if ($c['position']): ?>
              <span class="badge badge-info" style="font-size:0.7rem;margin-bottom:10px"><?= htmlspecialchars($c['position']) ?></span>
            <?php endif; ?>

            <?php if ($c['bio']): ?>
              <p style="font-size:0.78rem;color:var(--text-muted);line-height:1.5;text-align:left;margin-top:10px"><?= htmlspecialchars(substr($c['bio'],0,120)) ?></p>
            <?php endif; ?>

            <?php if ($c['symbol']): ?>
              <img src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($c['symbol']) ?>"
                   alt="Symbol" style="width:40px;height:40px;object-fit:contain;margin-top:10px">
            <?php endif; ?>

            <?php if ($c['manifesto']): ?>
              <details style="margin-top:12px;text-align:left">
                <summary style="font-size:0.78rem;color:var(--primary-light);cursor:pointer;font-weight:600">📋 Read Manifesto</summary>
                <p style="font-size:0.78rem;color:var(--text-muted);line-height:1.6;margin-top:8px;padding:10px;background:var(--bg-glass);border-radius:8px"><?= htmlspecialchars($c['manifesto']) ?></p>
              </details>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="max-width:400px;margin:0 auto">
        <button type="submit" class="btn btn-primary btn-full btn-lg" id="voteSubmitBtn" disabled>
          <i class="fas fa-vote-yea"></i> Proceed to Confirm Vote
        </button>
        <p style="text-align:center;font-size:0.78rem;color:var(--text-muted);margin-top:12px">
          <i class="fas fa-lock"></i> Your vote is anonymous and secured with OTP verification.
        </p>
      </div>
    </form>

    <script>
      document.getElementById('voteForm').addEventListener('submit', function(e) {
        const candidate = document.getElementById('selectedCandidate').value;
        if (!candidate) { e.preventDefault(); alert('Please select a candidate first.'); return; }
        if (!confirm('You are about to vote for this candidate.\n\nThis action cannot be undone. Continue?')) e.preventDefault();
      });
    </script>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
