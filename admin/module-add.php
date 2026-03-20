<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php'; // Load the gatekeeper
protectPage(['admin']);               // ONLY 'admin' can enter

if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff-login.php");
    exit;
}

// Get staff for dropdown
$staff = $pdo->query("SELECT * FROM Staff ORDER BY Name")->fetchAll();
$programmes = $pdo->query("SELECT * FROM Programmes ORDER BY ProgrammeName")->fetchAll();

// ===== THIS IS THE PART YOU ASKED ABOUT =====
// Check if we're copying from an existing module
$copy_id = isset($_GET['copy']) ? (int)$_GET['copy'] : 0;
$copy_data = null;

if ($copy_id) {
    // Get the existing module's data
    $stmt = $pdo->prepare("SELECT * FROM Modules WHERE ModuleID = ?");
    $stmt->execute([$copy_id]);
    $copy_data = $stmt->fetch();
}
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $leader_id = $_POST['leader_id'];
    $programme_id = $_POST['programme_id'] ?? null;
    $year = $_POST['year'] ?? 1;
    
    // Start transaction
    $pdo->beginTransaction();
    try {
        // ModuleID is not AUTO_INCREMENT in this schema, so assign the next ID.
        $nextModuleId = (int) $pdo->query("SELECT COALESCE(MAX(ModuleID), 0) + 1 FROM Modules")->fetchColumn();

        // Insert module
        $stmt = $pdo->prepare("INSERT INTO Modules (ModuleID, ModuleName, Description, ModuleLeaderID) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nextModuleId, $name, $description, $leader_id]);
        $module_id = $nextModuleId;
        
        // Link to programme if selected
        if ($programme_id) {
            $stmt = $pdo->prepare("INSERT INTO ProgrammeModules (ProgrammeID, ModuleID, Year) VALUES (?, ?, ?)");
            $stmt->execute([$programme_id, $module_id, $year]);
        }
        
        $pdo->commit();
        header("Location: modules.php?msg=added");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error creating module: " . htmlspecialchars($e->getMessage());
    }
}

include '../includes/header.php';

// Add admin.css
echo '<link rel="stylesheet" href="../assets/admin.css">';
?>

<div class="admin-wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1><?= $copy_data ? 'Duplicate Module' : 'Add New Module' ?></h1>
            <div class="admin-actions">
                <a href="modules.php" class="admin-btn admin-btn-view">
                    <span class="nav-icon">←</span> Back to Modules
                </a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="auth-message error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($copy_data): ?>
            <div class="auth-message success" style="margin-bottom: 1rem;">
                ⓘ You are duplicating: <strong><?= htmlspecialchars($copy_data['ModuleName']) ?></strong>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-body">
                <form method="POST" class="admin-form">
                    <div class="admin-form-group">
                        <label for="name">Module Name</label>
                        <input type="text" id="name" name="name" 
                               value="<?= $copy_data ? htmlspecialchars($copy_data['ModuleName']) : '' ?>" 
                               placeholder="e.g., Introduction to Programming" required>
                    </div>
                    
                    <div class="admin-form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" 
                                  placeholder="Describe what this module covers..." required><?= $copy_data ? htmlspecialchars($copy_data['Description']) : '' ?></textarea>
                    </div>
                    
                    <div class="admin-form-group">
                        <label for="leader_id">Module Leader</label>
                        <select id="leader_id" name="leader_id" required>
                            <option value="">Select Module Leader</option>
                            <?php foreach ($staff as $s): ?>
                            <option value="<?= $s['StaffID'] ?>" 
                                <?= ($copy_data && $copy_data['ModuleLeaderID'] == $s['StaffID']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['Name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="admin-form-group">
                        <label for="programme_id">Link to Programme (Optional)</label>
                        <select id="programme_id" name="programme_id">
                            <option value="">-- Not linked --</option>
                            <?php foreach ($programmes as $p): ?>
                            <option value="<?= $p['ProgrammeID'] ?>"><?= htmlspecialchars($p['ProgrammeName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="admin-form-group">
                        <label for="year">Year (if linked)</label>
                        <select id="year" name="year">
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                        </select>
                    </div>
                    
                    <div class="admin-form-actions">
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <span class="nav-icon">➕</span> <?= $copy_data ? 'Duplicate Module' : 'Create Module' ?>
                        </button>
                        <a href="modules.php" class="admin-btn admin-btn-view">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>


