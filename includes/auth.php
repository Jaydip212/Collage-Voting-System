<?php
// ============================================================
// AUTH & SESSION MANAGEMENT
// includes/auth.php
// ============================================================

require_once __DIR__ . '/config.php';

// ============================================================
// CHECK ROLE ACCESS
// ============================================================
function requireRole($role) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        $redirect = [
            'admin'   => '/login.php?role=admin',
            'student' => '/login.php?role=student',
            'teacher' => '/login.php?role=teacher',
            'hod'     => '/login.php?role=hod',
        ];
        $path = $redirect[$role] ?? '/login.php';
        header('Location: ' . BASE_URL . $path);
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function getCurrentUser() {
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'dept' => $_SESSION['user_dept'] ?? null,
    ];
}

// ============================================================
// LOGIN FUNCTIONS
// ============================================================
function loginAdmin($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email=? AND is_active=1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $admin['id'];
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_email']= $admin['email'];
        $_SESSION['user_photo']= $admin['profile_photo'];
        // Update last login
        $pdo->prepare("UPDATE admins SET last_login=NOW() WHERE id=?")->execute([$admin['id']]);
        return true;
    }
    return false;
}

function loginStudent($pdo, $identifier, $password) {
    // Identifier can be roll_number or email
    $stmt = $pdo->prepare("
        SELECT s.*, d.name as dept_name FROM students s 
        LEFT JOIN departments d ON s.department_id=d.id 
        WHERE (s.roll_number=? OR s.email=?) AND s.is_active=1
    ");
    $stmt->execute([$identifier, $identifier]);
    $student = $stmt->fetch();
    if ($student && password_verify($password, $student['password'])) {
        if (!$student['is_approved']) return ['error' => 'Your account is pending admin approval.'];
        if (!$student['email_verified']) return ['error' => 'Please verify your email first.'];
        session_regenerate_id(true);
        $_SESSION['user_id']    = $student['id'];
        $_SESSION['user_role']  = 'student';
        $_SESSION['user_name']  = $student['full_name'];
        $_SESSION['user_email'] = $student['email'];
        $_SESSION['user_dept']  = $student['department_id'];
        $_SESSION['user_photo'] = $student['profile_photo'];
        $_SESSION['dept_name']  = $student['dept_name'];
        $pdo->prepare("UPDATE students SET last_login=NOW() WHERE id=?")->execute([$student['id']]);
        return ['success' => true, 'user' => $student];
    }
    return ['error' => 'Invalid credentials.'];
}

function loginTeacher($pdo, $identifier, $password) {
    $stmt = $pdo->prepare("
        SELECT t.*, d.name as dept_name FROM teachers t 
        LEFT JOIN departments d ON t.department_id=d.id 
        WHERE (t.teacher_id=? OR t.email=?) AND t.is_active=1
    ");
    $stmt->execute([$identifier, $identifier]);
    $teacher = $stmt->fetch();
    if ($teacher && password_verify($password, $teacher['password'])) {
        if (!$teacher['is_approved']) return ['error' => 'Your account is pending admin approval.'];
        if (!$teacher['email_verified']) return ['error' => 'Please verify your email first.'];
        session_regenerate_id(true);
        $_SESSION['user_id']    = $teacher['id'];
        $_SESSION['user_role']  = 'teacher';
        $_SESSION['user_name']  = $teacher['full_name'];
        $_SESSION['user_email'] = $teacher['email'];
        $_SESSION['user_dept']  = $teacher['department_id'];
        $_SESSION['user_photo'] = $teacher['profile_photo'];
        $_SESSION['dept_name']  = $teacher['dept_name'];
        $pdo->prepare("UPDATE teachers SET last_login=NOW() WHERE id=?")->execute([$teacher['id']]);
        return ['success' => true, 'user' => $teacher];
    }
    return ['error' => 'Invalid credentials.'];
}

function loginHod($pdo, $identifier, $password) {
    $stmt = $pdo->prepare("
        SELECT h.*, d.name as dept_name FROM hods h 
        LEFT JOIN departments d ON h.department_id=d.id 
        WHERE (h.teacher_id=? OR h.email=?) AND h.is_active=1
    ");
    $stmt->execute([$identifier, $identifier]);
    $hod = $stmt->fetch();
    if ($hod && password_verify($password, $hod['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $hod['id'];
        $_SESSION['user_role']  = 'hod';
        $_SESSION['user_name']  = $hod['name'];
        $_SESSION['user_email'] = $hod['email'];
        $_SESSION['user_dept']  = $hod['department_id'];
        $_SESSION['user_photo'] = $hod['profile_photo'];
        $_SESSION['dept_name']  = $hod['dept_name'];
        $pdo->prepare("UPDATE hods SET last_login=NOW() WHERE id=?")->execute([$hod['id']]);
        return ['success' => true, 'user' => $hod];
    }
    return ['error' => 'Invalid credentials.'];
}

// ============================================================
// LOGOUT
// ============================================================
function logoutUser() {
    session_unset();
    session_destroy();
}
