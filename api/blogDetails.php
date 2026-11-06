<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";
require_once "includes/login_includes/login_view.inc.php";
require_once "includes/dashboard_includes/blog_upload_view.inc.php";

if (!isset($_GET['id'])) {
    die("No post selected.");
}

date_default_timezone_set('Africa/Lagos');
$post_id = (int) $_GET['id'];

/* ============================== POST + AUTHOR ============================== */
$stmt = $pdo->prepare("
    SELECT posts.*, users.firstname, users.lastname, users.file_name, users.id AS author_id
    FROM posts
    JOIN users ON posts.users_id = users.id
    WHERE posts.id = :id
");
$stmt->execute(['id' => $post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    die("Post not found.");
}

/* ============================== LIKE / BOOKMARK STATE ============================== */
$isLiked = $isBookmarked = false;
if (isset($_SESSION['user_id'])) {
    $c = $pdo->prepare("SELECT 1 FROM likes WHERE user_id = :u AND post_id = :p");
    $c->execute(['u' => $_SESSION['user_id'], 'p' => $post_id]);
    $isLiked = (bool) $c->fetchColumn();

    try {
        $b = $pdo->prepare("SELECT 1 FROM bookmarks WHERE user_id = :u AND post_id = :p");
        $b->execute(['u' => $_SESSION['user_id'], 'p' => $post_id]);
        $isBookmarked = (bool) $b->fetchColumn();
    } catch (Throwable $e) { /* table optional */
    }
}

/* ============================== COUNTS ============================== */
$totalLikes    = (int) $pdo->query("SELECT COUNT(*) FROM likes    WHERE post_id = $post_id")->fetchColumn();
$totalComments = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE post_id = $post_id")->fetchColumn();

/* ============================== RECORD VIEW ============================== */
$viewer = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];
$cv = $pdo->prepare("SELECT 1 FROM views WHERE post_id = :p AND viewer = :v");
$cv->execute(['p' => $post_id, 'v' => $viewer]);
if (!$cv->fetchColumn()) {
    $iv = $pdo->prepare("INSERT INTO views (post_id, viewer) VALUES (:p, :v)");
    $iv->execute(['p' => $post_id, 'v' => $viewer]);
}
$totalViews = (int) $pdo->query("SELECT COUNT(*) FROM views WHERE post_id = $post_id")->fetchColumn();

/* ============================== COMMENTS (threaded) ============================== */
$gc = $pdo->prepare("
    SELECT c.*, u.firstname, u.lastname, u.file_name
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = :p
    ORDER BY c.created_at ASC
");
$gc->execute(['p' => $post_id]);
$rows = $gc->fetchAll(PDO::FETCH_ASSOC);

/* Build tree: top-level = parent_id NULL/0 */
$byParent = [];
foreach ($rows as $r) {
    $pid = !empty($r['parent_id']) ? (int)$r['parent_id'] : 0;
    $byParent[$pid][] = $r;
}
$topComments = $byParent[0] ?? [];

/* ============================== RELATED ============================== */
$rel = $pdo->prepare("
    SELECT posts.id, posts.title, posts.post_img, posts.created_at,
           users.firstname, users.lastname
    FROM posts JOIN users ON posts.users_id = users.id
    WHERE posts.id != :id
    ORDER BY posts.created_at DESC LIMIT 3
");
$rel->execute(['id' => $post_id]);
$related = $rel->fetchAll(PDO::FETCH_ASSOC);

/* ============================== HELPERS ============================== */
function readingTime($t)
{
    return max(1, (int) ceil(str_word_count(strip_tags($t)) / 200));
}
function timeAgo($dt)
{
    $d = time() - strtotime($dt);
    if ($d < 60)     return $d . 's ago';
    if ($d < 3600)   return floor($d / 60)   . 'm ago';
    if ($d < 86400)  return floor($d / 3600) . 'h ago';
    if ($d < 604800) return floor($d / 86400) . 'd ago';
    return date('M j, Y', strtotime($dt));
}
function renderComment($c, $byParent)
{
    $name = htmlspecialchars($c['firstname'] . ' ' . $c['lastname']);
    $img  = htmlspecialchars($c['file_name'] ?: 'default.png');
?>
    <article class="comment" data-id="<?= (int)$c['id'] ?>">
        <img class="c-avatar" src="includes/<?= $img ?>" alt="">
        <div class="c-body">
            <header class="c-head">
                <strong><?= $name ?></strong>
                <time>· <?= timeAgo($c['created_at']) ?></time>
            </header>
            <p class="c-text"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
            <div class="c-actions">
                <button type="button" class="c-btn like-mini"><i class="ri-heart-line"></i> Like</button>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button type="button" class="c-btn reply-toggle"><i class="ri-reply-line"></i> Reply</button>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form class="reply-form" hidden>
                    <textarea placeholder="Reply to <?= $name ?>…" maxlength="1000" required></textarea>
                    <div class="rf-actions">
                        <button type="button" class="btn-ghost cancel-reply">Cancel</button>
                        <button type="submit" class="btn-primary"><i class="ri-send-plane-2-line"></i> Reply</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (!empty($byParent[$c['id']])): ?>
                <div class="c-replies">
                    <?php foreach ($byParent[$c['id']] as $child) renderComment($child, $byParent); ?>
                </div>
            <?php endif; ?>
        </div>
    </article>
<?php
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogar | <?= htmlspecialchars($post['title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars(mb_strimwidth(strip_tags($post['report']), 0, 155, '...')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700;9..144,900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/blogDetails.css">
</head>

<body>

    <!-- Navbar -->
    <?php require 'header.php'; ?>

    <main class="container">

        <!-- HERO -->
        <section class="post-hero">
            <div class="post-hero__inner">
                <a href="index.php" class="crumb"><i class="ri-arrow-left-line"></i> Back to feed</a>
                <span class="post-category"><?= htmlspecialchars($post['category']) ?></span>
                <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>

                <div class="post-meta">
                    <div class="author">
                        <img src="includes/<?= htmlspecialchars($post['file_name'] ?: 'default.png') ?>" class="author-avatar" alt="">
                        <div>
                            <p class="author-name"><?= htmlspecialchars($post['firstname'] . ' ' . $post['lastname']) ?></p>
                            <p class="author-sub"><?= date('M j, Y', strtotime($post['created_at'])) ?> · <?= readingTime($post['report']) ?> min read</p>
                        </div>
                    </div>
                    <div class="meta-stats">
                        <span><i class="ri-eye-line"></i> <?= number_format($totalViews) ?></span>
                        <span><i class="ri-heart-line"></i> <?= number_format($totalLikes) ?></span>
                        <span><i class="ri-chat-3-line"></i> <?= number_format($totalComments) ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($post['post_img'])): ?>
                <div class="post-cover">
                    <img src="includes/<?= htmlspecialchars($post['post_img']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                </div>
            <?php endif; ?>
        </section>

        <!-- BODY -->
        <section class="post-body-wrap">
            <aside class="action-rail" aria-label="Post actions">
                <button class="rail-btn like-btn <?= $isLiked ? 'liked' : '' ?>" data-post-id="<?= $post_id ?>" aria-label="Like">
                    <i class="<?= $isLiked ? 'ri-heart-fill' : 'ri-heart-line' ?>"></i>
                    <span class="rail-count" id="likeCount"><?= $totalLikes ?></span>
                </button>
                <a href="#comments" class="rail-btn" aria-label="Comments">
                    <i class="ri-chat-3-line"></i>
                    <span class="rail-count"><?= $totalComments ?></span>
                </a>
                <button class="rail-btn bookmark-btn <?= $isBookmarked ? 'saved' : '' ?>" data-post-id="<?= $post_id ?>" aria-label="Bookmark">
                    <i class="<?= $isBookmarked ? 'ri-bookmark-fill' : 'ri-bookmark-line' ?>"></i>
                </button>
                <button class="rail-btn share-btn" data-share aria-label="Share">
                    <i class="ri-share-forward-line"></i>
                </button>
            </aside>

            <article class="post-body">
                <?= nl2br($post['report']) ?>
            </article>

            <div class="post-tags">
                <span class="tag">#writing</span>
                <span class="tag">#story</span>
                <span class="tag">#blogar</span>
            </div>

            <!-- Mobile action bar -->
            <div class="mobile-actions">
                <button class="ma-btn like-btn <?= $isLiked ? 'liked' : '' ?>" data-post-id="<?= $post_id ?>">
                    <i class="<?= $isLiked ? 'ri-heart-fill' : 'ri-heart-line' ?>"></i>
                    <span id="likeCountMobile"><?= $totalLikes ?></span>
                </button>
                <a href="#comments" class="ma-btn"><i class="ri-chat-3-line"></i><span><?= $totalComments ?></span></a>
                <button class="ma-btn bookmark-btn <?= $isBookmarked ? 'saved' : '' ?>" data-post-id="<?= $post_id ?>">
                    <i class="<?= $isBookmarked ? 'ri-bookmark-fill' : 'ri-bookmark-line' ?>"></i>
                </button>
                <button class="ma-btn share-btn" data-share><i class="ri-share-forward-line"></i></button>
            </div>

            <!-- COMMENTS -->
            <section id="comments" class="comments-section" data-post-id="<?= $post_id ?>">
                <header class="comments-header">
                    <h2>Comments <span class="count-pill" id="commentsCount"><?= $totalComments ?></span></h2>
                    <p>Join the conversation. Be kind, be curious.</p>
                </header>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form id="commentForm" class="comment-form" autocomplete="off">
                        <img src="includes/<?= htmlspecialchars($_SESSION['file_name'] ?? 'default.png') ?>" alt="">
                        <div class="cf-input">
                            <textarea id="commentInput" name="comment" placeholder="Share your thoughts…" required maxlength="1000"></textarea>
                            <div class="cf-actions">
                                <span class="cf-count"><span id="cfCount">0</span>/1000</span>
                                <button type="submit" class="btn-primary"><i class="ri-send-plane-2-line"></i> Post</button>
                            </div>
                        </div>
                        <input type="hidden" name="post_id" value="<?= $post_id ?>">
                    </form>
                <?php else: ?>
                    <div class="comment-login">
                        <p><a href="login.php">Log in</a> to join the conversation.</p>
                    </div>
                <?php endif; ?>

                <div id="commentsList" class="comments-list">
                    <?php if (empty($topComments)): ?>
                        <div class="empty-comments" id="emptyComments">
                            <i class="ri-chat-smile-3-line"></i>
                            <p>No comments yet. Be the first to share your thoughts.</p>
                        </div>
                    <?php else: foreach ($topComments as $c) renderComment($c, $byParent);
                    endif; ?>
                </div>
            </section>

            <!-- RELATED -->
            <?php if (!empty($related)): ?>
                <section class="related">
                    <header class="related-header">
                        <h2>Keep reading</h2>
                        <a href="index.php">See all <i class="ri-arrow-right-line"></i></a>
                    </header>
                    <div class="related-grid">
                        <?php foreach ($related as $r): ?>
                            <a class="related-card" href="blogDetails.php?id=<?= (int)$r['id'] ?>">
                                <div class="rc-thumb"><img src="includes/<?= htmlspecialchars($r['post_img'] ?: 'default.png') ?>" alt=""></div>
                                <div class="rc-body">
                                    <h3><?= htmlspecialchars($r['title']) ?></h3>
                                    <p><?= htmlspecialchars($r['firstname'] . ' ' . $r['lastname']) ?> · <?= date('M j, Y', strtotime($r['created_at'])) ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </section>
    </main>

    <!-- Share sheet -->
    <!-- <div class="share-sheet" id="shareSheet" hidden>
        <div class="share-card">
            <h3>Share this post</h3>
            <div class="share-grid">
                <a class="share-opt" data-net="twitter"><i class="ri-twitter-x-line"></i><span>X</span></a>
                <a class="share-opt" data-net="facebook"><i class="ri-facebook-fill"></i><span>Facebook</span></a>
                <a class="share-opt" data-net="linkedin"><i class="ri-linkedin-fill"></i><span>LinkedIn</span></a>
                <a class="share-opt" data-net="whatsapp"><i class="ri-whatsapp-line"></i><span>WhatsApp</span></a>
                <button class="share-opt" id="copyLink"><i class="ri-link"></i><span>Copy link</span></button>
            </div>
            <button class="share-close" id="shareClose">Close</button>
        </div>
    </div> -->

    <div id="toast" class="toast" hidden></div>

    <script>
        window.__CURRENT_USER_AVATAR = "<?= htmlspecialchars($_SESSION['file_name'] ?? 'default.png') ?>";
    </script>
    <script src="assets/js/blog-details.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>