<?php
include 'includes/db.php'; // Includes connection file
include 'includes/header.php'; // Includes header file


// Fetch all programmes from the database
$query = $pdo->query("SELECT * FROM Programmes");
$programmes = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Web Course</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<main>
<body>
    <h1>Available Programmes</h1>
    
    <div class="courses-container">
        <?php
        foreach ($programmes as $course):
        ?>
            <div class="card">
                <h3><?php
                echo htmlspecialchars($course['ProgrammeName']);
                ?></h3>
                <p><?php
                echo htmlspecialchars(substr($course['Description'], 0, 100));
                ?></p>
                <button class="details-button">
                    <a href="programme-details.php?id=<?php
                echo $course['ProgrammeID'];
                ?>">View Details</a>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
        </main>
</body>
</html>
