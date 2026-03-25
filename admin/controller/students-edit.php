<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php'; 
protectPage(['admin', 'staff']);      // Both roles can enter
include __DIR__ . '/../../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../staff/controller/staff-login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$return_programme_id = isset($_GET['programme_id']) && ctype_digit($_GET['programme_id']) ? (int)$_GET['programme_id'] : null;

$programmes = $pdo->query("SELECT ProgrammeID, ProgrammeName FROM Programmes ORDER BY ProgrammeName")->fetchAll();

$stmt = $pdo->prepare(
    "SELECT i.*, COALESCE(NULLIF(u.FullName, ''), NULLIF(i.StudentName, ''), NULLIF(u.Username, ''), 'Student') AS DisplayName
     FROM InterestedStudents i
     LEFT JOIN Users u ON LOWER(u.Email) = LOWER(i.Email) AND u.Role = 'student'
     WHERE i.InterestID = ?"
);
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: ../view/students.php");
    exit;
}

$originalEmail = $student['Email'] ?? '';
$student['StudentName'] = $student['DisplayName'] ?? ($student['StudentName'] ?? '');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name']);
    $email = trim($_POST['email']);
    $programme_id = isset($_POST['programme_id']) ? (int)$_POST['programme_id'] : 0;
    $return_programme_id = isset($_POST['return_programme_id']) && ctype_digit($_POST['return_programme_id'])
        ? (int)$_POST['return_programme_id']
        : null;

    if ($student_name === '') {
        $error = 'Full name is required.';
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
                // Keep account profile aligned when this interested student has a linked student account.
                $sync = $pdo->prepare(
                    "UPDATE Users
                     SET FullName = ?
                     WHERE LOWER(Email) IN (LOWER(?), LOWER(?))
                       AND Role = 'student'"
                );
                $sync->execute([$student_name, $email, $originalEmail]);

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

<title>Edit Student | Student Course Hub</title>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Edit Student</h1>
            <div class="admin-actions">
                <a href="../view/students.php<?= $return_programme_id ? '?programme_id=' . (int)$return_programme_id : '' ?>" class="admin-btn admin-btn-view">← Back to Students</a>
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
                        <label for="student_name">Full Name</label>
                        <input type="text" id="student_name" name="student_name" value="<?= htmlspecialchars($student['StudentName']) ?>" placeholder="Enter student full name" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($student['Email']) ?>" placeholder="student@example.com" required>
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
                        <button type="submit" class="admin-btn admin-btn-primary"><span class="nav-icon"><span class="icon-svg icon-save" aria-hidden="true"></span></span> Update Student</button>
                        <a href="../view/students.php<?= $return_programme_id ? '?programme_id=' . (int)$return_programme_id : '' ?>" class="admin-btn admin-btn-view">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>






