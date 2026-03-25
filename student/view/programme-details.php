<?php
session_start();
require __DIR__ . '/../../includes/db.php'; 

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
JOIN Staff s ON m.ModuleLeaderID = s.StaffID
WHERE pm.ProgrammeID = ?
ORDER BY pm.Year ASC");

$modStmt->execute([$id]);
$modules = $modStmt->fetchAll();

$isStudent = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'student');
$alreadyRegistered = false;

if ($isStudent) {
    $studentEmail = strtolower(trim($_SESSION['email'] ?? ''));
    if ($studentEmail === '') {
        $lookup = $pdo->prepare('SELECT Email FROM Users WHERE UserID = ?');
        $lookup->execute([(int)$_SESSION['user_id']]);
        $studentEmail = strtolower((string)$lookup->fetchColumn());
        $_SESSION['email'] = $studentEmail;
    }

    if ($studentEmail !== '') {
        $check = $pdo->prepare('SELECT COUNT(*) FROM InterestedStudents WHERE ProgrammeID = ? AND LOWER(Email) = ?');
        $check->execute([$id, $studentEmail]);
        $alreadyRegistered = ((int)$check->fetchColumn() > 0);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programme Details | Student Course Hub</title>
    <link rel="stylesheet" href="../../assets/details.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>
    <main>
<div class="course-detail">
    <h1><?php echo htmlspecialchars($course['ProgrammeName']); ?></h1>
    <p><strong>Level: </strong> <?php echo htmlspecialchars($course['LevelName']); ?></p>

    <div class="course-info">
        <p><?php
        echo nl2br(htmlspecialchars($course['Description']));
        ?></p>
        <p><strong>Programme Leader: </strong><?php echo htmlspecialchars($course['LeaderName']);
        ?></p>
    </div>
</div>

<hr>

    <h2>What you will study</h2>
    <div class="module-list">
        <?php 
        $currentYear = 0;
        foreach ($modules as $mod): 
            if ($currentYear != $mod['Year']): 
                $currentYear = $mod['Year'];
                echo "<h3>Year $currentYear</h3>";
            endif;
        ?>
            <p><strong><?php echo htmlspecialchars($mod['ModuleName']); ?></strong> 
               (Led by: <?php echo htmlspecialchars($mod['ModuleLeader']); ?>)</p>
        <?php endforeach; ?>
    </div>

    <hr>

<section id="interest-form">
        <h2>Register Here</h2>
        <?php if ($isStudent): ?>
            <?php if ($alreadyRegistered): ?>
                <p>You are already registered for this programme.</p>
                <a href="student-dashboard.php" class="btn" style="display:inline-block; text-decoration:none;">Go to My Dashboard</a>
            <?php else: ?>
                <p>Register your interest using your student account.</p>
                <form action="register-interest.php" method="POST">
                    <input type="hidden" name="programme_id" value="<?php echo $id; ?>">
                    <button type="submit" class="btn">Register Interest</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <p>Wanna register your interest?</p>
            <a href="../controller/student-login.php" class="btn">Register</a>
        <?php endif; ?>
    </section>
</div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</html>