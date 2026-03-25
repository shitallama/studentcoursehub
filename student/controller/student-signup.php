<?php
session_start();
require __DIR__ . '/../../includes/db.php';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
    header('Location: ../view/student-dashboard.php');
    exit;
}

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '' || $username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Please provide valid name, username and email address.';
        $msg_type = 'error';
    } elseif (strlen($username) < 3) {
        $msg = 'Username must be at least 3 characters.';
        $msg_type = 'error';
    } elseif ($password !== $confirm_password) {
        $msg = 'Passwords do not match.';
        $msg_type = 'error';
    } elseif (strlen($password) < 6) {
        $msg = 'Password must be at least 6 characters.';
        $msg_type = 'error';
    } else {
        try {
            $check = $pdo->prepare('SELECT COUNT(*) FROM Users WHERE LOWER(Email) = LOWER(?) OR LOWER(Username) = LOWER(?)');
            $check->execute([$email, $username]);
            if ((int)$check->fetchColumn() > 0) {
                $msg = 'An account with this username or email already exists.';
                $msg_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO Users (Username, FullName, Email, Password, Role) VALUES (?, ?, ?, ?, 'student')");
                $stmt->execute([$username, $name, $email, $hashed_password]);
                $msg = "Student account created! <a href='student-login.php'>Login here</a>";
                $msg_type = 'success';
            }
        } catch (PDOException $e) {
            $msg = 'Registration failed. Please try again.';
            $msg_type = 'error';
        }
    }
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>
<title>Student Sign Up | Student Course Hub</title>
<link rel="stylesheet" href="../../assets/auth.css">

<main>
    <div class="auth-container">
        <h1>Student Sign Up</h1>

        <?php if ($msg): ?>
            <div class="auth-message <?= $msg_type ?>"><?= $msg ?></div>
        <?php endif; ?>

        <form method="POST" action="student-signup.php" class="auth-form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="student@example.com" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                    <button type="button" class="password-toggle" data-target="password" aria-label="Show password" title="Show password"><span class="icon-svg icon-eye-off" aria-hidden="true"></span></button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>
                    <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password" title="Show password"><span class="icon-svg icon-eye-off" aria-hidden="true"></span></button>
                </div>
            </div>

            <button type="submit" class="auth-button">Create Student Account</button>
        </form>

        <div class="auth-links">
            <p>Already registered? <a href="student-login.php">Login here</a></p>
        </div>
    </div>
</main>

<script src="../../assets/js/password-toggle.js"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
