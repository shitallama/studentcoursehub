<?php
include 'includes/db.php'; // Includes connection file

// Read filters from query string (GET)
$q = trim($_GET['q'] ?? '');
$level = strtolower(trim($_GET['level'] ?? 'all')); // all | undergraduate | postgraduate

// Build SQL with optional filters (safe)
$sql = "SELECT p.*
        FROM Programmes p
        JOIN Levels l ON p.LevelID = l.LevelID
        WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND p.ProgrammeName LIKE :q";
    $params[':q'] = '%' . $q . '%';
}

if ($level !== '' && $level !== 'all') {
    // In DB Levels are 'Undergraduate'/'Postgraduate'
    $sql .= " AND LOWER(l.LevelName) = :level";
    $params[':level'] = $level;
}

$sql .= " ORDER BY p.ProgrammeName ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programmes | Student Course Hub</title>
    <link rel="stylesheet" href="assets/home.css">
</head>
<body>
    <?php include 'includes/header.php'; // Includes header file ?>
    <main class="programmes-page">
    <h1>Available Programmes</h1>

    <form class="filter" method="GET" action="home.php">
        <select id="levelFilter" name="level">
            <option value="all" <?= ($level === 'all' ? 'selected' : '') ?>>All</option>
            <option value="undergraduate" <?= ($level === 'undergraduate' ? 'selected' : '') ?>>Undergraduate</option>
            <option value="postgraduate" <?= ($level === 'postgraduate' ? 'selected' : '') ?>>Postgraduate</option>
        </select>

        <input
            type="text"
            id="searchBar"
            name="q"
            placeholder="Search by programme name..."
            value="<?= htmlspecialchars($q) ?>"
        >

        <button type="submit" class="apply-button">Apply</button>
        <a href="home.php" class="reset-link">Reset</a>
    </form>

    <div class="courses-container programmes-grid">
        <?php foreach ($programmes as $course): ?>
            <a class="card card-link" href="student/view/programme-details.php?id=<?= (int)$course['ProgrammeID'] ?>">
                <h3><?= htmlspecialchars($course['ProgrammeName']) ?></h3>
                <p><?= htmlspecialchars(substr($course['Description'], 0, 100)) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</main>
<?php
include 'includes/footer.php'; // Includes footer file
?>
</html>