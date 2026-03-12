<?php
require 'includes/db.php';
include 'includes/header.php';
?>

<main style="min-height: 59.5vh;">
    <div class="feedback-msg">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $programmeId = $_POST['programme_id'] ?? 0;
            $studentName = trim($_POST['student_name'] ?? '');
            $student_email = trim($_POST['student_email'] ?? '');

            if (empty($studentName) || !filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
                echo "<h1>Invalid Input</h1><p>Please provide a valid name and email.</p>";
            } else {
                try {
                    $checkStmt = $pdo->prepare("SELECT * FROM InterestedStudents WHERE ProgrammeID = ? AND Email = ?");
                    $checkStmt->execute([$programmeId, $student_email]);

                    if ($checkStmt->rowCount() > 0) {
                        echo "<h1>Already Registered</h1>";
                        echo "<p>You have already registered for this programme.</p>";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO InterestedStudents (ProgrammeID, StudentName, Email) VALUES (?, ?, ?)");
                        $stmt->execute([$programmeId, $studentName, $student_email]);
                        echo "<h1>Success!</h1>";
                        echo "<p>Thank you, " . htmlspecialchars($studentName) . ". Interest recorded!</p>";
                    }
                } catch (PDOException $e) {
                    echo "<h1>System Error</h1><p>Could not save your registration.</p>";
                }
            }
        }
        ?>
        
        <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
            <div class="details-button">
                <a href="programme-details.php?id=<?php echo (int)$programmeId; ?>">Back to Course</a>
            </div>
            <div class="details-button">
                <a href="index.php">Home</a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
