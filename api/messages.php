<?php

require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";

if (!isset($_SESSION['user_id'])) {

    die("Login required");
}

$user_id = $_SESSION['user_id'];

$query = "

SELECT 
    user_messages.*,
    users.firstname,
    users.lastname,
    users.file_name

FROM user_messages

JOIN users
ON user_messages.sender_id = users.id

WHERE receiver_id = :receiver_id

ORDER BY user_messages.created_at DESC

";

$stmt = $pdo->prepare($query);

$stmt->execute([
    'receiver_id' => $user_id
]);

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo->prepare("
    UPDATE user_messages
    SET is_read = 1
    WHERE receiver_id = :user_id
");

$update->execute([
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
    <link rel="stylesheet" href="assets/css/message.css">
</head>

<body>

    <div class="layout">
        <?php require 'sidebar.php'; ?>

        <div class="main">
            <?php require 'navbar.php'; ?>

            <div class="container">

                <div class="messages-wrapper">

                    <!-- <h2 class="messages-title">
                        Messages
                    </h2> -->

                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $msg): ?>

                            <div class="messages-card  <?= $msg['is_read'] ? '' : 'unread' ?>">

                                <img
                                    src="includes/<?= htmlspecialchars($msg['file_name']) ?>"
                                    width="50" class="messages-img">

                                <div class="messages-content">

                                    <p>

                                        <strong>
                                            <?= htmlspecialchars($msg['firstname']) ?>
                                            <?= htmlspecialchars($msg['lastname']) ?>
                                        </strong>

                                        <?= htmlspecialchars($msg['message']) ?>

                                    </p>

                                    <small>
                                        <?php
                                        date_default_timezone_set('Africa/Lagos');

                                        $created = strtotime($msg['created_at']);

                                        $diff = time() - $created;

                                        if ($diff < 60) {

                                            echo "Just now";
                                        } elseif ($diff < 3600) {

                                            echo floor($diff / 60) . " min ago";
                                        } elseif ($diff < 86400) {

                                            echo floor($diff / 3600) . " hrs ago";
                                        } else {

                                            echo date("g:i A", $created);
                                        }
                                        ?>
                                    </small>

                                </div>


                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty-messages">

                            <i class="ri-message-3-line"></i>

                            <h3>No messages yet</h3>

                            <p>
                                When users send you messages, they will appear here.
                            </p>

                        </div>

                    <?php endif; ?>


                </div>
            </div>

        </div>


        <script src="assets/js/navbar.js"></script>
        <script src="assets/js/user_dashboard.js"></script>

</body>

</html>