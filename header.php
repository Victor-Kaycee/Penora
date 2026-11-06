<?php

require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";
require_once "includes/login_includes/login_view.inc.php";
require_once "includes/dashboard_includes/blog_upload_view.inc.php";

$logged_user = null;

if (isset($_SESSION['user_id'])) {

    $user_id = $_SESSION['user_id'];

    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $pdo->prepare($query);

    $stmt->execute([
        'id' => $user_id
    ]);

    $logged_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/header.css">
</head>

<body>

    <!-- NAVBAR -->
    <div class="container">
        <header class="navbar">

            <a href="index.php" class="logo">
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
                </svg> Penora<span class="brand-dot">.</span>
            </a>

            <!-- <nav class="menu">
            <a href="index.php">Home</a>
            <a href="#">Recent</a>
            <a href="#">Pages</a>
            <a href="#">Business</a>
            <a href="#">Entertainment</a>
        </nav> -->

            <div class="nav-right">

                <?php if ($logged_user): ?>

                    <!-- PROFILE DROPDOWN -->
                    <div class="profile-dropdown">

                        <div class="profile-btn" onclick="toggleDropdown()">

                            <img
                                src="includes/<?php echo $logged_user['file_name']; ?>"
                                alt="Profile">

                            <div class="profile-text">

                                <h4>
                                    <?php echo $logged_user['firstname'] . " " . $logged_user['lastname']; ?>
                                </h4>

                                <span>
                                    @<?php echo $logged_user['middlename']; ?>
                                </span>

                            </div>

                            <i class="ri-arrow-down-s-line"></i>

                        </div>

                        <!-- DROPDOWN -->
                        <div class="dropdown-menu" id="dropdownMenu">

                            <a href="profile.php?id=<?php echo $logged_user['id']; ?>">
                                <i class="ri-user-line"></i>
                                My Profile
                            </a>

                            <a href="user_dashboard.php">
                                <i class="ri-dashboard-line"></i>
                                Dashboard
                            </a>

                            <a href="user_profile.php">
                                <i class="ri-settings-3-line"></i>
                                Settings
                            </a>

                            <a href="logout.php" class="logout">
                                <i class="ri-logout-box-r-line"></i>
                                Logout
                            </a>

                        </div>

                    </div>

                <?php else: ?>

                    <!-- LOGIN BUTTON -->
                    <a href="login.php" class="login-btn">
                        <i class="ri-login-box-line"></i>
                        Login
                    </a>

                <?php endif; ?>

            </div>

        </header>

    </div>
</body>

</html>