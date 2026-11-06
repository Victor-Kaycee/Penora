<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";
require_once "includes/dashboard_includes/blog_upload_view.inc.php";


if (!isset($_SESSION["user_id"])) {

  header("Location: login.php");
  exit;
}

?>

<?php

$countQuery = $pdo->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = :user_id
    AND is_read = 0
");

$countQuery->execute([
  'user_id' => $_SESSION['user_id']
]);

$unreadCount = $countQuery->fetchColumn();

?>

<?php

/*
==================================
VIEW SYSTEM
==================================
*/

if (isset($_GET['id'])) {

  $post_id = (int) $_GET['id'];

  $user_ip = $_SERVER['REMOTE_ADDR'];

  $checkView = $pdo->prepare("
        SELECT id 
        FROM views 
        WHERE post_id = :post_id 
        AND user_ip = :user_ip
    ");

  $checkView->execute([
    'post_id' => $post_id,
    'user_ip' => $user_ip
  ]);

  if ($checkView->rowCount() == 0) {

    $insertView = $pdo->prepare("
            INSERT INTO views(post_id, user_ip)
            VALUES(:post_id, :user_ip)
        ");

    $insertView->execute([
      'post_id' => $post_id,
      'user_ip' => $user_ip
    ]);
  }
}

?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blogar | Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
  <link rel="stylesheet" href="sweetalert2.min.css">
  <link rel="stylesheet" href="assets/css/sidebar.css">
  <link rel="stylesheet" href="assets/css/navbar.css">
  <link rel="stylesheet" href="assets/css/user_dashboard.css">
</head>

<body>

  <div class="layout">
    <?php require 'sidebar.php'; ?>

    <!-- Main -->
    <div class="main">
      <!-- Navbar -->
      <?php // require 'navbar.php'; 
      ?>
      <?php
      $pageTitle = "Dashboard";
      include "navbar.php";
      ?>


      <div class="container">
        <!-- LEFT -->
        <div class="left">
          <div class="hero">
            <p>Penora.</p>
            <h1>Transform Your Ideas into <br> Impactful Stories</h1>
            <a href="blog_upload.php">Post Now</a>
          </div>
          <div class="tags">

            <?php

            $query = "SELECT COUNT(*) AS total_blogs FROM posts WHERE users_id = :user_id";

            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $total_blogs = $result['total_blogs'];

            // total likes


            $query = "SELECT COUNT(*) AS total_likes FROM likes INNER JOIN posts ON likes.post_id = posts.id WHERE posts.users_id = :user_id";

            $stmt = $pdo->prepare($query);

            $stmt->bindParam(':user_id', $user_id);

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $total_likes = $result['total_likes'];

            // total comments

            $query = "SELECT COUNT(*) AS total_comments FROM comments WHERE user_id = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $total_comments = $result['total_comments'];

            // total views

            $query = "SELECT COUNT(*) AS total_views FROM views INNER JOIN posts ON views.post_id = posts.id WHERE posts.users_id = :user_id";

            $stmt = $pdo->prepare($query);

            $stmt->bindParam(':user_id', $user_id);

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $total_views = $result['total_views'];
            ?>
            <!-- INCOME -->

            <div class="card income-card white-card">

              <div class="top-amount">

                <h1><?php echo $total_blogs; ?></h1>

                <div class="big-icon">
                  <i class="ri-news-fill"></i>
                </div>

              </div>

              <p>Blogs Posted</p>
              <span>-2% than last month</span>

            </div>

            <!-- EXPENSE -->

            <div class="card income-card red-card">

              <div class="top-amount">

                <h1><?php echo $total_likes; ?></h1>

                <div class="big-icon">
                  <i class="ri-heart-fill"></i>
                </div>

              </div>

              <p>Likes</p>
              <span>+0.5% than last month</span>

            </div>

            <!-- EXPENSE -->

            <div class="card income-card dark-red-card">

              <div class="top-amount">

                <h1><?php echo $total_views; ?></h1>

                <div class="big-icon">
                  <i class="ri-eye-fill"></i>
                </div>

              </div>

              <p>Views</p>
              <span>+0.5% than last month</span>

            </div>

          </div>



          <!-- POSTS -->


          <div class="posts">
            <h3 class="post-header">Recent Blogs</h3>

            <?php

            if (isset($_SESSION['user_id'])) {
              $user_id = $_SESSION['user_id'];

              $query = "SELECT posts.*, users.firstname, users.lastname, users.file_name FROM posts JOIN users ON posts.users_id = users.id WHERE posts.users_id = :user_id ORDER BY posts.created_at DESC LIMIT 3";
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

                  // date_default_timezone_set('Africa/Lagos');

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


                  echo '
               <div class="post">

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


        <!-- RIGHT -->
        <?php
        $query = "SELECT firstname, lastname, middlename, file_name FROM users WHERE id = :user_id";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);


        // Total blogs

        $query = "SELECT COUNT(*) AS totalBlogs FROM posts WHERE users_id = :user_id";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalBlogs = $result['totalBlogs'];

        // COUNT FOLLOWERS

        $query = "SELECT COUNT(*) AS totalFollowers FROM followers WHERE following_id = :user_id";

        $stmt = $pdo->prepare($query);

        $stmt->bindParam(':user_id', $user_id);

        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalFollowers = $result['totalFollowers'];

        // COUNT FOLLOWING

        $query = "SELECT COUNT(*) AS totalFollowing FROM followers WHERE follower_id = :user_id";

        $stmt = $pdo->prepare($query);

        $stmt->bindParam(':user_id', $user_id);

        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalFollowing = $result['totalFollowing'];

        ?>

        <div class="right">
          <div class="stats">
            <h3>Statistic</h3>
            <div class="circle">
              <img src="includes/<?php echo $user['file_name'] ?>" id="profileImage" onclick="openImage(this.src)" style="cursor:pointer;" alt="">
            </div>
            <h3><?php echo $user['firstname'] . " " . $user['lastname'] ?> 🔥</h3>
            <span>@<?php echo $user['middlename'] ?></span>

            <div class="stats-details">
              <div class="stats-det">
                <h3><?= $totalBlogs ?></h3>
                <span>BLOGS</span>
              </div>
              <div class="stats-det">
                <h3><?= $totalFollowers ?></h3>
                <span>FOLLOWERS</span>
              </div>
              <div class="stats-det">
                <h3><?= $totalFollowing ?></h3>
                <span>FOLLOWING</span>
              </div>
            </div>
          </div>


          <!-- chart -->

          <div class="patient-card">
            <h3>User Segment</h3>

            <div class="chart-wrapper">
              <canvas id="patientChart"></canvas>
              <div class="center-text">
                <h2><?= $totalFollowers ?></h2>
                <p>Total Followers</p>
              </div>
            </div>

            <div class="legend">
              <div><span class="dot gray"></span> Reader Distribution</div>
              <div><span class="dot purple"></span> Top Performing Article</div>
              <div><span class="dot orange"></span> Reading Trends</div>
            </div>
          </div>


        </div>
      </div>
    </div>


    <div id="imageModal" class="image-modal">
      <span class="close-btn" onclick="closeImage()">&times;</span>

      <img id="modalImg" class="modal-content">
    </div>

  </div>



  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="assets/js/profilepic.js"></script>
  <script src="assets/js/share.js"></script>
  <script src="assets/js/comments.js"></script>
  <script src="assets/js/likes.js"></script>
  <script src="assets/js/dash_chart.js"></script>
  <script src="assets/js/navbar.js"></script>
  <script src="assets/js/user_dashboard.js"></script>
</body>

</html>