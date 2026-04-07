<?php
// teacher/vote.php - Same logic as student/vote.php but for teachers
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/otp.php';
requireRole('teacher');

autoUpdateElectionStatus($pdo);
$teacher    = getTeacherById($pdo, $_SESSION['user_id']);
$electionId = (int)($_GET['election_id'] ?? 0);

if (!$electionId) { header('Location: ' . BASE_URL . '/teacher/elections.php'); exit; }

$stmt = $pdo->prepare("SELECT e.*, d.name as dept_name FROM elections e LEFT JOIN departments d ON e.department_id=d.id WHERE e.id=? AND e.election_type='teacher'");
$stmt->execute([$electionId]);
$election = $stmt->fetch();

if (!$election) { setFlash('danger','Election not found or not a teacher election.'); header('Location: ' . BASE_URL . '/teacher/elections.php'); exit; }

$isActive = isElectionActive($election);
$hasVoted = hasVoted($pdo, $electionId, 'teacher', $_SESSION['user_id']);
$step     = $_GET['step'] ?? 'vote';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postStep = $_POST['step'] ?? 'vote';

    if ($postStep === 'confirm') {
        if (!$isActive || $hasVoted) { setFlash('danger','Cannot vote.'); header('Location: ' . BASE_URL . "/teacher/vote.php?election_id=$electionId"); exit; }
        $candidateId = (int)$_POST['candidate_id'];
        $cv = $pdo->prepare("SELECT id FROM candidates WHERE id=? AND election_id=? AND status='approved'");
        $cv->execute([$candidateId, $electionId]);
        if (!$cv->fetch()) { setFlash('danger','Invalid candidate.'); header('Location: ' . BASE_URL . "/teacher/vote.php?election_id=$electionId"); exit; }
        $_SESSION['pending_vote'] = ['election_id'=>$electionId,'candidate_id'=>$candidateId];
        sendOtp($pdo, $teacher['email'], 'vote_confirm');
        header('Location: ' . BASE_URL . "/teacher/vote.php?election_id=$electionId&step=otp"); exit;
    }

    if ($postStep === 'otp') {
        $otpInput = trim($_POST['otp_code'] ?? '');
        if (!isset($_SESSION['pending_vote'])) { setFlash('danger','Session expired.'); header('Location: ' . BASE_URL . "/teacher/vote.php?election_id=$electionId"); exit; }
        if (verifyOtp($pdo, $teacher['email'], $otpInput, 'vote_confirm')) {
            $pv = $_SESSION['pending_vote'];
            $voteHash = hash('sha256', $pv['election_id'].'|teacher|'.$_SESSION['user_id'].'|'.$pv['candidate_id'].'|'.time());
            $pdo->prepare("INSERT INTO votes (election_id,candidate_id,voter_type,voter_id,ip_address,device_info,vote_hash) VALUES (?,?,'teacher',?,?,?,?)")
                ->execute([$pv['election_id'],$pv['candidate_id'],$_SESSION['user_id'],getIp(),getDeviceFingerprint(),$voteHash]);
            logActivity($pdo,'teacher',$_SESSION['user_id'],'VOTE_CAST',"Teacher vote cast for election {$pv['election_id']}");
            unset($_SESSION['pending_vote'],$_SESSION['demo_otp']);
            setFlash('success','🎉 Your vote has been cast!');
            header('Location: ' . BASE_URL . '/teacher/index.php'); exit;
        } else {
            setFlash('danger','Invalid OTP.');
            header('Location: ' . BASE_URL . "/teacher/vote.php?election_id=$electionId&step=otp"); exit;
        }
    }
}

$candStmt = $pdo->prepare("SELECT c.*, d.name as dept_name FROM candidates c LEFT JOIN departments d ON c.department_id=d.id WHERE c.election_id=? AND c.status='approved' ORDER BY c.candidate_number");
$candStmt->execute([$electionId]);
$candidates = $candStmt->fetchAll();

$pageTitle='Vote'; $activeMenu='elections';
$sidebarLinks=[
  ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','href'=>BASE_URL.'/teacher/index.php','key'=>'dashboard'],
  ['icon'=>'fas fa-vote-yea','label'=>'Elections','href'=>BASE_URL.'/teacher/elections.php','key'=>'elections'],
  ['icon'=>'fas fa-poll-h','label'=>'Results','href'=>BASE_URL.'/teacher/results.php','key'=>'results'],
  ['icon'=>'fas fa-user-circle','label'=>'My Profile','href'=>BASE_URL.'/teacher/profile.php','key'=>'profile'],
];
$roleLabel='Teacher';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div style="margin-bottom:20px"><a href="<?= BASE_URL ?>/teacher/elections.php" style="color:var(--text-muted);font-size:0.82rem"><i class="fas fa-arrow-left"></i> Back to Elections</a></div>

