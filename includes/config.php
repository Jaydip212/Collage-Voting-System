<?php
// ============================================================
// DATABASE CONFIGURATION
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'college_voting_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// SITE CONFIGURATION
// ============================================================
define('SITE_NAME', 'Haribhai V Desai College Pune-02');
define('SITE_SHORT_NAME', 'HVD College');
define('SITE_TAGLINE', 'Digital Democracy – Your Vote, Your Voice');

// ── AUTO-DETECT BASE_URL (PHP 8.0+ compatible) ───────────────
// Works on ANY laptop / server without manual config changes!
if (!defined('BASE_URL')) {
    $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot    = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $projectDir = realpath(__DIR__ . '/..');   // one level up from /includes

    if ($docRoot && $projectDir && strpos($projectDir, $docRoot) === 0) {
        // Normal case: project is inside htdocs
        $relPath = substr($projectDir, strlen($docRoot));
        $relPath = str_replace('\\', '/', $relPath);

        // Encode each path segment separately (PHP 8.0 safe – no lookbehind)
        $segments = explode('/', $relPath);
        $encoded  = array_map(function($seg) {
            return rawurlencode(rawurldecode($seg));
        }, $segments);
        $relPath  = implode('/', $encoded);

        define('BASE_URL', rtrim($protocol . '://' . $host . $relPath, '/'));
    } else {
        // Fallback – strip known sub-folder names from SCRIPT_NAME
        $knownSubs   = ['admin','student','teacher','hod','api','includes','uploads'];
        $scriptParts = array_values(array_filter(
            explode('/', $_SERVER['SCRIPT_NAME'] ?? ''), 'strlen'
        ));
        $pathParts = [];
        foreach ($scriptParts as $part) {
            if (in_array($part, $knownSubs)) break;
            if (pathinfo($part, PATHINFO_EXTENSION)) break;
            $pathParts[] = rawurlencode(rawurldecode($part));
        }
        define('BASE_URL', rtrim($protocol . '://' . $host . '/' . implode('/', $pathParts), '/'));
    }
}

define('OTP_EXPIRY_MINUTES', 2);
define('SESSION_TIMEOUT_MINUTES', 30);

// ============================================================
// UPLOAD PATHS
// ============================================================
define('UPLOAD_PROFILES', __DIR__ . '/../uploads/profiles/');
define('UPLOAD_CANDIDATES', __DIR__ . '/../uploads/candidates/');
define('UPLOAD_BANNERS', __DIR__ . '/../uploads/election_banners/');
define('UPLOAD_URL_PROFILES', BASE_URL . '/uploads/profiles/');
define('UPLOAD_URL_CANDIDATES', BASE_URL . '/uploads/candidates/');
define('UPLOAD_URL_BANNERS', BASE_URL . '/uploads/election_banners/');

// ============================================================
// DATABASE CONNECTION (PDO)
// ============================================================
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die(json_encode([
        'error' => true,
        'message' => 'Database connection failed. Please check XAMPP MySQL is running. Error: ' . $e->getMessage()
    ]));
}

// ============================================================
// SESSION CONFIG
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Auto-logout after inactivity
if (isset($_SESSION['last_activity'])) {
    $idle = time() - $_SESSION['last_activity'];
    if ($idle > SESSION_TIMEOUT_MINUTES * 60) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php?msg=session_expired');
        exit;
    }
}
$_SESSION['last_activity'] = time();

// ============================================================
// TIMEZONE
// ============================================================
date_default_timezone_set('Asia/Kolkata');
