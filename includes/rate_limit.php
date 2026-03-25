<?php
function rateLimitEnsureTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS AuthRateLimits (
        KeyHash CHAR(64) PRIMARY KEY,
        Bucket VARCHAR(60) NOT NULL,
        FailCount INT NOT NULL DEFAULT 0,
        FirstAttempt DATETIME NOT NULL,
        LastAttempt DATETIME NOT NULL,
        LockUntil DATETIME NULL,
        INDEX idx_authratelimits_bucket (Bucket),
        INDEX idx_authratelimits_lockuntil (LockUntil)
    )");
}

function rateLimitBuildKey(string $bucket, string $identifier): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $normalizedIdentifier = strtolower(trim($identifier));
    return hash('sha256', $bucket . '|' . $normalizedIdentifier . '|' . $ip);
}

function rateLimitGetStatus(PDO $pdo, string $bucket, string $identifier, int $windowSeconds): array {
    rateLimitEnsureTable($pdo);

    $keyHash = rateLimitBuildKey($bucket, $identifier);
    $stmt = $pdo->prepare('SELECT FailCount, FirstAttempt, LockUntil FROM AuthRateLimits WHERE KeyHash = ? LIMIT 1');
    $stmt->execute([$keyHash]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['allowed' => true, 'retry_after' => 0, 'key' => $keyHash];
    }

    $now = time();
    $lockUntilTs = !empty($row['LockUntil']) ? strtotime((string)$row['LockUntil']) : 0;
    if ($lockUntilTs > $now) {
        return ['allowed' => false, 'retry_after' => max(1, $lockUntilTs - $now), 'key' => $keyHash];
    }

    $firstAttemptTs = strtotime((string)$row['FirstAttempt']);
    if ($firstAttemptTs <= 0 || ($now - $firstAttemptTs) > $windowSeconds) {
        $reset = $pdo->prepare('DELETE FROM AuthRateLimits WHERE KeyHash = ?');
        $reset->execute([$keyHash]);
        return ['allowed' => true, 'retry_after' => 0, 'key' => $keyHash];
    }

    return ['allowed' => true, 'retry_after' => 0, 'key' => $keyHash];
}

function rateLimitRegisterFailure(PDO $pdo, string $bucket, string $identifier, int $maxAttempts, int $windowSeconds): void {
    rateLimitEnsureTable($pdo);

    $keyHash = rateLimitBuildKey($bucket, $identifier);
    $now = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare('SELECT FailCount, FirstAttempt FROM AuthRateLimits WHERE KeyHash = ? LIMIT 1');
    $stmt->execute([$keyHash]);
    $row = $stmt->fetch();

    if (!$row) {
        $insert = $pdo->prepare('INSERT INTO AuthRateLimits (KeyHash, Bucket, FailCount, FirstAttempt, LastAttempt, LockUntil) VALUES (?, ?, 1, ?, ?, NULL)');
        $insert->execute([$keyHash, $bucket, $now, $now]);
        return;
    }

    $firstAttemptTs = strtotime((string)$row['FirstAttempt']);
    $expiredWindow = ($firstAttemptTs <= 0) || ((time() - $firstAttemptTs) > $windowSeconds);
    $failCount = $expiredWindow ? 1 : ((int)$row['FailCount'] + 1);
    $firstAttempt = $expiredWindow ? $now : (string)$row['FirstAttempt'];
    $lockUntil = null;

    if ($failCount >= $maxAttempts) {
        $lockUntil = date('Y-m-d H:i:s', time() + $windowSeconds);
    }

    $update = $pdo->prepare('UPDATE AuthRateLimits SET Bucket = ?, FailCount = ?, FirstAttempt = ?, LastAttempt = ?, LockUntil = ? WHERE KeyHash = ?');
    $update->execute([$bucket, $failCount, $firstAttempt, $now, $lockUntil, $keyHash]);
}

function rateLimitClear(PDO $pdo, string $bucket, string $identifier): void {
    rateLimitEnsureTable($pdo);

    $keyHash = rateLimitBuildKey($bucket, $identifier);
    $delete = $pdo->prepare('DELETE FROM AuthRateLimits WHERE KeyHash = ?');
    $delete->execute([$keyHash]);
}
