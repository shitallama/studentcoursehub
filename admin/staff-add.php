<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php'; // Load the gatekeeper
protectPage(['admin']);               // ONLY 'admin' can enter

if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff-login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    
    if (!empty($name)) {
        // StaffID is not AUTO_INCREMENT in this schema, so assign the next ID.
        $nextId = (int) $pdo->query("SELECT COALESCE(MAX(StaffID), 0) + 1 FROM Staff")->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO Staff (StaffID, Name) VALUES (?, ?)");
        if ($stmt->execute([$nextId, $name])) {
            header("Location: staff.php?msg=added");
            exit;
        }
    }
}

include '../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../assets/admin.css">';
?>


<div class="admin-wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Add New Staff</h1>
            <div class="admin-actions">
                <a href="staff.php" class="admin-btn admin-btn-view">← Back to Staff</a>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body">
                <form method="POST" class="admin-form">
                    <div class="admin-form-group">
                        <label for="name">Staff Name</label>
                        <input type="text" id="name" name="name" placeholder="e.g., Dr. John Smith" required>
                    </div>
                    
                    <div class="admin-form-actions">
                        <button type="submit" class="admin-btn admin-btn-primary">➕ Add Staff</button>
                        <a href="staff.php" class="admin-btn admin-btn-view">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>


