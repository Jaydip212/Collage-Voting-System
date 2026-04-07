<?php
// ============================================================
// UTILITY FUNCTIONS
// includes/functions.php
// ============================================================

require_once __DIR__ . '/config.php';

// ============================================================
// SECURITY HELPERS
// ============================================================
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================
// CAPTCHA HELPERS
// ============================================================
function generateCaptcha() {
    $a = rand(1, 9);
    $b = rand(1, 9);
    $_SESSION['captcha_answer'] = $a + $b;
    return "$a + $b = ?";
}

function verifyCaptcha($answer) {
    if (!isset($_SESSION['captcha_answer'])) return false;
    $correct = (int)$_SESSION['captcha_answer'];
    unset($_SESSION['captcha_answer']);
    return ((int)$answer === $correct);
}


function getIp() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function getDeviceInfo() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

function getDeviceFingerprint() {
    return md5(getIp() . getDeviceInfo());
}

// ============================================================
// ACTIVITY LOG
// ============================================================
function logActivity($pdo, $role, $userId, $action, $description = '') {
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_role, user_id, action, description, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$role, $userId, $action, $description, getIp(), getDeviceInfo()]);
}

// ============================================================
// ELECTION HELPERS
// ============================================================
function isElectionActive($election) {
    $now = new DateTime();
    $start = new DateTime($election['start_datetime']);
    $end = new DateTime($election['end_datetime']);
    return ($now >= $start && $now <= $end && $election['status'] === 'active');
}

function getElectionStatus($election) {
    $now = new DateTime();
    $start = new DateTime($election['start_datetime']);
    $end = new DateTime($election['end_datetime']);
    if ($election['status'] === 'frozen') return 'frozen';
    if ($election['status'] === 'published') return 'published';
    if ($now < $start) return 'upcoming';
    if ($now > $end) return 'completed';
    return 'active';
}

