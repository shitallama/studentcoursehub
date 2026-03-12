<?php
// ---------- Database Connection (PDO) ----------
$host = "localhost";
$dbname = "student_course_hub";
$username = "root";   
$password = "";     

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(" Database Connection failed: " . $e->getMessage());
}
?>
