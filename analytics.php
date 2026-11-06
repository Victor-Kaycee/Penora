<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";
require_once "includes/dashboard_includes/blog_upload_view.inc.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"]; // ✅ ADD THIS LINE

// ── Core stats ────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_blogs FROM posts WHERE users_id = :uid");
$stmt->execute([':uid' => $user_id]);
$total_blogs = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_likes FROM likes INNER JOIN posts ON likes.post_id = posts.id WHERE posts.users_id = :uid");
$stmt->execute([':uid' => $user_id]);
$total_likes = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_comments FROM comments WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$total_comments = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_views FROM views INNER JOIN posts ON views.post_id = posts.id WHERE posts.users_id = :uid");
$stmt->execute([':uid' => $user_id]);
$total_views = $stmt->fetchColumn();

// ── Followers / Following ─────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id = :uid");
$stmt->execute([':uid' => $user_id]);
$total_followers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = :uid");
$stmt->execute([':uid' => $user_id]);
$total_following = $stmt->fetchColumn();

// ── Top 5 posts by views ──────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.created_at,
           COUNT(v.id) AS view_count,
           COUNT(DISTINCT l.id) AS like_count
    FROM posts p
    LEFT JOIN views v ON v.post_id = p.id
    LEFT JOIN likes l ON l.post_id = p.id
    WHERE p.users_id = :uid
    GROUP BY p.id
    ORDER BY view_count DESC
    LIMIT 4
");
$stmt->execute([':uid' => $user_id]);
$top_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Recent 5 comments on user's posts ────────────────────────
$stmt = $pdo->prepare("
    SELECT c.comment, c.created_at,
           u.firstname, u.file_name,
           p.title AS post_title
    FROM comments c
    INNER JOIN posts p ON c.post_id = p.id
    INNER JOIN users u ON c.user_id = u.id
    WHERE p.users_id = :uid
    ORDER BY c.created_at DESC
    LIMIT 3
");
$stmt->execute([':uid' => $user_id]);
$recent_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Weekly views for chart (last 7 days) ─────────────────────
$weekly_views = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM views v
        INNER JOIN posts p ON v.post_id = p.id
        WHERE p.users_id = :uid AND DATE(v.created_at) = :date
    ");
    $stmt->execute([':uid' => $user_id, ':date' => $date]);
    $weekly_views[] = (int) $stmt->fetchColumn();
}

// ── Weekly likes for chart (last 7 days) ─────────────────────
$weekly_likes = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM likes l
        INNER JOIN posts p ON l.post_id = p.id
        WHERE p.users_id = :uid AND DATE(l.created_at) = :date
    ");
    $stmt->execute([':uid' => $user_id, ':date' => $date]);
    $weekly_likes[] = (int) $stmt->fetchColumn();
}

