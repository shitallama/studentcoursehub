<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php';
protectPage(['admin']); // Restricted to Admin only
include '../includes/header.php';
echo '<link rel="stylesheet" href="../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff-login.php");
    exit;
}

// Handle User Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $target_id = (int)$_POST['user_id'];
    $current_user_id = (int)$_SESSION['user_id'];

    if ($target_id === $current_user_id) {
        $error = "You cannot delete your own account while logged in.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM Users WHERE UserID = ?");
            $stmt->execute([$target_id]);
            $msg = "User deleted successfully.";
        } catch (PDOException $e) {
            $error = "Unable to delete this user. Remove related records first.";
        }
    }
}

// Handle Role Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $target_id = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'] ?? '';
    $current_user_id = (int)$_SESSION['user_id'];

    if (!in_array($new_role, ['admin', 'staff'], true)) {
        $error = "Invalid role selected.";
    } elseif ($target_id === $current_user_id && $new_role === 'staff') {
        $error = "You cannot change yourself to staff";
    } else {
        $stmt = $pdo->prepare("UPDATE Users SET Role = ? WHERE UserID = ?");
        $stmt->execute([$new_role, $target_id]);
        $msg = "User role updated to " . htmlspecialchars($new_role) . "!";
    }
}

$users = $pdo->query("SELECT UserID, Username, Email, Role FROM Users")->fetchAll();
?>

<div class="admin-wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>System Permissions</h1>
        </div>

        <?php if(isset($msg)): ?>
            <div class="auth-message success"><?= $msg ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="auth-message" style="background:#fee2e2; color:#991b1b; margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-body">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Current Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['Username']) ?></td>
                            <td><?= htmlspecialchars($u['Email']) ?></td>
                            <td>
                                <span class="admin-badge <?= $u['Role'] === 'admin' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= strtoupper($u['Role']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                                    <input type="hidden" name="user_id" value="<?= $u['UserID'] ?>">
                                    <select name="new_role" class="admin-form-group" style="padding:5px; margin:0;">
                                        <option value="staff" <?= $u['Role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                        <option value="admin" <?= $u['Role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="update_role" class="admin-btn admin-btn-sm admin-btn-edit">🧑‍💻 Update</button>
                                    <button
                                        type="submit"
                                        name="delete_user"
                                        class="admin-btn admin-btn-sm admin-btn-delete"
                                        <?= ((int)$u['UserID'] === (int)$_SESSION['user_id']) ? 'disabled title="You cannot delete your own account" style="opacity:0.55; cursor:not-allowed;"' : 'onclick="return confirm(\'Delete this user account? This cannot be undone.\');"' ?>
                                    >
                                        🗑️ Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>


