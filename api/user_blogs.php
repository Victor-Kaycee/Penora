<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";
require_once "includes/login_includes/login_view.inc.php";
require_once "includes/dashboard_includes/blog_upload_view.inc.php";

if (!isset($_SESSION["user_id"])) {

  header("Location: login.php");
  exit;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blogar | User Blogs</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
  <link rel="stylesheet" href="sweetalert2.min.css">
  <link rel="stylesheet" href="assets/css/sidebar.css">
  <link rel="stylesheet" href="assets/css/navbar.css">
  <link rel="stylesheet" href="./assets/css/user_blogs.css">
</head>

<body>

  <div class="layout">

    <?php require 'sidebar.php'; ?>

    <div class="main">
      <?php $pageTitle = "My Blogs";
      include "navbar.php"; ?>


      <div class="container">

        <?php
        if (isset($_SESSION['user_id'])) {
          $user_id = $_SESSION['user_id'];

          $query = "SELECT posts.*, users.firstname, users.lastname, users.file_name
          FROM posts 
          JOIN users ON posts.users_id = users.id 
          WHERE posts.users_id = :user_id ORDER BY posts.created_at DESC";
          $stmt = $pdo->prepare($query);
          $stmt->execute(['user_id' => $user_id]);

          $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

          if (count($results) > 0) {
            foreach ($results as $row) {
              $user_id = $_SESSION['user_id'];
              $post_id = $row['id'];

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


              // 🔥 Time logic
              $created = strtotime($row['created_at']);
              $diff = time() - $created;

              if ($diff < 60) {
                $timeDisplay = "Just now";
              } elseif ($diff < 3600) {
                $timeDisplay = floor($diff / 60) . " min ago";
              } elseif ($diff < 86400) {
                $timeDisplay = floor($diff / 3600) . " hrs ago";
              } else {
                $timeDisplay = date("j, F Y", $created);
              }

              // Count Comments

              $countComments = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM comments 
                    WHERE post_id = :post_id
                ");

              $countComments->execute([
                'post_id' => $row['id']
              ]);

              $totalComments = $countComments->fetchColumn();

              /*
        ================================
        TOTAL VIEWS
        ================================
        */

              $viewCount = $pdo->prepare("SELECT COUNT(*) FROM views WHERE post_id = :post_id");

              $viewCount->execute([
                'post_id' => $post_id
              ]);

              $totalViews = $viewCount->fetchColumn();



              echo '<div class="post">

          <!-- MENU -->
              <div class="blog-menu-wrapper">

                  <button class="blog-menu-btn" onclick="toggleBlogMenu(event, this)">
                      <i class="ri-more-2-fill"></i>
                  </button>

                  <div class="blog-dropdown">
                    <button class="delete-btn" onclick="openDeleteModal(' . $row['id'] . ')">
                      <i class="ri-delete-bin-6-line"></i>
                      Delete Blog
                    </button>
                    <a href="blog_edit.php?id=' . (int)$row['id'] . '" class="edit-btn">
                     <i class="ri-edit-line"></i>
                      Edit Blog
                    </a>
                  </div>

              </div>


            <!-- LEFT -->
              <div class="post-left">

                <!-- CLICKABLE CONTENT -->
                <a href="blog_details.php?id=' . $row['id'] . '" class="post-link">

                    <span class="category">' . htmlspecialchars($row['category']) . '</span>

                    <h3 class="title">' . htmlspecialchars($row['title']) . '</h3>

                    <div class="post-flex-user">

                        <div class="post-user">
                            <img src="includes/' . htmlspecialchars($row['file_name']) . '" class="user-img">

                            <div>
                              <p class="username">' . htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . '</p>

                              <span class="time">' . $timeDisplay . '</span>
                            </div>
                        </div>

                        <div class="post-icons">
                            <i class="ri-facebook-fill"></i>
                            <i class="ri-instagram-line"></i>
                            <i class="ri-twitter-x-fill"></i>
                            <i class="ri-links-line"></i>
                        </div>

                    </div>

                </a>

                <!-- ACTION BUTTONS -->
                <div class="blog-actions">

                  <form class="like-form">

                    <input type="hidden" class="post-id" value="' . $post_id . '">

                    <button type="button" class="blog-action-btn like-btn ' . ($isLiked ? "liked" : "") . '" onclick="handleLike(this)">

                    <i class="' . ($isLiked ? 'ri-heart-fill' : 'ri-heart-line') . ' like-icon"></i>

                    <span class="like-text">' . ($isLiked ? "Liked" : "Like") . '</span>

                    <span class="like-count">' . $totalLikes . '</span>

                    </button>

                  </form>

                  
                 <a href="blog_details.php?id=' . $row['id'] . '" class="blog-action-btn">
                      <i class="ri-chat-1-line"></i>
                      <span>' . $totalComments . '</span>
                  </a>

                  <button class="blog-action-btn">
                      <i class="ri-eye-line"></i>
                      <span>' . $totalViews . '</span>
                  </button>

                  

                  <button class="blog-action-btn" onclick="sharePost()">
                      <i class="ri-share-forward-line"></i>
                      <span>Share</span>
                  </button>

                </div>

          </div>

          <!-- RIGHT IMAGE -->
          <a href="blog_details.php?id=' . $row['id'] . '" class="post-right">
              <img src="includes/' . htmlspecialchars($row['post_img']) . '" class="post-img">
          </a>

      </div>';
            }
          } else {
            echo '<div class="post-empty">';
            echo '<h4>You have not posted any blog yet. Please upload a blog.</h4>';
            echo '<a href="blog_upload.php" class="blog_post_btn"> Upload Blog! </a>';
            echo '</div>';
          }
        } else {
          echo "Please log in to view your blogs.";
        }

        ?>

      </div>
    </div>

    <!-- DELETE MODAL -->
    <div id="deleteModal" class="modal-overlay">
      <div class="modal-box">

        <h3>Delete Blog?</h3>
        <p>This action cannot be undone.</p>

        <div class="modal-actions">
          <button onclick="closeDeleteModal()" class="cancel-btn">Cancel</button>

          <form id="deleteForm" method="POST" action="delete_blog.php">
            <input type="hidden" name="id" id="deleteId">
            <button type="submit" class="confirm-btn">Yes, Delete</button>
          </form>
        </div>

      </div>
    </div>


  </div>


  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/js/navbar.js"></script>
  <script src="assets/js/comments.js"></script>
  <script src="assets/js/likes.js"></script>
  <script src="assets/js/user_dashboard.js"></script>
</body>

</html>