<?php

$dbhost = "localhost";
$dbname = "blog";
$dbusername = "root";
$dbpassword = "";


try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}
