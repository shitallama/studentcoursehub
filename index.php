<?php
include 'includes/db.php';

$featuredProgrammes = [];

try {
	$stmt = $pdo->query("SELECT ProgrammeID, ProgrammeName, Description FROM Programmes ORDER BY ProgrammeName ASC LIMIT 3");
	$featuredProgrammes = $stmt->fetchAll();
} catch (PDOException $e) {
	$featuredProgrammes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Home | Student Course Hub</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/indexstyle.css">
</head>
<body>
	<?php include 'includes/header.php'; ?>
    <main class="welcome-page">
	<section class="welcome-hero">
		<p class="welcome-eyebrow">Welcome to Student Course Hub</p>
		<h1>Start your learning journey with confidence.</h1>
		<p class="welcome-subtitle">
			Explore career-focused programmes, meet experienced lecturers, and find a course path that fits your goals.
		</p>
		<div class="welcome-actions">
			<a class="welcome-btn primary" href="#featured-programmes">View Programmes</a>
		</div>
	</section>

	<section class="welcome-highlights" aria-label="Platform highlights">
		<article>
			<h2>Industry Ready</h2>
			<p>Programmes are designed to build practical knowledge and real-world skills from year one.</p>
		</article>
		<article>
			<h2>Guided Support</h2>
			<p>Programme leaders and module tutors support your progress with clear advice and feedback.</p>
		</article>
		<article>
			<h2>Clear Next Steps</h2>
			<p>Check programme details, compare modules, and register your interest in just a few clicks.</p>
		</article>
	</section>

	<section id="featured-programmes" class="welcome-featured">
		<h2>Featured Programmes</h2>

		<?php if (!empty($featuredProgrammes)): ?>
			<div class="courses-container">
				<?php foreach ($featuredProgrammes as $programme): ?>
					<a class="card card-link" href="student/view/programme-details.php?id=<?= (int)$programme['ProgrammeID'] ?>" aria-label="View details for <?= htmlspecialchars($programme['ProgrammeName']) ?>">
						<h3><?php echo htmlspecialchars($programme['ProgrammeName']); ?></h3>
						<p>
							<?php
							$description = trim((string) $programme['Description']);
							echo htmlspecialchars(mb_strimwidth($description, 0, 130, '...'));
							?>
						</p>
					</a>
				<?php endforeach; ?>
			</div>
		<?php else: ?>
			<p class="welcome-empty">No programmes are available right now. Please check back soon.</p>
		<?php endif; ?>
	</section>
</main>
<?php include 'includes/footer.php'; ?>
</html>


