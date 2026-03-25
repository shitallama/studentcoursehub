<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php'; // Load the gatekeeper
require __DIR__ . '/../../includes/audit_log.php';
protectPage(['admin']);               // ONLY 'admin' can enter

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../staff/controller/staff-login.php");
    exit;
}

if (!isSuperAdminSession()) {
    header("Location: ../view/staff.php?error=superadmin_only");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '' || $username === '' || $email === '') {
        $error = 'Please complete all required fields.';
    } elseif (!isUniversityEmail($email)) {
        $error = 'Use an institutional email ending with @edu.nielsbrock.dk.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            $pdo->beginTransaction();

            $check = $pdo->prepare("SELECT u.UserID, u.Role, u.StaffID, s.StaffID AS ExistingStaffID FROM Users u LEFT JOIN Staff s ON s.StaffID = u.StaffID WHERE LOWER(u.Username) = LOWER(?) OR LOWER(u.Email) = LOWER(?) FOR UPDATE");
            $check->execute([$username, $email]);
            $matches = $check->fetchAll();

            $orphanStaffUserIds = [];
            $hasBlockingMatch = false;

            foreach ($matches as $matchedUser) {
                $role = (string)($matchedUser['Role'] ?? '');
                $hasValidStaffLink = !empty($matchedUser['StaffID']) && !empty($matchedUser['ExistingStaffID']);

                if ($role === 'staff' && !$hasValidStaffLink) {
                    $orphanStaffUserIds[] = (int)$matchedUser['UserID'];
                    continue;
                }

                $hasBlockingMatch = true;
                break;
            }

            if ($hasBlockingMatch) {
                $pdo->rollBack();
                $error = 'Username or Email already exists.';
            } else {
                if (!empty($orphanStaffUserIds)) {
                    $placeholders = implode(',', array_fill(0, count($orphanStaffUserIds), '?'));
                    $deleteOrphans = $pdo->prepare("DELETE FROM Users WHERE UserID IN ($placeholders)");
                    $deleteOrphans->execute($orphanStaffUserIds);
                }

                // StaffID is not AUTO_INCREMENT in this schema, so assign the next ID.
                $nextId = (int)$pdo->query("SELECT COALESCE(MAX(StaffID), 0) + 1 FROM Staff")->fetchColumn();

                $insertStaff = $pdo->prepare("INSERT INTO Staff (StaffID, Name) VALUES (?, ?)");
                $insertStaff->execute([$nextId, $name]);

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertUser = $pdo->prepare("INSERT INTO Users (Username, Email, Password, Role, StaffID) VALUES (?, ?, ?, 'staff', ?)");
                $insertUser->execute([$username, $email, $hashedPassword, $nextId]);
                $newUserId = (int)$pdo->lastInsertId();

                $pdo->commit();
                writeAuditLog($pdo, 'staff_id_created', [
                    'target_user_id' => $newUserId,
                    'target_staff_id' => $nextId,
                    'username' => $username,
                    'email' => $email,
                    'orphan_staff_users_cleaned' => count($orphanStaffUserIds)
                ]);
                header("Location: ../view/staff.php?msg=added_account");
                exit;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Failed to create staff account. Please try again.';
        }
    }
}

include __DIR__ . '/../../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../../assets/admin.css">';
?>

<title>Create Staff Account | Student Course Hub</title>


<div class="admin-wrapper">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Create Staff Account</h1>
            <div class="admin-actions">
                <a href="../view/staff.php" class="admin-btn admin-btn-view">← Back to Staff</a>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="auth-message error" style="margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-body">
                <form method="POST" class="admin-form">
                    <div class="admin-form-group">
                        <label for="name">Staff Name</label>
                        <input type="text" id="name" name="name" placeholder="e.g., Dr. John Smith" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="e.g., jsmith" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="email">University Email</label>
                        <input type="email" id="email" name="email" placeholder="name@edu.nielsbrock.dk" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" required placeholder="Password">
                            <button type="button" class="password-toggle" data-target="password" aria-label="Show password" title="Show password"><span class="icon-svg icon-eye-off" aria-hidden="true"></span></button>
                        </div>
                    </div>

                    <div class="admin-form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-field">
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm Password">
                            <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password" title="Show password"><span class="icon-svg icon-eye-off" aria-hidden="true"></span></button>
                        </div>
                    </div>
                    
                    <div class="admin-form-actions">
                        <button type="submit" class="admin-btn admin-btn-primary"><span class="nav-icon"><span class="icon-svg icon-add" aria-hidden="true"></span></span> Create Staff ID</button>
                        <a href="../view/staff.php" class="admin-btn admin-btn-view">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="../../assets/js/password-toggle.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>






