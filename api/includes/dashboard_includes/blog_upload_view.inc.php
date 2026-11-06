<?php

declare(strict_types=1);

function postInputTop()
{

    if (isset($_SESSION["post_data"]["category"])) {
        echo ' <input type="text" name="category" placeholder="Category(e.g, sports, entertainment, business)" value="' . $_SESSION["post_data"]["category"] . '">';
    } else {
        echo ' <input type="text" name="category" placeholder="Category(e.g, sports, entertainment, business)">';
    }

    if (isset($_SESSION["post_data"]["title"])) {
        echo '<input type="text" name="title" placeholder="Title" value="' . $_SESSION["post_data"]["title"] . '">';
    } else {
        echo '<input type="text" name="title" placeholder="Title">';
    }
}

function postInputText()
{

    if (isset($_SESSION["post_data"]["report"])) {
        echo '<textarea name="report" placeholder="Report" value="' . $_SESSION["post_data"]["report"] . '"></textarea>';
    } else {
        echo '<textarea name="report" placeholder="Report"></textarea>';
    }
}


function check_post_errors()
{
    if (isset($_SESSION["errors_post"])) {
        $errors = $_SESSION["errors_post"];

        echo "<br>";

        foreach ($errors as $error) {
            echo "<p class='form-error'>" . $error . "</p>";
        }

        unset($_SESSION["errors_post"]);
    } else if (isset($_GET["upload"]) && $_GET["upload"] === "success") {
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        Swal.fire({
        title: "Blog Uploaded Successfully",
        icon: "success",
        draggable: false
        }).then(() => {
        window.location.href = "user_dashboard.php";
        });
        </script>';
    }
}

function blog_view($pdo)
{
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        $query = "SELECT * FROM posts WHERE users_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            foreach ($results as $row) {
                echo "<h2>" . htmlspecialchars($row['title']) . "</h2>";
                echo "<p>" . htmlspecialchars($row['category']) . "</p>";
            }
        } else {
            echo "You have not posted any blog yet. Please upload a blog.";
        }

    } else {
        echo "Please log in to view your blogs.";
    }
}


function getLikeCount($pdo, $post_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    return $stmt->fetchColumn();
}