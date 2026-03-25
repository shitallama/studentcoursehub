<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    $from = strtolower(trim($_GET['from'] ?? ''));
    if ($from === 'student') {
        header('Location: ../controller/student-login.php?error=not_logged_in');
    } else {
        header('Location: ../../staff/controller/staff-login.php?error=not_logged_in');
    }
    exit;
}

$userId = (int)$_SESSION['user_id'];
$msg = '';
$msgType = '';

$loadUser = $pdo->prepare('SELECT u.UserID, u.Username, u.FullName, u.Email, u.Password, u.Role, u.StaffID, s.Name AS StaffName FROM Users u LEFT JOIN Staff s ON s.StaffID = u.StaffID WHERE u.UserID = ?');
$loadUser->execute([$userId]);
$user = $loadUser->fetch();

if (!$user) {
    session_unset();
    session_destroy();
    header('Location: ../../staff/controller/staff-login.php?error=account_not_found');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $normalizedName = strtolower(preg_replace('/\s+/', ' ', $name));
    $normalizedUsername = strtolower(preg_replace('/\s+/', ' ', $username));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $username === '' || $email === '') {
        $msg = 'Name, username and email are required.';
        $msgType = 'error';
    } elseif ($normalizedName === $normalizedUsername) {
        $msg = 'Name and username must be different.';
        $msgType = 'error';
    } elseif (strlen($username) < 3) {
        $msg = 'Username must be at least 3 characters.';
        $msgType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Please enter a valid email address.';
        $msgType = 'error';
    } elseif (in_array($user['Role'], ['admin', 'staff'], true) && !isUniversityEmail($email)) {
        $msg = 'Staff/Admin email must end with @edu.nielsbrock.dk.';
        $msgType = 'error';
    } else {
        $checkUsername = $pdo->prepare('SELECT COUNT(*) FROM Users WHERE LOWER(Username) = LOWER(?) AND UserID <> ?');
        $checkUsername->execute([$username, $userId]);

        if ((int)$checkUsername->fetchColumn() > 0) {
            $msg = 'Username is already in use. Please choose another username.';
            $msgType = 'error';
        } else {
            $checkEmail = $pdo->prepare('SELECT COUNT(*) FROM Users WHERE LOWER(Email) = LOWER(?) AND UserID <> ?');
            $checkEmail->execute([$email, $userId]);

            if ((int)$checkEmail->fetchColumn() > 0) {
                $msg = 'Email is already in use. Please choose another email.';
                $msgType = 'error';
            } else {
            $shouldUpdatePassword = ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '');

            if ($shouldUpdatePassword) {
                if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                    $msg = 'To change password, fill all password fields.';
                    $msgType = 'error';
                } elseif (!password_verify($currentPassword, $user['Password'])) {
                    $msg = 'Current password is incorrect.';
                    $msgType = 'error';
                } elseif (strlen($newPassword) < 6) {
                    $msg = 'New password must be at least 6 characters.';
                    $msgType = 'error';
                } elseif ($newPassword !== $confirmPassword) {
                    $msg = 'New password and confirmation do not match.';
                    $msgType = 'error';
                }
            }

                if ($msgType !== 'error') {
                    try {
                        $pdo->beginTransaction();

                        if ($shouldUpdatePassword) {
                            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                            $updateUser = $pdo->prepare('UPDATE Users SET Username = ?, FullName = ?, Email = ?, Password = ? WHERE UserID = ?');
                            $updateUser->execute([$username, $name, $email, $hashedPassword, $userId]);
                        } else {
                            $updateUser = $pdo->prepare('UPDATE Users SET Username = ?, FullName = ?, Email = ? WHERE UserID = ?');
                            $updateUser->execute([$username, $name, $email, $userId]);
                        }

                        if ($user['Role'] === 'staff') {
                            $staffId = (int)($user['StaffID'] ?? 0);
                            if ($staffId <= 0) {
                                $staffId = ensureCurrentStaffId($pdo);
                            }

                            if ($staffId > 0) {
                                $updateStaff = $pdo->prepare('UPDATE Staff SET Name = ? WHERE StaffID = ?');
                                $updateStaff->execute([$name, $staffId]);
                            }
                        }

                        $pdo->commit();

                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;

                        $loadUser->execute([$userId]);
                        $user = $loadUser->fetch();

                        $msg = 'Profile updated successfully.';
                        $msgType = 'success';
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $msg = 'Failed to update profile. Please try again.';
                        $msgType = 'error';
                    }
                }
            }
        }
    }
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>
<title>Profile | Student Course Hub</title>
<link rel="stylesheet" href="../../assets/auth.css">

<main>
    <div class="auth-container">
        <h1>My Profile</h1>

        <?php if ($msg !== ''): ?>
            <div class="auth-message <?= $msgType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="POST" action="profile.php" class="auth-form">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars(trim((string)($user['FullName'] ?? '')) !== '' ? (string)$user['FullName'] : ($user['Role'] === 'staff' ? ((string)($user['StaffName'] ?? $user['Username'])) : (string)$user['Username'])) ?>" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['Username']) ?>" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" placeholder="you@example.com" required>
                <?php if (in_array($user['Role'], ['admin', 'staff'], true)): ?>
                <?php endif; ?>
            </div>

            <hr style="border:0; border-top:1px solid #e5e7eb; margin: 1.2rem 0;">
            <p style="margin:0 0 0.85rem; color:#4b5563;">Change password (optional)</p>

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="password-field">
                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password">
                    <button type="button" class="password-toggle" data-target="current_password" aria-label="Show password" title="Show password"><span class="icon-svg icon-eye-off" aria-hidden="true"></span></button>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-field">
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
                    <button type="button" class="password-toggle" data-target="new_password" aria-label="Show password" title="Show password"><span class="icon-svg icon-eye-off" aria-hidden="true"></span></button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
                    <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password" title="Show password"><span class="icon-svg icon-eye-off" aria-hidden="true"></span></button>
                </div>
            </div>

            <button type="submit" class="auth-button">Update Profile</button>
        </form>
    </div>
</main>

<script src="../../assets/js/password-toggle.js"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
