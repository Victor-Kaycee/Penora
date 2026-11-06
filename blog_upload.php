<?php
require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";
require_once "includes/dashboard_includes/blog_upload_view.inc.php";

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
    <title>Blogar | Upload-Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="./assets/css/blog_upload.css">
</head>

<body>

    <div class="layout">
        <?php require 'sidebar.php'; ?>

        <div class="main">
            <?php $pageTitle = "Upload Blog";
            include "navbar.php"; ?>


            <div class="container">
                <form action="includes/blog_upload.inc.php" class="blog-post-form" method="POST" enctype="multipart/form-data">
                    <div class="blog-first">
                        <?php
                        postInputTop();
                        ?>
                    </div>
                    <?php
                    postInputText()
                    ?>
                    <input type="file" name="post_img">
                    <button>Upload Blog</button>
                </form>
                <?php
                check_post_errors();
                ?>
                <?php if (!empty($errors["invalid_image"])): ?>
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <script>
                        Swal.fire({
                            icon: "error",
                            title: "Invalid file!",
                            text: "<?php echo $errors['invalid_image']; ?>"
                        });
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/user_dashboard.js"></script>
</body>

</html>