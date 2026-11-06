<?php
$conn = new PDO("mysql:host=localhost;dbname=test", "root", "");

$stmt = $conn->query("SELECT file_name FROM users");

while ($row = $stmt->fetch()) {
    echo '<img src="' . $row['file_name'] . '" width="150"><br>';
}
