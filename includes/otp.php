<?php
// ============================================================
// OTP SYSTEM (Simulated – shown on screen for XAMPP demo)
// includes/otp.php
// ============================================================

require_once __DIR__ . '/config.php';

/**
 * Generate and store a 6-digit OTP
 */
function generateOtp($pdo, $identifier, $purpose = 'register') {
    // Invalidate old OTPs
    $pdo->prepare("UPDATE otp_verification SET is_used=1 WHERE identifier=? AND purpose=?")->execute([$identifier, $purpose]);

    $otp = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + (OTP_EXPIRY_MINUTES * 60));

    $stmt = $pdo->prepare("INSERT INTO otp_verification (identifier, otp_code, purpose, expires_at) VALUES (?,?,?,?)");
    $stmt->execute([$identifier, $otp, $purpose, $expiresAt]);

    return $otp;
}

/**
 * Verify OTP
 */
function verifyOtp($pdo, $identifier, $otpCode, $purpose = 'register') {
    $stmt = $pdo->prepare("
        SELECT * FROM otp_verification 
        WHERE identifier=? AND otp_code=? AND purpose=? AND is_used=0 AND expires_at > NOW()
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$identifier, $otpCode, $purpose]);
    $record = $stmt->fetch();

    if ($record) {
        $pdo->prepare("UPDATE otp_verification SET is_used=1 WHERE id=?")->execute([$record['id']]);
        return true;
    }
    return false;
}

/**
 * Get remaining seconds for OTP validity
 */
function getOtpRemainingSeconds($pdo, $identifier, $purpose = 'register') {
    $stmt = $pdo->prepare("
        SELECT expires_at FROM otp_verification 
        WHERE identifier=? AND purpose=? AND is_used=0 AND expires_at > NOW()
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$identifier, $purpose]);
    $record = $stmt->fetch();
    if ($record) {
        return strtotime($record['expires_at']) - time();
    }
    return 0;
}

/**
 * Send OTP (simulated – in real project replace with actual mail/SMS)
 * Returns the OTP so it can be shown on screen for demo purposes
 */
function sendOtp($pdo, $identifier, $purpose = 'register') {
    $otp = generateOtp($pdo, $identifier, $purpose);
    
    // ── SIMULATION MODE ──────────────────────────────────────
    // Store OTP in session so we can show it on screen
    $_SESSION['demo_otp']            = $otp;
    $_SESSION['demo_otp_identifier'] = $identifier;
    $_SESSION['demo_otp_purpose']    = $purpose;
    // ─────────────────────────────────────────────────────────

    /* 
    // To enable real email, uncomment and configure PHPMailer:
    // require_once __DIR__ . '/../vendor/autoload.php';
    // $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    // $mail->isSMTP();
    // $mail->Host       = 'smtp.gmail.com';
    // $mail->SMTPAuth   = true;
    // $mail->Username   = 'your@gmail.com';
    // $mail->Password   = 'your-app-password';
    // $mail->SMTPSecure = 'tls';
    // $mail->Port       = 587;
    // $mail->setFrom('your@gmail.com', SITE_NAME);
    // $mail->addAddress($identifier);
    // $mail->Subject = 'Your OTP – ' . SITE_NAME;
    // $mail->Body    = "Your OTP is: <b>$otp</b>. Valid for 2 minutes.";
    // $mail->isHTML(true);
    // $mail->send();
    */

    return $otp;
}
