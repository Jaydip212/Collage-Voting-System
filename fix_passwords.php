<?php
// ============================================================
// EMERGENCY FIX SCRIPT
// Run this in browser ONCE to fix all issues:
// http://localhost/collage%20voting%20system/fix_passwords.php
// OR
// http://localhost/voting/fix_passwords.php
// ============================================================

$host = 'localhost';
$db   = 'college_voting_system';
$user = 'root';
$pass = '';

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px;font-size:1rem'>";
echo "=== HVD College Voting System – Emergency Fix ===\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Fix Admin password
    $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE admins SET password=? WHERE email='admin@college.edu'")->execute([$adminHash]);
    echo "✅ Admin password reset → admin123\n";

    // 2. Fix HOD password
    $hodHash = password_hash('hod123', PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE hods SET password=? WHERE email='hod.cs@college.edu'")->execute([$hodHash]);
    echo "✅ HOD password reset   → hod123\n";

    // 3. Clear ALL failed login attempts
    $pdo->exec("DELETE FROM failed_logins");
    echo "✅ Failed login attempts cleared (no more 15-min block)\n";

    // 4. Clear expired OTPs
    $pdo->exec("DELETE FROM otp_verification WHERE expires_at < NOW()");
    echo "✅ Expired OTPs cleaned up\n";

    // 5. Verify passwords work
    $row = $pdo->query("SELECT password FROM admins WHERE email='admin@college.edu'")->fetch();
    $ok  = password_verify('admin123', $row['password']);
    echo "\n🔐 Admin password verify: " . ($ok ? "PASS ✅" : "FAIL ❌") . "\n";

    $hodRow = $pdo->query("SELECT password FROM hods WHERE email='hod.cs@college.edu'")->fetch();
    $hodOk  = password_verify('hod123', $hodRow['password']);
    echo "🔐 HOD password verify:  " . ($hodOk ? "PASS ✅" : "FAIL ❌") . "\n";

    echo "\n=== READY TO LOGIN ===\n";
    echo "Admin → admin@college.edu    / admin123\n";
    echo "HOD   → hod.cs@college.edu   / hod123\n";
    echo "\nLogin URL: <a href='login.php?role=admin' style='color:#06b6d4'>login.php?role=admin</a>\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Make sure MySQL is running and database is imported!\n";
}

echo "</pre>";
