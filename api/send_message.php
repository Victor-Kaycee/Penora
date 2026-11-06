<?php

require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";

if(!isset($_SESSION['user_id'])){

    die("Login required");
}

$sender_id = $_SESSION['user_id'];

$receiver_id = (int) $_POST['receiver_id'];

$message = trim($_POST['message']);

if(empty($message)){

    die("Message cannot be empty");
}

/*
=================================
INSERT MESSAGE
=================================
*/

$query = "
    INSERT INTO user_messages
    (sender_id, receiver_id, message)

    VALUES
    (:sender_id, :receiver_id, :message)
";

$stmt = $pdo->prepare($query);

$stmt->execute([

    'sender_id' => $sender_id,
    'receiver_id' => $receiver_id,
    'message' => $message

]);

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;