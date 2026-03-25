<?php
// ---------- Database Connection (PDO) ----------
//------------We used the database that was provided to us, and we have set up a connection to it using PDO.
//This allows us to securely interact with the database throughout our application.
$host = getenv('DB_HOST') ?: "localhost";
$port = getenv('DB_PORT') ?: "3306";
$dbname = getenv('DB_NAME') ?: "student_course_hub";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASSWORD') ?: "";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(" Database Connection failed: " . $e->getMessage());
}
?>