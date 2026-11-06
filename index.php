<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";
require_once "includes/login_includes/login_view.inc.php";
require_once "includes/dashboard_includes/blog_upload_view.inc.php";


if (isset($_SESSION['user_id'])) {

  $query = "SELECT * FROM users WHERE id = :id";
  $stmt = $pdo->prepare($query);
  $stmt->bindParam(':id', $user_id);
  $stmt->execute();

  $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<?php

/*
==================================
VIEW SYSTEM
==================================
*/

if (isset($_GET['id'])) {

  $post_id = (int) $_GET['id'];

  // Logged in user
  if (isset($_SESSION['user_id'])) {

    $viewer = $_SESSION['user_id'];
  } else {

    // Guest user
    $viewer = $_SERVER['REMOTE_ADDR'];
  }

  /*
=========================
CHECK IF ALREADY VIEWED
    =========================
*/

  $checkView = $pdo->prepare("
        SELECT id
        FROM views
        WHERE post_id = :post_id
        AND viewer = :viewer
    ");

  $checkView->execute([

    'post_id' => $post_id,
    'viewer' => $viewer

  ]);

  /*
=========================
INSERT VIEW
=========================
*/

  if ($checkView->rowCount() == 0) {

    $insertView = $pdo->prepare("INSERT INTO views(post_id, viewer) VALUES(:post_id, :viewer)");

    $insertView->execute([

      'post_id' => $post_id,
      'viewer' => $viewer

    ]);
  }
}

?>

<?php
$query = "SELECT posts.*, users.firstname, users.lastname, users.file_name FROM posts JOIN users ON posts.users_id = users.id ORDER BY posts.created_at DESC LIMIT 10";

$stmt = $pdo->prepare($query);
$stmt->execute();

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Blogar|Homepage</title>
  <link
    href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css"
    rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

  <?php require 'header.php'; ?>


  <div class="container">

    <main class="hero">
      <div class="slider" id="slider">

        <?php foreach ($posts as $index => $post): ?>

          <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" data-transition="reveal">

            <img
              class="slide-img"
              src="includes/<?php echo htmlspecialchars($post['post_img']); ?>"
              alt="<?php echo htmlspecialchars($post['title']); ?>">

            <div class="slide-card">

              <div class="slide-card-main">
                <a href="category.php?category=<?php echo $post['category']; ?>" class="slide-cat">
                  <?php echo htmlspecialchars($post['category']); ?>
                </a>

                <a href="blogDetails.php?id=<?php echo $post['id'] ?>" class="slide-title">
                  <?php echo htmlspecialchars($post['title']); ?>
                </a>
              </div>

              <a href="profile.php?id=<?php echo $post['users_id'] ?>" class="slide-author">

                <img
                  src="includes/<?php echo htmlspecialchars($post['file_name']); ?>"
                  alt="">

                <span>
                  <?php echo htmlspecialchars($post['firstname'] . ' ' . $post['lastname']); ?>
                </span>

              </a>

            </div>

          </div>

        <?php endforeach; ?>

      </div>

      <div class="slider-controls">
        <button class="ctrl prev" aria-label="Previous slide"><i class="ri-arrow-left-long-line"></i></button>
        <button class="ctrl next" aria-label="Next slide"><i class="ri-arrow-right-long-line"></i></button>
      </div>

      <div class="dots" id="dots"></div>
    </main>
  </div>


  <section class="trending">
    <div class="container">

      <div class="trending-header">
        <h1>Trending Topics</h1>
        <a href="#">See All Topics</a>
      </div>

      <div class="topics">

        <a href="category.php?category=Tech" class="topic-card">
          <img src="assets/img/category1.jpg">
          <span>Tech</span>
        </a>

        <a href="category.php?category=Sport" class="topic-card">
          <img src="assets/img/category2.jpg">
          <span>Sport</span>
        </a>

        <a href="category.php?category=Fitness" class="topic-card">
          <img src="assets/img/category3.jpg">
          <span>Fitness</span>
        </a>

        <a href="category.php?category=Health" class="topic-card">
          <img src="assets/img/category4.jpg">
          <span>Health</span>
        </a>

        <a href="category.php?category=Entertainment" class="topic-card">
          <img src="assets/img/category5.jpg">
          <span>Entertainment</span>
        </a>

        <a href="category.php?category=Business" class="topic-card">
          <img src="assets/img/category6.jpg">
          <span>Business</span>
        </a>

        <a href="category.php?category=Gaming" class="topic-card">
          <img src="assets/img/category7.jpg">
          <span>Gaming</span>
        </a>

        <a href="category.php?category=Fashion" class="topic-card">
          <img src="assets/img/category8.jpg">
          <span>Fashion</span>
        </a>

        <a href="category.php?category=Automobile" class="topic-card">
          <img src="assets/img/category9.jpg">
          <span>Automobile</span>
        </a>

        <a href="category.php?category=Animals" class="topic-card">
          <img src="assets/img/category10.jpg">
          <span>Animals</span>
        </a>

        <a href="category.php?category=Food" class="topic-card">
          <img src="assets/img/category11.jpg">
          <span>Food</span>
        </a>

      </div>

    </div>
  </section>



  <section class="popular">
    <div class="container">
      <h1>All</h1>

      <?php

      $query = "SELECT posts.*, users.firstname, users.lastname, users.file_name FROM posts JOIN users ON posts.users_id = users.id ORDER BY posts.created_at DESC";

      $stmt = $pdo->prepare($query);
      $stmt->execute();

      $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (count($posts) > 0) {

        foreach ($posts as $row) {

          $post_id = $row['id'];

          /*
        ================================
        CHECK IF USER LIKED POST
        ================================
        */

          $isLiked = false;

          if (isset($_SESSION['user_id'])) {

            $user_id = $_SESSION['user_id'];

            $check = $pdo->prepare("SELECT id FROM likes WHERE user_id = :user_id AND post_id = :post_id");

            $check->execute([
              'user_id' => $user_id,
              'post_id' => $post_id
            ]);

            $isLiked = $check->rowCount() > 0;
          }

          /*
        ================================
        TOTAL LIKES
        ================================
        */

          $count = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = :post_id");

          $count->execute([
            'post_id' => $post_id
          ]);

          $totalLikes = $count->fetchColumn();

          /*
        ================================
        TOTAL COMMENTS
        ================================
        */

          $countComments = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = :post_id");

          $countComments->execute([
            'post_id' => $post_id
          ]);

          $totalComments = $countComments->fetchColumn();

          /*
        ================================
        TIME
        ================================
        */

          $created = strtotime($row['created_at']);
          $diff = time() - $created;

          if ($diff < 60) {
            $timeDisplay = "Just now";
          } elseif ($diff < 3600) {
            $timeDisplay = floor($diff / 60) . " min ago";
          } elseif ($diff < 86400) {
            $timeDisplay = floor($diff / 3600) . " hrs ago";
          } else {
            $timeDisplay = date("j F Y", $created);
          }

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


          /*
        ================================
        OUTPUT POSTS
        ================================
        */

          echo '

        <section class="posts">
              <!-- Post 1 -->
              <article class="post">
                <div class="post-body">
                  <div class="post-first">
                  <a href="category.php?category=' . urlencode($row['category']) . '" class="category">
                      ' . htmlspecialchars($row['category']) . '
                    </a>
                  <a href="blogDetails.php?id=' . $row['id'] . '" class="title">
                      ' . htmlspecialchars($row['title']) . '
                    </a>
                    </div>

                  <div class="meta-row">
                    <a href="profile.php?id=' . $row['users_id'] . '" class="post-user">

                                    <img 
                                    src="includes/' . htmlspecialchars($row['file_name']) . '" 
                                    class="user-img"
                                    >

                                    <div>

                                        <p class="username">
                                            ' . htmlspecialchars($row['firstname']) . ' 
                                            ' . htmlspecialchars($row['lastname']) . '
                                        </p>

                                        <span class="time">
                                            ' . $timeDisplay . '
                                        </span>

                                    </div>

                                </a>
                    <div class="socials">
                      <i class="ri-facebook-fill"></i>
                      <i class="ri-instagram-line"></i>
                      <i class="ri-twitter-x-fill"></i>
                      <i class="ri-links-line"></i>
                    </div>
                  </div>

                  <div class="blog-actions">

                            <form class="like-form">

                                <input 
                                type="hidden" 
                                class="post-id" 
                                value="' . $post_id . '"
                                >

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

                            <a href="blogDetails.php?id=' . $row['id'] . '" class="blog-action-btn">

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
                <a href="blogDetails.php?id=' . $row['id'] . '" class="post-right">

                        <img 
                        src="includes/' . htmlspecialchars($row['post_img']) . '" 
                        class="post-img"
                        >

                </a>
              </article>

            </section>
                ';
        }
      } else {

        echo '
            <div class="post-empty">

                <h4>No blogs available yet.</h4>

            </div>
    ';
      }
      ?>

    </div>
  </section>


  <!-- ============ CHAT WIDGET ============ -->
  <div class="nova-widget" id="novaWidget">

    <!-- Proactive nudge bubble -->
    <div class="nova-nudge" id="novaNudge">
      <p>Hey — need a hand with anything? 👋</p>
      <button class="nova-nudge-close" id="novaNudgeClose" aria-label="Dismiss">×</button>
    </div>

    <!-- Chat panel -->
    <section class="nova-panel" id="novaPanel" role="dialog" aria-label="Chat with Nova" aria-hidden="true">
      <header class="nova-header">
        <div class="nova-header-id">
          <div class="nova-avatar">
            <svg width="220" height="300" viewBox="0 0 220 300" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <linearGradient id="penoraRed" x1="0" y1="0" x2="1" y2="1">
                  <stop offset="0%" stop-color="#FF2D55" />
                  <stop offset="100%" stop-color="#FF1744" />
                </linearGradient>
              </defs>

              <!-- Top curved section -->
              <path
                d="M20 20
       H120
       C170 20 200 55 200 105
       V160
       H135
       V120
       C135 95 120 85 95 85
       H20
       Z"
                fill="url(#penoraRed)" />

              <!-- Middle square -->
              <rect
                x="85"
                y="160"
                width="65"
                height="65"
                fill="url(#penoraRed)" />

              <!-- Bottom square -->
              <rect
                x="20"
                y="225"
                width="65"
                height="65"
                fill="url(#penoraRed)" />
            </svg>
          </div>
          <div class="nova-header-text">
            <h2>Penora AI Assistant</h2>
            <span class="nova-status"><i class="nova-status-dot"></i>Online — replies instantly</span>
          </div>
        </div>
        <div class="nova-header-actions">
          <button class="nova-icon-btn" id="novaSettingsBtn" title="AI settings" aria-label="AI settings" aria-expanded="false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.6" />
              <path d="M19.4 13.5a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V20a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1.11-1.56 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.56-1.04H4a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 5.65 8.4a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34H10a1.7 1.7 0 0 0 1.04-1.56V2a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1.04 1.56 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87V8a1.7 1.7 0 0 0 1.56 1.04H20a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.56 1.04Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
            </svg>
          </button>
          <button class="nova-icon-btn" id="novaClearBtn" title="Clear conversation" aria-label="Clear conversation">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2m2 0-.8 12.1a2 2 0 0 1-2 1.9H8.8a2 2 0 0 1-2-1.9L6 7h12Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </button>
          <button class="nova-icon-btn" id="novaCloseBtn" title="Close chat" aria-label="Close chat">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
          </button>
        </div>
      </header>

      <!-- AI settings drawer -->
      <div class="nova-settings" id="novaSettings" aria-hidden="true">
        <label class="nova-settings-label" for="novaApiKey">Anthropic API key</label>
        <input type="password" id="novaApiKey" class="nova-settings-input" placeholder="sk-ant-…" autocomplete="off" spellcheck="false">
        <p class="nova-settings-hint">
          Stored only in this browser's local storage, sent directly to Anthropic's API. Get a key at
          <a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener">console.anthropic.com</a>.
          Without a key, Penora AI Assistant falls back to canned demo replies.
        </p>
        <div class="nova-settings-row">
          <button type="button" class="nova-settings-save" id="novaSettingsSave">Save key</button>
          <button type="button" class="nova-settings-clear" id="novaSettingsClear">Remove key</button>
        </div>
        <p class="nova-settings-status" id="novaSettingsStatus"></p>
      </div>

      <div class="nova-messages" id="novaMessages" aria-live="polite"></div>

      <div class="nova-quickreplies" id="novaQuickReplies"></div>

      <form class="nova-inputbar" id="novaForm">
        <textarea
          id="novaInput"
          class="nova-input"
          placeholder="Message Penora AI…"
          rows="1"
          maxlength="500"
          aria-label="Type a message"></textarea>
        <button type="submit" class="nova-send" id="novaSend" aria-label="Send message">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M4 12 20 4l-6 16-2.6-6.4L4 12Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round" />
          </svg>
        </button>
      </form>

      <p class="nova-footer" id="novaFooter">Penora AI Assistant is a scripted demo assistant, not a live agent.</p>
    </section>

    <!-- Launcher button -->
    <button class="nova-launcher" id="novaLauncher" aria-label="Open chat" aria-expanded="false">
      <span class="nova-launcher-ring"></span>
      <span class="nova-unread" id="novaUnread" hidden>1</span>
      <svg class="nova-icon nova-icon-chat" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v8A2.5 2.5 0 0 1 17.5 16H10l-4.6 3.6A.8.8 0 0 1 4 19v-3-1.06A2.5 2.5 0 0 1 4 13.5v-8Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
      </svg>
      <svg class="nova-icon nova-icon-close" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
      </svg>
    </button>
  </div>




  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="assets/js/share.js"></script>
  <script src="assets/js/comments.js"></script>
  <script src="assets/js/likes.js"></script>
  <script src="assets/js/chatbot.js"></script>
  <script src="assets/js/script.js"></script>
</body>

</html>