function autoUpdateElectionStatus($pdo) {
    // Mark completed elections
    $pdo->query("
        UPDATE elections 
        SET status = 'completed' 
        WHERE status = 'active' AND end_datetime < NOW()
    ");
    // Mark upcoming as active
    $pdo->query("
        UPDATE elections 
        SET status = 'active' 
        WHERE status = 'upcoming' AND start_datetime <= NOW() AND end_datetime >= NOW()
    ");
}

function countdownInfo($datetime) {
    $now = time();
    $target = strtotime($datetime);
    $diff = $target - $now;
    if ($diff <= 0) return ['days'=>0,'hours'=>0,'minutes'=>0,'seconds'=>0,'ended'=>true];
    return [
        'days'    => floor($diff / 86400),
        'hours'   => floor(($diff % 86400) / 3600),
        'minutes' => floor(($diff % 3600) / 60),
        'seconds' => $diff % 60,
        'ended'   => false
    ];
}

// ============================================================
// VOTING CHECKS
// ============================================================
function hasVoted($pdo, $electionId, $voterType, $voterId) {
    $stmt = $pdo->prepare("
        SELECT vote_id FROM votes 
        WHERE election_id=? AND voter_type=? AND voter_id=?
    ");
    $stmt->execute([$electionId, $voterType, $voterId]);
    return $stmt->fetch() !== false;
}

function hasVotedFromDevice($pdo, $electionId, $fingerprint) {
    // Check device fingerprint in device_info column
    $stmt = $pdo->prepare("
        SELECT vote_id FROM votes 
        WHERE election_id=? AND device_info LIKE ?
    ");
    $stmt->execute([$electionId, '%' . $fingerprint . '%']);
    return $stmt->fetch() !== false;
}

function getVoteCount($pdo, $electionId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votes WHERE election_id=?");
    $stmt->execute([$electionId]);
    return $stmt->fetch()['total'];
}

// ============================================================
// FRAUD DETECTION
// ============================================================
function recordFailedLogin($pdo, $identifier) {
    $stmt = $pdo->prepare("INSERT INTO failed_logins (identifier, ip_address) VALUES (?,?)");
    $stmt->execute([$identifier, getIp()]);
}

function getFailedLoginCount($pdo, $minutes = 15) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt FROM failed_logins 
        WHERE ip_address=? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    $stmt->execute([getIp(), $minutes]);
    return $stmt->fetch()['cnt'];
}

function isSuspiciousIp($pdo, $threshold = 5) {
    return getFailedLoginCount($pdo) >= $threshold;
}

// ============================================================
// DEPARTMENT HELPERS
// ============================================================
function getDepartments($pdo) {
    return $pdo->query("SELECT * FROM departments WHERE is_active=1 ORDER BY name")->fetchAll();
}

function getDepartmentById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ============================================================
// USER HELPERS
// ============================================================
function getStudentById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT s.*, d.name as dept_name FROM students s LEFT JOIN departments d ON s.department_id=d.id WHERE s.id=?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getTeacherById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT t.*, d.name as dept_name FROM teachers t LEFT JOIN departments d ON t.department_id=d.id WHERE t.id=?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ============================================================
// NOTIFICATION
// ============================================================
function addNotification($pdo, $role, $recipientId, $title, $message, $type = 'info') {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (recipient_role, recipient_id, title, message, type)
        VALUES (?,?,?,?,?)
    ");
    $stmt->execute([$role, $recipientId, $title, $message, $type]);
}

function getUnreadNotifications($pdo, $role, $userId) {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE is_read=0 AND (
            (recipient_role=? AND recipient_id=?) OR recipient_role='all'
        ) ORDER BY created_at DESC LIMIT 10
    ");
    $stmt->execute([$role, $userId]);
    return $stmt->fetchAll();
}

// ============================================================
// RESULTS
// ============================================================
function publishResults($pdo, $electionId) {
    // Calculate votes per candidate
    $stmt = $pdo->prepare("
        SELECT candidate_id, COUNT(*) as total_votes 
        FROM votes WHERE election_id=? 
        GROUP BY candidate_id ORDER BY total_votes DESC
    ");
    $stmt->execute([$electionId]);
    $results = $stmt->fetchAll();

    $rank = 1;
    foreach ($results as $r) {
        $isWinner = ($rank === 1) ? 1 : 0;
        $ins = $pdo->prepare("
            INSERT INTO results (election_id, candidate_id, total_votes, `rank`, is_winner, published_at)
            VALUES (?,?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE total_votes=VALUES(total_votes), `rank`=VALUES(`rank`), is_winner=VALUES(is_winner), published_at=NOW()
        ");
        $ins->execute([$electionId, $r['candidate_id'], $r['total_votes'], $rank, $isWinner]);
        $rank++;
    }

    $pdo->prepare("UPDATE elections SET status='published', is_result_published=1 WHERE id=?")->execute([$electionId]);
}

// ============================================================
// FILE UPLOAD
// ============================================================
function uploadFile($file, $uploadDir, $allowedTypes = ['jpg','jpeg','png','gif']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File size exceeds 5MB'];
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newName = uniqid('img_', true) . '.' . $ext;
    $dest = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => true, 'filename' => $newName];
    }
    return ['success' => false, 'error' => 'Upload failed'];
}


// ============================================================
// DATE FORMATTING
// ============================================================
function formatDate($datetime) {
    return date('d M Y, h:i A', strtotime($datetime));
}

function timeAgo($datetime) {
    $now = time();
    $diff = $now - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hrs ago';
    return floor($diff/86400) . ' days ago';
}

// ============================================================
// FLASH MESSAGES
// ============================================================
function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlash($type) {
    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

function showFlashes() {
    $types = ['success', 'danger', 'warning', 'info'];
    $html = '';
    foreach ($types as $type) {
        $msg = getFlash($type);
        if ($msg) {
            $html .= "<div class='alert alert-{$type}'><i class='alert-icon'></i>" . sanitize($msg) . "</div>";
        }
    }
    return $html;
}

// ============================================================
// STATS HELPERS
// ============================================================
function getDashboardStats($pdo) {
    return [
        'total_students'  => $pdo->query("SELECT COUNT(*) FROM students WHERE is_active=1")->fetchColumn(),
        'total_teachers'  => $pdo->query("SELECT COUNT(*) FROM teachers WHERE is_active=1")->fetchColumn(),
        'total_elections' => $pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn(),
        'active_elections'=> $pdo->query("SELECT COUNT(*) FROM elections WHERE status='active'")->fetchColumn(),
        'total_votes'     => $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn(),
        'total_candidates'=> $pdo->query("SELECT COUNT(*) FROM candidates WHERE status='approved'")->fetchColumn(),
        'total_depts'     => $pdo->query("SELECT COUNT(*) FROM departments WHERE is_active=1")->fetchColumn(),
        'pending_students'=> $pdo->query("SELECT COUNT(*) FROM students WHERE is_approved=0")->fetchColumn(),
        'pending_teachers' => $pdo->query("SELECT COUNT(*) FROM teachers WHERE is_approved=0")->fetchColumn(),
    ];
}

// ============================================================
// HOD HELPERS
// ============================================================
function getHodById($pdo, $id) {
    $stmt = $pdo->prepare('SELECT h.*, d.name as dept_name FROM hods h LEFT JOIN departments d ON h.department_id=d.id WHERE h.id=?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

