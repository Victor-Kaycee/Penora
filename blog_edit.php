<?php

/**
 * edit.php — Edit an existing blog post
 *
 * Assumes:
 *  - PDO connection available as $pdo from db.php (adjust the require path below)
 *  - Session already started elsewhere and $_SESSION['user_id'] holds the logged-in user's id
 *  - posts table: id, category, title, content, image, user_id
 *  - Uploaded images are stored under /uploads and the `image` column holds the filename only
 *
 * NOTE: this file loads its dependencies exactly once (require_once) and never
 * re-queries the user row after the initial fetch, to avoid the duplicate-query
 * $user overwrite bug seen in settings.php.
 */


require_once 'includes/dbh.inc.php';
require_once "includes/config_session.inc.php";

$categories = ['Tech', 'Sport', 'Fitness', 'Food', 'Business', 'Health', 'Entertainment', 'Gaming', 'Fashion', 'Automobile', 'Animals'];

$errors = [];
$success = false;

// ---- Auth guard ----
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$currentUserId = (int) $_SESSION['user_id'];

// ---- Resolve post id ----
$postId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($postId <= 0) {
    http_response_code(400);
    exit('Missing or invalid post id.');
}

// ---- Fetch the post (once) ----
$stmt = $pdo->prepare('SELECT id, category, title, report, post_img, created_at, users_id FROM posts WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    exit('Post not found.');
}

if ((int) $post['users_id'] !== $currentUserId) {
    http_response_code(403);
    exit('You do not have permission to edit this post.');
}

// Uploads live in /includes/uploads, and the DB stores the value as "uploads/filename.ext"
define('UPLOAD_DIR_DISK', __DIR__ . '/includes/uploads');
define('UPLOAD_DB_PREFIX', 'uploads/');

// ---- Handle update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $category = trim($_POST['category'] ?? '');
    $title    = trim($_POST['title'] ?? '');
    $report  = trim($_POST['report'] ?? '');

    if (!in_array($category, $categories, true)) {
        $errors['category'] = 'Please choose a valid category.';
    }
    if ($title === '') {
        $errors['title'] = 'Title is required.';
    } elseif (mb_strlen($title) > 120) {
        $errors['title'] = 'Title must be 120 characters or fewer.';
    }
    if ($report === '') {
        $errors['report'] = 'Content cannot be empty.';
    }

    $newImageValue = $post['post_img']; // keep existing (e.g. "uploads/foo.jpg") by default

    // ---- Optional new image ----
    if (!empty($_FILES['post_img']['name'])) {
        $file = $_FILES['post_img'];

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $maxBytes = 8 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['post_img'] = 'There was a problem uploading the image.';
        } elseif ($file['size'] > $maxBytes) {
            $errors['post_img'] = 'Image is too large. Max 8MB.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!isset($allowedTypes[$mime])) {
                $errors['post_img'] = 'Unsupported file type. Use JPG, PNG, WEBP or GIF.';
            } else {
                $ext = $allowedTypes[$mime];
                $newFilename = time() . "_" . bin2hex(random_bytes(5)) . "." . $ext;

                if (!is_dir(UPLOAD_DIR_DISK)) {
                    mkdir(UPLOAD_DIR_DISK, 0755, true);
                }

                $destination = UPLOAD_DIR_DISK . '/' . $newFilename;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $errors['post_img'] = 'Could not save the uploaded image.';
                } else {
                    // this is what gets written to the database
                    $newImageValue = UPLOAD_DB_PREFIX . $newFilename;
                }
            }
        }
    }

    // ---- Persist ----
    if (empty($errors)) {
        $oldImageValue = $post['post_img'];

        $update = $pdo->prepare(
            'UPDATE posts SET category = :category, title = :title, report = :report, post_img = :post_img WHERE id = :id AND users_id = :users_id'
        );
        $update->execute([
            ':category' => $category,
            ':title'    => $title,
            ':report'  => $report,
            ':post_img'    => $newImageValue,
            ':id'       => $postId,
            ':users_id'  => $currentUserId,
        ]);

        // Remove the old file only after a successful update and only if it changed
        if ($newImageValue !== $oldImageValue && $oldImageValue) {
            $oldFilename = basename($oldImageValue); // strip any "uploads/" prefix
            $oldPath = UPLOAD_DIR_DISK . '/' . $oldFilename;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Refresh local copy for re-display, without re-querying the DB
        $post['category'] = $category;
        $post['title']    = $title;
        $post['report']  = $report;
        $post['post_img']    = $newImageValue;

        $success = true;
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post — Blogar</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/blog_edit.css">
</head>

<body>

    <div class="layout">

        <?php require 'sidebar.php'; ?>

        <div class="main">

            <?php $pageTitle = "Edit Blog";
            include "navbar.php"; ?>

            <main class="container">

                <?php if ($success): ?>
                    <div class="banner banner-success">Post updated successfully.</div>
                <?php elseif (!empty($errors)): ?>
                    <div class="banner banner-error">Please fix the highlighted fields below.</div>
                <?php endif; ?>

                <form class="editor" method="post" enctype="multipart/form-data" novalidate>

                    <div class="field <?= isset($errors['category']) ? 'invalid' : '' ?>">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="" disabled <?= $post['category'] === '' ? 'selected' : '' ?>>Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat) ?>" <?= $post['category'] === $cat ? 'selected' : '' ?>>
                                    <?= e(ucfirst($cat)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category'])): ?>
                            <span class="error-msg"><?= e($errors['category']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="field <?= isset($errors['title']) ? 'invalid' : '' ?>">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" maxlength="120" required
                            value="<?= e($post['title']) ?>">
                        <?php if (isset($errors['title'])): ?>
                            <span class="error-msg"><?= e($errors['title']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="field <?= isset($errors['report']) ? 'invalid' : '' ?>">
                        <label for="report">Content</label>
                        <textarea id="report" name="report" rows="14" required><?= e($post['report']) ?></textarea>
                        <?php if (isset($errors['report'])): ?>
                            <span class="error-msg"><?= e($errors['report']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="field <?= isset($errors['post_img']) ? 'invalid' : '' ?>">
                        <label for="post_img">Cover image</label>

                        <?php if (!empty($post['post_img'])): ?>
                            <div class="current-image">
                                <img src="includes/<?= e($post['post_img']) ?>" alt="Current cover image">
                                <span class="current-image-label">Current image — choose a new file to replace it</span>
                            </div>
                        <?php endif; ?>

                        <input type="file" id="post_img" name="post_img" accept="image/png, image/jpeg, image/webp, image/gif">
                        <span class="field-hint">PNG, JPG, WEBP or GIF · up to 8MB</span>
                        <?php if (isset($errors['post_img'])): ?>
                            <span class="error-msg"><?= e($errors['post_img']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <a class="btn btn-ghost" href="user_dashboard.php">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>

                </form>

            </main>

        </div>

    </div>

    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/user_dashboard.js"></script>

</body>

</html>