<div class="glass-card" style="padding:24px;margin-bottom:24px">
  <h2 style="font-size:1.2rem;margin-bottom:6px"><?= htmlspecialchars($election['title']) ?></h2>
  <div style="font-size:0.82rem;color:var(--text-muted)">Teacher Election · Ends: <?= formatDate($election['end_datetime']) ?></div>
  <?php if ($isActive): ?><div data-countdown="<?= $election['end_datetime'] ?>" style="margin-top:12px"></div><?php endif; ?>
</div>

<?php if ($hasVoted): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> ✅ You have already voted in this election.</div>
<?php elseif (!$isActive): ?>
  <div class="alert alert-warning"><i class="fas fa-clock"></i> This election is not currently active.</div>
<?php elseif ($step==='otp'): ?>
  <div style="max-width:440px;margin:0 auto">
    <div class="glass-card" style="padding:32px;text-align:center">
      <div style="font-size:3rem;margin-bottom:14px">🔐</div>
      <h3 style="margin-bottom:8px">Confirm Your Vote</h3>
      <?php if (!empty($_SESSION['demo_otp'])): ?>
        <div class="alert alert-demo"><strong>🔔 DEMO OTP</strong><span style="font-size:1.8rem;font-weight:800;letter-spacing:4px;color:var(--warning);display:block;margin:8px 0"><?= $_SESSION['demo_otp'] ?></span></div>
      <?php endif; ?>
      <form method="POST"><input type="hidden" name="step" value="otp"><input type="hidden" id="otpHidden" name="otp_code">
        <div class="otp-inputs"><?php for ($i=0;$i<6;$i++): ?><input type="text" class="otp-input" maxlength="1" pattern="\d" inputmode="numeric"><?php endfor; ?></div>
        <div class="otp-timer">Expires in <span id="otpCountdown">2:00</span></div>
        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:20px"><i class="fas fa-check-circle"></i> Confirm Vote</button>
      </form>
    </div>
  </div>
  <script>let s=120;const e=document.getElementById('otpCountdown');const t=setInterval(()=>{s--;if(s<=0){clearInterval(t);e.textContent='EXPIRED';e.style.color='var(--danger)';return;}e.textContent=Math.floor(s/60)+':'+String(s%60).padStart(2,'0');},1000);</script>
<?php else: ?>
  <h3 style="font-size:1rem;margin-bottom:16px"><i class="fas fa-hand-pointer" style="color:var(--accent)"></i> Select Your Candidate</h3>
  <?php if (empty($candidates)): ?>
    <div class="glass-card" style="padding:40px;text-align:center"><h3>No Candidates</h3><p style="color:var(--text-muted)">No approved candidates yet.</p></div>
  <?php else: ?>
    <form method="POST" id="voteForm">
      <input type="hidden" name="step" value="confirm"><input type="hidden" id="selectedCandidate" name="candidate_id">
      <div class="grid-auto" style="margin-bottom:24px">
        <?php foreach ($candidates as $c): ?>
          <div class="candidate-card" data-id="<?= $c['id'] ?>">
            <?php if ($c['candidate_number']): ?><div class="candidate-number"><?= $c['candidate_number'] ?></div><?php endif; ?>
            <img class="candidate-photo" src="<?= BASE_URL ?>/uploads/candidates/<?= htmlspecialchars($c['photo']) ?>" alt="<?= htmlspecialchars($c['name']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
            <div class="candidate-name"><?= htmlspecialchars($c['name']) ?></div>
            <div class="candidate-dept"><?= htmlspecialchars($c['dept_name']??'') ?></div>
            <?php if ($c['position']): ?><span class="badge badge-info" style="font-size:0.7rem;margin-bottom:8px"><?= htmlspecialchars($c['position']) ?></span><?php endif; ?>
            <?php if ($c['bio']): ?><p style="font-size:0.78rem;color:var(--text-muted);line-height:1.5;text-align:left;margin-top:8px"><?= htmlspecialchars(substr($c['bio'],0,100)) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div style="max-width:380px;margin:0 auto">
        <button type="submit" class="btn btn-primary btn-full btn-lg" id="voteSubmitBtn" disabled><i class="fas fa-vote-yea"></i> Proceed to Confirm</button>
      </div>
    </form>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
