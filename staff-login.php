<?php
session_start();
require 'includes/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM Users WHERE Username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['Password'])) {
        // Store user data in session
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['Role']; // Fetches 'admin' or 'staff'

        // Branching Redirect Logic
        if ($_SESSION['role'] === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: admin/staff-dashboard.php");
        }
        exit; 
    } else {
        $error = 'Invalid username or password.';
    }
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/auth.css">

<main>
    <div class="auth-container">
        <h1>Staff & Admin Login</h1>
        <?php if($error): ?>
            <div class="auth-message error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="staff-login.php" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="password-toggle" data-target="password">🙈</button>
                </div>
            </div>
            <button type="submit" class="auth-button">Sign In</button>
        </form>
        <div class="auth-links">
            <p>Need an account? <a href="staff-signup.php">Sign up here</a></p>
        </div>
    </div>
</main>
<script>
document.querySelectorAll('.password-toggle').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
        var input = document.getElementById(this.getAttribute('data-target'));
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        this.textContent = isPassword ? '👀' : '🙈';
    });
});
</script>
<?php include 'includes/footer.php'; ?>