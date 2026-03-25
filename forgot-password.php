<?php
session_start();
require 'includes/db.php';
require 'includes/auth_check.php';
require 'includes/rate_limit.php';

$mode = strtolower(trim($_GET['mode'] ?? ''));
if (!in_array($mode, ['student', 'staff'], true)) {
    $mode = 'student';
}

$msg = '';
$msgType = '';
$resetLink = '';

// Backward-compatible table creation for existing databases.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? $_POST['email'] ?? '');
    $normalized = strtolower($identifier);

    $bucket = $mode === 'student' ? 'student_forgot' : 'staff_forgot';
    $status = rateLimitGetStatus($pdo, $bucket, $normalized !== '' ? $normalized : 'empty', 900);
    if (!$status['allowed']) {
        $msg = 'Too many reset requests. Try again in ' . (int)$status['retry_after'] . ' seconds.';
        $msgType = 'error';
    }

    if ($msgType !== 'error' && $normalized === '') {
        $msg = 'Please enter your account identifier.';
        $msgType = 'error';
    } elseif ($msgType !== 'error') {
        if ($mode === 'student') {
            $find = $pdo->prepare("SELECT UserID, Role FROM Users WHERE LOWER(Email) = ? AND Role = 'student' LIMIT 1");
            $find->execute([$normalized]);
        } else {
            $find = $pdo->prepare("SELECT UserID, Role FROM Users WHERE LOWER(Email) = ? AND Role IN ('staff','admin') LIMIT 1");
            $find->execute([$normalized]);
        }

        $user = $find->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 1800); // 30 minutes

            $insert = $pdo->prepare('INSERT INTO PasswordResets (UserID, TokenHash, ExpiresAt) VALUES (?, ?, ?)');
            $insert->execute([(int)$user['UserID'], $tokenHash, $expiresAt]);

            $resetLink = 'reset-password.php?token=' . urlencode($token);
        }

        rateLimitRegisterFailure($pdo, $bucket, $normalized !== '' ? $normalized : 'empty', 5, 900);

        // Generic success response; shows local reset link only when account exists.
        $msg = 'If the account exists, a reset link has been generated.';
        $msgType = 'success';
    }
}
?>

<?php include 'includes/header.php'; ?>
<title>Forgot Password | Student Course Hub</title>
<link rel="stylesheet" href="assets/auth.css">

<main>
    <div class="auth-container">
        <h1><?= $mode === 'student' ? 'Student Password Reset' : 'Staff/Admin Password Reset' ?></h1>

        <?php if ($msg !== ''): ?>
            <div class="auth-message <?= $msgType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($resetLink !== ''): ?>
            <div class="auth-message success" style="text-align:left;">
                <strong>Reset Link:</strong><br>
                <a href="<?= htmlspecialchars($resetLink) ?>"><?= htmlspecialchars($resetLink) ?></a>
            </div>
        <?php endif; ?>

        <form method="POST" action="forgot-password.php?mode=<?= $mode ?>" class="auth-form">
            <div class="form-group">
                <?php if ($mode === 'student'): ?>
                    <label for="identifier">Student Email</label>
                    <input type="email" id="identifier" name="identifier" placeholder="student@example.com" required>
                <?php else: ?>
                    <label for="identifier">Staff/Admin Email</label>
                    <input type="email" id="identifier" name="identifier" placeholder="staff@example.com" required>
                <?php endif; ?>
            </div>

            <button type="submit" class="auth-button">Generate Reset Link</button>
        </form>

        <div class="auth-links">
            <?php if ($mode === 'student'): ?>
                <p>Back to login <a href="student/controller/student-login.php">Student Login</a></p>
            <?php else: ?>
                <p>Back to login <a href="staff/controller/staff-login.php">Staff &amp; Admin Login</a></p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
