<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {

    $blog_id = $_POST['id'];
    $user_id = $_SESSION['user_id'];

    // 1. GET IMAGE FIRST
    $select = "SELECT post_img FROM posts WHERE id = :id AND users_id = :user_id";
    $stmt = $pdo->prepare($select);
    $stmt->execute([
        'id' => $blog_id,
        'user_id' => $user_id
    ]);

    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {

        // 2. DELETE IMAGE FROM FOLDER
        $imagePath = "includes/" . $post['post_img'];

        if (!empty($post['post_img']) && file_exists($imagePath)) {
            unlink($imagePath);
        }

        // 3. DELETE DATABASE RECORD
        $delete = "DELETE FROM posts WHERE id = :id AND users_id = :user_id";
        $stmt = $pdo->prepare($delete);

        $stmt->execute([
            'id' => $blog_id,
            'user_id' => $user_id
        ]);
    }

    header("Location: user_dashboard.php?msg=deleted");
    exit;
}
