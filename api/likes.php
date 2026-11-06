<?php

require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "not_logged_in"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;

if (!$post_id) {
    echo json_encode(["error" => "missing_post_id"]);
    exit;
}

/*
========================================
CHECK LIKE
========================================
*/

$check = $pdo->prepare("
    SELECT id
    FROM likes
    WHERE user_id = :user_id
    AND post_id = :post_id
");

$check->execute([
    'user_id' => $user_id,
    'post_id' => $post_id
]);

/*
========================================
GET POST OWNER (ONCE)
========================================
*/

$getPostOwner = $pdo->prepare("
    SELECT users_id
    FROM posts
    WHERE id = :post_id
");

$getPostOwner->execute([
    'post_id' => $post_id
]);

$postOwner = $getPostOwner->fetch(PDO::FETCH_ASSOC);

/*
========================================
UNLIKE
========================================
*/

if ($check->rowCount() > 0) {

    $delete = $pdo->prepare("
        DELETE FROM likes
        WHERE user_id = :user_id
        AND post_id = :post_id
    ");

    $delete->execute([
        'user_id' => $user_id,
        'post_id' => $post_id
    ]);

    $liked = false;

/*
========================================
LIKE + NOTIFICATION
========================================
*/

} else {

    $insert = $pdo->prepare("
        INSERT INTO likes (user_id, post_id)
        VALUES (:user_id, :post_id)
    ");

    $insert->execute([
        'user_id' => $user_id,
        'post_id' => $post_id
    ]);

    $liked = true;

    /*
    ========================================
    CREATE NOTIFICATION ONLY ON LIKE
    ========================================
    */

    if ($postOwner) {

        $message = ($postOwner['users_id'] == $user_id)
            ? 'You liked your own blog post'
            : 'liked your blog post';

        $notify = $pdo->prepare("
            INSERT INTO notifications
            (user_id, sender_id, post_id, type, message)
            VALUES
            (:user_id, :sender_id, :post_id, :type, :message)
        ");

        $notify->execute([
            'user_id' => $postOwner['users_id'],
            'sender_id' => $user_id,
            'post_id' => $post_id,
            'type' => 'like',
            'message' => $message
        ]);
    }
}

/*
========================================
COUNT LIKES
========================================
*/

$count = $pdo->prepare("
    SELECT COUNT(*)
    FROM likes
    WHERE post_id = :post_id
");

$count->execute([
    'post_id' => $post_id
]);

/*
========================================
RETURN RESPONSE LAST
========================================
*/

echo json_encode([
    "liked" => $liked,
    "likes" => $count->fetchColumn()
]);

?>