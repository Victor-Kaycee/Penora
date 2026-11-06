<?php
require_once 'includes/dbh.inc.php';
require_once 'includes/config_session.inc.php';
require_once 'includes/login_includes/login_view.inc.php';
require_once 'includes/dashboard_includes/blog_upload_view.inc.php';

/* ── Profile ID guard ── */
if (empty($_GET['id'])) {
    http_response_code(404);
    die("User not found");
}
$profile_id = (int) $_GET['id'];

/* ── Helper: safe escape ── */
function e($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function fmtNum($n)
{
    $n = (int)$n;
    if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
    if ($n >= 1000)    return round($n / 1000, 1) . 'K';
    return number_format($n);
}

/* ── Fetch profile user ── */
$stmt = $pdo->prepare("
    SELECT id, firstname, lastname, middlename, email,
           file_name, phone_no, location, state, bio, created_at
    FROM users WHERE id = :id LIMIT 1
");
$stmt->execute([':id' => $profile_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    die("Profile not found");
}

/* ── Follow state ── */
$isFollowing  = false;
$isOwnProfile = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $profile_id;
if (isset($_SESSION['user_id']) && !$isOwnProfile) {
    $chk = $pdo->prepare("SELECT id FROM followers WHERE follower_id=:me AND following_id=:p LIMIT 1");
    $chk->execute([':me' => $_SESSION['user_id'], ':p' => $profile_id]);
    $isFollowing = $chk->rowCount() > 0;
}

/* ── Stats ── */
$s = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id=:id");
$s->execute([':id' => $profile_id]);
$totalFollowers = (int)$s->fetchColumn();

$s = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id=:id");
$s->execute([':id' => $profile_id]);
$totalFollowing = (int)$s->fetchColumn();

$s = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE users_id=:id");
$s->execute([':id' => $profile_id]);
$totalPosts = (int)$s->fetchColumn();

$s = $pdo->prepare("SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id=p.id WHERE p.users_id=:id");
$s->execute([':id' => $profile_id]);
$totalLikes = (int)$s->fetchColumn();

/* ── Posts ── */
$stmt = $pdo->prepare("
    SELECT p.*, u.firstname, u.lastname, u.file_name AS user_img
    FROM posts p
    JOIN users u ON p.users_id = u.id
    WHERE p.users_id = :id
    ORDER BY p.created_at DESC
");
$stmt->execute([':id' => $profile_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$postsData = [];
foreach ($posts as $row) {
    $pid = $row['id'];

    $isLiked = false;
    if (isset($_SESSION['user_id'])) {
        $l = $pdo->prepare("SELECT id FROM likes WHERE user_id=:u AND post_id=:p");
        $l->execute([':u' => $_SESSION['user_id'], ':p' => $pid]);
        $isLiked = $l->rowCount() > 0;
    }

    $lc = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id=:p");
    $lc->execute([':p' => $pid]);
    $row['likes'] = (int)$lc->fetchColumn();

    $cc = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id=:p");
    $cc->execute([':p' => $pid]);
    $row['comments'] = (int)$cc->fetchColumn();

    $vc = $pdo->prepare("SELECT COUNT(*) FROM views WHERE post_id=:p");
    $vc->execute([':p' => $pid]);
    $row['views'] = (int)$vc->fetchColumn();

    $row['is_liked'] = $isLiked;


    $diff = time() - strtotime($row['created_at']);
    if ($diff < 60)        $row['time_ago'] = 'Just now';
    elseif ($diff < 3600)  $row['time_ago'] = floor($diff / 60)  . ' min ago';
    elseif ($diff < 86400) $row['time_ago'] = floor($diff / 3600) . ' hrs ago';
    else                   $row['time_ago'] = date('j M Y', strtotime($row['created_at']));

    $postsData[] = $row;
}

/* ── Suggested people (excluding self & already-followed) ── */
$suggested = [];
try {
    $sq = $pdo->prepare("
        SELECT id, firstname, lastname, middlename, file_name, location
        FROM users
        WHERE id != :pid
        ORDER BY RAND()
        LIMIT 5
    ");
    $sq->execute([':pid' => $profile_id]);
    $suggested = $sq->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) {
    $suggested = [];
}

$fullname = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
$avatar   = !empty($user['file_name']) ? 'includes/' . $user['file_name'] : 'assets/img/avatar-placeholder.png';
$joined   = !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogar | <?= e($fullname) ?></title>
    <meta name="description" content="<?= e($fullname) ?>'s profile on Blogar — <?= e(mb_strimwidth($user['bio'] ?? 'View posts and activity.', 0, 140, '…')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/profile.css">
</head>

<body>

    <div class="page-root container">
        <?php require 'header.php' ?>

        <!-- Hero banner -->
        <section class="hero-banner" aria-hidden="true">
            <div class="hero-gradient"></div>
            <div class="hero-noise"></div>
        </section>

        <div class="profile-shell container">

            <!-- ═══════════ MAIN COLUMN ═══════════ -->
            <main class="col-main">

                <!-- Profile card -->
                <section class="pcard">
                    <div class="pcard-top">
                        <div class="avatar-wrap">
                            <img src="<?= e($avatar) ?>" alt="<?= e($fullname) ?>" class="avatar" onclick="openLightbox(this.src)">
                            <span class="online-dot" title="Online"></span>
                        </div>

                        <div class="pcard-actions">
                            <?php if ($isOwnProfile): ?>
                                <a href="settings.php" class="action-btn action-edit"><i class="ri-edit-2-line"></i><span>Edit Profile</span></a>
                                <button class="action-btn action-msg" onclick="sharePost()"><i class="ri-share-line"></i><span>Share</span></button>
                            <?php else: ?>
                                <button class="action-btn <?= $isFollowing ? 'action-following' : 'action-follow' ?>" onclick="toggleFollow(this)">
                                    <?php if ($isFollowing): ?>
                                        <i class="ri-user-follow-fill"></i><span>Following</span>
                                    <?php else: ?>
                                        <i class="ri-user-add-line"></i><span>Follow</span>
                                    <?php endif; ?>
                                </button>
                                <button class="action-btn action-msg" onclick="openMsg()"><i class="ri-mail-line"></i><span>Message</span></button>
                                <button class="action-btn action-edit" onclick="sharePost()" aria-label="Share profile"><i class="ri-share-line"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="pcard-body">
                        <h1 class="p-name"><?= e($fullname) ?></h1>
                        <div class="p-handle">@<?= e($user['middlename'] ?: strtolower(($user['firstname'] ?? 'user'))) ?></div>

                        <?php if (!empty($user['bio'])): ?>
                            <p class="p-bio"><?= nl2br(e($user['bio'])) ?></p>
                        <?php endif; ?>

                        <div class="p-meta-row">
                            <?php if (!empty($user['location'])): ?>
                                <span class="p-meta-item"><i class="ri-map-pin-line"></i><?= e($user['location']) ?><?= !empty($user['state']) ? ', ' . e($user['state']) : '' ?></span>
                            <?php endif; ?>
                            <?php if (!empty($user['email'])): ?>
                                <span class="p-meta-item"><i class="ri-mail-line"></i><?= e($user['email']) ?></span>
                            <?php endif; ?>
                            <?php if ($joined): ?>
                                <span class="p-meta-item"><i class="ri-calendar-line"></i>Joined <?= e($joined) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="stats-strip">
                        <div class="stat-item">
                            <span class="stat-n" id="followerCount"><?= fmtNum($totalFollowers) ?></span>
                            <span class="stat-l">Followers</span>
                        </div>
                        <span class="stat-div"></span>
                        <div class="stat-item">
                            <span class="stat-n"><?= fmtNum($totalFollowing) ?></span>
                            <span class="stat-l">Following</span>
                        </div>
                        <span class="stat-div"></span>
                        <div class="stat-item">
                            <span class="stat-n"><?= fmtNum($totalPosts) ?></span>
                            <span class="stat-l">Posts</span>
                        </div>
                        <span class="stat-div"></span>
                        <div class="stat-item">
                            <span class="stat-n"><?= fmtNum($totalLikes) ?></span>
                            <span class="stat-l">Likes earned</span>
                        </div>
                    </div>
                </section>

                <!-- Tab nav -->
                <nav class="tab-nav" role="tablist" aria-label="Profile sections">
                    <button class="tab-btn active" role="tab" data-tab="posts"><i class="ri-article-line"></i>Posts</button>
                    <button class="tab-btn" role="tab" data-tab="about"><i class="ri-user-line"></i>About</button>
                    <button class="tab-btn" role="tab" data-tab="activity"><i class="ri-pulse-line"></i>Activity</button>
                    <button class="tab-btn" role="tab" data-tab="media"><i class="ri-image-line"></i>Media</button>
                </nav>

                <!-- ── POSTS TAB ── -->
                <section class="tab-panel active section-card" id="tab-posts" role="tabpanel">
                    <div class="section-head">
                        <h2 class="section-title">Posts</h2>
                        <span class="post-count-badge"><?= count($postsData) ?></span>
                    </div>

                    <?php if (empty($postsData)): ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="ri-quill-pen-line"></i></div>
                            <h3>No posts yet</h3>
                            <p>When <?= $isOwnProfile ? 'you publish' : e($user['firstname']) . ' publishes' ?> a post, it will appear here.</p>
                            <?php if ($isOwnProfile): ?>
                                <a href="user_dashboard.php" class="btn-write"><i class="ri-add-line"></i>Write a post</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="posts-list">
                            <?php foreach ($postsData as $post):
                                $thumb = !empty($post['file_name']) ? 'includes/' . $post['file_name'] : '';
                                $authorImg = !empty($post['user_img']) ? 'includes/' . $post['user_img'] : 'assets/img/avatar-placeholder.png';
                            ?>
                                <article class="post-card" data-id="<?= (int)$post['id'] ?>">
                                    <?php if ($isOwnProfile): ?>
                                        <div class="post-menu-wrap">
                                            <button class="post-menu-btn" onclick="toggleMenu(event, this)" aria-label="Post options"><i class="ri-more-2-fill"></i></button>
                                            <div class="post-dropdown">
                                                <a href="blog_edit.php?id=<?= (int)$post['id'] ?>" class="drop-item"><i class="ri-edit-line"></i>Edit</a>
                                                <button class="drop-item drop-delete" onclick="confirmDelete(<?= (int)$post['id'] ?>)"><i class="ri-delete-bin-line"></i>Delete</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="post-body">
                                        <?php if (!empty($post['category'])): ?>
                                            <a href="category.php?category=<?= urlencode($post['category']) ?>" class="post-cat"><?= e($post['category']) ?></a>
                                        <?php endif; ?>
                                        <a href="blogDetails.php?id=<?= (int)$post['id'] ?>" class="post-title"><?= e($post['title'] ?? 'Untitled') ?></a>

                                        <div class="post-byline">
                                            <img src="<?= e($authorImg) ?>" alt="" class="post-avatar">
                                            <div class="post-byline-text">
                                                <span><?= e(trim($post['firstname'] . ' ' . $post['lastname'])) ?></span>
                                                <time><?= e($post['time_ago']) ?></time>
                                            </div>
                                        </div>

                                        <div class="post-actions">
                                            <button class="pact like-btn <?= $post['is_liked'] ? 'liked' : '' ?>" onclick="handleLike(this)">
                                                <input type="hidden" class="post-id" value="<?= (int)$post['id'] ?>">
                                                <i class="<?= $post['is_liked'] ? 'ri-heart-fill' : 'ri-heart-line' ?>"></i>
                                                <span class="like-count"><?= (int)$post['likes'] ?></span>
                                            </button>
                                            <a href="blogDetails.php?id=<?= (int)$post['id'] ?>#comments" class="pact"><i class="ri-chat-3-line"></i><span><?= (int)$post['comments'] ?></span></a>
                                            <span class="pact"><i class="ri-eye-line"></i><span><?= fmtNum($post['views']) ?></span></span>
                                            <button class="pact" onclick="sharePost()" aria-label="Share"><i class="ri-share-forward-line"></i></button>
                                        </div>
                                        <img class="post-img" src="includes/<?= e($post['post_img']) ?>" alt="">
                                    </div>


                                    <?php if ($thumb): ?>
                                        <a href="blogDetails.php?id=<?= (int)$post['id'] ?>" class="post-thumb">
                                            <img src="<?= e($thumb) ?>" alt="" loading="lazy">
                                        </a>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- ── ABOUT TAB ── -->
                <section class="tab-panel section-card" id="tab-about" role="tabpanel" hidden>
                    <div class="section-head">
                        <h2 class="section-title">About <?= e($user['firstname']) ?></h2>
                    </div>
                    <div class="about-pane">
                        <?php if (!empty($user['bio'])): ?>
                            <p class="about-bio"><?= nl2br(e($user['bio'])) ?></p>
                        <?php else: ?>
                            <p class="about-bio muted">No biography added yet.</p>
                        <?php endif; ?>
                        <ul class="about-details">
                            <?php if (!empty($user['email'])): ?><li class="about-row"><i class="ri-mail-line"></i><span><?= e($user['email']) ?></span></li><?php endif; ?>
                            <?php if (!empty($user['phone_no'])): ?><li class="about-row"><i class="ri-phone-line"></i><span><?= e($user['phone_no']) ?></span></li><?php endif; ?>
                            <?php if (!empty($user['location'])): ?><li class="about-row"><i class="ri-map-pin-line"></i><span><?= e($user['location']) ?><?= !empty($user['state']) ? ', ' . e($user['state']) : '' ?></span></li><?php endif; ?>
                            <?php if ($joined): ?><li class="about-row"><i class="ri-calendar-line"></i><span>Joined <?= e($joined) ?></span></li><?php endif; ?>
                        </ul>
                    </div>
                </section>

                <!-- ── ACTIVITY TAB ── -->
                <section class="tab-panel section-card" id="tab-activity" role="tabpanel" hidden>
                    <div class="section-head">
                        <h2 class="section-title">Recent activity</h2>
                    </div>
                    <ul class="timeline">
                        <?php
                        $recent = array_slice($postsData, 0, 5);
                        if (empty($recent)):
                        ?>
                            <li class="empty-state">
                                <p>No recent activity.</p>
                            </li>
                            <?php else: foreach ($recent as $r): ?>
                                <li class="tl-item">
                                    <span class="tl-dot"><i class="ri-quill-pen-line"></i></span>
                                    <div>
                                        <strong>Published</strong> <a href="blogDetails.php?id=<?= (int)$r['id'] ?>"><?= e($r['title'] ?? 'Untitled') ?></a>
                                        <time><?= e($r['time_ago']) ?></time>
                                    </div>
                                </li>
                        <?php endforeach;
                        endif; ?>
                    </ul>
                </section>

                <!-- ── MEDIA TAB ── -->
                <section class="tab-panel section-card" id="tab-media" role="tabpanel" hidden>
                    <div class="section-head">
                        <h2 class="section-title">Media</h2>
                    </div>
                    <div class="media-grid">
                        <?php
                        $mediaPosts = array_filter($postsData, fn($p) => !empty($p['file_name']));
                        if (empty($mediaPosts)):
                        ?>
                            <div class="empty-state" style="grid-column:1/-1">
                                <p>No media shared yet.</p>
                            </div>
                            <?php else: foreach ($mediaPosts as $m): $src = 'includes/' . $m['file_name']; ?>
                                <button class="media-tile" onclick="openLightbox('<?= e($src) ?>')">
                                    <img src="<?= e($src) ?>" alt="" loading="lazy">
                                </button>
                        <?php endforeach;
                        endif; ?>
                    </div>
                </section>

            </main>

            <!-- ═══════════ SIDEBAR ═══════════ -->
            <aside class="col-side">

                <!-- Achievements (new section) -->
                <section class="side-card">
                    <h3 class="side-title">Achievements</h3>
                    <ul class="badge-list">
                        <li class="badge<?= $totalPosts >= 1 ? ' earned' : '' ?>"><i class="ri-quill-pen-fill"></i><span>First Post</span></li>
                        <li class="badge<?= $totalFollowers >= 100 ? ' earned' : '' ?>"><i class="ri-group-fill"></i><span>Centurion</span></li>
                        <li class="badge<?= $totalLikes >= 50 ? ' earned' : '' ?>"><i class="ri-heart-pulse-fill"></i><span>Top Voice</span></li>
                        <li class="badge<?= $totalPosts >= 10 ? ' earned' : '' ?>"><i class="ri-fire-fill"></i><span>On Fire</span></li>
                    </ul>
                </section>

                <!-- About (sidebar, hidden on mobile) -->
                <section class="side-card about-card">
                    <h3 class="side-title">About</h3>
                    <?php if (!empty($user['bio'])): ?>
                        <p class="about-bio"><?= nl2br(e(mb_strimwidth($user['bio'], 0, 200, '…'))) ?></p>
                    <?php endif; ?>
                    <div class="about-details">
                        <?php if (!empty($user['location'])): ?><div class="about-row"><i class="ri-map-pin-line"></i><span><?= e($user['location']) ?></span></div><?php endif; ?>
                        <?php if (!empty($user['email'])): ?><div class="about-row"><i class="ri-mail-line"></i><span><?= e($user['email']) ?></span></div><?php endif; ?>
                        <?php if ($joined): ?><div class="about-row"><i class="ri-calendar-line"></i><span>Joined <?= e($joined) ?></span></div><?php endif; ?>
                    </div>
                </section>

                <!-- People you may know -->
                <section class="side-card">
                    <h3 class="side-title">People you may know</h3>
                    <ul class="people-list">
                        <?php foreach ($suggested as $i => $sg):
                            $sName = trim($sg['firstname'] . ' ' . $sg['lastname']);
                            $sImg  = !empty($sg['file_name']) ? 'includes/' . $sg['file_name'] : 'assets/img/avatar-placeholder.png';
                        ?>
                            <li class="person<?= $i >= 3 ? ' hidden-person' : '' ?>" onclick="window.location='profile.php?id=<?= (int)$sg['id'] ?>'">
                                <img src="<?= e($sImg) ?>" alt="">
                                <div class="person-info">
                                    <strong class="person-name"><?= e($sName) ?></strong>
                                    <span><?= e($sg['location'] ?: 'Member') ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($suggested) > 3): ?>
                        <button class="see-more-btn" id="seeMoreBtn" onclick="togglePeople()">See more <i class="ri-arrow-down-s-line"></i></button>
                    <?php endif; ?>
                </section>

                <!-- Share card -->
                <section class="side-card">
                    <h3 class="side-title">Share profile</h3>
                    <div class="share-row">
                        <button class="share-btn" onclick="sharePost()" aria-label="Copy link"><i class="ri-link"></i></button>
                        <a class="share-btn" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url=<?= urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" aria-label="Twitter"><i class="ri-twitter-x-line"></i></a>
                        <a class="share-btn" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" aria-label="Facebook"><i class="ri-facebook-fill"></i></a>
                        <a class="share-btn" target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" aria-label="LinkedIn"><i class="ri-linkedin-fill"></i></a>
                    </div>
                </section>

            </aside>
        </div>
    </div>

    <!-- Message modal -->
    <div class="overlay" id="msgOverlay" onclick="closeMsg()"></div>
    <div class="modal msg-modal" id="msgModal" role="dialog" aria-modal="true" aria-labelledby="msgTitle">
        <div class="modal-head">
            <h3 id="msgTitle">Send Message</h3>
            <button onclick="closeMsg()" aria-label="Close"><i class="ri-close-line"></i></button>
        </div>
        <form action="send_message.php" method="POST">
            <input
                type="hidden"
                name="receiver_id"
                value="<?= $profile_id ?>">
            <textarea name="message" placeholder="Write your message to <?= e($user['firstname']) ?>…" required></textarea>
            <button type="submit" class="modal-send-btn"><i class="ri-send-plane-fill"></i> Send</button>
        </form>
    </div>

    <!-- Lightbox -->
    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <button class="lb-close" onclick="closeLightbox()" aria-label="Close"><i class="ri-close-line"></i></button>
        <img id="lbImg" alt="">
    </div>

    <!-- Toast -->
    <div class="copy-toast" id="copyToast">Link copied!</div>

    <script>
        const LOGGED_IN = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
        const PROFILE_ID = <?= (int)$profile_id ?>;
    </script>

    <script src="assets/js/message.js"></script>
    <script src="assets/js/follow.js"></script>
    <script src="assets/js/share.js"></script>
    <script src="assets/js//comments.js"></script>
    <script src="assets/js/likes.js"></script>
    <script src="assets/js/profile.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>