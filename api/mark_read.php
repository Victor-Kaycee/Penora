<?php

require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";

if (!isset($_SESSION['user_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];

$update = $pdo->prepare("UPDATE notifications
SET is_read = 1
WHERE user_id = :user_id");

$update->execute([
    'user_id' => $user_id
]);

header("Location: notifications.php");
exit;