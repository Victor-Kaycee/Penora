<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";

if (!isset($_SESSION['user_id'])) {
    exit("login required");
}

$follower_id = $_SESSION['user_id'];
$following_id = (int) $_POST['user_id'];

/*
================================
CHECK IF ALREADY FOLLOWING
================================
*/

$check = $pdo->prepare("
    SELECT id 
    FROM followers 
    WHERE follower_id = :follower_id 
    AND following_id = :following_id
");

$check->execute([
    'follower_id' => $follower_id,
    'following_id' => $following_id
]);

if ($check->rowCount() > 0) {

    // UNFOLLOW
    $delete = $pdo->prepare("
        DELETE FROM followers 
        WHERE follower_id = :follower_id 
        AND following_id = :following_id
    ");

    $delete->execute([
        'follower_id' => $follower_id,
        'following_id' => $following_id
    ]);

    echo "unfollowed";

} else {

    // FOLLOW
    $insert = $pdo->prepare("
        INSERT INTO followers (follower_id, following_id)
        VALUES (:follower_id, :following_id)
    ");

    $insert->execute([
        'follower_id' => $follower_id,
        'following_id' => $following_id
    ]);

    echo "followed";
}