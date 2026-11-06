<?php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category = $_POST["category"];
    $title = $_POST["title"];
    $report = $_POST["report"];
    $postImg = $_FILES["post_img"];

    try {

        require_once "dbh.inc.php";
        require_once "dashboard_includes/blog_upload_model.inc.php";
        require_once "dashboard_includes/blog_upload_contr.inc.php";

        $errors = [];

        if (is_input_empty($category, $title, $report, $postImg)) {
            $errors["input_empty"] = '
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Upload failed",
                text: "Input all fields!"
            });
            </script>';
        }


        require_once "config_session.inc.php";

        $userId = $_SESSION['user_id'];

        if ($errors) {
            $_SESSION["errors_post"] = $errors;

            $postData = [
                "category" => $category,
                "title" => $title,
                "report" => $report
            ];

            $_SESSION["post_data"] = $postData;

            header("Location: ../blog_upload.php");
            die();
        }

        create_post($pdo, $userId, $category, $title, $report, $postImg);

        header("Location: ../blog_upload.php?upload=success");

        $pdo = null;
        $stmt = null;

        die();
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
} else {
    header("Location: ../blog_upload.php");
}
