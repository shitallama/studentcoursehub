<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php'; 
protectPage(['admin', 'staff']);      // Both roles can enter
include '../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff-login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$return_programme_id = isset($_GET['programme_id']) && ctype_digit($_GET['programme_id']) ? (int)$_GET['programme_id'] : null;

$programmes = $pdo->query("SELECT ProgrammeID, ProgrammeName FROM Programmes ORDER BY ProgrammeName")->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM InterestedStudents WHERE InterestID = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: students.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name']);
    $email = trim($_POST['email']);
    $programme_id = isset($_POST['programme_id']) ? (int)$_POST['programme_id'] : 0;
    $return_programme_id = isset($_POST['return_programme_id']) && ctype_digit($_POST['return_programme_id'])
        ? (int)$_POST['return_programme_id']
        : null;

    if ($student_name === '') {
        $error = 'Student name is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $programme_check = $pdo->prepare("SELECT COUNT(*) FROM Programmes WHERE ProgrammeID = ?");
        $programme_check->execute([$programme_id]);

        if ((int)$programme_check->fetchColumn() === 0) {
            $error = 'Please choose a valid programme.';
        } else {
            $update = $pdo->prepare("UPDATE InterestedStudents SET StudentName = ?, Email = ?, ProgrammeID = ? WHERE InterestID = ?");
            if ($update->execute([$student_name, $email, $programme_id, $id])) {
                $redirect = 'students.php?msg=updated';
                if ($return_programme_id) {
                    $redirect .= '&programme_id=' . $return_programme_id;
                }
                header('Location: ' . $redirect);
                exit;
            }
            $error = 'Update failed. Please try again.';
        }
    }

    $student['StudentName'] = $student_name;
    $student['Email'] = $email;
    $student['ProgrammeID'] = $programme_id;
}
?>


<div class="admin-wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Edit Student</h1>
            <div class="admin-actions">
                <a href="students.php<?= $return_programme_id ? '?programme_id=' . (int)$return_programme_id : '' ?>" class="admin-btn admin-btn-view">← Back to Students</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="auth-message error" style="margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-body">
                <form method="POST" class="admin-form">
                    <input type="hidden" name="return_programme_id" value="<?= $return_programme_id ? (int)$return_programme_id : '' ?>">

                    <div class="admin-form-group">
                        <label for="student_name">Student Name</label>
                        <input type="text" id="student_name" name="student_name" value="<?= htmlspecialchars($student['StudentName']) ?>" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($student['Email']) ?>" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="programme_id">Programme</label>
                        <select id="programme_id" name="programme_id" required>
                            <option value="">Select Programme</option>
                            <?php foreach ($programmes as $programme): ?>
                                <option value="<?= $programme['ProgrammeID'] ?>" <?= (int)$student['ProgrammeID'] === (int)$programme['ProgrammeID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($programme['ProgrammeName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="admin-form-actions">
                        <button type="submit" class="admin-btn admin-btn-primary">💾 Update Student</button>
                        <a href="students.php<?= $return_programme_id ? '?programme_id=' . (int)$return_programme_id : '' ?>" class="admin-btn admin-btn-view">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>


