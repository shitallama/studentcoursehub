<?php
require 'includes/db.php';
include 'includes/header.php';

echo '<link rel="stylesheet" href="assets/auth.css">';

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $requested_role = $_POST['role'];
    $secret_key = trim($_POST['secret_key'] ?? '');
    
    // Default role is staff unless role key validation fails.
    $final_role = 'staff';
    $can_register = true;

    if ($requested_role === 'admin') {
        if ($secret_key === 'HUB2026') {
            $final_role = 'admin';
        } else {
            $msg = "Incorrect Admin Secret Key.";
            $msg_type = 'error';
            $can_register = false;
        }
    } elseif ($requested_role === 'staff') {
        if ($secret_key !== 'STAFF2026') {
            $msg = "Incorrect Staff Secret Key.";
            $msg_type = 'error';
            $can_register = false;
        }
    } else {
        $msg = "Invalid account type selected.";
        $msg_type = 'error';
        $can_register = false;
    }

    if ($can_register) {
        if ($password !== $confirm_password) {
            $msg = "Passwords do not match.";
            $msg_type = 'error';
        } elseif (strlen($password) < 6) {
            $msg = "Password must be at least 6 characters.";
            $msg_type = 'error';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                $check = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE Username = ? OR Email = ?");
                $check->execute([$username, $email]);
                if ($check->fetchColumn() > 0) {
                    $msg = "Username or Email already exists.";
                    $msg_type = 'error';
                } else {
                    // Insert with the dynamically determined role
                    $stmt = $pdo->prepare("INSERT INTO Users (Username, Email, Password, Role) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$username, $email, $hashed_password, $final_role])) {
                        $msg = ucfirst($final_role) . " account created! <a href='staff-login.php'>Login here</a>";
                        $msg_type = 'success';
                    }
                }
            } catch (PDOException $e) {
                $msg = "Registration failed. Please try again.";
                $msg_type = 'error';
            }
        }
    }
}
?>

<main>
    <div class="auth-container">
        <h1>Registration</h1>
        <?php if($msg): ?>
            <div class="auth-message <?= $msg_type ?>"><?= $msg ?></div>
        <?php endif; ?>
        
        <form method="POST" action="staff-signup.php" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="role">Account Type</label>
                <select id="role" name="role" required onchange="toggleSecretKeyMeta()">
                    <option value="staff">Staff / Teacher</option>
                    <option value="admin">System Administrator</option>
                </select>
            </div>

            <div class="form-group" id="secret-key-group">
                <label for="secret_key" id="secret-key-label">Staff Secret Key</label>
                <input type="password" id="secret_key" name="secret_key" placeholder="Enter key for Staff access" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="password-toggle" data-target="password">🙈</button>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <button type="button" class="password-toggle" data-target="confirm_password">🙈</button>
                </div>
            </div>
            
            <button type="submit" class="auth-button">Create Account</button>
        </form>
    </div>
</main>

<script>
function toggleSecretKeyMeta() {
    const role = document.getElementById('role').value;
    const label = document.getElementById('secret-key-label');
    const input = document.getElementById('secret_key');

    if (role === 'admin') {
        label.textContent = 'Admin Secret Key';
        input.placeholder = 'Enter key for Admin access';
    } else {
        label.textContent = 'Staff Secret Key';
        input.placeholder = 'Enter key for Staff access';
    }
}

toggleSecretKeyMeta();

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