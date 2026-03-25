<?php
session_start();
require __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
	header('Location: ../controller/student-login.php?error=not_logged_in');
	exit;
}

$email = strtolower(trim($_SESSION['email'] ?? ''));
if ($email === '') {
	$lookup = $pdo->prepare('SELECT Email FROM Users WHERE UserID = ?');
	$lookup->execute([(int)$_SESSION['user_id']]);
	$email = strtolower((string)$lookup->fetchColumn());
	$_SESSION['email'] = $email;
}

$stmt = $pdo->prepare('SELECT i.InterestID, i.RegisteredAt, p.ProgrammeID, p.ProgrammeName, p.Description FROM InterestedStudents i JOIN Programmes p ON p.ProgrammeID = i.ProgrammeID WHERE LOWER(i.Email) = ? ORDER BY i.RegisteredAt DESC');
$stmt->execute([$email]);
$registrations = $stmt->fetchAll();
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/admin.css">
<title>Student Dashboard | Student Course Hub</title>

<main class="admin-content" style="max-width: 1100px; margin: 2rem auto;">
	<div class="admin-header">
		<h1>Manage Programmes</h1>
		<div class="admin-actions">
			<span class="admin-badge badge-success">Student Account</span>
			<a href="profile.php?from=student" class="admin-btn admin-btn-view">My Profile</a>
		</div>
	</div>

	<?php if (isset($_GET['msg']) && $_GET['msg'] === 'unregistered'): ?>
		<div class="auth-message success" style="margin-bottom: 1rem;">You have been unregistered from the selected programme.</div>
	<?php endif; ?>

	<div class="admin-card">
		<div class="admin-card-header">
			<h2>Registered Programmes</h2>
			<span class="admin-badge badge-warning">Total: <?= count($registrations) ?></span>
		</div>
		<div class="admin-card-body">
			<?php if ($registrations): ?>
				<table class="admin-table">
					<thead>
						<tr>
							<th>Programme</th>
							<th>Registered On</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($registrations as $r): ?>
							<tr>
								<td>
									<strong><?= htmlspecialchars($r['ProgrammeName']) ?></strong><br>
									<small><?= htmlspecialchars(mb_strimwidth((string)$r['Description'], 0, 90, '...')) ?></small>
								</td>
								<td><?= date('d M Y', strtotime($r['RegisteredAt'])) ?></td>
								<td class="admin-table-actions">
									<a href="programme-details.php?id=<?= (int)$r['ProgrammeID'] ?>" class="admin-btn admin-btn-view admin-btn-sm">View</a>
									<a href="../controller/student-unregister.php?programme_id=<?= (int)$r['ProgrammeID'] ?>"
									   class="admin-btn admin-btn-delete admin-btn-sm"
									   onclick="return confirm('Unregister from this programme?');">Unregister</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else: ?>
				<p style="text-align:center; padding:2rem; color: var(--warm-gray-500);">
					You have not registered interest in any programme yet.
					<a href="../../home.php">Browse programmes</a>.
				</p>
			<?php endif; ?>
		</div>
	</div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
