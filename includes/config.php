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
define('BASE_URL', 'http://localhost/collage%20voting%20system');
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
