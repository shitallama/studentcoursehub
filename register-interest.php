<?php
require 'includes/db.php';
include 'includes/header.php';

echo '<main>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programmeId = $_POST['programme_id'] ?? 0;
    $studentName = trim($_POST['student_name'] ?? '');
    $student_email = trim($_POST['student_email'] ?? '');

    if (empty($studentName) || !filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='feedback-msg'>";
        echo "<h1>Invalid Input</h1>";
        echo "<p>Please fill in all fields with valid information.</p>";
        echo "</div>";
    } else {
        try {
            $checkSql = "SELECT * FROM InterestedStudents WHERE ProgrammeID = ? AND Email = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$programmeId, $student_email]);

            echo "<div class='feedback-msg'>";
            
            if ($checkStmt->rowCount() > 0) {
                echo "<h1>Already Registered</h1>";
                echo "<p>You have already registered your interest for this programme using <strong>" . htmlspecialchars($student_email) . "</strong>.</p>";
            } else {
                $sql = "INSERT INTO InterestedStudents (ProgrammeID, StudentName, Email) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$programmeId, $studentName, $student_email]);

                echo "<h1>Success!</h1>";
                echo "<p>Thank you, <strong>" . htmlspecialchars($studentName) . "</strong>. Your interest has been recorded.</p>";
            }
            
            echo '<div style="display: flex; gap: 15px; justify-content: center; margin-top: 20px;">';
            echo '<div class="details-button">
                    <a href="programme-details.php?id=' . (int)$programmeId . '">View Programme</a>
                  </div>';
            
            echo '<div class="details-button">
                    <a href="index.php">Back to Home</a>
                  </div>';
                  
            echo '</div>';
            echo '</div>';

        } catch (PDOException $e) {
            echo "<div class='feedback-msg'>";
            echo "<h1>System Error</h1>";
            echo "<p>We couldn't process your request right now. Please try again later.</p>";
            echo "</div>";
        }
    }
} else {
    header("Location: index.php");
    exit();
}

echo '</main>';

include 'includes/footer.php';
?>
