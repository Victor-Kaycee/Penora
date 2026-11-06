<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";
require_once "includes/dashboard_includes/blog_upload_view.inc.php";


if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}

// check if id exists
if (!isset($_GET['id'])) {
    die("No post selected.");
}

date_default_timezone_set('Africa/Lagos');

$post_id = (int) $_GET['id'];



// fetch post + user info
$query = "SELECT posts.*, users.firstname, users.lastname, users.file_name  
          FROM posts 
          JOIN users ON posts.users_id = users.id 
          WHERE posts.id = :id";

$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $post_id]);

$post = $stmt->fetch(PDO::FETCH_ASSOC);

$user_id = $_SESSION['user_id'];
$post_id = $post['id'];

$check = $pdo->prepare("SELECT id FROM likes WHERE user_id = :user_id AND post_id = :post_id");
$check->execute([
    'user_id' => $user_id,
    'post_id' => $post_id
]);

$isLiked = $check->rowCount() > 0;

// count likes
$count = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = :post_id");
$count->execute(['post_id' => $post_id]);
$totalLikes = $count->fetchColumn();


if (!$post) {
    die("Post not found.");
}

// Count Comments

$countComments = $pdo->prepare(" SELECT COUNT(*) FROM comments WHERE post_id = :post_id");
$countComments->execute([
    'post_id' => $post['id']
]);

$totalComments = $countComments->fetchColumn();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogar | User Blog Details</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="./assets/css/blog_details.css">
</head>

<body>



    <?php require 'sidebar.php'; ?>

    <div class="main">
        <?php $pageTitle = "Blog Details";
        include "navbar.php"; ?>

        <?php

        $query = "
    SELECT 
        firstname,
        middlename,
        lastname,
        email,
        file_name,
        phone_no,
        location,
        bio,
        state
    FROM users
    WHERE id = :user_id
";

        $stmt = $pdo->prepare($query);

        $stmt->execute([
            'user_id' => $user_id
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>


        <div class="container">
            <div class="blog-details">
                <div class="blog-details-header">

                    <h1 class="title"><?php echo htmlspecialchars($post['title']); ?></h1>

                    <div class="blog-header-profile">
                        <div class="post-user">
                            <img src="includes/<?php echo htmlspecialchars($post['file_name']); ?>" class="user-img">
                            <div>
                                <p class="username">
                                    <?php echo htmlspecialchars($post['firstname'] . ' ' . $post['lastname']); ?>
                                </p>
                                <span class="time">
                                    <?php
                                    $created = strtotime($post['created_at']);
                                    echo date("j F Y", $created);
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="post-icons">
                            <i class="ri-facebook-fill"></i>
                            <i class="ri-instagram-line"></i>
                            <i class="ri-twitter-x-fill"></i>
                            <i class="ri-links-line"></i>
                        </div>
                    </div>

                </div>

                <img src="includes/<?php echo htmlspecialchars($post['post_img']); ?>" class="post-img" style="width:100%; max-height:400px; object-fit:cover;">

                <p>
                    <?php echo nl2br(htmlspecialchars($post['report'])); ?>
                </p>


                <div class="post-react">
                    <?php
                    echo '
                     <!-- ACTION BUTTONS -->
                    <div class="blog-actions">

                        <form class="like-form">

                            <input type="hidden" class="post-id" value="' . $post_id . '">

                            <button 
                            type="button"
                            class="blog-action-btn like-btn ' . ($isLiked ? "liked" : "") . '"
                            onclick="handleLike(this)"
                            >

                            <i class="' . ($isLiked ? 'ri-heart-fill' : 'ri-heart-line') . ' like-icon"></i>

                            <span class="like-text">
                            ' . ($isLiked ? "Liked" : "Like") . '
                            </span>

                            <span class="like-count">
                            ' . $totalLikes . '
                            </span>

                            </button>

                        </form>

                        
                        <button class="blog-action-btn">
                            <i class="ri-chat-1-line"></i>
                            <span>' . $totalComments . '</span>
                        </button>
                        

                        <button class="blog-action-btn">
                            <i class="ri-share-forward-line"></i>
                            <span>Share</span>
                        </button>

                    </div>'
                    ?>

                </div>




            </div>

        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js//comments.js"></script>
    <script src="assets/js/likes.js"></script>
    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/user_dashboard.js"></script>
</body>

</html>