<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php';

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin') {
        header('Location: ../../admin/view/dashboard.php');
    } elseif ($role === 'staff') {
        header('Location: ../view/staff-dashboard.php');
    } else {
        header('Location: ../../student/view/student-dashboard.php');
    }
    exit;
}

include __DIR__ . '/../../includes/header.php';

echo '<link rel="stylesheet" href="../../assets/auth.css">';
?>

<title>Staff Sign Up | Student Course Hub</title>

<main>
    <div class="auth-container">
        <h1>Staff Registration Closed</h1>
        <div class="auth-message error" style="margin-bottom: 1rem;">
            Staff accounts are created only by SuperAdmin.
        </div>
        <p style="margin-top: 0;">Please contact SuperAdmin to receive your staff login ID.</p>
        <div class="auth-links">
            <p><a href="staff-login.php">Back to Staff &amp; Admin Login</a></p>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>