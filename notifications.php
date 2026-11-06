<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}


// check login
if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

$user_id = $_SESSION['user_id'];

// fetch notifications
$query = "SELECT notifications.*, users.firstname, users.lastname, users.file_name
FROM notifications
JOIN users ON notifications.sender_id = users.id
WHERE notifications.user_id = :user_id
ORDER BY notifications.created_at DESC";

$stmt = $pdo->prepare($query);

$stmt->execute([
    'user_id' => $user_id
]);

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<?php

$markRead = $pdo->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE user_id = :user_id
");

$markRead->execute([
    'user_id' => $_SESSION['user_id']
]);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogar | Dashboard Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
</head>

<body>

    <div class="layout">

        <?php require 'sidebar.php'; ?>

        <div class="main">
            <?php require 'navbar.php'; ?>

            <div class="container">
                <div class="notification-wrapper">

                    <!-- <h2 class="notification-title">
                    Notifications
                </h2> -->

                    <?php if (count($notifications) > 0): ?>

                        <?php foreach ($notifications as $note): ?>

                            <div class="notification-card <?= $note['is_read'] ? '' : 'unread' ?>">

                                <img
                                    src="includes/<?= htmlspecialchars($note['file_name']) ?>"
                                    class="notification-img">

                                <div class="notification-content">

                                    <p>

                                        <strong>
                                            <?= htmlspecialchars($note['firstname']) ?>
                                            <?= htmlspecialchars($note['lastname']) ?>
                                        </strong>

                                        <?= htmlspecialchars($note['message']) ?>

                                    </p>

                                    <small>
                                        <?= date('j M Y', strtotime($note['created_at'])) ?>

                                    </small>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty-notification">

                            <i class="ri-notification-off-line" style="font-size:40px;"></i>

                            <h3>No notifications yet</h3>

                            <p>
                                When someone likes your blog post,
                                notifications will appear here.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/user_dashboard.js"></script>
</body>

</html>