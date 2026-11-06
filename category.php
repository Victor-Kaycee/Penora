<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="assets/js/share.js"></script>
<script src="assets/js/comments.js"></script>
<script src="assets/js/likes.js"></script>
<script src="assets/js/script.js"></script>



<?php

/*
 * Blogar category feed
 *
 * This page keeps the include and database conventions from the original
 * category template, but makes the page safe to render while the surrounding
 * Blogar includes are being wired up.
 */
$includeFiles = [
    __DIR__ . '/includes/dbh.inc.php',
    __DIR__ . '/includes/config_session.inc.php',
    __DIR__ . '/includes/login_includes/login_view.inc.php',
    __DIR__ . '/includes/dashboard_includes/blog_upload_view.inc.php',
];

foreach ($includeFiles as $includeFile) {
    if (is_file($includeFile)) {
        require_once $includeFile;
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function postImage(?string $path): string
{
    if (!$path) {
        return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800"%3E%3Crect width="1200" height="800" fill="%2313142b"/%3E%3Ccircle cx="920" cy="160" r="260" fill="%23ec3d2f" opacity=".8"/%3E%3Ccircle cx="240" cy="680" r="330" fill="%23d7ff64" opacity=".85"/%3E%3C/svg%3E';
    }

    return 'includes/' . ltrim($path, '/');
}

function timeLabel(?string $createdAt): string
{
    $created = $createdAt ? strtotime($createdAt) : false;

    if (!$created) {
        return 'Recently';
    }

    $difference = max(0, time() - $created);

    if ($difference < 60) {
        return 'Just now';
    }

    if ($difference < 3600) {
        return floor($difference / 60) . ' min read';
    }

    if ($difference < 86400) {
        return floor($difference / 3600) . ' hr read';
    }

    return date('j M Y', $created);
}

$category = trim((string) filter_input(INPUT_GET, 'category', FILTER_UNSAFE_RAW));
$category = $category !== '' ? $category : 'All stories';

$categoryLinks = [
    'All stories',
    'Tech',
    'Sport',
    'Fitness',
    'Business',
    'Entertainment',
    'Health',
    'Gaming',
    'Automobile',
    'Animals',
    'Food',
    'Fashion',
];

$posts = [];
$databaseMessage = null;

if (isset($pdo) && $pdo instanceof PDO && $category !== 'All stories') {
    $query = <<<'SQL'
        SELECT
            posts.*,
            users.firstname,
            users.lastname,
            users.file_name,
            (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS total_likes,
            (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS total_comments,
            (SELECT COUNT(*) FROM views WHERE views.post_id = posts.id) AS total_views,
            EXISTS (
                SELECT 1
                FROM likes viewer_like
                WHERE viewer_like.post_id = posts.id
                AND viewer_like.user_id = :viewer_id
            ) AS is_liked
        FROM posts
        JOIN users ON posts.users_id = users.id
        WHERE posts.category = :category
        ORDER BY posts.created_at DESC
    SQL;

    $statement = $pdo->prepare($query);
    $statement->execute([
        'category' => $category,
        'viewer_id' => $_SESSION['user_id'] ?? 0,
    ]);
    $posts = $statement->fetchAll(PDO::FETCH_ASSOC);
} elseif (isset($pdo) && $pdo instanceof PDO) {
    $query = <<<'SQL'
        SELECT
            posts.*,
            users.firstname,
            users.lastname,
            users.file_name,
            (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS total_likes,
            (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS total_comments,
            (SELECT COUNT(*) FROM views WHERE views.post_id = posts.id) AS total_views,
            EXISTS (
                SELECT 1
                FROM likes viewer_like
                WHERE viewer_like.post_id = posts.id
                AND viewer_like.user_id = :viewer_id
            ) AS is_liked
        FROM posts
        JOIN users ON posts.users_id = users.id
        ORDER BY posts.created_at DESC
        LIMIT 24
    SQL;

    $statement = $pdo->prepare($query);
    $statement->execute([
        'viewer_id' => $_SESSION['user_id'] ?? 0,
    ]);
    $posts = $statement->fetchAll(PDO::FETCH_ASSOC);
} else {
    $databaseMessage = 'Connect the Blogar database to load stories in this category.';
}

$postCount = count($posts);
$activeCategory = $category === 'All stories' ? 'All stories' : $category;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($activeCategory) ?> stories from Blogar">
    <title>Blogar / <?= e($activeCategory) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/category.css">
</head>

<body>
    <div class="site-shell">
        <?php
        include './header.php';
        ?>

        <main>

            <section class="category-bar" aria-label="Story categories">
                <div class="category-scroll">
                    <?php foreach ($categoryLinks as $categoryLink): ?>
                        <?php $isCurrent = $activeCategory === $categoryLink; ?>
                        <a class="category-pill <?= $isCurrent ? 'is-current' : '' ?>"
                            href="category.php?category=<?= urlencode($categoryLink) ?>"
                            <?= $isCurrent ? 'aria-current="page"' : '' ?>>
                            <?= e($categoryLink) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

            </section>

            <?php if ($databaseMessage): ?>
                <section class="empty-state">
                    <div class="empty-icon"><i class="ri-database-2-line" aria-hidden="true"></i></div>
                    <p class="eyebrow">Almost there</p>
                    <h2>Your next story is waiting.</h2>
                    <p><?= e($databaseMessage) ?></p>
                </section>
            <?php elseif ($posts): ?>
                <?php $featuredPost = $posts[0]; ?>
                <section class="content-grid">
                    <div class="story-column">
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow">Fresh from the community</p>
                                <h2>Worth your time</h2>
                            </div>
                            <span class="result-count"><?= str_pad((string) $postCount, 2, '0', STR_PAD_LEFT) ?> stories</span>
                        </div>

                        <article class="featured-story">
                            <a class="featured-image-wrap" href="blogDetails.php?id=<?= (int) $featuredPost['id'] ?>">
                                <img src="<?= e(postImage($featuredPost['post_img'] ?? null)) ?>"
                                    alt="<?= e($featuredPost['title']) ?>"
                                    class="featured-image"
                                    loading="eager">
                                <!-- <span class="image-label">Featured story</span> -->
                            </a>
                            <div class="featured-copy">
                                <a class="story-category" href="category.php?category=<?= urlencode((string) $featuredPost['category']) ?>">
                                    <?= e($featuredPost['category']) ?>
                                </a>
                                <a href="blogDetails.php?id=<?= (int) $featuredPost['id'] ?>">
                                    <h3><?= e($featuredPost['title']) ?></h3>
                                </a>
                                <p class="story-excerpt">
                                    <?= e($featuredPost['excerpt'] ?? 'A fresh perspective from the Blogar community.') ?>
                                </p>
                                <div class="story-footer">
                                    <a class="author" href="profile.php?id=<?= (int) $featuredPost['users_id'] ?>">
                                        <img src="<?= e(postImage($featuredPost['file_name'] ?? null)) ?>" alt="">
                                        <span>
                                            <strong><?= e(trim(($featuredPost['firstname'] ?? '') . ' ' . ($featuredPost['lastname'] ?? ''))) ?></strong>
                                            <small><?= e(timeLabel($featuredPost['created_at'] ?? null)) ?></small>
                                        </span>
                                    </a>
                                    <div class="story-stats">
                                        <span><i class="ri-heart-3-line" aria-hidden="true"></i> <?= (int) ($featuredPost['total_likes'] ?? 0) ?></span>
                                        <span><i class="ri-chat-3-line" aria-hidden="true"></i> <?= (int) ($featuredPost['total_comments'] ?? 0) ?></span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <div class="story-list">
                            <?php foreach (array_slice($posts, 1) as $post): ?>
                                <article class="story-card">
                                    <a class="story-card-image" href="blogDetails.php?id=<?= (int) $post['id'] ?>">
                                        <img src="<?= e(postImage($post['post_img'] ?? null)) ?>"
                                            alt="<?= e($post['title']) ?>"
                                            loading="lazy">
                                    </a>
                                    <div class="story-card-copy">
                                        <a class="story-category" href="category.php?category=<?= urlencode((string) $post['category']) ?>">
                                            <?= e($post['category']) ?>
                                        </a>
                                        <a href="blogDetails.php?id=<?= (int) $post['id'] ?>">
                                            <h3><?= e($post['title']) ?></h3>
                                        </a>
                                        <div class="story-card-meta">
                                            <a class="author author-compact" href="profile.php?id=<?= (int) $post['users_id'] ?>">
                                                <img src="<?= e(postImage($post['file_name'] ?? null)) ?>" alt="">
                                                <span><?= e(trim(($post['firstname'] ?? '') . ' ' . ($post['lastname'] ?? ''))) ?></span>
                                            </a>
                                            <span class="read-time"><?= e(timeLabel($post['created_at'] ?? null)) ?></span>
                                        </div>
                                    </div>
                                    <button class="save-button" type="button" aria-label="Save <?= e($post['title']) ?>" aria-pressed="false">
                                        <i class="ri-bookmark-line" aria-hidden="true"></i>
                                    </button>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <aside class="sidebar">
                        <div class="sidebar-block newsletter-block">
                            <span class="sidebar-number">01</span>
                            <p class="eyebrow">A little something good</p>
                            <h2>Ideas worth keeping.</h2>
                            <p>One thoughtful note in your inbox, every Sunday morning.</p>
                            <form class="newsletter-form" action="subscribe.php" method="post">
                                <label class="sr-only" for="newsletter-email">Your email address</label>
                                <input id="newsletter-email" name="email" type="email" placeholder="Your email address" required>
                                <button type="submit" aria-label="Subscribe"><i class="ri-arrow-right-line" aria-hidden="true"></i></button>
                            </form>
                            <small>No noise. Unsubscribe anytime.</small>
                        </div>

                        <div class="sidebar-block trending-block">
                            <div class="sidebar-block-heading">
                                <p class="eyebrow">02 / Discover</p>
                                <i class="ri-arrow-down-right-line" aria-hidden="true"></i>
                            </div>
                            <h2>Popular paths</h2>
                            <a href="category.php?category=Sport" class="trend-link"><span>Sport</span></a>
                            <a href="category.php?category=Tech" class="trend-link"><span>Tech</span></a>
                            <a href="category.php?category=Entertainment" class="trend-link"><span>Entertainment</span></a>
                            <a href="category.php?category=Fashion" class="trend-link"><span>Fashion</span></a>
                            <a href="category.php?category=Gaming" class="trend-link"><span>Gaming</span></a>
                        </div>
                    </aside>
                </section>
            <?php else: ?>
                <section class="empty-state">
                    <div class="empty-icon"><i class="ri-quill-pen-line" aria-hidden="true"></i></div>
                    <p class="eyebrow">A quiet corner</p>
                    <h2>No stories here yet.</h2>
                    <p>Be the first curious voice to add something to <?= e($activeCategory) ?>.</p>
                    <a class="button button-dark" href="user_dashboard.php">Write a story <i class="ri-arrow-right-up-line" aria-hidden="true"></i></a>
                </section>
            <?php endif; ?>
        </main>

        <!-- <footer class="site-footer">
            <a class="brand" href="index.php" aria-label="Blogar home">
                <span class="brand-mark" aria-hidden="true">b</span>
                <span>blogar<span class="brand-dot">.</span></span>
            </a>
            <p>For people who still like to wonder.</p>
            <span class="footer-note">© <?= date('Y') ?> Blogar</span>
        </footer> -->
    </div>

    <script>
        const menuToggle = document.querySelector('.menu-toggle');
        const mainNav = document.querySelector('.main-nav');
        const searchToggle = document.querySelector('.search-toggle');
        const searchDrawer = document.querySelector('.search-drawer');

        menuToggle?.addEventListener('click', () => {
            const isOpen = mainNav?.classList.toggle('is-open') ?? false;
            menuToggle.setAttribute('aria-expanded', String(isOpen));
            menuToggle.innerHTML = isOpen ?
                '<i class="ri-close-line" aria-hidden="true"></i>' :
                '<i class="ri-menu-4-line" aria-hidden="true"></i>';
        });

        searchToggle?.addEventListener('click', () => {
            const isOpen = searchDrawer?.classList.toggle('is-open') ?? false;
            searchDrawer?.setAttribute('aria-hidden', String(!isOpen));
            searchToggle.setAttribute('aria-expanded', String(isOpen));
            if (isOpen) {
                document.querySelector('#story-search')?.focus();
            }
        });

        document.querySelectorAll('.save-button').forEach((button) => {
            button.addEventListener('click', () => {
                const isSaved = button.getAttribute('aria-pressed') === 'true';
                button.setAttribute('aria-pressed', String(!isSaved));
                button.innerHTML = isSaved ?
                    '<i class="ri-bookmark-line" aria-hidden="true"></i>' :
                    '<i class="ri-bookmark-fill" aria-hidden="true"></i>';
            });
        });
    </script>
</body>

</html>