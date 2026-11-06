<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";
require_once './includes/login_includes/login_view.inc.php';
?>
<?php

$user_id = $_SESSION['user_id'];
$query = "SELECT firstname, lastname, file_name FROM users WHERE id = :user_id";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// count Notifications

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


/*
=========================
COUNT UNREAD MESSAGES
=========================
*/

$messageQuery = $pdo->prepare("
    SELECT COUNT(*)
    FROM user_messages
    WHERE receiver_id = :user_id
    AND is_read = 0
");

$messageQuery->execute([
    'user_id' => $_SESSION['user_id']
]);

$unreadMessages = $messageQuery->fetchColumn();

?>


<!-- Navbar -->
<div class="navbar">
    <button class="menu-toggle" id="menuToggle">
        <i class="ri-menu-line"></i>
    </button>
    <div class="nav-left">
        <?php
        $pageTitle = $pageTitle ?? basename($_SERVER['PHP_SELF'], ".php");
        $pageTitle = ucfirst($pageTitle);
        ?>

        <h3 id="page-title">
            <?= $pageTitle ?>
        </h3>

    </div>
    <div class="topbar">
        <div class="search-btn"><i class="ri-search-line"></i> Search...</div>
        <div class="right-s">
            <a href="notifications.php" class="notification-bell">

                <i class="ri-notification-3-fill"></i>

                <?php if ($unreadCount > 0): ?>

                    <span class="notification-count">

                        <?= $unreadCount ?>

                    </span>

                <?php endif; ?>

            </a>
            <a href="messages.php" class="messages-box">
                <i class="ri-mail-unread-fill"></i>
                <?php if ($unreadMessages > 0): ?>

                    <span class="messages-count">
                        <?= $unreadMessages ?>
                    </span>

                <?php endif; ?>
            </a>
            <div class="toggle" onclick="toggleDark()"><i class="ri-moon-fill"></i></div>
            <div class="profile-container">

                <div class="profile-btn" onclick="toggleMenu()">
                    <img src="includes/<?php echo $user['file_name']; ?>" alt="">
                </div>

                <div class="profile-dropdown" id="menu">

                    <div class="dropdown-header">
                        <img src="includes/<?php echo $user['file_name']; ?>" alt="">

                        <div>
                            <h4>
                                <?php echo $user['firstname'] . " " . $user['lastname']; ?>
                            </h4>
                            <span>@username</span>
                        </div>
                    </div>

                    <div class="dropdown-links">

                        <a href="user_profile.php" data-title="Profile">
                            <i class="ri-user-fill"></i>
                            Profile
                        </a>

                        <a href="settings.php" data-title="Settings">
                            <i class="ri-settings-4-fill"></i>
                            Settings
                        </a>

                        <a href="index.php" data-title="Homepage">
                            <i class="ri-question-fill"></i>
                            Homepage
                        </a>

                        <a href="logout.php" class="logout" id="logoutBtn">
                            <i class="ri-logout-box-fill"></i>
                            Sign Out
                        </a>

                    </div>

                </div>

            </div>

        </div>
    </div>

</div>