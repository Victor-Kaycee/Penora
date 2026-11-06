<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";
require_once "includes/update_user_profile.inc.php";

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
    <title>Blogar | Edit User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/user_profile_edit.css">
</head>

<body>
    <?php require 'sidebar.php'; ?>

    <!-- Main -->
    <div class="main">
        <!-- Navbar -->
        <?php require 'navbar.php'; ?>

        <?php

        $query = "SELECT firstname, middlename, lastname, email, file_name, phone_no, location, bio, state FROM users WHERE id = :user_id";

        $stmt = $pdo->prepare($query);

        $stmt->execute([
            'user_id' => $user_id
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>


        <div class="container">
            <section class="card" style="margin-top:30px;">

                <div class="profile-right-header">

                    <h3>Edit Profile</h3>

                    <div class="online-dot"></div>

                </div>

                <form class="edit-profile-form" action="includes/update_user_profile.inc.php" method="post" enctype="multipart/form-data">

                    <div class="edit-grid">

                        <div class="form-group profile-upload-group">

                            <div class="edit-avatar">
                                <img src="includes/<?= htmlspecialchars($user['file_name']) ?>" id="previewImage">
                            </div>

                            <label for="profileUpload" class="upload-btn">
                                <i class="ri-image-edit-line"></i>
                                Change Photo
                            </label>

                            <input type="file" name="profileimg" id="profileUpload" hidden>

                        </div>

                        <div class="form-group">
                            <label>Firstname</label>
                            <input type="text" name="firstname" placeholder="Enter your first name" value="<?= htmlspecialchars($user['firstname']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Middlename</label>
                            <input type="text" name="middlename" placeholder="Enter your middle name" value="<?= htmlspecialchars($user['middlename']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Lastname</label>
                            <input type="text" name="lastname" placeholder="Enter your last name" value="<?= htmlspecialchars($user['lastname']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="Enter your email" value="<?= htmlspecialchars($user['email']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Mobile No</label>
                            <input type="text" name="phone_no" placeholder="+234" value="<?= htmlspecialchars($user['phone_no']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="location" placeholder="Address" value="<?= htmlspecialchars($user['location']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Location</label>
                            <select name="state">

                                <option value="">Select State</option>

                                <?php foreach ($states as $state): ?>

                                    <option
                                        value="<?= $state ?>"
                                        <?= ($user['state'] == $state) ? 'selected' : '' ?>>

                                        <?= $state ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label>Bio</label>
                            <textarea name="bio" placeholder="Tell people about yourself..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                        </div>


                    </div>

                    <button type="submit" class="save-btn">
                        <i class="ri-save-line"></i>
                        Save Changes
                    </button>

                </form>

            </section>


        </div>
    </div>

    <script src="assets/js/user_profile_edit.js"></script>
</body>

</html>