<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php'; // Load the gatekeeper
protectPage(['admin']);               // ONLY 'admin' can enter
include '../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff-login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch staff data
$stmt = $pdo->prepare("SELECT * FROM Staff WHERE StaffID = ?");
$stmt->execute([$id]);
$staff = $stmt->fetch();

if (!$staff) {
    header("Location: staff.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE Staff SET Name = ? WHERE StaffID = ?");
        if ($stmt->execute([$name, $id])) {
            header("Location: staff.php?msg=updated");
            exit;
        }
    }
}
?>


<div class="admin-wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Edit Staff</h1>
            <div class="admin-actions">
                <a href="staff.php" class="admin-btn admin-btn-view">← Back to Staff</a>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body">
                <form method="POST" class="admin-form">
                    <div class="admin-form-group">
                        <label for="name">Staff Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($staff['Name']) ?>" required>
                    </div>
                    
                    <div class="admin-form-actions">
                        <button type="submit" class="admin-btn admin-btn-primary">💾 Update Staff</button>
                        <a href="staff.php" class="admin-btn admin-btn-view">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>


