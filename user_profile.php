<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogar | User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/user_profile.css">
</head>

<body>
    <?php require 'sidebar.php'; ?>

    <!-- Main -->
    <div class="main">
        <!-- Navbar -->
        <?php $pageTitle = "Profile";
        include "navbar.php"; ?>


        <div class="container">
            <?php


            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];

                $query = " SELECT users.firstname, users.middlename, users.lastname, users.email, users.file_name, users.phone_no, users.location, users.state, users.bio, COUNT(posts.id) AS total_posts FROM users LEFT JOIN posts ON posts.users_id = users.id WHERE users.id = :user_id GROUP BY users.id";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['user_id' => $user_id]);

                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);


                if (count($results) > 0) {
                    foreach ($results as $row) {
                        $user_id = $_SESSION['user_id'];
                        echo '
                <div class="blog_profile_div">
                <!-- PAGE HEADER -->


                <!-- PROFILE GRID -->

                <div class="profile-grid">

                    <!-- LEFT -->

                    <div class="card profile-left">
                     <div class="profile-avatar">

                            <img src="includes/' . htmlspecialchars($row['file_name']) . '" id="profileImage" onclick="openImage(this.src)" style="cursor:pointer;" alt="avatar">

                        </div>

                        <h2>' . htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . '</h2>

                        <span class="premium">
                            User
                        </span>

                       

                    </div>

                    <!-- RIGHT -->

                    <div class="card">

                        <div class="profile-right-header">

                            <h3>Bio & Other Details</h3>

                            <div class="online-dot"></div>

                        </div>

                        <div class="details-grid">

                            <div class="detail-box">
                                <h5>Firstname</h5>
                                <p>' . htmlspecialchars($row['firstname']) . '</p>
                            </div>

                            <div class="detail-box">
                                <h5>Middlename</h5>
                                <p>' . htmlspecialchars($row['middlename']) . '</p>
                            </div>

                            <div class="detail-box">
                                <h5>Lastname</h5>
                                <p>' . htmlspecialchars($row['lastname']) . '</p>
                            </div>

                            <div class="detail-box">
                                <h5>Email</h5>
                                <p>' . htmlspecialchars($row['email']) . '</p>
                            </div>

                            <div class="detail-box">
                                <h5>Location</h5>
                                <p>' . htmlspecialchars($row['location']) . "," . " " . htmlspecialchars($row['state']) . '</p>
                            </div>

                            <div class="detail-box">
                                <h5>Phone No</h5>
                                <p>
                                    ' . htmlspecialchars($row['phone_no']) . '
                                </p>
                            </div>

                        </div>

                        <div class="detail-box text_area">
                                <h5>Bio</h5>
                                <p>' . htmlspecialchars($row['bio']) . '</p>
                        </div>

                    </div>

                </div>

                <!-- SOCIALS -->

                <div class="card social-card">

                    <h3>Social Media</h3>

                    <div class="social-icons">

                        <a href="#">
                            <i class="ri-instagram-line"></i>
                        </a>

                        <a href="#">
                            <i class="ri-twitter-x-line"></i>
                        </a>

                        <a href="#">
                            <i class="ri-linkedin-box-line"></i>
                        </a>

                        <a href="#">
                            <i class="ri-github-line"></i>
                        </a>

                        <a href="#">
                            <i class="ri-dribbble-line"></i>
                        </a>

                    </div>

                </div>
                <div class="edit_user_profile">
                    <a href="settings.php">Edit Profile</a>
                </div>
            </div>
            ';
                    }
                } else {
                    echo '<h4>You Are Not Logged In!</h4>';
                }
            } else {
                echo "Please log in to view your blogs.";
            }



            ?>

        </div>

    </div>

    <div id="imageModal" class="image-modal">
        <span class="close-btn" onclick="closeImage()">&times;</span>

        <img id="modalImg" class="modal-content">
    </div>




    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/share.js"></script>
    <script src="assets/js/profilepic.js"></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/user_dashboard.js"></script>
</body>

</html>