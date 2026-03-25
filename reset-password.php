<?php
session_start();
require 'includes/db.php';
require 'includes/rate_limit.php';

$msg = '';
$msgType = '';
$token = trim($_GET['token'] ?? '');
$resetRow = null;

$pdo->exec("CREATE TABLE IF NOT EXISTS PasswordResets (
    ResetID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    TokenHash CHAR(64) NOT NULL,
    ExpiresAt DATETIME NOT NULL,
    UsedAt DATETIME NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_passwordresets_tokenhash (TokenHash),
    KEY idx_passwordresets_userid (UserID),
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE
)");

if ($token === '') {
    $msg = 'Invalid or missing reset token.';
    $msgType = 'error';
} else {
    $tokenHash = hash('sha256', $token);
    $lookup = $pdo->prepare("SELECT pr.ResetID, pr.UserID, u.Role FROM PasswordResets pr JOIN Users u ON u.UserID = pr.UserID WHERE pr.TokenHash = ? AND pr.UsedAt IS NULL AND pr.ExpiresAt >= NOW() LIMIT 1");
    $lookup->execute([$tokenHash]);
    $resetRow = $lookup->fetch();

    if (!$resetRow) {
        $msg = 'This reset link is invalid or has expired.';
        $msgType = 'error';
    }
}

if ($resetRow && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = rateLimitGetStatus($pdo, 'password_reset', $token !== '' ? $token : 'empty', 900);
    if (!$status['allowed']) {
        $msg = 'Too many reset attempts. Try again in ' . (int)$status['retry_after'] . ' seconds.';
        $msgType = 'error';
    }

    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($msgType !== 'error' && ($newPassword === '' || $confirmPassword === '')) {
        $msg = 'Please complete both password fields.';
        $msgType = 'error';
    } elseif ($msgType !== 'error' && strlen($newPassword) < 6) {
        $msg = 'Password must be at least 6 characters.';
        $msgType = 'error';
    } elseif ($msgType !== 'error' && $newPassword !== $confirmPassword) {
        $msg = 'Passwords do not match.';
        $msgType = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateUser = $pdo->prepare('UPDATE Users SET Password = ? WHERE UserID = ?');
            $updateUser->execute([$hashed, (int)$resetRow['UserID']]);

            $markUsed = $pdo->prepare('UPDATE PasswordResets SET UsedAt = NOW() WHERE ResetID = ?');
            $markUsed->execute([(int)$resetRow['ResetID']]);

            $pdo->commit();
            rateLimitClear($pdo, 'password_reset', $token);

            if (($resetRow['Role'] ?? '') === 'student') {
                header('Location: student/controller/student-login.php?msg=password_reset');
            } else {
                header('Location: staff/controller/staff-login.php?msg=password_reset');
            }
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            rateLimitRegisterFailure($pdo, 'password_reset', $token !== '' ? $token : 'empty', 5, 900);
            $msg = 'Password reset failed. Please try again.';
            $msgType = 'error';
        }
    }

    if ($msgType === 'error' && $msg !== '' && !str_starts_with($msg, 'Too many reset attempts')) {
        rateLimitRegisterFailure($pdo, 'password_reset', $token !== '' ? $token : 'empty', 5, 900);
    }
}
?>

<?php include 'includes/header.php'; ?>
<title>Reset Password | Student Course Hub</title>
<link rel="stylesheet" href="assets/auth.css">

<main>
    <div class="auth-container">
        <h1>Set New Password</h1>

        <?php if ($msg !== ''): ?>
            <div class="auth-message <?= $msgType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($resetRow): ?>
            <form method="POST" action="reset-password.php?token=<?= urlencode($token) ?>" class="auth-form">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-field">
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                        <button type="button" class="password-toggle" data-target="new_password" aria-label="Show password" title="Show password"><span class="icon-svg icon-eye-off" aria-hidden="true"></span></button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
                        <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password" title="Show password"><span class="icon-svg icon-eye-off" aria-hidden="true"></span></button>
                    </div>
                </div>

                <button type="submit" class="auth-button">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="auth-links">
            <p>Student login <a href="student/controller/student-login.php">Log in</a></p>
        </div>
    </div>
</main>

<script src="assets/js/password-toggle.js"></script>
<?php include 'includes/footer.php'; ?>
