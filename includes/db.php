<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "student_course_hub";

if (!$conn = mysqli_connect($host, $username, $password, $dbname)) {
    die("Connection failed");
}
