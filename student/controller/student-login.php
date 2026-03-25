<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/rate_limit.php';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
    header('Location: ../view/student-dashboard.php');
    exit;
}

$error = '';
$success = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'password_reset') {
    $success = 'Password reset successful. Please sign in with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $status = rateLimitGetStatus($pdo, 'student_login', $email !== '' ? $email : 'empty', 900);
    if (!$status['allowed']) {
        $error = 'Too many attempts. Try again in ' . (int)$status['retry_after'] . ' seconds.';
    }

    if ($error === '') {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = ? AND Role = 'student'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password'])) {
            rateLimitClear($pdo, 'student_login', $email);
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['email'] = $user['Email'];
            $_SESSION['role'] = 'student';

            header('Location: ../view/student-dashboard.php');
            exit;
        }

        rateLimitRegisterFailure($pdo, 'student_login', $email !== '' ? $email : 'empty', 5, 900);
        $error = 'Invalid email or password.';
    }
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>
<title>Student Login | Student Course Hub</title>
<link rel="stylesheet" href="../../assets/auth.css">

<main>
    <div class="auth-container">
        <h1>Student Login</h1>
        <?php if ($success): ?>
            <div class="auth-message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="auth-message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="student-login.php" class="auth-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="student@example.com" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" data-target="password" aria-label="Show password" title="Show password"><span class="icon-svg icon-eye-off" aria-hidden="true"></span></button>
                </div>
            </div>

            <button type="submit" class="auth-button">Sign In</button>
        </form>

        <div class="auth-links">
            <p>New student? <a href="student-signup.php">Create account</a></p>
            <p>Forgot password? <a href="../../forgot-password.php?mode=student">Reset password</a></p>
        </div>
    </div>
</main>

<script src="../../assets/js/password-toggle.js"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
