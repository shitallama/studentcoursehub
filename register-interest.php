<?php
require 'includes/db.php';
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programmeId = $_POST['programme_id'];
    $studentName = trim($_POST['student_name']);
    $student_email = trim($_POST['student_email']);

if (empty($studentName) || !filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
        echo "<p class='error'>Please fill in all fields with valid information.</p>";
    } else {
        try {
        $checkSql = "SELECT * FROM InterestedStudents WHERE ProgrammeID = ? AND Email = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$programmeId, $student_email]);

        if ($checkStmt->rowCount() > 0) {
            echo "<h2>You have already registered for this programme.</h2>";
        } else {
            $sql = "INSERT INTO InterestedStudents (ProgrammeID, StudentName, Email) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$programmeId, $studentName, $student_email]);

            echo "<h2>Thank you, " . htmlspecialchars($studentName) . "for registering your interest!</h2>";
        }
        echo '<br><a href="index.php" class="back-btn">Back to Home</a>';
    } catch (PDOException $e) {
        echo "<p>Sorry, There was an error with your request: " . $e->getMessage() . "</p>";
    }
} else {
    header("Location: index.php");
    exit();
}
}

include 'includes/footer.php';
?>
