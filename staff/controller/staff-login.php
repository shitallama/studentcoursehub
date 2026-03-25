<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php';
require __DIR__ . '/../../includes/rate_limit.php';
$error = '';
$success = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'password_reset') {
    $success = 'Password reset successful. Please sign in with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? $_POST['email'] ?? '');
    $normalizedIdentifier = strtolower($identifier);
    $password = $_POST['password'] ?? '';
    $user = null;

    $status = rateLimitGetStatus($pdo, 'staff_login', $normalizedIdentifier !== '' ? $normalizedIdentifier : 'empty', 900);
    if (!$status['allowed']) {
        $error = 'Too many attempts. Try again in ' . (int)$status['retry_after'] . ' seconds.';
    }

    if ($error === '' && str_contains($normalizedIdentifier, '@') && !isUniversityEmail($normalizedIdentifier)) {
        $error = 'Use your institutional email ending with @edu.nielsbrock.dk.';
    } elseif ($error === '') {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE (LOWER(Email) = ? OR LOWER(Username) = ?) AND Role IN ('admin', 'staff')");
        $stmt->execute([$normalizedIdentifier, $normalizedIdentifier]);
        $user = $stmt->fetch();
    }

    if ($user && password_verify($password, $user['Password'])) {
        rateLimitClear($pdo, 'staff_login', $normalizedIdentifier);
        // Store user data in session
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['email'] = $user['Email'];
        $_SESSION['role'] = $user['Role']; // Fetches 'admin' or 'staff'

        // Branching Redirect Logic
        if ($_SESSION['role'] === 'admin') {
            header("Location: ../../admin/view/dashboard.php");
        } else {
            header("Location: ../view/staff-dashboard.php");
        }
        exit; 
    } elseif ($error === '') {
        rateLimitRegisterFailure($pdo, 'staff_login', $normalizedIdentifier !== '' ? $normalizedIdentifier : 'empty', 5, 900);
        $error = 'Invalid email or password.';
    }
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>
<title>Staff & Admin Login | Student Course Hub</title>
<link rel="stylesheet" href="../../assets/auth.css">

<main>
    <div class="auth-container">
        <h1>Staff & Admin Login</h1>
        <?php if($success): ?>
            <div class="auth-message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="auth-message error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="staff-login.php" class="auth-form">
            <div class="form-group">
                <label for="identifier">University Email or Username</label>
                <input type="text" id="identifier" name="identifier" placeholder="name@edu.nielsbrock.dk" required>
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
            <p>Forgot password? <a href="../../forgot-password.php?mode=staff">Reset password</a></p>
            <p>Need an account? <a href="mailto:59154@edu.nielsbrock.dk">Contact Administration</a></p>
        </div>
    </div>
</main>
<script src="../../assets/js/password-toggle.js"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>