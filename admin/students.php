<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php'; 
protectPage(['admin', 'staff']);      // Both roles can enter

if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff-login.php");
    exit;
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $delete_stmt = $pdo->prepare("DELETE FROM InterestedStudents WHERE InterestID = ?");
    if ($delete_stmt->execute([$id])) {
        $redirect = "students.php?msg=deleted";
        if (isset($_GET['programme_id']) && ctype_digit($_GET['programme_id'])) {
            $redirect .= "&programme_id=" . (int)$_GET['programme_id'];
        }
        header("Location: " . $redirect);
        exit;
    }
    $error = "Failed to delete student record.";
}

// Filter by programme if specified
$programme_filter = isset($_GET['programme_id']) ? (int)$_GET['programme_id'] : null;

if ($programme_filter) {
    $stmt = $pdo->prepare("
        SELECT i.*, p.ProgrammeName 
        FROM InterestedStudents i
        JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
        WHERE i.ProgrammeID = ?
        ORDER BY i.RegisteredAt DESC
    ");
    $stmt->execute([$programme_filter]);
    $students = $stmt->fetchAll();
    
    // Get programme name for header
    $prog = $pdo->prepare("SELECT ProgrammeName FROM Programmes WHERE ProgrammeID = ?");
    $prog->execute([$programme_filter]);
    $programme_name = $prog->fetchColumn();
} else {
    $students = $pdo->query("
        SELECT i.*, p.ProgrammeName 
        FROM InterestedStudents i
        JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
        ORDER BY i.RegisteredAt DESC
    ")->fetchAll();
}

// Get all programmes for filter dropdown
$programmes = $pdo->query("SELECT ProgrammeID, ProgrammeName FROM Programmes ORDER BY ProgrammeName")->fetchAll();

include '../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../assets/admin.css">';
?>


<div class="admin-wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1><?= $programme_filter ? "Students for: " . htmlspecialchars($programme_name) : "Interested Students" ?></h1>
            <div class="admin-actions">
                <form method="GET" style="display: flex; gap: 0.5rem;">
                    <select name="programme_id" onchange="this.form.submit()" style="padding: 0.5rem; border-radius: 6px; border: 1px solid var(--warm-gray-300);">
                        <option value="">All Programmes</option>
                        <?php foreach ($programmes as $p): ?>
                        <option value="<?= $p['ProgrammeID'] ?>" <?= $programme_filter == $p['ProgrammeID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['ProgrammeName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($programme_filter): ?>
                    <a href="students.php" class="admin-btn admin-btn-view">Clear Filter</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="auth-message error" style="margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
            <div class="auth-message success" style="margin-bottom: 1rem;">
                <?php
                if ($_GET['msg'] === 'updated') echo "Student details updated successfully!";
                if ($_GET['msg'] === 'deleted') echo "Student record deleted successfully!";
                ?>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-header">
                <h2>Student Registrations</h2>
                <span class="admin-badge badge-success">Total: <?= count($students) ?></span>
            </div>
            <div class="admin-card-body">
                <?php if ($students): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Programme</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                        <tr>
                            <td>#<?= $s['InterestID'] ?></td>
                            <td><strong><?= htmlspecialchars($s['StudentName']) ?></strong></td>
                            <td><a href="mailto:<?= htmlspecialchars($s['Email']) ?>"><?= htmlspecialchars($s['Email']) ?></a></td>
                            <td><?= htmlspecialchars($s['ProgrammeName']) ?></td>
                            <td><?= date('d M Y', strtotime($s['RegisteredAt'])) ?></td>
                            <td class="admin-table-actions">
                                <a href="students-edit.php?id=<?= $s['InterestID'] ?><?= $programme_filter ? '&programme_id=' . (int)$programme_filter : '' ?>" class="admin-btn admin-btn-edit admin-btn-sm">✏️ Edit</a>
                                <a href="students.php?delete=<?= $s['InterestID'] ?><?= $programme_filter ? '&programme_id=' . (int)$programme_filter : '' ?>"
                                   class="admin-btn admin-btn-delete admin-btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this student record?')">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; padding: 2rem; color: var(--warm-gray-500);">No students registered yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>


