<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<?php

$user_id = $_SESSION['user_id'];
$query = "SELECT firstname, lastname, email, file_name FROM users WHERE id = :user_id";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar" aria-label="Primary">
    <!-- Brand -->
    <div class="sidebar-head">
        <a href="index.php" class="brand">
            <span class="brand-mark">
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
            </span>
            <span class="brand-text">Penora<span class="brand-dot">.</span></span>
        </a>
        <button class="icon-btn close-btnn" id="closeBtn" aria-label="Close menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>

    <!-- Nav -->
    <nav class="nav">
        <p class="nav-label">Menu</p>
        <a class="nav-item <?= $currentPage == 'user_dashboard.php' ? 'active' : '' ?>" href="user_dashboard.php" data-tip="Dashboard">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7" />
                <rect x="14" y="3" width="7" height="7" />
                <rect x="14" y="14" width="7" height="7" />
                <rect x="3" y="14" width="7" height="7" />
            </svg>
            <span class="nav-text">Dashboard</span>
        </a>
        <a class="nav-item <?= $currentPage == 'analytics.php' ? 'active' : '' ?>" href="analytics.php" data-tip="Analytics">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
            </svg>
            <span class="nav-text">Analytics</span>
        </a>
        <a class="nav-item <?= $currentPage == 'user_blogs.php' ? 'active' : '' ?>" href="user_blogs.php" data-tip="My Blogs">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" y1="13" x2="8" y2="13" />
                <line x1="16" y1="17" x2="8" y2="17" />
            </svg>
            <span class="nav-text">My Blogs</span>
        </a>
        <a class="nav-item <?= $currentPage == 'notifications.php' ? 'active' : '' ?>" href="notifications.php" data-tip="Notifications">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            <span class="nav-text">Notifications</span>
        </a>
        <a class="nav-item <?= $currentPage == 'messages.php' ? 'active' : '' ?>" href="messages.php" data-tip="Messages">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 3H21C21.55 3 22 3.45 22 4V20C22 20.55 21.55 21 21 21H3C2.45 21 2 20.55 2 20V4C2 3.45 2.45 3 3 3ZM20 7.24L12.07 14.34L4 7.22V19H20V7.24ZM4.51 5L12.06 11.66L19.5 5H4.51Z" />
            </svg>
            <span class="nav-text">Messages</span>
        </a>
        <a class="nav-item <?= $currentPage == 'settings.php' ? 'active' : '' ?>" href="settings.php" data-tip="Settings">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3" />
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
            </svg>
            <span class="nav-text">Settings</span>
        </a>
    </nav>

    <!-- Pro Upgrade -->
    <!-- <div class="pro-card">
        <span class="pro-pill">✦ Pro</span>
        <p class="pro-title">Unlock advanced analytics</p>
        <p class="pro-sub">Scheduled posts, AI tools, deeper insights.</p>
        <button class="pro-btn">Upgrade now</button>
    </div> -->

    <!-- Footer -->
    <div class="sidebar-footer">
        <div class="user">
            <div class="avatar"><img src="includes/<?php echo $user['file_name']; ?>" alt=""><span class="status"></span></div>
            <div class="user-meta">
                <p class="user-name"><?php echo $user['firstname'] . " " . $user['lastname']; ?></p>
                <p class="user-email"><?php echo $user['email']; ?></p>
            </div>
            <button class="logout-btn" id="logoutBtn" aria-label="Logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
            </button>
        </div>
        <button class="collapse-btn" id="collapseBtn" aria-label="Collapse sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="11 17 6 12 11 7" />
                <polyline points="18 17 13 12 18 7" />
            </svg>
            <span>Collapse</span>
        </button>
    </div>
</aside>