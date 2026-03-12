<?php
require 'includes/db.php'; 
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT p.*, s.Name as LeaderName, l.LevelName FROM Programmes p
                       JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
                       JOIN Levels l ON p.LevelID = l.LevelID
                       WHERE p.ProgrammeID = ?");
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) {
    echo "<p>Programme not found.</p>";
    exit;
}

$modStmt = $pdo->prepare("SELECT m.ModuleName, pm.Year, s.Name as ModuleLeader
FROM ProgrammeModules pm
JOIN Modules m ON pm.ModuleID = m.ModuleID
JOIN Staffs s ON m.ModuleLeaderID = s.StaffID
WHERE pm.ProgrammeID = ?
ORDER BY pm.Year ASC");
?>

<main>
<div class="course-detail">
    <h1><?php echo htmlspecialchars($course['ProgrammeName']); ?></h1>
    <p><strong>Level:</strong> <?php echo htmlspecialchars($course['LevelName']); ?></p>

    <div class="course-info">
        <p><?php
        echo nl2br(htmlspecialchars($course['Description']));
        ?></p>
        <p><strong>Programme Leader:</strong><?php echo htmlspecialchars($course['LeaderName']);
        ?></p>
    </div>
</div>
<hr>
<section id="interest-form">
        <h2>Register</h2>
        <p>Become a Student Today. Leave your details below.</p>
        
        <form action="register-interest.php" method="POST">
            <input type="hidden" name="programme_id" value="<?php echo $id; ?>">
            
            <label for="name">Full Name:</label><br>
            <input type="text" id="name" name="student_name" placeholder="Enter your full name here" required><br>
            
            <label for="email">Email Address:</label><br>
            <input type="email" id="email" name="student_email" placeholder="V2s6I@example.com" required><br>
            
            <button type="submit" class="btn">Submit</button>
        </form>
    </section>
</div>
</main>

<?php include 'includes/footer.php'; ?>
