<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php'; // Load the gatekeeper
protectPage(['admin']);               // ONLY 'admin' can enter
include __DIR__ . '/../../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../staff/controller/staff-login.php");
    exit;
}

syncStaffUsersToStaffTable($pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch programme data
$stmt = $pdo->prepare("SELECT * FROM Programmes WHERE ProgrammeID = ?");
$stmt->execute([$id]);
$programme = $stmt->fetch();

if (!$programme) {
    header("Location: ../view/programmes.php");
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
    
    $stmt = $pdo->prepare("UPDATE Programmes SET ProgrammeName = ?, Description = ?, LevelID = ?, ProgrammeLeaderID = ? WHERE ProgrammeID = ?");
    if ($stmt->execute([$name, $description, $level_id, $leader_id, $id])) {
        header("Location: ../view/programmes.php?msg=updated");
        exit;
    }
}
?>

<title>Edit Programme | Student Course Hub</title>


<div class="admin-wrapper">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Edit Programme</h1>
            <div class="admin-actions">
                <a href="../view/programmes.php" class="admin-btn admin-btn-view">← Back to Programmes</a>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body">
                <form method="POST" class="admin-form">
                    <div class="admin-form-group">
                        <label for="name">Programme Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($programme['ProgrammeName']) ?>" placeholder="Enter programme name" required>
                    </div>
                    
                    <div class="admin-form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" required><?= htmlspecialchars($programme['Description']) ?></textarea>
                    </div>
                    
                    <div class="admin-form-group">
                        <label for="level_id">Level</label>
                        <select id="level_id" name="level_id" required>
                            <option value="">Select Level</option>
                            <?php foreach ($levels as $level): ?>
                            <option value="<?= $level['LevelID'] ?>" <?= $level['LevelID'] == $programme['LevelID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($level['LevelName']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="admin-form-group">
                        <label for="leader_id">Programme Leader</label>
                        <select id="leader_id" name="leader_id" required>
                            <option value="">Select Staff</option>
                            <?php foreach ($staff as $s): ?>
                            <option value="<?= $s['StaffID'] ?>" <?= $s['StaffID'] == $programme['ProgrammeLeaderID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['Name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="admin-form-actions">
                        <button type="submit" class="admin-btn admin-btn-primary">Update Programme</button>
                        <a href="../view/programmes.php" class="admin-btn admin-btn-view">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>