$chart_data = json_encode(['views' => $weekly_views, 'likes' => $weekly_likes]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogar | Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/analytics.css">
</head>

<body>
    <div class="layout">
        <?php require 'sidebar.php'; ?>

        <div class="main">
            <?php $pageTitle = "Analytics";
            include "navbar.php"; ?>

            <div class="container">

                <!-- ══ ROW 1 · Stat Cards ══════════════════════════════════ -->
                <div class="stat-cards-row">

                    <div class="stat-card white-card">
                        <div class="stat-top">
                            <h1 class="stat-num"><?= $total_blogs ?></h1>
                            <div class="stat-icon"><i class="ri-news-fill"></i></div>
                        </div>
                        <p class="stat-label">Blogs Posted</p>
                        <span class="stat-delta negative"><i class="ri-arrow-down-s-fill"></i> 2% than last month</span>
                    </div>

                    <div class="stat-card red-card">
                        <div class="stat-top">
                            <h1 class="stat-num"><?= $total_likes ?></h1>
                            <div class="stat-icon"><i class="ri-heart-fill"></i></div>
                        </div>
                        <p class="stat-label">Total Likes</p>
                        <span class="stat-delta positive"><i class="ri-arrow-up-s-fill"></i> 0.5% than last month</span>
                    </div>

                    <div class="stat-card dark-card">
                        <div class="stat-top">
                            <h1 class="stat-num"><?= $total_views ?></h1>
                            <div class="stat-icon"><i class="ri-eye-fill"></i></div>
                        </div>
                        <p class="stat-label">Total Views</p>
                        <span class="stat-delta positive"><i class="ri-arrow-up-s-fill"></i> 0.5% than last month</span>
                    </div>

                    <div class="stat-card comment-card">
                        <div class="stat-top">
                            <h1 class="stat-num"><?= $total_comments ?></h1>
                            <div class="stat-icon"><i class="ri-chat-3-fill"></i></div>
                        </div>
                        <p class="stat-label">Comments</p>
                        <span class="stat-delta neutral"><i class="ri-subtract-fill"></i> No change</span>
                    </div>

                </div>

                <!-- ══ ROW 2 · Chart + Social ══════════════════════════════ -->
                <div class="row-2">

                    <!-- Chart Card -->
                    <div class="card chart-card">
                        <div class="balance-header">
                            <div>
                                <h2>Weekly Activity</h2>
                                <p class="chart-subtitle">Views &amp; likes over the last 7 days</p>
                            </div>
                            <div class="chart-legend">
                                <span class="legend-dot dark"></span> Views
                                <span class="legend-dot red"></span> Likes
                            </div>
                        </div>
                        <div class="chart-wrap">
                            <div class="y-labels" id="yLabels"></div>
                            <div class="chart-inner">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>
                        <div class="x-labels">
                            <?php
                            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            for ($i = 6; $i >= 0; $i--) {
                                echo '<span>' . $days[date('w', strtotime("-$i days"))] . '</span>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Social Card -->
                    <div class="card social-card">
                        <h2 class="card-title">Social</h2>

                        <div class="social-row">
                            <div class="social-block followers-block">
                                <div class="social-icon-wrap followers-icon">
                                    <i class="ri-group-fill"></i>
                                </div>
                                <div class="social-info">
                                    <span class="social-num"><?= number_format($total_followers) ?></span>
                                    <span class="social-lbl">Followers</span>
                                </div>
                                <!-- <a href="followers.php" class="social-link">
                                    <i class="ri-arrow-right-line"></i>
                                </a> -->
                            </div>

                            <div class="social-divider"></div>

                            <div class="social-block following-block">
                                <div class="social-icon-wrap following-icon">
                                    <i class="ri-user-follow-fill"></i>
                                </div>
                                <div class="social-info">
                                    <span class="social-num"><?= number_format($total_following) ?></span>
                                    <span class="social-lbl">Following</span>
                                </div>
                                <!-- <a href="following.php" class="social-link">
                                    <i class="ri-arrow-right-line"></i>
                                </a> -->
                            </div>
                        </div>

                        <!-- Engagement rate -->
                        <div class="engagement-wrap">
                            <div class="engagement-header">
                                <span class="eng-label">Engagement Rate</span>
                                <?php
                                $eng = $total_views > 0
                                    ? round(($total_likes + $total_comments) / $total_views * 100, 1)
                                    : 0;
                                ?>
                                <span class="eng-value"><?= $eng ?>%</span>
                            </div>
                            <div class="eng-bar-bg">
                                <div class="eng-bar-fill" style="width: <?= min($eng, 100) ?>%"></div>
                            </div>
                            <p class="eng-note">Based on views, likes &amp; comments</p>
                        </div>

                    </div>

                </div>

                <!-- ══ ROW 3 · Top Posts + Recent Comments ═════════════════ -->
                <div class="row-3">

                    <!-- Top Posts -->
                    <div class="card top-posts-card">
                        <div class="card-head">
                            <h2 class="card-title">Top Posts</h2>
                            <a href="user_blogs.php" class="see-all">See all <i class="ri-arrow-right-s-line"></i></a>
                        </div>

                        <?php if (empty($top_posts)): ?>
                            <div class="empty-state">
                                <i class="ri-file-text-line"></i>
                                <p>No posts yet</p>
                            </div>
                        <?php else: ?>
                            <div class="posts-list">
                                <?php foreach ($top_posts as $idx => $post): ?>
                                    <div class="post-row">
                                        <div class="post-rank"><?= $idx + 1 ?></div>
                                        <div class="post-info">
                                            <span class="post-title-text"><?= htmlspecialchars($post['title']) ?></span>
                                            <span class="post-date"><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                                        </div>
                                        <div class="post-metas">
                                            <span class="meta-pill views-pill">
                                                <i class="ri-eye-line"></i> <?= number_format($post['view_count']) ?>
                                            </span>
                                            <span class="meta-pill likes-pill">
                                                <i class="ri-heart-line"></i> <?= number_format($post['like_count']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Comments -->
                    <div class="card recent-comments-card">
                        <div class="card-head">
                            <h2 class="card-title">Recent Comments</h2>
                        </div>

                        <?php if (empty($recent_comments)): ?>
                            <div class="empty-state">
                                <i class="ri-chat-3-line"></i>
                                <p>No comments yet</p>
                            </div>
                        <?php else: ?>
                            <div class="comments-list">
                                <?php foreach ($recent_comments as $c): ?>
                                    <div class="comment-row">
                                        <div class="comment-avatar">
                                            <?php if (!empty($c['file_name'])): ?>
                                                <img src="includes/<?= htmlspecialchars($c['file_name']) ?>" alt="<?= htmlspecialchars($c['firstname']) ?>">
                                            <?php else: ?>
                                                <div class="avatar-fallback"><?= strtoupper(substr($c['firstname'], 0, 1)) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="comment-body">
                                            <div class="comment-meta">
                                                <strong><?= htmlspecialchars($c['firstname']) ?></strong>
                                                <span class="comment-time"><?= date('M j', strtotime($c['created_at'])) ?></span>
                                            </div>
                                            <p class="comment-text"><?= htmlspecialchars(mb_strimwidth($c['comment'], 0, 80, '…')) ?></p>
                                            <span class="comment-on">on <em><?= htmlspecialchars(mb_strimwidth($c['post_title'], 0, 40, '…')) ?></em></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
                <!-- end .container -->
            </div>
        </div>
    </div>

    <script>
        const chartData = <?= $chart_data ?>;
        window.chartData = chartData;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/analytics.js"></script>
    <script src="assets/js/user_dashboard.js"></script>
</body>

</html>