<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php'; // Load the gatekeeper
protectPage(['admin']);               // ONLY 'admin' can enter
include '../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get levels and staff for dropdowns
$levels = $pdo->query("SELECT * FROM Levels ORDER BY LevelName")->fetchAll();
$staff = $pdo->query("SELECT * FROM Staff ORDER BY Name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $level_id = $_POST['level_id'];
    $leader_id = $_POST['leader_id'];
    
    $stmt = $pdo->prepare("INSERT INTO Programmes (ProgrammeName, Description, LevelID, ProgrammeLeaderID) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $description, $level_id, $leader_id])) {
        header("Location: programmes.php?msg=added");
        exit;
    }
}
?>

<div class="admin-wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Add New Programme</h1>
            <div class="admin-actions">
                <a href="programmes.php" class="admin-btn admin-btn-view">← Back to Programmes</a>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body">
                <form method="POST" class="admin-form">
                    <div class="admin-form-group">
                        <label for="name">Programme Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="admin-form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" required></textarea>
                    </div>
                    
                    <div class="admin-form-group">
                        <label for="level_id">Level</label>
                        <select id="level_id" name="level_id" required>
                            <option value="">Select Level</option>
                            <?php foreach ($levels as $level): ?>
                            <option value="<?= $level['LevelID'] ?>"><?= htmlspecialchars($level['LevelName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="admin-form-group">
                        <label for="leader_id">Programme Leader</label>
                        <select id="leader_id" name="leader_id" required>
                            <option value="">Select Staff</option>
                            <?php foreach ($staff as $s): ?>
                            <option value="<?= $s['StaffID'] ?>"><?= htmlspecialchars($s['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="admin-form-actions">
                        <button type="submit" class="admin-btn admin-btn-primary">Create Programme</button>
                        <a href="programmes.php" class="admin-btn admin-btn-view">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>